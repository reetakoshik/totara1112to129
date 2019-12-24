<?php
/*
 * This file is part of Totara LMS
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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

define('PUBLIC_KEY_PATH', $CFG->dirroot . '/totara_public.pem');
define('TOTARA_SHOWFEATURE', 1);
define('TOTARA_HIDEFEATURE', 2);
define('TOTARA_DISABLEFEATURE', 3);

define('COHORT_ALERT_NONE', 0);
define('COHORT_ALERT_AFFECTED', 1);
define('COHORT_ALERT_ALL', 2);

define('COHORT_COL_STATUS_ACTIVE', 0);
define('COHORT_COL_STATUS_DRAFT_UNCHANGED', 10);
define('COHORT_COL_STATUS_DRAFT_CHANGED', 20);
define('COHORT_COL_STATUS_OBSOLETE', 30);

define('COHORT_BROKEN_RULE_NONE', 0);
define('COHORT_BROKEN_RULE_NOT_NOTIFIED', 1);
define('COHORT_BROKEN_RULE_NOTIFIED', 2);

define('COHORT_MEMBER_SELECTOR_MAX_ROWS', 1000);

define('COHORT_OPERATOR_TYPE_COHORT', 25);
define('COHORT_OPERATOR_TYPE_RULESET', 50);

define('COHORT_ASSN_ITEMTYPE_CATEGORY', 40);
define('COHORT_ASSN_ITEMTYPE_COURSE', 50);
define('COHORT_ASSN_ITEMTYPE_PROGRAM', 45);
define('COHORT_ASSN_ITEMTYPE_CERTIF', 55);
define('COHORT_ASSN_ITEMTYPE_MENU', 65);
define('COHORT_ASSN_ITEMTYPE_FEATURED_LINKS', 66);

// This should be extended when adding other tabs.
define ('COHORT_ASSN_VALUE_VISIBLE', 10);
define ('COHORT_ASSN_VALUE_ENROLLED', 30);
define ('COHORT_ASSN_VALUE_PERMITTED', 50);

// Visibility constants.
define('COHORT_VISIBLE_ENROLLED', 0);
define('COHORT_VISIBLE_AUDIENCE', 1);
define('COHORT_VISIBLE_ALL', 2);
define('COHORT_VISIBLE_NOUSERS', 3);

/**
 * Returns true or false depending on whether or not this course is visible to a user.
 *
 * @param int|stdClass $courseorid
 * @param int $userid
 * @return bool
 */
function totara_course_is_viewable($courseorid, $userid = null) {
    global $USER, $CFG;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (is_object($courseorid)) {
        $course = $courseorid;
    } else {
        $course = get_course($courseorid);
    }
    $coursecontext = context_course::instance($course->id);

    if (empty($CFG->audiencevisibility)) {
        // This check is moved from require_login().
        if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $userid)) {
            return false;
        }
    } else {
        require_once($CFG->dirroot . '/totara/cohort/lib.php');
        return check_access_audience_visibility('course', $course, $userid);
    }

    return true;
}

/**
 * Check visibility of the object passed and return if the element is hidden based on normal visibility setting or
 * audience visibility if enabled.
 *
 * @param $item The object as it comes from the database. It could be a course, a program or a certification.
 * Visibility properties should be present.
 * @return bool True if the item is hidden, false otherwise.
 * @throws coding_exception
 */
function totara_is_item_visibility_hidden($item) {
    global $CFG;

    if (!is_object($item) ||
        !property_exists($item, 'visible') ||
        !property_exists($item, 'audiencevisible')) {
        throw new coding_exception("Item passed is not an object or does not have visibility properties.");
    }


    $ishidden = (empty($CFG->audiencevisibility)) ? !$item->visible : $item->audiencevisible == COHORT_VISIBLE_NOUSERS;

    return $ishidden;
}

/**
 * This function loads the program settings that are available for the user
 *
 * @param navigation_node $navinode The navigation_node to add the settings to
 * @param context $context
 * @param bool $forceopen If set to true the course node will be forced open
 * @return navigation_node|false
 */
