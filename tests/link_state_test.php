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
 * Tests for link_state — the account-linking CSRF/state-token guard added
 * for an MDL Shield audit finding (2026-07-18): connect_callback.php
 * previously trusted any 'linkcode' with no check that it belonged to a
 * linking flow the current session actually started, letting an attacker
 * link a victim's Moodle account to the attacker's own Exchange token.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_oerclient\local\link_state
 */
final class link_state_test extends \advanced_testcase {
    public function test_a_freshly_issued_state_verifies_once(): void {
        $this->resetAfterTest();

        $state = link_state::issue();
        $this->assertNotSame('', $state);
        $this->assertTrue(link_state::verify($state));
    }

    public function test_a_state_can_only_be_verified_once(): void {
        $this->resetAfterTest();

        $state = link_state::issue();
        $this->assertTrue(link_state::verify($state));
        // Replaying the same (now-consumed) state must fail.
        $this->assertFalse(link_state::verify($state));
    }

    public function test_an_attacker_supplied_state_that_was_never_issued_fails(): void {
        $this->resetAfterTest();

        $this->assertFalse(link_state::verify('attacker-guessed-or-reused-state'));
    }

    public function test_a_mismatched_state_fails_and_still_consumes_the_pending_one(): void {
        $this->resetAfterTest();

        $state = link_state::issue();
        $this->assertFalse(link_state::verify('not-the-real-state'));
        // The legitimate state must not still be usable after a failed attempt.
        $this->assertFalse(link_state::verify($state));
    }
}
