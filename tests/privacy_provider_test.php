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

namespace local_oerclient\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider tests for local_oerclient — the first tests this plugin
 * has (MDL Shield audit finding 8, 2026-07-18: tests/ was previously an
 * empty fixtures/ dir).
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oerclient\privacy\provider
 */
final class privacy_provider_test extends \core_privacy\tests\provider_testcase {
    public function test_get_metadata_declares_all_three_tables(): void {
        $collection = new \core_privacy\local\metadata\collection('local_oerclient');
        $collection = provider::get_metadata($collection);

        $tables = array_map(
            fn($item) => method_exists($item, 'get_name') ? $item->get_name() : null,
            $collection->get_collection()
        );
        $this->assertContains('local_oerclient_link', $tables);
        $this->assertContains('local_oerclient_shares', $tables);
        $this->assertContains('local_oerclient_imports', $tables);
    }

    public function test_get_contexts_for_userid_empty_for_user_with_no_data(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertEmpty($contextlist->get_contextids());
    }

    public function test_export_and_delete_round_trip(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $DB->insert_record('local_oerclient_link', (object) [
            'userid' => $user->id, 'exchangeuserid' => 5, 'token' => 'abc', 'timecreated' => time(),
        ]);
        $DB->insert_record('local_oerclient_shares', (object) [
            'userid' => $user->id, 'courseid' => $course->id, 'cmid' => null, 'type' => 'course',
            'title' => 'Shared course', 'summary' => '', 'language' => '', 'tags' => '',
            'licenseshortname' => 'cc-4.0', 'activitytype' => null, 'status' => 'published',
            'exchangeresourceid' => 1, 'errormessage' => null, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('local_oerclient_imports', (object) [
            'userid' => $user->id, 'exchangeresourceid' => 1, 'exchangeversionid' => 1,
            'courseid' => $course->id, 'timecreated' => time(),
        ]);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertNotEmpty($contextlist->get_contextids());

        $this->setUser($user);
        writer::reset();
        $approvedlist = new approved_contextlist($user, 'local_oerclient', [\context_system::instance()->id]);
        provider::export_user_data($approvedlist);
        $data = writer::with_context(\context_system::instance())->get_data([get_string('pluginname', 'local_oerclient')]);
        $this->assertTrue($data->linked);
        $this->assertNotEmpty($data->shares);
        $this->assertNotEmpty($data->imports);

        provider::delete_data_for_user($approvedlist);
        $this->assertEquals(0, $DB->count_records('local_oerclient_link', ['userid' => $user->id]));
        $this->assertEquals(0, $DB->count_records('local_oerclient_shares', ['userid' => $user->id]));
        $this->assertEquals(0, $DB->count_records('local_oerclient_imports', ['userid' => $user->id]));
    }

    /**
     * Every row in this plugin's tables already belongs to exactly one user
     * (unlike local_oerexchange's shared catalogue, where one row can carry
     * other users' data too) — so, unlike that sibling plugin, a bulk
     * "delete all users' data in this context" request should actually wipe
     * the tables rather than being a no-op (MDL Shield audit finding,
     * 2026-07-18 round 3/4: this was previously an unconditional no-op
     * despite all this plugin's data living at the system context).
     */
    public function test_delete_data_for_all_users_in_context_wipes_system_context_data(): void {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $DB->insert_record('local_oerclient_link', (object) [
            'userid' => $user1->id, 'exchangeuserid' => 5, 'token' => 'abc', 'timecreated' => time(),
        ]);
        $DB->insert_record('local_oerclient_link', (object) [
            'userid' => $user2->id, 'exchangeuserid' => 6, 'token' => 'def', 'timecreated' => time(),
        ]);

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $this->assertEquals(0, $DB->count_records('local_oerclient_link'));
    }

    /**
     * A context other than the system context (e.g. a course context, which
     * this plugin never places data at) must be a safe no-op, not an error
     * or an accidental blanket wipe.
     */
    public function test_delete_data_for_all_users_in_context_ignores_non_system_context(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $DB->insert_record('local_oerclient_link', (object) [
            'userid' => $user->id, 'exchangeuserid' => 5, 'token' => 'abc', 'timecreated' => time(),
        ]);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertEquals(1, $DB->count_records('local_oerclient_link'));
    }
}