function totara_load_program_settings($navinode, $context, $forceopen = false) {
    global $CFG;

    $program = new program($context->instanceid);
    $exceptions = $program->get_exception_count();
    $exceptioncount = $exceptions ? $exceptions : 0;

    $adminnode = $navinode->add(get_string('programadministration', 'totara_program'), null, navigation_node::TYPE_COURSE, null, 'progadmin');
    if ($forceopen) {
        $adminnode->force_open();
    }
    // Standard tabs.
    if (has_capability('totara/program:viewprogram', $context)) {
        $url = new moodle_url('/totara/program/edit.php', array('id' => $program->id, 'action' => 'view'));
        $adminnode->add(get_string('overview', 'totara_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progoverview', new pix_icon('i/settings', get_string('overview', 'totara_program')));
    }
    if (has_capability('totara/program:configuredetails', $context)) {
        $url = new moodle_url('/totara/program/edit.php', array('id' => $program->id, 'action' => 'edit'));
        $adminnode->add(get_string('details', 'totara_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progdetails', new pix_icon('i/settings', get_string('details', 'totara_program')));
    }
    if (has_capability('totara/program:configurecontent', $context)) {
        $url = new moodle_url('/totara/program/edit_content.php', array('id' => $program->id));
        $adminnode->add(get_string('content', 'totara_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progcontent', new pix_icon('i/settings', get_string('content', 'totara_program')));
    }
    if (has_capability('totara/program:configureassignments', $context)) {
        $url = new moodle_url('/totara/program/edit_assignments.php', array('id' => $program->id));
        $adminnode->add(get_string('assignments', 'totara_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progassignments', new pix_icon('i/settings', get_string('assignments', 'totara_program')));
    }
    if (has_capability('totara/program:configuremessages', $context)) {
        $url = new moodle_url('/totara/program/edit_messages.php', array('id' => $program->id));
        $adminnode->add(get_string('messages', 'totara_program'), $url, navigation_node::TYPE_SETTING, null,
                    'progmessages', new pix_icon('i/settings', get_string('messages', 'totara_program')));
    }
    if (($exceptioncount > 0) && has_capability('totara/program:handleexceptions', $context)) {
        $url = new moodle_url('/totara/program/exceptions.php', array('id' => $program->id, 'page' => 0));
        $adminnode->add(get_string('exceptions', 'totara_program', $exceptioncount), $url, navigation_node::TYPE_SETTING, null,
                    'progexceptions', new pix_icon('i/settings', get_string('exceptionsreport', 'totara_program')));
    }
    if ($program->certifid && has_capability('totara/certification:configurecertification', $context)) {
        $url = new moodle_url('/totara/certification/edit_certification.php', array('id' => $program->id));
        $adminnode->add(get_string('certification', 'totara_certification'), $url, navigation_node::TYPE_SETTING, null,
                    'certification', new pix_icon('i/settings', get_string('certification', 'totara_certification')));
    }
    if (!empty($CFG->enableprogramcompletioneditor) && has_capability('totara/program:editcompletion', $context)) {
        // Certification/Program completion editor. Added Feb 2016 to 2.5.36, 2.6.29, 2.7.12, 2.9.4.
        $url = new moodle_url('/totara/program/completion.php', array('id' => $program->id));
        $adminnode->add(get_string('completion', 'totara_program'), $url, navigation_node::TYPE_SETTING, null,
            'certificationcompletion', new pix_icon('i/settings', get_string('completion', 'totara_program')));
    }
    // Roles and permissions.
    $usersnode = $adminnode->add(get_string('users'), null, navigation_node::TYPE_CONTAINER, null, 'users');
    // Override roles.
    if (has_capability('moodle/role:review', $context)) {
        $url = new moodle_url('/admin/roles/permissions.php', array('contextid' => $context->id));
        $permissionsnode = $usersnode->add(get_string('permissions', 'role'), $url, navigation_node::TYPE_SETTING, null, 'override');
    } else {
        $url = null;
        $permissionsnode = $usersnode->add(get_string('permissions', 'role'), $url, navigation_node::TYPE_CONTAINER, null, 'override');
        $trytrim = true;
    }

    // Add assign or override roles if allowed.
    if (is_siteadmin()) {
        if (has_capability('moodle/role:assign', $context)) {
            $url = new moodle_url('/admin/roles/assign.php', array('contextid' => $context->id));
            $permissionsnode->add(get_string('assignedroles', 'role'), $url, navigation_node::TYPE_SETTING, null,
                    'roles', new pix_icon('t/assignroles', get_string('assignedroles', 'role')));
        }
    }
    // Check role permissions.
    if (has_any_capability(array('moodle/role:assign', 'moodle/role:safeoverride', 'moodle/role:override', 'moodle/role:assign'), $context)) {
        $url = new moodle_url('/admin/roles/check.php', array('contextid' => $context->id));
        $permissionsnode->add(get_string('checkpermissions', 'role'), $url, navigation_node::TYPE_SETTING, null,
                    'permissions', new pix_icon('i/checkpermissions', get_string('checkpermissions', 'role')));
    }
    // Just in case nothing was actually added.
    if (isset($trytrim)) {
        $permissionsnode->trim_if_empty();
    }

    $usersnode->trim_if_empty();
    $adminnode->trim_if_empty();
}

/**
 * Returns the major Totara version of this site (which may be different from Moodle in older versions)
 *
 * Totara version numbers consist of three numbers (four for emergency releases)separated by a dot,
 * for example 1.9.11 or 2.0.2. The first two numbers, like 1.9 or 2.0, represent so
 * called major version. This function extracts the major version from
 * the $TOTARA->version variable defined in the main version.php.
 *
 * @return string|false the major version like '2.3', false if could not be determined
 */
function totara_major_version() {
    global $CFG;

    $release = null;
    require($CFG->dirroot.'/version.php');
    if (empty($TOTARA)) {
        return false;
    }

    // Starting in Totara 9 we do not return decimals here.
    if (preg_match('/^[0-9]+/', $TOTARA->version, $matches)) {
        return $matches[0];
    } else {
        return false;
    }
}

/**
 * Setup version information for installs and upgrades
 *
 * Moodle and Totara version numbers consist of three numbers (four for emergency releases)separated by a dot,
 * for example 1.9.11 or 2.0.2. The first two numbers, like 1.9 or 2.0, represent so
 * called major version. This function extracts the Moodle and Totara version info for use in checks and messages
 * @return stdClass containing moodle and totara version info
 */
function totara_version_info() {
    global $CFG;

    // Fetch version infos.
    $version = null;
    $release = null;
    $branch = null;
    $TOTARA = new stdClass();
    require("$CFG->dirroot/version.php");

    $a = new stdClass();
    $a->existingtotaraversion = false;
    $a->newtotaraversion = $TOTARA->version;
    $a->upgradecore = false;
    $a->newversion = "Totara {$TOTARA->release}";
    $a->oldversion = '';

    if (empty($CFG->version)) {
        // New install.
        return $a;
    }

    if (!empty($CFG->totara_release)) {
        // Existing Totara install.
        $a->oldversion = "Totara {$CFG->totara_release}";
    } else if (!empty($CFG->release)) {
        // Must be upgrade from Moodle.
        // Do not mention Moodle unless we are upgrading from it!
        $a->oldversion = "Moodle {$CFG->release}";
    }

    // Detect core downgrades.
    if ($version < $CFG->version) {
        if (!empty($CFG->totara_release)) {
            // Somebody is trying to downgrade Totara.
            $a->totaraupgradeerror = 'error:cannotupgradefromnewertotara';
            return $a;

        } else {
            // The original Moodle install is newer than Totara.
            // Hack oldversion because the lang string cannot be changed easily.
            $a->oldversion = $CFG->version;
            $a->totaraupgradeerror = 'error:cannotupgradefromnewermoodle';
            return $a;
        }
    }

    // Find out if we should upgrade the core.
    if ($version > $CFG->version) {
        // Moodle core version upgrade.
        $a->upgradecore = true;
    } else if ($a->newversion !== $a->oldversion) {
        // Different Totara release - build or version changed.
        $a->upgradecore = true;
    }

    return $a;
}

/**
 * gets a clean timezone array compatible with PHP DateTime, DateTimeZone etc functions
 * @param bool $assoc return a simple numerical index array or an associative array
 * @return array a clean timezone list that can be used safely
 */
function totara_get_clean_timezone_list($assoc=false) {
    $zones = array();
    foreach (DateTimeZone::listIdentifiers() as $zone) {
        if ($assoc) {
            $zones[$zone] = $zone;
        } else {
            $zones[] = $zone;
        }
    }
    return $zones;
}

/**
 * gets a list of bad timezones with the most likely proper named location zone
 * @return array a bad timezone list key=>bad value=>replacement
 */
function totara_get_bad_timezone_list() {
    $zones = array();
    //unsupported but common abbreviations
    $zones['EST'] = 'America/New_York';
    $zones['EDT'] = 'America/New_York';
    $zones['EST5EDT'] = 'America/New_York';
    $zones['CST'] = 'America/Chicago';
    $zones['CDT'] = 'America/Chicago';
    $zones['CST6CDT'] = 'America/Chicago';
    $zones['MST'] = 'America/Denver';
    $zones['MDT'] = 'America/Denver';
    $zones['MST7MDT'] = 'America/Denver';
    $zones['PST'] = 'America/Los_Angeles';
    $zones['PDT'] = 'America/Los_Angeles';
    $zones['PST8PDT'] = 'America/Los_Angeles';
    $zones['HST'] = 'Pacific/Honolulu';
    $zones['WET'] = 'Europe/London';
    $zones['GMT'] = 'Europe/London';
    $zones['EET'] = 'Europe/Kiev';
    $zones['FET'] = 'Europe/Minsk';
    $zones['CET'] = 'Europe/Amsterdam';
    //now the stupid Moodle offset zones. If an offset does not really exist then set to nearest
    $zones['-13.0'] = 'Pacific/Apia';
    $zones['-12.5'] = 'Pacific/Apia';
    $zones['-12.0'] = 'Pacific/Kwajalein';
    $zones['-11.5'] = 'Pacific/Niue';
    $zones['-11.0'] = 'Pacific/Midway';
    $zones['-10.5'] = 'Pacific/Rarotonga';
    $zones['-10.0'] = 'Pacific/Honolulu';
    $zones['-9.5'] = 'Pacific/Marquesas';
    $zones['-9.0'] = 'America/Anchorage';
    $zones['-8.5'] = 'America/Anchorage';
    $zones['-8.0'] = 'America/Los_Angeles';
    $zones['-7.5'] = 'America/Los_Angeles';
    $zones['-7.0'] = 'America/Denver';
    $zones['-6.5'] = 'America/Denver';
    $zones['-6.0'] = 'America/Chicago';
    $zones['-5.5'] = 'America/Chicago';
    $zones['-5.0'] = 'America/New_York';
    $zones['-4.5'] = 'America/Caracas';
    $zones['-4.0'] = 'America/Santiago';
    $zones['-3.5'] = 'America/St_Johns';
    $zones['-3.0'] = 'America/Sao_Paulo';
    $zones['-2.5'] = 'America/Sao_Paulo';
    $zones['-2.0'] = 'Atlantic/South_Georgia';
    $zones['-1.5'] = 'Atlantic/Cape_Verde';
    $zones['-1.0'] = 'Atlantic/Cape_Verde';
    $zones['-0.5'] = 'Europe/London';
    $zones['0.0'] = 'Europe/London';
    $zones['0.5'] = 'Europe/London';
    $zones['1.0'] = 'Europe/Amsterdam';
    $zones['1.5'] = 'Europe/Amsterdam';
    $zones['2.0'] = 'Europe/Helsinki';
    $zones['2.5'] = 'Europe/Minsk';
    $zones['3.0'] = 'Asia/Riyadh';
    $zones['3.5'] = 'Asia/Tehran';
    $zones['4.0'] = 'Asia/Dubai';
    $zones['4.5'] = 'Asia/Kabul';
    $zones['5.0'] = 'Asia/Karachi';
    $zones['5.5'] = 'Asia/Kolkata';
    $zones['6.0'] = 'Asia/Dhaka';
    $zones['6.5'] = 'Asia/Rangoon';
    $zones['7.0'] = 'Asia/Bangkok';
    $zones['7.5'] = 'Asia/Singapore';
    $zones['8.0'] = 'Australia/Perth';
    $zones['8.5'] = 'Australia/Perth';
    $zones['9.0'] = 'Asia/Tokyo';
    $zones['9.5'] = 'Australia/Adelaide';
    $zones['10.0'] = 'Australia/Sydney';
    $zones['10.5'] = 'Australia/Lord_Howe';
    $zones['11.0'] = 'Pacific/Guadalcanal';
    $zones['11.5'] = 'Pacific/Norfolk';
    $zones['12.0'] = 'Pacific/Auckland';
    $zones['12.5'] = 'Pacific/Auckland';
    $zones['13.0'] = 'Pacific/Apia';
    return $zones;
}
/**
 * gets a clean timezone attempting to compensate for some Moodle 'special' timezones
 * where the returned zone is compatible with PHP DateTime, DateTimeZone etc functions
 * @param string/float $tz either a location identifier string or, in some Moodle special cases, a number
 * @return string a clean timezone that can be used safely
 */
function totara_get_clean_timezone($tz=null) {
    global $CFG, $DB;

    $cleanzones = DateTimeZone::listIdentifiers();
    if (empty($tz)) {
        $tz = get_user_timezone();
    }

    //if already a good zone, return
    if (in_array($tz, $cleanzones, true)) {
        return $tz;
    }
    //for when all else fails
    $default = 'Europe/London';
    //try to handle UTC offsets, and numbers including '99' (server local time)
    //note: some old versions of moodle had GMT offsets stored as floats
    if (is_numeric($tz)) {
        if (intval($tz) == 99) {
            //check various config settings to try and resolve to something useful
            if (isset($CFG->forcetimezone) && $CFG->forcetimezone != 99) {
                $tz = $CFG->forcetimezone;
            } else if (isset($CFG->timezone) && $CFG->timezone != 99) {
                $tz = $CFG->timezone;
            }
        }
        if (intval($tz) == 99) {
            //no useful CFG settings, try a system call
            $tz = date_default_timezone_get();
            // From PHP 5.4 this may return UTC if no info is set in php.ini etc.
            $tz = ($tz == 'UTC') ? $default : $tz;
        }
        //do we have something useful yet?
        if (in_array($tz, $cleanzones, true)) {
            return $tz;
        }
        //check the bad timezone replacement list
        if (is_float($tz)) {
            $tz = number_format($tz, 1);
        }
        $badzones = totara_get_bad_timezone_list();
        //does this exist in our replacement list?
        if (in_array($tz, array_keys($badzones))) {
            return $badzones[$tz];
        }
    }
    //everything has failed, set to London
    return $default;
}

/**
 * checks the md5 of the zip file, grabbed from download.moodle.org,
 * against the md5 of the local language file from last update
 * @param string $lang
 * @param string $md5check
 * @return bool
 */
function local_is_installed_lang($lang, $md5check) {
    global $CFG;
    $md5file = $CFG->dataroot.'/lang/'.$lang.'/'.$lang.'.md5';
    if (file_exists($md5file)){
        return (file_get_contents($md5file) == $md5check);
    }
    return false;
}

/**
 * Runs on every upgrade to get the latest language packs from Totara language server
 *
 * Code mostly refactored from admin/tool/langimport/index.php
 *
 * @return  void
 */
function totara_upgrade_installed_languages() {
    global $CFG, $OUTPUT;
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->libdir.'/componentlib.class.php');
    core_php_time_limit::raise(0);
    $notice_ok = array();
    $notice_error = array();
    $installer = new lang_installer();

    // Do not download anything if there is only 'en' lang pack.
    $currentlangs = array_keys(get_string_manager()->get_list_of_translations(true));
    if (count($currentlangs) === 1 and in_array('en', $currentlangs)) {
        echo $OUTPUT->notification(get_string('nolangupdateneeded', 'tool_langimport'), 'notifysuccess');
        return;
    }

    if (!$availablelangs = $installer->get_remote_list_of_languages()) {
        echo $OUTPUT->notification(get_string('cannotdownloadtotaralanguageupdatelist', 'totara_core'), 'notifyproblem');
        return;
    }
    $md5array = array();    // (string)langcode => (string)md5
    foreach ($availablelangs as $alang) {
        $md5array[$alang[0]] = $alang[1];
    }

    // filter out unofficial packs
    $updateablelangs = array();
    foreach ($currentlangs as $clang) {
        if (!array_key_exists($clang, $md5array)) {
            $notice_ok[] = get_string('langpackupdateskipped', 'tool_langimport', $clang);
            continue;
        }
        $dest1 = $CFG->dataroot.'/lang/'.$clang;
        $dest2 = $CFG->dirroot.'/lang/'.$clang;

        if (file_exists($dest1.'/langconfig.php') || file_exists($dest2.'/langconfig.php')){
            $updateablelangs[] = $clang;
        }
    }

    // then filter out packs that have the same md5 key
    $neededlangs = array();   // all the packs that needs updating
    foreach ($updateablelangs as $ulang) {
        if (!local_is_installed_lang($ulang, $md5array[$ulang])) {
            $neededlangs[] = $ulang;
        }
    }

    make_temp_directory('');
    make_upload_directory('lang');

    // install all needed language packs
    $installer->set_queue($neededlangs);
    $results = $installer->run();
    $updated = false;    // any packs updated?
    foreach ($results as $langcode => $langstatus) {
        switch ($langstatus) {
        case lang_installer::RESULT_DOWNLOADERROR:
            $a       = new stdClass();
            $a->url  = $installer->lang_pack_url($langcode);
            $a->dest = $CFG->dataroot.'/lang';
            echo $OUTPUT->notification(get_string('remotedownloaderror', 'error', $a), 'notifyproblem');
            break;
        case lang_installer::RESULT_INSTALLED:
            $updated = true;
            $notice_ok[] = get_string('langpackinstalled', 'tool_langimport', $langcode);
            break;
        case lang_installer::RESULT_UPTODATE:
            $notice_ok[] = get_string('langpackuptodate', 'tool_langimport', $langcode);
            break;
        }
    }

    if ($updated) {
        $notice_ok[] = get_string('langupdatecomplete', 'tool_langimport');
    } else {
        $notice_ok[] = get_string('nolangupdateneeded', 'tool_langimport');
    }

    unset($installer);
    get_string_manager()->reset_caches();
    //display notifications
    $delimiter = (CLI_SCRIPT) ? "\n" : html_writer::empty_tag('br');
    if (!empty($notice_ok)) {
        $info = implode($delimiter, $notice_ok);
        echo $OUTPUT->notification($info, 'notifysuccess');
    }

    if (!empty($notice_error)) {
        $info = implode($delimiter, $notice_error);
        echo $OUTPUT->notification($info, 'notifyproblem');
    }
}

/**
 * Save a notification message for displaying on the subsequent page view
 *
 * Optionally supply a url for redirecting to before displaying the message
 * and/or an options array.
 *
 * Currently the options array only supports a 'class' entry for passing as
 * the second parameter to notification()
 *
 * @param string $message Message to display
 * @param string $redirect Url to redirect to (optional)
 * @param array $options An array of options to pass to totara_queue_append (optional)
 * @param bool $immediatesend If set to true the notification is immediately sent
 * @return void
 */
function totara_set_notification($message, $redirect = null, $options = array(), $immediatesend = true) {

    // Check options is an array
    if (!is_array($options)) {
        print_error('error:notificationsparamtypewrong', 'totara_core');
    }

    $data = [];
    $data['message'] = $message;
    $data['class'] = isset($options['class']) ? $options['class'] : null;
    // Add anything apart from 'classes' from the options object.
    $data['customdata'] = array_filter($options, function($key) {
        return !($key === 'class');
    }, ARRAY_FILTER_USE_KEY);

    // Add to notifications queue
    totara_queue_append('notifications', $data);

    // Redirect if requested
    if ($redirect !== null) {
        // Cancel redirect for AJAX scripts.
        if (is_ajax_request($_SERVER)) {
            if (!$immediatesend) {
                ajax_result(true);
            } else {
                ajax_result(true, totara_queue_shift('notifications'));
            }
        } else {
            redirect($redirect);
        }
        exit();
    }
}

/**
 * Return an array containing any notifications in $SESSION
 *
 * Should be called in the theme's header
 *
 * @return  array
 */
function totara_get_notifications() {

    $notifications = \core\notification::fetch();

    // Ensure notifications are in the format Totara expects from this function.
    return array_map('totara_convert_notification_to_legacy_array', $notifications);
}

/**
 * Add an item to a totara session queue
 *
 * @param   string  $key    Queue key
 * @param   mixed   $data   Data to add to queue
 * @return  void
 */
function totara_queue_append($key, $data) {
    global $SESSION;

    // Since TL-11584 / MDL-30811
    if ($key === 'notifications') {
        \core\notification::add_totara_legacy($data['message'], $data['class'], $data['customdata']);
        return;
    }

    if (!isset($SESSION->totara_queue)) {
        $SESSION->totara_queue = array();
    }

    if (!isset($SESSION->totara_queue[$key])) {
        $SESSION->totara_queue[$key] = array();
    }

    $SESSION->totara_queue[$key][] = $data;
}


/**
 * Return part or all of a totara session queue
 *
 * @param   string  $key    Queue key
 * @param   boolean $all    Flag to return entire session queue (optional)
 * @return  mixed
 */
function totara_queue_shift($key, $all = false) {
    global $SESSION;

    // Value to return if no items in queue
    $return = $all ? array() : null;

    // Check if an items in queue
    if (empty($SESSION->totara_queue) || empty($SESSION->totara_queue[$key])) {
        return $return;
    }

    // If returning all, grab all and reset queue
    if ($all) {
        $return = $SESSION->totara_queue[$key];
        $SESSION->totara_queue[$key] = array();
        return $return;
    }

    // Otherwise pop oldest item from queue
    return array_shift($SESSION->totara_queue[$key]);
}



/**
 *  Calls module renderer to return markup for displaying a progress bar for a user's course progress
 *
 * Optionally with a link to the user's profile if they have the correct permissions
 *
 * @deprecated since Totara 10.0
 * @access  public
 * @param   $userid     int
 * @param   $courseid   int
 * @param   $status     int     COMPLETION_STATUS_ constant
 * @return  string
 */
function totara_display_course_progress_icon($userid, $courseid, $status) {
    debugging('The function totara_display_course_progress_icon has been deprecated since 10.0. Please use totara_display_course_progress_bar.', DEBUG_DEVELOPER);
    return totara_display_course_progress_bar($userid, $courseid, $status);
}

/**
 *  Calls module renderer to return markup for displaying a progress bar for a user's course progress
 *
 * @param int $userid User id
 * @param int $courseid Course id
 * @param int $status COMPLETION_STATUS_ constant
 * @return string
 */
function totara_display_course_progress_bar($userid, $courseid, $status) {
    global $PAGE;

    /** @var totara_core_renderer $renderer */
    $renderer = $PAGE->get_renderer('totara_core');
    $content = $renderer->course_progress_bar($userid, $courseid, $status);
    return $content;
}

/**
 *  Adds the current icon and icon select dropdown to a moodle form
 *  replaces all the old totara/icon classes
 *
 * @access  public
 * @param   object $mform Reference to moodle form object.
 * @param   string $action Form action - add, edit or view.
 * @param   string $type Program, course or message icons.
 * @param   string $currenticon Value currently stored in db.
 * @param   int    $nojs 1 if Javascript is disabled.
 * @param   bool   $fieldset If true, include a 'header' around the icon picker.
 * @return  void
*/
function totara_add_icon_picker(&$mform, $action, $type, $currenticon='default', $nojs=0, $fieldset=true) {
    global $CFG;
    //get all icons of this type from core
    $replace = array('.png' => '', '_' => ' ', '-' => ' ');
    $iconhtml = totara_icon_picker_preview($type, $currenticon);

    if ($fieldset) {
        $mform->addElement('header', 'iconheader', get_string($type.'icon', 'totara_core'));
    }
    if ($nojs == 1) {
        $mform->addElement('static', 'currenticon', get_string('currenticon', 'totara_core'), $iconhtml);
        if ($action=='add' || $action=='edit') {
            $path = $CFG->dirroot . '/totara/core/pix/' . $type . 'icons';
            foreach (scandir($path) as $icon) {
                if ($icon == '.' || $icon == '..') { continue;}
                $iconfile = str_replace('.png', '', $icon);
                $iconname = strtr($icon, $replace);
                $icons[$iconfile] = ucwords($iconname);
            }
            $mform->addElement('select', 'icon', get_string('icon', 'totara_core'), $icons);
            $mform->setDefault('icon', $currenticon);
            $mform->setType('icon', PARAM_TEXT);
        }
    } else {
        $buttonhtml = '';
        if ($action=='add' || $action=='edit') {
            $buttonhtml = html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseicon', 'totara_program'), 'id' => 'show-icon-dialog'));
            $mform->addElement('hidden', 'icon');
            $mform->setType('icon', PARAM_TEXT);
        }
        $mform->addElement('static', 'currenticon', get_string('currenticon', 'totara_core'), $iconhtml . $buttonhtml);
    }
    if ($fieldset) {
        $mform->setExpanded('iconheader');
    }
}

/**
 *  Adds the current icon and icon select dropdown to a moodle form
 *  replaces all the old totara/icon classes
 *
 * @access  public
 * @param   object $mform Reference to moodle form object.
 * @param   string $action Form action - add, edit or view.
 * @param   string $type Program, course or message icons.
 * @param   string $currenticon Value currently stored in db.
 * @param   int    $nojs 1 if Javascript is disabled.
 * @param   mixed  $ind index to add to icon title
 * @return  array of created elements
 */
function totara_create_icon_picker(&$mform, $action, $type, $currenticon = '', $nojs = 0, $ind = '') {
    global $CFG;
    $return = array();
    if ($currenticon == '') {
        $currenticon = 'default';
    }
    // Get all icons of this type from core.
    $replace = array('.png' => '', '_' => ' ', '-' => ' ');
    $iconhtml = totara_icon_picker_preview($type, $currenticon, $ind);

    if ($nojs == 1) {
        $return['currenticon'.$ind] = $mform->createElement('static', 'currenticon',
                get_string('currenticon', 'totara_core'), $iconhtml);
        if ($action == 'add' || $action == 'edit') {
            $path = $CFG->dirroot . '/totara/core/pix/' . $type . 'icons';
            foreach (scandir($path) as $icon) {
                if ($icon == '.' || $icon == '..') {
                    continue;
                }
                $iconfile = str_replace('.png', '', $icon);
                $iconname = strtr($icon, $replace);
                $icons[$iconfile] = ucwords($iconname);
            }
            $return['icon'.$ind] = $mform->createElement('select', 'icon',
                    get_string('icon', 'totara_core'), $icons);
            $mform->setDefault('icon', $currenticon);
        }
    } else {
        $linkhtml = '';
        if ($action == 'add' || $action == 'edit') {
            $linkhtml = html_writer::tag('a', get_string('chooseicon', 'totara_program'),
                    array('href' => '#', 'data-ind'=> $ind, 'id' => 'show-icon-dialog' . $ind,
                          'class' => 'show-icon-dialog'));
            $return['icon'.$ind] = $mform->createElement('hidden', 'icon', '',
                    array('id'=>'icon' . $ind));
        }
        $return['currenticon' . $ind] = $mform->createElement('static', 'currenticon', '',
                get_string('icon', 'totara_program') . $iconhtml . $linkhtml);
    }
    return $return;
}

/**
 * Render preview of icon
 *
 * @param string $type type of icon (course or program)
 * @param string $currenticon current icon
 * @param string $ind index of icon on page (when several icons previewed)
 * @param string $alt alternative text for icon
 * @return string HTML
 */
function totara_icon_picker_preview($type, $currenticon, $ind = '', $alt = '') {
    list($src, $alt) = totara_icon_url_and_alt($type, $currenticon, $alt);

    $iconhtml = html_writer::empty_tag('img', array('src' => $src, 'id' => 'icon_preview' . $ind,
            'class' => "course_icon", 'alt' => $alt, 'title' => $alt));

    return $iconhtml;
}

/**
 * Get the url and alternate text of icon.
 *
 * @param string $type type of icon (course or program)
 * @param string $icon icon key (name for built-in icon or hash for user image)
 * @param string $alt alternative text for icon (overrides calculated alt text)
 * @return string HTML
 */
function totara_icon_url_and_alt($type, $icon, $alt = '') {
    global $OUTPUT, $DB, $PAGE;

    $component = 'totara_core';
    $src = '';

    // See if icon is a custom icon.
    if ($customicon = $DB->get_record('files', array('pathnamehash' => $icon))) {
        $fs = get_file_storage();
        $context = context_system::instance();
        if ($file = $fs->get_file($context->id, $component, $type, $customicon->itemid, '/', $customicon->filename)) {
            $icon = $customicon->filename;
            $src = moodle_url::make_pluginfile_url($file->get_contextid(), $component,
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $customicon->filename, true);
        }
    }

    if (empty($src)) {
        $iconpath = $type . 'icons/';
        $imagelocation = $PAGE->theme->resolve_image_location($iconpath. $icon, $component);
        if (empty($icon) || empty($imagelocation)) {
            $icon = 'default';
        }
        $src = $OUTPUT->pix_url('/' . $iconpath . $icon, $component);
    }

    $replace = array('.png' => '', '_' => ' ', '-' => ' ');
    $alt = ($alt != '') ? $alt : ucwords(strtr($icon, $replace));

    return array($src, $alt);
}

/**
* print out the Totara My Team nav section
*/
function totara_print_my_team_nav() {
    global $CFG, $USER, $PAGE;

    $managerroleid = $CFG->managerroleid;

    // return users with this user as manager
    $staff = \totara_job\job_assignment::get_staff_userids($USER->id);
    $teammembers = count($staff);

    //call renderer
    $renderer = $PAGE->get_renderer('totara_core');
    $content = $renderer->my_team_nav($teammembers);
    return $content;
}

/**
* print out the table of visible reports
*/
function totara_print_report_manager() {
    global $CFG, $USER, $PAGE;
    require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

    $context = context_system::instance();
    $canedit = has_capability('totara/reportbuilder:managereports',$context);

    $reportbuilder_permittedreports = get_my_reports_list();

    if (count($reportbuilder_permittedreports) > 0) {
        $renderer = $PAGE->get_renderer('totara_core');
        $returnstr = $renderer->report_list($reportbuilder_permittedreports, $canedit);
    } else {
        $returnstr = get_string('nouserreports', 'totara_reportbuilder');
    }
    return $returnstr;
}


function get_my_reports_list() {
    $reportbuilder_permittedreports = reportbuilder::get_user_permitted_reports();

    foreach ($reportbuilder_permittedreports as $key => $reportrecord) {
        if ($reportrecord->embedded) {
            try {
                new reportbuilder($reportrecord->id);
            } catch (moodle_exception $e) {
                if ($e->errorcode == "nopermission") {
                    // The report creation failed, almost certainly due to a failed is_capable check in an embedded report.
                    // In this case, we just skip it.
                    unset($reportbuilder_permittedreports[$key]);
                } else {
                    throw ($e);
                }
            }
        }
    }

    return $reportbuilder_permittedreports;
}


/**
* Returns markup for displaying saved scheduled reports
*
* Optionally without the options column and add/delete form
* Optionally with an additional sql WHERE clause
* @access public
* @param boolean $showoptions SHow icons to edit and delete scheduled reports.
* @param boolean $showaddform Show a simple form to allow reports to be scheduled.
* @param array $sqlclause In the form array($where, $params)
*/
function totara_print_scheduled_reports($showoptions=true, $showaddform=true, $sqlclause=array()) {
    global $CFG, $PAGE;

    require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
    require_once($CFG->dirroot . '/totara/core/lib/scheduler.php');
    require_once($CFG->dirroot . '/calendar/lib.php');
    require_once($CFG->dirroot . '/totara/reportbuilder/scheduled_forms.php');

    $scheduledreports = get_my_scheduled_reports_list();

    // If we want the form generate the content so it can be used into the templated.
    if ($showaddform) {
        $mform = new scheduled_reports_add_form($CFG->wwwroot . '/totara/reportbuilder/scheduled.php', array());
        $addform = $mform->render();
    } else {
        $addform = '';
    }

    $renderer = $PAGE->get_renderer('totara_core');
    echo $renderer->scheduled_reports($scheduledreports, $showoptions, $addform);
}


/**
 * Build a list of scheduled reports for display in a table.
 *
 * @param array $sqlclause In the form array($where, $params)
 * @return array
 * @throws coding_exception
 */
function get_my_scheduled_reports_list($sqlclause=array()) {
    global $DB, $REPORT_BUILDER_EXPORT_FILESYSTEM_OPTIONS, $USER;

    $myreports = reportbuilder::get_user_permitted_reports();

    $sql = "SELECT rbs.*, rb.fullname
            FROM {report_builder_schedule} rbs
            JOIN {report_builder} rb
            ON rbs.reportid=rb.id
            WHERE rbs.userid=?";

    $parameters = array($USER->id);

    if (!empty($sqlclause)) {
        list($conditions, $params) = $sqlclause;
        $parameters = array_merge($parameters, $params);
        $sql .= " AND " . $conditions;
    }
    //note from M2.0 these functions return an empty array, not false
    $scheduledreports = $DB->get_records_sql($sql, $parameters);
    //pre-process before sending to renderer
    foreach ($scheduledreports as $sched) {
        if (!isset($myreports[$sched->reportid])) {
            // Cannot access this report.
            unset($scheduledreports[$sched->id]);
            continue;
        }
        //data column
        if ($sched->savedsearchid != 0){
            $sched->data = $DB->get_field('report_builder_saved', 'name', array('id' => $sched->savedsearchid));
        }
        else {
            $sched->data = get_string('alldata', 'totara_reportbuilder');
        }
        // Format column.
        $format = \totara_core\tabexport_writer::normalise_format($sched->format);
        $allformats = \totara_core\tabexport_writer::get_export_classes();
        if (isset($allformats[$format])) {
            $classname = $allformats[$format];
            $sched->format = $classname::get_export_option_name();
        } else {
            $sched->format = get_string('error');
        }
        // Export column.
        $key = array_search($sched->exporttofilesystem, $REPORT_BUILDER_EXPORT_FILESYSTEM_OPTIONS);
        $sched->exporttofilesystem = get_string($key, 'totara_reportbuilder');
        //schedule column
        if (isset($sched->frequency) && isset($sched->schedule)){
            $schedule = new scheduler($sched, array('nextevent' => 'nextreport'));
            $formatted = $schedule->get_formatted();
            if ($next = $schedule->get_scheduled_time()) {
                if ($next < time()) {
                    // As soon as possible.
                    $next = time();
                }
                $formatted .= '<br />' . userdate($next);
            }
        } else {
            $formatted = get_string('schedulenotset', 'totara_reportbuilder');
        }
        $sched->schedule = $formatted;
    }

    return $scheduledreports;
}

function totara_print_my_courses() {
    global $CFG, $OUTPUT, $PAGE;

    // Report builder lib is required for the embedded report.
    require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

    echo $OUTPUT->heading(get_string('mycurrentprogress', 'totara_core'));

    $sid = optional_param('sid', '0', PARAM_INT);
    $debug  = optional_param('debug', 0, PARAM_INT);

    if (!$report = reportbuilder_get_embedded_report('course_progress', array(), false, $sid)) {
        print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
    }

    $report->include_js();

    /** @var totara_reportbuilder_renderer $renderer */
    $renderer = $PAGE->get_renderer('totara_reportbuilder');
    // This must be done after the header and before any other use of the report.
    list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);
    echo $debughtml;
    echo $reporthtml;
}


/**
 * Check if a user is a manager of another user
 *
 * If managerid is not set, uses the current user
 *
 * @deprecated since 9.0
 * @param int $userid       ID of user
 * @param int $managerid    ID of a potential manager to check (optional)
 * @param int $postype      Type of the position to check (POSITION_TYPE_* constant). Defaults to all positions (optional)
 * @return boolean true if user $userid is managed by user $managerid
 **/
function totara_is_manager($userid, $managerid = null, $postype = null) {
    global $USER;

    debugging('The function totara_is_manager has been deprecated since 9.0. Please use \totara_job\job_assignment::is_managing instead.', DEBUG_DEVELOPER);

    if (empty($managerid)) {
        $managerid = $USER->id;
    }

    $staffjaid = null;

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
        // If postype has been included then we'll look according to sortorder. We're only getting job assignments
        // where there's a manager.
        $jobassignments = \totara_job\job_assignment::get_all($userid, true);
        foreach($jobassignments as $jobassignment) {
            if ($jobassignment->sortorder == $postype) {
                $staffjaid = $jobassignment->id;
                break;
            }
        }

        if (empty($staffjaid)) {
            // None found with that $postype, meaning there is no manager at all for that postype.
            return false;
        }
    }

    return \totara_job\job_assignment::is_managing($managerid, $userid, $staffjaid);
}

/**
 * Returns the staff of the specified user
 *
 * @deprecated since 9.0
 * @param int $managerid ID of a user to get the staff of, If $managerid is not set, returns staff of current user
 * @param mixed $postype Type of the position to check (POSITION_TYPE_* constant). Defaults to primary position (optional)
 * @param bool $sort optional ordering by lastname, firstname
 * @return array|bool Array of userids of staff who are managed by user $userid , or false if none
 **/
function totara_get_staff($managerid = null, $postype = null, $sort = false) {
    global $USER;

    debugging('totara_get_staff has been deprecated since 9.0. Use \totara_job\job_assignment::get_staff_userids instead.', DEBUG_DEVELOPER);

    if ($sort) {
        debugging('Warning: The $sort argument in deprecated function totara_get_staff is no longer valid. Returned ids will not be sorted according to last name and first name.',
            DEBUG_DEVELOPER);
    }

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    if (empty($managerid)) {
        $managerid = $USER->id;
    }

    $jobassignments = \totara_job\job_assignment::get_all($managerid);
    $result = false;
    foreach ($jobassignments as $jobassignment) {
        if (!empty($postype) && $jobassignment->sortorder != $postype) {
            // If $postype was specified, closest to backwards-compatibility we can achieve is to base it on sortorder.
            continue;
        }

        $result = \totara_job\job_assignment::get_staff_userids($managerid, $jobassignment->id, true);
        break;
    }

    if (empty($result)) {
        return false;
    } else {
        return $result;
    }
}

/**
 * Find out a user's manager.
 *
 * @deprecated since 9.0
 * @param int $userid Id of the user whose manager we want
 * @param int $postype Type of the position we want the manager for (POSITION_TYPE_* constant). Defaults to primary position (i.e. sortorder=1).
 * @param boolean $skiptemp Skip check and return of temporary manager
 * @param boolean $skipreal Skip check and return of real manager
 * @return mixed False if no manager. Manager user object from mdl_user if the user has a manager.
 */
function totara_get_manager($userid, $postype = null, $skiptemp = false, $skipreal = false) {
    global $CFG, $DB;

    debugging('totara_get_manager has been deprecated since 9.0. You will need to use methods from \totara_job\job_assignment instead.', DEBUG_DEVELOPER);

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    $jobassignments = \totara_job\job_assignment::get_all($userid);

    $managerid = false;
    foreach ($jobassignments as $jobassignment) {
        if (!empty($postype) && $jobassignment->sortorder != $postype) {
            // If $postype was specified, closest to backwards-compatibility we can achieve is to base it on sortorder.
            continue;
        }
        if (!$skiptemp && $jobassignment->tempmanagerjaid && !empty($CFG->enabletempmanagers)) {
            $managerid = $jobassignment->tempmanagerid;
            break;
        }
        if (!$skipreal && $jobassignment->managerjaid) {
            $managerid = $jobassignment->managerid;
            break;
        }
    }

    if ($managerid) {
        return $DB->get_record('user', array('id' => $managerid));
    } else {
        return false;
    }
}

/**
 * Find the manager of the user's 'first' job.
 *
 * @deprecated since version 9.0
 * @param int|bool $userid Id of the user whose manager we want
 * @return mixed False if no manager. Manager user object from mdl_user if the user has a manager.
 */
function totara_get_most_primary_manager($userid = false) {
    global $DB, $USER;

    debugging("totara_get_most_primary_manager is deprecated. Use \\totara_job\\job_assignment methods instead.", DEBUG_DEVELOPER);

    if ($userid === false) {
        $userid = $USER->id;
    }

    $managers = \totara_job\job_assignment::get_all_manager_userids($userid);
    if (!empty($managers)) {
        $managerid = reset($managers);
        return $DB->get_record('user', array('id' => $managerid));
    }
    return false;
}

/**
 * Update/set a temp manager for the specified user
 *
 * @deprecated since 9.0
 * @param int $userid Id of user to set temp manager for
 * @param int $managerid Id of temp manager to be assigned to user.
 * @param int $expiry Temp manager expiry epoch timestamp
 */
function totara_update_temporary_manager($userid, $managerid, $expiry) {
    global $CFG, $DB, $USER;

    debugging('totara_update_temporary_manager is deprecated. Use \totara_job\job_assignment::update instead.', DEBUG_DEVELOPER);

    if (!$user = $DB->get_record('user', array('id' => $userid))) {
        return false;
    }

    // With multiple job assignments, we'll only consider the first job assignment for this function.
    $jobassignment = \totara_job\job_assignment::get_first($userid, false);
    if (empty($jobassignment)) {
        return false;
    }

    if (empty($jobassignment->managerid)) {
        $realmanager = false;
    } else {
        $realmanager = $DB->get_record('user', array('id' => $jobassignment->managerid));
    }

    if (empty($jobassignment->tempmanagerid)) {
        $oldtempmanager = false;
    } else {
        $oldtempmanager = $DB->get_record('user', array('id' => $jobassignment->tempmanagerid));
    }

    if (!$newtempmanager = $DB->get_record('user', array('id' => $managerid))) {
        return false;
    }

    // Set up messaging.
    require_once($CFG->dirroot.'/totara/message/messagelib.php');
    $msg = new stdClass();
    $msg->userfrom = $USER;
    $msg->msgstatus = TOTARA_MSG_STATUS_OK;
    $msg->contexturl = $CFG->wwwroot.'/totara/job/jobassignment.php?jobassignmentid='.$this->id;
    $msg->contexturlname = get_string('xpositions', 'totara_core', fullname($user));
    $msgparams = (object)array('staffmember' => fullname($user), 'tempmanager' => fullname($newtempmanager),
        'expirytime' => userdate($expiry, get_string('strftimedatefulllong', 'langconfig')), 'url' => $msg->contexturl);

    if (!empty($oldtempmanager) && $newtempmanager->id == $oldtempmanager->tempmanagerid) {
        if ($jobassignment->tempmanagerexpirydate == $expiry) {
            // Nothing to do here.
            return true;
        } else {
            // Update expiry time.
            $jobassignment->update(array('tempmanagerexpirydate' => $expiry));

            // Expiry change notifications.

            // Notify staff member.
            $msg->userto = $user;
            $msg->subject = get_string('tempmanagerexpiryupdatemsgstaffsubject', 'totara_core', $msgparams);
            $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgstaff', 'totara_core', $msgparams);
            $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgstaff', 'totara_core', $msgparams);
            tm_alert_send($msg);

            // Notify real manager.
            if (!empty($realmanager)) {
                $msg->userto = $realmanager;
                $msg->subject = get_string('tempmanagerexpiryupdatemsgmgrsubject', 'totara_core', $msgparams);
                $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgmgr', 'totara_core', $msgparams);
                $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgmgr', 'totara_core', $msgparams);
                $msg->roleid = $CFG->managerroleid;
                tm_alert_send($msg);
            }

            // Notify temp manager.
            $msg->userto = $newtempmanager;
            $msg->subject = get_string('tempmanagerexpiryupdatemsgtmpmgrsubject', 'totara_core', $msgparams);
            $msg->fullmessage = get_string('tempmanagerexpiryupdatemsgtmpmgr', 'totara_core', $msgparams);
            $msg->fullmessagehtml = get_string('tempmanagerexpiryupdatemsgtmpmgr', 'totara_core', $msgparams);
            $msg->roleid = $CFG->managerroleid;
            tm_alert_send($msg);

            return true;
        }
    }

    $newtempmanagerja = \totara_job\job_assignment::get_first($newtempmanager->id);
    if (empty($newtempmanagerja)) {
        $newtempmanagerja = \totara_job\job_assignment::create_default($newtempmanager->id);
    }
    // Assign/update temp manager role assignment.
    $jobassignment->update(array('tempmanagerjaid' => $newtempmanagerja->id, 'tempmanagerexpirydate' => $expiry));

    // Send assignment notifications.

    // Notify staff member.
    $msg->userto = $user;
    $msg->subject = get_string('tempmanagerassignmsgstaffsubject', 'totara_core', $msgparams);
    $msg->fullmessage = get_string('tempmanagerassignmsgstaff', 'totara_core', $msgparams);
    $msg->fullmessagehtml = get_string('tempmanagerassignmsgstaff', 'totara_core', $msgparams);
    tm_alert_send($msg);

    // Notify real manager.
    if (!empty($realmanager)) {
        $msg->userto = $realmanager;
        $msg->subject = get_string('tempmanagerassignmsgmgrsubject', 'totara_core', $msgparams);
        $msg->fullmessage = get_string('tempmanagerassignmsgmgr', 'totara_core', $msgparams);
        $msg->fullmessagehtml = get_string('tempmanagerassignmsgmgr', 'totara_core', $msgparams);
        $msg->roleid = $CFG->managerroleid;
        tm_alert_send($msg);
    }

    // Notify temp manager.
    $msg->userto = $newtempmanager;
    $msg->subject = get_string('tempmanagerassignmsgtmpmgrsubject', 'totara_core', $msgparams);
    $msg->fullmessage = get_string('tempmanagerassignmsgtmpmgr', 'totara_core', $msgparams);
    $msg->fullmessagehtml = get_string('tempmanagerassignmsgtmpmgr', 'totara_core', $msgparams);
    $msg->roleid = $CFG->managerroleid;
    tm_alert_send($msg);
}

/**
 * Unassign the temporary manager of the specified user
 *
 * @deprecated since 9.0
 * @param int $userid
 * @return boolean true on success
 * @throws Exception
 */
function totara_unassign_temporary_manager($userid) {
    global $DB, $CFG;

    debugging('totara_unassign_temporary_manager is deprecated. Use \totara_job\job_assignment::update instead.', DEBUG_DEVELOPER);

    // We'll use first job assignment only.
    $jobassignment = \totara_job\job_assignment::get_first($userid, false);
    if (empty($jobassignment)) {
        return false;
    }

    if (empty($jobassignment->tempmanagerid)) {
        // Nothing to do.
        return true;
    }
    $jobassignment->update(array('tempmanagerjaid' => null, 'tempmanagerexpirydate' => null));

    return true;
}

/**
 * Find out a user's teamleader (manager's manager).
 *
 * @deprecated since 9.0
 * @param int $userid Id of the user whose teamleader we want
 * @param int $postype Type of the position we want the teamleader for (POSITION_TYPE_* constant).  Defaults to primary position (i.e. sortorder=1).
 * @return mixed False if no teamleader. Teamleader user object from mdl_user if the user has a teamleader.
 */
function totara_get_teamleader($userid, $postype = null) {

    debugging('totara_get_teamleader is deprecated. Use \totara_job\job_assignment methods instead.', DEBUG_DEVELOPER);

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, manager assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    $manager = totara_get_manager($userid, $postype);

    if (empty($manager)) {
        return false;
    } else {
        return totara_get_manager($manager->id, $postype);
    }
}


/**
 * Find out a user's appraiser.
 *
 * @deprecated since 9.0
 * @param int $userid Id of the user whose appraiser we want
 * @param int $postype Type of the position we want the appraiser for (POSITION_TYPE_* constant).
 *                     Defaults to primary position(optional)
 * @return mixed False if no appraiser. Appraiser user object from mdl_user if the user has a appraiser.
 */
function totara_get_appraiser($userid, $postype = null) {
    global $DB;

    debugging('totara_get_appraiser is deprecated. Use \totara_job\job_assignment methods instead.', DEBUG_DEVELOPER);

    if (!empty($postype)) {
        if (!in_array($postype, array(POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY))) {
            // Position type not recognised. Or if it was for an aspiration position, appraiser assignments were not possible.
            return false;
        }
    } else {
        $postype = POSITION_TYPE_PRIMARY;
    }

    $jobassignments = \totara_job\job_assignment::get_all($userid);

    $appraiserid = false;
    foreach ($jobassignments as $jobassignment) {
        if (!empty($postype) && $jobassignment->sortorder != $postype) {
            // If $postype was specified, closest to backwards-compatibility we can achieve is to base it on sortorder.
            continue;
        }
        $appraiserid = $jobassignment->appraiserid;
    }

    if ($appraiserid) {
        return $DB->get_record('user', array('id' => $appraiserid));
    } else {
        return false;
    }
}


/**
 * Returns unix timestamp from a date string depending on the date format
 * for the current $USER or server timezone.
 *
 * Note: timezone info in $format is not supported
 *
 * @param string $format e.g. "d/m/Y" - see date_parse_from_format for supported formats
 * @param string $date a date to be converted e.g. "12/06/12"
 * @param bool $servertimezone
 * @param string $forcetimezone force one specific timezone, $servertimezone is ignored if specified
 * @return int unix timestamp (0 if fails to parse)
 */
function totara_date_parse_from_format($format, $date, $servertimezone = false, $forcetimezone = null) {
    $dateArray = date_parse_from_format($format, $date);
    if (!is_array($dateArray) or !empty($dateArray['error_count'])) {
        return 0;
    }
    if ($dateArray['is_localtime']) {
        // Not timezone support, sorry.
        return 0;
    }

    if (!is_null($forcetimezone)) {
        $tzobj = new DateTimeZone(core_date::normalise_timezone($forcetimezone));
    } else if ($servertimezone) {
        $tzobj = core_date::get_server_timezone_object();
    } else {
        $tzobj = core_date::get_user_timezone_object();
    }

    $date = new DateTime('now', $tzobj);
    $date->setDate($dateArray['year'], $dateArray['month'], $dateArray['day']);
    $date->setTime($dateArray['hour'], $dateArray['minute'], $dateArray['second']);

    return $date->getTimestamp();
}


/**
 * Check if the HTTP request was of type POST
 *
 * This function is useful as sometimes the $_POST array can be empty
 * if it's size exceeded post_max_size
 *
 * @access  public
 * @return  boolean
 */
function totara_is_post_request() {
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
}


/**
 * Download stored errorlog as a zip
 *
 * @deprecated since Totara 11
 */
function totara_errors_download() {
    global $DB;

    debugging(__FUNCTION__ . ' was deprecated in Totara 11 and will be removed in a future version. There is no alternative.', DEBUG_DEVELOPER);

    // Load errors from database
    $errors = $DB->get_records('errorlog');
    if (!$errors) {
        $errors = array();
    }

    // Format them nicely as strings
    $content = '';
    foreach ($errors as $error) {
        $error = (array) $error;
        foreach ($error as $key => $value) {
            $error[$key] = str_replace(array("\t", "\n"), ' ', $value);
        }

        $content .= implode("\t", $error);
        $content .= "\n";
    }

    send_temp_file($content, 'totara-error.log', true);
}


/**
 * Generate markup for search box
 *
 * Gives ability to specify courses, programs and/or categories in the results
 * as well as the ability to limit by category
 *
 * @access  public
 * @param   string  $value      Search value
 * @param   bool    $return     Return results (always true in M2.0, param left until all calls elsewhere cleaned up!)
 * @param   string  $type       Type of results ('all', 'course', 'program', 'certification', 'category')
 * @param   int     $category   Parent category (0 means all, -1 means global search)
 * @return  string|void
 */
function print_totara_search($value = '', $return = true, $type = 'all', $category = -1) {

    global $CFG, $DB, $PAGE;
    $return = ($return) ? $return : true;

    static $count = 0;

    $count++;

    $id = 'totarasearch';

    if ($count > 1) {
        $id .= '_'.$count;
    }

    $action = "{$CFG->wwwroot}/course/search.php";

    // If searching in a category, indicate which category
    if ($category > 0) {
        // Get category name
        $categoryname = $DB->get_field('course_categories', 'name', array('id' => $category));
        if ($categoryname) {
            $strsearch = get_string('searchx', 'totara_core', $categoryname);
        } else {
            $strsearch = get_string('search');
        }
    } else {
        if ($type == 'course') {
            $strsearch = get_string('searchallcourses', 'totara_coursecatalog');
        } elseif ($type == 'program') {
            $strsearch = get_string('searchallprograms', 'totara_coursecatalog');
        } elseif ($type == 'certification') {
            $strsearch = get_string('searchallcertifications', 'totara_coursecatalog');
        } elseif ($type == 'category') {
            $strsearch = get_string('searchallcategories', 'totara_coursecatalog');
        } else {
            $strsearch = get_string('search');
            $type = '';
        }
    }

    $hiddenfields = array(
        'viewtype' => $type,
        'category' => $category,
    );
    $formid = 'searchtotara';
    $inputid = 'navsearchbox';
    $value = s($value, true);
    $strsearch = s($strsearch);

    $renderer = $PAGE->get_renderer('totara_core');
    $output = $renderer->print_totara_search($action, $hiddenfields, $strsearch, $value, $formid, $inputid);

    return $output;
}


/**
 * Displays a generic editing on/off button suitable for any page
 *
 * @param string $settingname Name of the $USER property used to determine if the button should display on or off
 * @param array $params Associative array of additional parameters to pass (optional)
 *
 * @return string HTML to display the button
 */
function totara_print_edit_button($settingname, $params = array()) {
    global $CFG, $USER, $OUTPUT;

    $currentstate = isset($USER->$settingname) ?
        $USER->$settingname : null;

    // Work out the appropriate action.
    if (empty($currentstate)) {
        $label = get_string('turneditingon');
        $edit = 'on';
    } else {
        $label = get_string('turneditingoff');
        $edit = 'off';
    }

    // Generate the button HTML.
    $params[$settingname] = $edit;
    return $OUTPUT->single_button(new moodle_url(qualified_me(), $params), $label, 'get');
}

/**
 * Returns the SQL to be used in order to CAST one column to CHAR
 *
 * @param string fieldname the name of the field to be casted
 * @return string the piece of SQL code to be used in your statement.
 *
 * @deprecated since Totara 10.0
 */
function sql_cast2char($fieldname) {
    global $DB;
    debugging('sql_cast2char() is deprecated. Use DB->sql_cast_2char() instead.', DEBUG_DEVELOPER);
    return $DB->sql_cast_2char($fieldname);
}


/**
 * Returns the SQL to be used in order to CAST one column to FLOAT
 *
 * @param string fieldname the name of the field to be casted
 * @return string the piece of SQL code to be used in your statement.
 *
 * @deprecated since Totara 10.0
 */
function sql_cast2float($fieldname) {
    global $DB;
    debugging('sql_cast2float() is deprecated. Use DB->sql_cast_char2float() instead.', DEBUG_DEVELOPER);
    return $DB->sql_cast_char2float($fieldname);
}

/**
 * Returns as case sensitive field name.
 *
 * @param string $field table field name
 * @return string SQL code fragment
 */
function sql_collation($field) {
    global $DB;

    $namefield = $field;
    switch ($DB->get_dbfamily()) {
        case('sqlsrv'):
        case('mssql'):
            $namefield  = "{$field} COLLATE " . mssql_get_collation(). " AS {$field}";
            break;
        case('mysql'):
            $namefield = "(BINARY {$field}) AS {$field}";
            break;
        case('postgres'):
            $namefield = $field;
            break;
    }

    return $namefield;
}

/**
 * Returns 'collation' part of a query.
 *
 * @param bool $casesensitive use case sensitive search
 * @param bool $accentsensitive use accent sensitive search
 * @return string SQL code fragment
 */
function mssql_get_collation($casesensitive = true, $accentsensitive = true) {
    global $DB, $CFG;

    // Make some default.
    $collation = 'Latin1_General_CI_AI';

    $sql = "SELECT CAST(DATABASEPROPERTYEX('{$CFG->dbname}', 'Collation') AS varchar(255)) AS SQLCollation";
    $record = $DB->get_record_sql($sql, null, IGNORE_MULTIPLE);
    if ($record) {
        $collation = $record->sqlcollation;
        if ($casesensitive) {
            $collation = str_replace('_CI', '_CS', $collation);
        } else {
            $collation = str_replace('_CS', '_CI', $collation);
        }
        if ($accentsensitive) {
            $collation = str_replace('_AI', '_AS', $collation);
        } else {
            $collation = str_replace('_AS', '_AI', $collation);
        }
    }

    return $collation;
}

/**
 * Purge the MUC of ignored embedded reports and sources.
 *
 * @return void
 */
function totara_rb_purge_ignored_reports() {
    // Embedded reports.
    $cache = cache::make('totara_reportbuilder', 'rb_ignored_embedded');
    $cache->purge();
    // Report sources.
    $cache = cache::make('totara_reportbuilder', 'rb_ignored_sources');
    $cache->purge();
}

/**
* Loops through the navigation options and returns an array of classes
*
* The array contains the navigation option name as a key, and a string
* to be inserted into a class as the value. The string is either
* ' selected' if the option is currently selected, or an empty string ('')
*
* @param array $navstructure A nested array containing the structure of the menu
* @param string $primary_selected The name of the primary option
* @param string $secondary_selected The name of the secondary option
*
* @return array Array of strings, keyed on option names
*/
function totara_get_nav_select_classes($navstructure, $primary_selected, $secondary_selected) {

    $selectedstr = ' selected';
    $selected = array();
    foreach($navstructure as $primary => $secondaries) {
        if($primary_selected == $primary) {
            $selected[$primary] = $selectedstr;
        } else {
            $selected[$primary] = '';
        }
        foreach($secondaries as $secondary) {
            if($secondary_selected == $secondary) {
                $selected[$secondary] = $selectedstr;
            } else {
                $selected[$secondary] = '';
            }
        }
    }
    return $selected;
}

/**
 * Reset Totara menu caching.
 */
function totara_menu_reset_cache() {
    global $SESSION;
    unset($SESSION->mymenu);
}

/**
 * Builds Totara menu, returns an array of objects that
 * represent the stucture of the menu
 *
 * The parents must be defined before the children so we
 * can correctly figure out which items should be selected
 *
 * @return Array of menu item objects
 */
function totara_build_menu() {
    global $SESSION, $USER, $CFG;

    $lang = current_language();
    if (!empty($CFG->menulifetime) and !empty($SESSION->mymenu['lang'])) {
        if ($SESSION->mymenu['id'] == $USER->id and $SESSION->mymenu['lang'] === $lang) {
            if ($SESSION->mymenu['c'] + $CFG->menulifetime > time()) {
                $tree = $SESSION->mymenu['tree'];
                foreach ($tree as $k => $node) {
                    $node = clone($node);
                    $node->url = \totara_core\totara\menu\menu::replace_url_parameter_placeholders($node->url);
                    $tree[$k] = $node;
                }
                return $tree;
            }
        }
    }
    unset($SESSION->mymenu);

    $rs = \totara_core\totara\menu\menu::get_nodes();
    $tree = array();
    $parentree = array();
    foreach ($rs as $id => $item) {

        if (!isset($parentree[$item->parentid])) {
            $node = \totara_core\totara\menu\menu::get($item->parentid);
            // Silently ignore bad nodes - they might have been removed
            // from the code but not purged from the DB yet.
            if ($node === false) {
                continue;
            }
            $parentree[$item->parentid] = $node;
        }
        $node = $parentree[$item->parentid];

        switch ((int)$item->parentvisibility) {
            case \totara_core\totara\menu\menu::HIDE_ALWAYS:
                if (!is_null($item->parentvisibility)) {
                    continue 2;
                }
                break;
            case \totara_core\totara\menu\menu::SHOW_WHEN_REQUIRED:
                $classname = $item->parent;
                if (!is_null($classname) && class_exists($classname)) {
                    $parentnode = new $classname($node);
                    if ($parentnode->get_visibility() != \totara_core\totara\menu\menu::SHOW_ALWAYS) {
                        continue 2;
                    }
                }
                break;
            case \totara_core\totara\menu\menu::SHOW_ALWAYS:
                break;
            case \totara_core\totara\menu\menu::SHOW_CUSTOM:
                $classname = $item->parent;
                if (!is_null($classname) && class_exists($classname)) {
                    $parentnode = new $classname($node);
                    if (!$parentnode->get_visibility()) {
                        continue 2;
                    }
                }
                break;
            default:
                // Silently ignore bad nodes - they might have been removed
                // from the code but not purged from the DB yet.
                continue 2;
        }

        $node = \totara_core\totara\menu\menu::node_instance($item);
        // Silently ignore bad nodes - they might have been removed
        // from the code but not purged from the DB yet.
        if ($node === false) {
            continue;
        }
        // Check each node's visibility.
        if ($node->get_visibility() != \totara_core\totara\menu\menu::SHOW_ALWAYS) {
            continue;
        }

        $tree[] = (object)array(
            'name'     => $node->get_name(),
            'linktext' => $node->get_title(),
            'parent'   => $node->get_parent(),
            'url'      => $node->get_url(false),
            'target'   => $node->get_targetattr()
        );
    }

    if (!empty($CFG->menulifetime)) {
        $SESSION->mymenu = array(
            'id' => $USER->id,
            'lang' => $lang,
            'c' => time(),
            'tree' => $tree,
        );
    }

    foreach ($tree as $k => $node) {
        $node = clone($node);
        $node->url = \totara_core\totara\menu\menu::replace_url_parameter_placeholders($node->url);
        $tree[$k] = $node;
    }

    return $tree;
}

function totara_upgrade_menu() {
    totara_menu_reset_cache();
    $TOTARAMENU = new \totara_core\totara\menu\build();
    $plugintypes = core_component::get_plugin_types();
    foreach ($plugintypes as $plugin => $path) {
        $pluginname = core_component::get_plugin_list_with_file($plugin, 'db/totaramenu.php');
        if (!empty($pluginname)) {
            foreach ($pluginname as $name => $file) {
                // This is NOT a library file!
                require($file);
            }
        }
    }
    $TOTARAMENU->upgrade();
}

/**
 * Color functions used by totara themes for auto-generating colors
 */

/**
 * Given a hex color code lighten or darken the color by the specified
 * percentage and return the hex code of the new color
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @param integer $percent Number between -100 and 100, negative to darken
 * @return string 6 digit hex color code for resulting color
 */
function totara_brightness($color, $percent) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to totara_brightness().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    if ($percent >= 0) {
        $red = floor($red + (255 - $red) * $percent / 100);
        $green = floor($green + (255 - $green) * $percent / 100);
        $blue = floor($blue + (255 - $blue) * $percent / 100);
    } else {
        // remember $percent is negative
        $red = floor($red + $red * $percent / 100);
        $green = floor($green + $green * $percent / 100);
        $blue = floor($blue + $blue * $percent / 100);
    }

    return '#' .
        str_pad(dechex($red), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($green), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
}


/**
 * Given a hex color code lighten or darken the color by the specified
 * number of hex points and return the hex code of the new color
 *
 * This differs from {@link totara_brightness} in that the scaling is
 * linear (until pure white or black is reached). *
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @param integer $amount Number between -255 and 255, negative to darken
 * @return string 6 digit hex color code for resulting color
 */
function totara_brightness_linear($color, $amount) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to totara_brightness_linear().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    // max and min ensure colour remains within range
    $red = max(min($red + $amount, 255), 0);
    $green = max(min($green + $amount, 255), 0);
    $blue = max(min($blue + $amount, 255), 0);

    return '#' .
        str_pad(dechex($red), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($green), 2, '0', STR_PAD_LEFT) .
        str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
}

