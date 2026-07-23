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

namespace local_oerclient\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for detecting that a share's source course/activity is gone.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(share_manager::class)]
final class source_exists_test extends \advanced_testcase {
    public function test_a_live_course_share_has_its_source(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $share = (object) ['courseid' => $course->id, 'cmid' => null, 'type' => 'course'];

        $this->assertTrue(share_manager::source_exists($share));
    }

    /**
     * A missing course is reported as missing.
     *
     * Note this is a defence-in-depth case, not the everyday one: deleting a
     * course fires core\event\course_deleted, and this plugin's own observer
     * (classes/observer.php) deletes the share rows for that course outright —
     * so after a normal deletion there is no share left to update. The check
     * still matters for a share row that outlives its course some other way
     * (observer disabled or failed, a row restored from a database backup, a
     * courseid that never existed).
     */
    public function test_a_missing_course_is_detected(): void {
        $this->resetAfterTest();

        $share = (object) ['courseid' => -1, 'cmid' => null, 'type' => 'course'];

        $this->assertFalse(share_manager::source_exists($share));
    }

    public function test_the_observer_removes_share_rows_when_a_course_is_deleted(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $shareid = $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => $course->id, 'cmid' => null, 'type' => 'course',
            'title' => 't', 'status' => 'published', 'exchangeresourceid' => 1,
            'timecreated' => time(), 'timemodified' => time(),
        ]);

        delete_course($course, false);

        $this->assertFalse(
            $DB->record_exists('local_oerclient_shares', ['id' => $shareid]),
            'the course_deleted observer should already have removed this'
        );
    }

    public function test_a_live_activity_share_has_its_source(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $share = (object) ['courseid' => $course->id, 'cmid' => $page->cmid, 'type' => 'activity'];

        $this->assertTrue(share_manager::source_exists($share));
    }

    /**
     * The case that matters most: the course is fine, so a course-level check
     * alone would wrongly say the source is still there, but the specific
     * activity that was shared has been deleted.
     */
    public function test_a_deleted_activity_is_detected_even_though_its_course_survives(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $share = (object) ['courseid' => $course->id, 'cmid' => $page->cmid, 'type' => 'activity'];

        (new \core_courseformat\local\cmactions($course))->delete($page->cmid);

        $this->assertTrue(
            $GLOBALS['DB']->record_exists('course', ['id' => $course->id]),
            'precondition: the course itself must still be there'
        );
        $this->assertFalse(share_manager::source_exists($share));
    }

    /**
     * An activity share whose cmid belongs to some other course must not be
     * treated as present just because a row with that id exists somewhere.
     */
    public function test_an_activity_from_a_different_course_does_not_count(): void {
        $this->resetAfterTest();

        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $courseb->id]);

        $share = (object) ['courseid' => $coursea->id, 'cmid' => $page->cmid, 'type' => 'activity'];

        $this->assertFalse(share_manager::source_exists($share));
    }

    /**
     * The real user-facing case: the course survives (so the share row does
     * too) but the shared activity has been deleted. The task must refuse
     * cleanly rather than dying inside backup_controller with an error
     * meaningless to a teacher.
     */
    public function test_the_upload_task_fails_cleanly_when_the_shared_activity_is_gone(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');

        $shareid = $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => $course->id, 'cmid' => $page->cmid, 'type' => 'activity',
            'title' => 't', 'summary' => '', 'language' => '', 'tags' => '',
            'licenseshortname' => 'cc-4.0', 'activitytype' => 'page', 'status' => 'pending',
            'exchangeresourceid' => null, 'errormessage' => null,
            'timecreated' => time(), 'timemodified' => time(),
        ]);

        (new \core_courseformat\local\cmactions($course))->delete($page->cmid);

        $task = new \local_oerclient\task\share_upload_task();
        $task->set_custom_data(['shareid' => $shareid]);
        $task->execute();

        $share = $DB->get_record('local_oerclient_shares', ['id' => $shareid], '*', MUST_EXIST);
        $this->assertSame('failed', $share->status);
        $this->assertSame(get_string('error_sourcegoneactivity', 'local_oerclient'), $share->errormessage);
    }
}
