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
 * restore user interface stages
 *
 * This file contains the classes required to manage the stages that make up the
 * restore user interface.
 * These will be primarily operated a {@link restore_ui} instance.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Abstract stage class
 *
 * This class should be extended by all restore stages (a requirement of many restore ui functions).
 * Each stage must then define two abstract methods
 *  - process : To process the stage
 *  - initialise_stage_form : To get a restore_moodleform instance for the stage
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class restore_ui_stage extends base_ui_stage {
    /**
     * The restore id from the restore controller
     * @return string
     */
    final public function get_restoreid() {
        return $this->get_uniqueid();
    }

    /**
     * This is an independent stage
     * @return int
     */
    final public function is_independent() {
        return false;
    }

    /**
     * No sub stages for this stage
     * @return false
     */
    public function has_sub_stages() {
        return false;
    }

    /**
     * The name of this stage
     * @return string
     */
    final public function get_name() {
        return get_string('restorestage'.$this->stage, 'backup');
    }

    /**
     * Returns true if this is the settings stage
     * @return bool
     */
    final public function is_first_stage() {
        return $this->stage == restore_ui::STAGE_SETTINGS;
    }

    /**
     * Returns list of supported backupo file mimetypes.
     *
     * @return string[]
     */
    final public static function get_allowed_mimetypes() {
        return array(
            'application/vnd.moodle.backup', // Totara 2 backup.
            'application/vnd.ims.imsccv1p1', // IMSCC archive.
            'application/zip', // Totara 1 backup files.
        );
    }
}

