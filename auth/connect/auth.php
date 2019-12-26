<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for Totara Connect
 */
class auth_plugin_connect extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'connect';
        $this->config = get_config('auth_connect');
    }

    /**
     * Tweak the advanced editing form.
     *
     * @param user_editadvanced_form $form
     * @param stdClass $user
     */
    public function editadvanced_form_after_data(user_editadvanced_form $form, stdClass $user) {
        $mform = $form->_form;
        $mform->hardFreeze('auth');
        if ($mform->elementExists('passwordpolicyinfo')) {
            $mform->removeElement('passwordpolicyinfo');
        }
        if ($mform->elementExists('newpassword')) {
            $mform->removeElement('newpassword');
        }
        if ($mform->elementExists('preference_auth_forcepasswordchange')) {
            $mform->removeElement('preference_auth_forcepasswordchange');
        }
    }

    /**
     * Login page Identity Provider list.
     *
     * @param string $wantsurl
     * @return array
     */
    public function loginpage_idp_list($wantsurl) {
        global $DB;

        $result = array();

        if (!is_enabled_auth('connect')) {
            return $result;
        }

        $servers = $DB->get_records('auth_connect_servers', array('status' => \auth_connect\util::SERVER_STATUS_OK));
        if (!$servers) {
            return $result;
        }

        foreach ($servers as $server) {
            $idp = array(
                'url' => new moodle_url('/auth/connect/sso_start.php', array('serverid' => $server->id)),
                'name' => format_string($server->servername),
                'icon' => new pix_icon('icon', format_string($server->servername), 'auth_connect'),
            );
            $result[] = $idp;
        }

        return $result;
    }

    /**
     * Executed immediately before logout is actioned.
     */
    public function prelogout_hook() {
        global $DB, $SESSION;

        unset($SESSION->loginerrormsg);

        $ssosession = $DB->get_record('auth_connect_sso_sessions', array('sid' => session_id()));
        if (!$ssosession) {
            return;
        }

        \auth_connect\util::force_sso_logout($ssosession);
    }

    /**
     * Hook for overriding behaviour of login page.
     * This method is called from login/index.php page for all enabled auth plugins.
     */
    public function loginpage_hook() {
        global $DB;

        if (isloggedin() and !isguestuser()) {
            // Nothing to do.
            return;
        }

        // Add ?nosso=1 to the login page URL if you needs to log in without SSO to local site directly.
        $nosso = optional_param('nosso', 0, PARAM_BOOL);
        if ($nosso) {
            return;
        }

        if (data_submitted()) {
            // Let them post username and password directly.
            return;
        }

        $testsession = optional_param('testsession', 0, PARAM_INT);  // Tests session works properly/
        if ($testsession) {
            return;
        }

        $autossoserver = get_config('auth_connect', 'autossoserver');
        if (!$autossoserver) {
            // No auto SSO, let them click the link.
            return;
        }

        $server = $DB->get_record('auth_connect_servers', array('id' => $autossoserver, 'status' => \auth_connect\util::SERVER_STATUS_OK));
        if (!$server) {
            return;
        }

        redirect(new moodle_url('/auth/connect/sso_start.php', array('serverid' => $server->id)));
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        return false;
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object  $user        User table object
     * @param  string  $newpassword Plaintext password
     * @return boolean result
     *
     */
    public function user_update_password($user, $newpassword) {
        return false;
    }

    /**
     * Indicates if password hashes should be stored in local moodle database.
     *
     * @return bool
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Returns true if this plugin allows the user to edit thier profile.
     * @return bool
     */
    public function can_edit_profile() {
        return true;
    }

    /**
     * Returns the edit profile link.
     *
     * @param int $userid local user id, null means current user
     * @return moodle_url url of the profile editing page or null if standard used
     */
    public function edit_profile_url($userid = null) {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        // No need to check validity here, the target page does it.
        return new \moodle_url('/auth/connect/user_edit.php', array('userid' => $userid));
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        // Absolutely not, because each user record needs server link.
        return false;
    }

    /**
     * Returns true if user information should be synchronised from the external source.
     * @return bool
     */
    public function is_synchronised_with_external() {
        // Changes are pushed from external server automatically,
        // we cannot rely on the username magic.
        return false;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     */
    public function config_form($config, $err, $user_fields) {
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    public function process_config($config) {
        return false;
    }

    /**
     * SSO plugins are not compatible with persistent logins.
     *
     * @param stdClass $user
     * @return bool
     */
    public function allow_persistent_login(stdClass $user) {
        return false;
    }
}


