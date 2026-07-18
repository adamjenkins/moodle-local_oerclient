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

namespace local_oerclient\task;

use local_oerclient\local\exchange_client;

/**
 * Adhoc task: build a sanitized (users=false) backup of a share's
 * course/activity, upload it to the Exchange, and publish it. See
 * DESIGN.md §1 flow 1 "Share".
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class share_upload_task extends \core\task\adhoc_task {
    #[\Override]
    public function execute() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $data = $this->get_custom_data();
        $shareid = (int) $data->shareid;

        $share = $DB->get_record('local_oerclient_shares', ['id' => $shareid]);
        if (!$share) {
            mtrace("local_oerclient: share {$shareid} no longer exists, skipping.");
            return;
        }

        $tmppath = null;
        try {
            // The synchronous share.php request checked local/oerclient:share at
            // submission time, but this task can run well after that (queued
            // adhoc tasks are picked up by the next cron run) — re-check it here
            // against the sharing user's *current* capability so a role
            // change/unenrolment between submission and execution can't let a
            // course/activity backup still go out to the Exchange (MDL Shield
            // audit finding, 2026-07-18: async sinks must re-check capabilities,
            // not just trust that the synchronous request-time check still holds).
            $sharecontext = \context_course::instance($share->courseid);
            if (!has_capability('local/oerclient:share', $sharecontext, $share->userid)) {
                throw new \moodle_exception('error_sharecapabilitylost', 'local_oerclient');
            }

            $this->set_status($shareid, 'backingup');

            $link = $DB->get_record('local_oerclient_link', ['userid' => $share->userid]);
            if (!$link) {
                throw new \moodle_exception('error_notlinked', 'local_oerclient');
            }

            $tmppath = $this->run_backup($share);

            $this->set_status($shareid, 'uploading');

            $exchangeurl = get_config('local_oerclient', 'exchangeurl');
            $siteid = (int) get_config('local_oerclient', 'siteid');
            $client = new exchange_client($exchangeurl);

            $filename = basename($tmppath);
            $draftitemid = $client->upload_file($link->token, $tmppath, $filename);

            $response = $client->call('local_oerexchange_publish_resource', [
                'siteid' => $siteid,
                'draftitemid' => $draftitemid,
                'type' => $share->type,
                'title' => $share->title,
                'summary' => $share->summary ?? '',
                'language' => $share->language ?? '',
                'tags' => $share->tags ?? '',
                'licenseshortname' => $share->licenseshortname ?? '',
                'activitytype' => $share->activitytype ?? '',
                'resourceid' => $this->find_existing_resource_id($share),
            ], $link->token);

            $DB->update_record('local_oerclient_shares', (object) [
                'id' => $shareid,
                'status' => 'published',
                'exchangeresourceid' => $response['resourceid'],
                'errormessage' => null,
                'timemodified' => time(),
            ]);
        } catch (\Throwable $e) {
            $DB->update_record('local_oerclient_shares', (object) [
                'id' => $shareid,
                'status' => 'failed',
                'errormessage' => $e->getMessage(),
                'timemodified' => time(),
            ]);
        } finally {
            // The run_backup() step writes the .mbz into a per-share temp dir.
            // On the failure path (upload/publish threw) neither the (potentially
            // large) backup file nor its dir was being removed — they only got
            // swept by core's week-old temp-file cleanup task. Remove the whole
            // per-share dir on every exit path so a run of failing shares can't
            // pile .mbz files up on disk.
            if ($tmppath !== null) {
                $tmpdir = dirname($tmppath);
                if (is_dir($tmpdir)) {
                    fulldelete($tmpdir);
                }
            }
        }
    }

    /**
     * Finds a prior successful share of the same course/activity by the same
     * user, so re-sharing (e.g. after updating the course) adds a new
     * version to the existing Exchange catalogue entry instead of creating
     * a duplicate one — local_oerexchange_publish_resource has always
     * supported this via its 'resourceid' param, and resource_manager::
     * publish() already versions when given one, but this call site never
     * passed it (found live, 2026-07-19, while re-sharing a course for the
     * walkthrough docs produced a second catalogue entry instead of a new
     * version of the first).
     *
     * @param \stdClass $share
     * @return int existing resource id to add a version to, or 0 for new
     */
    protected function find_existing_resource_id(\stdClass $share): int {
        global $DB;

        $conditions = [
            'userid' => $share->userid,
            'courseid' => $share->courseid,
            'cmid' => $share->cmid ?: null,
            'status' => 'published',
        ];
        $previous = $DB->get_records('local_oerclient_shares', $conditions, 'timemodified DESC', 'exchangeresourceid', 0, 1);
        $previous = reset($previous);

        return $previous ? (int) $previous->exchangeresourceid : 0;
    }

    /**
     * Builds a sanitized (no user data) backup of a share's course or activity.
     *
     * @param \stdClass $share
     * @return string temp path to the produced .mbz
     */
    protected function run_backup(\stdClass $share): string {
        global $CFG;

        if ($share->type === 'activity' && !empty($share->cmid)) {
            $bc = new \backup_controller(
                \backup::TYPE_1ACTIVITY,
                $share->cmid,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $share->userid
            );
        } else {
            $bc = new \backup_controller(
                \backup::TYPE_1COURSE,
                $share->courseid,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $share->userid
            );
        }

        // The backup_controller constructor already runs check_security,
        // which locks the users setting to false for any executing user
        // who lacks the backup:userinfo capability - ordinary teachers,
        // not just admins/managers. A locked setting throws on any further
        // set_value call, even to its current value, so unconditionally
        // forcing false here broke sharing for every teacher without that
        // capability (found in the 2026-07-19 MDL Shield audit pass). Only
        // force it when it is still ours to set.
        $userssetting = $bc->get_plan()->get_setting('users');
        if ($userssetting->get_status() === \base_setting::NOT_LOCKED) {
            $userssetting->set_value(false);
        }
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $tmpdir = make_temp_directory('oerclient/share_' . $share->id);
        $tmppath = $tmpdir . '/' . $file->get_filename();
        $file->copy_content_to($tmppath);

        $bc->destroy();

        return $tmppath;
    }

    /**
     * Updates a share's status and timemodified.
     *
     * @param int $shareid
     * @param string $status
     */
    protected function set_status(int $shareid, string $status): void {
        global $DB;
        $DB->set_field('local_oerclient_shares', 'status', $status, ['id' => $shareid]);
        $DB->set_field('local_oerclient_shares', 'timemodified', time(), ['id' => $shareid]);
    }
}