/**
 * Abstract class used to represent a restore stage that is indenependent.
 *
 * An independent stage is a judged to be so because it doesn't require, and has
 * no use for the restore controller.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @author    Petr Skoda <petr.skoda@totaralearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class restore_ui_independent_stage {
    const DESTINATION_NEW = 'new';
    const DESTINATION_EXISTING = 'existing';
    const DESTINATION_CURRENT = 'current';

    /**
     * The context ID.
     * @var int
     */
    protected $contextid;

    /**
     * The backup file to be restored.
     * @var stored_file
     */
    protected $backupfile;

    /**
     * Automated course backup file stored in external directory.
     * @var string
     */
    protected $externalfile;

    /**
     * Restore destination.
     * @var int
     */
    protected $destination;

    /**
     * @var \core\progress\base Optional progress reporter
     */
    private $progressreporter;

    /**
     * Constructs the restore stage.
     * @param int $contextid
     */
    public function __construct($contextid) {
        $this->contextid = $contextid;
        $this->destination = optional_param('destination', null, PARAM_ALPHA);
        $this->backupfile = $this->get_backup_file();
        if (!$this->backupfile) {
            $this->externalfile = $this->get_external_file();
            if (!$this->externalfile) {
                $errorurl = new moodle_url('/backup/restorefile.php', array('contextid' => $this->contextid));
                throw new moodle_exception('invalidparameter', 'debug', $errorurl, null, 'No valid backup file to be restored specified');
            }
        }
    }

    /**
     * Get backup file instance and validate current user may restore it.
     *
     * @return stored_file|null
     */
    protected function get_backup_file() {
        global $DB;

        // NOTE: the access control must match /backup/restorefile.php logic!

        $context = context::instance_by_id($this->contextid);
        require_capability('moodle/restore:restorefile', $context);

        $backupfileid = optional_param('backupfileid', null, PARAM_INT);
        if (!$backupfileid) {
            return null;
        }

        $filerecord = $DB->get_record('files', array('id' => $backupfileid));
        if (!$filerecord) {
            debugging('Backup file does not exist', DEBUG_DEVELOPER);
            return null;
        }

        $syscontext = context_system::instance();
        $filecontext = context::instance_by_id($filerecord->contextid);
        $file = get_file_storage()->get_file_instance($filerecord);

        if (!\backup_helper::is_trusted_backup($file)) {
            require_capability('moodle/restore:restoreuntrusted', $context);
        }

        if ($filerecord->component === 'backup') {
            if ($filecontext->id != $this->contextid) {
                debugging('Invalid file context', DEBUG_DEVELOPER);
                return null;
            }
            if ($filerecord->filearea === 'course') {
                if ($filecontext->contextlevel != CONTEXT_COURSE) {
                    debugging('Invalid context level', DEBUG_DEVELOPER);
                    return null;
                }
                return $file;
            }
            if ($filerecord->filearea === 'section') {
                if ($filecontext->contextlevel != CONTEXT_COURSE)  {
                    debugging('Invalid context level', DEBUG_DEVELOPER);
                    return null;
                }
                return $file;
            }
            if ($filerecord->filearea === 'activity') {
                if ($filecontext->contextlevel != CONTEXT_MODULE)  {
                    debugging('Invalid context level', DEBUG_DEVELOPER);
                    return null;
                }
                return $file;
            }
            if ($filerecord->filearea === 'automated') {
                if ($filecontext->contextlevel != CONTEXT_COURSE)  {
                    debugging('Invalid context level', DEBUG_DEVELOPER);
                    return null;
                }
                $config = get_config('backup');
                if ($config->backup_auto_active == 0) {
                    debugging('Automated backups are disabled', DEBUG_DEVELOPER);
                    return null;
                }
                require_capability('moodle/restore:viewautomatedfilearea', $filecontext);
                if (has_capability('moodle/site:config', $syscontext)) {
                    // Admin can always restore if automated backups enabled.
                    return $file;
                }
                if ($config->backup_auto_storage == backup_cron_automated_helper::STORAGE_COURSE
                    or $config->backup_auto_storage == backup_cron_automated_helper::STORAGE_COURSE_AND_DIRECTORY) {
                    return $file;
                }
                debugging('Cannot access automated course backup files', DEBUG_DEVELOPER);
                return null;
            }
            debugging('Invalid backup file area', DEBUG_DEVELOPER);
        }

        if ($filerecord->component === 'user') {
            // NOTE: the access control here is a bit weird, users needs at least one area
            //       with restore file capability in oder to be able to upload or use private area.
            if ($filecontext->contextlevel != CONTEXT_USER)  {
                debugging('Invalid context level', DEBUG_DEVELOPER);
                return null;
            }
            if ($filerecord->filearea === 'draft') {
                require_capability('moodle/restore:uploadfile', $context);
                return $file;
            }
            if ($filerecord->filearea === 'backup') {
                return $file;
            }
            debugging('Invalid user file area', DEBUG_DEVELOPER);
            return null;
        }

        debugging('Invalid file component', DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Get backup file instance and validate current user may restore it.
     *
     * @return string|null full file path
     */
    public function get_external_file() {
        $context = context::instance_by_id($this->contextid);
        require_capability('moodle/restore:restorefile', $context);
        require_capability('moodle/restore:viewautomatedfilearea', $context);

        if ($context->contextlevel != CONTEXT_COURSE) {
            debugging('Invalid context level for external file', DEBUG_DEVELOPER);
            return null;
        }

        $config = get_config('backup');
        if ($config->backup_auto_active == 0) {
            debugging('Automated backup is disabled', DEBUG_DEVELOPER);
            return null;
        }

        if ($config->backup_auto_storage != backup_cron_automated_helper::STORAGE_DIRECTORY) {
            debugging('Automated backup is not configured to use external directory only', DEBUG_DEVELOPER);
            return null;
        }

        if (!$config->backup_auto_destination or !file_exists($config->backup_auto_destination)) {
            debugging('backup_auto_destination is not valid', DEBUG_DEVELOPER);
            return null;
        }

        $externalfilename = optional_param('externalfilename', null, PARAM_FILE);
        if (!$externalfilename) {
            return null;
        }

        $totararegex = \backup_cron_automated_helper::get_external_file_regex($context->instanceid, false);
        $oldregex = \backup_cron_automated_helper::get_external_file_regex($context->instanceid, true);

        if (!preg_match($totararegex, $externalfilename)) {
            if (!preg_match($oldregex, $externalfilename)) {
                debugging('Invalid file name for the context', DEBUG_DEVELOPER);
                return null;
            }
        }

        $externalfile = $config->backup_auto_destination . '/' . $externalfilename;
        if (!file_exists($externalfile)) {
            debugging('External automated backup file does not exist', DEBUG_DEVELOPER);
            return null;
        }

        if (!\backup_helper::is_trusted_backup($externalfile)) {
            require_capability('moodle/restore:restoreuntrusted', $context);
        }

        return $externalfile;
    }

    /**
     * Processes the current restore stage.
     * @return mixed
     */
    public function process() {
        // Not used!
        return null;
    }

    /**
     * Displays this restore stage.
     * @param core_backup_renderer $renderer
     * @return mixed
     */
    abstract public function display(core_backup_renderer $renderer);

    /**
     * Returns the current restore stage.
     * @return int
     */
    abstract public function get_stage();

    /**
     * Gets the progress reporter object in use for this restore UI stage.
     *
     * IMPORTANT: This progress reporter is used only for UI progress that is
     * outside the restore controller. The restore controller has its own
     * progress reporter which is used for progress during the main restore.
     * Use the restore controller's progress reporter to report progress during
     * a restore operation, not this one.
     *
     * This extra reporter is necessary because on some restore UI screens,
     * there are long-running tasks even though there is no restore controller
     * in use. There is a similar function in restore_ui. but that class is not
     * used on some stages.
     *
     * @return \core\progress\none
     */
    public function get_progress_reporter() {
        if (!$this->progressreporter) {
            $this->progressreporter = new \core\progress\none();
        }
        return $this->progressreporter;
    }

    /**
     * Sets the progress reporter that will be returned by get_progress_reporter.
     *
     * @param \core\progress\base $progressreporter Progress reporter
     */
    public function set_progress_reporter(\core\progress\base $progressreporter) {
        $this->progressreporter = $progressreporter;
    }

    /**
     * Gets an array of progress bar items that can be displayed through the restore renderer.
     * @return array Array of items for the progress bar
     */
    public function get_progress_bar() {
        $stage = restore_ui::STAGE_COMPLETE;
        $currentstage = $this->get_stage();
        $items = array();
        while ($stage > 0) {
            $classes = array('backup_stage');
            if (floor($stage / 2) == $currentstage) {
                $classes[] = 'backup_stage_next';
            } else if ($stage == $currentstage) {
                $classes[] = 'backup_stage_current';
            } else if ($stage < $currentstage) {
                $classes[] = 'backup_stage_complete';
            }
            $item = array('text' => strlen(decbin($stage)).'. '.get_string('restorestage'.$stage, 'backup'), 'class' => join(' ', $classes));
            // Totara: no stage change links!
            array_unshift($items, $item);
            $stage = floor($stage / 2);
        }
        return $items;
    }

    /**
     * Returns the restore stage name.
     * @return string
     */
    abstract public function get_stage_name();

    /**
     * Obviously true
     * @return true
     */
    final public function is_independent() {
        return true;
    }

    /**
     * Handles the destruction of this object.
     */
    public function destroy() {
        // Nothing to destroy here!.
    }
}

/**
 * The confirmation stage.
 *
 * This is the first stage, it is independent.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @author    Petr Skoda <petr.skoda@totaralearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_stage_confirm extends restore_ui_independent_stage {

    /**
     * Renders the confirmation stage screen
     *
     * @param core_backup_renderer $renderer renderer instance to use
     * @return string HTML code
     */
    public function display(core_backup_renderer $renderer) {
        global $USER, $CFG;

        $prevstageurl = new moodle_url('/backup/restorefile.php', array('contextid' => $this->contextid));

        $nextparams = array(
            'contextid' => $this->contextid,
            'stage' => restore_ui::STAGE_DESTINATION,
            'sesskey' => sesskey(),
        );
        if ($this->backupfile) {
            $backupfile = $this->backupfile;
            $nextparams['backupfileid'] = $this->backupfile->get_id();
        } else {
            $nextparams['externalfilename'] = basename($this->externalfile);
            $backupfile = $this->externalfile;
        }
        try {
            // Note: do not bother with progress bar here, this should be relatively fast,
            //       a bit slower is much better here than consuming temp space unnecessarily.
            $details = \backup_general_helper::get_backup_information_from_mbz($backupfile);
        } catch (Throwable $ex) {
            // Fallback to full extract for CC converters.
            $details = null;
        }
        if ($details) {
            $nextparams['backupformat'] = $details->format;
            $nextparams['backuptype'] = $details->type;
            if ($details->format === backup::FORMAT_MOODLE) {
                $nextstageurl = new moodle_url('/backup/restore.php', $nextparams);
                $destinations = self::get_destinations($details->type, $this->contextid);
                return $renderer->backup_details($details, $nextstageurl, $backupfile, $destinations, $this->destination);
            }
        }

        // This is going to be slow, but nobody should really care about huge non-standard backups...
        ignore_user_abort(true);
        $filepath = restore_controller::get_tempdir_name($this->contextid, $USER->id);
        try {
            $fb = get_file_packer('application/vnd.moodle.backup');
            $fb->extract_to_pathname($backupfile, $CFG->tempdir . '/backup/' . $filepath . '/', null);
            $format = backup_general_helper::detect_backup_format($filepath);
        } catch (Throwable $ex) {
            $format = backup::FORMAT_UNKNOWN;
        }
        remove_dir($CFG->tempdir . '/backup/' . $filepath . '/');
        ignore_user_abort(false);

        if ($format === backup::FORMAT_MOODLE or $format == backup::FORMAT_UNKNOWN) {
            // Unknown format - we can't do anything here.
            return $renderer->backup_details_unknown($prevstageurl);
        }

        // Non-standard format to be converted.
        $details = (object)array('format' => $format, 'type' => backup::TYPE_1COURSE); // todo type to be returned by a converter
        $nextparams['backupformat'] = $details->format;
        $nextparams['backuptype'] = $details->type;
        $nextstageurl = new moodle_url('/backup/restore.php', $nextparams);
        $destinations = self::get_destinations($details->type, $this->contextid);
        return $renderer->backup_details_nonstandard($details, $nextstageurl, $backupfile, $destinations, $this->destination);
    }

    /**
     * The restore stage name.
     * @return string
     * @throws coding_exception
     */
    public function get_stage_name() {
        return get_string('restorestage1', 'backup');
    }

    /**
     * The restore stage this class is for.
     * @return int
     */
    public function get_stage() {
        return restore_ui::STAGE_CONFIRM;
    }

    /**
     * Returns destinations the current user may restore into.
     *
     * @param string $type
     * @param int $contextid
     * @return array
     */
    public static function get_destinations($type, $contextid) {
        list($context, $course, $cm) = get_context_info_array($contextid);

        $destinations = array();
        if ($type == backup::TYPE_1COURSE) {
            if (self::may_have_capability('moodle/course:create')) {
                $destinations[self::DESTINATION_NEW] = get_string('restoretonewcourse', 'backup');
            }
            if (self::may_have_capability('moodle/restore:restorecourse')) {
                $destinations[self::DESTINATION_EXISTING] = get_string('restoretoexistingcourse', 'backup');
            }
            if ($context->contextlevel == CONTEXT_COURSE) {
                if (has_capability('moodle/restore:restorecourse', $context)) {
                    $destinations[self::DESTINATION_CURRENT] = get_string('restoretocurrentcourse', 'backup');
                }
            }

        } else if ($type == backup::TYPE_1ACTIVITY) {
            if (self::may_have_capability('moodle/restore:restoreactivity')) {
                $destinations[self::DESTINATION_EXISTING] = get_string('restoretoexistingcourse', 'backup');
            }
            if ($context->contextlevel == CONTEXT_COURSE or $context->contextlevel == CONTEXT_MODULE) {
                $coursecontext = context_course::instance($course->id);
                if (has_capability('moodle/restore:restoreactivity', $coursecontext)) {
                    $destinations[self::DESTINATION_CURRENT] = get_string('restoretocurrentcourse', 'backup');
                }
            }

        } else if ($type == backup::TYPE_1SECTION) {
            if (self::may_have_capability('moodle/restore:restoresection')) {
                $destinations[self::DESTINATION_EXISTING] = get_string('restoretoexistingcourse', 'backup');
            }
            if ($context->contextlevel == CONTEXT_COURSE) {
                if (has_capability('moodle/restore:restoresection', $context)) {
                    $destinations[self::DESTINATION_CURRENT] = get_string('restoretocurrentcourse', 'backup');
                }
            }
        }

        return $destinations;
    }

    /**
     * Guess if this user may theoretically have capability anywhere in the system.
     *
     * @param string $capability
     * @return bool
     */
    public static function may_have_capability($capability) {
        global $DB, $USER, $CFG;

        if (has_capability($capability, context_system::instance())) {
            // Fast shortcut for managers.
            return true;
        }

        $roles = get_roles_with_capability($capability, CAP_ALLOW);
        if (!$roles) {
            return false;
        }

        if (!empty($CFG->defaultuserroleid)) {
            if (isset($roles[$CFG->defaultuserroleid])) {
                return true;
            }
        }

        list($roleids, $params) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
        $params['userid'] = $USER->id;

        $sql = "SELECT COUNT('x')
                  FROM {role_assignments} ra
                 WHERE ra.userid = :userid AND ra.roleid $roleids";

        $count = $DB->count_records_sql($sql, $params);

        return ($count > 0);
    }

}