/**
 * Given a hex color code return the hex code for either white or black,
 * depending on which color will have the most contrast compared to $color
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @return string 6 digit hex color code for resulting color
 */
function totara_readable_text($color) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to totara_readable_text().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    // get average intensity
    $avg = array_sum(array($red, $green, $blue)) / 3;
    return ($avg >= 153) ? '#000000' : '#FFFFFF';
}

/**
 * Given a hex color code return the rgba shadow that will work best on text
 * that is the readable-text color
 *
 * This is useful for adding shadows to text that uses the readable-text color.
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @return string rgba() colour to provide an appropriate shadow for readable-text
 */
function totara_readable_text_shadow($color) {
    if (totara_readable_text($color) == '#FFFFFF') {
        return 'rgba(0, 0, 0, 0.75)';
    } else {
        return 'rgba(255, 255, 255, 0.75)';
    }
}
/**
 * Given a hex color code return the hex code for a desaturated version of
 * $color, which has the same brightness but is greyscale
 *
 * @param string $color Hex color code in format '#abc' or '#aabbcc'
 * @return string 6 digit hex color code for resulting greyscale color
 */
function totara_desaturate($color) {
    // convert 3 digit color codes into 6 digit form
    $pattern = '/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
    $color = preg_replace($pattern, '#$1$1$2$2$3$3', $color );

    // don't change if color format not recognised
    $pattern = '/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
    if (!preg_match($pattern, $color, $matches)) {
        debugging("Bad hex color '{$color}' passed to desaturate().", DEBUG_DEVELOPER);
        return $color;
    }
    $red = hexdec($matches[1]);
    $green = hexdec($matches[2]);
    $blue = hexdec($matches[3]);

    // get average intensity
    $avg = array_sum(array($red, $green, $blue)) / 3;

    return '#' . str_repeat(str_pad(dechex($avg), 2, '0', STR_PAD_LEFT), 3);
}

