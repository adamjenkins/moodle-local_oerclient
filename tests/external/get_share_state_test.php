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

namespace local_oerclient\external;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for local_oerclient_get_share_state, polled by the share status page
 * while share_upload_task works through its stages.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(get_share_state::class)]
final class get_share_state_test extends \advanced_testcase {
    /**
     * Seed a share row in the given state.
     *
     * @param int $userid the sharer
     * @param int $courseid
     * @param string $status
     * @param string|null $errormessage
     * @return int share id
     */
    protected function seed(int $userid, int $courseid, string $status, ?string $errormessage = null): int {
        global $DB;

        return (int) $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'cmid' => null,
            'type' => 'course',
            'title' => 'A course',
            'summary' => '',
            'language' => 'en',
            'tags' => '',
            'licenseshortname' => 'cc-sa-4.0',
            'activitytype' => null,
            'status' => $status,
            'exchangeresourceid' => null,
            'errormessage' => $errormessage,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    public function test_each_in_flight_stage_keeps_the_poller_running(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($user);

        foreach (['pending', 'backingup', 'uploading'] as $stage) {
            $shareid = $this->seed((int) $user->id, (int) $course->id, $stage);

            $result = get_share_state::execute($shareid);

            $this->assertSame($stage, $result['status']);
            $this->assertFalse($result['settled'], "$stage should not stop the poller");
            $this->assertFalse($result['published']);
        }
    }

    public function test_a_published_share_settles(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($user);

        $result = get_share_state::execute($this->seed((int) $user->id, (int) $course->id, 'published'));

        $this->assertTrue($result['settled']);
        $this->assertTrue($result['published']);
        $this->assertSame('', $result['error']);
    }

    public function test_a_failed_share_settles_and_reports_its_reason(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($user);

        $shareid = $this->seed((int) $user->id, (int) $course->id, 'failed', 'The Exchange refused it.');

        $result = get_share_state::execute($shareid);

        $this->assertTrue($result['settled']);
        $this->assertFalse($result['published']);
        $this->assertSame('The Exchange refused it.', $result['error']);
    }

    public function test_the_sharer_can_still_read_their_share_without_course_access(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        // NOT enrolled: a teacher whose enrolment ended after they shared, and
        // the admin case below. An earlier version validated the SHARE'S
        // COURSE context, which runs core's enrolment check — verified live
        // 2026-08-02 that it answered every poll with "Course or activity not
        // accessible.", leaving the status page spinning forever. The page
        // itself only requires login plus ownership.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $result = get_share_state::execute($this->seed((int) $user->id, (int) $course->id, 'uploading'));

        $this->assertSame('uploading', $result['status']);
        $this->assertFalse($result['settled']);
    }

    public function test_another_teachers_share_is_refused(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $sharer = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $other = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        // Being in the same course is not enough: a share row names the
        // Exchange resource it produced, and belongs to the person who made it.
        $shareid = $this->seed((int) $sharer->id, (int) $course->id, 'uploading');

        $this->setUser($other);
        $this->expectException(\required_capability_exception::class);
        get_share_state::execute($shareid);
    }

    public function test_an_admin_may_read_any_share(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $sharer = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $shareid = $this->seed((int) $sharer->id, (int) $course->id, 'uploading');

        // Matches share_status.php's own gate, so support staff diagnosing a
        // stuck share see the same thing the page shows them.
        $this->setAdminUser();
        $result = get_share_state::execute($shareid);

        $this->assertSame('uploading', $result['status']);
    }
}
