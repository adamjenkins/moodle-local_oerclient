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
use core\navigation\views\secondary;

/**
 * Tests for hook_listener::add_share_link(). Found live (not by this test
 * suite) on 2026-07-18: the original code used empty($PAGE->course) as its
 * "is this a real course page" guard. moodle_page's __isset() can report
 * false — making empty() true — even while ->course->id resolves correctly
 * via __get(), so the guard silently bailed on every real course page and
 * the "Share to OER Exchange" link never appeared anywhere. This test
 * exercises the same node-adding logic directly; the authoritative
 * verification for this specific class of bug was live HTTP against a real
 * page (a PHPUnit-constructed $PAGE does not necessarily reproduce the same
 * __isset()/__get() divergence a real request does) — see
 * dev-docs/oer-platform/WALKTHROUGH.md for the live-verified screenshots.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oerclient\hook_listener
 */
final class hook_listener_test extends \advanced_testcase {
    public function test_adds_the_share_node_on_a_real_course_page_for_an_editing_teacher(): void {
        global $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));

        $secondaryview = new secondary($PAGE);
        $hook = new secondary_extend($secondaryview);

        hook_listener::add_share_link($hook);

        $node = $secondaryview->get('oerclientshare');
        $this->assertNotNull($node, 'the share node must be added for a course teacher');
        $this->assertStringContainsString(
            (string) $course->id,
            $node->action->out(false)
        );
    }

    public function test_does_not_add_the_node_on_the_site_course(): void {
        global $PAGE, $SITE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $PAGE->set_course($SITE);
        $PAGE->set_context(\context_course::instance($SITE->id));

        $secondaryview = new secondary($PAGE);
        $hook = new secondary_extend($secondaryview);

        hook_listener::add_share_link($hook);

        $this->assertFalse($secondaryview->get('oerclientshare'));
    }

    public function test_does_not_add_the_node_for_a_user_without_the_capability(): void {
        global $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $PAGE->set_course($course);
        $PAGE->set_context(\context_course::instance($course->id));

        $secondaryview = new secondary($PAGE);
        $hook = new secondary_extend($secondaryview);

        hook_listener::add_share_link($hook);

        $this->assertFalse($secondaryview->get('oerclientshare'));
    }
}
