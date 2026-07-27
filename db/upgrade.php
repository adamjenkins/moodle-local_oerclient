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
 * Upgrade steps for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_oerclient_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026072700) {
        // Foreign keys for the userid/courseid relations (documented in
        // install.xml since 0.1.3) and the indexes they bring: the privacy
        // provider queries every table by userid, and the course_deleted
        // observer deletes shares/imports by courseid.
        $table = new xmldb_table('local_oerclient_link');
        $index = new xmldb_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $dbman->add_key($table, $key);

        $table = new xmldb_table('local_oerclient_shares');
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $dbman->add_key($table, new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']));
        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_key($table, new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']));
        }

        $table = new xmldb_table('local_oerclient_imports');
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_key($table, new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']));
        }
        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_key($table, new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']));
        }

        upgrade_plugin_savepoint(true, 2026072700, 'local', 'oerclient');
    }

    return true;
}
