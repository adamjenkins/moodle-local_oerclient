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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_oerclient. All data lives under the system
 * context; the plugin also sends data to an external system (the configured
 * Exchange), declared via a subsystem link.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_oerclient_link', [
            'exchangeuserid' => 'privacy:metadata:local_oerclient_link:exchangeuserid',
            'token' => 'privacy:metadata:local_oerclient_link:token',
            'timecreated' => 'privacy:metadata:local_oerclient_link:timecreated',
        ], 'privacy:metadata:local_oerclient_link');

        $collection->add_database_table('local_oerclient_shares', [
            'userid' => 'privacy:metadata:local_oerclient_shares:userid',
            'title' => 'privacy:metadata:local_oerclient_shares:title',
            'timecreated' => 'privacy:metadata:local_oerclient_shares:timecreated',
        ], 'privacy:metadata:local_oerclient_shares');

        $collection->add_database_table('local_oerclient_imports', [
            'userid' => 'privacy:metadata:local_oerclient_imports:userid',
            'timecreated' => 'privacy:metadata:local_oerclient_imports:timecreated',
        ], 'privacy:metadata:local_oerclient_imports');

        $collection->add_subsystem_link(
            'local_oerexchange',
            [],
            'privacy:metadata:oerexchange'
        );

        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $hasdata = $DB->record_exists('local_oerclient_link', ['userid' => $userid])
            || $DB->record_exists('local_oerclient_shares', ['userid' => $userid])
            || $DB->record_exists('local_oerclient_imports', ['userid' => $userid]);

        if ($hasdata) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        $link = $DB->get_record('local_oerclient_link', ['userid' => $userid]);
        $shares = $DB->get_records('local_oerclient_shares', ['userid' => $userid]);
        $imports = $DB->get_records('local_oerclient_imports', ['userid' => $userid]);

        $data = (object) [
            'linked' => $link ? true : false,
            'exchangeuserid' => $link->exchangeuserid ?? null,
            'shares' => array_values(array_map(fn($s) => [
                'title' => $s->title, 'status' => $s->status,
                'timecreated' => \core_privacy\local\request\transform::datetime($s->timecreated),
            ], $shares)),
            'imports' => array_values(array_map(fn($i) => [
                'courseid' => $i->courseid,
                'timecreated' => \core_privacy\local\request\transform::datetime($i->timecreated),
            ], $imports)),
        ];

        writer::with_context(\context_system::instance())->export_data(
            [get_string('pluginname', 'local_oerclient')],
            $data
        );
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        return;
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_oerclient_link', ['userid' => $userid]);
        $DB->delete_records('local_oerclient_shares', ['userid' => $userid]);
        $DB->delete_records('local_oerclient_imports', ['userid' => $userid]);
    }
}
