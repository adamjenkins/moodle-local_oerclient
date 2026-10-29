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
 * Post-import localization checklist. See DESIGN.md §1 flow 2.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
// Which kind of import this was: it decides only which visibility notice and
// checklist item are shown, so a tampered value changes nothing but wording.
$type = optional_param('type', 'course', PARAM_ALPHA) === 'activity' ? 'activity' : 'course';

$context = context_course::instance($courseid);
require_capability('local/oerclient:import', $context);

$PAGE->set_url('/local/oerclient/import_result.php', ['courseid' => $courseid, 'type' => $type]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('importresulttitle', 'local_oerclient'));
$PAGE->set_heading(get_string('importresulttitle', 'local_oerclient'));

// Built here rather than passed in the URL: it depends only on the resource's
// type, and as a PARAM_BASE64 parameter it never arrived (see the method's docblock).
$checklist = \local_oerclient\local\import_manager::localization_checklist(['type' => $type]);

echo $OUTPUT->header();

echo $OUTPUT->notification(get_string('importsuccess', 'local_oerclient'), 'success');
// The content was written on another site and nobody here has reviewed it, so
// the import left it hidden from students; say so where the teacher will see it.
echo $OUTPUT->notification(
    get_string($type === 'activity' ? 'importedhidden_activity' : 'importedhidden_course', 'local_oerclient'),
    'info'
);

if (!empty($checklist)) {
    echo $OUTPUT->heading(get_string('localizationchecklist', 'local_oerclient'), 4);
    echo html_writer::start_tag('ul');
    foreach ($checklist as $item) {
        echo html_writer::tag('li', s($item));
    }
    echo html_writer::end_tag('ul');
}

$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
echo html_writer::link($courseurl, get_string('gotocourse', 'local_oerclient'), ['class' => 'btn btn-primary']);

echo $OUTPUT->footer();
