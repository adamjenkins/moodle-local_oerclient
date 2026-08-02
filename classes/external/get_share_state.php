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

namespace local_oerclient\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * local_oerclient_get_share_state external function — how far along one of
 * the caller's own shares is.
 *
 * Reads only THIS site's `local_oerclient_shares` row, which share_upload_task
 * moves through pending → backingup → uploading → published|failed. It
 * deliberately makes no call to the Exchange: this answers a poll running
 * every few seconds in a teacher's browser, and a cross-site HTTP request on
 * that cadence would turn one teacher watching a page into a steady load on
 * another institution's server. share_status.php still makes the single
 * cross-site call for the richer detail once the share is published.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_share_state extends external_api {
    /**
     * Describes the parameters this function accepts.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'shareid' => new external_value(PARAM_INT, 'Local share id'),
        ]);
    }

    /**
     * The share's current local state.
     *
     * @param int $shareid
     * @return array
     */
    public static function execute(int $shareid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['shareid' => $shareid]);

        $share = $DB->get_record('local_oerclient_shares', ['id' => $params['shareid']], '*', MUST_EXIST);

        // System context, NOT the share's course context — deliberately, and
        // verified live on 2026-08-02 after the course-context version failed
        // with "Course or activity not accessible.". validate_context() on a
        // course runs core's require_login($course), i.e. an ENROLMENT check,
        // and this function has two legitimate callers who can fail it:
        // an administrator reading someone else's stuck share (share_status.php
        // allows exactly that, via moodle/site:config), and a teacher whose
        // enrolment ended after they shared. The page itself gates on plain
        // require_login() plus ownership, and this must match it rather than
        // being quietly stricter than the page that calls it. Nothing here
        // discloses course content: it is one row of the caller's own share.
        self::validate_context(\context_system::instance());

        // The same owner-or-admin gate share_status.php applies. A share row
        // carries the course and its Exchange resource id, so a teacher on
        // this site must not be able to read another teacher's.
        if ((int) $share->userid !== (int) $USER->id) {
            require_capability('moodle/site:config', \context_system::instance());
        }

        $status = (string) $share->status;

        return [
            'shareid' => (int) $share->id,
            'status' => $status,
            // Terminal states only. 'pending', 'backingup' and 'uploading'
            // are all still in flight, and naming them here rather than in
            // JavaScript keeps the list beside the task that writes them.
            'settled' => in_array($status, ['published', 'failed'], true),
            'published' => ($status === 'published'),
            // Written by share_upload_task from the Exchange's own rejection
            // message or a local failure; already author-facing.
            'error' => ($status === 'failed') ? (string) $share->errormessage : '',
        ];
    }

    /**
     * Describes the structure of execute()'s return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'shareid' => new external_value(PARAM_INT, 'Local share id'),
            'status' => new external_value(PARAM_ALPHA, 'pending, backingup, uploading, published or failed'),
            'settled' => new external_value(PARAM_BOOL, 'False while the share is still in flight — keep polling'),
            'published' => new external_value(PARAM_BOOL, 'True once the Exchange has accepted the share'),
            'error' => new external_value(PARAM_TEXT, 'Failure reason, empty unless the share failed'),
        ]);
    }
}