/**
 * Given an array of the form:
 * array(
 *   // setting name => default value
 *   'linkcolor' => '#dddddd',
 * );
 * perform substitutions on the css provided
 *
 * @param string $css CSS to substitute settings variables
 * @param object $theme Theme object
 * @param array $substitutions Array of settingname/defaultcolor pairs
 * @return string CSS with replacements
 */
function totara_theme_generate_autocolors($css, $theme, $substitutions) {

    // each element added here will generate a new color
    // with the key appended to the existing setting name
    // and with the color passed through the function with the arguments
    // supplied via an array:
    $autosettings = array(
        'lighter' => array('brightness_linear', 15),
        'darker' => array('brightness_linear', -15),
        'light' => array('brightness_linear', 25),
        'dark' => array('brightness_linear', -25),
        'lighter-perc' => array('brightness', 15),
        'darker-perc' => array('brightness', -15),
        'light-perc' => array('brightness', 25),
        'dark-perc' => array('brightness', -25),
        'readable-text' => array('readable_text'),
        'readable-text-shadow' => array('readable_text_shadow'),
    );

    $find = array();
    $replace = array();
    foreach ($substitutions as $setting => $defaultcolor) {
        $value = isset($theme->settings->$setting) ? $theme->settings->$setting : $defaultcolor;
        if (substr($value, 0, 1) == '#') {
            $find[] = "[[setting:{$setting}]]";
            $replace[] = $value;

            foreach ($autosettings as $suffix => $modification) {
                if (!is_array($modification) || count($modification) < 1) {
                    continue;
                }
                $function_name = 'totara_' . array_shift($modification);
                $function_args = $modification;
                array_unshift($function_args, $value);

                $find[] = "[[setting:{$setting}-$suffix]]";
                $replace[] = call_user_func_array($function_name, $function_args);
            }
        }

    }
    if (isset($theme->settings->headerbgc)) {
        $find[] = "[[setting:heading-on-headerbgc]]";
        $replace[] = (totara_readable_text($theme->settings->headerbgc) == '#000000' ? '#444444' : '#b3b3b3');

        $find[] = "[[setting:text-on-headerbgc]]";
        $replace[] = (totara_readable_text($theme->settings->headerbgc) == '#000000' ? '#444444' : '#cccccc');
    }
    return str_replace($find, $replace, $css);
}

