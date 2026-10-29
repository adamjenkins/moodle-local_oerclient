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

namespace local_oerclient\local;

/**
 * Small helpers for the share wizard that need to be independently
 * testable rather than living inline in share.php.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class share_manager {
    /**
     * Whether the course (and, for an activity share, the activity) this share
     * was made from still exists on this site.
     *
     * A published resource is deliberately independent of the course it came
     * from — that is the point of publishing an OER — so a deleted source must
     * never alter what is on the Exchange. It does mean "update the shared
     * copy" has nothing to re-read, and without this check the attempt died
     * deep inside context_course::instance() or backup_controller with an
     * error meaningless to a teacher.
     *
     * @param \stdClass $share a row from local_oerclient_shares
     * @return bool
     */
    public static function source_exists(\stdClass $share): bool {
        global $DB;

        if (!$DB->record_exists('course', ['id' => $share->courseid])) {
            return false;
        }

        if ($share->type === 'activity' && !empty($share->cmid)) {
            // Checking course_modules directly rather than via
            // get_coursemodule_from_id(), which throws on a missing cm — the
            // whole point here is to answer the question without an exception.
            return $DB->record_exists('course_modules', [
                'id' => $share->cmid,
                'course' => $share->courseid,
            ]);
        }

        return true;
    }

    /**
     * Re-runs the share pipeline for a share that has finished ("Update the
     * shared copy"), and refuses one that is still in flight.
     *
     * Before this check a share that was pending, backing up or uploading
     * could be reset to pending and queued again. Two tasks then backed up
     * the course at the same time, and on a first share both saw no
     * Exchange resource id yet, so both published a new catalogue entry
     * (security audit OERC-03, 2026-10-28). The page only drew the button
     * for a published share; the request itself was never checked.
     *
     * The status is moved to pending with a conditional UPDATE, so of two
     * requests racing on the same row only the first changes it, and the
     * task is queued with core's duplicate check as a second guard.
     *
     * @param \stdClass $share a row from local_oerclient_shares
     * @return bool true if an update was queued, false if the share is still in flight
     */
    public static function queue_update(\stdClass $share): bool {
        global $DB;

        $finished = ['published', 'failed'];
        $current = $DB->get_field('local_oerclient_shares', 'status', ['id' => $share->id]);
        if (!in_array($current, $finished, true)) {
            return false;
        }

        [$insql, $params] = $DB->get_in_or_equal($finished, SQL_PARAMS_NAMED, 'st');
        $now = time();
        $DB->execute(
            "UPDATE {local_oerclient_shares}
                SET status = :pending, errormessage = NULL, timemodified = :now
              WHERE id = :id AND status $insql",
            $params + ['pending' => 'pending', 'now' => $now, 'id' => $share->id]
        );
        // Another request may have won the race between the read above and
        // this update; if the row was not ours to move, do not queue.
        $after = $DB->get_record('local_oerclient_shares', ['id' => $share->id], 'status, timemodified');
        if (!$after || $after->status !== 'pending' || (int) $after->timemodified !== $now) {
            return false;
        }

        $task = new \local_oerclient\task\share_upload_task();
        $task->set_custom_data(['shareid' => (int) $share->id]);
        \core\task\manager::queue_adhoc_task($task, true);

        return true;
    }
}
