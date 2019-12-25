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
 * No authentication plugin upgrade code
 *
 * @package    auth_email
 * @copyright  2017 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade auth_email.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_email_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    // Totara 11 branching line.

    // Totara 12 branching line.

    if ($oldversion < 2017020700) {
        // Convert info in config plugins from auth/email to auth_email.
        upgrade_fix_config_auth_plugin_names('email');

        // Totara: add default settings to make the upgrade settings page shorter.
        if (!is_enabled_auth('email')) {
            $defaults = array(
                'recaptcha' => '0',
                'field_lock_firstname' => 'unlocked',
                'field_lock_lastname' => 'unlocked',
                'field_lock_email' => 'unlocked',
                'field_lock_city' => 'unlocked',
                'field_lock_country' => 'unlocked',
                'field_lock_lang' => 'unlocked',
                'field_lock_description' => 'unlocked',
                'field_lock_url' => 'unlocked',
                'field_lock_idnumber' => 'unlocked',
                'field_lock_institution' => 'unlocked',
                'field_lock_department' => 'unlocked',
                'field_lock_phone1' => 'unlocked',
                'field_lock_phone2' => 'unlocked',
                'field_lock_address' => 'unlocked',
                'field_lock_firstnamephonetic' => 'unlocked',
                'field_lock_lastnamephonetic' => 'unlocked',
                'field_lock_middlename' => 'unlocked',
                'field_lock_alternatename' => 'unlocked',
            );
            foreach ($defaults as $name => $value) {
                if (get_config('auth_email', $name) === false) {
                    set_config($name, $value, 'auth_email');
                }
            }
        }

        upgrade_plugin_savepoint(true, 2017020700, 'auth', 'email');
    }

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
