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

defined('MOODLE_INTERNAL') || die();

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
}
