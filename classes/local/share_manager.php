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
 * Small helpers for the share wizard that need to be independently
 * testable rather than living inline in share.php.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class share_manager {
    /**
     * share.php's form builds its license <select> from
     * \license_manager::get_licenses(), but the POST handler previously
     * stored whatever 'licenseshortname' string the client submitted without
     * checking it was actually one of the offered options — the same
     * un-revalidated-select-value pattern MDL Shield flagged in
     * mod_confcheckin's paymentaccountid (2026-07-18 audit, class 1).
     * A share with a spoofed license string is misinformation once published
     * to the Exchange catalogue, so re-validate here against the same menu
     * the form was built from.
     *
     * @param string $licenseshortname
     * @return bool true iff $licenseshortname is one of the licenses core offers
     */
    public static function is_valid_license(string $licenseshortname): bool {
        global $CFG;
        require_once($CFG->libdir . '/licenselib.php');

        foreach (\license_manager::get_licenses() as $license) {
            if ($license->shortname === $licenseshortname) {
                return true;
            }
        }

        return false;
    }
}
