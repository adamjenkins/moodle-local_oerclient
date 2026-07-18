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

defined('MOODLE_INTERNAL') || die();

/**
 * CSRF-style state token for the account-linking handshake (index.php ->
 * the Exchange's connect.php -> connect_callback.php).
 *
 * connect_callback.php previously trusted whatever 'linkcode' a request
 * carried, with no check that the code belonged to a linking flow this
 * browser/session actually started. The Exchange mints a linkcode bound
 * only to *its own* logged-in user (local_oerexchange's connect.php), and
 * its redirect preserves any extra query params already on the callback
 * URL we hand it (`$callback . $separator . 'linkcode=' . $code` —
 * confirmed by reading local_oerexchange/connect.php). That means an
 * attacker who starts their own connect flow on the Exchange can hand a
 * victim — already logged into this site — a
 * connect_callback.php?linkcode=... URL and have the victim's Moodle
 * account silently linked to the attacker's Exchange token: every future
 * share the victim makes would then upload using the attacker's Exchange
 * identity, exfiltrating the victim's course content to the attacker's
 * Exchange account. Binding a per-session nonce into the callback URL and
 * verifying it on return closes that gap, the same way an OAuth 'state'
 * parameter does (MDL Shield audit finding, 2026-07-18).
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_state {
    /** @var string Session key the pending state token is stored under. */
    protected const SESSIONKEY = 'local_oerclient_linkstate';

    /**
     * Mint a fresh, session-bound state token and remember it for
     * verify() to check on the way back from the Exchange.
     *
     * @return string
     */
    public static function issue(): string {
        global $SESSION;

        $state = random_string(32);
        $SESSION->{self::SESSIONKEY} = $state;

        return $state;
    }

    /**
     * Verify (and consume) a state token returned from the Exchange.
     * Single-use: calling this a second time with the same pending state
     * always fails, whether or not the first call matched.
     *
     * @param string $state
     * @return bool
     */
    public static function verify(string $state): bool {
        global $SESSION;

        $expected = $SESSION->{self::SESSIONKEY} ?? null;
        unset($SESSION->{self::SESSIONKEY});

        return $expected !== null && hash_equals($expected, $state);
    }
}
