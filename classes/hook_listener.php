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

        // The page's course is NEVER null/unset — moodle_page defaults it to the site
        // — so empty($PAGE->course) is not a valid "no course" check: it
        // consults moodle_page's __isset(), which can report false (making
        // empty() true) even while ->course->id resolves correctly via
        // __get(). This silently disabled this hook entirely on every course
        // page (found live, 2026-07-18, MDL Shield audit follow-up). Compare
        // the id directly instead.
        if ($PAGE->course->id <= 1) {
            return;
        }

        $context = \context_course::instance($PAGE->course->id);
        if (!has_capability('local/oerclient:share', $context)) {
            return;
        }

        $hook->get_secondaryview()->add(
            get_string('sharetoexchange', 'local_oerclient'),
            new \moodle_url('/local/oerclient/share.php', ['courseid' => $PAGE->course->id]),
            // The global class name, deliberately, not core\navigation\navigation_node:
            // the namespaced class only exists from Moodle 5.1, while this plugin
            // supports 5.0-5.2. On 5.0 the global IS the class; on 5.1+ core ends
            // lib/classes/navigation/navigation_node.php with a class_alias() back
            // to the global name, so this one reference is correct on all three.
            // Importing the namespaced name fataled on 5.0 with "Class
            // core\navigation\navigation_node not found" — on a real course page,
            // not just in tests.
            \navigation_node::TYPE_SETTING,
            null,
            'oerclientshare'
        );
    }
}
