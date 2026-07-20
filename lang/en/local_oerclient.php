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
 * English language strings for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['browseexchange'] = 'Browse OER Exchange';
$string['checklist_dates'] = 'Activity due dates and course start date — these came from the original course and likely need updating.';
$string['checklist_grading'] = 'Grade items and grading scales — check they match your gradebook setup.';
$string['checklist_names'] = 'Any names, institutions, or contact details mentioned in activity content.';
$string['checklist_visibility'] = 'The imported course is hidden by default — review it, then make it visible to students.';
$string['connectintro'] = 'Link your personal account on the Exchange to share and review resources as yourself.';
$string['connectsuccess'] = 'Your account is now linked to the Exchange.';
$string['createdby'] = 'Created by {$a}';
$string['downloadbutton'] = 'Download';
$string['error_invalidlicense'] = 'Choose a license from the list provided.';
$string['error_invalidlinkstate'] = 'This account-linking request could not be verified. Please try linking your account again.';
$string['error_noexchangeurl'] = 'Set the Exchange URL in settings first.';
$string['error_notargetcourses'] = 'You do not have permission to import into any course.';
$string['error_notlinked'] = 'Link your personal Exchange account first.';
$string['error_notregistered'] = 'This site is not yet registered (or not yet approved) with an OER Exchange. See Site administration > Plugins > OER Client > General settings.';
$string['error_restoreprecheckfailed'] = 'The restore precheck failed for this backup.';
$string['error_sharecapabilitylost'] = 'You no longer have permission to share this course or activity.';
$string['error_targetcourserequired'] = 'A target course is required to import a single activity.';
$string['exchangeerror'] = 'Exchange error: {$a}';
$string['filterbytype'] = 'Type';
$string['generalsettings'] = 'General settings';
$string['gotocourse'] = 'Go to course';
$string['importbutton'] = 'Import';
$string['importheading'] = 'Import this resource';
$string['importresulttitle'] = 'Import complete';
$string['importsuccess'] = 'The resource was imported successfully.';
$string['importtargetcourse'] = 'Import into course';
$string['licenselabel'] = 'License: {$a}';
$string['linkaccount'] = 'Link my Exchange account';
$string['linkedas'] = 'Linked to Exchange account #{$a}.';
$string['localizationchecklist'] = 'Before you use this with students, check:';
$string['nocatalogresources'] = 'No resources match your search.';
$string['oerclient:import'] = 'Import a resource from the OER Exchange';
$string['oerclient:share'] = 'Share a course or activity to the OER Exchange';
$string['plugininstalled'] = 'installed here';
$string['pluginmissing'] = 'not installed — will be skipped';
$string['pluginname'] = 'OER Client';
$string['privacy:metadata:local_oerclient_imports'] = 'A record of a resource you imported from the Exchange.';
$string['privacy:metadata:local_oerclient_imports:timecreated'] = 'The time of the import.';
$string['privacy:metadata:local_oerclient_imports:userid'] = 'The user who imported it.';
$string['privacy:metadata:local_oerclient_link'] = 'Your personal link to an OER Exchange account.';
$string['privacy:metadata:local_oerclient_link:exchangeuserid'] = 'Your userid on the Exchange.';
$string['privacy:metadata:local_oerclient_link:timecreated'] = 'The time the link was created.';
$string['privacy:metadata:local_oerclient_link:token'] = 'The web service token used to act as you on the Exchange.';
$string['privacy:metadata:local_oerclient_shares'] = 'A record of a course/activity you shared to the Exchange.';
$string['privacy:metadata:local_oerclient_shares:timecreated'] = 'The time the share was queued.';
$string['privacy:metadata:local_oerclient_shares:title'] = 'The title given to the share.';
$string['privacy:metadata:local_oerclient_shares:userid'] = 'The user who shared it.';
$string['privacy:metadata:oerexchange'] = 'To link your account, share resources, and browse/import the catalogue, data is exchanged with the OER Exchange site configured in this plugin\'s settings.';
$string['privacy:metadata:oerexchange:sharedcontent'] = 'The sanitized (no user data) course/activity backup you chose to share.';
$string['privacy:metadata:oerexchange:token'] = 'Your personal web service token, so the Exchange can attribute shares/reviews to your account.';

$string['registeractive'] = 'Registered and active (site id {$a}).';
$string['registerbutton'] = 'Register this site';
$string['registerintro'] = 'Register this site with the configured Exchange. An Exchange admin must approve the request before you receive a site token.';
$string['registerpending'] = 'Registration pending approval (site id {$a}). Paste the site token into settings once you receive it by email.';
$string['registersuccess'] = 'Registration request sent. Watch your email for the site token once approved.';
$string['registertitle'] = 'Register with the Exchange';
$string['requiredplugins'] = 'Required plugins';
$string['searchbutton'] = 'Search';
$string['sectionnumber'] = 'Section {$a}';
$string['settings_exchangeurl'] = 'Exchange URL';
$string['settings_exchangeurl_desc'] = 'Base URL of the OER Exchange site, e.g. https://vagrant.wisecat.net';
$string['settings_siteid'] = 'Site id';
$string['settings_siteid_desc'] = 'Assigned by the Exchange on registration. Set automatically by the Register page.';
$string['settings_sitetoken'] = 'Site token';
$string['settings_sitetoken_desc'] = 'The web service token emailed to you after the Exchange admin approves this site.';
$string['settingsheading'] = 'OER Exchange connection';
$string['settingsheading_desc'] = 'Configure which OER Exchange this site talks to. Register first, then paste the site key you receive by email into "Site token" below.';
$string['sharelanguagelabel'] = 'Language';
$string['sharelicenselabel'] = 'License';
$string['sharequeued'] = 'Queued — this may take a minute. We\'ll show you the status here.';
$string['sharestatus_backingup'] = 'building a sanitized backup';
$string['sharestatus_failed'] = 'failed';
$string['sharestatus_pending'] = 'queued';
$string['sharestatus_published'] = 'published';
$string['sharestatus_uploading'] = 'uploading to the Exchange';
$string['sharestatuslabel'] = 'Status: {$a}';
$string['sharestatustitle'] = 'Share status';
$string['sharesubmit'] = 'Share it';
$string['sharesummarylabel'] = 'Summary';
$string['sharetagslabel'] = 'Tags (comma-separated)';
$string['sharetitlelabel'] = 'Title';
$string['sharetoexchange'] = 'Share to OER Exchange';
$string['sitecontact'] = 'Contact email';
$string['structurepreview'] = 'Structure preview';
$string['typeactivity'] = 'Activity';
$string['typecourse'] = 'Course';
$string['viewonexchange'] = 'View on the Exchange';
