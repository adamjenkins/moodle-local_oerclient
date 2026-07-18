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
 * Site registration with the Exchange (admin action).
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_oerclient\local\exchange_client;

require(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/oerclient/register.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('registertitle', 'local_oerclient'));
$PAGE->set_heading(get_string('registertitle', 'local_oerclient'));

$doregister = optional_param('doregister', 0, PARAM_INT);
if ($doregister && confirm_sesskey()) {
    $exchangeurl = get_config('local_oerclient', 'exchangeurl');
    $contact = required_param('contact', PARAM_EMAIL);
    $client = new exchange_client($exchangeurl);
    $siteid = $client->register($SITE->fullname, $CFG->wwwroot, $contact);
    set_config('siteid', $siteid, 'local_oerclient');
    \core\notification::success(get_string('registersuccess', 'local_oerclient'));
    redirect(new moodle_url('/local/oerclient/register.php'));
}

echo $OUTPUT->header();

$exchangeurl = get_config('local_oerclient', 'exchangeurl');
$siteid = get_config('local_oerclient', 'siteid');
$sitetoken = get_config('local_oerclient', 'sitetoken');

if (empty($exchangeurl)) {
    echo $OUTPUT->notification(get_string('error_noexchangeurl', 'local_oerclient'), 'warning');
} else if (!empty($siteid) && !empty($sitetoken)) {
    echo $OUTPUT->notification(get_string('registeractive', 'local_oerclient', $siteid), 'success');
} else if (!empty($siteid)) {
    echo $OUTPUT->notification(get_string('registerpending', 'local_oerclient', $siteid), 'info');
} else {
    echo html_writer::tag('p', get_string('registerintro', 'local_oerclient'));
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/local/oerclient/register.php')]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'doregister', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::tag('label', get_string('sitecontact', 'local_oerclient'));
    echo html_writer::empty_tag('input', ['type' => 'email', 'name' => 'contact', 'class' => 'form-control mb-2', 'required' => 'required']);
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('registerbutton', 'local_oerclient'), 'class' => 'btn btn-primary']);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
