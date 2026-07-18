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

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/local/oerclient/resource_preview.php', ['id' => $id]);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

$exchangeurl = get_config('local_oerclient', 'exchangeurl');
$sitetoken = get_config('local_oerclient', 'sitetoken');
if (empty($exchangeurl) || empty($sitetoken)) {
    throw new moodle_exception('error_notregistered', 'local_oerclient');
}

$client = new exchange_client($exchangeurl);
$resource = $client->call('local_oerexchange_get_resource', ['resourceid' => $id], $sitetoken);

$PAGE->set_title($resource['title']);
$PAGE->set_heading($resource['title']);

global $DB, $USER;

if (data_submitted() && confirm_sesskey() && optional_param('doimport', 0, PARAM_INT)) {
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

echo html_writer::tag('p', get_string('licenselabel', 'local_oerclient', s($resource['licenseshortname'])));
echo html_writer::tag('div', format_text($resource['summary'] ?? '', FORMAT_PLAIN), ['class' => 'mb-3']);

$requiredplugins = json_decode($resource['requiredplugins'] ?? '[]', true) ?: [];
if (!empty($requiredplugins)) {
    echo $OUTPUT->heading(get_string('requiredplugins', 'local_oerclient'), 4);
    echo html_writer::start_tag('ul');
    foreach ($requiredplugins as $plugin) {
        $installed = \core_plugin_manager::instance()->get_plugin_info($plugin['type'] . '_' . $plugin['name']) !== null;
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
        // readable label instead of leaving it as a bare digit.
        echo ctype_digit((string) $title)
            ? s(get_string('sectionnumber', 'local_oerclient', $title))
            : s($title);
        if (!empty($section['activities'])) {
            echo html_writer::start_tag('ul');
            foreach ($section['activities'] as $activity) {
                echo html_writer::tag('li', s($activity['modulename']) . ': ' . s($activity['title']));
            }
            echo html_writer::end_tag('ul');
        }
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
}

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
        if (has_capability('local/oerclient:import', context_course::instance($c->id))) {
            $options[$c->id] = $c->fullname;
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

echo $OUTPUT->footer();
