<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_core
 *
 * This file contains functions used by the registration pages
 */
defined('MOODLE_INTERNAL') || die();
define('SITE_REGISTRATION_EMAIL', 'registrations@totaralearning.com');

/**
 * Could this be a valid code?
 *
 * @param string $code
 * @return bool
 */
function is_valid_registration_code_format($code) {
    if (!is_string($code)) {
        return false;
    }
    if (strlen($code) !== 16 or !preg_match('/^[0-9a-f]+$/', $code)) {
        return false;
    }
    if (substr(sha1(substr($code, 0, 14)), 0, 2) !== substr($code, -2)) {
        return false;
    }
    return true;
}

/**
 * Should we redirect the current user to the registration page?
 * @return bool
 */
function is_registration_required() {
    global $CFG;

    // Only admins may view or update registration!
    if (!has_capability('moodle/site:config', context_system::instance())) {
        return false;
    }
    // Admins must visit the registration page at least once to make sure they saw the privacy warnings.
    if (!isset($CFG->registrationenabled)) {
        return true;
    }
    // Registrations can be disabled via config.php setting only.
    if (!$CFG->registrationenabled) {
        return false;
    }
    // Site type must be always specified, no exceptions.
    if (empty($CFG->sitetype)) {
        return true;
    }
    // Production mode requires valid registration code.
    if ($CFG->sitetype === 'production') {
        if (empty($CFG->registrationcode)) {
            return true;
        }
        if (!is_valid_registration_code_format($CFG->registrationcode)) {
            return true;
        }
    }
    // Ask for a new registration code when wwwroot changes.
    if (!isset($CFG->config_php_settings['registrationcode']) and !empty($CFG->registrationcode)) {
        if (isset($CFG->registrationcodewwwhash) and $CFG->registrationcodewwwhash !== sha1($CFG->wwwroot)) {
            return true;
        }
    }
    return false;
}

/**
 *  Collect information to be sent to register.totaralms.com
 *
 *  @return array Associative array of data to return
 */
function get_registration_data() {
    global $CFG, $SITE, $DB;
    require_once($CFG->libdir . '/badgeslib.php');

    $dbinfo = $DB->get_server_info();
    $db_version = $dbinfo['version']; // Versions are normalised on the server.

    $TOTARA = new stdClass();
    include($CFG->dirroot . '/version.php');

    $addons = array();
    $pluginman = core_plugin_manager::instance();
    foreach ($pluginman->get_plugins() as $plugins) {
        foreach ($plugins as $plugin) {
            /** @var core\plugininfo\base $plugin */
            if (!$plugin->is_standard()) {
                $addons[] = $plugin->component;
            }
        }
    }

    $data['siteidentifier'] = $CFG->siteidentifier;
    $data['wwwroot'] = $CFG->wwwroot;
    $data['siteshortname'] = $SITE->shortname;
    $data['sitefullname'] = $SITE->fullname;
    $data['orgname'] = $CFG->orgname;
    $data['techsupportphone'] = $CFG->techsupportphone;
    $data['techsupportemail'] = $CFG->techsupportemail;
    $data['moodlerelease'] = $CFG->release;
    $data['totaraversion'] = $TOTARA->version;
    $data['totarabuild'] = $TOTARA->build;
    $data['totararelease'] = $TOTARA->release;
    $data['phpversion'] = phpversion();
    $data['dbtype'] = $CFG->dbfamily . ' ' . $db_version;
    $data['webserversoftware'] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
    $data['usercount'] = $DB->count_records('user', array('deleted' => '0'));
    $data['coursecount'] = $DB->count_records_select('course', 'format <> ?', array('site'));
    $oneyearago = time() - 60*60*24*365;
    $threemonthsago = time() - 60*60*24*90;
    // See MDL-22481 for why currentlogin is used instead of lastlogin
    $data['activeusercount'] = $DB->count_records_select('user', "currentlogin > ?", array($oneyearago));
    $data['activeusercount3mth'] = $DB->count_records_select('user', "currentlogin > ?", array($threemonthsago));
    $data['usersessionscount'] = $DB->count_records_sql("SELECT COUNT('x') FROM {sessions} WHERE userid > 0");
    $data['badgesnumber'] = $DB->count_records_select('badge', 'status <> ' . BADGE_STATUS_ARCHIVED);
    $data['issuedbadgesnumber'] = $DB->count_records('badge_issued');
    $data['debugstatus'] = (isset($CFG->debug) ? $CFG->debug : DEBUG_NONE); // Support needs to know what errors users see.
    $data['lastcron'] = $DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}'); // Support needs to know if cron is configured and running.
    $data['addons'] = implode(',', $addons); // Support needs to know if there are plugins that might be incompatible with Totara.
    $data['installedlangs'] = implode(',', array_keys(get_string_manager()->get_list_of_translations())); // Language pack usage informs translation effort.
    if ($flavour = get_config('totara_flavour', 'currentflavour')) {
        $data['flavour'] = $flavour;
    }
    if (!empty($CFG->sitetype)) {
        $data['sitetype'] = $CFG->sitetype;
    }
    if (!empty($CFG->registrationcode)) {
        $data['registrationcode'] = $CFG->registrationcode;
    }

    $pluginmanager = \core_plugin_manager::instance();
    $componentdata = $pluginmanager->get_component_usage_data();
    $data['componentusage'] = json_encode($componentdata);

    return $data;
}

