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

global $CFG;
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');

abstract class totara_sync_element {
    public $config;

    /**
     * @var int
     * Determines whether an element's sync() should be run before or after others.
     * Lower values are run first. Leave at 0 if ordering does not matter.
     */
    public $syncweighting = 0;

    /**
     * @var totara_sync_source
     *
     * Subclass of totara_sync_source.
     */
    public $source;

    /**
     * Returns the element's name to be used for construction of classes, etc.
     *
     * To be implemented in child classes
     */
    abstract function get_name();

    abstract function has_config();

    /**
     * Element config form elements
     *
     * To be implemented in child classes
     */
    abstract function config_form(&$mform);

    abstract function config_save($data);

    function validation($data, $file) {
        return array();
    }

    /**
     * Function that handles sync between external sources and Totara
     *
     * To be implemented in child classes
     *
     * @throws totara_sync_exception
     */
    abstract function sync();

    function __construct() {
        if ($this->has_config()) {
            $this->config = get_config($this->get_classname());
        }
    }

    function get_classname() {
        return get_class($this);
    }

    /**
     * @return totara_sync_source[]
     */
    function get_sources() {
        global $CFG;

        $elname = $this->get_name();

        // Get all available sync element files
        $sdir = $CFG->dirroot.'/admin/tool/totara_sync/sources/';
        $pattern = '/^source_' . $elname . '_(.*?)\.php$/';
        $sfiles = preg_grep($pattern, scandir($sdir));
        $sources = array();
        foreach ($sfiles as $f) {
            require_once($sdir.$f);

            $basename = basename($f, '.php');
            $sname = str_replace("source_{$elname}_", '', $basename);

            $sclass = "totara_sync_{$basename}";
            if (!class_exists($sclass)) {
                continue;
            }

            $sources[$sname] = new $sclass;
        }

        return $sources;
    }

    /**
     * Get the enabled source for the element
     *
     * @param string $sourceclass name
     * @return totara_sync_source source object or false if no source could be determined
     * @throws totara_sync_exception
     */
    function get_source($sourceclass=null) {
        global $CFG;

        if (empty($this->source)) {

            $elname = $this->get_name();

            if (empty($sourceclass)) {
                // Get enabled source
                if (!$sourceclass = get_config('totara_sync', 'source_' . $elname)) {
                    throw new totara_sync_exception($elname, 'getsource', 'nosourceenabled');
                }
            }
            $sourcefilename = str_replace('totara_sync_', '', $sourceclass);

            $sourcefile = $CFG->dirroot . '/admin/tool/totara_sync/sources/' . $sourcefilename . '.php';
            if (!file_exists($sourcefile)) {
                throw new totara_sync_exception($elname, 'getsource', 'sourcefilexnotfound', $sourcefile);
            }

            require_once($sourcefile);

            if (!class_exists($sourceclass)) {
                throw new totara_sync_exception($elname, 'getsource', 'sourceclassxnotfound', $sourceclass);
            }

            $this->source = new $sourceclass;
        }

        return $this->source;
    }

    /**
     * Gets the element's source's sync table
     *
     * @return string sync table name, e.g mdl_totara_sync_org
     * @throws totara_sync_exception
     */
    function get_source_sync_table() {
        $source = $this->get_source();
        if (!method_exists($source, 'get_sync_table')) {
            // Method to retrieve recordset does not exist, die!
            throw new totara_sync_exception($this->get_name(), 'getsource', 'nosynctablemethodforsourcex', $source->get_name());
        }

        return $source->get_sync_table();
    }

    /**
     * Gets the element's source's sync table clone
     *
     * @return string name of sync table clone, e.g mdl_totara_sync_org
     * @throws totara_sync_exception
     */
    function get_source_sync_table_clone($temptable) {
        $source = $this->get_source();
        if (!method_exists($source, 'get_sync_table_clone')) {
            // Don't continue if no recordset can be retrieved
            throw new totara_sync_exception($this->get_name(), 'getsource', 'nosynctablemethodforsourcex', $source->get_name());
        }

        return $source->get_sync_table_clone();
    }

    /**
     * Is element syncing enabled?
     *
     * @return boolean
     */
    function is_enabled() {
        return get_config('totara_sync', 'element_'.$this->get_name().'_enabled');
    }

