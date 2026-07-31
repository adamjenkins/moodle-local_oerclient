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
 * Test double for exchange_client: returns a canned decoded response, or
 * throws the same exception shape a real transport failure produces.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stub_exchange_client extends exchange_client {
    /** @var array decoded response call() should return */
    public array $response = [];

    /** @var bool when true, call() throws instead of returning */
    public bool $fail = false;

    /** @var int how many times call() was invoked */
    public int $calls = 0;

    /**
     * Records the call and returns the canned response, or throws if $fail.
     *
     * @param string $function
     * @param array $params
     * @param string $token
     * @return array
     */
    public function call(string $function, array $params, string $token): array {
        $this->calls++;
        if ($this->fail) {
            throw new \moodle_exception('exchangeerror', 'local_oerclient');
        }
        return $this->response;
    }
}