/**
 * Send registration data to totaralms.com
 *
 * @param array $data Associative array of data to send
 */
function send_registration_data($data) {
    global $CFG;
    require_once($CFG->libdir . '/filelib.php');

    set_config('registrationattempted', time());

    if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
        set_config('registered', time());
        return;
    }
    if (defined('BEHAT_UTIL') or defined('BEHAT_TEST') or defined('BEHAT_SITE_RUNNING')) {
        set_config('registered', time());
        return;
    }

    if (!isset($data['manualupdate'])) {
        // Must be a cron task if nto specified.
        $data['manualupdate'] = 0;
    }

    $ch = new curl();
    $options = array(
            'FOLLOWLOCATION' => true,
            'RETURNTRANSFER' => true, // RETURN THE CONTENTS OF THE CALL
            'SSL_VERIFYPEER' => true,
            'SSL_VERIFYHOST' => 2,
            'HEADER' => 0 // DO NOT RETURN HTTP HEADERS
    );

    // Send registration data directly via curl.
    $recdata = $ch->post('https://subscriptions.totara.community/register/report.php', $data, $options);
    if ($recdata === '') {
        // Legacy answer when 'sitetype' not specified.
        set_config('registered', time());
        return;
    }
    $recdata = @json_decode($recdata, true);
    if (!empty($recdata['status'])) {
        // New response.
        if ($recdata['status'] === 'success') {
            set_config('registered', time());
            return;
        }
    }

    // Fall back to email notification.
    $recdata = send_registration_data_email($data);
    if ($recdata === true) {
        set_config('registered', time());
    }

}

/**
 * Send registration data to totaralms.com using email
 *
 * @param array $data Associative array of data to send
 * @return bool Result of operation
 */
function send_registration_data_email($data) {
    global $CFG;

    $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
    $sdata = json_encode($data, $options);
    $encrypted = encrypt_data($sdata);

    $attachmentpath = make_temp_directory('register') . '/' . md5('register' . microtime(true));
    file_put_contents($attachmentpath, $encrypted);

    $attachmentfilename = 'site_registration.ttr';
    $subject = "[SITE REGISTRATION] Site: " . $data['sitefullname'];
    $message = get_string('siteregistrationemailbody', 'totara_core', $data['sitefullname']);
    $fromaddress = $CFG->noreplyaddress;

    $touser = \totara_core\totara_user::get_external_user(SITE_REGISTRATION_EMAIL);
    $emailed = email_to_user($touser, $fromaddress, $subject, $message, '', $attachmentpath, $attachmentfilename);

    if (!unlink($attachmentpath)) {
        mtrace(get_string('error:failedtoremovetempfile', 'totara_reportbuilder'));
    }

    return $emailed;
}
