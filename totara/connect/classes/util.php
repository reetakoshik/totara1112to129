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
 * @package totara_connect
 */

namespace totara_connect;

use \totara_core\jsend;

/**
 * Class util containing internal implementation,
 * do NOT use outside of this plugin.
 *
 * @package totara_connect
 */
class util {
    /** Client is active. */
    const CLIENT_STATUS_OK = 0;
    /** Client was deleted, it will not be used any more */
    const CLIENT_STATUS_DELETED = 1;

    /** Minimum version supported by this server */
    const MIN_API_VERSION = 1;
    /** Maximum version supported by this server */
    const MAX_API_VERSION = 2;

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
            $secret = sha1(random_string(20) . get_site_identifier());
        } while ($DB->record_exists_select($table, "{$field} = ?", array($secret)));
        // The select allows comparison of text fields, Oracle is not supported!

        return $secret;
    }

    /**
     * Get the setup endpoint.
     *
     * @param \stdClass $client client record
     * @return string
     */
    public static function get_sep_setup_url(\stdClass $client) {
        return $client->clienturl . '/auth/connect/sep_setup.php';
    }

    /**
     * Get service endpoint.
     *
     * @param \stdClass $client client record
     * @return string
     */
    public static function get_sep_url(\stdClass $client) {
        return $client->clienturl . '/auth/connect/sep.php';
    }

    /**
     * get final SSO endpoint.
     *
     * @param \stdClass $client client record
     * @return string
     */
    public static function get_sso_finish_url(\stdClass $client) {
        return $client->clienturl . '/auth/connect/sso_finish.php';
    }

    /**
     * Register new client.
     *
     * @param \stdClass $data from totara_connect_form_client_add
     * @return int|bool new client id or false on failure
     */
    public static function add_client($data) {
        global $CFG, $SITE, $DB;

        // WS request may take some time.
        ignore_user_abort();
        \core_php_time_limit::raise(120);

        $client = new \stdClass();
        $client->status         = self::CLIENT_STATUS_OK;
        $client->clientidnumber = self::create_unique_hash('totara_connect_clients', 'clientidnumber');
        $client->clientsecret   = self::create_unique_hash('totara_connect_clients', 'clientsecret');
        $client->clientname     = $data->clientname;
        $client->clienturl      = rtrim($data->clienturl, '/');
        $client->clienttype     = ''; // Still unknown.
        $client->clientcomment  = $data->clientcomment;
        $client->cohortid       = (empty($data->cohortid) ? null : $data->cohortid);
        $client->syncprofilefields = $data->syncprofilefields;
        $client->serversecret   = self::create_unique_hash('totara_connect_clients', 'serversecret');
        $client->apiversion     = self::MIN_API_VERSION; // The lowest allowed API version, client may change it later.
        $client->addnewcohorts  = $data->addnewcohorts;
        $client->addnewcourses  = $data->addnewcourses;
        $client->syncjobs       = $data->syncjobs;
        $client->allowpluginsepservices = isset($data->allowpluginsepservices) ? $data->allowpluginsepservices : 0;
        $client->timecreated    = time();
        $client->timemodified   = $client->timecreated;

        $client->id = $DB->insert_record('totara_connect_clients', $client);

        if ($data->cohorts !== '') {
            $cohorts = explode(',', $data->cohorts);
            foreach ($cohorts as $cid) {
                self::add_client_cohort($client, $cid);
            }
        }

        if ($data->courses !== '') {
            $courses = explode(',', $data->courses);
            foreach ($courses as $cid) {
                self::add_client_course($client, $cid);
            }
        }

        if (!empty($data->positionframeworks)) {
            foreach ($data->positionframeworks as $fid) {
                self::add_client_pos_framework($client, $fid);
            }
        }

        if (!empty($data->organisationframeworks)) {
            foreach ($data->organisationframeworks as $fid) {
                self::add_client_org_framework($client, $fid);
            }
        }

        $data = array(
            'setupsecret'    => $data->setupsecret,
            'serveridnumber' => get_config('totara_connect', 'serveridnumber'),
            'serversecret'   => $client->serversecret,
            'serverurl'      => $CFG->wwwroot,
            'servername'     => $SITE->fullname,
            'clientidnumber' => $client->clientidnumber,
            'clientsecret'   => $client->clientsecret,
            'minapiversion'  => self::MIN_API_VERSION,
            'maxapiversion'  => self::MAX_API_VERSION,
        );

        $result = jsend::request(self::get_sep_setup_url($client), $data);

        if ($result['status'] === 'success') {
            return $client->id;
        }

        // Completely delete the client record,
        // we do not want any leftovers after a failed registration.
        $DB->set_field('totara_connect_clients', 'status', self::CLIENT_STATUS_DELETED, array('id' => $client->id));
        $client->status = self::CLIENT_STATUS_DELETED;
        self::purge_deleted_client($client);
        $DB->delete_records('totara_connect_clients', array('id' => $client->id));

        return false;
    }

    /**
     * Edit client.
     *
     * @param \stdClass $data from totara_connect_form_client_edit
     */
    public static function edit_client($data) {
        global $DB;

        $client = new \stdClass();
        $client->id            = $data->id;
        $client->clientname    = $data->clientname;
        $client->clientcomment = $data->clientcomment;
        $client->cohortid      = (empty($data->cohortid) ? null : $data->cohortid);
        $client->syncprofilefields = $data->syncprofilefields;
        $client->addnewcohorts = $data->addnewcohorts;
        $client->addnewcourses = $data->addnewcourses;
        $client->syncjobs      = $data->syncjobs;
        $client->allowpluginsepservices = isset($data->allowpluginsepservices) ? $data->allowpluginsepservices : 0;
        $client->timemodified  = time();

        $DB->update_record('totara_connect_clients', $client);
        $client = $DB->get_record('totara_connect_clients', array('id' => $client->id));

        if ($client->status == util::CLIENT_STATUS_DELETED) {
            // They should not be able to get here, make sure there are no leftovers.
            util::purge_deleted_client($client);
            return;
        };

        // Update cohorts.
        if (!$data->cohorts) {
            $cohorts = array();
        } else {
            $cohorts = explode(',', $data->cohorts);
            $cohorts = array_flip($cohorts);
        }
        $current = $DB->get_records('totara_connect_client_cohorts', array('clientid' => $client->id));
        foreach ($current as $a) {
            if (isset($cohorts[$a->cohortid])) {
                unset($cohorts[$a->cohortid]);
                continue;
            }
            self::remove_client_cohort($client, $a->cohortid);
        }
        foreach ($cohorts as $cid => $unused) {
            self::add_client_cohort($client, $cid);
        }

        // Update courses.
        if (!$data->courses) {
            $courses = array();
        } else {
            $courses = explode(',', $data->courses);
            $courses = array_flip($courses);
        }
        $current = $DB->get_records('totara_connect_client_courses', array('clientid' => $client->id));
        foreach ($current as $a) {
            if (isset($courses[$a->courseid])) {
                unset($courses[$a->courseid]);
                continue;
            }
            self::remove_client_course($client, $a->courseid);
        }
        foreach ($courses as $cid => $unused) {
            self::add_client_course($client, $cid);
        }

        // Update pos frameworks.
        if (empty($data->positionframeworks)) {
            $frameworks = array();
        } else {
            $frameworks = array_values($data->positionframeworks);
            $frameworks = array_flip($frameworks);
        }
        $current = $DB->get_records('totara_connect_client_pos_frameworks', array('clientid' => $client->id));
        foreach ($current as $a) {
            if (isset($frameworks[$a->fid])) {
                unset($frameworks[$a->fid]);
                continue;
            }
            self::remove_client_pos_framework($client, $a->fid);
        }
        foreach ($frameworks as $fid => $unused) {
            self::add_client_pos_framework($client, $fid);
        }

        // Update org frameworks.
        if (empty($data->organisationframeworks)) {
            $frameworks = array();
        } else {
            $frameworks = array_values($data->organisationframeworks);
            $frameworks = array_flip($frameworks);
        }
        $current = $DB->get_records('totara_connect_client_org_frameworks', array('clientid' => $client->id));
        foreach ($current as $a) {
            if (isset($frameworks[$a->fid])) {
                unset($frameworks[$a->fid]);
                continue;
            }
            self::remove_client_org_framework($client, $a->fid);
        }
        foreach ($frameworks as $fid => $unused) {
            self::add_client_org_framework($client, $fid);
        }
    }

    /**
     * Purge all data related to deleted client client.
     * @param
     */
    public static function purge_deleted_client($client) {
        global $DB;

        if ($client->status != self::CLIENT_STATUS_DELETED) {
            throw new \coding_exception('Cannot purge active client');
        }

        $DB->delete_records('totara_connect_sso_sessions', array('clientid' => $client->id));
        $DB->delete_records('totara_connect_client_cohorts', array('clientid' => $client->id));
        $DB->delete_records('totara_connect_client_courses', array('clientid' => $client->id));
        $DB->delete_records('totara_connect_client_pos_frameworks', array('clientid' => $client->id));
        $DB->delete_records('totara_connect_client_org_frameworks', array('clientid' => $client->id));
    }

    /**
     * Add cohort to client.
     *
     * @param \stdClass $client client record
     * @param int $cohortid
     */
    public static function add_client_cohort($client, $cohortid) {
        global $DB;

        $rec = new \stdClass();
        $rec->clientid    = $client->id;
        $rec->cohortid    = $cohortid;
        $rec->timecreated = time();
        $DB->insert_record('totara_connect_client_cohorts', $rec);
    }

    /**
     * Remove cohort from client.
     *
     * @param \stdClass $client client record
     * @param int $cohortid
     */
    public static function remove_client_cohort($client, $cohortid) {
        global $DB;
        $DB->delete_records('totara_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohortid));
    }

    /**
     * Add course to client.
     *
     * @param \stdClass $client client record
     * @param int $courseid
     */
    public static function add_client_course($client, $courseid) {
        global $DB;

        $rec = new \stdClass();
        $rec->clientid    = $client->id;
        $rec->courseid    = $courseid;
        $rec->timecreated = time();
        $DB->insert_record('totara_connect_client_courses', $rec);
    }

    /**
     * Add course to pos framework.
     *
     * @param \stdClass $client client record
     * @param int $fid
     */
    public static function add_client_pos_framework($client, $fid) {
        global $DB;

        $rec = new \stdClass();
        $rec->clientid = $client->id;
        $rec->fid      = $fid;
        $rec->timecreated = time();
        $DB->insert_record('totara_connect_client_pos_frameworks', $rec);
    }

    /**
     * Add course to org framework.
     *
     * @param \stdClass $client client record
     * @param int $fid
     */
    public static function add_client_org_framework($client, $fid) {
        global $DB;

        $rec = new \stdClass();
        $rec->clientid = $client->id;
        $rec->fid      = $fid;
        $rec->timecreated = time();
        $DB->insert_record('totara_connect_client_org_frameworks', $rec);
    }

    /**
     * Remove course from client.
     *
     * @param \stdClass $client client record
     * @param int $courseid
     */
    public static function remove_client_course($client, $courseid) {
        global $DB;
        $DB->delete_records('totara_connect_client_courses', array('clientid' => $client->id, 'courseid' => $courseid));
    }

    /**
     * Remove pos framework from client.
     *
     * @param \stdClass $client client record
     * @param int $fid
     */
    public static function remove_client_pos_framework($client, $fid) {
        global $DB;
        $DB->delete_records('totara_connect_client_pos_frameworks', array('clientid' => $client->id, 'fid' => $fid));
    }

    /**
     * Remove org framework from client.
     *
     * @param \stdClass $client client record
     * @param int $fid
     */
    public static function remove_client_org_framework($client, $fid) {
        global $DB;
        $DB->delete_records('totara_connect_client_org_frameworks', array('clientid' => $client->id, 'fid' => $fid));
    }

    /**
     * Is this request token valid for this client?
     *
     * @param \stdClass $client client record
     * @param string $requesttoken secret token created by client when requesting SSO login
     * @return bool
     */
    public static function validate_sso_request_token(\stdClass $client, $requesttoken) {
        $data = array(
            'clientidnumber' => $client->clientidnumber,
            'clientsecret'   => $client->clientsecret,
            'service'        => 'validate_sso_request_token',
            'requesttoken'   => $requesttoken,
        );

        $result = jsend::request(self::get_sep_url($client), $data);

        return ($result['status'] === 'success');
    }

    /**
     * Create SSO session record for current user on given client.
     *
     * @param \stdClass $client client record
     * @return \stdClass connect session record, null on error
     */
    public static function create_sso_session(\stdClass $client) {
        global $USER, $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        if (!isloggedin() or isguestuser()) {
            return null;
        }

        if ($client->cohortid) {
            // Cohort restriction is used, user may login only if member of cohort.
            if (!cohort_is_member($client->cohortid, $USER->id)) {
                return null;
            }
        }

        $session = new \stdClass();
        $session->clientid    = $client->id;
        $session->userid      = $USER->id;
        $session->sid         = session_id();
        $session->ssotoken    = self::create_unique_hash('totara_connect_sso_sessions', 'ssotoken');
        $session->timecreated = time();

        $session->id = $DB->insert_record('totara_connect_sso_sessions', $session);

        return $DB->get_record('totara_connect_sso_sessions', array('id' => $session->id));
    }

    /**
     * Hook called before timing out of user session.
     *
     * @param \stdClass $user
     * @param string $sid session id
     * @param int $timecreated start of session
     * @param int $timemodified user last seen
     * @return bool true means do not timeout session yet
     */
    public static function ignore_timeout_hook($user, $sid, $timecreated, $timemodified) {
        global $CFG, $DB;

        if (empty($CFG->enableconnectserver)) {
            return false;
        }

        $ssosessions = $DB->get_records('totara_connect_sso_sessions', array('sid' => $sid, 'userid' => $user->id));
        foreach ($ssosessions as $ssosession) {
            $client = $DB->get_record('totara_connect_clients', array('id' => $ssosession->clientid));
            if (!$client or $client->status != self::CLIENT_STATUS_OK) {
                continue;
            }

            $data = array(
                'clientidnumber' => $client->clientidnumber,
                'clientsecret'   => $client->clientsecret,
                'service'        => 'is_sso_user_active',
                'ssotoken'       => $ssosession->ssotoken,
            );

            $result = jsend::request(self::get_sep_url($client), $data, 10);
            if ($result['status'] === 'success' and !empty($result['data']['active'])) {
                // User is active on client, this means we should not timeout session on this server.
                return true;
            }
        }

        return false;
    }

    /**
     * Terminate SSO session on client and remove server record.
     *
     * @param \stdClass $client client record
     * @param \stdClass $ssosession session record
     */
    public static function terminate_sso_session(\stdClass $client, \stdClass $ssosession) {
        global $DB;

        $DB->delete_records('totara_connect_sso_sessions', array('id' => $ssosession->id));

        $data = array(
            'clientidnumber' => $client->clientidnumber,
            'clientsecret'   => $client->clientsecret,
            'service'        => 'kill_sso_user',
            'ssotoken'       => $ssosession->ssotoken,
        );

        // Ignore result, this may be called at any time.
        jsend::request(self::get_sep_url($client), $data);
    }

    /**
     * Returns extra info text for login page,
     * usually the back button if user is logging from the TC client.
     *
     * @return string html fragment
     */
    public static function login_page_info() {
        global $CFG, $DB, $SESSION, $OUTPUT;

        if (empty($CFG->enableconnectserver) or empty($SESSION->totaraconnectssostarted)) {
            return null;
        }

        $clientidnumber = $SESSION->totaraconnectssostarted['clientidnumber'];
        $requesttoken = $SESSION->totaraconnectssostarted['requesttoken'];

        $client = $DB->get_record('totara_connect_clients', array('clientidnumber' => $clientidnumber));
        if (!$client) {
            return null;
        }

        $url = new \moodle_url('/totara/connect/sso_request.php', array(
            'clientidnumber' => $clientidnumber,
            'requesttoken' => $requesttoken,
            'action' => 'cancel',
            'sesskey' => sesskey(),
        ));

        return '<div class="subcontent">' . $OUTPUT->single_button($url, get_string('cancelsso', 'totara_connect', format_string($client->clientname))) . '</div>';
    }

    /**
     * Print notice if site not https.
     * @return string html fragment
     */
    public static function warn_if_not_https() {
        global $CFG, $OUTPUT;
        if (strpos($CFG->wwwroot, 'https://') !== 0) {
            return $OUTPUT->notification(get_string('errorhttpserver', 'totara_connect'), 'notifyproblem');
        }
        return '';
    }

    /**
     * Add all data to user object that is supposed to be sent
     * to TC client.
     *
     * @param \stdClass $client
     * @param \stdClass $user
     * @param bool $sso true if preparing user for get_sso_user function
     * @return void the $user param object is modified
     */
    public static function prepare_user_for_client($client, $user, $sso = false) {
        global $CFG, $DB;
        require_once("$CFG->libdir/filelib.php");

        if ($user->deleted != 0) {
            // Do not send all info about deleted users, they should not be used any more.
            $user->password = null;
            unset($user->secret);
            if ($sso) {
                $user->picture = '0';
                $user->pictures = array();
            } else {
                unset($user->picture);
            }
            $user->description = null;
            $user->descriptionformat = null;
            return;
        }

        // Password hashes are better kept on server, unless there is some special setup.
        if (!get_config('totara_connect', 'syncpasswords')) {
            $user->password = null;
        }

        // Secret is for this site only!
        unset($user->secret);

        if ($sso) {
            // For performance reasons some user data is synced only during sso login.
            $user->pictures = array();
            if ($user->picture) {
                $context = \context_user::instance($user->id);
                $fs = get_file_storage();
                foreach (array('f1', 'f2', 'f3') as $filename) {
                    if (!$file = $fs->get_file($context->id, 'user', 'icon', 0, '/', $filename.'.png')) {
                        if (!$file = $fs->get_file($context->id, 'user', 'icon', 0, '/', $filename . '.jpg')) {
                            continue;
                        }
                    }
                    $user->pictures[$file->get_filename()] = base64_encode($file->get_content());
                }
            }
        } else {
            unset($user->picture);
        }

        // Add description - keep links to original embedded images, but do not use filters.
        $usercontext = \context_user::instance($user->id);
        $user->description = file_rewrite_pluginfile_urls($user->description, 'pluginfile.php', $usercontext->id, 'user', 'profile', null);
        $user->description = format_text($user->description, $user->descriptionformat, array('filter' => false));
        $user->descriptionformat = FORMAT_HTML;

        if ($client->apiversion >= 2) {
            if (empty($client->syncprofilefields)) {
                $user->profile_fields = null;
            } else {
                $user->profile_fields = array();
                $sql = "SELECT f.shortname, f.datatype, d.data
                          FROM {user_info_data} d
                          JOIN {user_info_field} f ON f.id = d.fieldid
                         WHERE d.userid = :userid
                      ORDER BY f.shortname ASC";
                $fields = $DB->get_recordset_sql($sql, array('userid' => $user->id));
                foreach ($fields as $field) {
                    $user->profile_fields[] = $field;
                }
                $fields->close();
            }
            if (empty($client->syncjobs)) {
                $user->jobs = null;
            } else {
                $sql = "SELECT ja.*
                          FROM {job_assignment} ja
                         WHERE ja.userid = :userid
                      ORDER BY ja.sortorder ASC";
                $jobs = $DB->get_records_sql($sql, array('userid' => $user->id));
                // Get rid of all manager data, it is way too complex tree structure.
                foreach ($jobs as $job) {
                    unset($job->userid);
                    unset($job->managerjaid);
                    unset($job->managerjapath);
                    unset($job->tempmanagerjaid);
                    unset($job->tempmanagerexpirydate);
                    unset($job->appraiserid);
                }
                $user->jobs = array_values($jobs);
            }
        }
    }
}
