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
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_oerclient. All data lives under the system
 * context; the plugin also sends data to an external system (the configured
 * Exchange), declared via an external location link.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_oerclient_link', [
            'userid' => 'privacy:metadata:local_oerclient_link:userid',
            'exchangeuserid' => 'privacy:metadata:local_oerclient_link:exchangeuserid',
            'token' => 'privacy:metadata:local_oerclient_link:token',
            'timecreated' => 'privacy:metadata:local_oerclient_link:timecreated',
        ], 'privacy:metadata:local_oerclient_link');

        $collection->add_database_table('local_oerclient_shares', [
            'userid' => 'privacy:metadata:local_oerclient_shares:userid',
            'courseid' => 'privacy:metadata:local_oerclient_shares:courseid',
            'cmid' => 'privacy:metadata:local_oerclient_shares:cmid',
            'type' => 'privacy:metadata:local_oerclient_shares:type',
            'title' => 'privacy:metadata:local_oerclient_shares:title',
            'summary' => 'privacy:metadata:local_oerclient_shares:summary',
            'language' => 'privacy:metadata:local_oerclient_shares:language',
            'tags' => 'privacy:metadata:local_oerclient_shares:tags',
            'licenseshortname' => 'privacy:metadata:local_oerclient_shares:licenseshortname',
            'activitytype' => 'privacy:metadata:local_oerclient_shares:activitytype',
            'status' => 'privacy:metadata:local_oerclient_shares:status',
            'exchangeresourceid' => 'privacy:metadata:local_oerclient_shares:exchangeresourceid',
            'errormessage' => 'privacy:metadata:local_oerclient_shares:errormessage',
            'timecreated' => 'privacy:metadata:local_oerclient_shares:timecreated',
            'timemodified' => 'privacy:metadata:local_oerclient_shares:timemodified',
        ], 'privacy:metadata:local_oerclient_shares');

        $collection->add_database_table('local_oerclient_imports', [
            'userid' => 'privacy:metadata:local_oerclient_imports:userid',
            'exchangeresourceid' => 'privacy:metadata:local_oerclient_imports:exchangeresourceid',
            'exchangeversionid' => 'privacy:metadata:local_oerclient_imports:exchangeversionid',
            'courseid' => 'privacy:metadata:local_oerclient_imports:courseid',
            'timecreated' => 'privacy:metadata:local_oerclient_imports:timecreated',
        ], 'privacy:metadata:local_oerclient_imports');

        // The Exchange is an EXTERNAL system, not a Moodle subsystem: what
        // leaves this site is the linked personal token exchange and the
        // shared course content itself.
        $collection->add_external_location_link('oerexchange', [
            'token' => 'privacy:metadata:oerexchange:token',
            'sharedcontent' => 'privacy:metadata:oerexchange:sharedcontent',
        ], 'privacy:metadata:oerexchange');

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
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        // All three tables key their per-user rows on a plain 'userid' column
        // and only ever live at the system context (see get_metadata()), so a
        // single column selector per table enumerates every affected user.
        foreach (['local_oerclient_link', 'local_oerclient_shares', 'local_oerclient_imports'] as $table) {
            $userlist->add_from_sql('userid', "SELECT userid FROM {{$table}}", []);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $select = "userid $insql";
        $DB->delete_records_select('local_oerclient_link', $select, $inparams);
        $DB->delete_records_select('local_oerclient_shares', $select, $inparams);
        $DB->delete_records_select('local_oerclient_imports', $select, $inparams);
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
                'title' => $s->title,
                'summary' => $s->summary,
                'tags' => $s->tags,
                'type' => $s->type,
                'courseid' => $s->courseid,
                'cmid' => $s->cmid,
                'language' => $s->language,
                'licenseshortname' => $s->licenseshortname,
                'activitytype' => $s->activitytype,
                'status' => $s->status,
                'exchangeresourceid' => $s->exchangeresourceid,
                'errormessage' => $s->errormessage,
                'timecreated' => \core_privacy\local\request\transform::datetime($s->timecreated),
                'timemodified' => \core_privacy\local\request\transform::datetime($s->timemodified),
            ], $shares)),
            'imports' => array_values(array_map(fn($i) => [
                'courseid' => $i->courseid,
                'exchangeresourceid' => $i->exchangeresourceid,
                'exchangeversionid' => $i->exchangeversionid,
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
        global $DB;

        // Unlike local_oerexchange's catalogue (where one row can carry
        // other users' reviews/shared content, so a blanket wipe would
        // destroy data that isn't the requesting context's own), every row
        // in all three of this plugin's tables already belongs to exactly
        // one user (get_contexts_for_userid()/get_metadata() only ever
        // place data at the system context) — so honouring a bulk
        // "delete all users' data in this context" request here is safe and
        // required for privacy-tool completeness (MDL Shield audit finding,
        // 2026-07-18 round 3/4: this was previously an unconditional no-op).
        if (!$context instanceof \context_system) {
            return;
        }

        $DB->delete_records('local_oerclient_link');
        $DB->delete_records('local_oerclient_shares');
        $DB->delete_records('local_oerclient_imports');
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