/**
 * Encrypt any string using totara public key
 *
 * @param string $plaintext
 * @param string $key Public key If not set totara public key will be used
 * @return string Encrypted data
 */
function encrypt_data($plaintext, $key = '') {
    global $CFG;
    require_once($CFG->dirroot . '/totara/core/lib/phpseclib/Crypt/RSA.php');
    require_once($CFG->dirroot . '/totara/core/lib/phpseclib/Crypt/Hash.php');
    require_once($CFG->dirroot . '/totara/core/lib/phpseclib/Crypt/Random.php');
    require_once($CFG->dirroot . '/totara/core/lib/phpseclib/Math/BigInteger.php');

    $rsa = new \phpseclib\Crypt\RSA();
    if ($key === '') {
        $key = file_get_contents(PUBLIC_KEY_PATH);
    }
    if (!$key) {
        return false;
    }
    $rsa->loadKey($key);
    $rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_PKCS1);
    $ciphertext = $rsa->encrypt($plaintext);
    return $ciphertext;
}

/**
 * Get course/program icon for displaying in course/program page.
 *
 * @param $instanceid
 * @return string icon URL.
 */
function totara_get_icon($instanceid, $icontype) {
    global $DB, $OUTPUT, $PAGE;

    $component = 'totara_core';
    $urlicon = '';

    if ($icontype == TOTARA_ICON_TYPE_COURSE) {
        $icon = $DB->get_field('course', 'icon', array('id' => $instanceid));
    } else {
        $icon = $DB->get_field('prog', 'icon', array('id' => $instanceid));
    }

    if ($customicon = $DB->get_record('files', array('pathnamehash' => $icon))) {
        $fs = get_file_storage();
        $context = context_system::instance();
        if ($file = $fs->get_file($context->id, $component, $icontype, $customicon->itemid, '/', $customicon->filename)) {
            $urlicon = moodle_url::make_pluginfile_url($file->get_contextid(), $component,
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $customicon->filename, true);
        }
    }

    if (empty($urlicon)) {
        $iconpath = $icontype . 'icons/';
        $imagelocation = $PAGE->theme->resolve_image_location($iconpath . $icon, $component);
        if (empty($icon) || empty($imagelocation)) {
            $icon = 'default';
        }
        $urlicon = $OUTPUT->pix_url('/' . $iconpath . $icon, $component);
    }

    return $urlicon->out();
}

