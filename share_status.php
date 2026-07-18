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
 * Status of a queued share.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_login();

$id = required_param('id', PARAM_INT);

global $DB, $USER;
$share = $DB->get_record('local_oerclient_shares', ['id' => $id], '*', MUST_EXIST);
if ((int) $share->userid !== (int) $USER->id) {
    require_capability('moodle/site:config', context_system::instance());
}

$PAGE->set_url('/local/oerclient/share_status.php', ['id' => $id]);
$PAGE->set_context(context_course::instance($share->courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('sharestatustitle', 'local_oerclient'));
$PAGE->set_heading(get_string('sharestatustitle', 'local_oerclient'));

echo $OUTPUT->header();

echo html_writer::tag('p', get_string(
    'sharestatuslabel',
    'local_oerclient',
    get_string('sharestatus_' . $share->status, 'local_oerclient')
));

if ($share->status === 'failed' && $share->errormessage) {
    echo $OUTPUT->notification(s($share->errormessage), 'error');
}

if ($share->status === 'published' && $share->exchangeresourceid) {
    $exchangeurl = get_config('local_oerclient', 'exchangeurl');
    $resourceurl = rtrim($exchangeurl, '/') . '/local/oerexchange/resource.php?id=' . $share->exchangeresourceid;
    echo html_writer::link(
        $resourceurl,
        get_string('viewonexchange', 'local_oerclient'),
        ['class' => 'btn btn-primary', 'target' => '_blank']
    );
}

echo $OUTPUT->footer();
