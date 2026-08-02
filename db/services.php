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
 * External function declarations.
 *
 * This plugin is a web-service CLIENT, not a provider: everything it does
 * across sites it does by calling the Exchange. The one function here is
 * AJAX-only and never published as a service — it exists so a teacher's own
 * browser can watch their share progress without reloading the page.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_oerclient_get_share_state' => [
        'classname'   => 'local_oerclient\external\get_share_state',
        'methodname'  => 'execute',
        'description' => 'Progress of one of the caller\'s own shares, for the share status page.',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
