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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for share_manager::is_valid_license() — the license re-validation
 * added for MDL Shield audit finding (2026-07-18): share.php's POST handler
 * stored whatever 'licenseshortname' string the client submitted without
 * checking it was actually one of the options the <select> offered.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(share_manager::class)]
final class share_manager_test extends \advanced_testcase {
    public function test_a_real_core_license_shortname_is_valid(): void {
        $this->resetAfterTest();

        // The 'unknown' license is core's own always-present fallback.
        $this->assertTrue(share_manager::is_valid_license('unknown'));
    }

    public function test_an_arbitrary_client_supplied_string_is_not_valid(): void {
        $this->resetAfterTest();

        $this->assertFalse(share_manager::is_valid_license('<script>alert(1)</script>'));
        $this->assertFalse(share_manager::is_valid_license('not-a-real-license'));
        $this->assertFalse(share_manager::is_valid_license(''));
    }
}
