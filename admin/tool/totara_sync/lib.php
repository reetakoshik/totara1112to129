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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */

defined('MOODLE_INTERNAL') || die;

define('TOTARA_SYNC_DBROWS', 10000);
define('FILE_ACCESS_DIRECTORY', 0);
define('FILE_ACCESS_UPLOAD', 1);
define('TOTARA_SYNC_FILE_ACCESS_MEMORY', 2);
define('TOTARA_SYNC_LOGTYPE_MAX_NOTIFICATIONS', 50);

/**
 * Finds the run id of the latest sync run
 *
 * @return int latest runid
 */
function latest_runid() {
    global $DB;

    $runid = $DB->get_field_sql('SELECT MAX(runid) FROM {totara_sync_log}');

    if (!empty($runid)) {
        return $runid;
    } else {
        return 0;
    }
}

/**
 * Search the sync log for any errors or warnings from the last run.
 *
 * @return bool true if errors found
 */
function latest_run_has_errors() {
    global $DB;

    $sql = "SELECT id
              FROM {totara_sync_log}
             WHERE runid = :runid
               AND (logtype = :logtype1 OR logtype = :logtype2)";

    $params = array(
        'runid' => latest_runid(),
        'logtype1' => 'warn',
        'logtype2' => 'error'
    );

    return $DB->record_exists_sql($sql, $params);
}

/**
 * Sync Totara elements with external sources
 *
 * @param bool $isscheduledtask Set to true if this is being run by a scheduled task that would run all elements,
 *      except for those that have their own schedule configuration.
 * @return bool success
 */
function tool_totara_sync_run($isscheduledtask = false) {

    // Check enabled sync element objects
    $elements = totara_sync_get_elements(true);
    if (empty($elements)) {
        mtrace(get_string(
            'syncnotconfiguredsummary',
            'tool_totara_sync',
            get_string('noenabledelements', 'tool_totara_sync')
        ));
        return false;
    }

    $status = true;

    // Sort according to weighting.
    usort($elements, function($a, $b) {
        return $a->syncweighting - $b->syncweighting;
    });

    foreach ($elements as $element) {

        if ($isscheduledtask) {
            if (empty($element->config->scheduleusedefaults)) {
                // This element should not be run via the default scheduled task.
                continue;
            }
        }

        $success = $element->run_sync();
        $status = $status && $success;
    }

    return $status;
}

/**
 * Method for adding sync log messages
 *
 * @param string $element element name
 * @param string $info the log message
 * @param string $type the log message type
 * @param string $action the action which caused the log message
 * @param boolean $showmessage shows error messages on the main page when running sync if it is true
 */
function totara_sync_log($element, $info, $type='info', $action='', $showmessage=true) {
    global $DB, $OUTPUT;

    // Avoid getting an error from the database trying to save a value longer than length limit (255 characters).
    if (core_text::strlen($info) > 255) {
        $info = trim(core_text::substr($info, 0, 252)) . "...";
    }

    static $sync_runid = null;

    if ($sync_runid == null) {
        $sync_runid = latest_runid() + 1;
    }

    $todb = new stdClass;
    $todb->element = $element;
    $todb->logtype = $type;
    $todb->action = $action;
    $todb->info = $info;
    $todb->time = time();
    $todb->runid = $sync_runid;

    if ($showmessage && ($type == 'warn' || $type == 'error')) {
        $typestr = get_string($type, 'tool_totara_sync');
        $class = $type == 'warn' ? 'notifynotice' : 'notifyproblem';
        echo $OUTPUT->notification($typestr . ':' . $element . ' - ' . $info, $class);
    }

    return $DB->insert_record('totara_sync_log', $todb);
}

/**
 * Get the sync file paths for all elements
 *
 * @return array of filepaths
 */
function totara_sync_get_element_files() {
    global $CFG;

    // Get all available sync element files
    $edir = $CFG->dirroot.'/admin/tool/totara_sync/elements/';
    $pattern = '/(.*?)\.php$/';
    $files = preg_grep($pattern, scandir($edir));
    $filepaths = array();
    foreach ($files as $key => $val) {
        $filepaths[] = $edir . $val;
    }
    return $filepaths;
}

