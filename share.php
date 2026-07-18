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
 * Share wizard: whole course or a single activity, license + metadata,
 * queues the sanitized backup/upload adhoc task. See DESIGN.md §1 flow 1.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($courseid);
require_capability('local/oerclient:share', $context);

$PAGE->set_url('/local/oerclient/share.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('sharetoexchange', 'local_oerclient'));
$PAGE->set_heading(get_string('sharetoexchange', 'local_oerclient'));

global $DB, $USER;

$link = $DB->get_record('local_oerclient_link', ['userid' => $USER->id]);
if (!$link) {
    redirect(new moodle_url('/local/oerclient/index.php'), get_string('error_notlinked', 'local_oerclient'), null, \core\output\notification::NOTIFY_WARNING);
}

if (data_submitted() && confirm_sesskey() && optional_param('dosubmit', 0, PARAM_INT)) {
    $type = $cmid ? 'activity' : 'course';
    $activitytype = '';
    if ($cmid) {
        $cm = get_coursemodule_from_id('', $cmid, $courseid, false, MUST_EXIST);
        $activitytype = $cm->modname;
    }

    $shareid = $DB->insert_record('local_oerclient_shares', (object) [
        'userid' => $USER->id,
        'courseid' => $courseid,
        'cmid' => $cmid ?: null,
        'type' => $type,
        'title' => required_param('title', PARAM_TEXT),
        'summary' => required_param('summary', PARAM_TEXT),
        'language' => optional_param('language', '', PARAM_TEXT),
        'tags' => optional_param('tags', '', PARAM_TEXT),
        'licenseshortname' => required_param('licenseshortname', PARAM_TEXT),
        'activitytype' => $activitytype ?: null,
        'status' => 'pending',
        'exchangeresourceid' => null,
        'errormessage' => null,
        'timecreated' => time(),
        'timemodified' => time(),
    ]);

    $task = new \local_oerclient\task\share_upload_task();
    $task->set_custom_data(['shareid' => $shareid]);
    \core\task\manager::queue_adhoc_task($task);

    redirect(
        new moodle_url('/local/oerclient/share_status.php', ['id' => $shareid]),
        get_string('sharequeued', 'local_oerclient'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

require_once($CFG->libdir . '/licenselib.php');
$licenses = \license_manager::get_licenses();

echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/local/oerclient/share.php', ['courseid' => $courseid, 'cmid' => $cmid])]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dosubmit', 'value' => 1]);

echo html_writer::tag('label', get_string('sharetitlelabel', 'local_oerclient'));
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'title', 'class' => 'form-control mb-2',
    'value' => $cmid ? get_coursemodule_from_id('', $cmid, $courseid)->name : $course->fullname,
    'required' => 'required',
]);

echo html_writer::tag('label', get_string('sharesummarylabel', 'local_oerclient'));
echo html_writer::tag('textarea', '', ['name' => 'summary', 'class' => 'form-control mb-2', 'required' => 'required']);

echo html_writer::tag('label', get_string('sharelanguagelabel', 'local_oerclient'));
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'language', 'class' => 'form-control mb-2', 'value' => current_language()]);

echo html_writer::tag('label', get_string('sharetagslabel', 'local_oerclient'));
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'tags', 'class' => 'form-control mb-2']);

echo html_writer::tag('label', get_string('sharelicenselabel', 'local_oerclient'));
$licenseoptions = [];
foreach ($licenses as $license) {
    $licenseoptions[$license->shortname] = $license->fullname;
}
echo html_writer::select($licenseoptions, 'licenseshortname', 'cc-4.0', false, ['class' => 'form-select mb-2']);

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('sharesubmit', 'local_oerclient'), 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