/**
 * This is the destination stage.
 *
 * This stage is the second stage and is also independent
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @author    Petr Skoda <petr.skoda@totaralearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_stage_destination extends restore_ui_independent_stage implements file_progress {
    /** @var string */
    protected $format;

    /** @var string */
    protected $type;

    /** @var bool $deletedata */
    protected $deletedata;

    /** @var int $targetid */
    protected $targetid = null;

    /**
     * @var bool True if we have started reporting progress
     */
    protected $startedprogress = false;

    /**
     * Stage constructor.
     * @param int $contextid
     */
    public function __construct($contextid) {
        parent::__construct($contextid);

        $this->format = required_param('backupformat', PARAM_ALPHANUM);
        $this->type = required_param('backuptype', PARAM_ALPHANUM);
        $this->deletedata = optional_param('deletedata', 0, PARAM_INT);
        if ($this->destination == self::DESTINATION_CURRENT) {
            list($context, $course, $cm) = get_context_info_array($this->contextid);
            $this->targetid = $course->id;
        } else {
            $this->targetid = optional_param('targetid', 0, PARAM_INT);
        }
    }

    /**
     * Are we ready to create controller for the settings stage?
     * @return bool
     */
    public function is_target_selected() {
        if (optional_param('searchcourses', false, PARAM_BOOL)) {
            return false;
        }

        return !empty($this->targetid);
    }

    /**
     * Renders the destination stage screen
     *
     * @param core_backup_renderer $renderer renderer instance to use
     * @return string HTML code
     */
    public function display(core_backup_renderer $renderer) {
        $nextparams = array(
            'contextid' => $this->contextid,
            'stage' => restore_ui::STAGE_SETTINGS,
            'backupformat' => $this->format,
            'backuptype' => $this->type,
            'destination' => $this->destination,
            'sesskey' => sesskey(),
        );
        if ($this->backupfile) {
            $nextparams['backupfileid'] = $this->backupfile->get_id();
        } else {
            $nextparams['externalfilename'] = basename($this->externalfile);
        }
        $nextstageurl = new moodle_url('/backup/restore.php', $nextparams);

        $prevparams = array(
            'contextid' => $this->contextid,
            'stage' => restore_ui::STAGE_CONFIRM,
            'destination' => $this->destination,
            'sesskey' => sesskey(),
        );
        if ($this->backupfile) {
            $prevparams['backupfileid'] = $this->backupfile->get_id();
        } else {
            $prevparams['externalfilename'] = basename($this->externalfile);
        }
        $prevstageurl = new moodle_url('/backup/restore.php', $prevparams);

        if ($this->destination == self::DESTINATION_NEW) {
            $categorysearch = new restore_category_search(array('url' => $nextstageurl));
            return $renderer->course_selector_new($this->type, $nextstageurl, $prevstageurl, $categorysearch);

        } else if ($this->destination == self::DESTINATION_CURRENT) {
            $nextstageurl->param('targetid', $this->targetid);
            return $renderer->course_selector_current($this->type, $nextstageurl, $prevstageurl);

        } else if ($this->destination == self::DESTINATION_EXISTING) {
            $coursesearch = new restore_course_search(array('url' => $nextstageurl));
            return $renderer->course_selector_existing($this->type, $nextstageurl, $prevstageurl, $coursesearch);

        } else {
            throw new invalid_parameter_exception('unknown restore destination');
        }
    }

    /**
     * Returns the stage name.
     * @return string
     * @throws coding_exception
     */
    public function get_stage_name() {
        return get_string('restorestage2', 'backup');
    }

    /**
     * Returns the current restore stage
     * @return int
     */
    public function get_stage() {
        return restore_ui::STAGE_DESTINATION;
    }

    /**
     * Implementation for file_progress interface to display unzip progress.
     *
     * @param int $progress Current progress
     * @param int $max Max value
     */
    public function progress($progress = file_progress::INDETERMINATE, $max = file_progress::INDETERMINATE) {
        $reporter = $this->get_progress_reporter();

        // Start tracking progress if necessary.
        if (!$this->startedprogress) {
            $reporter->start_progress('extract_backup_archive',
                ($max == file_progress::INDETERMINATE) ? \core\progress\base::INDETERMINATE : $max);
            $this->startedprogress = true;
        }

        // Pass progress through to whatever handles it.
        $reporter->progress(
            ($progress == file_progress::INDETERMINATE) ? \core\progress\base::INDETERMINATE : $progress);
    }

    /**
     * Create restore controller using backup file and selected target.
     *
     * @return restore_controller
     */
    public function create_restore_controller() {
        global $USER, $CFG;

        // Access control first!!!
        if ($this->destination == self::DESTINATION_NEW) {
            $categorycontext = context_coursecat::instance($this->targetid);
            require_capability('moodle/course:create', $categorycontext);
        } else {
            $coursecontext = context_course::instance($this->targetid);
            if ($this->type == backup::TYPE_1COURSE) {
                require_capability('moodle/restore:restorecourse', $coursecontext);
            } else if ($this->type == backup::TYPE_1ACTIVITY) {
                require_capability('moodle/restore:restoreactivity', $coursecontext);
            } else if ($this->type == backup::TYPE_1SECTION) {
                require_capability('moodle/restore:restoresection', $coursecontext);
            } else {
                throw new invalid_parameter_exception('unknown backup type');
            }
        }

        // The unpack the archive.
        if ($this->backupfile) {
            $backupfile = $this->backupfile;
        } else {
            $backupfile = $this->externalfile;
        }

        $filepath = restore_controller::get_tempdir_name($this->contextid, $USER->id);
        $fb = get_file_packer('application/vnd.moodle.backup');
        $tempdir = $CFG->tempdir . '/backup/' . $filepath . '/';
        $result = $fb->extract_to_pathname($backupfile, $tempdir, null, $this);
        // If any progress happened, end it.
        if ($this->startedprogress) {
            $this->get_progress_reporter()->end_progress();
            $this->startedprogress = false;
        }
        if (!$result) {
            remove_dir($tempdir);
            throw new restore_ui_exception('invalidrestorefile');
        }

        // Make sure nobody hacked the type or format!
        $format = backup_general_helper::detect_backup_format($filepath);
        if ($format != $this->format) {
            remove_dir($tempdir);
            throw new invalid_parameter_exception('invalid format parameter');
        }
        if ($format === backup::FORMAT_MOODLE) {
            // Standard Moodle 2 format, let use get the type of the backup.
            $details = backup_general_helper::get_backup_information($filepath);
            if ($this->type != $details->type) {
                remove_dir($tempdir);
                throw new invalid_parameter_exception('invalid type parameter');
            }
            unset($details);
        } else {
            // Non-standard format to be converted. We assume it contains the
            // whole course for now. However, in the future there might be a callback
            // to the installed converters.
            if ($this->type != backup::TYPE_1COURSE) {
                remove_dir($tempdir);
                throw new invalid_parameter_exception('invalid type parameter');
            }
        }

        if ($this->destination == self::DESTINATION_NEW) {
            // Create the course.
            list($fullname, $shortname) = restore_dbops::calculate_course_names(0, get_string('restoringcourse', 'backup'), get_string('restoringcourseshortname', 'backup'));
            $courseid = restore_dbops::create_new_course($fullname, $shortname, $this->targetid);
            $target = backup::TARGET_NEW_COURSE;

        } else if ($this->destination == self::DESTINATION_CURRENT) {
            if ($this->deletedata and $this->type == backup::TYPE_1COURSE) {
                $target = backup::TARGET_CURRENT_DELETING;
            } else {
                $target = backup::TARGET_CURRENT_ADDING;
            }
            $courseid = $coursecontext->instanceid;

        } else if ($this->destination == self::DESTINATION_EXISTING) {
            if ($this->deletedata and $this->type == backup::TYPE_1COURSE) {
                $target = backup::TARGET_EXISTING_DELETING;
            } else {
                $target = backup::TARGET_EXISTING_ADDING;
            }
            $courseid = $this->targetid;

        } else {
            throw new invalid_parameter_exception('unknown restore destination');
        }

        // Finally create the controller.
        return new restore_controller($filepath, $courseid, backup::INTERACTIVE_YES, backup::MODE_GENERAL, $USER->id, $target);
    }
}