    /**
     * Enable element syncing
     */
    function enable() {
        return set_config('element_'.$this->get_name().'_enabled', '1', 'totara_sync');
    }

    /**
     * Disable element syncing
     */
    function disable() {
        return set_config('element_'.$this->get_name().'_enabled', '0', 'totara_sync');
    }

    /**
     * Add sync log message
     */
    function addlog($info, $type='info', $action='') {
        // false param avoid showing error messages on the main page when running sync
        totara_sync_log($this->get_name(), $info, $type, $action, false);
    }

    /**
     * Set element config value
     */
    function set_config($name, $value) {
        $this->config->{$name} = $value;
        return set_config($name, $value, $this->get_classname());
    }

    /**
     * Saves data for this element from the element settings configuration form.
     *
     * @param stdClass $data Values from submitted config form.
     */
    public function save_configuration($data) {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/totara_sync/locallib.php');

        // Set selected source. This is saved within the plugin 'totara_sync', while other setting consider
        // the plugin to be the element classname when being saved.
        $sourcesettingname = 'source_' . $this->get_name();
        set_config($sourcesettingname, $data->{$sourcesettingname}, 'totara_sync');

        if (isset($data->fileaccess) && has_capability('tool/totara_sync:setfileaccess', context_system::instance())) {
            if ($data->fileaccess == totara_sync_element_settings_form::USE_DEFAULT) {
                $this->set_config('fileaccessusedefaults', true);
            } else {
                $this->set_config('fileaccessusedefaults', false);
                $this->set_config('fileaccess', $data->fileaccess);
                if (isset($data->filesdir)) {
                    $this->set_config('filesdir', $data->filesdir);
                }
            }
        }

        $this->set_config('notificationusedefaults', $data->notificationusedefaults);
        if (empty($data->notificationusedefaults)) {
            $notifytypes = !empty($data->notifytypes) ? implode(',', array_keys($data->notifytypes)) : '';
            $this->set_config('notifytypes', $notifytypes);
            $this->set_config('notifymailto', $data->notifymailto);
        }

        $scheduled_task = $this->get_dedicated_scheduled_task();
        if ($scheduled_task) {
            if ($data->scheduleusedefaults) {
                $data->cronenable = 0;
            }
            save_scheduled_task_from_form(
                $data,
                $scheduled_task
            );
        } else {
            // False for $scheduled_task means it could not be found. For backwards compatibility, allow this but let devs know.
            debugging('There is no dedicated scheduled task for this element: ' . $this->get_name());
            $data->scheduleusedefaults = 1;
        }
        $this->set_config('scheduleusedefaults', $data->scheduleusedefaults);


        if ($this->has_config()) {
            // Save element-specific config.
            $this->config_save($data);
        }
    }

    /**
     * Return an instance of a scheduled task that would run only this element.
     *
     * So this does not return the default scheduled task even if this element is set to use schedule defaults.
     * This is only to return the scheduled tasks that is specific to this element.
     *
     * @return \core\task\scheduled_task|bool The associated scheduled task or False if none can be found.
     */
    public function get_dedicated_scheduled_task() {
        return \core\task\manager::get_scheduled_task('\tool_totara_sync\task\\' . $this->get_name());
    }

