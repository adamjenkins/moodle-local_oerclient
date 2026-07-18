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

use local_oerclient\local\import_manager;

/**
 * Tests for import_manager::require_import_capability() — the capability
 * re-scoping fixed for MDL Shield audit finding 1c (2026-07-18): course-type
 * import now checks moodle/course:create (system context, correct for
 * "create a brand-new course"), while activity-type import checks our own
 * local/oerclient:import at the real target course context.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oerclient\local\import_manager
 */
final class import_manager_test extends \advanced_testcase {
    public function test_activity_import_without_targetcourseid_throws_moodle_exception(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\moodle_exception::class);
        import_manager::require_import_capability('activity', null);
    }

    public function test_activity_import_denies_user_without_capability_in_target_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        // Deliberately not enrolled/given any role in the course.
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        import_manager::require_import_capability('activity', (int) $course->id);
    }

    public function test_activity_import_allows_editingteacher_in_target_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        // Should not throw.
        import_manager::require_import_capability('activity', (int) $course->id);
        $this->assertTrue(true);
    }

    public function test_course_import_denies_user_without_course_create_capability(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        import_manager::require_import_capability('course', null);
    }

    public function test_course_import_allows_user_with_course_create_capability(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        // Should not throw — this is exactly the case audit finding 1c
        // flagged as broken (ordinary teachers with only a course-level role
        // used to fail the old system-context local/oerclient:import check).
        import_manager::require_import_capability('course', null);
        $this->assertTrue(true);
    }

    /**
     * A course-type import creates the target course *before* the restore
     * runs. If the restore then fails (bad backup, failed precheck, restore
     * exception), that fresh course must be rolled back rather than left
     * behind as an empty orphan.
     */
    public function test_restore_into_rolls_back_a_created_course_when_the_backup_is_invalid(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $user = $this->getDataGenerator()->create_user();
        // Stand in for the fresh course import() would have just created.
        $created = $this->getDataGenerator()->create_course();
        $this->assertTrue($DB->record_exists('course', ['id' => $created->id]));

        // A file that is not a valid .mbz — extraction/restore must fail.
        $tmpdir = make_temp_directory('oerclient/testinvalid_' . random_string(4));
        $tmppath = $tmpdir . '/import.mbz';
        file_put_contents($tmppath, 'this is not a real moodle backup');

        $method = new \ReflectionMethod(import_manager::class, 'restore_into');
        $method->setAccessible(true);

        $threw = false;
        try {
            // Args: tmppath, courseid, type, userid, createdcourseid.
            $method->invoke(null, $tmppath, (int) $created->id, 'course', $user->id, (int) $created->id);
        } catch (\Throwable $e) {
            $threw = true;
        }

        // The zip packer emits a debugging() call when handed a non-zip file.
        $this->resetDebugging();

        $this->assertTrue($threw, 'restore_into should rethrow on an invalid backup');
        $this->assertFalse(
            $DB->record_exists('course', ['id' => $created->id]),
            'the created course should have been rolled back'
        );
        $this->assertFileDoesNotExist($tmppath, 'the temp .mbz should have been cleaned up');
    }

    /**
     * Conversely, an *activity*-type import restores into an existing course
     * the user chose — a failed restore there must NOT delete that course
     * (only courses this import itself created are rolled back).
     */
    public function test_restore_into_does_not_delete_a_pre_existing_target_course_on_failure(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $user = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_course();

        $tmpdir = make_temp_directory('oerclient/testinvalid_' . random_string(4));
        $tmppath = $tmpdir . '/import.mbz';
        file_put_contents($tmppath, 'this is not a real moodle backup');

        $method = new \ReflectionMethod(import_manager::class, 'restore_into');
        $method->setAccessible(true);

        $threw = false;
        try {
            // The final null createdcourseid means this import did not create the target.
            $method->invoke(null, $tmppath, (int) $target->id, 'activity', $user->id, null);
        } catch (\Throwable $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'restore_into should rethrow on an invalid backup');

        // The zip packer emits a debugging() call when handed a non-zip file.
        $this->resetDebugging();

        $this->assertTrue(
            $DB->record_exists('course', ['id' => $target->id]),
            'a pre-existing target course must survive a failed activity import'
        );
    }
}