/**
 * Get sync elements
 *
 * @param boolean $onlyenabled only return enabled elements
 *
 * @return totara_sync_element[]
 */
function totara_sync_get_elements($onlyenabled=false) {
    global $CFG;

    $efiles = totara_sync_get_element_files();

    $elements = array();
    foreach ($efiles as $filepath) {
        $element = basename($filepath, '.php');

        if ($element == 'pos' && totara_feature_disabled('positions')) {
            continue;
        }
        if ($element == 'comp' && totara_feature_disabled('competencies')) {
            continue;
        }

        if ($onlyenabled) {
            if (!get_config('totara_sync', 'element_'.$element.'_enabled')) {
                continue;
            }
        }

        require_once($filepath);

        $elementclass = 'totara_sync_element_'.$element;
        if (!class_exists($elementclass)) {
            // Skip if the class does not exist
            continue;
        }

        $elements[$element] = new $elementclass;
    }

    return $elements;
}

/**
 * Get a specified element object
 *
 * @param string $element the element name
 *
 * @return totara_sync_element|bool An instance of the requested element or false if not found.
 */
function totara_sync_get_element($element) {
    $elements = totara_sync_get_elements();

    if (!in_array($element, array_keys($elements))) {
        return false;
    }

    return $elements[$element];
}

/**
 * Create the directory to be used for directory check csv sources
 *
 * @param string $dirpath the directory path
 * @return bool
 */
function totara_sync_make_dirs($dirpath) {
    global $CFG;

    if (!is_dir($dirpath)) {
        if (!mkdir($dirpath, $CFG->directorypermissions, true)) {
            return false;
        }
    }

    return true;
}

/**
 * Cleans the values and returns as an array
 *
 * @param array $fields
 * @param string $encoding the encoding type that string is being converted from to utf-8 (deprecated, use totara_sync_clean_csvfile)
 * @return array $fields
 * @deprecated since Totara 12
 */
function totara_sync_clean_fields($fields, $encoding = 'UTF-8') {

    // We only want to show this once, not every time for a file that might have lots of rows.
    static $debugged = false;
    if (!$debugged) {
        debugging('totara_sync_clean_fields has been deprecated. It is recommended that you do cleaning specific to your import source.');
        $debugged = true;
    }

    foreach ($fields as $key => $value) {
        $format = ($key == 'password') ? PARAM_RAW : PARAM_TEXT;
        if ($encoding !== 'UTF-8') {
            $value = core_text::convert($value, $encoding, 'UTF-8');
        }
        $fields[$key] = clean_param(trim($value), $format);
    }
    return $fields;
}

/**
 * Convert and cleans content
 *
 * @param string $storefilepath original file to clean up
 * @param string $encoding content encoding
 * @param integer $fileaccess is FILE_ACCESS_DIRECTORY or FILE_ACCESS_UPLOAD
 * @param string $elementname the name of the element this source applies to
 *
 * @return string temporary file with clean content
 */
function totara_sync_clean_csvfile($storefilepath, $encoding, $fileaccess, $elementname) {

    if (!is_readable($storefilepath)) {
        throw new totara_sync_exception($elementname, 'populatesynctablecsv', 'storedfilecannotread', $storefilepath);
    }

    $content = file_get_contents($storefilepath);

    if (strtoupper($encoding) === 'UTF-8') {
        // Remove Unicode BOM from first line.
        $content = core_text::trim_utf8_bom($content);
    }
    $content = core_text::convert($content, $encoding, 'utf-8');

    // Create a temporary file and store the csv file there and delete original filename or
    // overwrite original filename.
    if ($fileaccess == FILE_ACCESS_UPLOAD) {
        unlink($storefilepath);
        $file = tempnam(make_temp_directory('/csvimport'), 'tmp');
    } else {
        @unlink($storefilepath);
        $file = $storefilepath;
    }

    $result = file_put_contents($file, $content);
    if ($result === false) {
        if ($fileaccess == FILE_ACCESS_UPLOAD) {
            @unlink($file);
        }
        throw new totara_sync_exception($elementname, 'populatesynctablecsv', 'cannotsavedata', $file);
    }

    // Use permissions form parent dir.
    @chmod($file, (fileperms(dirname($file)) & 0666));
    return $file;
}