/**
 * This stage is the settings stage.
 *
 * This stage is the third stage, it is dependent on a restore controller and
 * is the first stage as such.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_stage_settings extends restore_ui_stage {
    /**
     * Initial restore stage constructor
     * @param restore_ui $ui
     * @param array $params
     */
    public function __construct(restore_ui $ui, array $params = null) {
        $this->stage = restore_ui::STAGE_SETTINGS;
        parent::__construct($ui, $params);
    }

    /**
     * Process the settings stage.
     *
     * @param base_moodleform $form
     * @return bool|int
     */
    public function process(base_moodleform $form = null) {
        $form = $this->initialise_stage_form();

        if ($form->is_cancelled()) {
            $this->ui->cancel_process();
        }

        $data = $form->get_data();
        if ($data && confirm_sesskey()) {
            $tasks = $this->ui->get_tasks();
            $changes = 0;
            foreach ($tasks as &$task) {
                // We are only interesting in the backup root task for this stage.
                if ($task instanceof restore_root_task || $task instanceof restore_course_task) {
                    // Get all settings into a var so we can iterate by reference.
                    $settings = $task->get_settings();
                    foreach ($settings as &$setting) {
                        $name = $setting->get_ui_name();
                        if (isset($data->$name) &&  $data->$name != $setting->get_value()) {
                            $setting->set_value($data->$name);
                            $changes++;
                        } else if (!isset($data->$name) && $setting->get_ui_type() == backup_setting::UI_HTML_CHECKBOX && $setting->get_value()) {
                            $setting->set_value(0);
                            $changes++;
                        }
                    }
                }
            }
            // Return the number of changes the user made.
            return $changes;
        } else {
            return false;
        }
    }

    /**
     * Initialise the stage form.
     *
     * @return backup_moodleform|base_moodleform|restore_settings_form
     * @throws coding_exception
     */
    protected function initialise_stage_form() {
        global $PAGE;
        if ($this->stageform === null) {
            $form = new restore_settings_form($this, $PAGE->url);
            // Store as a variable so we can iterate by reference.
            $tasks = $this->ui->get_tasks();
            $headingprinted = false;
            // Iterate all tasks by reference.
            foreach ($tasks as &$task) {
                // For the initial stage we are only interested in the root settings.
                if ($task instanceof restore_root_task) {
                    if (!$headingprinted) {
                        $form->add_heading('rootsettings', get_string('restorerootsettings', 'backup'));
                        $headingprinted = true;
                    }
                    $settings = $task->get_settings();
                    // First add all settings except the filename setting.
                    foreach ($settings as &$setting) {
                        if ($setting->get_name() == 'filename') {
                            continue;
                        }
                        $form->add_setting($setting, $task);
                    }
                    // Then add all dependencies.
                    foreach ($settings as &$setting) {
                        if ($setting->get_name() == 'filename') {
                            continue;
                        }
                        $form->add_dependencies($setting);
                    }
                }
            }
            $this->stageform = $form;
        }
        // Return the form.
        return $this->stageform;
    }
}

