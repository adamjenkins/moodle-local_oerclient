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

namespace local_oerclient;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for local_oerclient's event observers.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(observer::class)]
final class observer_test extends \advanced_testcase {
    /**
     * Deleting a course must not leave local_oerclient_shares/_imports rows
     * pointing at a courseid that no longer exists (MDL Shield audit
     * finding, 2026-07-18 round 3/4: previously nothing cleaned these up,
     * and share_status.php would throw for an orphaned row's
     * context_course::instance() lookup).
     */
    public function test_deleting_a_course_removes_its_shares_and_imports(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $keepcourse = $this->getDataGenerator()->create_course();
        $deletedcourse = $this->getDataGenerator()->create_course();

        $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => $deletedcourse->id, 'cmid' => null, 'type' => 'course',
            'title' => 'Doomed', 'summary' => '', 'language' => '', 'tags' => '',
            'licenseshortname' => 'cc-4.0', 'activitytype' => null, 'status' => 'published',
            'exchangeresourceid' => 1, 'errormessage' => null, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => $keepcourse->id, 'cmid' => null, 'type' => 'course',
            'title' => 'Survivor', 'summary' => '', 'language' => '', 'tags' => '',
            'licenseshortname' => 'cc-4.0', 'activitytype' => null, 'status' => 'published',
            'exchangeresourceid' => 2, 'errormessage' => null, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('local_oerclient_imports', (object) [
            'userid' => $user->id, 'exchangeresourceid' => 3, 'exchangeversionid' => 1,
            'courseid' => $deletedcourse->id, 'timecreated' => time(),
        ]);
        $DB->insert_record('local_oerclient_imports', (object) [
            'userid' => $user->id, 'exchangeresourceid' => 4, 'exchangeversionid' => 1,
            'courseid' => $keepcourse->id, 'timecreated' => time(),
        ]);

        delete_course($deletedcourse, false);

        $this->assertEquals(0, $DB->count_records('local_oerclient_shares', ['courseid' => $deletedcourse->id]));
        $this->assertEquals(0, $DB->count_records('local_oerclient_imports', ['courseid' => $deletedcourse->id]));
        $this->assertEquals(1, $DB->count_records('local_oerclient_shares', ['courseid' => $keepcourse->id]));
        $this->assertEquals(1, $DB->count_records('local_oerclient_imports', ['courseid' => $keepcourse->id]));
    }

    /**
     * Deleting a user removes their account link, and with it their live
     * personal token for the Exchange (security audit OERC-04, 2026-10-28).
     * Core's user deletion does not call privacy providers, so nothing else
     * would. Another user's link is left alone.
     */
    public function test_deleting_a_user_removes_their_exchange_link(): void {
        global $DB;
        $this->resetAfterTest();

        $doomed = $this->getDataGenerator()->create_user();
        $survivor = $this->getDataGenerator()->create_user();
        foreach ([$doomed, $survivor] as $i => $user) {
            $DB->insert_record('local_oerclient_link', (object) [
                'userid' => $user->id, 'exchangeuserid' => 100 + $i,
                'token' => 'token' . $i, 'timecreated' => time(),
            ]);
        }

        delete_user($doomed);

        $this->assertFalse($DB->record_exists('local_oerclient_link', ['userid' => $doomed->id]));
        $this->assertFalse($DB->record_exists('local_oerclient_link', ['token' => 'token0']));
        $this->assertTrue($DB->record_exists('local_oerclient_link', ['userid' => $survivor->id]));
    }
}
