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
 * The licences a share from this site may use — as decided by the Exchange.
 *
 * The Exchange is the sole authority: it validates every publish against its
 * own allowed list (resource_manager::publish()), so a licence this site
 * happens to know, or happens to have disabled, is irrelevant. This class
 * asks, remembers the answer, and falls back to the last one it was given
 * rather than guessing — a guess would re-create the late failure on
 * share_status.php that asking exists to prevent.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allowed_licenses {
    /** @var int seconds a stored answer is served before asking again */
    const CACHE_TTL = 900;

    /** @var string preselected whenever the Exchange allows it */
    const PREFERRED_DEFAULT = 'cc-sa-4.0';

    /**
     * The Exchange's current list, with how current it actually is.
     *
     * 'confirmed' === null means this site has never had an answer — which is
     * different from an Exchange that answered "no licences at all", and the
     * two must not be collapsed: the first is a connectivity problem, the
     * second is a deliberate configuration.
     *
     * @param exchange_client|null $client injected by tests
     * @return array{shortnames: string[], confirmed: int|null, live: bool}
     */
    public static function state(?exchange_client $client = null): array {
        $stored = get_config('local_oerclient', 'acceptedlicenses');
        $confirmed = (int) get_config('local_oerclient', 'acceptedlicensestime');
        $hasstored = $stored !== false;

        if ($hasstored && $confirmed > 0 && (time() - $confirmed) < self::CACHE_TTL) {
            return self::result(self::split($stored), $confirmed, true);
        }

        $exchangeurl = (string) get_config('local_oerclient', 'exchangeurl');
        $sitetoken = (string) get_config('local_oerclient', 'sitetoken');
        if ($exchangeurl === '' || $sitetoken === '') {
            // Not registered: there is nobody to ask. Serve whatever was
            // stored before the site was unregistered, if anything.
            return self::result(
                $hasstored ? self::split($stored) : [],
                $hasstored ? $confirmed : null,
                false
            );
        }

        $client = $client ?? new exchange_client($exchangeurl);
        try {
            $response = $client->call('local_oerexchange_get_config', [], $sitetoken);
            $raw = (string) ($response['acceptedlicenses'] ?? '');
            $now = time();
            set_config('acceptedlicenses', $raw, 'local_oerclient');
            set_config('acceptedlicensestime', $now, 'local_oerclient');
            return self::result(self::split($raw), $now, true);
        } catch (\Throwable $e) {
            return self::result(
                $hasstored ? self::split($stored) : [],
                $hasstored ? $confirmed : null,
                false
            );
        }
    }

    /**
     * @param string[] $shortnames
     * @param int|null $confirmed
     * @param bool $live
     * @return array{shortnames: string[], confirmed: int|null, live: bool}
     */
    protected static function result(array $shortnames, ?int $confirmed, bool $live): array {
        return ['shortnames' => $shortnames, 'confirmed' => $confirmed ?: null, 'live' => $live];
    }

    /**
     * @param string $raw comma-separated shortnames
     * @return string[]
     */
    protected static function split(string $raw): array {
        return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
    }
}
