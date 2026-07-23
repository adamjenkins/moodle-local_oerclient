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
 * Status of a queued share.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_login();

$id = required_param('id', PARAM_INT);

global $DB, $USER;
$share = $DB->get_record('local_oerclient_shares', ['id' => $id], '*', MUST_EXIST);
if ((int) $share->userid !== (int) $USER->id) {
    require_capability('moodle/site:config', context_system::instance());
}

$PAGE->set_url('/local/oerclient/share_status.php', ['id' => $id]);
$PAGE->set_context(context_course::instance($share->courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('sharestatustitle', 'local_oerclient'));
$PAGE->set_heading(get_string('sharestatustitle', 'local_oerclient'));

// Handle "Update the shared copy": re-run the share pipeline for this share.
// share_upload_task already finds the prior published share and passes its
// resourceid to publish_resource, so the Exchange adds this as a new version
// of the existing catalogue entry (and then supersedes the old one) rather
// than creating a duplicate.
if (optional_param('update', 0, PARAM_INT) && confirm_sesskey()) {
    require_capability('local/oerclient:share', context_course::instance($share->courseid));

    if (!\local_oerclient\local\share_manager::source_exists($share)) {
        redirect(
            new moodle_url('/local/oerclient/share_status.php', ['id' => $id]),
            get_string('error_sourcegone', 'local_oerclient'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $DB->update_record('local_oerclient_shares', (object) [
        'id' => $share->id,
        'status' => 'pending',
        'errormessage' => null,
        'timemodified' => time(),
    ]);

    $task = new \local_oerclient\task\share_upload_task();
    $task->set_custom_data(['shareid' => $share->id]);
    \core\task\manager::queue_adhoc_task($task);

    redirect(
        new moodle_url('/local/oerclient/share_status.php', ['id' => $id]),
        get_string('updatequeued', 'local_oerclient'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo html_writer::tag('p', get_string(
    'sharestatuslabel',
    'local_oerclient',
    get_string('sharestatus_' . $share->status, 'local_oerclient')
));

if ($share->status === 'failed' && $share->errormessage) {
    echo $OUTPUT->notification(s($share->errormessage), 'error');
}

if ($share->status === 'published' && $share->exchangeresourceid) {
    $exchangeurl = get_config('local_oerclient', 'exchangeurl');
    $resourceurl = rtrim($exchangeurl, '/') . '/local/oerexchange/resource.php?id=' . $share->exchangeresourceid;

    // Ask the Exchange what it currently holds. This is a live cross-site
    // call, so it can fail (Exchange down, token revoked, resource deleted
    // there) — a failure must degrade to "we can't tell you right now"
    // rather than breaking the page.
    $status = null;
    $statuserror = null;
    $link = $DB->get_record('local_oerclient_link', ['userid' => $share->userid]);
    if ($link) {
        try {
            $client = new \local_oerclient\local\exchange_client($exchangeurl);
            $status = $client->call(
                'local_oerexchange_get_share_status',
                ['resourceid' => (int) $share->exchangeresourceid],
                $link->token
            );
        } catch (\Throwable $e) {
            $statuserror = $e->getMessage();
        }
    }

    if ($status) {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable w-auto';
        $table->data = [
            [get_string('firstpublished', 'local_oerclient'), userdate($status['timeshared'])],
            [get_string('lastupdated', 'local_oerclient'), userdate($status['timeupdated'])],
            [
                get_string('exchangevisibility', 'local_oerclient'),
                $status['visible']
                    ? get_string('exchangevisible', 'local_oerclient')
                    : get_string('exchangehidden', 'local_oerclient'),
            ],
            [get_string('downloadcountlabel', 'local_oerclient'), (int) $status['downloadcount']],
            [get_string('importcountlabel', 'local_oerclient'), (int) $status['importcount']],
        ];
        echo html_writer::table($table);

        if (!$status['visible']) {
            echo $OUTPUT->notification(get_string('exchangehiddenhint', 'local_oerclient'), 'info');
        }
    } else if ($statuserror !== null) {
        echo $OUTPUT->notification(
            get_string('error_statusunavailable', 'local_oerclient', s($statuserror)),
            'warning'
        );
    }

    echo html_writer::start_tag('div', ['class' => 'mt-3']);
    echo html_writer::link(
        $resourceurl,
        get_string('viewonexchange', 'local_oerclient'),
        ['class' => 'btn btn-primary me-2', 'target' => '_blank']
    );
    if (has_capability('local/oerclient:share', context_course::instance($share->courseid))) {
        if (\local_oerclient\local\share_manager::source_exists($share)) {
            echo html_writer::link(
                new moodle_url('/local/oerclient/share_status.php', [
                    'id' => $id, 'update' => 1, 'sesskey' => sesskey(),
                ]),
                get_string('updateexchangecopy', 'local_oerclient'),
                ['class' => 'btn btn-outline-primary']
            );
            echo html_writer::tag('div', get_string('updateexchangecopyhint', 'local_oerclient'), [
                'class' => 'small text-muted mt-1',
            ]);
        } else {
            // No Update button at all rather than one that always fails. The
            // published copy is untouched and stays exactly as it is.
            echo $OUTPUT->notification(
                get_string($share->type === 'activity' ? 'error_sourcegoneactivity' : 'error_sourcegone', 'local_oerclient'),
                'warning'
            );
        }
    }
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
