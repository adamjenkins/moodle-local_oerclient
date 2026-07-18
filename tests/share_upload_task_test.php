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

namespace local_oerclient\task;

/**
 * Tests for share_upload_task::find_existing_resource_id(). Found live,
 * 2026-07-19: re-sharing an already-shared course produced a second,
 * duplicate Exchange catalogue entry instead of a new version of the first
 * — local_oerexchange_publish_resource has always accepted a 'resourceid'
 * param for exactly this, and resource_manager::publish() already knows how
 * to version, but the call site here never looked one up and passed it.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oerclient\task\share_upload_task
 */
final class share_upload_task_test extends \advanced_testcase {
    /**
     * Invokes the protected find_existing_resource_id() via reflection.
     *
     * @param \stdClass $share
     * @return int
     */
    protected function call(\stdClass $share): int {
        $task = new share_upload_task();
        $method = new \ReflectionMethod(share_upload_task::class, 'find_existing_resource_id');
        $method->setAccessible(true);
        return $method->invoke($task, $share);
    }

    public function test_finds_the_most_recent_published_share_of_the_same_course(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => 5, 'cmid' => null, 'type' => 'course',
            'title' => 't', 'status' => 'published', 'exchangeresourceid' => 42,
            'timecreated' => 100, 'timemodified' => 100,
        ]);
        $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => 5, 'cmid' => null, 'type' => 'course',
            'title' => 't', 'status' => 'published', 'exchangeresourceid' => 43,
            'timecreated' => 200, 'timemodified' => 200,
        ]);

        $newshare = (object) ['userid' => $user->id, 'courseid' => 5, 'cmid' => null];
        $this->assertSame(43, $this->call($newshare));
    }

    public function test_returns_zero_when_no_prior_share_exists(): void {
        $this->resetAfterTest();

        $share = (object) ['userid' => 999, 'courseid' => 999, 'cmid' => null];
        $this->assertSame(0, $this->call($share));
    }

    public function test_ignores_a_failed_prior_share(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => 5, 'cmid' => null, 'type' => 'course',
            'title' => 't', 'status' => 'failed', 'exchangeresourceid' => null,
            'timecreated' => 100, 'timemodified' => 100,
        ]);

        $share = (object) ['userid' => $user->id, 'courseid' => 5, 'cmid' => null];
        $this->assertSame(0, $this->call($share));
    }

    public function test_distinguishes_a_single_activity_share_from_its_own_course(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        // The whole course was shared as resource 42.
        $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => 5, 'cmid' => null, 'type' => 'course',
            'title' => 't', 'status' => 'published', 'exchangeresourceid' => 42,
            'timecreated' => 100, 'timemodified' => 100,
        ]);

        // Re-sharing one activity FROM that course must not match the
        // course-level share — it's a distinct catalogue entry.
        $share = (object) ['userid' => $user->id, 'courseid' => 5, 'cmid' => 9];
        $this->assertSame(0, $this->call($share));
    }

    /**
     * MDL Shield audit finding (2026-07-18): the task used to run the backup
     * (and upload it to the Exchange) purely on the strength of a share
     * record created earlier by a synchronous request-time capability check,
     * without re-checking that capability at execute() time — the same
     * "async sink never rechecked the capability" pattern flagged in
     * report_discoursestats. If the sharing user's local/oerclient:share
     * capability in that course is gone by the time the adhoc task runs
     * (role change, unenrolment), execute() must now fail cleanly instead of
     * still backing up and publishing the course/activity.
     */
    public function test_execute_fails_cleanly_when_the_sharing_user_no_longer_has_the_capability(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        // Deliberately not enrolled/given any role in the course — mirrors a
        // teacher who shared, then was unenrolled before the task ran.
        $user = $this->getDataGenerator()->create_user();

        $shareid = $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => $course->id, 'cmid' => null, 'type' => 'course',
            'title' => 't', 'summary' => '', 'language' => '', 'tags' => '',
            'licenseshortname' => 'cc-4.0', 'activitytype' => null, 'status' => 'pending',
            'exchangeresourceid' => null, 'errormessage' => null,
            'timecreated' => time(), 'timemodified' => time(),
        ]);

        $task = new share_upload_task();
        $task->set_custom_data(['shareid' => $shareid]);
        $task->execute();

        $share = $DB->get_record('local_oerclient_shares', ['id' => $shareid], '*', MUST_EXIST);
        $this->assertSame('failed', $share->status);
        $this->assertSame(
            get_string('error_sharecapabilitylost', 'local_oerclient'),
            $share->errormessage
        );
    }

    /**
     * MDL Shield audit finding (2026-07-18, found live while verifying the
     * capability-recheck fix above): run_backup() used to call
     * $bc->get_plan()->get_setting('users')->set_value(false) unconditionally.
     * backup_controller's own constructor already runs check_security(),
     * which locks that setting to false (LOCKED_BY_PERMISSION) for any user
     * lacking moodle/backup:userinfo — which editingteacher does NOT have by
     * default in stock Moodle, only manager/admin do. Core's
     * base_setting::set_value() throws on ANY set_value() call once locked,
     * even to the value it's already at, so the plugin's own redundant call
     * broke sharing for its actual target audience (ordinary teachers) and
     * only ever worked for admins. This test proves an editingteacher-only
     * user can now run a full backup without hitting that exception.
     */
    public function test_run_backup_succeeds_for_a_teacher_without_backup_userinfo_capability(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');

        // Sanity check: this is exactly the condition that broke sharing —
        // editingteacher has local/oerclient:share but not this core capability.
        $coursecontext = \context_course::instance($course->id);
        $this->assertFalse(has_capability('moodle/backup:userinfo', $coursecontext, $user->id));

        $share = (object) [
            'id' => 0,
            'userid' => $user->id,
            'courseid' => $course->id,
            'cmid' => null,
            'type' => 'course',
        ];

        $task = new share_upload_task();
        $method = new \ReflectionMethod(share_upload_task::class, 'run_backup');
        $method->setAccessible(true);

        // Must not throw base_setting_exception('setting_locked_by_permission').
        $tmppath = $method->invoke($task, $share);
        $this->assertFileExists($tmppath);
        @unlink($tmppath);
    }
}
