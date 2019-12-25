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

namespace auth_connect;

use \totara_core\jsend;

/**
 * Class util containing internal implementation,
 * do NOT use outside of this plugin.
 *
 * @package auth_connect
 */
class util {
    /** Client is connected to the server */
    const SERVER_STATUS_OK = 0;

    /** Client delete was requested, but server was not told yet. Record will be deleted later. */
    const SERVER_STATUS_DELETING = 1;

    /** How much time do we give user to login on SSO server in seconds? */
    const REQUEST_LOGIN_TIMEOUT = 600;

    const MIN_API_VERSION = 1;
    const MAX_API_VERSION = 2;

    /**
     * Names of user columns that we don't want to sync for existing users
     * These include fields that are controlled by admin or user sync as well as
     * user preferences stored in the user table
     * @var string[]
     */
    protected static $user_nosync_columns = array(
        'username',
        'suspended',
        'deleted',
        'emailstop',
    );

    /**
     * Log failed SSO attempt.
     *
     * @param string $message
     */
    public static function log_sso_attempt_error($message) {
        if (PHPUNIT_TEST) {
            // No logging in unit tests, this silences errors sid tests in PHP 7.1.
            return;
        }
        error_log('TC SSO ERROR: ' . $message);
    }

    /**
     * Show SSO error page and give them some instructions to retry or restart browser.
     *
     * NOTE: this method terminates script execution.
     *
     * @param string $error lang string name from auth_connect
     * @param string|\moodle_url $retryurl
     */
    public static function sso_error_page($error, $retryurl) {
        global $OUTPUT, $SESSION, $CFG;

        // Prevent redirection back to SSO scripts!
        if (empty($SESSION->wantsurl)) {
            $SESSION->wantsurl = $CFG->wwwroot . '/';
        }

        if (PHPUNIT_TEST) {
            // Needed for testing of finish_sso().
            redirect($retryurl);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('ssologinfailed', 'auth_connect'));
        echo $OUTPUT->notification(get_string($error, 'auth_connect'), 'notifyproblem');
        echo $OUTPUT->single_button($retryurl, get_string('retrylogin', 'auth_connect'), 'get');
        echo $OUTPUT->footer();
        die;
    }

    /**
     * Create unique hash for a field in a db table.
     *
     * @param string $table database table
     * @param string $field table field
     * @return string unique string in the form of a SHA1 hash
     */
    public static function create_unique_hash($table, $field) {
        global $DB;

        do {
            $secret = sha1(microtime(false) . uniqid('', true) . get_site_identifier() . $table . $field);
        } while ($DB->record_exists_select($table, "{$field} = ?", array($secret)));
        // The select allows comparison of text fields, Oracle is not supported!

        return $secret;
    }

    /**
     * Return sep url on server.
     *
     * @param \stdClass $server
     * @return string
     */
    public static function get_sep_url(\stdClass $server) {
        return "{$server->serverurl}/totara/connect/sep.php";
    }

    /**
     * Return URL of SSO request on server.
     * @param \stdClass $server
     * @return string
     */
    public static function get_sso_request_url(\stdClass $server) {
        return "{$server->serverurl}/totara/connect/sso_request.php";
    }

    /**
     * Enable new server registration.
     */
    public static function enable_registration() {
        $secret = self::create_unique_hash('config', 'value');
        set_config('setupsecret', $secret, 'auth_connect');
    }

    /**
     * Cancel new server registration.
     */
    public static function cancel_registration() {
        unset_config('setupsecret', 'auth_connect');
    }

    /**
     * Get new server registration info.
     *
     * @return string
     */
    public static function get_setup_secret() {
        return get_config('auth_connect', 'setupsecret');
    }

    /**
     * Is this a valid setup request?
     *
     * @param string $setupsecret
     * @return bool
     */
    public static function verify_setup_secret($setupsecret) {
        if (!is_enabled_auth('connect')) {
            return false;
        }

        if (empty($setupsecret)) {
            return false;
        }

        $secret = self::get_setup_secret();

        return ($secret AND $secret === $setupsecret);
    }

    /**
     * Select Totara Connect API version.
     *
     * @param int $minapiversion
     * @param int $maxapiversion
     * @return int 0 means error, anything else is api version compatible with this auth plugin.
     */
    public static function select_api_version($minapiversion, $maxapiversion) {
        if ($minapiversion > $maxapiversion) {
            return 0;
        }
        if ($maxapiversion < self::MIN_API_VERSION) {
            return 0;
        }
        if ($minapiversion > self::MAX_API_VERSION) {
            return 0;
        }
        if ($maxapiversion >= self::MAX_API_VERSION) {
            return self::MAX_API_VERSION;
        }
        return $maxapiversion;
    }

    /**
     * Edit server.
     *
     * @param \stdClass $data from auth_connect_form_server_edit
     */
    public static function edit_server($data) {
        global $DB;

        $server = new \stdClass();
        $server->id            = $data->id;
        $server->servercomment = $data->servercomment;
        $server->timemodified  = time();

        $DB->update_record('auth_connect_servers', $server);
    }

    /**
     * Delete server.
     *
     * @param \stdClass $data from auth_connect_form_server_delete
     */
    public static function delete_server($data) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        $server = $DB->get_record('auth_connect_servers', array('id' => $data->id), '*', MUST_EXIST);

        // Prevent any new log-ins and requests from server.
        $DB->set_field('auth_connect_servers', 'status', self::SERVER_STATUS_DELETING, array('id' => $server->id));

        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {auth_connect_users} cu ON cu.userid = u.id
                 WHERE cu.serverid = :serverid";
        $rs = $DB->get_recordset_sql($sql, array('serverid' => $server->id));
        foreach ($rs as $user) {
            if ($user->deleted != 0) {
                // Nothing to do, user is already deleted.

            } else if ($data->removeuser === 'delete') {
                user_delete_user($user);

            } else {
                $record = new \stdClass();
                $record->id = $user->id;
                $record->timemodified = time();
                $record->auth = $data->newauth;
                if ($user->suspended == 0 and $data->removeuser === 'suspend') {
                    $record->suspended = '1';
                }
                // Do not use user_update_user() here because it is messing with usernames!
                $DB->update_record('user', $record);
                \core\event\user_updated::create_from_userid($user->id)->trigger();
                if (isset($record->suspended)) {
                    $user = $DB->get_record('user', array('id' => $user->id));
                    \totara_core\event\user_suspended::create_from_user($user)->trigger();
                }
                unset($record);
                \core\session\manager::kill_user_sessions($user->id);
            }
            $DB->delete_records('auth_connect_users', array('userid' => $user->id));
        }
        $rs->close();

        // Unprotect the cohorts, but keep them.
        $ccs = $DB->get_records('auth_connect_user_collections', array('serverid' => $server->id));
        foreach ($ccs as $cc) {
            $DB->set_field('cohort', 'component', '', array('id' => $cc->cohortid));
        }

        $DB->delete_records('auth_connect_user_collections', array('serverid' => $server->id));
        $DB->delete_records('auth_connect_users', array('serverid' => $server->id));
        $DB->delete_records('auth_connect_sso_requests', array('serverid' => $server->id));
        $DB->delete_records('auth_connect_sso_sessions', array('serverid' => $server->id));
        $DB->delete_records('auth_connect_ids', array('serverid' => $server->id));

