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

/**
 * Event observers for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * A deleted course leaves local_oerclient_shares/_imports rows pointing
     * at a courseid that no longer exists — those rows were otherwise
     * orphaned forever (no db/uninstall.php cleanup applies, since this
     * happens on course deletion, not plugin uninstall) and share_status.php
     * would throw when it tried to resolve context_course::instance() for
     * one of them (MDL Shield audit finding, 2026-07-18 round 3/4).
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;

        $courseid = $event->objectid;
        $DB->delete_records('local_oerclient_shares', ['courseid' => $courseid]);
        $DB->delete_records('local_oerclient_imports', ['courseid' => $courseid]);
    }
}