/**
 * Schema stage of backup process
 *
 * During the schema stage the user is required to set the settings that relate
 * to the area that they are backing up as well as its children.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_stage_schema extends restore_ui_stage {
    /**
     * @var int Maximum number of settings to add to form at once
     */
    const MAX_SETTINGS_BATCH = 1000;

    /**
     * Schema stage constructor
     * @param restore_ui $ui
     * @param array $params
     */
    public function __construct(restore_ui $ui, array $params = null) {
        $this->stage = restore_ui::STAGE_SCHEMA;
        parent::__construct($ui, $params);
    }

    /**
     * Processes the schema stage
     *
     * @param base_moodleform $form
     * @return int The number of changes the user made
     */
    public function process(base_moodleform $form = null) {
        $form = $this->initialise_stage_form();
        // Check it wasn't cancelled.
        if ($form->is_cancelled()) {
            $this->ui->cancel_process();
        }

        // Check it has been submit.
        $data = $form->get_data();
        if ($data && confirm_sesskey()) {
            // Get the tasks into a var so we can iterate by reference.
            $tasks = $this->ui->get_tasks();
            $changes = 0;
            // Iterate all tasks by reference.
            foreach ($tasks as &$task) {
                // We are only interested in schema settings.
                if (!($task instanceof restore_root_task)) {
                    // Store as a variable so we can iterate by reference.
                    $settings = $task->get_settings();
                    // Iterate by reference.
                    foreach ($settings as &$setting) {
                        $name = $setting->get_ui_name();
                        if (isset($data->$name) &&  $data->$name != $setting->get_value()) {
                            $setting->set_value($data->$name);
                            $changes++;
                        } else if (!isset($data->$name) && $setting->get_ui_type() == backup_setting::UI_HTML_CHECKBOX && $setting->get_value()) {
                            $setting->set_value(0);
                            $changes++;
                        }
                    }
                }
            }
            // Return the number of changes the user made.
            return $changes;
        } else {
            return false;
        }
    }

    /**
     * Creates the backup_schema_form instance for this stage
     *
     * @return backup_schema_form
     */
    protected function initialise_stage_form() {
        global $PAGE;
        if ($this->stageform === null) {
            $form = new restore_schema_form($this, $PAGE->url);
            $tasks = $this->ui->get_tasks();
            $courseheading = false;

            // Track progress through each stage.
            $progress = $this->ui->get_progress_reporter();
            $progress->start_progress('Initialise schema stage form', 3);

            $progress->start_progress('', count($tasks));
            $done = 1;
            $allsettings = array();
            foreach ($tasks as $task) {
                if (!($task instanceof restore_root_task)) {
                    if (!$courseheading) {
                        // If we haven't already display a course heading to group nicely.
                        $form->add_heading('coursesettings', get_string('coursesettings', 'backup'));
                        $courseheading = true;
                    }
                    // Put each setting into an array of settings to add. Adding
                    // a setting individually is a very slow operation, so we add.
                    // them all in a batch later on.
                    foreach ($task->get_settings() as $setting) {
                        $allsettings[] = array($setting, $task);
                    }
                } else if ($this->ui->enforce_changed_dependencies()) {
                    // Only show these settings if dependencies changed them.
                    // Add a root settings heading to group nicely.
                    $form->add_heading('rootsettings', get_string('rootsettings', 'backup'));
                    // Iterate all settings and add them to the form as a fixed
                    // setting. We only want schema settings to be editable.
                    foreach ($task->get_settings() as $setting) {
                        if ($setting->get_name() != 'filename') {
                            $form->add_fixed_setting($setting, $task);
                        }
                    }
                }
                // Update progress.
                $progress->progress($done++);
            }
            $progress->end_progress();

            // Add settings for tasks in batches of up to 1000. Adding settings
            // in larger batches improves performance, but if it takes too long,
            // we won't be able to update the progress bar so the backup might.
            // time out. 1000 is chosen to balance this.
            $numsettings = count($allsettings);
            $progress->start_progress('', ceil($numsettings / self::MAX_SETTINGS_BATCH));
            $start = 0;
            $done = 1;
            while ($start < $numsettings) {
                $length = min(self::MAX_SETTINGS_BATCH, $numsettings - $start);
                $form->add_settings(array_slice($allsettings, $start, $length));
                $start += $length;
                $progress->progress($done++);
            }
            $progress->end_progress();

            // Add the dependencies for all the settings.
            $progress->start_progress('', count($allsettings));
            $done = 1;
            foreach ($allsettings as $settingtask) {
                $form->add_dependencies($settingtask[0]);
                $progress->progress($done++);
            }
            $progress->end_progress();

            $progress->end_progress();
            $this->stageform = $form;
        }
        return $this->stageform;
    }
}