/**
 * Determine if the current request is an ajax request
 *
 * @param array $server A $_SERVER array
 * @return boolean
 */
function is_ajax_request($server) {
    return (isset($server['HTTP_X_REQUESTED_WITH']) && strtolower($server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

/**
 * Totara specific initialisation
 * Currently needed only for AJAX scripts
 * Caution: Think before change to avoid conflict with other $CFG->moodlepageclass affecting code (for example installation scripts)
 */
function totara_setup() {
    global $CFG;
    if (is_ajax_request($_SERVER)) {
        $CFG->moodlepageclassfile = $CFG->dirroot.'/totara/core/pagelib.php';
        $CFG->moodlepageclass = 'totara_page';
    }
}

/**
 * Checks if idnumber already exists.
 * Used when adding new or updating exisiting records.
 *
 * @param string $table Name of the table
 * @param string $idnumber IDnumber to check
 * @param int $itemid Item id. Zero value means new item.
 *
 * @return bool True if idnumber already exists
 */
function totara_idnumber_exists($table, $idnumber, $itemid = 0) {
    global $DB;

    if (!$itemid) {
        $duplicate = $DB->record_exists($table, array('idnumber' => $idnumber));
    } else {
        $duplicate = $DB->record_exists_select($table, 'idnumber = :idnumber AND id != :itemid',
                                               array('idnumber' => $idnumber, 'itemid' => $itemid));
    }

    return $duplicate;
}

/**
 * List of strings which can be used with 'totara_feature_*() functions'.
 *
 * Update this list if you add/remove settings in admin/settings/subsystems.php.
 *
 * @return array Array of strings of supported features (should have a matching "enable{$feature}" config setting).
 */
function totara_advanced_features_list() {
    return array(
        'goals',
        'competencies',
        'appraisals',
        'feedback360',
        'learningplans',
        'programs',
        'certifications',
        'totaradashboard',
        'reportgraphs',
        'myteam',
        'recordoflearning',
        'positions',
    );
}

/**
 * Check the state of a particular Totara feature against the specified state.
 *
 * Used by the totara_feature_*() functions to see if some Totara functionality is visible/hidden/disabled.
 *
 * @param string $feature Name of the feature to check, must match options from {@link totara_advanced_features_list()}.
 * @param integer $stateconstant State to check, must match one of TOTARA_*FEATURE constants defined in this file.
 * @return bool True if the feature's config setting is in the specified state.
 */
function totara_feature_check_state($feature, $stateconstant) {
    global $CFG;

    if (!in_array($feature, totara_advanced_features_list())) {
        throw new coding_exception("'{$feature}' not supported by Totara feature checking code.");
    }

    $cfgsetting = "enable{$feature}";
    return (isset($CFG->$cfgsetting) && $CFG->$cfgsetting == $stateconstant);
}

/**
 * Check to see if a feature is set to be visible in Advanced Features
 *
 * @param string $feature The name of the feature from the list in {@link totara_feature_check_support()}.
 * @return bool True if the feature is set to be visible.
 */
function totara_feature_visible($feature) {
    return totara_feature_check_state($feature, TOTARA_SHOWFEATURE);
}

/**
 * Check to see if a feature is set to be disabled in Advanced Features
 *
 * @param string $feature The name of the feature from the list in {@link totara_feature_check_support()}.
 * @return bool True if the feature is disabled.
 */
function totara_feature_disabled($feature) {
    return totara_feature_check_state($feature, TOTARA_DISABLEFEATURE);
}

/**
 * Check to see if a feature is set to be hidden in Advanced Features
 *
 * @param string $feature The name of the feature from the list in {@link totara_feature_check_support()}.
 * @return bool True if the feature is hidden.
 */
function totara_feature_hidden($feature) {
    return totara_feature_check_state($feature, TOTARA_HIDEFEATURE);
}

/**
 * A centralised location for getting all name fields. Returns an array or sql string snippet.
 * Moodle's get_all_user_name_fields function is faulty - it ignores the $tableprefix and $fieldprefix
 * when $returnsql is false. This wrapper function uses get_all_user_name_fields to get the list of fields,
 * then applies the given parameters to the raw list.
 *
 * @param bool $returnsql True for an sql select field snippet.
 * @param string $tableprefix table query prefix to use in front of each field.
 * @param string $prefix prefix added to the name fields e.g. authorfirstname.
 * @param string $fieldprefix sql field prefix e.g. id AS userid.
 * @param bool $onlyused true to only return the fields used by fullname() (and sorted as they appear)
 * @return array|string All name fields.
 */
function totara_get_all_user_name_fields($returnsql = false, $tableprefix = null, $prefix = null, $fieldprefix = null, $onlyused = false) {
    global $CFG, $SESSION;

    $fields = get_all_user_name_fields();

    // Find the fields that are used by fullname() and sort them as they would appear.
    if ($onlyused) {
        // Get the setting for user name display format.
        if (!empty($SESSION->fullnamedisplay)) {
            $CFG->fullnamedisplay = $SESSION->fullnamedisplay;
        }
        $fullnamedisplay = $CFG->fullnamedisplay;

        // Find the fields that are used.
        $usedfields = array();
        foreach ($fields as $field) {
            $posfound = strpos($fullnamedisplay, $field);
            if ($posfound !== false) {
                $usedfields[$posfound] = $field;
            }
        }

        // Sorts the fields.
        ksort($usedfields);
        $fields = $usedfields;

        // Make sure that something is returned.
        if (empty($fields)) {
            $fields = array('firstname', 'lastname');
        }
    }

    // Add the prefix if provided.
    if ($prefix) {
        foreach ($fields as $key => $field) {
            $fields[$key] = $prefix . $field;
        }
    }

    if ($tableprefix) {
        $tableprefix = $tableprefix . ".";
    }

    // Add the tableprefix and fieldprefix and set up the sql. Do this even if tableprefix, fieldprefix and
    // returnsql are all unused, as this will set the correct array keys (field aliases).
    $prefixedfields = array();
    foreach ($fields as $field) {
        if ($returnsql && $fieldprefix) {
            $prefixedfields[$fieldprefix . $field] = $tableprefix . $field . ' AS ' . $fieldprefix . $field;
        } else {
            $prefixedfields[$fieldprefix . $field] = $tableprefix . $field;
        }
    }

    if ($returnsql) {
        return implode(',', $prefixedfields);
    } else {
        return $prefixedfields;
    }
}

/**
 * SQL concat ready option of totara_get_all_user_name_fields function
 * This function return null-safe field names for concatentation into one field using $DB->sql_concat_join()
 *
 * @param string $tableprefix table query prefix to use in front of each field.
 * @param string $prefix prefix added to the name fields e.g. authorfirstname.
 * @param bool $onlyused true to only return the fields used by fullname() (and sorted as they appear)
 * @return array|string All name fields.
 */
function totara_get_all_user_name_fields_join($tableprefix = null, $prefix = null, $onlyused = false) {
    $fields = totara_get_all_user_name_fields(false, $tableprefix, $prefix, null, $onlyused);
    foreach($fields as $key => $field) {
        $fields[$key] = "COALESCE($field,'')";
    }
    return $fields;
}

/**
 * Creates a unique value within given table column
 *
 * @param string $table The database table.
 * @param string $column The database column to search within for uniqueness.
 * @param string $prefix A prefix to the name.
 * @return string a unique sha1
 */
function totara_core_generate_unique_db_value($table, $column, $prefix = null) {
    global $DB;
    $exists = true;
    $name = null;
    while ($exists) {
        $name = sha1(uniqid(rand(), true));
        if ($prefix) {
            $name = $prefix . '_' . $name;
        }
        $exists = $DB->record_exists($table, array($column => $name));
    }
    return $name;
}

/**
 * Convert a core\output\notification instance to the legacy array format.
 *
 * @param \core\output\notification $notification The templatable to be converted.
 */
function totara_convert_notification_to_legacy_array(\core\output\notification $notification) {
    global $OUTPUT;

    $type = $notification->get_message_type();
    $variables = $notification->export_for_template($OUTPUT);

    $data = [ 'message' => $variables['message'], 'class' => trim($type . ' ' . $variables['extraclasses'])];

    return array_merge($notification->get_totara_customdata(), $data);
}

/**
 * Is the clone db configured?
 *
 * @return bool
 */
function totara_is_clone_db_configured() {
    global $CFG;
    return !empty($CFG->clone_dbname);
}

/**
 * Returns instance of read only database clone.
 *
 * @param bool $reconnect force reopening of new connection
 * @return moodle_database|null
 */
function totara_get_clone_db($reconnect = false) {
    global $CFG;

    /** @var moodle_database $db */
    static $db = null;

    if ($reconnect) {
        if ($db) {
            $db->dispose();
        }
        $db = null;
    } else if (isset($db)) {
        if ($db === false) {
            // Previous init failed.
            return null;
        }
        return $db;
    }

    if (empty($CFG->clone_dbname)) {
        // Not configured, this is fine.
        $db = false;
        return null;
    }

    if (!$db = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary, false)) {
        debugging('Cannot find driver for the cloned database', DEBUG_DEVELOPER);
        $db = false;
        return null;
    }

    try {
        // NOTE: dbname is always required and the prefix must be exactly the same.
        $dbhost = isset($CFG->clone_dbhost) ? $CFG->clone_dbhost : $CFG->dbhost;
        $dbuser = isset($CFG->clone_dbuser) ? $CFG->clone_dbuser : $CFG->dbuser;
        $dbpass = isset($CFG->clone_dbpass) ? $CFG->clone_dbpass : $CFG->dbpass;
        $dboptions = isset($CFG->clone_dboptions) ? $CFG->clone_dboptions : $CFG->dboptions;

        $db->connect($dbhost, $dbuser, $dbpass, $CFG->clone_dbname, $CFG->prefix, $dboptions);
    } catch (Exception $e) {
        debugging('Cannot connect to the cloned database', DEBUG_DEVELOPER);
        $db = false;
        return null;
    }

    return $db;
}