/**
 * Perform bulk inserts into specified table
 *
 * @param string $table table name
 * @param array $datarows an array of row arrays
 *
 * @return boolean
 */
function totara_sync_bulk_insert($table, $datarows) {
    global $CFG, $DB;

    if (empty($datarows)) {
        return true;
    }

    $DB->insert_records($table, $datarows);
    return true;
}

/**
 * Notify admin users or admin user of any sync failures since last notification.
 *
 * Note that this function must be only executed from the cron script
 *
 * @return bool true if executed, false if not
 * @deprecated since Totara 12
 */
function totara_sync_notify() {
    global $CFG, $DB;

    debugging('totara_sync_notify has been deprecated. Running elements via their run_sync() method will notify users following the run.');

    $now = time();
    $dateformat = get_string('strftimedateseconds', 'langconfig');
    $notifyemails = get_config('totara_sync', 'notifymailto');
    $notifyemails = !empty($notifyemails) ? explode(',', $notifyemails) : array();
    $notifytypes = get_config('totara_sync', 'notifytypes');
    $notifytypes = !empty($notifytypes) ? explode(',', $notifytypes) : array();

    if (empty($notifyemails) || empty($notifytypes)) {
        set_config('lastnotify', $now, 'totara_sync');
        return false;
    }

    // The same users as login failures.
    if (!$lastnotify = get_config('totara_sync', 'lastnotify')) {
        $lastnotify = 0;
    }

    // Get most recent log messages of type.
    list($sqlin, $params) = $DB->get_in_or_equal($notifytypes);
    $params = array_merge($params, array($lastnotify));
    $logitems = $DB->get_records_select('totara_sync_log', "logtype {$sqlin} AND time > ?", $params,
                                        'time DESC', '*', 0, TOTARA_SYNC_LOGTYPE_MAX_NOTIFICATIONS);
    if (!$logitems) {
        // Nothing to report.
        return true;
    }

    // Build email message.
    $logcount = count($logitems);
    $sitename = get_site();
    $sitename = format_string($sitename->fullname);
    $notifytypes_str = array_map(function($type) {return get_string($type.'plural', 'tool_totara_sync');}, $notifytypes);
    $subject = get_string('notifysubject', 'tool_totara_sync', $sitename);

    $a = new stdClass();
    $a->logtypes = implode(', ', $notifytypes_str);
    $a->count = $logcount;
    $a->since = userdate($lastnotify, $dateformat);
    $message = get_string('notifymessagestart', 'tool_totara_sync', $a);
    $message .= "\n\n";
    foreach ($logitems as $logentry) {
        $logentry->time = userdate($logentry->time, $dateformat);
        $logentry->logtype = get_string($logentry->logtype, 'tool_totara_sync');
        $message .= get_string('notifymessage', 'tool_totara_sync', $logentry) . "\n\n";
    }
    $message .= "\n" . get_string('viewsyncloghere', 'tool_totara_sync',
            $CFG->wwwroot . '/admin/tool/totara_sync/admin/synclog.php');

    // Send emails.
    mtrace("\n{$logcount} relevant totara sync log messages since " .
        userdate($lastnotify, $dateformat)) . ". Sending notifications...";
    $supportuser = core_user::get_support_user();
    foreach ($notifyemails as $emailaddress) {
        $userto = \totara_core\totara_user::get_external_user(trim($emailaddress));
        email_to_user($userto, $supportuser, $subject, $message);
    }

    // Update lastnotify with current time.
    set_config('lastnotify', $now, 'totara_sync');

    return true;
}

class totara_sync_exception extends moodle_exception {
    public $tsync_element;
    public $tsync_action;
    public $tsync_logtype;

    /**
     * Constructor
     * @param string $errorcode The name of the string from error.php to print
     * @param object $a Extra words and phrases that might be required in the error string
     * @param string $debuginfo optional debugging information
     * @param string $logtype optional totara sync log type
     */
    public function __construct($element, $action, $errorcode, $a = null, $debuginfo = null, $logtype = 'error') {
        $this->tsync_element = $element;
        $this->tsync_action = $action;
        $this->tsync_logtype = $logtype;

        parent::__construct($errorcode, 'tool_totara_sync', $link='', $a, $debuginfo);
    }
}
