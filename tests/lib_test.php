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

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for local_oerclient_extend_settings_navigation() (lib.php).
 *
 * Found live (not by this test suite) on 2026-07-18: the course-page
 * "Share to OER Exchange" link (hook_listener::add_share_link(), via
 * core\hook\navigation\secondary_extend) never appears on an activity page,
 * because that hook is only dispatched from
 * secondary::load_course_navigation() — secondary::load_module_navigation()
 * has no equivalent dispatch anywhere in Moodle 5.2 core. The real,
 * supported extension point for a module-page settings link is the
 * extend_settings_navigation local-plugin callback, which
 * load_module_navigation() reads via the 'modulesettings' branch. This test
 * exercises that callback directly; the authoritative check was live HTTP
 * against a real activity page (a real single-activity share end-to-end,
 * including the resulting Exchange resource and a successful client import)
 * — see dev-docs/oer-platform/WALKTHROUGH.md for the screenshots.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('local_oerclient_extend_settings_navigation')]
final class lib_test extends \advanced_testcase {
    public function test_adds_the_share_node_on_a_real_activity_page_for_an_editing_teacher(): void {
        global $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $PAGE->set_cm($cm, $course);
        $PAGE->set_context(\context_module::instance($cm->id));
        $PAGE->set_url('/mod/forum/view.php', ['id' => $cm->id]);

        $settingsnav = new \settings_navigation($PAGE);
        $settingsnav->initialise();

        $node = $settingsnav->find('oerclientshareactivity', \settings_navigation::TYPE_SETTING);
        $this->assertNotFalse($node, 'the activity share node must be added for a course teacher');
        $url = $node->action->out(false);
        $this->assertStringContainsString('courseid=' . $course->id, $url);
        $this->assertStringContainsString('cmid=' . $cm->id, $url);
    }

    public function test_does_not_add_the_node_for_a_user_without_the_capability(): void {
        global $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $PAGE->set_cm($cm, $course);
        $PAGE->set_context(\context_module::instance($cm->id));
        $PAGE->set_url('/mod/forum/view.php', ['id' => $cm->id]);

        $settingsnav = new \settings_navigation($PAGE);
        $settingsnav->initialise();

        $node = $settingsnav->find('oerclientshareactivity', \settings_navigation::TYPE_SETTING);
        $this->assertFalse($node, 'the node must not be added for a user without local/oerclient:share');
    }

    public function test_does_not_add_the_node_on_a_course_page(): void {
        global $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);

        $settingsnav = new \settings_navigation($PAGE);
        $settingsnav->initialise();

        $node = $settingsnav->find('oerclientshareactivity', \settings_navigation::TYPE_SETTING);
        $this->assertFalse($node, 'the activity-specific node must not appear on a course page');
    }
}
