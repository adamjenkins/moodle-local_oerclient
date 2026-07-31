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
// Classes under tests/fixtures/ are NOT autoloaded — only classes/ is. Without
// this require the stub resolves to nothing and every test here errors with
// "Class not found", which reads like the class under test is missing.
require_once($CFG->dirroot . '/local/oerclient/tests/fixtures/stub_exchange_client.php');

/**
 * Tests for allowed_licenses — the Exchange, not this site, decides which
 * licences a share may use.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(allowed_licenses::class)]
final class allowed_licenses_test extends \advanced_testcase {
    /**
     * Puts the site in the registered state every fetch path requires.
     */
    protected function register(): void {
        set_config('exchangeurl', 'https://exchange.invalid', 'local_oerclient');
        set_config('sitetoken', 'abc123', 'local_oerclient');
    }

    /**
     * Builds a stub that answers with the given accepted-licences string.
     *
     * @param string $accepted
     * @return stub_exchange_client
     */
    protected function stub(string $accepted): stub_exchange_client {
        $stub = new stub_exchange_client('https://exchange.invalid');
        $stub->response = ['acceptedlicenses' => $accepted];
        return $stub;
    }

    public function test_a_successful_fetch_is_returned_and_stored(): void {
        $this->resetAfterTest();
        $this->register();

        $state = allowed_licenses::state($this->stub('cc-4.0,cc-sa-4.0'));

        $this->assertSame(['cc-4.0', 'cc-sa-4.0'], $state['shortnames']);
        $this->assertTrue($state['live']);
        $this->assertNotNull($state['confirmed']);
        $this->assertSame('cc-4.0,cc-sa-4.0', get_config('local_oerclient', 'acceptedlicenses'));
    }

    public function test_a_fresh_stored_list_is_used_without_calling_the_exchange(): void {
        $this->resetAfterTest();
        $this->register();
        set_config('acceptedlicenses', 'cc-nd-4.0', 'local_oerclient');
        set_config('acceptedlicensestime', time(), 'local_oerclient');

        $stub = $this->stub('cc-4.0');
        $state = allowed_licenses::state($stub);

        $this->assertSame(['cc-nd-4.0'], $state['shortnames']);
        $this->assertSame(0, $stub->calls);
    }

    public function test_a_failed_fetch_falls_back_to_the_stored_list(): void {
        $this->resetAfterTest();
        $this->register();
        set_config('acceptedlicenses', 'cc-sa-4.0', 'local_oerclient');
        set_config('acceptedlicensestime', time() - (allowed_licenses::CACHE_TTL + 60), 'local_oerclient');

        $stub = $this->stub('');
        $stub->fail = true;
        $state = allowed_licenses::state($stub);

        $this->assertSame(['cc-sa-4.0'], $state['shortnames']);
        $this->assertFalse($state['live']);
        $this->assertNotNull($state['confirmed']);
    }

    public function test_a_failed_fetch_with_nothing_stored_yields_no_list_and_no_confirmation(): void {
        $this->resetAfterTest();
        $this->register();

        $stub = $this->stub('');
        $stub->fail = true;
        $state = allowed_licenses::state($stub);

        $this->assertSame([], $state['shortnames']);
        $this->assertNull($state['confirmed']);
        $this->assertFalse($state['live']);
    }

    public function test_an_exchange_that_accepts_nothing_is_recorded_as_confirmed_and_empty(): void {
        $this->resetAfterTest();
        $this->register();

        $state = allowed_licenses::state($this->stub(''));

        $this->assertSame([], $state['shortnames']);
        $this->assertNotNull($state['confirmed'], 'an empty answer is still an answer');
        $this->assertTrue($state['live']);
    }

    public function test_an_unregistered_site_is_never_asked(): void {
        $this->resetAfterTest();

        $stub = $this->stub('cc-4.0');
        $state = allowed_licenses::state($stub);

        $this->assertSame(0, $stub->calls);
        $this->assertSame([], $state['shortnames']);
        $this->assertNull($state['confirmed']);
    }
}