    /**
     * Sends emails to users who should be notified about log messages following a run of this element.
     *
     * @param int $runid The runid to notify users about. This is set on relevant log messages in the totara_sync_log table.
     */
    protected function notify_users(int $runid) {
        global $DB, $CFG;

        $dateformat = get_string('strftimedateseconds', 'langconfig');

        if (isset($this->config->notificationusedefaults) && empty($this->config->notificationusedefaults)) {
            $notifymailto = !empty($this->config->notifymailto) ? explode(',', $this->config->notifymailto) : [];
            $notifytypes = !empty($this->config->notifytypes) ? explode(',', $this->config->notifytypes) : [];
        } else {
            $notifymailto = get_config('totara_sync', 'notifymailto');
            $notifymailto = !empty($notifymailto) ? explode(',', $notifymailto) : [];
            $notifytypes = get_config('totara_sync', 'notifytypes');
            $notifytypes = !empty($notifytypes) ? explode(',', $notifytypes) : [];
        }

        if (empty($notifymailto) || empty($notifytypes)) {
            return;
        }

        // Get most recent log messages of type.
        list($sqlin, $params) = $DB->get_in_or_equal($notifytypes, SQL_PARAMS_NAMED);
        $params = array_merge($params, ['runid' => $runid]);
        $logitems = $DB->get_records_select(
            'totara_sync_log',
            "logtype {$sqlin} AND runid = :runid",
            $params,
            'time DESC',
            '*',
            0,
            TOTARA_SYNC_LOGTYPE_MAX_NOTIFICATIONS
        );

        if (empty($logitems)) {
            // Nothing to report.
            return;
        }

        // Build email message.
        $logcount = count($logitems);
        $sitename = get_site();
        $sitename = format_string($sitename->fullname);
        $notifytypes_str = array_map(
            function($type) {
                return get_string($type.'plural', 'tool_totara_sync');
            },
            $notifytypes
        );
        $subject = get_string('notifysubject', 'tool_totara_sync', $sitename);

        $a = new stdClass();
        $a->logtypes = implode(', ', $notifytypes_str);
        $a->count = $logcount;
        $a->runid = $runid;
        $message = get_string('notifymessagestartrunid', 'tool_totara_sync', $a);
        $message .= "\n\n";
        foreach ($logitems as $logentry) {
            $logentry->time = userdate($logentry->time, $dateformat);
            $logentry->logtype = get_string($logentry->logtype, 'tool_totara_sync');
            $message .= get_string('notifymessage', 'tool_totara_sync', $logentry);
            $message .= "\n\n";
        }
        $message .= "\n";
        $message .= get_string(
            'viewsyncloghere',
            'tool_totara_sync',
            $CFG->wwwroot . '/admin/tool/totara_sync/admin/synclog.php'
        );

        // Send emails.
        if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
            mtrace("\n{$logcount} relevant totara sync log messages for run id: " . $runid . ". Sending notifications...");
        }
        $supportuser = core_user::get_support_user();
        foreach ($notifymailto as $emailaddress) {
            $userto = \totara_core\totara_user::get_external_user(trim($emailaddress));
            email_to_user($userto, $supportuser, $subject, $message);
        }
    }

    /**
     * This returns an array of any errors relating to the configuration of this element.
     *
     * Used when attempting to run a sync for example. Note: This is not for form validation.
     *
     * @return string[] Error messages that will be displayed to the user attempting to run a sync using this element.
     *    An empty string indicates no configuration errors.
     */
    protected function get_configuration_errors(): array {
        $errors = [];

        if (get_string_manager()->string_exists('displayname:'.$this->get_name(), 'tool_totara_sync')) {
            $elementtext = get_string('displayname:' . $this->get_name(), 'tool_totara_sync');
        } else {
            $elementtext = $this->get_name();
        }

        if (!get_config('totara_sync', 'source_' . $this->get_name())) {
            $errors[] = get_string('sourcenotfound', 'tool_totara_sync', $elementtext);

            // The rest of the checks won't work.
            return $errors;
        }

        try {
            $source = $this->get_source();
        } catch (totara_sync_exception $exception) {
            $errors[] = get_string('sourcetypenotloaded', 'tool_totara_sync', $elementtext);

            // The rest of the checks won't work.
            return $errors;
        }

        // This might fail if the source isn't valid.
        $config = $source->get_config(null);
        $props = get_object_vars($config);
        if (empty($props)) {
            $errors[] = get_string('nosourceconfig', 'tool_totara_sync', $elementtext);
        }

        try {
            if ($source->uses_files()
                && ($this->get_fileaccess() == FILE_ACCESS_DIRECTORY)
                && empty($this->get_filesdir())) {
                $errors[] = get_string('nofilesdir', 'tool_totara_sync');
            }
        } catch (totara_sync_exception $exception) {
            $errors[] = $exception->getMessage();
        }

        return $errors;
    }

    /**
     * Run this element, including checking configuration, the sync itself and notifying users.
     *
     * While sync is also public, run_sync() is what should be run by any external code in order to
     * checks etc mentioned above.
     *
     * @return bool True if the sync ran without errors.
     */
    public function run_sync(): bool {
        $success = false;

        $errors = $this->get_configuration_errors();
        if (!empty($errors)) {
            if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
                mtrace(get_string('syncnotconfiguredsummary', 'tool_totara_sync', implode(", ", $errors)));
            }
            return false;
        }

        try {
            $success = $this->sync();
        } catch (totara_sync_exception $e) {
            $msg = $e->getMessage();
            $msg .= !empty($e->debuginfo) ? " - {$e->debuginfo}" : '';
            totara_sync_log($e->tsync_element, $msg, $e->tsync_logtype, $e->tsync_action);
        } catch (Exception $e) {
            totara_sync_log($this->get_name(), $e->getMessage(), 'error', 'unknown');
        }

        $this->get_source()->drop_table();

        \tool_totara_sync\event\sync_completed::create(['other' => ['element' => $this->get_name()]])->trigger();
        $this->notify_users(latest_runid());

        return $success;
    }

    /**
     * Confirms that the current user is able to upload a file for this element, also taking into account
     * whether this element has the correct configuration for uploading files.
     * e.g. If the source is for CSV and file access is via upload.
     *
     * @return bool True if te current user can upload a file for this element.
     */
    public function can_upload_file(): bool {
        if (!has_capability('tool/totara_sync:upload' . $this->get_name(), context_system::instance())) {
            return false;
        }

        try {
            // We could get a totara_sync_exception here because no source has been set yet.
            if (!$this->get_source()->uses_files()) {
                return false;
            }

            // We could get a totara_sync_exception here because fileaccess has not been set.
            return $this->get_fileaccess() == FILE_ACCESS_UPLOAD;
        } catch (totara_sync_exception $exception) {
            // This exception is ok because various settings not being set yet are not a concern.
            return false;
        }
        // We don't catch other exceptions because we don't expect others should be thrown, so if they are, let them through.
    }

    /**
     * @return bool True if default file access settings should be used.
     */
    public function use_fileaccess_defaults(): bool {
        if (isset($this->config->fileaccessusedefaults)) {
            return !empty($this->config->fileaccessusedefaults);
        }

        // If this setting does not exist, use defaults for backwards compatibility.
        return true;
    }

    /**
     * @return bool True if default notification settings should be used.
     */
    public function use_notification_defaults(): bool {
        if (isset($this->config->notificationusedefaults)) {
            return !empty($this->config->notificationusedefaults);
        }

        // If this setting does not exist, use defaults for backwards compatibility.
        return true;
    }

    /**
     * @return bool True if default scheduling settings should be used.
     */
    public function use_schedule_defaults(): bool {
        if (isset($this->config->scheduleusedefaults)) {
            return !empty($this->config->scheduleusedefaults);
        }

        // If this setting does not exist, use defaults for backwards compatibility.
        return true;
    }

    /**
     * Get the value of the file access config setting for this element, checking whether
     * default settings should apply.
     *
     * @return int FILE_ACCESS_UPLOAD or FILE_ACCESS_DIRECTORY
     * @throws totara_sync_exception If this setting has not been set (including if the default has not been
     *    set and the default should be used).
     */
    public function get_fileaccess() {
        if ($this->use_fileaccess_defaults()) {
            $default = get_config('totara_sync', 'fileaccess');
            // false is returned if no config was found.
            if ($default !== false) {
                return $default;
            }
        } else if (isset($this->config->fileaccess)) {
            return $this->config->fileaccess;
        }

        throw new totara_sync_exception($this->get_name(), 'settings', 'fileaccessnotset');
    }

    /**
     * Get the value of the filesdir config setting for this element, checking whether default settings
     * should apply.
     *
     * @return string Path set for accessing files on the server to be imported.
     * @throws totara_sync_exception If this setting has not been set (including if the default has not been
     *    set and the default should be used).
     */
    public function get_filesdir() {
        if ($this->use_fileaccess_defaults()) {
            $default = get_config('totara_sync', 'filesdir');
            // false is returned if no config was found.
            if ($default !== false) {
                return $default;
            }
        } else if (isset($this->config->filesdir)) {
            return $this->config->filesdir;
        }

        throw new totara_sync_exception($this->get_name(), 'settings', 'filesdirnotset');
    }
}
