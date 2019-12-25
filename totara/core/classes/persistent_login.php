<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistent login support code.
 */
class persistent_login {
    /**
     * Returns persistent login permanent cookie name.
     *
     * @return string
     */
    public static function get_cookie_name() {
        global $CFG;
        if (!empty($CFG->sessioncookie)) {
            return 'TOTARAPL_' . $CFG->sessioncookie;
        } else {
            return 'TOTARAPL';
        }
    }

    /**
     * Returns persistent login permanent cookie lifetime.
     *
     * @return int
     */
    public static function get_cookie_lifetime() {
        return 60 * 60 * 24 * 21;
    }

    /**
     * Is the persistent cookie marked as https only?
     *
     * @return bool
     */
    public static function is_cookie_secure() {
        return is_moodle_cookie_secure();
    }

    /**
     * Add new persistent login for current user.
     *
     * NOTE: this must be called only from the login page.
     */
    public static function start() {
        global $USER, $CFG, $DB;

        if (empty($CFG->persistentloginenable)) {
            return;
        }

        if (!isloggedin() or isguestuser()) {
            // This should not happen on login page!
            return;
        }

        if (CLI_SCRIPT or WS_SERVER or NO_MOODLE_COOKIES or headers_sent()) {
            // This should not happen on login page!
            return;
        }

        if (!session_id()) {
            // This should not happen on login page!
            return;
        }

        $userauth = get_auth_plugin($USER->auth);
        if (!$userauth or !$userauth->allow_persistent_login($USER)) {
            return;
        }

        $record = new \stdClass();
        $record->userid = $USER->id;
        $record->cookie = random_string(96);
        $record->timecreated = time();
        $record->timeautologin = null;
        $record->useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $record->sid = session_id();
        $record->lastip = getremoteaddr();

        try {
            $DB->insert_record('persistent_login', $record);
        } catch (\Exception $e) {
            // This should not happen on login page after upgrade.
            error_log('Error inserting persistent login record: ' . $e->getMessage());
            return;
        }

        // Send cookie to the browser.
        setcookie(self::get_cookie_name(), $record->cookie, time() + self::get_cookie_lifetime(), $CFG->sessioncookiepath, $CFG->sessioncookiedomain, self::is_cookie_secure(), true);
    }

