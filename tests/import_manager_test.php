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
}
