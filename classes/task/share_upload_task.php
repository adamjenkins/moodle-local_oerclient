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

defined('MOODLE_INTERNAL') || die();

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

        try {
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
            ], $link->token);

            $DB->update_record('local_oerclient_shares', (object) [
                'id' => $shareid,
                'status' => 'published',
                'exchangeresourceid' => $response['resourceid'],
                'errormessage' => null,
                'timemodified' => time(),
            ]);

            @unlink($tmppath);
        } catch (\Throwable $e) {
            $DB->update_record('local_oerclient_shares', (object) [
                'id' => $shareid,
                'status' => 'failed',
                'errormessage' => $e->getMessage(),
                'timemodified' => time(),
            ]);
        }
    }

    /**
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

        $bc->get_plan()->get_setting('users')->set_value(false);
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
     * @param int $shareid
     * @param string $status
     */
    protected function set_status(int $shareid, string $status): void {
        global $DB;
        $DB->set_field('local_oerclient_shares', 'status', $status, ['id' => $shareid]);
        $DB->set_field('local_oerclient_shares', 'timemodified', time(), ['id' => $shareid]);
    }
}
