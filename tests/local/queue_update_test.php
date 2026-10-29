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
 * Tests for share_manager::queue_update(), the "Update the shared copy"
 * action: a share still in flight must not be queued a second time
 * (security audit OERC-03, 2026-10-28).
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(share_manager::class)]
final class queue_update_test extends \advanced_testcase {
    /**
     * Inserts a course share with the given status and returns the row.
     *
     * @param string $status share status
     * @return \stdClass the share row as stored
     */
    protected function make_share(string $status): \stdClass {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['summary' => '']);
        $id = $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => $course->id, 'cmid' => null, 'type' => 'course',
            'title' => 'Shared', 'summary' => '', 'language' => '', 'tags' => '',
            'licenseshortname' => 'cc-4.0', 'activitytype' => null, 'status' => $status,
            'exchangeresourceid' => $status === 'published' ? 7 : null,
            'errormessage' => $status === 'failed' ? 'Upload failed' : null,
            'timecreated' => time() - 60, 'timemodified' => time() - 60,
        ]);
        return $DB->get_record('local_oerclient_shares', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Number of share upload tasks waiting in the adhoc queue.
     *
     * @return int
     */
    protected function queued_tasks(): int {
        return count(\core\task\manager::get_adhoc_tasks(\local_oerclient\task\share_upload_task::class));
    }

    public function test_a_published_share_is_queued_for_update(): void {
        global $DB;
        $this->resetAfterTest();

        $share = $this->make_share('published');

        $this->assertTrue(share_manager::queue_update($share));
        $this->assertSame('pending', $DB->get_field('local_oerclient_shares', 'status', ['id' => $share->id]));
        $tasks = \core\task\manager::get_adhoc_tasks(\local_oerclient\task\share_upload_task::class);
        $this->assertCount(1, $tasks);
        $this->assertEquals((int) $share->id, reset($tasks)->get_custom_data()->shareid);
    }

    public function test_a_failed_share_can_be_retried_and_loses_its_old_error(): void {
        global $DB;
        $this->resetAfterTest();

        $share = $this->make_share('failed');

        $this->assertTrue(share_manager::queue_update($share));
        $row = $DB->get_record('local_oerclient_shares', ['id' => $share->id]);
        $this->assertSame('pending', $row->status);
        $this->assertNull($row->errormessage);
        $this->assertSame(1, $this->queued_tasks());
    }

    public function test_a_share_in_flight_is_not_queued_again(): void {
        global $DB;
        $this->resetAfterTest();

        foreach (['pending', 'backingup', 'uploading'] as $status) {
            $share = $this->make_share($status);

            $this->assertFalse(share_manager::queue_update($share), "a $status share must not be requeued");
            $row = $DB->get_record('local_oerclient_shares', ['id' => $share->id]);
            $this->assertSame($status, $row->status, "a $status share must keep its status");
            $this->assertSame((int) $share->timemodified, (int) $row->timemodified);
        }
        $this->assertSame(0, $this->queued_tasks());
    }

    /**
     * The guard reads the status from the database, not from the row the
     * page loaded: a page opened while the share was published can still
     * send the request after the background task has started on it.
     */
    public function test_a_stale_published_row_does_not_requeue_a_running_share(): void {
        global $DB;
        $this->resetAfterTest();

        $share = $this->make_share('published');
        $DB->set_field('local_oerclient_shares', 'status', 'uploading', ['id' => $share->id]);

        $this->assertFalse(share_manager::queue_update($share));
        $this->assertSame('uploading', $DB->get_field('local_oerclient_shares', 'status', ['id' => $share->id]));
        $this->assertSame(0, $this->queued_tasks());
    }

    /**
     * Pressing the button twice (or replaying the link) queues one update.
     */
    public function test_a_repeated_request_queues_one_update(): void {
        $this->resetAfterTest();

        $share = $this->make_share('published');

        $this->assertTrue(share_manager::queue_update($share));
        $this->assertFalse(share_manager::queue_update($share));
        $this->assertSame(1, $this->queued_tasks());
    }
}
