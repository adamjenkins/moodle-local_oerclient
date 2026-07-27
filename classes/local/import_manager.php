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
 * Download a resource's .mbz from the Exchange and restore it: a new course
 * for a course-type resource, or into the current course for an
 * activity-type resource. See DESIGN.md §1 flow 2 "Browse/import".
 *
 * Synchronous by design (v1 simplification vs. the "import task" wording in
 * DESIGN.md) — restore is already a synchronous web-request operation
 * elsewhere in core (e.g. course/restorefile.php), so this matches existing
 * Moodle UX rather than adding queueing complexity for comparable wait time.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_manager {
    /**
     * Authorize an import for the current user. Activity-type resources have
     * a real target course context to check our own capability against;
     * course-type resources create a brand-new course, for which the correct
     * gate is core's own moodle/course:create (local/oerclient:import is
     * declared course-scoped and would be checked against no real context —
     * see MDL Shield audit finding 1c, 2026-07-18).
     *
     * @param string $resourcetype 'course'|'activity'
     * @param int|null $targetcourseid required for 'activity'
     * @throws \required_capability_exception|\moodle_exception
     */
    public static function require_import_capability(string $resourcetype, ?int $targetcourseid): void {
        if ($resourcetype === 'activity') {
            if (!$targetcourseid) {
                throw new \moodle_exception('error_targetcourserequired', 'local_oerclient');
            }
            require_capability('local/oerclient:import', \context_course::instance($targetcourseid));
        } else {
            require_capability('moodle/course:create', \context_system::instance());
        }
    }

    /**
     * Downloads a resource's backup and restores it as a new course, or into an existing one for a single activity.
     *
     * @param array $resource decoded local_oerexchange_get_resource response
     * @param int $userid importing user
     * @param int|null $targetcourseid required when the resource is an activity — the
     *                                 course to import the activity into
     * @return array {courseid, checklist} — checklist is a list of localization hints
     */
    public static function import(array $resource, int $userid, ?int $targetcourseid = null): array {
        global $CFG, $DB;

        // Neither of these is loaded on every request, and both are reached on
        // ordinary paths through this method: create_course() below for a
        // course-type import, fulldelete() in the finally for every import.
        // Without them a course-type import died with "Call to undefined
        // function local_oerclient\local\create_course()" — and then the
        // finally's fulldelete() died the same way, replacing the real error
        // with a second, more confusing one before it could surface.
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/filelib.php');

        $tmppath = self::download($resource['downloadurl']);

        // The restore_into() helper cleans up on a *restore-stage* failure,
        // but the downloaded .mbz and its temp dir also have to be removed if we
        // never reach the restore at all — e.g. create_course() throwing on a
        // shortname collision when two users import the same resource
        // concurrently (unique_shortname() is a check-then-create with a race
        // window), or the activity-target guard below. A finally guarantees
        // cleanup on every exit path; restore_into() unlinking the same file
        // first is harmless (fulldelete of an already-emptied dir).
        try {
            // Track a course we create for this import (course-type only) so a
            // restore that fails *after* the course exists doesn't leave an empty
            // orphan course behind — see restore_into()'s cleanup.
            $createdcourseid = null;
            if ($resource['type'] === 'activity') {
                if (!$targetcourseid) {
                    throw new \moodle_exception('error_targetcourserequired', 'local_oerclient');
                }
                $courseid = $targetcourseid;
            } else {
                $category = \core_course_category::get_default();
                $newcourse = create_course((object) [
                    'fullname' => $resource['title'],
                    'shortname' => self::unique_shortname($resource['title']),
                    'category' => $category->id,
                    'visible' => 0, // Hidden until the teacher reviews the localization checklist.
                ]);
                $courseid = $newcourse->id;
                $createdcourseid = (int) $newcourse->id;
            }

            self::restore_into($tmppath, (int) $courseid, $resource['type'], $userid, $createdcourseid);

            $DB->insert_record('local_oerclient_imports', (object) [
                'userid' => $userid,
                'exchangeresourceid' => $resource['id'],
                'exchangeversionid' => $resource['versionid'],
                'courseid' => $courseid,
                'timecreated' => time(),
            ]);

            return [
                'courseid' => $courseid,
                'checklist' => self::localization_checklist($resource),
            ];
        } finally {
            $tmpdir = dirname($tmppath);
            if (is_dir($tmpdir)) {
                fulldelete($tmpdir);
            }
        }
    }

    /**
     * Extracts a downloaded .mbz and restores it into $courseid. If the
     * restore fails at any stage and this import created a fresh course for
     * it ($createdcourseid), that now-empty course is deleted so a failed
     * import can't leave an orphan course behind. The temp .mbz is always
     * removed.
     *
     * @param string $tmppath local path to the downloaded .mbz
     * @param int $courseid course to restore into
     * @param string $type 'course'|'activity'
     * @param int $userid restoring user
     * @param int|null $createdcourseid course this import created (to roll back on failure), or null
     * @throws \moodle_exception|\Throwable if the restore fails
     */
    protected static function restore_into(
        string $tmppath,
        int $courseid,
        string $type,
        int $userid,
        ?int $createdcourseid
    ): void {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        // Both delete_course() on the failure path and course_change_visibility()
        // on the success path live here; see import()'s note on why this cannot
        // be left to whatever happens to be loaded already.
        require_once($CFG->dirroot . '/course/lib.php');

        try {
            // The \restore_controller class takes the name of an already-extracted backup
            // temp directory (under $CFG->tempdir/backup/), not a file path — extract the
            // downloaded .mbz there first (same pattern core uses internally, e.g.
            // backup_general_helper::get_backup_information_from_mbz()).
            $backupid = 'oerclientimport_' . time() . '_' . random_string(4);
            $extractdir = make_backup_temp_directory($backupid);
            $fp = get_file_packer('application/vnd.moodle.backup');
            $fp->extract_to_pathname($tmppath, $extractdir);

            $rc = new \restore_controller(
                $backupid,
                $courseid,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $userid,
                $type === 'activity' ? \backup::TARGET_CURRENT_ADDING : \backup::TARGET_NEW_COURSE
            );

            if (!$rc->execute_precheck()) {
                $rc->destroy();
                throw new \moodle_exception('error_restoreprecheckfailed', 'local_oerclient');
            }

            $rc->execute_plan();
            $rc->destroy();

            // A course restore writes the backup's own course settings over the
            // ones the new course was created with — visibility included. So
            // create_course()'s 'visible' => 0 in import() was silently undone
            // here, and every course-type import landed VISIBLE: content pulled
            // in from another site went live to students before anyone had
            // reviewed it, while the post-import checklist told the teacher the
            // opposite ("hidden by default — review it, then make it visible").
            // Re-asserted after the restore, where nothing else overwrites it,
            // and only for a course this import created — never for a
            // pre-existing course the user chose to import an activity into.
            if ($createdcourseid) {
                course_change_visibility($createdcourseid, false);
            }
        } catch (\Throwable $e) {
            if ($createdcourseid) {
                delete_course($createdcourseid, false);
            }
            @unlink($tmppath);
            throw $e;
        }

        @unlink($tmppath);
    }

    /**
     * Downloads a signed resource URL to a local temp file.
     *
     * @param string $url signed download URL
     * @return string local temp path
     */
    protected static function download(string $url): string {
        // The download URL arrives inside the Exchange's own WS response,
        // so a compromised Exchange (or a MITM) could point it anywhere —
        // including internal-network hosts this server can reach but the
        // remote can't. Only fetch from the origin the admin actually
        // configured as the Exchange.
        $expected = parse_url((string) get_config('local_oerclient', 'exchangeurl'));
        $actual = parse_url($url);
        if (
            ($actual['scheme'] ?? '') !== ($expected['scheme'] ?? '')
                || ($actual['host'] ?? '') !== ($expected['host'] ?? '')
                || ($actual['port'] ?? null) !== ($expected['port'] ?? null)
        ) {
            throw new \moodle_exception(
                'exchangeerror',
                'local_oerclient',
                '',
                get_string('error_downloadorigin', 'local_oerclient')
            );
        }

        $tmpdir = make_temp_directory('oerclient/import_' . time() . '_' . random_string(4));
        $tmppath = $tmpdir . '/import.mbz';

        // Same transport policy as every other Exchange request (TLS
        // verified unless the dev-only setting opts out), streamed straight
        // to disk — a whole-course .mbz can be hundreds of MB and must not
        // transit PHP memory.
        $client = new \core\http_client(exchange_client::http_options());
        $client->request('GET', $url, ['sink' => $tmppath]);

        return $tmppath;
    }

    /**
     * Derives a course shortname from a title, disambiguating against existing courses if needed.
     *
     * @param string $title
     * @return string a shortname that doesn't collide with an existing course
     */
    protected static function unique_shortname(string $title): string {
        global $DB;

        $base = clean_param(str_replace(' ', '-', strtolower($title)), PARAM_ALPHANUMEXT) ?: 'oer-import';
        $base = substr($base, 0, 80);
        $candidate = $base;
        $suffix = 1;
        while ($DB->record_exists('course', ['shortname' => $candidate])) {
            $suffix++;
            $candidate = $base . '-' . $suffix;
        }
        return $candidate;
    }

    /**
     * A short, generic set of "you probably want to change these" hints —
     * the post-import localization checklist from DESIGN.md §1.
     *
     * @param array $resource
     * @return string[]
     */
    protected static function localization_checklist(array $resource): array {
        return [
            get_string('checklist_dates', 'local_oerclient'),
            get_string('checklist_names', 'local_oerclient'),
            get_string('checklist_visibility', 'local_oerclient'),
            get_string('checklist_grading', 'local_oerclient'),
        ];
    }
}
