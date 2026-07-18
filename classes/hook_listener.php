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

use core\hook\navigation\secondary_extend;
use core\navigation\navigation_node;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook listeners for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {
    /**
     * Add "Share to OER Exchange" to a course's secondary navigation.
     *
     * @param secondary_extend $hook
     */
    public static function add_share_link(secondary_extend $hook): void {
        global $PAGE;

        if (empty($PAGE->course) || $PAGE->course->id <= 1) {
            return;
        }

        $context = \context_course::instance($PAGE->course->id);
        if (!has_capability('local/oerclient:share', $context)) {
            return;
        }

        $hook->get_secondaryview()->add(
            get_string('sharetoexchange', 'local_oerclient'),
            new \moodle_url('/local/oerclient/share.php', ['courseid' => $PAGE->course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'oerclientshare'
        );
    }
}