/**
 * Confirmation stage
 *
 * On this stage the user reviews the setting for the backup and can change the filename
 * of the file that will be generated.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_stage_review extends restore_ui_stage {

    /**
     * Constructs the stage
     * @param restore_ui $ui
     * @param array $params
     */
    public function __construct($ui, array $params = null) {
        $this->stage = restore_ui::STAGE_REVIEW;
        parent::__construct($ui, $params);
    }

    /**
     * Processes the confirmation stage
     *
     * @param base_moodleform $form
     * @return int The number of changes the user made
     */
    public function process(base_moodleform $form = null) {
        $form = $this->initialise_stage_form();
        // Check it hasn't been cancelled.
        if ($form->is_cancelled()) {
            $this->ui->cancel_process();
        }

        $data = $form->get_data();
        if ($data && confirm_sesskey()) {
            return 0;
        } else {
            return false;
        }
    }
    /**
     * Creates the backup_confirmation_form instance this stage requires
     *
     * @return backup_confirmation_form
     */
    protected function initialise_stage_form() {
        global $PAGE, $DB;
        if ($this->stageform === null) {
            // Get the form.
            $form = new restore_review_form($this, $PAGE->url);
            $courseheading = false;

            $progress = $this->ui->get_progress_reporter();
            $tasks = $this->ui->get_tasks();
            $progress->start_progress('initialise_stage_form', count($tasks));

            // Totara: add destination info.
            $form->add_heading('destination', get_string('restorestage2', 'backup'));
            /** @var restore_ui $ui */
            $ui = $this->get_ui();
            $target = $ui->get_target();
            $courseid = $ui->get_courseid();
            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
            $category = $DB->get_record('course_categories', array('id' => $course->category));

            if ($target == backup::TARGET_NEW_COURSE) {
                $form->_form->addElement('static', 'target', get_string('restoretarget', 'backup'), get_string('restoretonewcourse', 'backup'));
                if ($category) {
                    $form->_form->addElement('static', 'target', get_string('coursecategory', 'core'), format_string($category->name));
                }

            } else if ($target == backup::TARGET_CURRENT_ADDING) {
                $form->_form->addElement('static', 'target', get_string('restoretarget', 'backup'), get_string('restoretocurrentcourseadding', 'backup'));

            } else if ($target == backup::TARGET_CURRENT_DELETING) {
                $form->_form->addElement('static', 'target', get_string('restoretarget', 'backup'), get_string('restoretocurrentcoursedeleting', 'backup'));

            } else if ($target == backup::TARGET_EXISTING_ADDING) {
                $form->_form->addElement('static', 'target', get_string('restoretarget', 'backup'), get_string('restoretoexistingcourseadding', 'backup'));
                $form->_form->addElement('static', 'target', get_string('restoretocourse', 'backup'), format_string($course->fullname));
                if ($category) {
                    $form->_form->addElement('static', 'target', get_string('coursecategory', 'core'), format_string($category->name));
                }

            } else if ($target == backup::TARGET_EXISTING_DELETING) {
                $form->_form->addElement('static', 'target', get_string('restoretarget', 'backup'), get_string('restoretoexistingcoursedeleting', 'backup'));
                $form->_form->addElement('static', 'target', get_string('restoretocourse', 'backup'), format_string($course->fullname));
                if ($category) {
                    $form->_form->addElement('static', 'target', get_string('coursecategory', 'core'), format_string($category->name));
                }
            }

            $done = 1;
            foreach ($tasks as $task) {
                if ($task instanceof restore_root_task) {
                    // If its a backup root add a root settings heading to group nicely.
                    $form->add_heading('rootsettings', get_string('restorerootsettings', 'backup'));
                } else if (!$courseheading) {
                    // We haven't already add a course heading.
                    $form->add_heading('coursesettings', get_string('coursesettings', 'backup'));
                    $courseheading = true;
                }
                // Iterate all settings, doesnt need to happen by reference.
                foreach ($task->get_settings() as $setting) {
                    $form->add_fixed_setting($setting, $task);
                }
                // Update progress.
                $progress->progress($done++);
            }
            $progress->end_progress();
            $this->stageform = $form;
        }
        return $this->stageform;
    }
}

