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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/oerclient/tests/fixtures/stub_exchange_client.php');

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
    public function test_a_licence_the_exchange_accepts_is_valid(): void {
        $this->resetAfterTest();
        set_config('exchangeurl', 'https://exchange.invalid', 'local_oerclient');
        set_config('sitetoken', 'abc123', 'local_oerclient');

        $stub = new stub_exchange_client('https://exchange.invalid');
        $stub->response = ['acceptedlicenses' => 'cc-sa-4.0,cc-4.0'];

        $this->assertTrue(share_manager::is_valid_license('cc-sa-4.0', $stub));
    }

    public function test_a_core_licence_the_exchange_does_not_accept_is_refused(): void {
        $this->resetAfterTest();
        set_config('exchangeurl', 'https://exchange.invalid', 'local_oerclient');
        set_config('sitetoken', 'abc123', 'local_oerclient');

        $stub = new stub_exchange_client('https://exchange.invalid');
        $stub->response = ['acceptedlicenses' => 'cc-sa-4.0'];

        // 'unknown' is core's own always-present licence — being real on this
        // site is exactly what no longer makes a licence acceptable.
        $this->assertFalse(share_manager::is_valid_license('unknown', $stub));
        $this->assertFalse(share_manager::is_valid_license('<script>alert(1)</script>', $stub));
        $this->assertFalse(share_manager::is_valid_license('', $stub));
    }
}