        $DB->set_field('auth_connect_servers', 'timemodified', time(), array('id' => $server->id));

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'delete_client',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        // Keep the record until it is deleted properly on the server,
        // this prevents repeated registration problems.
        if ($result['status'] === 'success') {
            $DB->delete_records('auth_connect_servers', array('id' => $server->id));
        }
    }

    /**
     * Logout from the SSO session on master and all clients.
     *
     * @param \stdClass $ssosession
     */
    public static function force_sso_logout(\stdClass $ssosession) {
        global $DB;

        $server = $DB->get_record('auth_connect_servers', array('id' => $ssosession->serverid));
        if ($server) {
            $data = array(
                'serveridnumber' => $server->serveridnumber,
                'serversecret' => $server->serversecret,
                'service' => 'force_sso_logout',
                'ssotoken' => $ssosession->ssotoken,
            );

            // Ignore result, this function cannot fail.
            jsend::request(self::get_sep_url($server), $data);
        }

        // Just in case the web service request from master did not get back to this client.
        $DB->delete_records('auth_connect_sso_sessions', array('id' => $ssosession->id));
    }

    /**
     * Update versions of all servers.
     *
     * @return void
     */
    public static function update_api_versions() {
        global $DB;

        $servers = $DB->get_records('auth_connect_servers', array('status' => util::SERVER_STATUS_OK), 'id ASC');
        foreach ($servers as $server) {
            util::update_api_version($server);
        }
    }

    /**
     * Make sure we are using the latest API version available.
     *
     * @param \stdClass $server
     * @return bool success
     */
    public static function update_api_version(\stdClass $server) {
        global $DB;

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_api_version',
            'clienttype' => 'totaralms',
        );
        $result = jsend::request(self::get_sep_url($server), $data);
        if ($result['status'] !== 'success') {
            return false;
        }
        $apiversion = self::select_api_version($result['data']['minapiversion'], $result['data']['maxapiversion']);
        if ($apiversion < 1) {
            return false;
        }
        if ($apiversion == $server->apiversion) {
            // Nothing to do.
            return true;
        }

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'update_api_version',
            'clienttype' => 'totaralms',
            'apiversion' => $apiversion,
        );
        $result = jsend::request(self::get_sep_url($server), $data);
        if ($result['status'] !== 'success') {
            return false;
        }

        $DB->set_field('auth_connect_servers', 'apiversion', $apiversion, array('id' => $server->id));
        return true;
    }

    /**
     * Sync all users with connect server.
     *
     * @param \stdClass $server
     * @return bool success
     */
    public static function sync_users(\stdClass $server) {
        if ($server->status != self::SERVER_STATUS_OK) {
            return false;
        }
        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_users',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        if ($result['status'] !== 'success' or !isset($result['data']['users'])) {
            return false;
        }

        self::update_local_users($server, $result['data']['users']);

        return true;
    }

    /**
     * Sync local user data with with connect server.
     *
     * NOTE: session data is updated for current $USER if necessary.
     *
     * @param int $userid
     * @return bool success
     */
    public static function sync_user($userid) {
        global $DB, $USER;

        if (!is_enabled_auth('connect')) {
            return false;
        }

        $user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0, 'auth' => 'connect'));
        if (!$user) {
            return false;
        }
        $connectuser = $DB->get_record('auth_connect_users', array('userid' => $user->id));
        if (!$connectuser) {
            return false;
        }
        $server = $DB->get_record('auth_connect_servers', array('id' => $connectuser->serverid));
        if (!$server) {
            return false;
        }
        if ($server->status != self::SERVER_STATUS_OK) {
            return false;
        }

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_user',
            'userid' => $connectuser->serveruserid,
        );
        $result = jsend::request(self::get_sep_url($server), $data);

        if ($result['status'] !== 'success' or !isset($result['data']['user'])) {
            return false;
        }

        $localuser = self::update_local_user($server, $result['data']['user'], true);

        if (!$localuser or $localuser->id != $user->id) {
            return false;
        }

        if ($user->id != $USER->id) {
            return true;
        }

        $newuser = $DB->get_record('user', array('id' => $user->id));

        if ($newuser->deleted or $newuser->suspended) {
            // This should not happen, user should have been already logged out.
            return false;
        }

        // Override old $USER session variable if needed.
        foreach ((array)$newuser as $variable => $value) {
            if ($variable === 'description' or $variable === 'password') {
                // These are not set for security and performance reasons.
                continue;
            }
            $USER->$variable = $value;
        }
        // Preload custom fields.
        profile_load_custom_fields($USER);

        // Make sure the all preferences are preloaded too.
        check_user_preferences_loaded($USER, 0);

        return true;
    }

    /**
     * Update local users to match the list of server users.
     *
     * Note: Users fully deleted on Connect server are unconditionally
     *       deleted on clients too. Users may also disappear as a result of
     *       changed cohort membership - this case is controlled via removeuser
     *       setting.
     *
     * @param \stdClass $server
     * @param array $serverusers list of user records on TC server
     * @return void
     */
    public static function update_local_users(\stdClass $server, array $serverusers) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/authlib.php');

        $removeaction = get_config('auth_connect', 'removeuser');

        // Fetch the complete list of current users into memory.
        $sql = "SELECT cu.serveruserid, cu.userid, u.deleted, u.suspended, u.id AS knownuser
                  FROM {auth_connect_users} cu
             LEFT JOIN {user} u ON u.id = cu.userid
                 WHERE cu.serverid = ?";
        $userinfos = $DB->get_records_sql($sql, array($server->id));

        foreach ($serverusers as $k => $serveruser) {
            if (isset($userinfos[$serveruser['id']])) {
                // Updating existing.
                $userinfo = $userinfos[$serveruser['id']];
                unset($userinfos[$serveruser['id']]); // This allows us to find users that disappeared due to cohort restriction.
            } else{
                // Adding or migrating.
                $userinfo = false;
            }

            if ($serveruser['deleted'] != 0) {
                if (!$userinfo) {
                    // User does not exist locally and never did, nothing to delete.
                    continue;
                }
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on this client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
                if ($userinfo->deleted == 0) {
                    // All server user deletes are propagated to clients.
                    $user = $DB->get_record('user', array('id' => $userinfo->userid));
                    delete_user($user);
                    continue;
                }
                // If we got here it means local user account is already deleted,
                // we want to keep the auth_connect_users record just in case they
                // somehow undelete the server account.
                continue;
            }

            // What to do with the suspended flag depends on the auth_connect/removeuser setting
            // Ideally users should not be suspended manually on the client site, but at the
            // moment there is no way of preventing this
            if ($userinfo &&
                !is_null($userinfo->knownuser) &&
                !$userinfo->deleted &&
                $serveruser['suspended'] != $userinfo->suspended) {

                // Not doing anything if $removeaction == AUTH_REMOVEUSER_KEEP as we did nothing when the user was deleted
                // - therefore not doing anything when user re-appears
                // Not doing anything if $removeaction == AUTH_REMOVEUSER_FULLDELETE as the user should have been deleted
                // on the client when it was deleted on the server and we shouldn't get here in this case

                if ($removeaction == AUTH_REMOVEUSER_SUSPEND) {
                    // Sync from server as we assume that the user was suspended when he 'disappeared' from the server
                    // (basically ignore changes made on the client)
                    $DB->set_field('user', 'suspended', $serveruser['suspended'], array('id' => $userinfo->userid));
                    $user = $DB->get_record('user', array('id' => $userinfo->userid));
                    \core\event\user_updated::create_from_userid($user->id)->trigger();

                    if ($serveruser['suspended'] == 1) {
                        \totara_core\event\user_suspended::create_from_user($user)->trigger();
                    }
                }
            }

            // Create, update or undelete local user account.
            self::update_local_user($server, $serveruser);
        }

        // Deal with users that this client is not allowed to see any more,
        // this is the result of removing users from a cohort that restricts a client.
        if ($removeaction == AUTH_REMOVEUSER_SUSPEND) {
            foreach ($userinfos as $userinfo) {
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on TC client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
                if ($userinfo->deleted == 0 and $userinfo->suspended == 0) {
                    $DB->set_field('user', 'suspended', '1', array('id' => $userinfo->userid));
                    $user = $DB->get_record('user', array('id' => $userinfo->userid));
                    \core\event\user_updated::create_from_userid($user->id)->trigger();
                    \totara_core\event\user_suspended::create_from_user($user)->trigger();
                    continue;
                }
            }

        } else if ($removeaction == AUTH_REMOVEUSER_FULLDELETE) {
            foreach ($userinfos as $userinfo) {
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on TC client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
                if ($userinfo->deleted == 0) {
                    $user = $DB->get_record('user', array('id' => $userinfo->userid));
                    delete_user($user);
                    continue;
                }
            }

        } else {
            // This is for $removeaction == AUTH_REMOVEUSER_KEEP, we keep accounts unchanged.
            foreach ($userinfos as $userinfo) {
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on TC client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
            }
        }
    }

    /**
     * Create or update local user record.
     *
     * @param \stdClass $server
     * @param array $serveruser
     * @param bool $sso true during sso login (performance trick)
     * @return \stdClass local user record, null on error
     */
    public static function update_local_user(\stdClass $server, array $serveruser, $sso = false) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/user/lib.php");
        require_once("$CFG->dirroot/user/profile/lib.php");

        if ($serveruser['deleted'] != 0) {
            // Cannot sync deleted users, sorry.
            return null;
        }

        if ($serveruser['username'] === 'guest') {
            // Cannot sync guest accounts, sorry.
            return null;
        }

        $user = (object)$serveruser;
        $serveruser = (object)$serveruser;

        // Set local values.
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->auth       = 'connect';
        $user->confirmed  = '1'; // There is no way to confirm server account, luckily they cannot SSO without it.

        // Unset all site specific fields.
        unset($user->id);
        unset($user->timecreated);
        unset($user->timemodified);
        unset($user->firstaccess);
        unset($user->lastaccess);
        unset($user->lastlogin);
        unset($user->currentlogin);
        unset($user->lastip);
        unset($user->secret);
        unset($user->policyagreed);
        unset($user->totarasync); // Totara sync flag does not get migrated to clients, it is for local sync only!

        // If server does not want to give us the password, keep whatever was there before.
        if (!isset($user->password)) {
            unset($user->password);
        }

        // Did we see this server user before?
        $sql = "SELECT cu.*, u.id AS knownuser
                  FROM {auth_connect_users} cu
             LEFT JOIN {user} u ON u.id = cu.userid
                 WHERE cu.serverid = :serverid AND cu.serveruserid = :serveruserid";
        $userinfo = $DB->get_record_sql($sql, array('serverid' => $server->id, 'serveruserid' => $serveruser->id));
        if ($userinfo) {
            if ($userinfo->knownuser === null) {
                // Weird somebody deleted client user record from DB,
                // let's pretend we did not see the user yet..
                $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                $userinfo = false;
            } else {
                unset($userinfo->knownuser);
            }
        }

        if (!$userinfo and get_config('auth_connect', 'migrateusers')) {
            // Let's try to migrate the server user to existing local account..
            $mapfield = get_config('auth_connect', 'migratemap');
            $candidates = array();
            if ($mapfield === 'uniqueid') {
                $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.username = :username
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                $params = array('username' => self::create_local_username($server, $serveruser), 'mnethostid' => $CFG->mnet_localhost_id);
                $candidates = $DB->get_records_sql($sql, $params);

            } else if ($mapfield === 'email') {
                if (validate_email($user->email)) {
                    $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.email = :email
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                    $params = array('email' => $serveruser->email, 'mnethostid' => $CFG->mnet_localhost_id);
                    $candidates = $DB->get_records_sql($sql, $params);
                }

            } else if ($mapfield === 'idnumber') {
                if (!empty($serveruser->idnumber)) {
                    $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.idnumber = :idnumber
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                    $params = array('idnumber' => $serveruser->idnumber, 'mnethostid' => $CFG->mnet_localhost_id);
                    $candidates = $DB->get_records_sql($sql, $params);
                }

            } else if ($mapfield === 'username') {
                if (!empty($serveruser->username)) {
                    $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.username = :username
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                    $params = array('username' => $serveruser->username, 'mnethostid' => $CFG->mnet_localhost_id);
                    $candidates = $DB->get_records_sql($sql, $params);
                }
            }

            if ($candidates) {
                $candidate = reset($candidates);
                $userinfo = new \stdClass();
                $userinfo->serverid     = $server->id;
                $userinfo->serveruserid = $serveruser->id;
                $userinfo->userid       = $candidate->id;
                $userinfo->timecreated  = time();
                $userinfo->id = $DB->insert_record('auth_connect_users', $userinfo);
            }
            unset($candidates);
        }

        $userupdated = false;
        $usercreated = false;
        $userundeleted = false;
        $usersuspended = false;
        $olduser = false;

        if ($userinfo) {
            // Unset all ancient user preferences that are stored directly in user table
            // because user preferences are not supposed to be synced after initial user creation.
            unset($user->trackforums);
            unset($user->maildigest);
            unset($user->autosubscribe);
            unset($user->lang);

            $olduser = $DB->get_record('user', array('id' => $userinfo->userid), '*', MUST_EXIST);

            if ($olduser->deleted != 0) {
                if (!is_undeletable_user($olduser)) {
                    // Undeleting regularly deleted user, we need to get some valid username and email.
                    $user->username = self::create_local_username($server, $serveruser);
                } else {
                    // Legacy hacky Totara delete by flipping the delete flag only.
                }
                $userundeleted = true;
            } else {
                // Regular user update - do not sync these fields
                foreach (self::$user_nosync_columns as $colname) {
                    if (property_exists($user, $colname)) {
                        unset($user->{$colname});
                    }
                }
            }

            if (!empty($user->idnumber)) {
                if ($olduser->idnumber !== $user->idnumber and totara_idnumber_exists('user', $user->idnumber, $olduser->id)) {
                    // No idnumber duplicates, sorry.
                    unset($user->idnumber);
                }
            }

            // Did the user info change?
            $columns = $DB->get_columns('user', true);
            $record = array();
            foreach ($columns as $k => $ignored) {
                if (!property_exists($user, $k)) {
                    // Missing info from server.
                    continue;
                }
                if ((string)$user->$k === $olduser->$k) {
                    continue;
                }
                $record[$k] = $user->$k;
            }

            $user->id = $olduser->id;
            if ($record) {
                if (isset($serveruser->suspended) and $serveruser->suspended != 0 and $olduser->suspended == 0) {
                    $usersuspended = true;
                }
                // NOTE: do NOT use update_user() because it is messing with usernames and other things!
                $record['id'] = $user->id;
                $record['timemodified'] = time();
                $DB->update_record('user', $record);
                $userupdated = true;
            }
            unset($record);

        } else {
            // Make sure there are no bogus fields, use the guest account as a template.
            $record = array();
            $columns = $DB->get_columns('user', true);
            foreach ($columns as $k => $ignored) {
                if (!property_exists($user, $k)) {
                    // Missing info from server.
                    continue;
                }
                $record[$k] = $user->$k;
            }

            $record['username'] = self::create_local_username($server, $serveruser);
            if (!isset($record['password'])) {
                $record['password'] = AUTH_PASSWORD_NOT_CACHED;
            }
            if (!empty($record['idnumber']) and totara_idnumber_exists('user', $record['idnumber'])) {
                // No idnumber duplicates, sorry.
                $record['idnumber'] = '';
            }

            // Make sure the username is not taken, if yes skip this user completely.
            if ($DB->record_exists('user', array('username' => $record['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
                return null;
            }

            $transaction = $DB->start_delegated_transaction();
            $user->id = user_create_user($record, false, false);
            $usercreated = true;
            unset($record);

            $userinfo = new \stdClass();
            $userinfo->serverid     = $server->id;
            $userinfo->serveruserid = $serveruser->id;
            $userinfo->userid       = $user->id;
            $userinfo->timecreated  = time();
            $DB->insert_record('auth_connect_users', $userinfo);

            $transaction->allow_commit();
        }

        $userrecord = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);

        if ($sso) {
            // For performance reasons update extra user data only during login.
            $updatepictures = false;
            if ($usercreated and $userrecord->picture > 0) {
                $updatepictures = true;
            }
            if ($olduser and $userrecord->picture != $olduser->picture) {
                $updatepictures = true;
            }
            if ($updatepictures) {
                $usercontext = \context_user::instance($userrecord->id);
                $fs = get_file_storage();
                $fs->delete_area_files($usercontext->id, 'user', 'icon', 0);
                if ($userrecord->picture > 0) {
                    if (!empty($serveruser->pictures)) {
                        foreach ($serveruser->pictures as $filename => $data) {
                            $record = new \stdClass();
                            $record->contextid = $usercontext->id;
                            $record->component = 'user';
                            $record->filearea = 'icon';
                            $record->itemid = 0;
                            $record->filepath = '/';
                            $record->filename = $filename;
                            $fs->create_file_from_string($record, base64_decode($data));
                        }
                    }
                }
            }
        }

        // Sync profile fields.
        $fieldschanged = false;
        if ($server->apiversion >= 2 and isset($serveruser->profile_fields) and get_config('auth_connect', 'syncprofilefields')) {
            $fieldschanged = self::update_profile_fields($userrecord, $serveruser->profile_fields, $usercreated);
        }

        // Trigger events after all changes are stored in DB.
        if ($userundeleted) {
            // Do not use standard undelete_user() because it has extra validation.
            \totara_core\event\user_undeleted::create_from_user($userrecord)->trigger();

        } else if ($usercreated) {
            // Newly created user.
            \core\event\user_created::create_from_userid($userrecord->id)->trigger();
            if ($userrecord->suspended != 0) {
                \totara_core\event\user_suspended::create_from_user($userrecord)->trigger();
            }

        } else if ($userupdated or $fieldschanged) {
            // Just an update.
            \core\event\user_updated::create_from_userid($userrecord->id)->trigger();
            if ($usersuspended) {
                \totara_core\event\user_suspended::create_from_user($userrecord)->trigger();
            }
        }

        // Sync job assignments.
        if ($server->apiversion >= 2 and isset($serveruser->jobs) and get_config('auth_connect', 'syncjobs')) {
            $sql = "SELECT ja.*, ci.remoteid
                      FROM {job_assignment} ja
                 LEFT JOIN {auth_connect_ids} ci ON (ci.serverid = :serverid AND ci.tablename = 'job_assignment' AND ci.localid = ja.id)
                     WHERE ja.userid = :userid
                  ORDER BY ja.sortorder";
            $rs = $DB->get_recordset_sql($sql, array('userid' => $userrecord->id, 'serverid' => $server->id));
            $currentjobs = array();
            $oldsortorder = array();
            $manualpresent = false;
            foreach ($rs as $job) {
                if (!$job->remoteid) {
                    // Ignore manually created job assignments and do not reorder anything.
                    $manualpresent = true;
                    continue;
                }
                $currentjobs[$job->remoteid] = $job;
                unset($job->remoteid);
                $oldsortorder[] = $job->id;
            }
            $rs->close();
            $sortorder = array();
            foreach ($serveruser->jobs as $serverjob) {
                $serverjob = (object)$serverjob;
                $serverjob->userid = $userrecord->id;
                if (!isset($currentjobs[$serverjob->id])) {
                    $job = self::add_job_assignment($server, $serverjob);
                } else {
                    $job = self::update_job_assignment($server, $currentjobs[$serverjob->id], $serverjob);
                    unset($currentjobs[$serverjob->id]);
                }
                $sortorder[] = $job->id;
            }
            foreach ($currentjobs as $oldjob) {
                self::delete_job_assignment($oldjob);
            }
            if ($serveruser->jobs and !$manualpresent and ($oldsortorder !== $sortorder)) {
                \totara_job\job_assignment::resort_all($userrecord->id, $sortorder);
            }
        }

        return $userrecord;
    }

    /**
     * Add/update/remove custom user profile fields.
     *
     * @param \stdClass $user
     * @param array $serverfields
     * @param bool $usercreated
     * @return void
     */
    protected static function update_profile_fields(\stdClass $user, array $serverfields, $usercreated) {
        global $DB;

        if ($usercreated and !$serverfields) {
            return false;
        }

        $updated = false;

        $sql = "SELECT f.shortname, f.id, f.datatype, d.id AS dataid, d.data
                  FROM {user_info_field} f
             LEFT JOIN {user_info_data} d ON (d.fieldid = f.id AND d.userid = :userid)
              ORDER BY f.shortname ASC";
        $fields = $DB->get_records_sql($sql, array('userid' => $user->id));

        foreach ($serverfields as $serverfield) {
            $serverfield = (object)$serverfield;
            if (!isset($fields[$serverfield->shortname])) {
                // Field does not exist on client.
                continue;
            }
            $field = $fields[$serverfield->shortname];
            if ($field->datatype !== $serverfield->datatype) {
                // Delete data of fields with different type,
                // this way admins will see something if wrong with their set-up.
                continue;
            }
            unset($fields[$serverfield->shortname]);
            // We have a match, let's insert the value - no validation or file support, sorry.
            // It is the responsibility of admin to set up the profile fields in a compatible way!
            if (isset($field->dataid)) {
                if ($serverfield->data !== $field->data) {
                    $record = new \stdClass();
                    $record->id = $field->dataid;
                    $record->data = $serverfield->data;
                    $DB->update_record('user_info_data', $record);
                    $updated = true;
                }
            } else {
                $record = new \stdClass();
                $record->data = $serverfield->data;
                $record->fieldid = $field->id;
                $record->userid = $user->id;
                $DB->insert_record('user_info_data', $record);
                $updated = true;
            }
        }
        foreach ($fields as $field) {
            if (isset($field->dataid)) {
                $DB->delete_records('user_info_data', array('id' => $field->dataid));
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Add job assignment.
     *
     * @param \stdClass $server
     * @param \stdClass $serverjob
     * @return \stdClass job record
     */
    protected static function add_job_assignment(\stdClass $server, \stdClass $serverjob) {
        global $DB;

        $job = new \stdClass();
        $job->userid = $serverjob->userid;
        $job->fullname = $serverjob->fullname;
        $job->shortname = $serverjob->shortname;
        $job->idnumber = $serverjob->idnumber;
        $job->description = $serverjob->description;
        $job->startdate = $serverjob->startdate;
        $job->enddate = $serverjob->enddate;
        $job->positionid = null;
        if ($serverjob->positionid and get_config('auth_connect', 'syncpositions')) {
            $positionid = $DB->get_field('auth_connect_ids', 'localid',
                array('serverid' => $server->id, 'tablename' => 'pos', 'remoteid' => $serverjob->positionid));
            if ($positionid) {
                $job->positionid = $positionid;
            }
        }
        $job->organisationid = null;
        if ($serverjob->organisationid and get_config('auth_connect', 'syncorganisations')) {
            $organisationid = $DB->get_field('auth_connect_ids', 'localid',
                array('serverid' => $server->id, 'tablename' => 'org', 'remoteid' => $serverjob->organisationid));
            if ($organisationid) {
                $job->organisationid = $organisationid;
            }
        }
        $job->managerjaid = null;
        $job->tempmanagerjaid = null;
        $job->appraiserid = null;

        $conflict = $DB->get_record('job_assignment', array('userid' => $job->userid, 'idnumber' => $job->idnumber));
        if ($conflict) {
            self::delete_job_assignment($conflict);
        }

        $ja = \totara_job\job_assignment::create($job);

        $DB->delete_records('auth_connect_ids', array('serverid' => $server->id, 'tablename' => 'job_assignment', 'remoteid' => $serverjob->id));
        $record = new \stdClass();
        $record->serverid = $server->id;
        $record->tablename = 'job_assignment';
        $record->remoteid = $serverjob->id;
        $record->localid = $ja->id;
        $record->timecreated = time();
        $DB->insert_record('auth_connect_ids', $record);

        return $DB->get_record('job_assignment', array('id' => $ja->id));
    }

    /**
     * Update job assignment.
     *
     * @param \stdClass $server
     * @param \stdClass $oldjob
     * @param \stdClass $serverjob
     * @return \stdClass job record
     */
    protected static function update_job_assignment(\stdClass $server, \stdClass $oldjob, \stdClass $serverjob) {
        global $DB;

        if ($oldjob->userid != $serverjob->userid) {
            throw new \coding_exception('Invalid job assignment update request.');
        }

        $data = array();
        $testfields = array('fullname', 'shortname', 'idnumber', 'description', 'startdate', 'enddate');
        foreach ($testfields as $field) {
            if ($oldjob->$field !== $serverjob->$field) {
                $data[$field] = $serverjob->$field;
            }
        }
        $positionid = null;
        if ($serverjob->positionid and get_config('auth_connect', 'syncpositions')) {
            $positionid = $DB->get_field('auth_connect_ids', 'localid',
                array('serverid' => $server->id, 'tablename' => 'pos', 'remoteid' => $serverjob->positionid));
            if (!$positionid) {
                $positionid = null;
            }
        }
        if ($oldjob->positionid != $positionid) {
            $data['positionid'] = $positionid;
        }
        $organisationid = null;
        if ($serverjob->organisationid and get_config('auth_connect', 'syncorganisations')) {
            $organisationid = $DB->get_field('auth_connect_ids', 'localid',
                array('serverid' => $server->id, 'tablename' => 'org', 'remoteid' => $serverjob->organisationid));
            if (!$organisationid) {
                $organisationid = null;
            }
        }
        if ($oldjob->organisationid != $organisationid) {
            $data['organisationid'] = $organisationid;
        }

        if (!$data) {
            return $oldjob;
        }

        $conflict = $DB->get_record('job_assignment', array('userid' => $oldjob->userid, 'idnumber' => $serverjob->idnumber));
        if ($conflict and $conflict->id != $oldjob->id) {
            self::delete_job_assignment($conflict);
        }

        $ja = \totara_job\job_assignment::get_with_id($oldjob->id, false);
        if (!$ja) {
            // Oops, the record disappeared in the meantime.
            return self::add_job_assignment($server, $serverjob);
        }
        $ja->update($data);

        return $DB->get_record('job_assignment', array('id' => $oldjob->id));
    }

    /**
     * Delete job assignment.
     *
     * @param \stdClass $oldjob
     */
    protected static function delete_job_assignment(\stdClass $oldjob) {
        global $DB;

        $oldjob = $DB->get_record('job_assignment', array('id' => $oldjob->id));
        if ($oldjob) {
            $ja = \totara_job\job_assignment::get_with_id($oldjob->id);
            \totara_job\job_assignment::delete($ja);
        }

        $DB->delete_records('auth_connect_ids', array('tablename' => 'job_assignment', 'localid' => $oldjob->id));
    }

    /**
     * Sync all users with connect server.
     *
     * @param \stdClass $server
     * @return bool success
     */
    public static function sync_user_collections(\stdClass $server) {
        if ($server->status != self::SERVER_STATUS_OK) {
            return false;
        }
        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_user_collections',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        if ($result['status'] !== 'success' or !is_array($result['data'])) {
            return false;
        }

        self::update_local_user_collections($server, $result['data']);

        return true;
    }

    /**
     * Sync local cohorts and other collection types if they are implemented.
     *
     * @param \stdClass $server
     * @param array $servercollections
     */
    public static function update_local_user_collections(\stdClass $server, array $servercollections) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $records = $DB->get_records('auth_connect_user_collections', array('serverid' => $server->id));
        $existing = array();
        foreach ($records as $record) {
            $existing[$record->serverid . '-' . $record->collectiontype . '-' . $record->collectionid] = $record;
        }

        foreach ($servercollections as $type => $servercollection) {
            foreach ($servercollection as $serveritem) {
                unset($existing[$server->id . '-' . $type . '-' . $serveritem['id']]);
                self::update_local_user_collection($server, $type, $serveritem);
            }
            foreach ($existing as $collection) {
                $DB->delete_records('auth_connect_user_collections', array('id' => $collection->id));
                if ($cohort = $DB->get_record('cohort', array('id' => $collection->cohortid))) {
                    cohort_delete_cohort($cohort);
                }
            }
        }
    }

    /**
     * Update one local cohort to match user collection item on server.
     * (Map one server cohort or server course to one local cohort.)
     *
     * @param \stdClass $server
     * @param string $type 'cohort' or 'course'
     * @param array $serveritem
     */
    public static function update_local_user_collection(\stdClass $server, $type, array $serveritem) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $serveritem = (object)$serveritem;

        $cohort = false;
        $map = $DB->get_record('auth_connect_user_collections',
            array('serverid' => $server->id, 'collectiontype' => $type, 'collectionid' => $serveritem->id));
        if ($map) {
            $cohort = $DB->get_record('cohort', array('id' => $map->cohortid));
            if (!$cohort) {
                // Somebody was messing with the cohort in DB.
                $DB->delete_records('auth_connect_user_collections', array('id' => $map));
            }
        }
        unset($map);

        $idnumber = 'tc_' . $type . '_' . $serveritem->id . '_' . $server->serveridnumber;  // Something unique.
        $name = ($type === 'course') ? $serveritem->fullname :  $serveritem->name;
        $description = ($type === 'course') ? $serveritem->summary : $serveritem->description;
        $descriptionformat = ($type === 'course') ? $serveritem->summaryformat : $serveritem->descriptionformat;

        if ($cohort) {
            $update = false;

            if ($cohort->name !== $name) {
                $cohort->name = $name;
                $update = true;
            }
            if ($cohort->description !== $description) {
                $cohort->description = $description;
                $update = true;
            }
            if ($cohort->descriptionformat !== $descriptionformat) {
                $cohort->descriptionformat = $descriptionformat;
                $update = true;
            }
            if ($cohort->idnumber != $idnumber) {
                $cohort->idnumber = $idnumber;
                $update = true;
            }
            if ($cohort->component !== 'auth_connect') {
                // Hands off, this is our cohort!
                $cohort->component = 'auth_connect';
                $update = true;
            }

            if ($update) {
                cohort_update_cohort($cohort);
                $cohort = $DB->get_record('cohort', array('id' => $cohort->id));
            }

        } else {
            $trans = $DB->start_delegated_transaction();

            $cohort = new \stdClass();
            $cohort->name              = $name;
            $cohort->contextid         = \context_system::instance()->id;
            $cohort->idnumber          = $idnumber;
            $cohort->description       = $description;
            $cohort->descriptionformat = $descriptionformat;
            $cohort->component         = 'auth_connect';
            $cohort->id = cohort_add_cohort($cohort, false);
            $cohort = $DB->get_record('cohort', array('id' => $cohort->id));

            $record = new \stdClass();
            $record->serverid       = $server->id;
            $record->collectiontype = $type;
            $record->collectionid   = $serveritem->id;
            $record->cohortid       = $cohort->id;
            $record->timecreated    = time();
            $DB->insert_record('auth_connect_user_collections', $record);

            $trans->allow_commit();
        }

        // Now sync the cohort members.

        $sql = "SELECT cu.serveruserid, cu.userid
                  FROM {user} u
                  JOIN {auth_connect_users} cu ON cu.userid = u.id
                  JOIN {cohort_members} cm ON cm.userid = u.id
                 WHERE cu.serverid = :serverid AND cm.cohortid = :cohortid";
        $current = $DB->get_records_sql_menu($sql, array('serverid' => $server->id, 'cohortid' => $cohort->id));

        foreach ($serveritem->members as $serveruser) {
            $serveruserid = $serveruser['id'];
            if (isset($current[$serveruserid])) {
                unset($current[$serveruserid]);
                continue;
            }
            $sql = "SELECT u.id
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid = :serveruserid AND u.deleted = 0";
            $user = $DB->get_record_sql($sql, array('serverid' => $server->id, 'serveruserid' => $serveruserid));
            if ($user) {
                cohort_add_member($cohort->id, $user->id);
            }
        }

        foreach ($current as $serveruserid => $userid) {
            cohort_remove_member($cohort->id, $userid);
        }
    }

    /**
     * Sync positions with connect server.
     *
     * @param \stdClass $server
     * @return bool success
     */
    public static function sync_positions(\stdClass $server) {
        if ($server->status != self::SERVER_STATUS_OK) {
            return false;
        }

        if ($server->apiversion < 2) {
            return true;
        }

        if (!get_config('auth_connect', 'syncpositions')) {
            return true;
        }

        if (totara_feature_disabled('positions')) {
            return true;
        }

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_positions',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        if ($result['status'] !== 'success') {
            return false;
        }
        if (!isset($result['data']['frameworks']) or !isset($result['data']['positions'])) {
            // Disabled positions on server.
            return true;
        }

        self::update_local_hierarchy($server, 'pos', $result['data']['frameworks'], $result['data']['positions']);

        return true;
    }

    /**
     * Sync organisations with connect server.
     *
     * @param \stdClass $server
     * @return bool success
     */
    public static function sync_organisations(\stdClass $server) {
        if ($server->status != self::SERVER_STATUS_OK) {
            return false;
        }

        if ($server->apiversion < 2) {
            return true;
        }

        if (!get_config('auth_connect', 'syncorganisations')) {
            return true;
        }

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_organisations',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        if ($result['status'] !== 'success') {
            return false;
        }

        if (!isset($result['data']['frameworks']) or !isset($result['data']['organisations'])) {
            // Disabled organisations on server.
            return true;
        }

        self::update_local_hierarchy($server, 'org', $result['data']['frameworks'], $result['data']['organisations']);

        return true;
    }

    /**
     * Update hierarchy items.
     *
     * @param \stdClass $server
     * @param string $shortprefix Either pos or org
     * @param array $serverframeworks
     * @param array $serveritems
     */
    public static function update_local_hierarchy(\stdClass $server, $shortprefix, array $serverframeworks, array $serveritems) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        $sql = "SELECT ci.remoteid, f.*
                  FROM {{$shortprefix}_framework} f
                  JOIN {auth_connect_ids} ci ON (ci.tablename = :tablename AND ci.localid = f.id)
                 WHERE ci.serverid = :serverid
              ORDER BY ci.remoteid ASC";
        $localframeworks = $DB->get_records_sql($sql, array('serverid' => $server->id, 'tablename' => $shortprefix.'_framework'));

        $frids = array();
        foreach($serverframeworks as $sf) {
            $sf = (object)$sf;
            if (isset($localframeworks[$sf->id])) {
                $framework = self::update_framework($server, $shortprefix, $localframeworks[$sf->id], $sf);
                unset($framework->remoteid);
                $localframeworks[$sf->id] = $framework;
            } else {
                $framework = self::add_framework($server, $shortprefix, $sf);
                unset($framework->remoteid);
                $localframeworks[$sf->id] = $framework;
            }
            $frids[$framework->id] = $framework->id;
        }

        $deleteframeworks = array();
        foreach ($localframeworks as $sid => $localframework) {
            if (isset($localframework->remoteid)) {
                // Do not delete framework yet, we need to allow item migration first.
                $deleteframeworks[$sid] = $localframework;
                unset($localframeworks[$sid]);
            }
        }

        // First remove all items that did not come from this remote server,
        // nobody should be messing with these frameworks!
        if ($frids) {
            list($sqlfrids, $params) = $DB->get_in_or_equal($frids, SQL_PARAMS_NAMED);
            $params['serverid'] = $server->id;
            $params['tablename'] = $shortprefix;
            $sql = "SELECT i.*
                      FROM {{$shortprefix}} i
                 LEFT JOIN {auth_connect_ids} ci ON (ci.serverid = :serverid AND ci.tablename = :tablename AND ci.localid = i.id)
                     WHERE i.frameworkid {$sqlfrids} AND ci.id IS NULL
                  ORDER BY i.depthlevel DESC, i.id DESC";
            $deleteitems = $DB->get_records_sql($sql, $params);
            foreach ($deleteitems as $item) {
                self::delete_hierarchy_item($shortprefix, $item);
            }
        }

        $sql = "SELECT ci.remoteid, i.*
                  FROM {{$shortprefix}} i
                  JOIN {auth_connect_ids} ci ON (ci.serverid = :serverid AND ci.tablename = :tablename AND ci.localid = i.id)
              ORDER BY i.depthlevel DESC, i.id DESC";
        $localitems = $DB->get_records_sql($sql, array('serverid' => $server->id, 'tablename' => $shortprefix));

        // Note that server items are guaranteed to be sorted top-to-bottom,
        // it is safe to use parents from higher levels.
        foreach ($serveritems as $serveritem) {
            $serveritem = (object)$serveritem;
            if (!isset($localframeworks[$serveritem->frameworkid])) {
                continue;
            }
            $serveritem->frameworkid = $localframeworks[$serveritem->frameworkid]->id;
            if ($serveritem->parentid) {
                if (!isset($localitems[$serveritem->parentid])) {
                    continue;
                }
                $serveritem->parentid = $localitems[$serveritem->parentid]->id;
            }
            if (isset($localitems[$serveritem->id])) {
                $localitem = $localitems[$serveritem->id];
                $item = self::update_hierarchy_item($server, $shortprefix, $localitem, $serveritem);
                unset($item->remoteid); // Mark as processed.
                $localitems[$serveritem->id] = $item;
            } else {
                $item = self::add_hierarchy_item($server, $shortprefix, $serveritem);
                unset($item->remoteid);  // Mark as processed.
                $localitems[$serveritem->id] = $item;
            }
        }

        foreach ($localitems as $localitem) {
            if (isset($localitem->remoteid)) {
                self::delete_hierarchy_item($shortprefix, $localitem);
            }
        }
        unset($localitems);

        foreach ($deleteframeworks as $deleteframework) {
            self::delete_framework($shortprefix, $deleteframework);
        }

        // Replicate the sort order in bulk here, we cannot do it earlier before all items exist,
        // ignore any inconsistencies here.
        $sql = "SELECT ci.remoteid, i.id, i.sortthread
                  FROM {{$shortprefix}} i
                  JOIN {auth_connect_ids} ci ON (ci.serverid = :serverid AND ci.tablename = :tablename AND ci.localid = i.id)";
        $localitems = $DB->get_records_sql($sql, array('serverid' => $server->id, 'tablename' => $shortprefix));
        foreach ($serveritems as $serveritem) {
            $serveritem = (object)$serveritem;
            if (!isset($localitems[$serveritem->id])) {
                continue;
            }
            $localitem = $localitems[$serveritem->id];
            if ($localitem->sortthread === $serveritem->sortthread) {
                continue;
            }
            $DB->set_field($shortprefix, 'sortthread', $serveritem->sortthread, array('id' => $localitem->id));
        }
    }

    /**
     * Add new framework.
     *
     * @param \stdClass $server
     * @param string $shortprefix
     * @param \stdClass $serverframework
     * @return \stdClass framework
     */
    protected static function add_framework(\stdClass $server, $shortprefix, $serverframework) {
        global $DB, $USER;

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        $now = time();

        $record = new \stdClass();
        $record->shortname = $serverframework->shortname;
        $record->idnumber = $serverframework->idnumber;
        $record->description = $serverframework->description;
        $record->sortorder = $DB->get_field($shortprefix.'_framework', 'COALESCE(MAX(sortorder), 0) + 1', array());
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->usermodified = empty($USER->id) ? get_admin()->id : $USER->id; // Mimic update_hierarchy_item();
        $record->visible = $serverframework->visible;
        $record->hidecustomfields = $serverframework->hidecustomfields;
        $record->fullname = $serverframework->fullname;

        $record->id = $DB->insert_record($shortprefix.'_framework', $record);
        $framework = $DB->get_record($shortprefix.'_framework', array('id' => $record->id));

        $DB->delete_records('auth_connect_ids', array('serverid' => $server->id, 'tablename' => $shortprefix.'_framework', 'remoteid' => $serverframework->id));
        $record = new \stdClass();
        $record->serverid = $server->id;
        $record->tablename = $shortprefix.'_framework';
        $record->remoteid = $serverframework->id;
        $record->localid = $framework->id;
        $record->timecreated = $now;
        $DB->insert_record('auth_connect_ids', $record);

        $eventclass = "\\hierarchy_{$prefix}\\event\\framework_created";
        $eventclass::create_from_instance($framework)->trigger();

        return $framework;
    }

    /**
     * Update existing framework.
     *
     * @param \stdClass $server
     * @param string $shortprefix
     * @param \stdClass $framework
     * @param \stdClass $serverframework
     * @return \stdClass framework
     */
    protected static function update_framework(\stdClass $server, $shortprefix, $framework, $serverframework) {
        global $DB, $USER;

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        $record = array();
        $testfields = array('shortname', 'idnumber', 'description', 'visible', 'hidecustomfields', 'fullname');
        foreach ($testfields as $field) {
            if ($framework->$field !== $serverframework->$field) {
                $record[$field] = $serverframework->$field;
            }
        }

        if (!$record) {
            return $framework;
        }

        $record = (object)$record;
        $record->id = $framework->id;

        $record->timemodified = time();
        $record->usermodified = empty($USER->id) ? get_admin()->id : $USER->id; // Mimic update_hierarchy_item();

        $DB->update_record($shortprefix.'_framework', $record);
        $framework = $DB->get_record($shortprefix.'_framework', array('id' => $record->id));

        $eventclass = "\\hierarchy_{$prefix}\\event\\framework_updated";
        $eventclass::create_from_instance($framework)->trigger();

        return $framework;
    }

    /**
     * Delete framework and all child items.
     *
     * @param string $shortprefix
     * @param \stdClass $framework
     * @return void
     */
    protected static function delete_framework($shortprefix, $framework) {
        global $DB;

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        // Delete items cleanup up the mapping tables.
        $items = $DB->get_records($shortprefix, array('frameworkid' => $framework->id), 'depthlevel DESC');
        foreach ($items as $item) {
            self::delete_hierarchy_item($shortprefix, $item);
        }

        $hierarchy = \hierarchy::load_hierarchy($prefix);
        $hierarchy->get_framework($framework->id);
        $hierarchy->delete_framework(false);
        $DB->delete_records('auth_connect_ids', array('tablename' => $shortprefix.'_framework', 'localid' => $framework->id));

        $eventclass = "\\hierarchy_{$prefix}\\event\\framework_deleted";
        $eventclass::create_from_instance($framework)->trigger();
    }

    /**
     * Add position/organisation.
     *
     * @param \stdClass $server
     * @param string $shortprefix
     * @param \stdClass $serveritem
     * @return \stdClass item
     */
    protected static function add_hierarchy_item(\stdClass $server, $shortprefix, \stdClass $serveritem) {
        global $DB, $USER;

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        $item = clone($serveritem);

        unset($item->id);
        unset($item->path);
        unset($item->depth);
        unset($item->sortthread);
        unset($item->timecreated);
        unset($item->timemodified);
        $item->usermodified = empty($USER->id) ? get_admin()->id : $USER->id; // Mimic update_hierarchy_item();
        unset($item->totarasync);

        $item->typeid = 0;
        if (isset($item->typeidnumber) and $item->typeidnumber !== '') {
            $type = $DB->get_record($shortprefix . '_type', array('idnumber' => $item->typeidnumber));
            if ($type) {
                $item->typeid = $type->id;
            }
        }

        $hierarchy = \hierarchy::load_hierarchy($prefix);
        $newitem = $hierarchy->add_hierarchy_item($item, $item->parentid, $item->frameworkid, false, false, false);

        if (!$newitem) {
            // This is a bloody mess, add_hierarchy_item should never fail and when it does it should trigger exceptions!
            throw new \coding_exception('Cannot insert new hierarchy item');
        }

        $DB->delete_records('auth_connect_ids', array('serverid' => $server->id, 'tablename' => $shortprefix, 'remoteid' => $serveritem->id));
        $record = new \stdClass();
        $record->serverid = $server->id;
        $record->tablename = $shortprefix;
        $record->remoteid = $serveritem->id;
        $record->localid = $newitem->id;
        $record->timecreated = time();
        $DB->insert_record('auth_connect_ids', $record);

        // Add custom fields.
        if ($newitem->typeid and isset($serveritem->custom_fields) and $serveritem->custom_fields) {
            self::update_hierarchy_item_fields($shortprefix, $newitem, $serveritem->custom_fields, true);
        }

        $eventclass = "\\hierarchy_{$prefix}\\event\\{$prefix}_created";
        $eventclass::create_from_instance($newitem)->trigger();

        return $newitem;
    }

    /**
     * Update position/organisation.
     *
     * @param \stdClass $server
     * @param string $shortprefix
     * @param \stdClass $item
     * @param \stdClass $serveritem
     * @return \stdClass item
     */
    protected static function update_hierarchy_item(\stdClass $server, $shortprefix, \stdClass $item, \stdClass $serveritem) {
        global $DB;

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        $serveritem = clone($serveritem);

        $serveritem->typeid = '0';
        if (isset($serveritem->typeidnumber) and $serveritem->typeidnumber !== '') {
            $type = $DB->get_record($shortprefix . '_type', array('idnumber' => $serveritem->typeidnumber));
            if ($type) {
                $serveritem->typeid = $type->id;
            }
            unset($type);
        }
        if ($serveritem->typeid != $item->typeid) {
            // Delete all custom fields, they will be recreated later.
            $fields = $DB->get_fieldset_sql("SELECT id FROM {{$shortprefix}_type_info_data} WHERE {$prefix}id = ?", array($item->id));
            if (!empty($fields)) {
                list($sqlin, $paramsin) = $DB->get_in_or_equal($fields);
                $DB->delete_records_select("{$shortprefix}_type_info_data_param", "dataid {$sqlin}", $paramsin);
                $DB->delete_records_select("{$shortprefix}_type_info_data", "id {$sqlin}", $paramsin);
            }
        }

        $updated = false;
        $testfields = array('shortname', 'idnumber', 'description', 'frameworkid', 'visible', 'fullname', 'typeid', 'frameworkid', 'parentid');
        if ($shortprefix === 'pos') {
            $testfields[] = 'timevalidfrom';
            $testfields[] = 'timevalidto';
        }
        foreach ($testfields as $field) {
            if ($item->$field !== $serveritem->$field) {
                $item->$field = $serveritem->$field;
                $updated = true;
            }
        }
        if ($updated) {
            unset($item->timemodified);
            $hierarchy = \hierarchy::load_hierarchy($prefix);
            $updateditem = $hierarchy->update_hierarchy_item($item->id, $item, false, false, false);
        } else {
            $updateditem = $item;
        }

        // Add and update all custom fields.
        if ($updateditem->typeid and isset($serveritem->custom_fields)) {
            if (self::update_hierarchy_item_fields($shortprefix, $updateditem, $serveritem->custom_fields, false)) {
                $updated = true;
            }
        }

        if ($updated) {
            $eventclass = "\\hierarchy_{$prefix}\\event\\{$prefix}_updated";
            $eventclass::create_from_instance($updateditem)->trigger();
        }

        return $updateditem;
    }

    /**
     * Delete position/organisation
     *
     * @param string $shortprefix
     * @param \stdClass $item
     */
    protected static function delete_hierarchy_item($shortprefix, \stdClass $item) {
        global $DB;

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        $hierarchy = \hierarchy::load_hierarchy($prefix);
        $hierarchy->delete_hierarchy_item($item->id, true);

        $DB->delete_records('auth_connect_ids', array('tablename' => $shortprefix, 'localid' => $item->id));
    }

    /**
     * Update hierarchy item custom fields.
     *
     * @param string $shortprefix
     * @param \stdClass $item
     * @param array $serverfields
     * @param bool $created
     */
    public static function update_hierarchy_item_fields($shortprefix, \stdClass $item, array $serverfields, $created) {
        global $DB;

        if ($shortprefix === 'pos') {
            $prefix = 'position';
        } else if ($shortprefix === 'org') {
            $prefix = 'organisation';
        } else {
            throw new \coding_exception('Unsupported short prefix: ' . $shortprefix);
        }

        if ($created and !$serverfields) {
            return false;
        }

        $updated = false;

        $sql = "SELECT f.shortname, f.id, f.datatype, d.id AS dataid, d.data, p.id AS paramid, p.value
                  FROM {{$shortprefix}_type_info_field} f
             LEFT JOIN {{$shortprefix}_type_info_data} d ON (d.fieldid = f.id AND d.{$prefix}id = :itemid)
             LEFT JOIN {{$shortprefix}_type_info_data_param} p ON p.dataid = d.id
                 WHERE f.typeid = :typeid
              ORDER BY f.shortname ASC";
        $fields = $DB->get_records_sql($sql, array('itemid' => $item->id, 'typeid' => $item->typeid));

        foreach ($serverfields as $serverfield) {
            $serverfield = (object)$serverfield;
            if (!isset($fields[$serverfield->shortname])) {
                // Field does not exist on client.
                continue;
            }
            $field = $fields[$serverfield->shortname];
            if ($field->datatype !== $serverfield->datatype) {
                // Delete data of fields with different type,
                // this way admins will see something if wrong with their set-up.
                continue;
            }
            unset($fields[$serverfield->shortname]);
            // We have a match, let's insert the value - no validation or file support, sorry.
            // It is the responsibility of admin to set up the type fields the same way!
            if (isset($field->dataid)) {
                if ($serverfield->data !== $field->data) {
                    $record = new \stdClass();
                    $record->id = $field->dataid;
                    $record->data = $serverfield->data;
                    $DB->update_record($shortprefix . '_type_info_data', $record);
                    $updated = true;
                }
                $did = $field->dataid;
            } else {
                $record = new \stdClass();
                $record->data = $serverfield->data;
                $record->fieldid = $field->id;
                $record->{$prefix . 'id'} = $item->id;
                $did = $DB->insert_record($shortprefix . '_type_info_data', $record);
                $updated = true;
            }
            if (isset($serverfield->value)) {
                if (isset($field->paramid)) {
                    if ($serverfield->value !== $field->value) {
                        $record = new \stdClass();
                        $record->id = $field->paramid;
                        $record->value = $serverfield->value;
                        $DB->update_record($shortprefix . '_type_info_data_param', $record);
                        $updated = true;
                    }
                } else {
                    $record = new \stdClass();
                    $record->dataid = $did;
                    $record->value = $serverfield->value;
                    $DB->insert_record($shortprefix . '_type_info_data_param', $record);
                    $updated = true;
                }
            } else {
                if (isset($field->paramid)) {
                    $DB->delete_records($shortprefix . '_type_info_data_param', array('id' => $field->paramid));
                    $updated = true;
                }
            }
        }
        foreach ($fields as $field) {
            if (isset($field->paramid)) {
                $DB->delete_records($shortprefix . '_type_info_data_param', array('id' => $field->paramid));
                $updated = true;
            }
            if (isset($field->dataid)) {
                $DB->delete_records($shortprefix . '_type_info_data', array('id' => $field->dataid));
                $updated = true;
            }
        }

        return $updated;
    }
    /**
     * Finish SSO request by setting up $USER and adding new user if necessary.
     *
     * @param \stdClass $server
     * @param string $ssotoken
     */
    public static function finish_sso(\stdClass $server, $ssotoken) {
        global $SESSION, $CFG, $DB;

        if (isloggedin() and !isguestuser()) {
            throw new \coding_exception('user must not be logged in yet');
        }

        // Fetch user info for given token.

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_sso_user',
            'ssotoken' => $ssotoken,
        );

        $url = self::get_sep_url($server);

        $result = jsend::request($url, $data);

        if ($result['status'] !== 'success') {
            util::log_sso_attempt_error("SSO error during finishing callback - " . $result['message']);
            util::sso_error_page('ssoerrorgeneral', get_login_url());
        }

        $serveruser = $result['data'];
        $user = self::update_local_user($server, $serveruser, true);

        if (!$user or $user->deleted != 0 or $user->suspended != 0) {
            // Cannot login on this client, sorry.
            util::log_sso_attempt_error("SSO server user {$serveruser->id} is not allowed to log in to this client");
            util::sso_error_page('ssoerrornotallowed', get_login_url());
        }

        complete_user_login($user);

        $ssosession = new \stdClass();
        $ssosession->sid          = session_id();
        $ssosession->ssotoken     = $ssotoken;
        $ssosession->serverid     = $server->id;
        $ssosession->serveruserid = $serveruser['id'];
        $ssosession->userid       = $user->id;
        $ssosession->timecreated  = time();

        $DB->insert_record('auth_connect_sso_sessions', $ssosession);

        if (isset($SESSION->wantsurl)) {
            $urltogo = $SESSION->wantsurl;
        } else {
            $urltogo = $CFG->wwwroot . '/';
        }

        // Clear all session flags.
        unset($SESSION->wantsurl);
        unset($SESSION->loginerrormsg);

        redirect($urltogo);
    }

    /**
     * Request SSO session.
     *
     * @param \stdClass $server record from auth_connect_servers table
     * @return \moodle_url SSO request passed via web browser
     */
    public static function create_sso_request(\stdClass $server) {
        global $DB;

        if (!is_enabled_auth('connect')) {
            return null;
        }

        if (isloggedin() and !isguestuser()) {
            return null;
        }

        if (!session_id()) {
            // This should not happen, every normal web request must have sid.
            util::log_sso_attempt_error('Cannot start SSO session because session_id is missing');
            return null;
        }

        if ($server->status != self::SERVER_STATUS_OK) {
            return null;
        }

        $request = $DB->get_record('auth_connect_sso_requests', array('sid' => session_id()));
        if ($request and time() - $request->timecreated > self::REQUEST_LOGIN_TIMEOUT) {
            // Delete previous timed-out attempt and try again with different request id.
            $DB->delete_records('auth_connect_sso_requests', array('sid' => session_id()));
            $request = null;
        }

        if (!$request) {
            $request = new \stdClass();
            $request->serverid     = $server->id;
            $request->requesttoken = self::create_unique_hash('auth_connect_sso_requests', 'requesttoken');
            $request->sid          = session_id();
            $request->timecreated  = time();
            $request->id = $DB->insert_record('auth_connect_sso_requests', $request);
        }

        $requestparams = array('clientidnumber' => $server->clientidnumber, 'requesttoken' => $request->requesttoken);
        return new \moodle_url(self::get_sso_request_url($server), $requestparams);
    }

    /**
     * Get local user name.
     *
     * Note: username duplicates are not verified here intentionally.
     *
     * @param \stdClass $server
     * @param \stdClass $serveruser
     * @return string username for local user table
     */
    protected static function create_local_username(\stdClass $server, \stdClass $serveruser) {
        // This should be unique enough because serveridnnumber is complex and unique.
        return 'tc_' . $serveruser->id . '_' . $server->serveridnumber;
    }

    /**
     * Return notice if site not https.
     * @return string html fragment
     */
    public static function warn_if_not_https() {
        global $CFG, $OUTPUT;
        if (strpos($CFG->wwwroot, 'https://') !== 0) {
            return $OUTPUT->notification(get_string('errorhttp', 'auth_connect'), 'notifyproblem');
        }
        return '';
    }
}