/**
 * Final stage of backup
 *
 * This stage is special in that it is does not make use of a form. The reason for
 * this is the order of procession of backup at this stage.
 * The processesion is:
 * 1. The final stage will be intialise.
 * 2. The confirmation stage will be processed.
 * 3. The backup will be executed
 * 4. The complete stage will be loaded by execution
 * 5. The complete stage will be displayed
 *
 * This highlights that we neither need a form nor a display method for this stage
 * we simply need to process.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_stage_process extends restore_ui_stage {

    /**
     * There is no substage required.
     */
    const SUBSTAGE_NONE = 0;

    /**
     * The prechecks substage is required/the current substage.
     */
    const SUBSTAGE_PRECHECKS = 2;

    /**
     * The current substage.
     * @var int
     */
    protected $substage = 0;

    /**
     * Constructs the final stage
     * @param base_ui $ui
     * @param array $params
     */
    public function __construct(base_ui $ui, array $params = null) {
        $this->stage = restore_ui::STAGE_PROCESS;
        parent::__construct($ui, $params);
    }
    /**
     * Processes the final stage.
     *
     * In this case it checks to see if there is a sub stage that we need to display
     * before execution, if there is we gear up to display the subpage, otherwise
     * we return true which will lead to execution of the restore and the loading
     * of the completed stage.
     *
     * @param base_moodleform $form
     */
    public function process(base_moodleform $form = null) {
        if (optional_param('cancel', false, PARAM_BOOL)) {
            redirect(new moodle_url('/course/view.php', array('id' => $this->get_ui()->get_controller()->get_courseid())));
        }

        // First decide whether a substage is needed.
        $rc = $this->ui->get_controller();
        if ($rc->get_status() == backup::STATUS_SETTING_UI) {
            $rc->finish_ui();
        }
        if ($rc->get_status() == backup::STATUS_NEED_PRECHECK) {
            if (!$rc->precheck_executed()) {
                $rc->execute_precheck(true);
            }
            $results = $rc->get_precheck_results();
            if (!empty($results)) {
                $this->substage = self::SUBSTAGE_PRECHECKS;
            }
        }

        $substage = optional_param('substage', null, PARAM_INT);
        if (empty($this->substage) && !empty($substage)) {
            $this->substage = $substage;
            // Now check whether that substage has already been submit.
            if ($this->substage == self::SUBSTAGE_PRECHECKS && optional_param('sesskey', null, PARAM_RAW) == sesskey()) {
                $info = $rc->get_info();
                if (!empty($info->role_mappings->mappings)) {
                    foreach ($info->role_mappings->mappings as $key => &$mapping) {
                        $mapping->targetroleid = optional_param('mapping'.$key, $mapping->targetroleid, PARAM_INT);
                    }
                    $info->role_mappings->modified = true;
                }
                // We've processed the substage now setting it back to none so we
                // can move to the next stage.
                $this->substage = self::SUBSTAGE_NONE;
            }
        }

        return empty($this->substage);
    }
    /**
     * should NEVER be called... throws an exception
     */
    protected function initialise_stage_form() {
        throw new backup_ui_exception('backup_ui_must_execute_first');
    }

    /**
     * Renders the process stage screen
     *
     * @throws restore_ui_exception
     * @param core_backup_renderer $renderer renderer instance to use
     * @return string HTML code
     */
    public function display(core_backup_renderer $renderer) {
        global $PAGE;

        $html = '';
        $haserrors = false;
        $url = new moodle_url($PAGE->url, array(
            'restore'   => $this->get_uniqueid(),
            'stage'     => restore_ui::STAGE_PROCESS,
            'substage'  => $this->substage,
            'sesskey'   => sesskey()));
        $html .= html_writer::start_tag('form', array(
            'action'    => $url->out_omit_querystring(),
            'class'     => 'backup-restore',
            'enctype'   => 'application/x-www-form-urlencoded', // Enforce compatibility with our max_input_vars hack.
            'method'    => 'post'));
        foreach ($url->params() as $name => $value) {
            $html .= html_writer::empty_tag('input', array(
                'type'  => 'hidden',
                'name'  => $name,
                'value' => $value));
        }
        switch ($this->substage) {
            case self::SUBSTAGE_PRECHECKS :
                $results = $this->ui->get_controller()->get_precheck_results();
                $info = $this->ui->get_controller()->get_info();
                $haserrors = (!empty($results['errors']));
                $html .= $renderer->precheck_notices($results);
                if (!empty($info->role_mappings->mappings)) {
                    $context = context_course::instance($this->ui->get_controller()->get_courseid());
                    $assignableroles = get_assignable_roles($context, ROLENAME_ALIAS, false);
                    $html .= $renderer->role_mappings($info->role_mappings->mappings, $assignableroles);
                }
                break;
            default:
                throw new restore_ui_exception('backup_ui_must_execute_first');
        }
        $html .= $renderer->substage_buttons($haserrors);
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * Returns true if this stage can have sub-stages.
     * @return bool|false
     */
    public function has_sub_stages() {
        return true;
    }
}

