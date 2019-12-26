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
 * Admin settings and defaults.
 *
 * @package auth_email
 * @copyright  2017 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_email/pluginname', '',
        new lang_string('auth_emaildescription', 'auth_email')));

    $options = array(
        0 => new lang_string('no'),
        1 => new lang_string('yes'),
    );

    $settings->add(new admin_setting_configselect('auth_email/recaptcha',
        new lang_string('auth_emailrecaptcha_key', 'auth_email'),
        new lang_string('auth_emailrecaptcha', 'auth_email'), 0, $options));

    // NOTE: the whole email signup and related stuff will be removed in T12,
    //       we will just keep the email auth for existing users.
    $settings->add(new admin_setting_configselect('totara_job/allowsignupposition',
        new lang_string('allowsignupposition', 'totara_job'),
        new lang_string('allowsignupposition_help', 'totara_job'), 0, $options));
    $settings->add(new admin_setting_configselect('totara_job/allowsignuporganisation',
        new lang_string('allowsignuporganisation', 'totara_job'),
        new lang_string('allowsignuporganisation_help', 'totara_job'), 0, $options));
    $settings->add(new admin_setting_configselect('totara_job/allowsignupmanager',
        new lang_string('allowsignupmanager', 'totara_job'),
        new lang_string('allowsignupmanager_help', 'totara_job'), 0, $options));

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('email');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
            get_string('auth_fieldlocks_help', 'auth'), false, false);
}
