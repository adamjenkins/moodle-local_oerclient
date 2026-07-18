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
 * Account-linking handshake, client side: receives the one-time linkcode
 * from the Exchange's connect.php redirect, exchanges it for a personal
 * token, and stores the link. See DESIGN.md §1.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oerclient\local\exchange_client;

require(__DIR__ . '/../../config.php');
require_login();

$linkcode = required_param('linkcode', PARAM_ALPHANUM);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$PAGE->set_url('/local/oerclient/connect_callback.php', ['linkcode' => $linkcode]);
$PAGE->set_context(context_system::instance());

$exchangeurl = get_config('local_oerclient', 'exchangeurl');
$client = new exchange_client($exchangeurl);
$result = $client->consume_linkcode($linkcode);

global $DB, $USER;
$existing = $DB->get_record('local_oerclient_link', ['userid' => $USER->id]);
if ($existing) {
    $DB->update_record('local_oerclient_link', (object) [
        'id' => $existing->id,
        'exchangeuserid' => $result['userid'],
        'token' => $result['token'],
        'timecreated' => time(),
    ]);
} else {
    $DB->insert_record('local_oerclient_link', (object) [
        'userid' => $USER->id,
        'exchangeuserid' => $result['userid'],
        'token' => $result['token'],
        'timecreated' => time(),
    ]);
}

\core\notification::success(get_string('connectsuccess', 'local_oerclient'));
redirect($returnurl !== '' ? new moodle_url($returnurl) : new moodle_url('/local/oerclient/index.php'));
