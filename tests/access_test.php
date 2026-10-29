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

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Tests for the capability definitions in db/access.php.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class access_test extends \advanced_testcase {
    /**
     * Importing restores a backup written on another site, so the role
     * screens must show the XSS risk core shows for
     * moodle/restore:restoreactivity (security audit OERC-02, 2026-10-28).
     * Read from the installed capability, which is what those screens show.
     */
    public function test_the_import_capability_carries_the_xss_risk(): void {
        $this->resetAfterTest();

        $import = get_capability_info('local/oerclient:import');
        $this->assertNotEmpty($import);
        $this->assertSame(RISK_XSS, (int) $import->riskbitmask & RISK_XSS);
        $this->assertSame(RISK_SPAM, (int) $import->riskbitmask & RISK_SPAM);

        $restore = get_capability_info('moodle/restore:restoreactivity');
        $this->assertSame(RISK_XSS, (int) $restore->riskbitmask & RISK_XSS);
    }
}
