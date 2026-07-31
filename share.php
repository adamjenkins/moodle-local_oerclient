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

use local_oerclient\local\allowed_licenses;

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
    redirect(
        new moodle_url('/local/oerclient/index.php'),
        get_string('error_notlinked', 'local_oerclient'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

if (data_submitted() && confirm_sesskey() && optional_param('dosubmit', 0, PARAM_INT)) {
    $type = $cmid ? 'activity' : 'course';
    $activitytype = '';
    if ($cmid) {
        $cm = get_coursemodule_from_id('', $cmid, $courseid, false, MUST_EXIST);
        $activitytype = $cm->modname;
    }

    $licencestate = allowed_licenses::state();
    if (!$licencestate['shortnames']) {
        // No list at all: never reached the Exchange, or it accepts nothing.
        // Either way this is not "your licence is invalid" — say which it is.
        throw new moodle_exception(
            $licencestate['confirmed'] === null
                ? 'error_licensesunavailable'
                : 'error_nolicensesaccepted',
            'local_oerclient'
        );
    }

    // Re-validate against the same menu the <select> below was built from —
    // required_param() alone only confirms it's a string, not that it's one
    // of the licenses actually offered (MDL Shield audit finding, 2026-07-18).
    // Validated against the state already fetched above, not a fresh
    // share_manager::is_valid_license() call: on the failure path nothing is
    // written to config, so a second call would re-attempt the Exchange
    // request — worst case two timeouts on one form submit.
    $licenseshortname = required_param('licenseshortname', PARAM_TEXT);
    if (!in_array($licenseshortname, $licencestate['shortnames'], true)) {
        throw new moodle_exception('error_invalidlicense', 'local_oerclient');
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
        'licenseshortname' => $licenseshortname,
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

$licencestate = allowed_licenses::state();
if (!$licencestate['shortnames']) {
    // Nothing may be chosen: either this site has never reached the Exchange
    // (connectivity) or the Exchange accepts no licence at all (deliberate
    // configuration). They are different problems and get different messages.
    $message = $licencestate['confirmed'] === null
        ? get_string('error_licensesunavailable', 'local_oerclient')
        : get_string('error_nolicensesaccepted', 'local_oerclient');
    echo $OUTPUT->notification($message, 'error');
    echo $OUTPUT->footer();
    exit;
}

if (!$licencestate['live']) {
    echo $OUTPUT->notification(
        get_string('licenseliststale', 'local_oerclient', userdate($licencestate['confirmed'])),
        'info'
    );
}

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/oerclient/share.php', ['courseid' => $courseid, 'cmid' => $cmid]),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dosubmit', 'value' => 1]);

echo html_writer::tag(
    'label',
    get_string('sharetitlelabel', 'local_oerclient'),
    ['for' => 'oerclient-share-title']
);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'title', 'id' => 'oerclient-share-title', 'class' => 'form-control mb-2',
    'value' => $cmid ? get_coursemodule_from_id('', $cmid, $courseid, false, MUST_EXIST)->name : $course->fullname,
    'required' => 'required',
]);

echo html_writer::tag(
    'label',
    get_string('sharesummarylabel', 'local_oerclient'),
    ['for' => 'oerclient-share-summary']
);
echo html_writer::tag(
    'textarea',
    '',
    ['name' => 'summary', 'id' => 'oerclient-share-summary', 'class' => 'form-control mb-2',
        'required' => 'required']
);

echo html_writer::tag(
    'label',
    get_string('sharelanguagelabel', 'local_oerclient'),
    ['for' => 'oerclient-share-language']
);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'language', 'id' => 'oerclient-share-language',
    'class' => 'form-control mb-2', 'value' => current_language(),
]);

echo html_writer::tag('label', get_string('sharetagslabel', 'local_oerclient'), ['for' => 'oerclient-share-tags']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'tags', 'id' => 'oerclient-share-tags', 'class' => 'form-control mb-2',
]);

echo html_writer::tag(
    'label',
    get_string('sharelicenselabel', 'local_oerclient'),
    ['for' => 'oerclient-share-license']
);
echo html_writer::select(
    allowed_licenses::menu($licencestate['shortnames']),
    'licenseshortname',
    allowed_licenses::default_shortname($licencestate['shortnames']),
    false,
    ['id' => 'oerclient-share-license', 'class' => 'form-select mb-2']
);

echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('sharesubmit', 'local_oerclient'), 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
