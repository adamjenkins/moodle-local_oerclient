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

/**
 * Version information for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_oerclient';
$plugin->version   = 2026072901;
// 2025041400 = the Moodle 5.0 branching version. The previous value
// (2024100700) was Moodle 4.5 while its comment claimed 5.0 — and this
// plugin hard-depends on core\navigation\navigation_node, which only exists
// under that namespace since 5.0 (MDL-82159), so a 4.5 install would fatal
// on every course page for a sharer.
$plugin->requires  = 2025041400;
$plugin->supported = [500, 502];
$plugin->release   = '1.0.1';
$plugin->maturity  = MATURITY_STABLE;
