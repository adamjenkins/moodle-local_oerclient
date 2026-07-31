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
 * Library callbacks for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add "Share to OER Exchange" to a single activity's settings menu.
 *
 * core\hook\navigation\secondary_extend (used for the course-page link in
 * hook_listener::add_share_link()) is only dispatched from
 * secondary::load_course_navigation() — never from
 * secondary::load_module_navigation() — so it cannot reach activity pages in
 * Moodle 5.2 (verified by reading
 * lib/classes/navigation/views/secondary.php live on the test site,
 * 2026-07-18: the hook dispatch at the end of load_course_navigation() has
 * no equivalent in load_module_navigation()). This is the actual supported
 * extension point for a module-page settings link: local plugins'
 * extend_settings_navigation() callbacks are called for every context,
 * including CONTEXT_MODULE, and load_module_navigation() reads its nodes
 * from the 'modulesettings' branch this function adds to.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_oerclient_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return;
    }

    $cm = $settingsnav->get_page()->cm;
    if (!$cm) {
        return;
    }

    // The capability is defined at CONTEXT_COURSE (db/access.php) — same
    // scope as the course-page link, since sharing a single activity is
    // still an action on behalf of the whole course.
    $coursecontext = context_course::instance($cm->course);
    if (!has_capability('local/oerclient:share', $coursecontext)) {
        return;
    }

    $modulesettings = $settingsnav->find('modulesettings', settings_navigation::TYPE_SETTING);
    if (!$modulesettings) {
        return;
    }

    $modulesettings->add(
        get_string('sharetoexchange', 'local_oerclient'),
        new moodle_url('/local/oerclient/share.php', ['courseid' => $cm->course, 'cmid' => $cm->id]),
        navigation_node::TYPE_SETTING,
        null,
        'oerclientshareactivity'
    );
}

/**
 * Invalidates the cached accepted-licence list (settings.php callback).
 *
 * Wired to both 'exchangeurl' and 'sitetoken' so that repointing this site
 * at a different Exchange never serves the previous Exchange's list — not
 * for up to CACHE_TTL seconds, and not indefinitely as last-known-good if
 * the new Exchange turns out to be unreachable. The shortname list itself
 * ('acceptedlicenses') is deliberately left in place: clearing only the
 * timestamp forces allowed_licenses::state() to re-fetch on the very next
 * call, while still leaving something to fall back to if that fetch fails.
 */
function local_oerclient_licensecache_updated_callback(): void {
    unset_config('acceptedlicensestime', 'local_oerclient');
}