/**
 * This is the completed stage.
 *
 * Once this is displayed there is nothing more to do.
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_ui_stage_complete extends restore_ui_stage_process {

    /**
     * The results of the backup execution
     * @var array
     */
    protected $results;

    /**
     * Constructs the complete backup stage
     * @param restore_ui $ui
     * @param array $params
     * @param array $results
     */
    public function __construct(restore_ui $ui, array $params = null, array $results = null) {
        global $CFG;
        $this->results = $results;
        parent::__construct($ui, $params);
        $this->stage = restore_ui::STAGE_COMPLETE;

        // Totara: Purge restore temp directory after completion.
        $rc = $this->get_ui()->get_controller();
        if ($rc->get_status() == backup::STATUS_FINISHED_OK or $rc->get_status() == backup::STATUS_FINISHED_ERR) {
            if ($tempdir = $rc->get_tempdir()) {
                $temp = $CFG->tempdir . '/backup/' . $tempdir;
                remove_dir($temp);
            }
        }
    }

    /**
     * Displays the completed backup stage.
     *
     * Currently this just envolves redirecting to the file browser with an
     * appropriate message.
     *
     * @param core_backup_renderer $renderer
     * @return string HTML code to echo
     */
    public function display(core_backup_renderer $renderer) {

        $html  = '';
        if (!empty($this->results['file_aliases_restore_failures'])) {
            $html .= $renderer->box_start('generalbox filealiasesfailures');
            $html .= $renderer->heading_with_help(get_string('filealiasesrestorefailures', 'core_backup'),
                'filealiasesrestorefailures', 'core_backup');
            $html .= $renderer->container(get_string('filealiasesrestorefailuresinfo', 'core_backup'));
            $html .= $renderer->container_start('aliaseslist');
            $html .= html_writer::start_tag('ul');
            foreach ($this->results['file_aliases_restore_failures'] as $alias) {
                $html .= html_writer::tag('li', s($alias));
            }
            $html .= html_writer::end_tag('ul');
            $html .= $renderer->container_end();
            $html .= $renderer->box_end();
        }
        $html .= $renderer->box_start();
        if (array_key_exists('file_missing_in_backup', $this->results)) {
            $html .= $renderer->notification(get_string('restorefileweremissing', 'backup'), 'notifyproblem');
        }
        $html .= $renderer->notification(get_string('restoreexecutionsuccess', 'backup'), 'notifysuccess');
        $html .= $renderer->continue_button(new moodle_url('/course/view.php', array(
            'id' => $this->get_ui()->get_controller()->get_courseid())), 'get');
        $html .= $renderer->box_end();

        return $html;
    }
}
