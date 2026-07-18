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
 * OER Client landing page.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oerclient\local\exchange_client;
use local_oerclient\local\link_state;

require(__DIR__ . '/../../config.php');
require_login();

$PAGE->set_url('/local/oerclient/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_oerclient'));
$PAGE->set_heading(get_string('pluginname', 'local_oerclient'));

global $DB, $USER;
$link = $DB->get_record('local_oerclient_link', ['userid' => $USER->id]);
$exchangeurl = get_config('local_oerclient', 'exchangeurl');
$siteid = (int) get_config('local_oerclient', 'siteid');

echo $OUTPUT->header();

if (empty($exchangeurl) || empty($siteid)) {
    echo $OUTPUT->notification(get_string('error_notregistered', 'local_oerclient'), 'warning');
} else if (!$link) {
    echo html_writer::tag('p', get_string('connectintro', 'local_oerclient'));
    $client = new exchange_client($exchangeurl);
    $callback = new moodle_url('/local/oerclient/connect_callback.php', ['state' => link_state::issue()]);
    $connecturl = $client->connect_url($siteid, $callback);
    echo html_writer::link($connecturl, get_string('linkaccount', 'local_oerclient'), ['class' => 'btn btn-primary']);
} else {
    echo $OUTPUT->notification(get_string('linkedas', 'local_oerclient', $link->exchangeuserid), 'success');
}

echo html_writer::tag('h4', get_string('browseexchange', 'local_oerclient'), ['class' => 'mt-4']);
echo html_writer::link(new moodle_url('/local/oerclient/browse.php'), get_string('browseexchange', 'local_oerclient'), ['class' => 'btn btn-outline-primary']);

echo $OUTPUT->footer();
