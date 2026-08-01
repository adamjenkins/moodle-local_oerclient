<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Preview an Exchange resource and import it. See DESIGN.md §1 flow 2/3.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oerclient\local\exchange_client;
use local_oerclient\local\import_manager;

require(__DIR__ . '/../../config.php');
require_login();
if (isguestuser()) {
    throw new moodle_exception('noguest');
}

$id = required_param('id', PARAM_INT);

$context = context_system::instance();

$PAGE->set_url('/local/oerclient/resource_preview.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

$exchangeurl = get_config('local_oerclient', 'exchangeurl');
$sitetoken = get_config('local_oerclient', 'sitetoken');
if (empty($exchangeurl) || empty($sitetoken)) {
    throw new moodle_exception('error_notregistered', 'local_oerclient');
}

$client = new exchange_client($exchangeurl);
try {
    $resource = $client->call('local_oerexchange_get_resource', ['resourceid' => $id], $sitetoken);
} catch (\Throwable $e) {
    // The Exchange refuses anything not 'published', so this is what an author
    // hiding or deleting a resource looks like from here. Say that plainly
    // instead of surfacing the far side's raw exception text, which for this
    // very common case is a bare "Not found.".
    $PAGE->set_title(get_string('error_resourcegone', 'local_oerclient'));
    $PAGE->set_heading(get_string('error_resourcegone', 'local_oerclient'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error_resourcegone', 'local_oerclient'), 'info');
    echo html_writer::link(
        new moodle_url('/local/oerclient/browse.php'),
        get_string('browseexchange', 'local_oerclient'),
        ['class' => 'btn btn-primary']
    );
    echo $OUTPUT->footer();
    exit;
}

// Deliberately raw: both of these run the value through format_string()
// themselves on every supported core (lib/pagelib.php — set_title() calls
// format_string() then strip_tags(); set_heading()'s $applyformatting
// parameter defaults to true and calls format_string()). Pre-formatting here
// would filter the string twice and double-escape any ampersand in it.
$PAGE->set_title($resource['title']);
$PAGE->set_heading($resource['title']);

global $DB, $USER;

if ($resource['type'] !== 'data' && data_submitted() && confirm_sesskey() && optional_param('doimport', 0, PARAM_INT)) {
    $targetcourseid = optional_param('targetcourseid', 0, PARAM_INT);

    import_manager::require_import_capability($resource['type'], $targetcourseid ?: null);

    $importresult = import_manager::import($resource, $USER->id, $targetcourseid ?: null);

    try {
        $client->call('local_oerexchange_record_import', [
            'resourceid' => $resource['id'],
            'versionid' => $resource['versionid'],
        ], $sitetoken);
    } catch (\Throwable $e) {
        // Non-fatal: the import already succeeded locally.
        debugging('local_oerclient: record_import failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    redirect(new moodle_url('/local/oerclient/import_result.php', [
        'courseid' => $importresult['courseid'],
        'checklist' => base64_encode(json_encode($importresult['checklist'])),
    ]));
}

echo $OUTPUT->header();

// Cover image, when the Exchange supplied one — a plain inline <img>, the
// same shape the Exchange's own resource.php draws. No placeholder when
// there is none: this is a single-resource page, not a grid, so there is no
// row-alignment reason to show an empty panel. PARAM_URL rejects
// javascript:/data: schemes — this URL came over the wire from the Exchange.
$coverurl = clean_param((string) ($resource['coverimageurl'] ?? ''), PARAM_URL);
if ($coverurl !== '') {
    echo html_writer::empty_tag('img', [
        'src' => $coverurl,
        // Run the title through format_string() so a multilang title
        // collapses to the viewer's language here too, then decode it back to
        // plain text: html_writer escapes attribute values itself
        // (html_writer::attribute() calls s()), so handing it already-escaped
        // output renders an ampersand as the literal "&amp;". The narrower
        // format_string(..., 'escape' => false) is not enough — it suppresses
        // only format_string's own ampersand escaping, not clean_text()'s,
        // and does nothing for a title stored with pre-encoded entities.
        'alt' => get_string(
            'thumbnailalt',
            'local_oerclient',
            html_entity_decode(
                format_string($resource['title'], true, ['context' => $context]),
                ENT_QUOTES,
                'UTF-8'
            )
        ),
        'class' => 'img-fluid mb-3', 'style' => 'max-height:200px;',
    ]);
}

if (!empty($resource['creatorname'])) {
    // Use format_string(), not s(): a creator name can carry multilang markup,
    // and format_string() escapes on the way out — re-wrapping it in s()
    // would double-escape. It lands in link text / element content, which
    // html_writer does not escape, so no 'escape' => false here.
    $creatorlabel = format_string($resource['creatorname'], true, ['context' => $context]);
    // PARAM_URL rejects javascript:/data: schemes — html_writer only
    // attribute-escapes, and this URL came from the Exchange.
    $profileurl = clean_param((string) ($resource['creatorprofileurl'] ?? ''), PARAM_URL);
    if ($profileurl !== '') {
        $creatorlabel = html_writer::link($profileurl, $creatorlabel);
    }
    echo html_writer::tag('p', get_string('createdby', 'local_oerclient', $creatorlabel));
}
// Licence shortname stays s()-escaped, NOT format_string()'d: it is an
// identifier from the Exchange's accepted-licence list ('cc-sa-4.0'), not
// authored display text — nobody writes a multilang span in one, and it is
// upper-cased here precisely because it is read as a code. Matches the
// Exchange's own resource.php, which also uses s() for it.
echo html_writer::tag('p', get_string(
    'licenselabel',
    'local_oerclient',
    s(\core_text::strtoupper($resource['licenseshortname']))
));
// FORMAT_HTML, with cleaning left ON. Two reasons, and they pull the same way:
// FORMAT_PLAIN s()-escapes before any filter runs (lib/classes/formatting.php),
// so multilang spans could never collapse and URLs were never auto-linked; and
// this text arrived over a web service from a REMOTE Exchange site, so it is
// untrusted and Moodle's HTML purifier must run over it. Never pass
// 'noclean' => true here. The Exchange stores this column as TYPE="text" with
// no companion format column and fills it from a client's course summary
// (HTML), so FORMAT_HTML is also what the real data actually is.
//
// 'blanktarget' => true because the purifier permits <a href> and <img src>:
// stored XSS is blocked, but a hostile or compromised Exchange could still
// place a link in a summary. Forcing target="_blank" makes core add
// rel="noreferrer" (lib/weblib.php), so such a link cannot see or reach this
// site's page. It also keeps the visitor's own session on the client site.
echo html_writer::tag(
    'div',
    format_text($resource['summary'] ?? '', FORMAT_HTML, [
        'context' => $context,
        'blanktarget' => true,
    ]),
    ['class' => 'mb-3']
);

$requiredplugins = json_decode($resource['requiredplugins'] ?? '[]', true) ?: [];
if (!empty($requiredplugins)) {
    echo $OUTPUT->heading(get_string('requiredplugins', 'local_oerclient'), 4);
    echo html_writer::start_tag('ul');
    foreach ($requiredplugins as $plugin) {
        $installed = \core_plugin_manager::instance()->get_plugin_info($plugin['type'] . '_' . $plugin['name']) !== null;
        // A frankenstyle component name ('mod_quiz'), not authored display
        // text — s(), never format_string().
        $label = $plugin['type'] . '_' . $plugin['name'];
        $badge = $installed
            ? html_writer::tag('span', get_string('plugininstalled', 'local_oerclient'), ['class' => 'badge bg-success ms-2'])
            : html_writer::tag('span', get_string('pluginmissing', 'local_oerclient'), ['class' => 'badge bg-warning ms-2']);
        echo html_writer::tag('li', s($label) . $badge);
    }
    echo html_writer::end_tag('ul');
}

$structure = json_decode($resource['structurejson'] ?? '', true);
echo $OUTPUT->heading(get_string('structurepreview', 'local_oerclient'), 4);
if ($structure && !empty($structure['sections'])) {
    echo html_writer::start_tag('ul');
    foreach ($structure['sections'] as $section) {
        echo html_writer::start_tag('li');
        $title = $section['title'] ?? '';
        // Unnamed topics/weekly sections store just the bare section number
        // in the backup XML (Moodle applies "Topic N"/"Week N" only at
        // display time in core, not in the backup) — show that number in a
        // readable label instead of leaving it as a bare digit. That branch
        // interpolates a digits-only value into a site-owned lang string, so
        // it needs neither escaping nor filtering; the real-title branch is
        // an author's section name and goes through format_string().
        echo ctype_digit((string) $title)
            ? get_string('sectionnumber', 'local_oerclient', $title)
            : format_string($title, true, ['context' => $context]);
        if (!empty($section['activities'])) {
            echo html_writer::start_tag('ul');
            foreach ($section['activities'] as $activity) {
                // The modulename value is the module's plugin name from the
                // backup ('quiz'), so it stays s()-escaped; the title is
                // the author's text and is filtered.
                echo html_writer::tag(
                    'li',
                    s($activity['modulename']) . ': '
                        . format_string($activity['title'], true, ['context' => $context])
                );
            }
            echo html_writer::end_tag('ul');
        }
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
}

// The canonical public page for this resource lives on the Exchange, and
// until now nothing here linked to it — so there was no way to reach the
// page that carries the share buttons, reviews and author profile.
echo html_writer::tag(
    'p',
    html_writer::link(
        rtrim($exchangeurl, '/') . '/local/oerexchange/resource.php?id=' . (int) $resource['id'],
        get_string('viewonexchange', 'local_oerclient'),
        ['class' => 'btn btn-outline-secondary btn-sm', 'target' => '_blank', 'rel' => 'noopener noreferrer']
    )
);

if ($resource['type'] === 'data') {
    // Same distrust as creatorprofileurl: the Exchange chose this URL.
    $downloadurl = clean_param((string) ($resource['downloadurl'] ?? ''), PARAM_URL);
    if ($downloadurl !== '') {
        echo html_writer::link(
            $downloadurl,
            get_string('downloadbutton', 'local_oerclient'),
            ['class' => 'btn btn-success']
        );
    }
} else {
    echo $OUTPUT->heading(get_string('importheading', 'local_oerclient'), 4);
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/oerclient/resource_preview.php', ['id' => $id]),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'doimport', 'value' => 1]);

    if ($resource['type'] === 'activity') {
        $courses = enrol_get_users_courses($USER->id, true, null, 'fullname');
        $options = [];
        foreach ($courses as $c) {
            $coursecontext = context_course::instance($c->id);
            if (has_capability('local/oerclient:import', $coursecontext)) {
                // The html_writer::select() helper does NOT escape option
                // label text (select_option() passes it to html_writer::tag() —
                // it escapes optgroup labels only), so a raw course fullname
                // here was both unescaped and unfiltered. format_string()
                // fixes both, in the course's own context.
                $options[$c->id] = format_string($c->fullname, true, ['context' => $coursecontext]);
            }
        }
        if (empty($options)) {
            echo html_writer::tag('p', get_string('error_notargetcourses', 'local_oerclient'));
        } else {
            echo html_writer::tag('label', get_string('importtargetcourse', 'local_oerclient'));
            echo html_writer::select($options, 'targetcourseid', '', false, ['class' => 'form-select mb-2']);
        }
    }

    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('importbutton', 'local_oerclient'), 'class' => 'btn btn-success',
    ]);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
