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
$string['downloadcountlabel'] = 'Downloads';
$string['error_downloadorigin'] = 'The download URL offered by the Exchange is not on the configured Exchange host, so it was refused.';
$string['error_invalidlicense'] = 'Choose a licence from the list provided.';
$string['error_invalidlinkstate'] = 'This account-linking request could not be verified. Please try linking your account again.';
$string['error_licensesunavailable'] = 'Cannot reach the OER Exchange, so the licences it accepts are unknown. Please try again shortly.';
$string['error_noexchangeurl'] = 'Set the Exchange URL in settings first.';
$string['error_nolicensesaccepted'] = 'The OER Exchange is not currently accepting any licence, so nothing can be shared to it. Ask its administrator to allow at least one licence.';
$string['error_notargetcourses'] = 'You do not have permission to import into any course.';
$string['error_notlinked'] = 'Link your personal Exchange account first.';
$string['error_notregistered'] = 'This site is not yet registered (or not yet approved) with an OER Exchange. See Site administration > Plugins > Local plugins > OER Client > General settings.';
$string['error_requestfailed'] = 'The request to the Exchange failed.';
$string['error_resourcegone'] = 'This resource is no longer available on the Exchange. It may have been hidden or deleted by its author.';
$string['error_restoreprecheckfailed'] = 'The restore precheck failed for this backup.';
$string['error_sharecapabilitylost'] = 'You no longer have permission to share this course or activity.';
$string['error_sourcegone'] = 'The course this was shared from no longer exists on this site, so the shared copy can\'t be updated from here. The published resource on the Exchange is unaffected.';
$string['error_sourcegoneactivity'] = 'The activity this was shared from no longer exists on this site, so the shared copy can\'t be updated from here. The published resource on the Exchange is unaffected.';
$string['error_statusunavailable'] = 'Could not read the current status from the Exchange: {$a}';
$string['error_targetcourserequired'] = 'A target course is required to import a single activity.';
$string['error_unknown'] = 'Unknown error.';
$string['error_uploadfailed'] = 'The upload to the Exchange failed.';
$string['error_userdatalockedon'] = 'This site\'s backup defaults force user data to be included in every backup, so nothing can be shared safely. Ask an administrator to unlock "Include enrolled users" under Site administration > Courses > Backups > General backup defaults.';
$string['exchangeerror'] = 'Exchange error: {$a}';
$string['exchangehidden'] = 'Hidden';
$string['exchangehiddenhint'] = 'You hid this resource on the Exchange, so it is not in the catalogue. You can show it again from its page there.';
$string['exchangerejected'] = 'The Exchange rejected your most recent upload, so it was not published: {$a}';
$string['exchangevisibility'] = 'On the Exchange';
$string['exchangevisible'] = 'Visible in the catalogue';
$string['filterbytype'] = 'Type';
$string['firstpublished'] = 'First published';
$string['generalsettings'] = 'General settings';
$string['gotocourse'] = 'Go to course';
$string['importbutton'] = 'Import';
$string['importcountlabel'] = 'Imports';
$string['importheading'] = 'Import this resource';
$string['importresulttitle'] = 'Import complete';
$string['importsuccess'] = 'The resource was imported successfully.';
$string['importtargetcourse'] = 'Import into course';
$string['lastupdated'] = 'Last updated';
$string['licenselabel'] = 'Licence: {$a}';
$string['licenseliststale'] = 'Could not reach the OER Exchange just now, so this list was last confirmed on {$a}. Your share will still be queued.';
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
$string['privacy:metadata:local_oerclient_imports:courseid'] = 'The local course the resource was imported into.';
$string['privacy:metadata:local_oerclient_imports:exchangeresourceid'] = 'The imported resource\'s id on the Exchange.';
$string['privacy:metadata:local_oerclient_imports:exchangeversionid'] = 'The imported version\'s id on the Exchange.';
$string['privacy:metadata:local_oerclient_imports:timecreated'] = 'The time of the import.';
$string['privacy:metadata:local_oerclient_imports:userid'] = 'The user who imported it.';
$string['privacy:metadata:local_oerclient_link'] = 'Your personal link to an OER Exchange account.';
$string['privacy:metadata:local_oerclient_link:exchangeuserid'] = 'Your userid on the Exchange.';
$string['privacy:metadata:local_oerclient_link:timecreated'] = 'The time the link was created.';
$string['privacy:metadata:local_oerclient_link:token'] = 'The web service token used to act as you on the Exchange.';
$string['privacy:metadata:local_oerclient_link:userid'] = 'The local user the Exchange link belongs to.';
$string['privacy:metadata:local_oerclient_shares'] = 'A record of a course/activity you shared to the Exchange.';
$string['privacy:metadata:local_oerclient_shares:activitytype'] = 'The module type, when a single activity was shared.';
$string['privacy:metadata:local_oerclient_shares:cmid'] = 'The course module shared, when a single activity was shared.';
$string['privacy:metadata:local_oerclient_shares:courseid'] = 'The local course the share came from.';
$string['privacy:metadata:local_oerclient_shares:errormessage'] = 'The error recorded if the share failed.';
$string['privacy:metadata:local_oerclient_shares:exchangeresourceid'] = 'The published resource\'s id on the Exchange.';
$string['privacy:metadata:local_oerclient_shares:language'] = 'The language you declared for the share.';
$string['privacy:metadata:local_oerclient_shares:licenseshortname'] = 'The licence you chose for the share.';
$string['privacy:metadata:local_oerclient_shares:status'] = 'The share\'s processing status.';
$string['privacy:metadata:local_oerclient_shares:summary'] = 'The summary you wrote for the share.';
$string['privacy:metadata:local_oerclient_shares:tags'] = 'The tags you gave the share.';
$string['privacy:metadata:local_oerclient_shares:timecreated'] = 'The time the share was queued.';
$string['privacy:metadata:local_oerclient_shares:timemodified'] = 'The time the share last changed.';
$string['privacy:metadata:local_oerclient_shares:title'] = 'The title given to the share.';
$string['privacy:metadata:local_oerclient_shares:type'] = 'Whether a whole course or a single activity was shared.';
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
$string['settings_acceptinvalidcerts'] = 'Accept invalid TLS certificates (development only)';
$string['settings_acceptinvalidcerts_desc'] = 'DEVELOPMENT ONLY. When enabled, connections to the Exchange skip TLS certificate verification and Moodle\'s outbound-request security checks (blocked hosts/ports). Never enable this on a production site: it lets an attacker on the network read every token this plugin sends.';
$string['settings_exchangeurl'] = 'Exchange URL';
$string['settings_exchangeurl_desc'] = 'Base URL of the OER Exchange site, e.g. https://vagrant.wisecat.net';
$string['settings_siteid'] = 'Site id';
$string['settings_siteid_desc'] = 'Assigned by the Exchange on registration. Set automatically by the Register page.';
$string['settings_sitetoken'] = 'Site token';
$string['settings_sitetoken_desc'] = 'The web service token emailed to you after the Exchange admin approves this site.';
$string['settings_uppercaselicencenames'] = 'Show licence codes in capitals';
$string['settings_uppercaselicencenames_desc'] = 'Display a resource\'s licence code as CC-SA-4.0 rather than cc-sa-4.0 when browsing and previewing the catalogue. This changes the appearance only: the licence is stored, sent to the Exchange and compared exactly as it was received, and text copied from the page still matches it.';
$string['settingsheading'] = 'OER Exchange connection';
$string['settingsheading_desc'] = 'Configure which OER Exchange this site talks to. Register first, then paste the site key you receive by email into "Site token" below.';
$string['sharelanguagelabel'] = 'Language';
$string['sharelicenselabel'] = 'Licence';
$string['sharequeued'] = 'Queued — this may take a minute. We\'ll show you the status here.';
$string['sharestatus_backingup'] = 'building a sanitized backup';
$string['sharestatus_failed'] = 'failed';
$string['sharestatus_pending'] = 'queued';
$string['sharestatus_published'] = 'published';
$string['sharestatus_uploading'] = 'uploading to the Exchange';
$string['sharestatuslabel'] = 'Status: {$a}';
$string['sharestatustitle'] = 'Share status';
$string['sharestillrunning'] = 'This share is taking longer than expected. Your course is unaffected; reload this page to check again, or ask an administrator whether scheduled tasks are running.';
$string['sharesubmit'] = 'Share it';
$string['sharesummarylabel'] = 'Summary';
$string['sharetagslabel'] = 'Tags (comma-separated)';
$string['sharetitlelabel'] = 'Title';
$string['sharetoexchange'] = 'Share to OER Exchange';
$string['sitecontact'] = 'Contact email';
$string['structurepreview'] = 'Structure preview';
$string['thumbnailalt'] = 'Thumbnail for {$a}';
$string['typeactivity'] = 'Activity';
$string['typecourse'] = 'Course';
$string['typedata'] = 'Data resource';
$string['updateexchangecopy'] = 'Update the shared copy';
$string['updateexchangecopyhint'] = 'Re-uploads this course as it stands now, replacing the copy on the Exchange. The catalogue entry, its link, and its reviews stay as they are.';
$string['updatequeued'] = 'Queued. The shared copy will be updated shortly.';
$string['viewonexchange'] = 'View on the Exchange';