    /**
     * Attempt automatic login via persistent login cookie.
     */
    public static function attempt_auto_login() {
        global $CFG, $DB, $USER, $SCRIPT, $SESSION;

        if (empty($CFG->persistentloginenable)) {
            return;
        }

        if (isloggedin()) {
            // No overriding of current user!
            return;
        }

        if (CLI_SCRIPT or WS_SERVER or NO_MOODLE_COOKIES or headers_sent()) {
            // We cannot send cookies to auto-login user, bad luck!
            return;
        }

        if (!session_id()) {
            // We need a valid session.
            return;
        }

        $cookiename = \totara_core\persistent_login::get_cookie_name();

        if (empty($_COOKIE[$cookiename])) {
            // No cookie means nothing to do.
            return;
        }

        try {
            $persistentlogin = $DB->get_record('persistent_login', array('cookie' => $_COOKIE[$cookiename]));
            if (!$persistentlogin) {
                self::delete_cookie();
                return;
            }
        } catch (\Exception $e) {
            // Ignore problems, tehre is not much we can do this fails.
            self::delete_cookie();
            return;
        }

        if (CLI_MAINTENANCE or !empty($CFG->maintenance_enabled)) {
            error_log('persistent login error: auto login is forbiddent in maintenance mode ' . $persistentlogin->userid);
            $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
            self::delete_cookie();
            return;
        }

        if (!empty($CFG->tracksessionip)) {
            $remoteaddr = getremoteaddr();
            if ($persistentlogin->lastip != $remoteaddr) {
                // Users need to login again after IP address change.
                $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
                self::delete_cookie();
                return;
            }
        }

        if ($persistentlogin->timecreated < time() - self::get_cookie_lifetime()) {
            // Users need to login again after some time for security reasons.
            $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
            self::delete_cookie();
            return;
        }

        $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ($persistentlogin->useragent !== $useragent) {
            error_log('persistent login error: invalid user agent ' . $persistentlogin->userid);
            $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
            self::delete_cookie();
            return;
        }

        if ($persistentlogin->sid === session_id()) {
            error_log('persistent login error: session id did not change after timeout ' . $persistentlogin->userid);
            $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
            self::delete_cookie();
            return;
        }

        $user = $DB->get_record('user', array('id' => $persistentlogin->userid, 'deleted' => 0, 'suspended' => 0));
        if (!$user) {
            $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
            self::delete_cookie();
            return;
        }

        if (!is_enabled_auth($user->auth)) {
            $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
            self::delete_cookie();
            return;
        }

        $userauth = get_auth_plugin($user->auth);
        if (!$userauth or !$userauth->allow_persistent_login($user)) {
            $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));
            self::delete_cookie();
            return;
        }

        // Kill previous session in case user closed browser without log out.
        \core\session\manager::kill_session($persistentlogin->sid, true);

        $prevignoreabort = ignore_user_abort(true);

        // NOTE: using complete_user_login() is not optional, but it should work with some ugly hacks here and at the end of this method.
        unset($SESSION->lang);
        unset($SESSION->has_timed_out);

        $olduser = clone($user);
        complete_user_login($user);
        moodle_setlocale();

        // Update info.
        $record = new \stdClass();
        $record->id = $persistentlogin->id;
        $record->sid = session_id();
        $record->timeautologin = time();
        $DB->update_record('persistent_login', $record);

        // Hack alert: undo some complete_user_login() tricks.
        unset($SESSION->justloggedin); // No failed login notifications here.
        $record = new \stdClass();
        $record->id = $olduser->id;
        $record->lastlogin = $USER->lastlogin = $olduser->lastlogin;
        $record->currentlogin = $USER->currentlogin = $olduser->currentlogin;
        $DB->update_record('user', $record); // This is not a real login - undo the changed timestamps.

        if (!empty($CFG->preventmultiplelogins)) {
            // If multiple logins not permitted, clear out any other existing sessions and persistent logins for this user,
            // do not rely on cleanup via session records, make sure there are no other persistent logins left.
            $DB->delete_records_select('persistent_login', "userid = :userid AND id <> :id", array('userid' => $USER->id, 'id' => $persistentlogin->id));
            \core\session\manager::kill_user_sessions($USER->id, session_id());
        }

        ignore_user_abort($prevignoreabort);
        if ($prevignoreabort) {
            if (connection_aborted()) {
                die;
            }
        }
    }

    /**
     * Called right before deleting of timed out user session.
     *
     * @param \stdClass $usersession
     */
    public static function session_timeout(\stdClass $usersession) {
        global $DB, $CFG;

        if (empty($CFG->persistentloginenable)) {
            return;
        }

        try {
            $persistentlogin = $DB->get_record('persistent_login', array('sid' => $usersession->sid));
            if (!$persistentlogin) {
                return;
            }
        } catch (\Exception $e) {
            // Ignore problems before upgrade.
            return;
        }

        // Copy last access info for the sessiosn report.
        $record = new \stdClass();
        $record->id = $persistentlogin->id;
        $record->lastip = $usersession->s_lastip;
        $record->lastaccess = $usersession->s_timemodified;
        $DB->update_record('persistent_login', $record);
    }

    /**
     * Delete persistent login cookie.
     */
    public static function delete_cookie() {
        global $CFG;

        if (CLI_SCRIPT) {
            return;
        }

        if (headers_sent()) {
            return;
        }

        // Delete cookie from browser.
        setcookie(self::get_cookie_name(), '', time() - HOURSECS, $CFG->sessioncookiepath, $CFG->sessioncookiedomain, self::is_cookie_secure(), true);
    }

    /**
     * Kill one persistent login.
     *
     * @param string $sid
     */
    public static function kill(string $sid) {
        global $DB, $CFG;

        if (empty($CFG->persistentloginenable)) {
            return;
        }

        try {
            $persistentlogin = $DB->get_record('persistent_login', array('sid' => $sid));
            if (!$persistentlogin) {
                return;
            }
        } catch (\Exception $ignored) {
            // Probably install/upgrade - ignore any problems.
            return;
        }

        $DB->delete_records('persistent_login', array('id' => $persistentlogin->id));

        if (PHPUNIT_TEST) {
            return;
        }

        if (session_id() !== $sid) {
            // Not a session of current user.
            return;
        }

        self::delete_cookie();
    }

    /**
     * Kill all persistent logins of one user.
     *
     * @param int $userid
     */
    public static function kill_user($userid) {
        global $DB, $CFG, $USER;

        if (empty($CFG->persistentloginenable)) {
            return;
        }

        if (!PHPUNIT_TEST and $userid == $USER->id) {
            $sid = session_id();
            if ($sid) {
                self::kill($sid);
            }
        }

        try {
            $DB->delete_records('persistent_login', array('userid' => $userid));
        } catch (\Exception $ignored) {
            // Probably install/upgrade - ignore any problems.
        }
    }

    /**
     * Kill al persistent logins.
     */
    public static function kill_all() {
        global $DB;

        try {
            // NOTE: there is no need to deal with cookies here, they are useless without these records.
            $DB->delete_records('persistent_login', array());
        } catch (\Exception $ignored) {
            // Probably install/upgrade - ignore this problem.
        }
    }

    /**
     * Admin setting callback.
     *
     * @param string $fullsettingname
     */
    public static function settings_updated(string $fullsettingname = null) {
        global $CFG;
        if (empty($CFG->persistentloginenable)) {
            self::kill_all();
        }
    }

    /**
     * Deletes expired persistent logins.
     */
    public static function gc() {
        global $DB;

        $DB->delete_records_select('persistent_login', "timecreated < ?", array(time() - self::get_cookie_lifetime()));
    }
}