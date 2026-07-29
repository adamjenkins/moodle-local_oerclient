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
 * Browse the Exchange catalogue from inside this Moodle site.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oerclient\local\exchange_client;

require(__DIR__ . '/../../config.php');
require_login();
if (isguestuser()) {
    throw new moodle_exception('noguest');
}

$query = optional_param('q', '', PARAM_TEXT);
$type = optional_param('type', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

$PAGE->set_url('/local/oerclient/browse.php', ['q' => $query, 'type' => $type, 'page' => $page]);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browseexchange', 'local_oerclient'));
$PAGE->set_heading(get_string('browseexchange', 'local_oerclient'));

echo $OUTPUT->header();

$exchangeurl = get_config('local_oerclient', 'exchangeurl');
$sitetoken = get_config('local_oerclient', 'sitetoken');

if (empty($exchangeurl) || empty($sitetoken)) {
    echo $OUTPUT->notification(get_string('error_notregistered', 'local_oerclient'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/oerclient/browse.php')]);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'q', 'value' => $query, 'class' => 'form-control d-inline w-auto',
    'aria-label' => get_string('searchplaceholder', 'local_oerclient'),
]);
echo html_writer::tag(
    'label',
    get_string('filterbytype', 'local_oerclient'),
    ['for' => 'oerclient-filter-type', 'class' => 'ms-2 me-1']
);
echo html_writer::select(
    [
        '' => get_string('all'),
        'course' => get_string('typecourse', 'local_oerclient'),
        'activity' => get_string('typeactivity', 'local_oerclient'),
        'data' => get_string('typedata', 'local_oerclient'),
    ],
    'type',
    $type,
    false,
    ['id' => 'oerclient-filter-type', 'class' => 'form-select d-inline w-auto']
);
echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('searchbutton', 'local_oerclient'), 'class' => 'btn btn-primary ms-2',
]);
echo html_writer::end_tag('form');

try {
    $client = new exchange_client($exchangeurl);
    $result = $client->call('local_oerexchange_search', [
        'query' => $query, 'type' => $type, 'page' => $page, 'perpage' => $perpage,
    ], $sitetoken);
} catch (\Throwable $e) {
    echo $OUTPUT->notification(s($e->getMessage()), 'error');
    echo $OUTPUT->footer();
    exit;
}

if (empty($result['results'])) {
    echo html_writer::tag('p', get_string('nocatalogresources', 'local_oerclient'), ['class' => 'mt-3']);
} else {
    echo html_writer::start_tag('div', ['class' => 'row row-cols-1 row-cols-md-3 g-3 mt-2']);
    foreach ($result['results'] as $r) {
        $url = new moodle_url('/local/oerclient/resource_preview.php', ['id' => $r['id']]);
        echo html_writer::start_tag('div', ['class' => 'col']);
        echo html_writer::start_tag('div', ['class' => 'card h-100']);
        // The card leads with the cover image, matching the Exchange's own
        // catalogue. PARAM_URL rejects javascript:/data: schemes — this URL
        // came over the wire from the Exchange, same distrust as
        // creatorprofileurl below. Empty string becomes null so the
        // same-sized neutral panel keeps the grid rows aligned.
        $coverurl = clean_param((string) ($r['coverimageurl'] ?? ''), PARAM_URL);
        echo html_writer::link(
            $url,
            \local_oerclient\local\cover_image::card($coverurl !== '' ? $coverurl : null),
            ['tabindex' => '-1', 'aria-hidden' => 'true']
        );
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        echo html_writer::tag('h5', html_writer::link($url, s($r['title'])), ['class' => 'card-title']);
        if (!empty($r['creatorname'])) {
            $creatorlabel = s($r['creatorname']);
            // PARAM_URL rejects javascript:/data: schemes — html_writer
            // only attribute-escapes, and this URL came from the Exchange.
            $profileurl = clean_param((string) ($r['creatorprofileurl'] ?? ''), PARAM_URL);
            if ($profileurl !== '') {
                $creatorlabel = html_writer::link($profileurl, $creatorlabel);
            }
            echo html_writer::tag(
                'div',
                get_string('createdby', 'local_oerclient', $creatorlabel),
                ['class' => 'small text-muted']
            );
        }
        echo html_writer::tag('p', s(shorten_text(strip_tags($r['summary']), 140)), ['class' => 'card-text text-muted']);
        echo html_writer::tag('div', s($r['licenseshortname']), ['class' => 'small text-muted']);
        // Link out to the canonical page on the Exchange, which carries the
        // share buttons, reviews and author profile this preview does not.
        echo html_writer::tag(
            'div',
            html_writer::link(
                rtrim($exchangeurl, '/') . '/local/oerexchange/resource.php?id=' . (int) $r['id'],
                get_string('viewonexchange', 'local_oerclient'),
                ['class' => 'small', 'target' => '_blank', 'rel' => 'noopener noreferrer']
            ),
            ['class' => 'mt-1']
        );
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
    echo html_writer::end_tag('div');

    $baseurl = new moodle_url('/local/oerclient/browse.php', ['q' => $query, 'type' => $type]);
    echo $OUTPUT->paging_bar($result['total'], $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
