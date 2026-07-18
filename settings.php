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
 * Settings for local_oerclient.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Own category, nested under Plugins, instead of sharing the generic
    // "Local plugins" list indistinguishable from every other local plugin.
    $ADMIN->add('localplugins', new admin_category(
        'local_oerclient_category',
        get_string('pluginname', 'local_oerclient')
    ));

    $settings = new admin_settingpage('local_oerclient', get_string('generalsettings', 'local_oerclient'));
    $ADMIN->add('local_oerclient_category', $settings);

    $settings->add(new admin_setting_heading(
        'local_oerclient/heading',
        get_string('settingsheading', 'local_oerclient'),
        get_string('settingsheading_desc', 'local_oerclient')
    ));

    $settings->add(new admin_setting_configtext(
        'local_oerclient/exchangeurl',
        get_string('settings_exchangeurl', 'local_oerclient'),
        get_string('settings_exchangeurl_desc', 'local_oerclient'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_oerclient/siteid',
        get_string('settings_siteid', 'local_oerclient'),
        get_string('settings_siteid_desc', 'local_oerclient'),
        '',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_oerclient/sitetoken',
        get_string('settings_sitetoken', 'local_oerclient'),
        get_string('settings_sitetoken_desc', 'local_oerclient'),
        ''
    ));

    $ADMIN->add('local_oerclient_category', new admin_externalpage(
        'local_oerclient_register',
        get_string('registertitle', 'local_oerclient'),
        new moodle_url('/local/oerclient/register.php'),
        'moodle/site:config'
    ));
}
