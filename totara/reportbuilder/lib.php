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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara_reportbuilder
 */

/**
 * Main Class definition and library functions for report builder
 */

require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');
require_once($CFG->dirroot . '/totara/core/lib/scheduler.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/totaratablelib.php');
require_once($CFG->dirroot . '/totara/core/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_base_source.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_base_content.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_base_embedded.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_join.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_column.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_column_option.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_filter_option.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_param.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_param_option.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_content_option.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_global_restriction.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_global_restriction_set.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
 * Content mode options
 */
define('REPORT_BUILDER_CONTENT_MODE_NONE', 0);
define('REPORT_BUILDER_CONTENT_MODE_ANY', 1);
define('REPORT_BUILDER_CONTENT_MODE_ALL', 2);

/**
 * Access mode options
 */
define('REPORT_BUILDER_ACCESS_MODE_NONE', 0);
define('REPORT_BUILDER_ACCESS_MODE_ANY', 1);
define('REPORT_BUILDER_ACCESS_MODE_ALL', 2);

/*
 * Initial Display Options
 */
define('RB_INITIAL_DISPLAY_SHOW', 0);
define('RB_INITIAL_DISPLAY_HIDE', 1);

/**
 * Report cache status flags
 */
define('RB_CACHE_FLAG_NONE', -1);   // Cache not used.
define('RB_CACHE_FLAG_OK', 0);      // Everything ready.
define('RB_CACHE_FLAG_CHANGED', 1); // Cache table needs to be rebuilt.
define('RB_CACHE_FLAG_FAIL', 2);    // Cache table creation failed.
define('RB_CACHE_FLAG_GEN', 3);     // Cache table is being generated.

/**
 *  Export to file system constants.
 *
 */
define('REPORT_BUILDER_EXPORT_EMAIL', 0);
define('REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE', 1);
define('REPORT_BUILDER_EXPORT_SAVE', 2);

global $REPORT_BUILDER_EXPORT_FILESYSTEM_OPTIONS;
$REPORT_BUILDER_EXPORT_FILESYSTEM_OPTIONS = array(
    'exporttoemail' => REPORT_BUILDER_EXPORT_EMAIL,
    'exporttoemailandsave' => REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE,
    'exporttosave' => REPORT_BUILDER_EXPORT_SAVE
);

// Maximum allowed time for report caching
define('REPORT_CACHING_TIMEOUT', 3600);

/**
 *  Pdf export constants.
 *
 */
define('REPORT_BUILDER_PDF_FONT_SIZE_DATA', 10);
define('REPORT_BUILDER_PDF_FONT_SIZE_RECORD', 14);
define('REPORT_BUILDER_PDF_FONT_SIZE_TITLE', 20);
define('REPORT_BUILDER_PDF_MARGIN_FOOTER', 10);
define('REPORT_BUILDER_PDF_MARGIN_BOTTOM', 20);

/**
 * Main report builder object class definition
 */
class reportbuilder {
    /**
     * Available filter settings
     */
    const FILTERNONE = 0;
    const FILTER = 1;
    const FILTERALL = 2;

    /**
     * Used to represent the different methods of getting records and counts for this report
     * @since Totara 12.4
     */
    const FETCHMETHOD_DATABASE_RECOMMENDATION = 0;
    const FETCHMETHOD_STANDARD_RECORDSET = 1;
    const FETCHMETHOD_COUNTED_RECORDSET = 2;

    /** Disable global restrictions in report */
    const GLOBAL_REPORT_RESTRICTIONS_DISABLED = 0;
    /** Use site-wide global restrictions in report */
    const GLOBAL_REPORT_RESTRICTIONS_ENABLED = 1;

    /**
     * Custom uniqueid setting to apply during reportbuilder instantiation
     * It should be set by @see reportbuilder::overrideuniqueid() before every new reportbuilder call with custom uniqueid
     * @var string
     */
    private static $overrideuniquid = null;

    /**
     * Internal flag for detection of incorrect constructor use,
     * this will be removed after we make constructor protected and type hinted.
     * @var bool
     */
    private static $preventpublicconstructor = true;

    /** @var rb_base_source */
    public $src;

    /** @var rb_column_option[] */
    public $columnoptions;

    /** @var rb_column[] */
    public $columns;

    public $fullname, $shortname, $source, $hidden, $searchcolumns, $filters, $filteroptions, $requiredcolumns, $initialdisplay;
    public $_filtering, $contentoptions, $contentmode, $embeddedurl, $description;
    public $_id, $recordsperpage, $defaultsortcolumn, $defaultsortorder;
    private $_joinlist, $_base, $_params, $_sid;
    protected $uniqueid;

    private $_paramoptions, $_embeddedparams, $_fullcount, $_filteredcount, $_isinitiallyhidden;
    private $_hasdisabledfilter = false;

    /**
     * @var bool Does report instance use GROUP BY statement (aggregation)?
     */
    public $grouped = false;

    /**
     * @var bool Indicates that report instance is grouped internally and not only because user selected custom aggregation for column
     */
    protected $pregrouped = false;

    public $reportfor, $embedded, $embedobj, $toolbarsearch;

    public $hidetoolbar = false;

    /**
     * The the state of global restrictions in this report.
     * @var int $globalrestriction values GLOBAL_REPORT_RESTRICTIONS_DISABLED or GLOBAL_REPORT_RESTRICTIONS_ENABLED
     */
    public $globalrestriction;

    /** @var rb_global_restriction_set $globalrestrictionset null if restrictions not used */
    public $globalrestrictionset;

    private $_post_config_restrictions;

    /**
     * @var array $filterurlparams URL parameters that need to be reapplied when report reloaded
     */
    private $filterurlparams = array();

    /**
     * Base url of report instance (report page url).
     * @var moodle_url
     */
    protected $baseurl = null;

    /**
     * Caching data for display methods, use display class name as key.
     * This is intended to prevent repeated DB requests in display methods.
     * @var array
     */
    public $displaycache = array();

    /**
     * @var bool $cache Cache state for current report
     */
    public $cache;

    /**
     * @var bool $cacheignore If true cache will be ignored during report preparation
     */
    public $cacheignore = false;

    /**
     * @var bool Should current instance ignore params or not (null - default behaviour
     */
    protected $ignoreparams = null;

    /**
     * @var bool Set for next created instance ignore params overriding default/page settings
     */
    protected static $overrideignoreparams = null;

    /**
     * @var stdClass $cacheschedule Record of cache scheduling and readyness
     */
    public $cacheschedule;

    /**
     * @var string|bool name of caching table if used and up-to-date, false if not present
     */
    protected $cachetable = null;

    /**
     * @var bool $ready State variable. True when reportbuilder finished construction.
     */
    protected $ready = false;

    /**
     * @var bool $usercanaccess true means user many access report, this is used for performacne only
     */
    protected $usercanaccess;

    /**
     * Please use {@link reportbuilder::can_display_total_count()}
     * Don't access this property outside of the Report Builder API.
     * It should be considered private. It is public only because of the report form requirements.
     * @internal
     * @var bool
     */
    public $showtotalcount = false;

    /**
     * Don't access this property outside of the Report Builder API.
     * It should be considered private. It is public only because of the report form requirements.
     * @internal
     * @var bool
     */
    public $useclonedb;

    /**
     * Sets how data and counts are collected for the report.
     * Values:
     *   - FETCHMETHOD_COUNTED_RECORDSET: Counted recordsets will be used, allowing counts to be collected when counting records.
     *   - FETCHMETHOD_STANDARD_RECORDSET: A standard recordset will be used to get data, and a separate query will be executed to get counts.
     *   - FETCHMETHOD_DATABASE_RECOMMENDATION: Use the system default, which if not set uses the database recommendation (recommended)
     *
     * Don't access this property outside of the Report Builder API.
     * It should be considered private. It is public only because of the report form requirements.
     * @internal
     * @var int
     */
    private $fetchmethod = null;

    /**
     * Factory method for creating instance of report (both user and embedded reports ids are allowed).
     *
     * NOTE: embedded reports often require extra embedded init data, developers must make sure
     *       that the report does not leak sensitive information when embedded data is not supplied here!
     *
     * @param int $id report id
     * @param rb_config|null $config
     * @param bool $checkaccess true mans verify that report can be access by current user or the user specified in reportfor
     *
     * @return reportbuilder
     */
    public static function create(int $id, rb_config $config = null, bool $checkaccess = true) {
        global $DB;

        $report = $DB->get_record('report_builder', ['id' => $id], '*', IGNORE_MISSING);
        if (!$report) {
            print_error('reportwithidnotfound', 'totara_reportbuilder', $id);
        }

        if ($config == null) {
            $config = new rb_config();
        }

        self::$preventpublicconstructor = false;

        return new reportbuilder($report, $config, $checkaccess);
    }

    /**
     * Factory method for creating an instance of an embedded report,
     * the report is created automatically if it does not exist yet.
     *
     * This is the only correct way to display embedded report on it's page.
     *
     * @param string $name name of embedded report
     * @param rb_config|null $config
     * @param bool $checkaccess true mans verify that report can be access by current user or the user specified in reportfor
     *
     * @return reportbuilder
     */
    public static function create_embedded(string $name, rb_config $config = null, bool $checkaccess = true) {
        global $DB;

        if (is_numeric($name)) {
            throw new coding_exception('Embedded report name cannot be a number');
        }

        $report = $DB->get_record('report_builder', ['shortname' => $name, 'embedded' => 1], '*', IGNORE_MISSING);

        if ($config == null) {
            $config = new rb_config();
        }

        // Handle if report not found in db.
        $embed = null;
        if (!$report) {
            if ($embedclass = self::get_embedded_report_class($name)) {
                $embed = new $embedclass($config->get_embeddata());
                if ($embed) {
                    // Maybe this is the first time we have run it, so try to create it.
                    if (!$id = reportbuilder_create_embedded_record($name, $embed, $error)) {
                        print_error('error:creatingembeddedrecord', 'totara_reportbuilder', '', $error);
                    }
                    $report = $DB->get_record('report_builder', ['id' => $id]);
                }
            }
        }

        if (!$report) {
            print_error('reportwithnamenotfound', 'totara_reportbuilder', $name);
        }

        self::$preventpublicconstructor = false;

        return new reportbuilder($report, $config, $checkaccess);
    }

    /**
     * Constructor for the reportbuilder object.
     *
     * NOTE: do not use directly in code, use create() and create_embedded() factory methods instead.
     *
     * @param \stdClass $report report record
     * @param rb_config $config report configuration
     * @param bool $checkaccess true mans verify that report can be access by current user or the user specified in reportfor
     */
    public function __construct($report, $config = null, $checkaccess = true) {
        global $DB;

        if (!is_object($report)) {
            debugging("From Totara 12, report constructor must not be called directly, use reportbuilder::create() instead.", DEBUG_DEVELOPER);
            if (!$report) {
                throw new coding_exception('Report id must be specified!');
            }

            $report = $DB->get_record('report_builder', array('id' => $report), '*', MUST_EXIST);

            $args = func_get_args();

            $config = new rb_config();
            if (!empty($args[3])) {
                $config->set_sid((int)$args[3]);
            }
            if (!empty($args[4])) {
                $config->set_reportfor($args[4]);
            }
            if (!empty($args[5])) {
                $config->set_nocache(true);
            }
            if (!empty($args[6])) {
                $config->set_embeddata($args[6]);
            }
            if (!empty($args[7])) {
                $config->set_global_restriction_set($args[7]);
            }
            $checkaccess = true;

        } else {
            if (self::$preventpublicconstructor) {
                throw new coding_exception('New reportbuilder constructor cannot be called directly, use reportbuilder::create() instead.');
            }
            self::$preventpublicconstructor = true;

            if ($config === null) {
                throw new coding_exception('Missing report config');
            } else if (!is_object($config) or !($config instanceof rb_config)) {
                throw new coding_exception('Invalid report config object supplied');
            }

            if (!is_bool($checkaccess)) {
                throw new coding_exception('Invalid report constructor checkaccess value');
            }
        }

        // No more changes in rb_config instance.
        $config->finalise();

        $this->_id = $report->id;
        $this->source = $report->source;
        $this->shortname = $report->shortname;
        $this->fullname = $report->fullname;
        $this->hidden = $report->hidden;
        $this->initialdisplay = $report->initialdisplay;
        $this->toolbarsearch = $report->toolbarsearch;
        $this->description = $report->description;
        $this->globalrestriction = $report->globalrestriction;
        $this->contentmode = $report->contentmode;
        $this->recordsperpage = $report->recordsperpage;
        $this->defaultsortcolumn = $report->defaultsortcolumn;
        $this->defaultsortorder = $report->defaultsortorder;
        $this->showtotalcount = (!empty($report->showtotalcount) && !empty(get_config('totara_reportbuilder', 'allowtotalcount')));
        $this->useclonedb = $report->useclonedb;
        $this->embedded = $report->embedded;
        $this->cache = $report->cache;

        // Use config settings.
        $this->_sid = $config->get_sid();
        $this->reportfor = $config->get_reportfor();
        $this->cacheignore = $config->get_nocache();
        $this->globalrestrictionset = $config->get_global_restriction_set();

        // Assign a unique identifier for this report.
        $this->uniqueid = $report->id;

        // If this is an embedded report then load the embedded report object.
        if ($this->embedded) {
            if ($embedclass = self::get_embedded_report_class($this->shortname)) {
                $embed = new $embedclass($config->get_embeddata());
                if (!$embed) {
                    throw new coding_exception('Embedded report definition not found');
                }
                $this->embedobj = $embed;
                $this->embeddedurl = $this->embedobj->url;
                unset($embed);
            }
        }

        $this->initialise();
        $this->ready = true;

        if ($checkaccess) {
            if (!$this->can_access()) {
                throw new moodle_exception('nopermission', 'totara_reportbuilder');
            }
        }
    }

    /**
     * Initialises the report with the configuration settings required.
     */
    private function initialise() {
        global $CFG, $DB;

        if ($this->is_ready()) {
            throw new coding_exception('This report instance cannot be initialised any more.');
        }

        // Set default fetch method for this report.
        $this->fetchmethod = self::get_default_fetch_method();

        // Load restriction set.
        if (!empty($CFG->enableglobalrestrictions)) {
            if ($this->embedobj and !$this->embedobj->embedded_global_restrictions_supported()) {
                $usesourcecache = true; // There is no restrictionset so we can use the sourcecache.
            } else {
                $this->cacheignore = true; // Caching cannot work together with restrictions, sorry.
                $usesourcecache = false; // Cannot use the source cache if we have a restrictionset.
            }
        } else {
            $usesourcecache = true; // There is no restrictionset so we can use the sourcecache.
        }

        $this->src = self::get_source_object($this->source, $usesourcecache, true, $this->globalrestrictionset);

        // If uniqueid was overridden, apply it here and reset.
        if (isset(self::$overrideuniquid)) {
            $this->uniqueid = self::$overrideuniquid;
            self::$overrideuniquid = null;
        }

        // If ignoreparams was overridden, apply it here and reset.
        if (isset(self::$overrideignoreparams)) {
            $this->ignoreparams = self::$overrideignoreparams;
            self::$overrideignoreparams = null;
        }

        if ($this->src->cacheable) {
            $this->cacheschedule = $DB->get_record('report_builder_cache', array('reportid' => $this->_id), '*', IGNORE_MISSING);
        } else {
            $this->cache = 0;
            $this->cacheschedule = false;
        }

        if ($this->_sid) {
            $this->restore_saved_search();
        }

        $this->_paramoptions = $this->src->paramoptions;

        if ($this->embedobj) {
            $this->_embeddedparams = $this->embedobj->embeddedparams;
        }
        $this->_params = $this->get_current_params();

        // Allow sources to modify itself based on params.
        $this->src->post_params($this);

        $this->_base = $this->src->base . ' base';

        $this->requiredcolumns = array();
        if (!empty($this->src->requiredcolumns)) {
            foreach ($this->src->requiredcolumns as $column) {
                $key = $column->type . '-' . $column->value;
                $this->requiredcolumns[$key] = $column;
            }
        }
        if ($this->embedobj) {
            if (!empty($this->embedobj->requiredcolumns)) {
                foreach ($this->embedobj->requiredcolumns as $column) {
                    $key = $column->type . '-' . $column->value;
                    $this->requiredcolumns[$key] = $column;
                }
            }
        }

        $this->columnoptions = array();
        foreach ($this->src->columnoptions as $columnoption) {
            $key = $columnoption->type . '-' . $columnoption->value;
            if (isset($this->columnoptions[$key])) {
                debugging("Duplicate column option $key detected in source " . get_class($this->src), DEBUG_DEVELOPER);
            }
            $this->columnoptions[$key] = $columnoption;
        }

        $this->columns = $this->get_columns();

        // Some sources add joins when generating new columns.
        $this->_joinlist = $this->src->joinlist;

        $this->contentoptions = $this->src->contentoptions;

        $this->filteroptions = array();
        foreach ($this->src->filteroptions as $filteroption) {
            $key = $filteroption->type . '-' . $filteroption->value;
            if (isset($this->filteroptions[$key])) {
                debugging("Duplicate filter option $key detected in source " . get_class($this->src), DEBUG_DEVELOPER);
            }
            $this->filteroptions[$key] = $filteroption;
        }

        $this->filters = $this->get_filters();

        $this->searchcolumns = $this->get_search_columns();

        // Make sure everything is compatible with caching, if not disable the cache.
        if ($this->cache) {
            if ($this->get_caching_problems()) {
                $this->cache = 0;
            }
        }

        $this->process_filters();

        // Allow the source to configure additional restrictions,
        // note that columns must not be changed any more here
        // because we may have already decided if cache is used.
        $colkeys = array_keys($this->columns);
        $reqkeys = array_keys($this->requiredcolumns);
        $this->src->post_config($this);
        if ($colkeys != array_keys($this->columns) or $reqkeys != array_keys($this->requiredcolumns)) {
            throw new coding_exception('Report source ' . get_class($this->src) .
                                       '::post_config() must not change report columns!');
        }
    }

    /**
     * Can report user access this report?
     *
     * NOTE: the result is cached for performance reasons.
     *
     * @return bool
     */
    public function can_access() {
        if (isset($this->usercanaccess)) {
            return $this->usercanaccess;
        }

        if ($this->needs_require_login() and $this->reportfor <= 0) {
            $this->usercanaccess = false;
            return $this->usercanaccess;
        }

        if ($this->embedobj) {
            $this->usercanaccess = true;
            // Run the embedded report's capability checks.
            if (method_exists($this->embedobj, 'is_capable')) {
                if (!$this->embedobj->is_capable($this->reportfor, $this)) {
                    $this->usercanaccess = false;
                }
            } else {
                debugging('This report doesn\'t implement is_capable(). Sidebar filters will only use form submission rather than instant filtering.', DEBUG_DEVELOPER);
                $this->usercanaccess = false;
            }

        } else {
            $this->usercanaccess = self::is_capable($this->_id, $this->reportfor);
        }

        return $this->usercanaccess;
    }

    /**
     * Return if reportbuilder is ready to work.
     * @return bool
     */
    public function is_ready() {
        return $this->ready;
    }

    /**
     * Returns true if the Total Count can be displayed and false otherwise.
     * @return bool
     */
    public function can_display_total_count() {
        return $this->showtotalcount;
    }

    /**
     * Returns list of reasons why caching cannot be enabled
     * for this report.
     *
     * @return string[]
     */
    public function get_caching_problems() {
        global $CFG;

        if (empty($CFG->enablereportcaching)) {
            $enablelink = new moodle_url("/".$CFG->admin."/settings.php", array('section' => 'optionalsubsystems'));
            return array(get_string('reportcachingdisabled', 'totara_reportbuilder', $enablelink->out()));
        }

        $problems = array();
        foreach ($this->filters as $filter) {
            /** @var rb_filter_type $filter */
            if (!$filter->is_caching_compatible()) {
                $problems[] = get_string('reportcachingincompatiblefilter', 'totara_reportbuilder', $filter->label);
            }
        }
        return $problems;
    }

    /**
     * Shortcut to function in report source.
     *
     * This may be called before data is generated for a report (e.g. embedded report page, report.php).
     * It should not be called when data will not be generated (e.g. report setup/config pages).
     */
    public function handle_pre_display_actions() {
        if (!$this->can_access()) {
            throw new moodle_exception('nopermission', 'totara_reportbuilder');
        }
        $this->src->pre_display_actions();
    }

    /**
     * Include javascript code needed by report builder
     */
    function include_js() {
        global $CFG, $PAGE;

        // Array of options for local_js
        $code = array();

        // Get any required js files that are specified by the source.
        $js = $this->src->get_required_jss();

        $code[] = TOTARA_JS_DIALOG;
        $jsdetails = new stdClass();
        $jsdetails->initcall = 'M.totara_reportbuilder_showhide.init';
        $jsdetails->jsmodule = array('name' => 'totara_reportbuilder_showhide',
            'fullpath' => '/totara/reportbuilder/showhide.js');
        $jsdetails->args = array('hiddencols' => $this->js_get_hidden_columns());
        $jsdetails->strings = array(
            'totara_reportbuilder' => array('showhidecolumns'),
            'moodle' => array('ok')
        );
        $js[] = $jsdetails;

        // Add saved search.js.
        $jsdetails = new stdClass();
        $jsdetails->initcall = 'M.totara_reportbuilder_savedsearches.init';
        $jsdetails->jsmodule = array('name' => 'totara_reportbuilder_savedsearches',
            'fullpath' => '/totara/reportbuilder/saved_searches.js');
        $jsdetails->strings = array(
            'totara_reportbuilder' => array('managesavedsearches'),
            'form' => array('close')
        );
        $js[] = $jsdetails;

        $jsdetails = new \stdClass();
        $jsdetails->initcall = 'M.totara_reportbuilder_export.init';
        $jsdetails->jsmodule = array(
            'name' => 'totara_reportbuilder_export',
            'fullpath' => '/totara/reportbuilder/js/export.js'
        );
        $js[] = $jsdetails;

        local_js($code);
        foreach ($js as $jsdetails) {
            if (!empty($jsdetails->strings)) {
                foreach ($jsdetails->strings as $scomponent => $sstrings) {
                    $PAGE->requires->strings_for_js($sstrings, $scomponent);
                }
            }

            $PAGE->requires->js_init_call($jsdetails->initcall,
                empty($jsdetails->args) ? null : $jsdetails->args,
                false, $jsdetails->jsmodule);
        }


        // Load Js for these filters.
        foreach ($this->filters as $filter) {
            $classname = get_class($filter);
            $filtertype = $filter->filtertype;
            $filterpath = $CFG->dirroot.'/totara/reportbuilder/filters/'.$filtertype.'.php';
            if (file_exists($filterpath)) {
                require_once $filterpath;
                if (method_exists($classname, 'include_js')) {
                    call_user_func(array($filter, 'include_js'));
                }
            }
        }
    }


    /**
     * Method for debugging SQL statement generated by report builder.
     *
     * When using this method it is strong recommended to call it BEFORE you attempt to display the report
     * or do anything with it other than setting it up.
     * This way if the SQL in the report contains an error you get the debug information out BEFORE any exceptions are thrown.
     * You can use the $return argument to collect the required HTML early and then output it when ready.
     *
     * @param int $level
     * @param bool $return If set to true the debug HTML is returned instead of output.
     * @return string|null HTML if the return argument is set, otherwise null and the HTML is echo'd out.
     */
    public function debug($level = 1, $return = false) {
        global $OUTPUT;
        if (!is_siteadmin()) {
            return '';
        }
        list($sql, $params) = $this->build_query(false, true);
        $sql .= $this->get_report_sort();
        $html = $OUTPUT->heading('Query', 3);
        $html .= html_writer::tag('pre', $sql, array('class' => 'notifymessage'));
        $html .= $OUTPUT->heading('Query params', 3);
        $html .= html_writer::tag('pre', s(print_r($params, true)), array('class' => 'notifymessage'));
        if ($level > 1) {
            $html .= $OUTPUT->heading('Reportbuilder Object', 3);
            $html .= html_writer::tag('pre', s(print_r($this, true)), array('class' => 'notifymessage'));
        }
        if ($return) {
            return $html;
        }
        echo $html;
    }

    /**
     * Custom uniqueid setting to apply during reportbuilder instantiation.
     *
     * Call this method right before every reportbuilder instantiation that requires custom uniqueid.
     *
     * @param string $uniqueid
     */
    public static function overrideuniqueid($uniqueid) {
        self::$overrideuniquid = $uniqueid;
    }

    /**
     * Custom setting of reportbuilder params ignore for next created instance.
     *
     * Call this method right before every reportbuilder instantiation that requires custom ignore param setting.
     *
     * @param bool $overrideignoreparams
     */
    public static function overrideignoreparams($overrideignoreparams) {
        self::$overrideignoreparams = $overrideignoreparams;
    }

    /**
     * Get instance uniqueid.
     *
     * @param string $prefix optional prefix to make namespaces for uniqueid with other components (e.g. flexible_table).
     * @return string
     */
    public function get_uniqueid($prefix = '') {
        if ($prefix) {
            $prefix .= '_';
        }
        return $prefix . $this->uniqueid;
    }
    /**
     * Static cache of source objects to speed up pages loading multiple report sources,
     * primarily used by get_source_object().
     */
    protected static $sourceobjects = array();

    /**
     * Useful during testing as it allows us to reset the source object cache.
     */
    public static function reset_source_object_cache() {
        self::$sourceobjects = array();
    }

    /**
     * Searches for and returns an instance of the specified source class
     *
     * @param string  $source       The name of the source class to return
     *                                 (excluding the rb_source prefix)
     * @param boolean $usecache     Whether to use the source cache or load from scratch
     * @param boolean $exception    Whether bad sources should throw exceptions or be ignored
     * @param rb_global_restriction_set $globalrestrictionset optional global restriction set to restrict the report,
     *                              not compatible with $usecache
     * @return rb_base_source An instance of the source. Returns false if
     *                the source can't be found
     */
    public static function get_source_object($source, $usecache = false, $exception = true, rb_global_restriction_set $globalrestrictionset = null) {
        global $USER;
        if ($globalrestrictionset and $usecache) {
            debugging('parameter $globalrestrictionset is not compatible with $usecache parameter, ignoring caches in get_source_object()', DEBUG_DEVELOPER);
            $usecache = false;
        }

        // Source objects are different for different users (regarding capabilities), so each user should have own source.
        if ($usecache && isset(self::$sourceobjects[$USER->id][$source])) {
            return self::$sourceobjects[$USER->id][$source];
        }

        // Check if the current source class is already included and cached.
        // No need to check $usecache here as we are not skipping restriction checks.
        if (isset(self::$cache_sourceclasses[$source])) {
            $instance = new self::$cache_sourceclasses[$source](null, $globalrestrictionset);
            if (!$globalrestrictionset) {
                self::$sourceobjects[$USER->id][$source] = $instance;
            }
            return $instance;
        }

        $sourcepaths = self::find_source_dirs();
        foreach ($sourcepaths as $sourcepath) {
            $classfile = $sourcepath . 'rb_source_' . $source . '.php';
            if (is_readable($classfile)) {
                include_once($classfile);
                $classname = 'rb_source_' . $source;
                if (class_exists($classname)) {
                    $instance = new $classname(null, $globalrestrictionset);
                    if (!$globalrestrictionset) {
                        self::$sourceobjects[$USER->id][$source] = $instance;
                    }
                    return $instance;
                }
            }
        }

        // Source not found.
        if ($exception) {
            throw new ReportBuilderException("Source '$source' not found");
        }

        return false;
    }

    /**
     * Searches for and returns a class name from the report source.
     *
     * Given a report source name, it finds the class and includes its library.
     * The class name is cached and returned, or false if something went wrong.
     *
     * @param string $source The name of the source class to return
     *                       (excluding the rb_source prefix)
     *
     * @return string|boolean Returns false if the source can't be found
     */
    public static function get_source_class(string $source) {
        if (isset(self::$cache_sourceclasses[$source])) {
            return self::$cache_sourceclasses[$source];
        }

        $sourcepaths = self::find_source_dirs();
        foreach ($sourcepaths as $sourcepath) {
            $classfile = $sourcepath . 'rb_source_' . $source . '.php';
            if (is_readable($classfile)) {
                include_once($classfile);
                $classname = 'rb_source_' . $source;
                if (class_exists($classname)) {
                    if (is_subclass_of($classname, 'rb_base_source')) {
                        self::$cache_sourceclasses[$source] = $classname;
                        return $classname;
                    } else {
                        debugging('All report source classes should extend rb_base_source', DEBUG_DEVELOPER);
                        return false;
                    }
                } else {
                    debugging('Report source class was not found in ' . $classfile, DEBUG_DEVELOPER);
                    return false;
                }
            }
        }

        // File or class not found.
        return false;
    }

    protected static $reportrecordcache = null;

    protected static $cache_userpermittedreports = null;
    protected static $cache_userpermittedreports_userid = null;
    protected static $cache_sourceclasses = null;

    /**
     * Retreives or creates a cached array of data objects for reports,
     * and returns the specified types of report, defaulting to all reports.
     *
     * @param boolean $embedded         True if we want the results to include embedded reports.
     * @param boolean $generated        True if we want the results to include user generated reports.
     * @param boolean $refreshcache     Regenerates any existing cache.
     * @return array                    Of normalised database objects.
     */
    protected static function get_report_records($embedded = true, $generated = true, $refreshcache = false) {
        // Generate or refresh the reportbuilder records cache.
        if ($refreshcache || is_null(self::$reportrecordcache)) {
            $reports = self::get_normalised_report_records();
            self::$reportrecordcache = $reports;
        }

        $records = array();
        foreach (self::$reportrecordcache as $report) {
            if ($report->embedded && $embedded) {
                // Include embedded reports.
                $records[$report->id] = $report;
            } else if (!$report->embedded && $generated) {
                // Include user generated reports.
                $records[$report->id] = $report;
            }
        }
        return $records;
    }

    /**
     * Reset static and session caches.
     */
    public static function reset_caches() {
        global $SESSION;

        unset($SESSION->reportbuilder);

        self::$reportrecordcache = null;
        self::$cache_userpermittedreports = null;
        self::$cache_userpermittedreports_userid = null;
        self::$cache_sourceclasses = null;
    }

    /**
     * Returns reports that the current user can view.
     *
     * @return array Array of report records
     */
    public static function get_user_permitted_reports() {
        global $USER;

        if (isset(self::$cache_userpermittedreports) and $USER->id == self::$cache_userpermittedreports_userid) {
            return self::$cache_userpermittedreports;
        }

        self::$cache_userpermittedreports_userid = $USER->id;

        $visiblesources = [];
        self::$cache_userpermittedreports = reportbuilder::get_permitted_reports($USER->id, false);
        foreach (self::$cache_userpermittedreports as $id => $report) {
            if (in_array($report->source, $visiblesources)) {
                continue;
            }
            $sourceclass = self::get_source_class($report->source);
            // Deprecated method is used here to ensure backwards compatibility.
            // This should be replaced with a direct call to $sourceclass::is_source_ignored() in the future.
            if ($report->embedded || !$sourceclass || self::is_source_class_ignored($report->source)) {
                unset(self::$cache_userpermittedreports[$id]);
                continue;
            }
            $visiblesources[] = $report->source;
        }

        return self::$cache_userpermittedreports;
    }

    /**
     * @return array()  Of normalised database objects for user generated reports.
     */
    public static function get_user_generated_reports() {
        return self::get_report_records(false, true);
    }

    /**
     * @return array()  Of normalised database objects for embedded reports.
     */
    public static function get_embedded_reports() {
        $reports = self::get_report_records(true, false);

        // NOTE: luckily this is not called often, let's filter out
        //       the ignored embedded reports here to improve the performance.
        foreach ($reports as $i => $report) {
            $embedclass = self::get_embedded_report_class($report->shortname);
            // Deprecated method is used here to ensure backwards compatibility.
            // This should be replaced with direct call to $embedclass::is_report_ignored() in the future.
            if (self::is_embedded_class_ignored($embedclass)) {
                unset($reports[$i]);
            } else {
                $reports[$i]->embedobj = new $embedclass([]);
            }
        }
        return $reports;
    }

    /**
     * Function that loops through all the embedded reports and generates the ones that are missing.
     * This used to be done as a part of get_user_permitted_reports(), but for performance benefit
     * it should be called from a limited number of places, like the embedded reports page.
     */
    public static function generate_embedded_reports() {
        global $DB;

        $embedrecords = $DB->get_records_menu('report_builder', ['embedded' => 1], '', 'shortname,1');
        $embedobjects = reportbuilder_get_all_embedded_reports();
        $error = null;

        foreach ($embedobjects as $embedobject) {
            // Check if the embedded report already exists or not (to make it safely re-runnable).
            if (!isset($embedrecords[$embedobject->shortname])) {
                $error = null;
                // If the result is false, then the report could not be generated.
                if (!reportbuilder_create_embedded_record($embedobject->shortname, $embedobject, $error)) {
                    // This is horrible but it is how the report builder_create_embedded_record was designed.
                    debugging('Embedded report generation failed with the error: ' . $error, DEBUG_DEVELOPER);
                }
            }
        }
    }

    /**
     * Generate class name from an embedded report name.
     *
     * Given an embedded report name, it finds the class and includes its library.
     * The class name is returned, or false if something went wrong.
     *
     * @param string $embedname Shortname of an embedded report
     *                          e.g. X from rb_X_embedded.php
     *
     * @return string|boolean
     */
    public static function get_embedded_report_class(string $embedname) {
        global $CFG;

        $sourcepaths = reportbuilder::find_source_dirs();
        $sourcepaths[] = $CFG->dirroot . '/totara/reportbuilder/embedded/';

        foreach ($sourcepaths as $sourcepath) {
            $classfile = $sourcepath . 'rb_' . $embedname . '_embedded.php';
            if (is_readable($classfile)) {
                include_once($classfile);
                $classname = 'rb_' . $embedname . '_embedded';
                if (class_exists($classname)) {
                    if (is_subclass_of($classname, 'rb_base_embedded')) {
                        return $classname;
                    } else {
                        debugging('All embedded report classes should extend rb_base_embedded', DEBUG_DEVELOPER);
                        return false;
                    }
                } else {
                    debugging('Embedded report class was not found in ' . $classfile, DEBUG_DEVELOPER);
                    return false;
                }
            }
        }

        // File or class not found.
        return false;
    }

    /**
     * @return array()  Of normalised database objects for all reports.
     */
    protected static function get_normalised_report_records() {
        global $DB;
        $reports = $DB->get_records('report_builder', array());
        $reportsclasses = reportbuilder_get_all_embedded_reports();
        $error = null;

        // Update the url property for any embedded reports.
        foreach ($reportsclasses as $object) {
            // We need to track if the embedded report exists or not. If not we need to trigger its creation.
            $found = false;
            foreach ($reports as $id => $report) {
                if ($report->shortname === $object->shortname) {
                    $report->url = $object->url;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // The embedded report did not exist within the database, its new or this is the first time anyone has seen it.
                // Trigger its creation.
                $error = null;
                $id = reportbuilder_create_embedded_record($object->shortname, $object, $error);
                // If $id is false then the report could not be generated.
                // There is no warning for this currently unfortunately.
                if ($id) {
                    $report = $DB->get_record('report_builder', array('id' => $id), '*', MUST_EXIST);
                    $report->url = $object->url;
                    $reports[$id] = $report;
                } else {
                    // This is horrible but it is how the report builder_create_embedded_record was designed :(
                    debugging('Embedded report generation failed with the error: '.$error, DEBUG_DEVELOPER);
                }
            }
        }

        $cache = reportbuilder_get_all_cached();
        foreach ($reports as $report) {
            // Add extra cache properties for cached reports.
            if (isset($cache[$report->id])) {
                $report->cache = true;
                $report->nextreport = $cache[$report->id]->nextreport;
            }

            $sourceclass = self::get_source_class($report->source);
            // If we can't find the reports source or this source is ignored, do not show it anywhere.
            // Deprecated method is used here to ensure backwards compatibility.
            // This should be replaced with a direct call to $sourceclass::is_source_ignored() in the future.
            if (!$sourceclass || self::is_source_class_ignored($report->source)) {
                unset($reports[$report->id]);
                continue;
            }
            // Source object will be initialised from the cached $sourceclass.
            $src = self::get_source_object($report->source, true, false);
            $report->sourcetitle = $src->sourcetitle;
            $report->sourceobject = $src;
        }
        return $reports;
    }

    /**
     * Searches codebase for report builder source files and returns a list
     *
     * @param bool $includenonselectable If true then include sources even if they can't be used in custom reports (for testing)
     * @return array Associative array of all available sources, formatted
     *               to be used in a select element.
     */
    public static function get_source_list($includenonselectable = false) {
        $output = array();

        foreach (self::find_source_dirs() as $dir) {
            if (is_dir($dir) && $dh = opendir($dir)) {
                while(($file = readdir($dh)) !== false) {
                    if (is_dir($file) || !preg_match('|^rb_source_(.*)\.php$|', $file, $matches)) {
                        continue;
                    }
                    $source = $matches[1];
                    $file = $dir . 'rb_source_' . $source . '.php';
                    if (is_readable($file)) {
                        include_once($file);
                        // Deprecated method is used here to ensure backwards compatibility.
                        // This should be replaced with:
                        //     $classname = 'rb_source_' . $source;
                        //     if (class_exists($classname) && $classname::is_source_ignored()) {
                        // in the future.
                        if (self::is_source_class_ignored($source)) {
                            continue;
                        }
                    }

                    $src = self::get_source_object($source);
                    if ($src->selectable || $includenonselectable) {
                        $output[$source] = $src->sourcetitle;
                    }
                }
                closedir($dh);
            }
        }
        asort($output);
        return $output;
    }

    /**
     * Return an array of sources which should be ignored.
     *
     * @return array List of sources to ignore.
     */
    public static function get_ignored_sources() {
        static $ignored = null;
        if (is_null($ignored)) {
            $cache = cache::make('totara_reportbuilder', 'rb_ignored_sources');
            $ignored = $cache->get('all');

            if (!is_array($ignored)) {
                $ignored = [];
                foreach (self::find_source_dirs() as $dir) {
                    if (is_dir($dir) && $dh = opendir($dir)) {
                        while(($file = readdir($dh)) !== false) {
                            if (is_dir($file) || !preg_match('|^rb_source_(.*)\.php$|', $file, $matches)) {
                                continue;
                            }
                            $source = $matches[1];
                            $file = $dir . 'rb_source_' . $source . '.php';
                            if (is_readable($file)) {
                                include_once($file);
                                // Deprecated method is used here to ensure backwards compatibility.
                                // This should be replaced with:
                                //     $classname = 'rb_source_' . $source;
                                //     if (class_exists($classname) && $classname::is_source_ignored()) {
                                // in the future.
                                if (self::is_source_class_ignored($source)) {
                                    $ignored[] = $source;
                                }
                            }
                        }
                        closedir($dh);
                    }
                }
                $cache->set('all', $ignored);
            }
        }
        return $ignored;
    }

    /**
     * Return an array of embedded reports which should be ignored.
     *
     * @return array List of embedded files which should be ignored.
     */
    public static function get_ignored_embedded() {
        global $CFG;
        static $ignored = null;

        if (is_null($ignored)) {
            $cache = cache::make('totara_reportbuilder', 'rb_ignored_embedded');
            $ignored = $cache->get('all');

            if (!is_array($ignored)) {
                $ignored = [];
                $source_dirs = self::find_source_dirs();
                $source_dirs[] = $CFG->dirroot . '/totara/reportbuilder/embedded/';

                foreach ($source_dirs as $dir) {
                    if (is_dir($dir) && $dh = opendir($dir)) {
                        while(($file = readdir($dh)) !== false) {
                            if (is_dir($file) || !preg_match('|^rb_(.*)\_embedded.php$|', $file, $matches)) {
                                continue;
                            }
                            $embedded = $matches[1];
                            $embedclass = self::get_embedded_report_class($embedded);
                            // Deprecated method is used here to ensure backwards compatibility.
                            // This should be replaced with direct call to $embedclass::is_report_ignored() in the future.
                            if (self::is_embedded_class_ignored($embedclass)) {
                                $ignored[] = $embedded;
                            }
                        }
                        closedir($dh);
                    }
                }
                $cache->set('all', $ignored);
            }
        }
        return $ignored;
    }

    /**
     * Gets list of source directories to look in for source files
     *
     * @param bool $resetstatic If set to true the static variable is ignored and reset.
     * @return array An array of paths to source directories
     */
    public static function find_source_dirs($resetstatic = false) {
        static $sourcepaths;

        if ($sourcepaths !== null && !$resetstatic) {
            return $sourcepaths;
        }

        $cache = cache::make('totara_reportbuilder', 'rb_source_directories');
        $sourcepaths = $cache->get('all');
        if (!is_array($sourcepaths)) {
            $sourcepaths = array();
            $locations = array(
                'auth',
                'mod',
                'block',
                'tool',
                'totara',
                'local',
                'enrol',
                'repository',
            );
            // Search for rb_sources directories for each plugin type.
            foreach ($locations as $modtype) {
                foreach (core_component::get_plugin_list($modtype) as $mod => $path) {
                    $dir = "$path/rb_sources/";
                    if (file_exists($dir) && is_dir($dir)) {
                        $sourcepaths[] = $dir;
                    }
                }
            }
            $cache->set('all', $sourcepaths);
        }
        return $sourcepaths;
    }


    /**
     * Reduces an array of objects to those that match all specified conditions
     *
     * @param array $items An array of objects to reduce
     * @param array $conditions An associative array of conditions.
     *                          key is the object's property, value is the value
     *                          to match against
     * @param boolean $multiple If true, returns all matches, as an array,
     *                          otherwise returns first match as an object
     *
     * @return mixed An array of objects or a single object that match all
     *               the conditions
     */
    static function reduce_items($items, $conditions, $multiple=true) {
        if (!is_array($items)) {
            throw new ReportBuilderException('Input not an array');
        }
        if (!is_array($conditions)) {
            throw new ReportBuilderException('Conditions not an array');
        }
        $output = array();
        foreach ($items as $item) {
            $status = true;
            foreach ($conditions as $name => $value) {
                // condition fails if property missing
                if (!property_exists($item, $name)) {
                    $status = false;
                    break;
                }
                if ($item->$name != $value) {
                    $status = false;
                    break;
                }
            }
            if ($status && $multiple) {
                $output[] = $item;
            } else if ($status) {
                return $item;
            }
        }
        return $output;
    }

    static function get_single_item($items, $type, $value) {
        $cond = array('type' => $type, 'value' => $value);
        return self::reduce_items($items, $cond, false);
    }


    /**
     * Check the joins provided are in the joinlist
     *
     * @param array $joinlist Join list to check for joins
     * @param mixed $joins Single, or array of joins to check
     * @returns boolean True if all specified joins are in the list
     *
     */
    static function check_joins($joinlist, $joins) {
        // nothing to check
        if ($joins === null) {
            return true;
        }

        // get array of available names from join list provided
        $joinnames = array('base');
        foreach ($joinlist as $item) {
            $joinnames[] = $item->name;
        }

        // return false if any listed joins don't exist
        if (is_array($joins)) {
            foreach ($joins as $join) {
                if (!in_array($join, $joinnames)) {
                    return false;
                }
            }
        } else {
            if (!in_array($joins, $joinnames)) {
                return false;
            }
        }
        return true;
    }


    /**
     * Looks up the saved search ID specified and attempts to restore
     * the SESSION variable if access is permitted
     *
     * @return Boolean True if user can view, error otherwise
     */
    function restore_saved_search() {
        global $SESSION, $DB;
        if ($saved = $DB->get_record('report_builder_saved', array('id' => $this->_sid))) {

            if ($saved->ispublic != 0 || $saved->userid == $this->reportfor) {
                $SESSION->reportbuilder[$this->get_uniqueid()] = unserialize($saved->search);
            } else {
                if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
                    mtrace('Saved search not found or search is not public');
                } else {
                    print_error('savedsearchnotfoundornotpublic', 'totara_reportbuilder');
                }
                return false;
            }
        } else {
            if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
                mtrace('Saved search not found or search is not public');
            } else {
                print_error('savedsearchnotfoundornotpublic', 'totara_reportbuilder');
            }
            return false;
        }
        return true;
    }

    /**
     * Returns true if a report has filter of given type and value. Returns false if not.
     *
     * @param $reportid
     * @param $type
     * @param $value
     * @return bool
     */
    public static function contains_filter($reportid, $type, $value) {
        global $DB;
        $filterexists = $DB->record_exists('report_builder_filters',
            array(
                'reportid' => $reportid,
                'type' => $type,
                'value' => $value));
        return $filterexists;
    }

    /**
     * Gets any filters set for the current report from the database
     *
     * @return array Array of filters for current report or empty array if none set
     */
    public function get_filters() {
        global $DB;

        if (!$this->filteroptions) {
            return array();
        }

        $out = array();
        $filters = $DB->get_records('report_builder_filters', array('reportid' => $this->_id), 'sortorder');
        foreach ($filters as $filter) {
            $type = $filter->type;
            $value = $filter->value;
            $advanced = $filter->advanced;
            $defaultvalue = !empty($filter->defaultvalue) ? unserialize($filter->defaultvalue) : array();
            $region = $filter->region;
            $key = "{$filter->type}-{$filter->value}";

            if (!isset($this->filteroptions[$key])) {
                continue;
            }
            $option = $this->filteroptions[$key];

            // Only include filter if a valid object is returned.
            if ($filterobj = rb_filter_type::get_filter($type, $value, $advanced, $region, $this, $defaultvalue)) {
                $filterobj->filterid = $filter->id;
                $filterobj->filtername = $filter->filtername;
                $filterobj->customname = $filter->customname;
                if ($filter->customname and $filter->filtername) {
                    // Use value from database.
                    $filterobj->label = $filter->filtername;
                } else if (!empty($option->filteroptions['addtypetoheading'])) {
                    $type = $this->get_type_heading($option->type);
                    $text = (object) array ('column' => $option->label, 'type' => $type);
                    $filterobj->label = get_string ('headingformat', 'totara_reportbuilder', $text);
                }
                $out[$key] = $filterobj;

                // enabled report grouping if any filters are grouped
                if (isset($filterobj->grouping) && $filterobj->grouping != 'none') {
                    $this->grouped = true;
                    $this->pregrouped = true;
                }
            }
        }
        return $out;
    }

    /**
     * Gets any search columns set for the current report from the database
     *
     * @return array Array of search columns for current report or empty array if none set
     */
    public function get_search_columns() {
        global $DB;

        $searchcolumns = $DB->get_records('report_builder_search_cols', array('reportid' => $this->_id));

        return $searchcolumns;
    }

    /**
     * Returns sql where statement based on active filters
     * @param string $extrasql
     * @param array $extraparams for the extra sql clause (named params)
     * @return array containing one array of SQL clauses and one array of params
     */
    function fetch_sql_filters($extrasql='', $extraparams=array()) {
        global $SESSION;

        $where_sqls = array();
        $having_sqls = array();
        $filterparams = array();

        if ($extrasql != '') {
            if (strpos($extrasql, '?')) {
                print_error('extrasqlshouldusenamedparams', 'totara_reportbuilder');
            }
            $where_sqls[] = $extrasql;
        }

        if (!empty($SESSION->reportbuilder[$this->get_uniqueid()])) {
            foreach ($SESSION->reportbuilder[$this->get_uniqueid()] as $fname => $data) {
                if (isset($data['value'])) {
                    if (is_array($data['value']) || is_object($data['value'])) {
                        $data['value'] = clean_param_array((array)$data['value'], PARAM_RAW_TRIMMED);
                    } else {
                        $data['value'] = clean_param($data['value'], PARAM_RAW_TRIMMED);
                    }
                }
                if ($fname == 'toolbarsearchtext') {
                    if ($this->toolbarsearch && $this->has_toolbar_filter() && $data) {
                        list($where_sqls[], $params) = $this->get_toolbar_sql_filter($data);
                        $filterparams = array_merge($filterparams, $params);
                    }
                    else if (!array_key_exists($fname, $this->filters)) {
                        $this->_hasdisabledfilter = true;
                    }
                } else if (array_key_exists($fname, $this->filters)) {
                    $filter = $this->filters[$fname];
                    if ($filter->grouping != 'none') {
                        list($having_sqls[], $params) = $filter->get_sql_filter($data);
                    } else {
                        list($where_sqls[], $params) = $filter->get_sql_filter($data);
                    }
                    $filterparams = array_merge($filterparams, $params);
                } else if (!array_key_exists($fname, $this->filters)) {
                    $this->_hasdisabledfilter = true;
                }
            }
        }

        $out = array();
        if (!empty($having_sqls)) {
            // Remove empty values.
            $having_sqls = array_filter($having_sqls);
            $out['having'] = implode(' AND ', $having_sqls);
        }
        if (!empty($where_sqls)) {
            // Remove empty values.
            $where_sqls = array_filter($where_sqls);
            $out['where'] = implode(' AND ', $where_sqls);
        }

        return array($out, array_merge($filterparams, $extraparams));
    }

    /**
     * Same as fetch_sql_filters() but returns array of strings
     * describing active filters instead of SQL
     *
     * @return array of strings
     */
    function fetch_text_filters() {
        global $SESSION;
        $out = array();
        if (!empty($SESSION->reportbuilder[$this->get_uniqueid()])) {
            foreach ($SESSION->reportbuilder[$this->get_uniqueid()] as $fname => $data) {
                if ($fname == 'toolbarsearchtext') {
                    if ($this->toolbarsearch && $this->has_toolbar_filter() && $data) {
                        $out[] = $this->get_toolbar_text_filter($data);
                    }
                } else if (array_key_exists($fname, $this->filters)) {
                    $field = $this->filters[$fname];
                    $out[] = $field->get_label($data);
                }
            }
        }
        return $out;
    }

    /**
     * Determine if there are columns defined for the toolbar search for this report
     *
     * @return bool true if there are toolbar search columns defined
     */
    private function has_toolbar_filter() {
        $columns = $this->get_search_columns();
        return (!empty($columns));
    }

    /**
     * Returns the condition to be used with SQL where
     *
     * @param string $toolbarsearchtext filter settings
     * @return array containing filtering condition SQL clause and params
     */
    private function get_toolbar_sql_filter($toolbarsearchtext) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/core/searchlib.php');

        $keywords = totara_search_parse_keywords($toolbarsearchtext);
        $columns = $this->get_search_columns();

        if (empty($keywords) || empty($columns)) {
            return array('1=1', array());
        }

        $dbfields = array();
        foreach ($columns as $column) {
            if ($this->is_cached()) {
                $dbfields[] = $column->type . '_' . $column->value;
            } else {
                $columnobject = self::get_single_item($this->columnoptions, $column->type, $column->value);
                if (!empty($columnobject)) {
                    $dbfields[] = $columnobject->field;
                }
            }
        }

        return totara_search_get_keyword_where_clause($keywords, $dbfields, SQL_PARAMS_NAMED);
    }

    /**
     * Returns a human friendly description of the toolbar search criteria
     *
     * @param array $toolbarsearchtext the text that is being looked for
     * @return string active toolbar search criteria
     */
    private function get_toolbar_text_filter($toolbarsearchtext) {
        $columns = $this->get_search_columns();

        $numberoffields = count($columns);

        if ($numberoffields == 0) {
            return '';

        } else if ($numberoffields == 1) {
            $column = reset($columns);
            $columnobject = self::get_single_item($this->columnoptions, $column->type, $column->value);
            $a = new stdClass();
            $a->searchtext = $toolbarsearchtext;
            $a->column = $columnobject->name;
            return get_string('toolbarsearchtextiscontainedinsingle', 'totara_reportbuilder', $a);

        } else {
            $result = get_string('toolbarsearchtextiscontainedinmultiple', 'totara_reportbuilder', $toolbarsearchtext);
            $columnnames = array();
            foreach ($columns as $column) {
                $columnobject = self::get_single_item($this->columnoptions, $column->type, $column->value);
                $columnnames[] = $columnobject->name;
            }
            $result .= implode(', ', $columnnames);
            return $result;
        }
    }

    private function process_filters() {
        global $CFG, $SESSION;
        require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');
        $clearfilters = optional_param('clearfilters', 0, PARAM_INT);
        $mformstandard = new report_builder_standard_search_form(null,
                array('fields' => $this->get_standard_filters()));
        $adddatastandard = $mformstandard->get_data(false);
        // Get submitted data as get_data could result in NUll if validation fails.
        $standardsubmitteddata = $mformstandard->get_submitted_data();
        $clearstandardfilters = $clearfilters || isset($standardsubmitteddata->submitgroupstandard['clearstandardfilters']);
        if ($adddatastandard || $clearstandardfilters) {
            foreach ($this->get_standard_filters() as $field) {
                if ($clearstandardfilters) {
                    // Clear out any existing filters.
                    $field->unset_data();
                } else {
                    $data = $field->check_data($adddatastandard);
                    if ($data === false) {
                        // Unset existing result if field has been set back to "not set" position.
                        $field->unset_data();
                    } else {
                        $field->set_data($data);
                    }
                }
            }
            if ($clearstandardfilters) {
                $SESSION->reportbuilder[$this->get_uniqueid()] = array();
            }
        }
        $mformsidebar = new report_builder_sidebar_search_form(null,
                array('report' => $this, 'fields' => $this->get_sidebar_filters(), 'nodisplay' => true));
        $adddatasidebar = $mformsidebar->get_data(false);
        // Get submitted data as get_data could result in NUll if validation fails.
        $sidebarsubmitteddata = $mformsidebar->get_submitted_data();
        $clearsidebarfilters = $clearfilters || isset($sidebarsubmitteddata->submitgroupsidebar['clearsidebarfilters']);
        if ($adddatasidebar || $clearsidebarfilters) {
            foreach ($this->get_sidebar_filters() as $field) {
                if ($clearsidebarfilters) {
                    // Clear out any existing filters.
                    $field->unset_data();
                } else {
                    $data = $field->check_data($adddatasidebar);
                    if ($data === false) {
                        // Unset existing result if field has been set back to "not set" position.
                        $field->unset_data();
                    } else {
                        $field->set_data($data);
                    }
                }
            }
        }
        $mformtoolbar = new report_builder_toolbar_search_form(null);
        $adddatatoolbar = $mformtoolbar->get_data(false);
        // Get submitted data as get_data could result in NUll if validation fails.
        $toolbarsubmitteddata = $mformtoolbar->get_submitted_data();
        $cleartoolbarsearchtext = $clearfilters || isset($toolbarsubmitteddata->cleartoolbarsearchtext);
        if ($adddatatoolbar || $cleartoolbarsearchtext) {
            if ($cleartoolbarsearchtext) {
                // Clear out any existing data.
                unset($SESSION->reportbuilder[$this->get_uniqueid()]['toolbarsearchtext']);
                unset($_POST['toolbarsearchtext']);
            } else {
                $data = $adddatatoolbar->toolbarsearchtext;
                if (empty($data)) {
                    // Unset existing result if field has been set back to "not set" position.
                    unset($SESSION->reportbuilder[$this->get_uniqueid()]['toolbarsearchtext']);
                    unset($_POST['toolbarsearchtext']);
                } else {
                    $SESSION->reportbuilder[$this->get_uniqueid()]['toolbarsearchtext'] = $data;
                }
            }
        }
    }

    /**
     * Get column names in resulting query
     *
     * @return array
     */
    function get_column_aliases() {
        $fields = array();
        foreach ($this->columns as $column) {
            $fields[] = $column->value;
        }
        return $fields;
    }

    /**
     * Get fields and aliases from appropriate source
     *
     * @param array $source soruce should object with 'field' and 'fieldalias' properties
     * @param bool $aliasonly if enabled will return only aliases of field
     * @return array of SQL snippets
     */
    function get_alias_fields(array $source, $aliasonly = false) {
        $result = array();
        foreach($source as $fields) {
            if (is_object($fields) && (method_exists($fields, 'get_field') || isset($fields->field))) {
                if (method_exists($fields, 'get_field')) {
                    $fieldname = $fields->get_field();
                }
                else {
                    $fieldname = $fields->field;
                }
                // support of several fields in one filter/column/etc
                if (is_array($fieldname)) {
                    $field = array();
                    foreach ($fieldname as $key => $value) {
                        // need to namespace these extra keys to avoid collisions
                        $field["rb_composite_{$key}"] = $value;
                    }
                } else {
                     if (isset($fields->fieldalias)) {
                         $field = array($fields->fieldalias => $fieldname);
                     }
                }

                foreach ($field as $alias=>$name) {
                    if ($aliasonly) {
                        $result[] = $alias;
                    } else {
                        $result[] = "{$name} AS {$alias}";
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Returns user visible column heading name
     *
     * @param rb_column $column
     * @param bool false means return html, true means utf-8 plaintext for exports
     * @return string
     */
    public function format_column_heading(rb_column $column, $plaintext) {
        if ($column->customheading) {
            // Use value from database.
            $heading = format_string($column->heading);
        } else {
            // Use default value.
            $defaultheadings = $this->get_default_headings_array();
            $heading = isset($defaultheadings[$column->type . '-' . $column->value]) ?
                $defaultheadings[$column->type . '-' . $column->value] : null;

            if ($column->grouping === 'none') {
                if ($column->transform) {
                    $heading = get_string("transformtype{$column->transform}_heading", 'totara_reportbuilder', $heading);
                } else if ($column->aggregate) {
                    $heading = get_string("aggregatetype{$column->aggregate}_heading", 'totara_reportbuilder', $heading);
                }
            }
        }

        if ($plaintext) {
            $heading = strip_tags($heading);
            $heading = core_text::entities_to_utf8($heading);
        }

        return $heading;
    }

    /**
     * Gets any columns set for the current report from the database
     *
     * @return array Array of columns for current report or empty array if none set
     */
    public function get_columns() {
        global $DB;

        $out = array();
        $id = isset($this->_id) ? $this->_id : null;
        if (empty($id)) {
            return $out;
        }

        $columns = $DB->get_records('report_builder_columns', array('reportid' => $id), 'sortorder ASC, id ASC');

        foreach ($columns as $column) {
            // Find the column option that matches this column.
            $key = $column->type . '-' . $column->value;
            if (!isset($this->columnoptions[$key])) {
                continue;
            }
            $columnoption = $this->columnoptions[$key];

            // Debugging message for any developer using a deprecated column.
            if ($columnoption->deprecated && (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING)) {
                debugging("Column {$key} is a deprecated column in source " . get_class($this->src), DEBUG_DEVELOPER);
            }

            if (!empty($columnoption->columngenerator)) {
                /* Rather than putting the column into the list, we call the generator and it
                 * will supply an array of columns (0 or more) that should be included. We pass
                 * all available information to the generator (columnoption and hidden). */
                $columngenerator = 'rb_cols_generator_' . $columnoption->columngenerator;
                $results = $this->src->$columngenerator($columnoption, $column->hidden);
                foreach ($results as $result) {
                    $key = $result->type . '-' . $result->value;
                    if (isset($this->requiredcolumns[$key])) {
                        debugging("Generated column $key duplicates required column in source " . get_class($this->src),
                                    DEBUG_DEVELOPER);
                        continue;
                    }
                    if (isset($out[$key])) {
                        debugging("Generated column $key overrides column in source " . get_class($this->src), DEBUG_DEVELOPER);
                        continue;
                    }
                    $out[$key] = $result;
                    if ($out[$key]->grouping != 'none' or $out[$key]->aggregate) {
                        $this->grouped = true;
                    }
                    if ($out[$key]->grouping != 'none') {
                        $this->pregrouped = true;
                    }
                }
            } else {
                if (isset($this->requiredcolumns[$key])) {
                    debugging("Column $key duplicates required column in source " . get_class($this->src), DEBUG_DEVELOPER);
                    continue;
                }

                try {
                    $out[$key] = $this->src->new_column_from_option(
                        $column->type,
                        $column->value,
                        $column->transform,
                        $column->aggregate,
                        $column->heading,
                        $column->customheading,
                        $column->hidden
                    );
                    // Enabled report grouping if any columns are grouped.
                    if ($out[$key]->grouping !== 'none' or $out[$key]->aggregate) {
                        $this->grouped = true;
                    }
                    if ($out[$key]->grouping !== 'none') {
                        $this->pregrouped = true;
                    }
                } catch (ReportBuilderException $e) {
                    debugging($e->getMessage(), DEBUG_NORMAL);
                }
            }
        }

        // Now append any required columns.
        foreach ($this->requiredcolumns as $column) {
            $key = $column->type . '-' . $column->value;
            $column->required = true;
            $out[$key] = $column;
            // Enabled report grouping if any columns are grouped.
            if ($column->grouping !== 'none' or $column->aggregate) {
                $this->grouped = true;
            }
            if ($column->grouping !== 'none') {
                $this->pregrouped = true;
            }
        }

        return $out;
    }


    /**
     * Returns an associative array of the default headings for this report
     *
     * Looks up all the columnoptions (from this report's source)
     * For each one gets the default heading according the the following criteria:
     *  - if the report is embedded get the heading from the embedded source
     *  - if not embedded or the column's heading isn't specified in the embedded source,
     *    get the defaultheading from the columnoption
     *  - if that isn't specified, use the columnoption name
     *
     * @return array Associtive array of default headings for all the column options in this report
     *               Key is "{$type}-{$value]", value is the default heading string
     */
    function get_default_headings_array() {
        if (!isset($this->columnoptions) || !is_array($this->columnoptions)) {
            return false;
        }

        $out = array();
        foreach ($this->columnoptions as $option) {
            $key = $option->type . '-' . $option->value;

            if ($this->embedobj && $embeddedheading = $this->embedobj->get_embedded_heading($option->type, $option->value)) {
                // Use heading from embedded source, but do not add the type because embedded report has own default!
                $out[$key] = format_string($embeddedheading);
                continue;
            } else {
                if (isset($option->defaultheading)) {
                    // use default heading
                    $defaultheading = $option->defaultheading;
                } else {
                    // fall back to columnoption name
                    $defaultheading = $option->name;
                }
            }

            // There may be more than one type of data (for example, users)
            // so add the type to the heading to differentiate the types - if required.
            if (isset($option->addtypetoheading) && $option->addtypetoheading) {
                $type = $this->get_type_heading($option->type);
                $text = (object) array ('column' => $defaultheading, 'type' => $type);
                $defaultheading = get_string ('headingformat', 'totara_reportbuilder', $text);
            }

            $out[$key] = format_string($defaultheading);
        }
        return $out;
    }

    /**
     * Returns the translated heading name for given type
     *
     * @param string $type
     * @return string
     */
    public function get_type_heading($type) {
        // Standard source.
        $sourcename = $this->source;

        $langstr = 'type_' . $type;
        if (get_string_manager()->string_exists($langstr, 'rb_source_' . $sourcename)) {
            // Is there a type string in the source file?
            $heading = get_string($langstr, 'rb_source_' . $sourcename);
        } else if (get_string_manager()->string_exists($langstr, 'totara_reportbuilder')) {
            // How about in report builder?
            $heading = get_string($langstr, 'totara_reportbuilder');
        } else {
            // Display in missing string format to make it obvious.
            $heading = get_string($langstr, 'rb_source_' . $sourcename);
        }
        return $heading;
    }

    /**
     * Given a report fullname, try to generate a sensible shortname that will be unique
     *
     * @param string $fullname The report's full name
     * @return string A unique shortname suitable for this report
     */
    public static function create_shortname($fullname) {
        global $DB;

        // Transliterate all non-latin characters to latin.
        if (function_exists('transliterator_transliterate')) {
            $fullname = transliterator_transliterate('Any-Latin; Latin-ASCII', $fullname);
        }

        // Leaves only letters and numbers replaces spaces + dashes with underscores.
        $fullname = strtolower(preg_replace(['/[^a-zA-Z\d\s\-_]/', '/[\s\-]/'], ['', '_'], $fullname));
        $shortname = "report_{$fullname}";

        if (strlen($shortname) > 255) {
            $shortname = substr($shortname, 0, 255);
        }

        while ($DB->get_field('report_builder', 'id', ['shortname' => $shortname])) {
            $hash = substr(sha1($shortname . (time() + microtime(true))), 10, 10);
            $shortname = substr($shortname, 0, 244) . "_{$hash}";
        }

        return $shortname;
    }


    /**
     * Return the URL to view the current report
     * @param bool $params reapply params of report (if any)
     *
     * @return string URL of current report
     */
    function report_url($params = false) {
        global $CFG;
        if ($this->embeddedurl === null) {
            $url = new moodle_url($CFG->wwwroot . '/totara/reportbuilder/report.php', array('id' => $this->_id));
        } else {
            $url = new moodle_url($CFG->wwwroot . $this->embeddedurl);
        }
        if ($params) {
            foreach ($this->get_current_url_params() as $filtername => $filtervalue) {
                if (is_null($url->param($filtername))) {
                    $url->param($filtername, $filtervalue);
                }
            }
        }
        return $url->out(false);
    }

    /**
     * Return array of report params to be applied to URL
     *
     * @return array of params
     */
    public function get_current_url_params() {
        return $this->filterurlparams;
    }

    /**
     * Set base url for report instance
     * @param moodle_url $baseurl
     */
    public function set_baseurl(moodle_url $baseurl) {
        $this->baseurl = $baseurl;
    }

    /**
     * Get the current page url maintaining report specific parameters, minus any pagination or sort order elements
     * Good for submitting forms
     *
     * Note: Make sure $PAGE->set_url() is called before this function is used, otherwise qualified_me is returned.
     *
     * @return string Current URL, minus any spage and ssort parameters
     */
    function get_current_url() {
        global $PAGE;

        if (!empty($this->baseurl)) {
            // Use the cached url, could have been set using set_baseurl or from $PAGE.
            $currenturl = $this->baseurl;
        } else if ($PAGE->has_set_url()) {
            // No url cached, so set the cache to the $PAGE url.
            $currenturl = new moodle_url($PAGE->url);
            $this->baseurl = $currenturl;
        } else {
            // No cached or $PAGE url, so use qualified_me, but don't cache it ($PAGE may be set before the next call).
            $currenturl = new moodle_url(qualified_me());
            foreach ($currenturl->params() as $name => $value) {
                if (in_array($name, array('spage', 'ssort', 'sid', 'clearfilters'))) {
                    $currenturl->remove_params($name);
                }
            }
        }

        // Reapply filter url params.
        $url = clone($currenturl);
        foreach ($this->get_current_url_params() as $filtername => $filtervalue) {
            if (is_null($url->param($filtername))) {
                $url->param($filtername, $filtervalue);
            }
        }
        return $url->out(false);
    }


    /**
     * Returns an array of arrays containing information about any currently
     * set URL parameters. Used to determine which joins are required to
     * match against URL parameters
     *
     * @param bool $all Return all params including unused in current request
     *
     * @return array Array of set URL parameters and their values
     */
    function get_current_params($all = false) {
        global $SESSION;

        $clearfiltersparam = optional_param('clearfilters', 0, PARAM_INT);

        // This hack is necessary because the report instance may be constructed
        // on pages with colliding GET or POST page parameters.
        $ignorepageparams = false;
        if (defined('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS')) {
            $ignorepageparams = REPORT_BUILDER_IGNORE_PAGE_PARAMETERS;
        }

        // Check if ignore params is set for current instance.
        if (isset($this->ignoreparams)) {
            $ignorepageparams = $this->ignoreparams;
        }

        $out = array();
        if (empty($this->_paramoptions)) {
            return $out;
        }
        foreach ($this->_paramoptions as $param) {
            $name = $param->name;
            if ($ignorepageparams) {
                $var = null;
            } else if ($param->type == 'string') {
                $var = optional_param($name, null, PARAM_TEXT);
            } else {
                $var = optional_param($name, null, PARAM_INT);
            }
            if (isset($this->_embeddedparams[$name])) {
                // Embedded params take priority over url params.
                $res = new rb_param($name, $this->_paramoptions);
                $res->value = $this->_embeddedparams[$name];
                $out[] = $res;
            } else if ($all) {
                // When all parameters required, they are not restricted to particular value.
                if (!empty($param->field)) {
                    $out[] = new rb_param($name, $this->_paramoptions);
                }
            } else if (isset($var) || $clearfiltersparam) {
                if (isset($var)) {
                    // This url param exists, add to params to use.
                    $res = new rb_param($name, $this->_paramoptions);
                    $res->value = $var; // Save the value.
                    $out[] = $res;
                    $this->set_filter_url_param($name, $var);
                }
            }
        }
        return $out;
    }

    /**
     * Set parameter related to report filters and settings that need to be reapplied when report reloaded
     * This should be used when columns sorting are changed, filters reconfigured, etc.
     *
     * @param int $name Name of param
     * @param string $value current value of param
     */
    public function set_filter_url_param($name, $value) {
        $this->filterurlparams[$name] = $value;
    }

    /**
     * Wrapper for displaying search form from filtering class
     *
     * @return Nothing returned but prints the search box
     */
    public function display_search() {
        global $CFG;

        $standard_filters = $this->get_standard_filters();
        if (count($standard_filters) === 0) {
            return;
        }

        require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');
        $mformstandard = new report_builder_standard_search_form($this->get_current_url(),
                array('fields' => $standard_filters), 'post', '', array('class' => 'rb-search'));
        // Calling get_data to get the form validated before displaying it, so we can see errors present in the form.
        $mformstandard->get_data();
        $mformstandard->display();
    }

    /**
     * Display active selected global report restrictions for current $USER
     * and allow them to choose restrictions if possible.
     *
     * Note: pages using this method must call rb_global_restriction_set::create_from_page_parameters() first.
     *
     * @return void - echoes HTML output
     */
    public function display_restrictions() {
        global $CFG, $USER, $PAGE, $SESSION;

        // Display only if GR enabled and active for report.
        if (empty($CFG->enableglobalrestrictions) or $this->globalrestriction == reportbuilder::GLOBAL_REPORT_RESTRICTIONS_DISABLED) {
            // Restrictions are disabled.
            return;
        }

        // Does the report support restrictions?
        if (!$this->src->global_restrictions_supported()) {
            return;
        }

        $allrestrictions = rb_global_restriction_set::get_user_all_restrictions($USER->id);
        if (!$allrestrictions) {
            // No restrictions available - we cannot ask them to select anything.
            if ($this->globalrestrictionset) {
                if (!$this->globalrestrictionset->get_current_restriction_ids()) {
                    // Do not tell users what is going on, this is a required feature.
                    // Users will not see any records.
                    return;
                } else {
                    // This should not happen, it might be a very weird race condition when deleting stuff.
                    return;
                }
            } else {
                // User can see all records, no worries here.
                return;
            }
        }

        // Add multilang support for names of restrictions.
        foreach ($allrestrictions as $restriction) {
            $restriction->name = format_string($restriction->name);
        }

        // Get the data from rb_global_restriction_set::create_from_page_parameters().
        if (isset($SESSION->rb_global_restriction)) {
            $sessionids = $SESSION->rb_global_restriction;
        } else {
            debugging('Missing session GRR data, make sure the report page calls rb_global_restriction_set::create_from_page_parameters()', DEBUG_DEVELOPER);
            $sessionids = array();
        }
        $appliednames = array();
        $appliedids = array();

        foreach ($sessionids as $restid) {
            if (!isset($allrestrictions[$restid])) {
                // This should not happen, likely concurrent delete, it will get fixed on page reload.
                continue;
            }
            $cur = $allrestrictions[$restid];
            $appliednames[] = $cur->name;
            $appliedids[] = $cur->id;
        }

        // Dialog box JS.
        local_js(array(
            TOTARA_JS_DIALOG
        ));

        // Note: PAGE->url is the right place to return to, blocks are using it too.
        $args = array($this->_id, $PAGE->url->out(false));
        $jsmodule = array('name' => 'totara_email_scheduled_report',
            'fullpath' => '/totara/reportbuilder/js/chooserestriction.js'
        );

        $PAGE->requires->strings_for_js(array('chooserestrictiontitle'), 'totara_reportbuilder');
        $PAGE->requires->js_init_call('M.totara_reportbuilder_chooserestriction.init', $args, false, $jsmodule);

        $chooselink = html_writer::link('#', get_string('changeglobalrestriction', 'totara_reportbuilder'),
            array('id' => 'show-chooserestriction-dialog', 'class' => 'restrictions_dialog'));

        if (!$appliedids) {
            // Strange, no restriction was picked automatically, this should not happen, but let them pick one manually now.
            $messagehtml = html_writer::div(get_string('selectedglobalrestrictionsselect', 'totara_reportbuilder', $chooselink),
                'notifynotice globalrestrictionsnotice alert alert-info');
            echo html_writer::div($messagehtml, 'globalrestrictionscontainer');
            return;
        }

        if (count($allrestrictions) === 1) {
            // Do not tell users what is going on, this is a required feature.
            // Users can see records from the current restriction only.
            return;
        }

        $chooselink = html_writer::link('#', get_string('changeglobalrestriction', 'totara_reportbuilder'), array(
            'id' => 'show-chooserestriction-dialog',
            'class' => 'restrictions_dialog',
            'data-selected' => implode(',', $appliedids)));

        $a = new stdClass();
        $a->appliednamesstr = implode(', ', $appliednames);
        $a->chooselink = $chooselink;
        $messagehtml = html_writer::div(get_string('selectedglobalrestrictionsmany', 'totara_reportbuilder', $a),
            'notifynotice globalrestrictionsnotice alert alert-info');
        echo html_writer::div($messagehtml, 'globalrestrictionscontainer');
    }

    /**
     * Wrapper for displaying search form from filtering class
     *
     * @return Nothing returned but prints the search box
     */
    public function display_sidebar_search() {
        global $CFG, $PAGE;

        $sidebarfilters = $this->get_sidebar_filters();
        if (count($sidebarfilters) === 0) {
            return;
        }

        require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');
        $mformsidebar = new report_builder_sidebar_search_form($this->get_current_url(),
                array('report' => $this, 'fields' => $sidebarfilters), 'post', '', array('class' => 'rb-sidebar'));
        // Calling get_data to get the form validated before displaying it, so we can see errors present in the form.
        $mformsidebar->get_data();
        $mformsidebar->display();

        // If is_capable is not implemented on an embedded report then don't activate instant filters.
        // Instead, we force the user to use standard form submission (the same as when javascript is not available).
        if ($this->embedobj && !method_exists($this->embedobj, 'is_capable')) {
            return;
        }

        $PAGE->requires->js_call_amd('totara_reportbuilder/instantfilter', 'init', array('id' => $this->_id));
    }


    public function get_standard_filters() {
        $result = array();
        foreach ($this->filters as $key => $filter) {
            if ($filter->region == rb_filter_type::RB_FILTER_REGION_STANDARD) {
                $result[$key] = $filter;
            }
        }
        return $result;
    }

    public function get_sidebar_filters() {
        $result = array();
        foreach ($this->filters as $key => $filter) {
            if ($filter->region == rb_filter_type::RB_FILTER_REGION_SIDEBAR) {
                $result[$key] = $filter;
            }
        }
        return $result;
    }

    /** Returns true if the current user has permission to view this report
     *
     * @param integer $id ID of the report to be viewed
     * @param integer $userid ID of user to check permissions for
     * @return boolean True if they have any of the required capabilities
     */
    public static function is_capable($id, $userid=null) {
        global $USER;

        $foruser = isset($userid) ? $userid : $USER->id;
        $allowed = array_keys(reportbuilder::get_permitted_reports($foruser, true));
        $permitted = in_array($id, $allowed);
        return $permitted;
    }

    /**
     * Returns true if require_login should be executed.
     *
     * Only embedded reports can specify not to run require_login.
     *
     * @return boolean True if require_login should be executed
     */
    public final function needs_require_login() {
        if (empty($this->embedded)) {
            return true;
        } else {
            return $this->embedobj->needs_require_login();
        }
    }

    /**
    * Returns an array of defined reportbuilder access plugins
    *
    * @return \totara_reportbuilder\rb\access\base[] Array of access plugin classes indexed by type name
    */
    public static function get_all_access_plugins() {
        $plugins = array();
        $classes = core_component::get_namespace_classes('rb\access', 'totara_reportbuilder\rb\access\base');
        foreach ($classes as $class) {
            /** @var totara_reportbuilder\rb\access\base $obj */
            $obj = new $class();
            $type = $obj->get_type();
            if (in_array($type, $plugins)) {
                debugging('Duplicate reportbuilder plugin name detected: '. $type, DEBUG_DEVELOPER);
                continue;
            }
            $plugins[$type] = $obj;
        }

        return $plugins;
    }

    /**
    * Returns an array of associative arrays keyed by reportid,
    * each associative array containing ONLY the plugins actually enabled on each report,
    * with a 0/1 value of whether the report passes each plugin checks for the specified user
    * For example a return array in the following form
    *
    * array[1] = array('role_access' => 1, 'individual_access' => 0)
    * array[4] = array('role_access' => 0, 'individual_access' => 0, 'hierarchy_access' => 0)
    *
    * would mean:
    * report id 1 has 'role_access' and 'individual_access' plugins enabled,
    * this user passed role_access checks but failed the individual_access checks;
    * report id 4 has 'role_access', 'individual_access and 'hierarchy_access' plugins enabled,
    * and the user failed access checks in all three.
    *
    * @param int $userid The user to check which reports they have access to
    * @return array Array of reports, with enabled plugin names and access status
    */
    public static function get_reports_plugins_access($userid) {
        global $DB;
        //create return variable
        $report_plugin_access = array();
        //if no list of plugins specified, check them all
        $plugins = self::get_all_access_plugins();
        //keep track of which plugins are actually active according to report_builder_settings
        /** @var \totara_reportbuilder\rb\access\base[] $active_plugins */
        $active_plugins = array();
        //now get the info for plugins that are actually enabled for any reports
        list($insql, $params) = $DB->get_in_or_equal(array_keys($plugins));
        $sql = "SELECT id,reportid,type
                  FROM {report_builder_settings}
                 WHERE type $insql
                   AND name = ?
                   AND value = ?";
        $params[] = 'enable';
        $params[] = '1';
        $reportinfo = $DB->get_records_sql($sql, $params);

        foreach ($reportinfo as $id => $plugin) {
            //foreach scope variables for efficiency
            $rid = $plugin->reportid;
            $ptype = '' . $plugin->type;
            if (!isset($plugins[$ptype])) {
                continue;
            }
            $active_plugins[$ptype] = $plugins[$ptype];
            //set up enabled plugin info for this report
            if (isset($report_plugin_access[$rid])) {
                $report_plugin_access[$rid][$ptype] = 0;
            } else {
                $report_plugin_access[$rid] = array($ptype => 0);
            }
        }
        //now call the plugin class to get the accessible reports for each actually used plugin
        foreach ($active_plugins as $type => $obj) {
            $accessible = $obj->get_accessible_reports($userid);
            foreach ($accessible as $key => $rid) {
                if (isset($report_plugin_access[$rid]) && is_array($report_plugin_access[$rid])) {
                    //report $rid has passed checks in $plugin
                    //the plugin should already have an entry with value 0 from above
                    if (isset($report_plugin_access[$rid][$type])) {
                        $report_plugin_access[$rid][$type] = 1;
                    }
                }
            }
        }

        return $report_plugin_access;
    }

    /**
     * Returns an array of reportbuilder records that the user can view
     *
     * Note: We don't want to do is_capable checks on embedded reports in this function
     * as it needs to be optimised for speed.
     *
     * @param int $userid The user to check which reports they have access to
     * @param boolean $showhidden If true, reports which are hidden
     *                            will also be included
     * @return array Array of results from the report_builder table
     */
    public static function get_permitted_reports($userid = null, $showhidden = false) {
        global $DB, $USER;

        // check access for specified user, or the current user if none set
        $foruser = isset($userid) ? $userid : $USER->id;
        //array to hold the final list
        $permitted_reports = array();
        //get array of all reports with enabled plugins and whether they passed or failed each enabled plugin
        $enabled_plugins = self::get_reports_plugins_access($foruser);
        //get basic reports list
        $hidden = (!$showhidden) ? ' WHERE hidden = 0 ' : '';
        $sql = "SELECT *
                  FROM {report_builder}
                 $hidden
                 ORDER BY fullname ASC";
        $reports = $DB->get_records_sql($sql);
        //we now have all the information we need
        if ($reports) {
            foreach ($reports as $report) {
                $report->url = reportbuilder_get_report_url($report);

                if ($report->accessmode == REPORT_BUILDER_ACCESS_MODE_NONE) {
                    $permitted_reports[$report->id] = $report;
                    continue;
                }
                if ($report->accessmode == REPORT_BUILDER_ACCESS_MODE_ANY) {
                    if (!empty($enabled_plugins) && isset($enabled_plugins[$report->id])) {
                        foreach ($enabled_plugins[$report->id] as $plugin => $value) {
                            if ($value == 1) {
                                //passed in some plugin so allow it
                                $permitted_reports[$report->id] = $report;
                                break;
                            }
                        }
                        continue;
                    } else {
                        // Bad data - set to "any plugin passing", but no plugins actually have settings to check for this report.
                        continue;
                    }
                }
                if ($report->accessmode == REPORT_BUILDER_ACCESS_MODE_ALL) {
                    if (!empty($enabled_plugins) && isset($enabled_plugins[$report->id])) {
                        $status=true;
                        foreach ($enabled_plugins[$report->id] as $plugin => $value) {
                            if ($value == 0) {
                                //failed in some expected plugin, reject
                                $status = false;
                                break;
                            }
                        }
                        if ($status) {
                            $permitted_reports[$report->id] = $report;
                            continue;
                        }
                    } else {
                        // bad data - set to "all plugins passing", but no plugins actually have settings to check for this report
                        continue;
                    }
                }
            }
        }
        return $permitted_reports;
    }

    /**
     * Check if the user can view at least one report.
     *
     * This method is similar to get_permitted_reports(), but instead of collecting all report data
     * and storing it, we loop through the data until we find a report that the user can access.
     *
     * @param int $userid The user to check which reports they have access to
     *
     * @return bool
     */
    public static function has_reports($userid = null) {
        global $DB, $USER;

        // Check access for specified user, or the current user if none set.
        $foruser = isset($userid) ? $userid : $USER->id;
        // Get array of all reports with enabled plugins and whether they passed or failed each enabled plugin.
        $enabled_plugins = \reportbuilder::get_reports_plugins_access($foruser);
        // Get basic reports list.
        $reports = $DB->get_records('report_builder', ['hidden' => 0], 'fullname ASC');

        if ($reports) {
            foreach ($reports as $report) {
                if (!$sourceclass = self::get_source_class($report->source)) {
                    // No point of going any further if we are't able to find the class for 'ignored' check.
                    continue;
                }
                // Calls to deprecated is_source_class_ignored method are used here to ensure backwards compatibility.
                // These should be replaced with the direct calls to $sourceclass::is_source_ignored() in the future.
                if ($report->accessmode == REPORT_BUILDER_ACCESS_MODE_NONE) {
                    if (!self::is_source_class_ignored($report->source)) {
                        return true;
                    }
                } else if ($report->accessmode == REPORT_BUILDER_ACCESS_MODE_ANY) {
                    if (!empty($enabled_plugins) && isset($enabled_plugins[$report->id])) {
                        foreach ($enabled_plugins[$report->id] as $plugin => $value) {
                            if ($value == 1) {
                                if (!self::is_source_class_ignored($report->source)) {
                                    return true;
                                }
                            }
                        }
                        continue;
                    } else {
                        // Bad data - set to "any plugin passing", but no plugins actually have settings to check for this report.
                        continue;
                    }
                } else if ($report->accessmode == REPORT_BUILDER_ACCESS_MODE_ALL) {
                    if (!empty($enabled_plugins) && isset($enabled_plugins[$report->id])) {
                        $status = true;
                        foreach ($enabled_plugins[$report->id] as $plugin => $value) {
                            if ($value == 0) {
                                // Failed in some expected plugin, reject.
                                $status = false;
                                break;
                            }
                        }
                        if ($status) {
                            if (!self::is_source_class_ignored($report->source)) {
                                return true;
                            }
                        }
                    } else {
                        // Bad data - set to "all plugins passing", but no plugins actually have settings to check for this report.
                        continue;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the value of the specified parameter, or null if not found
     *
     * @param string $name name of the parameter
     * @return mixed the value
     */
    public function get_param_value($name) {
        foreach ($this->_params as $param) {
            if ($param->name == $name) {
                return $param->value;
            }
        }
        return null;
    }


    /**
     * Returns an SQL snippet that, when applied to the WHERE clause of the query,
     * reduces the results to only include those matched by any specified URL parameters
     * @param bool $cache if enabled only field alias will be used
     *
     * @return array containing SQL snippet (created from URL parameters) and SQL params
     */
    function get_param_restrictions($cache = false) {
        global $DB;
        $out = array();
        $sqlparams = array();
        $params = $this->_params;
        if (is_array($params)) {
            $count = 1;
            foreach ($params as $param) {
                $field = ($cache) ? $param->fieldalias : $param->field;
                $value = $param->value;

                // don't include if param not set to anything
                if (!isset($value) || (!is_array($value) && strlen(trim($value)) == 0) || $param->field == '') {
                    continue;
                }

                $wherestr = $field;

                // Notice: If you change value parsing logic please document changes in @see rb_param_option class.
                if (is_array($value)) {
                    list($sql, $params) = $DB->get_in_or_equal($value, SQL_PARAMS_NAMED, "pr{$count}_");
                    $wherestr .= ' ' . $sql;
                    $sqlparams = array_merge($sqlparams, $params);
                } else {
                    $uniqueparam = rb_unique_param("pr{$count}_");
                    // if value starts with '!', do a not equals match
                    // to the rest of the string
                    if (substr($value, 0, 1) == '!') {
                        $wherestr .= " != :{$uniqueparam}";
                        // Strip off the leading '!'
                        $sqlparams[$uniqueparam] = substr($value, 1);
                    } else if (substr($value, 0, 1) == '>') {
                        $wherestr .= " > :{$uniqueparam}";
                        // Strip off the leading '!'
                        $sqlparams[$uniqueparam] = substr($value, 1);
                    } else if (substr($value, 0, 1) == '<') {
                        $wherestr .= " < :{$uniqueparam}";
                        // Strip off the leading '!'
                        $sqlparams[$uniqueparam] = substr($value, 1);
                    } else {
                        // normal match
                        $wherestr .= " = :{$uniqueparam}";
                        $sqlparams[$uniqueparam] = $value;
                    }
                }

                $out[] = $wherestr;
                $count++;
            }
        }
        if (count($out) == 0) {
            return array('', array());
        }
        return array('(' . implode(' AND ', $out) . ')', $sqlparams);
    }


    /**
     * Returns an SQL snippet that, when applied to the WHERE clause of the query,
     * reduces the results to only include those matched by any specified content
     * restrictions
     * @param bool $cache if enabled, only alias fields will be used
     *
     * @return array containing SQL snippet created from content restrictions, as well as SQL params array
     */
    function get_content_restrictions($cache = false) {
        // if no content restrictions enabled return a TRUE snippet
        // use 1=1 instead of TRUE for MSSQL support
        if ($this->contentmode == REPORT_BUILDER_CONTENT_MODE_NONE) {
            return array("( 1=1 )", array());
        } else if ($this->contentmode == REPORT_BUILDER_CONTENT_MODE_ALL) {
            // require all to match
            $op = "\n    AND ";
        } else {
            // require any to match
            $op = "\n    OR ";
        }

        $reportid = $this->_id;
        $out = array();
        $params = array();

        // go through the content options
        if (isset($this->contentoptions) && is_array($this->contentoptions)) {
            foreach ($this->contentoptions as $option) {
                $name = $option->classname;
                $classname = 'rb_' . $name . '_content';
                $settingname = $name . '_content';

                $fields = array();
                foreach ($option->fields as $key => $field) {
                    if ($cache) {
                        $fields[$key] = 'rb_content_option_' . $key;
                    } else {
                        $fields[$key] = $field;
                    }
                }

                // Collapse array to string if it consists of only one element
                // This provides backward compatibility in case fields is just
                // a string instead of an array.
                if (count($fields) === 1) {
                    $fields = array_shift($fields);
                }

                if (class_exists($classname)) {
                    $class = new $classname($this->reportfor);

                    if (reportbuilder::get_setting($reportid, $settingname, 'enable')) {
                        // this content option is enabled
                        // call function to get SQL snippet
                        list($out[], $contentparams) = $class->sql_restriction($fields, $reportid);
                        $params = array_merge($params, $contentparams);
                    }
                } else {
                    print_error('contentclassnotexist', 'totara_reportbuilder', '', $classname);
                }
            }
        }
        // show nothing if no content restrictions enabled
        if (count($out) == 0) {
            // use 1=0 instead of FALSE for MSSQL support
            return array('(1=0)', array());
        }

        return array('(' . implode($op, $out) . ')', $params);
    }

    /**
     * Returns an SQL snippet that, when applied to the WHERE clause of hierarchy queries,
     * reduces the results to only include those matched by any specified content
     * restrictions.
     *
     * NOTE: This is intended primarily for hierarchy dialogs in reports,
     *       that is why the restriction should also include all parent items to the top,
     *       so that we may display the results as tree.
     *
     * @param string $prefix Get restrictions for this prefix class only
     *
     * @return array containing SQL snippet created from content restrictions, as well as SQL params array
     */
    function get_hierarchy_content_restrictions($prefix) {
        // if no content restrictions enabled return a TRUE snippet
        // use 1=1 instead of TRUE for MSSQL support
        if ($this->contentmode == REPORT_BUILDER_CONTENT_MODE_NONE) {
            return array("( 1=1 )", array());
        } else if ($this->contentmode == REPORT_BUILDER_CONTENT_MODE_ALL) {
            // require all to match
            $op = "\n    AND ";
        } else {
            // require any to match
            $op = "\n    OR ";
        }

        $reportid = $this->_id;
        $out = array();
        $params = array();

        // go through the content options
        if (isset($this->contentoptions) && is_array($this->contentoptions)) {
            foreach ($this->contentoptions as $option) {
                $name = $option->classname;
                $classname = 'rb_' . $name . '_content';
                $settingname = $name . '_content';

                if (class_exists($classname) && method_exists($classname, 'sql_hierarchy_restriction')) {
                    $class = new $classname($this->reportfor);

                    if (method_exists($classname, 'sql_hierarchy_restriction_prefix') &&
                        $class->sql_hierarchy_restriction_prefix() == $prefix) {
                        if (reportbuilder::get_setting($reportid, $settingname, 'enable')) {
                            // this content option is enabled call function to get SQL snippet,
                            // linking to queries in the hierarchy class - it is always base.id
                            list($out[], $contentparams) = $class->sql_hierarchy_restriction('base.id', $reportid);
                            $params = array_merge($params, $contentparams);
                        }
                    }
                }
            }
        }
        // show everything if no hierarchy content restrictions enabled
        if (count($out) == 0) {
            // use 1=1 instead of TRUE for MSSQL support
            return array('(1=1)', array());
        }

        return array('(' . implode($op, $out) . ')', $params);
    }

    /**
     * Returns human readable descriptions of any content or
     * filter restrictions that are limiting the number of results
     * shown. Used to let the user known what a report contains
     *
     * @param string $which Which restrictions to return, defaults to all
     *                      but can be 'filter' or 'content' to just return
     *                      restrictions of that type
     * @return array An array of strings containing descriptions
     *               of any restrictions applied to this report
     */
    function get_restriction_descriptions($which='all') {
        // include content restrictions
        $content_restrictions = array();
        $reportid = $this->_id;
        $res = array();
        if ($this->contentmode != REPORT_BUILDER_CONTENT_MODE_NONE) {
            foreach ($this->contentoptions as $option) {
                $name = $option->classname;
                $classname = 'rb_' . $name . '_content';
                $settingname = $name . '_content';
                $title = $option->title;
                if (class_exists($classname)) {
                    $class = new $classname($this->reportfor);
                    if (reportbuilder::get_setting($reportid, $settingname, 'enable')) {
                        // this content option is enabled
                        // call function to get text string
                        $res[] = $class->text_restriction($title, $reportid);
                    }
                } else {
                    print_error('contentclassnotexist', 'totara_reportbuilder', '', $classname);
                }
            }
            if ($this->contentmode == REPORT_BUILDER_CONTENT_MODE_ALL) {
                // 'and' show one per line
                $content_restrictions = $res;
            } else {
                // 'or' show as a single line
                $content_restrictions[] = implode(get_string('or', 'totara_reportbuilder'), $res);
            }
        }

        $filter_restrictions = $this->fetch_text_filters();

        switch($which) {
        case 'content':
            $restrictions = $content_restrictions;
            break;
        case 'filter':
            $restrictions = $filter_restrictions;
            break;
        default:
            $restrictions = array_merge($content_restrictions, $filter_restrictions);
        }
        return $restrictions;
    }




    /**
     * Returns an array of fields that must form part of the SQL query
     * in order to provide the data need to display the columns required
     *
     * Each element in the array is an SQL snippet with an alias built
     * from the $type and $value of that column
     *
     * @param int $mode How aliases for grouping columns should be prepared
     * @return array Array of SQL snippets for use by SELECT query
     *
     */
    function get_column_fields($mode = rb_column::REGULAR) {
        $fields = array();
        $src = $this->src;
        foreach ($this->columns as $column) {
            $fields = array_merge($fields, $column->get_fields($src, $mode, true));
        }
        return $fields;
    }


    /**
     * Returns the names of all the joins in the joinlist
     *
     * @return array Array of join names from the joinlist
     */
    function get_joinlist_names() {
        $joinlist = $this->_joinlist;
        $joinnames = array();
        foreach ($joinlist as $item) {
            $joinnames[] = $item->name;
        }
        return $joinnames;
    }


    /**
     * Return a join from the joinlist by name
     *
     * @param string $name Join name to get from the join list
     *
     * @return object {@link rb_join} object for the matching join, or false
     */
    function get_joinlist_item($name) {
        $joinlist = $this->_joinlist;
        foreach ($joinlist as $item) {
            if ($item->name == $name) {
                return $item;
            }
        }
        return false;
    }


    /**
     * Given an item, returns an array of {@link rb_join} objects needed by this item
     *
     * @param object $item An object containing a 'joins' property
     * @param string $usage The function is called to obtain joins for various
     *                     different elements of the query. The usage is displayed
     *                     in the error message to help with debugging
     * @return array An array of {@link rb_join} objects used to build the join part of the query
     */
    function get_joins($item, $usage) {
        $output = array();

        // extract the list of joins into an array format
        if (isset($item->joins) && is_array($item->joins)) {
            $joins = $item->joins;
        } else if (isset($item->joins)) {
            $joins = array($item->joins);
        } else {
            $joins = array();
        }

        foreach ($joins as $join) {
            if ($join == 'base') {
                continue;
            }

            $joinobj = $this->get_single_join($join, $usage);
            $output[] = $joinobj;

            $this->get_dependency_joins($output, $joinobj);

        }

        return $output;
    }

    /**
     * Given a join name, look for it in the joinlist and return the join object
     *
     * @param string $join A single join name (should match joinlist item name)
     * @param string $usage The function is called to obtain joins for various
     *                      different elements of the query. The usage is
     *                      displayed in the error message to help with debugging
     * @return string An rb_join object for the specified join, or error
     */
    function get_single_join($join, $usage) {

        if ($match = $this->get_joinlist_item($join)) {
            // return the join object for the item
            return $match;
        } else {
            print_error('joinnotinjoinlist', 'totara_reportbuilder', '', (object)array('join' => $join, 'usage' => $usage));
            return false;
        }
    }

    /**
     * Recursively build an array of {@link rb_join} objects that includes all
     * dependencies
     */
    function get_dependency_joins(&$joins, $joinobj) {

        // get array of dependencies, excluding references to the
        // base table
        if (isset($joinobj->dependencies)
            && is_array($joinobj->dependencies)) {

            $dependencies = array();
            foreach ($joinobj->dependencies as $item) {
                // ignore references to base as a dependency
                if ($item == 'base') {
                    continue;
                }
                $dependencies[] = $item;
            }
        } else if (isset($joinobj->dependencies)
                && $joinobj->dependencies != 'base') {

            $dependencies = array($joinobj->dependencies);
        } else {
            $dependencies = array();
        }

        // loop through dependencies, adding any that aren't already
        // included
        foreach ($dependencies as $dependency) {
            $joinobj = $this->get_single_join($dependency, 'dependencies');
            if (in_array($joinobj, $joins)) {
                // prevents infinite loop if dependencies include
                // circular references
                continue;
            }
            // add to list of current joins
            $joins[] = $joinobj;

            // recursively get dependencies of this dependency
            $this->get_dependency_joins($joins, $joinobj);
        }

    }


    /**
     * Return an array of {@link rb_join} objects containing the joins required by
     * the current enabled content restrictions
     *
     * @return array An array of {@link rb_join} objects containing join information
     */
    function get_content_joins() {
        $reportid = $this->_id;

        if ($this->contentmode == REPORT_BUILDER_CONTENT_MODE_NONE) {
            // no limit on content so no joins necessary
            return array();
        }
        $contentjoins = array();
        foreach ($this->contentoptions as $option) {
            $name = $option->classname;
            $classname = 'rb_' . $name . '_content';
            if (class_exists($classname)) {
                // @TODO take settings form instance, not database, otherwise caching will fail after content settings change
                if (reportbuilder::get_setting($reportid, $name . '_content', 'enable')) {
                    // this content option is enabled
                    // get required joins
                    $contentjoins = array_merge($contentjoins,
                        $this->get_joins($option, 'content'));
                }
            }
        }
        return $contentjoins;
    }

    /**
     * Return an array of strings containing the fields required by
     * the current enabled content restrictions
     *
     * @return array An array for strings conaining SQL snippets for field list
     */
    function get_content_fields() {
        $reportid = $this->_id;

        if ($this->contentmode == REPORT_BUILDER_CONTENT_MODE_NONE) {
            // no limit on content so no joins necessary
            return array();
        }

        $fields = array();
        if (isset($this->contentoptions) && is_array($this->contentoptions)) {
            foreach ($this->contentoptions as $option) {
                $name = $option->classname;
                $classname = 'rb_' . $name . '_content';
                $settingname = $name . '_content';
                if (class_exists($classname)) {
                    if (reportbuilder::get_setting($reportid, $settingname, 'enable')) {
                        foreach ($option->fields as $alias => $field) {
                            $fields[] = $field . ' AS rb_content_option_' . $alias;
                        }
                    }
                }
            }
        }
        return $fields;
    }


    /**
     * Return an array of {@link rb_join} objects containing the joins required by
     * the current column list
     *
     * @return array An array of {@link rb_join} objects containing join information
     */
    function get_column_joins() {
        $coljoins = array();
        foreach ($this->columns as $column) {
            $coljoins = array_merge($coljoins,
                $this->get_joins($column, 'column'));
        }
        return $coljoins;
    }

    /**
     * Return an array of {@link rb_join} objects containing the joins required by
     * the current param list
     *
     * @param bool $all Return all joins even for unused params
     *
     * @return array An array of {@link rb_join} objects containing join information
     */
    function get_param_joins($all = false) {
        $paramjoins = array();
        foreach ($this->_params as $param) {
            $value = $param->value;
            // don't include joins if param not set
            if (!$all && (!isset($value) || $value == '')) {
                continue;
            }
            $paramjoins = array_merge($paramjoins,
                $this->get_joins($param, 'param'));
        }
        return $paramjoins;
    }


    /**
     * Return an array of {@link rb_join} objects containing the joins required by
     * the source joins
     *
     * @return array An array of {@link rb_join} objects containing join information
     */
    function get_source_joins() {
        // no where clause - don't add any joins
        // as they won't be used
        if (empty($this->src->sourcewhere)) {
            return array();
        }

        // no joins specified
        if (empty($this->src->sourcejoins)) {
            return array();
        }

        $item = new stdClass();
        $item->joins = $this->src->sourcejoins;

        return $this->get_joins($item, 'source');

    }

    /**
     * Get list of global restriction joins.
     *
     * @return rb_join[] An array of rb_join objects containing join information.
     */
    public function get_global_restriction_joins() {
        if (empty($this->src->globalrestrictionjoins)) {
            return array();
        }

        $joins = array();
        foreach ($this->src->globalrestrictionjoins as $join) {
            $joins[] = $join;
            $this->get_dependency_joins($joins, $join);
        }

        return $joins;
    }

    /**
     * Get sql parameters used in restrictions (joins and query parts).
     *
     * @return array sql parameters
     */
    public function get_global_restriction_parameters() {
        return $this->src->globalrestrictionparams;
    }

    /**
     * Return an array of {@link rb_join} objects containing the joins of all enabled
     * filters regardless their usage in current request (useful for caching)
     *
     * @return array An array of {@link rb_join} objects containing join information
     */
    function get_all_filter_joins() {
        $filterjoins = array();
        foreach ($this->filters as $filter) {
            $value = $filter->value;
            // Don't include joins if param not set.
            if (!isset($value) || $value == '') {
                continue;
            }
            $filterjoins = array_merge($filterjoins,
                $this->get_joins($filter, 'filter'));
        }
        foreach ($this->searchcolumns as $searchcolumn) {
            $value = $searchcolumn->value;
            // Don't include joins if param not set.
            if (!isset($value) || $value == '') {
                continue;
            }
            $searchcolumnoption = $this->get_single_item($this->columnoptions, $searchcolumn->type, $searchcolumn->value);
            $filterjoins = array_merge($filterjoins,
                $this->get_joins($searchcolumnoption, 'searchcolumn'));
        }
        return $filterjoins;
    }

    /**
     * Check the current session for active filters, and if found
     * collect together join data into a format suitable for {@link get_joins()}
     *
     * @return array An array of arrays containing filter join information
     */
    function get_filter_joins() {
        global $SESSION;
        $filterjoins = array();
        // Check session variable for any active filters.
        // If they exist we need to make sure we have included joins for them too.
        if (isset($SESSION->reportbuilder[$this->get_uniqueid()]) &&
            is_array($SESSION->reportbuilder[$this->get_uniqueid()])) {
            foreach ($SESSION->reportbuilder[$this->get_uniqueid()] as $fname => $unused) {
                if (!array_key_exists($fname, $this->filters)) {
                    continue; // filter not used in this report
                }
                $filter = $this->filters[$fname];

                $filterjoins = array_merge($filterjoins,
                    $this->get_joins($filter, 'filter'));
            }
        }
        // Check session variable for toolbar search text.
        // If it exists we need to make sure we have included joins for it too.
        if (isset($SESSION->reportbuilder[$this->get_uniqueid()]) &&
            isset($SESSION->reportbuilder[$this->get_uniqueid()]['toolbarsearchtext'])) {
            foreach ($this->searchcolumns as $searchcolumn) {
                $columnoption = $this->get_single_item($this->columnoptions, $searchcolumn->type, $searchcolumn->value);
                $filterjoins = array_merge($filterjoins,
                    $this->get_joins($columnoption, 'searchcolumn'));
            }
        }
        return $filterjoins;
    }

    /**
     * Returns true if any filters are in use on this report.
     *
     * @since Totara 2.7.29, 2.9.21, 9.9, 10
     * @return bool
     */
    private function are_any_filters_in_use() {
        global $SESSION;

        if (!isset($SESSION->reportbuilder[$this->get_uniqueid()])) {
            return false;
        }

        if (empty($SESSION->reportbuilder[$this->get_uniqueid()])) {
            return false;
        }

        return true;
    }


    /**
     * Given an array of {@link rb_join} objects, convert them into an SQL snippet
     *
     * @param array $joins Array of {@link rb_join} objects
     *
     * @return string SQL snippet that includes all the joins in the order provided
     */
    function get_join_sql($joins) {
        $out = array();

        foreach ($joins as $join) {
            $name = $join->name;
            $type = $join->type;
            $table = $join->table;
            $conditions = $join->conditions;

            if (array_key_exists($name, $out)) {
                // we've already added this join
                continue;
            }
            // store in associative array so we can tell which
            // joins we've already added
            $sql = "$type JOIN $table $name";
            if (!empty($conditions)) {
                $sql .= "\n        ON $conditions";
            }
            $out[$name] = $sql;
        }
        return implode("\n    ", $out) . " \n";
    }


    /**
     * Sort an array of {@link rb_join} objects
     *
     * Given an array of {@link rb_join} objects, sorts them such that:
     * - any duplicate joins are removed
     * - any joins with dependencies appear after those dependencies
     *
     * This is achieved by repeatedly looping through the list of
     * joins, moving joins to the sorted list only when all their
     * dependencies are already in the sorted list.
     *
     * On the first pass any joins that have no dependencies are
     * saved to the sorted list and removed from the current list.
     *
     * References to the moved items are then removed from the
     * dependencies lists of all the remaining items and the loop
     * is repeated.
     *
     * The loop continues until there is an iteration where no
     * more items are removed. At this point either:
     * - The current list is empty
     * - There are references to joins that don't exist
     * - There are circular references
     *
     * In the later two cases we throw an error, otherwise return
     * the sorted list.
     *
     * @param array Array of {@link rb_join} objects to be sorted
     *
     * @return array Sorted array of {@link rb_join} objects
     */
    function sort_joins($unsortedjoins) {

        // get structured list of dependencies for each join
        $items = $this->get_dependencies_array($unsortedjoins);

        // make an index of the join objects with name as key
        $joinsbyname = array();
        foreach ($unsortedjoins as $join) {
            $joinsbyname[$join->name] = $join;
        }

        // loop through items, storing any that don't have
        // dependencies in the output list

        // safety net to avoid infinite loop if something
        // unexpected happens
        $maxdepth = 50;
        $i = 0;
        $output = array();
        while($i < $maxdepth) {

            // items with empty dependencies array
            $nodeps = $this->get_independent_items($items);

            foreach ($nodeps as $nodep) {
                $output[] = $joinsbyname[$nodep];
                unset($items[$nodep]);
                // remove references to this item from all
                // the other dependency lists
                $this->remove_from_dep_list($items, $nodep);
            }

            // stop when no more items can be removed
            // if all goes well, this will be after all items
            // have been removed
            if (count($nodeps) == 0) {
                break;
            }

            $i++;
        }

        // we shouldn't have any items left once we've left the loop
        if (count($items) != 0) {
            print_error('couldnotsortjoinlist', 'totara_reportbuilder');
        }

        return $output;
    }


    /**
     * Remove joins that have no impact on the results count
     *
     * Given an array of {@link rb_join} objects we want to return a similar list,
     * but with any joins that have no effect on the count removed. This is
     * done for performance reasons when calculating the count.
     *
     * The only joins that can be safely removed match the following criteria:
     * 1- Only LEFT joins are safe to remove
     * 2- Even LEFT joins are unsafe, unless the relationship is either
     *   One-to-one or many-to-one
     * 3- The join can't have any dependencies that don't also match the
     *   criteria above: e.g.:
     *
     *   base LEFT JOIN table_a JOIN table_b
     *
     *   Table_b can't be removed because it fails criteria 1. Table_a
     *   can't be removed, even though it passes criteria 1 and 2, because
     *   table_b is dependent on it.
     *
     * To achieve this result, we use a similar strategy to sort_joins().
     * As a side effect, duplicate joins are removed but note that this
     * method doesn't change the sort order of the joins provided.
     *
     * @param array $unprunedjoins Array of rb_join objects to be pruned
     *
     * @return array Array of {@link rb_join} objects, minus any joins
     *               that don't affect the total record count
     */
    function prune_joins($unprunedjoins) {
        // get structured list of dependencies for each join
        $items = $this->get_dependencies_array($unprunedjoins);

        // make an index of the join objects with name as key
        $joinsbyname = array();
        foreach ($unprunedjoins as $join) {
            $joinsbyname[$join->name] = $join;
        }

        // safety net to avoid infinite loop if something
        // unexpected happens
        $maxdepth = 100;
        $i = 0;
        $output = array();
        while($i < $maxdepth) {
            $prunecount = 0;
            // items with empty dependencies array
            $nodeps = $this->get_nondependent_items($items);
            foreach ($nodeps as $nodep) {
                if ($joinsbyname[$nodep]->pruneable()) {
                    unset($items[$nodep]);
                    $this->remove_from_dep_list($items, $nodep);
                    unset($joinsbyname[$nodep]);
                    $prunecount++;
                }
            }

            // stop when no more items can be removed
            if ($prunecount == 0) {
                break;
            }

            $i++;
        }

        return array_values($joinsbyname);
    }


    /**
     * Reformats an array of {@link rb_join} objects to a structure helpful for managing dependencies
     *
     * Saves the dependency info in the following format:
     *
     * array(
     *    'name1' => array('dep1', 'dep2'),
     *    'name2' => array('dep3'),
     *    'name3' => array(),
     *    'name4' => array(),
     * );
     *
     * This has the effect of:
     * - Removing any duplicate joins (joins with the same name)
     * - Removing any references to 'base' in the dependencies list
     * - Converting null dependencies to array()
     * - Converting string dependencies to array('string')
     *
     * @param array $joins Array of {@link rb_join} objects
     *
     * @return array Array of join dependencies
     */
    private function get_dependencies_array($joins) {
        $items = array();
        foreach ($joins as $join) {

            // group joins in a more consistent way and remove all
            // references to 'base'
            if (is_array($join->dependencies)) {
                $deps = array();
                foreach ($join->dependencies as $dep) {
                    if ($dep == 'base') {
                        continue;
                    }
                    $deps[] = $dep;
                }
                $items[$join->name] = $deps;
            } else if (isset($join->dependencies)
                && $join->dependencies != 'base') {
                $items[$join->name] = array($join->dependencies);
            } else {
                $items[$join->name] = array();
            }
        }
        return $items;
    }


    /**
     * Remove references to a particular join from the
     * join dependencies list
     *
     * Given a list of join dependencies (as generated by
     * get_dependencies_array() ) remove all references to
     * the join named $joinname
     *
     * @param array &$items Array of dependencies. Passed by ref
     * @param string $joinname Name of join to remove from list
     *
     * @return true;
     */
    private function remove_from_dep_list(&$items, $joinname) {
        foreach ($items as $join => $deps) {
            foreach ($deps as $key => $dep) {
                if ($dep == $joinname) {
                    unset($items[$join][$key]);
                }
            }
        }
        return true;
    }


    /**
     * Return a list of items with no dependencies (e.g. the 'tips' of the tree)
     *
     * Given a list of join dependencies (as generated by
     * get_dependencies_array() ) return the names (keys)
     * of elements with no dependencies.
     *
     * @param array $items Array of dependencies
     *
     * @return array Array of names of independent items
     */
    private function get_independent_items($items) {
        $nodeps = array();
        foreach ($items as $join => $deps) {
            if (count($deps) == 0) {
                $nodeps[] = $join;
            }
        }
        return $nodeps;
    }


    /**
     * Return a list of items which no other items depend on (e.g the 'base' of
     * the tree)
     *
     * Given a list of join dependencies (as generated by
     * get_dependencies_array() ) return the names (keys)
     * of elements which are not dependent on any other items
     *
     * @param array $items Array of dependencies
     *
     * @return array Array of names of non-dependent items
     */
    private function get_nondependent_items($items) {
        $alldeps = array();
        // get all the dependencies in one array
        foreach ($items as $join => $deps) {
            foreach ($deps as $dep) {
                $alldeps[] = $dep;
            }
        }
        $nondeps = array();
        foreach (array_keys($items) as $join) {
            if (!in_array($join, $alldeps)) {
                $nondeps[] = $join;
            }
        }
        return $nondeps;
    }


    /**
     * Returns the ORDER BY SQL snippet for the current report
     *
     * @param object $table Flexible table object to use to find the sort parameters (optional)
     *                      If not provided a new object will be created based on the report's
     *                      shortname, false means use default sort only
     *
     * @return string SQL string to order the report to be appended to the main query
     */
    public function get_report_sort($table = null) {
        global $SESSION;

        // check the sort session var doesn't contain old columns that no
        // longer exist
        $this->check_sort_keys();

        // unless the table object is provided we need to call get_sql_sort() statically
        // and pass in the report's unique id (shortname)
        if ($table === false) {
            $sort = '';
        } else if ($table === null) {
            $sort = trim(flexible_table::get_sort_for_table($this->get_uniqueid('rb')));
        } else {
            $sort = trim($table->get_sql_sort());
        }

        if ($sort === '' and $this->defaultsortcolumn) {
            // Use default sort if no valid sort found in user session or preferences.
            $exists = false;
            foreach ($this->columns as $col) {
                if ($col->type . '_' . $col->value === $this->defaultsortcolumn) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                $sort = $this->defaultsortcolumn;
                if ($this->defaultsortorder == SORT_DESC) {
                    $sort .= ' DESC';
                }
            }
        }

        // always include the base id as a last resort to ensure order is
        // predetermined for pagination
        $baseid = $this->grouped ? 'min(base.id)' : 'base.id';
        $order = ($sort != '') ? " ORDER BY $sort, $baseid" : " ORDER BY $baseid";

        return $order;
    }

    /**
     * Get DB instance used for fetching of report data.
     *
     * @return moodle_database
     */
    public function get_report_db() {
        global $DB;
        if ($this->cache or !$this->useclonedb) {
            return $DB;
        }

        $db = totara_get_clone_db();
        if (!$db) {
            return $DB;
        }
        return $db;
    }

    /**
     * Returns the fetch method this report should use.
     *
     * @return int One of self::FETCHMETHOD_*
     */
    private function get_fetch_method() {
        global $DB;

        $value = $this->fetchmethod;

        if ($value === self::FETCHMETHOD_STANDARD_RECORDSET) {
            return self::FETCHMETHOD_STANDARD_RECORDSET;
        }
        if ($value === self::FETCHMETHOD_COUNTED_RECORDSET) {
            return self::FETCHMETHOD_COUNTED_RECORDSET;
        }

        $default = self::get_default_fetch_method();
        if ($default === self::FETCHMETHOD_STANDARD_RECORDSET) {
            return self::FETCHMETHOD_STANDARD_RECORDSET;
        }
        if ($default === self::FETCHMETHOD_COUNTED_RECORDSET) {
            return self::FETCHMETHOD_COUNTED_RECORDSET;
        }

        if ($DB->recommends_counted_recordset()) {
            return self::FETCHMETHOD_COUNTED_RECORDSET;
        }

        return self::FETCHMETHOD_STANDARD_RECORDSET;
    }

    /**
     * Returns the default fetch method for report builder reports.
     *
     * @return int One of self::FETCHMETHOD_*
     */
    public static function get_default_fetch_method() {
        $default = get_config('totara_reportbuidler', 'defaultfetchmethod');
        if ($default !== false) {
            $default = (int)$default;
            if ($default === self::FETCHMETHOD_STANDARD_RECORDSET) {
                return self::FETCHMETHOD_STANDARD_RECORDSET;
            }
            if ($default === self::FETCHMETHOD_COUNTED_RECORDSET) {
                return self::FETCHMETHOD_COUNTED_RECORDSET;
            }
        }
        return self::FETCHMETHOD_DATABASE_RECOMMENDATION;
    }

    /**
     * Is report caching enabled and cache is ready and not cache is not ignored
     *
     * @return bool
     */
    public function is_cached() {
        if ($this->cacheignore or !$this->cache) {
            return false;
        }

        if ($this->get_cache_table()) {
            return true;
        }
    }

    /**
     * Returns cache status.
     * @return int constants RB_CACHE_FLAG_*
     */
    public function get_cache_status() {
        global $DB;

        if (!$this->cache) {
            return RB_CACHE_FLAG_NONE;
        }

        if (!$this->cacheschedule) {
            return RB_CACHE_FLAG_CHANGED;
        }

        if ($this->cacheschedule->genstart) {
            return RB_CACHE_FLAG_GEN;
        }

        if ($this->cacheschedule->changed) {
            return RB_CACHE_FLAG_CHANGED;
        }

        if (!$this->cacheschedule->cachetable) {
            return RB_CACHE_FLAG_FAIL;
        }

        if ($this->cachetable) {
            // Shortcut.
            return RB_CACHE_FLAG_OK;
        }

        list($query, $params) = $this->build_create_cache_query();
        if (sha1($query.serialize($params)) === $this->cacheschedule->queryhash) {
            $this->cachetable = $this->cacheschedule->cachetable;
            return RB_CACHE_FLAG_OK;
        }

        $this->cachetable = false;
        $DB->set_field('report_builder_cache', 'changed', RB_CACHE_FLAG_CHANGED, array('id' => $this->cacheschedule->id));
        $this->cacheschedule->changed = RB_CACHE_FLAG_CHANGED;
    }

    public function get_cache_table() {
        $status = $this->get_cache_status();
        if ($status !== RB_CACHE_FLAG_OK) {
            return false;
        }

        return $this->cachetable;
    }

    /**
     * This function builds the main SQL query used to generate cache for report
     *
     * @return array containing the full SQL query and SQL params
     */
    function build_create_cache_query() {
        // Save report instance state
        $paramssave = $this->_params;
        $groupedsave = $this->grouped;
        // Prepare instance to generate cache:
        // - Disable grouping
        // - Enable all params (not only used in request)
        $this->cacheignore = true;
        $this->_params = $this->get_current_params(true);
        $this->grouped = false;
        // get the fields required by display, any filter, param, or content option used in report
        $fields = array_merge($this->get_column_fields(rb_column::NOGROUP),
                              $this->get_content_fields(),
                              $this->get_alias_fields($this->filters),
                              $this->get_alias_fields($this->_params));
        // Include all search columns (but not their extrafields).
        foreach ($this->searchcolumns as $searchcolumn) {
            $searchcolumnoption = $this->get_single_item($this->columnoptions, $searchcolumn->type, $searchcolumn->value);
            $fields[] = $searchcolumnoption->field . " AS " . $searchcolumnoption->type . "_" . $searchcolumnoption->value;
        }
        $fields = array_unique($fields);
        $joins = $this->collect_joins(reportbuilder::FILTERALL);

        $where = array();
        $sqlparams = array();
        if (!empty($this->src->sourcewhere)) {
            $where[] = $this->src->sourcewhere;

            if (!empty($this->src->sourceparams)) {
                $sqlparams = array_merge($sqlparams, $this->src->sourceparams);
            }
        }
        $sql = $this->collect_sql($fields, $this->src->base, $joins, $where);

        // Revert report instance state
        $this->_params = $paramssave;
        $this->cacheignore = false;
        $this->grouped = $groupedsave;
        return array($sql, $sqlparams);
    }

    /**
     * This function builds main cached SQL query to get the data for page
     *
     * @return array array($sql, $params, $cache). If no cache found array('', array(), array()) will be returned
     */
    public function build_cache_query($countonly = false, $filtered = false) {
        if (!$this->is_cached()) {
            return array('', array(), array());
        }
        $table = $this->get_cache_table();
        $fields = $this->get_column_fields(rb_column::CACHE);

        list($where, $group, $having, $sqlparams, $allgrouped) = $this->collect_restrictions($filtered, true);

        $sql = $this->collect_sql($fields, $table, array(), $where, $group, $having,
                                  $countonly, $allgrouped);

        return array($sql, $sqlparams, (array)$this->cacheschedule);
    }

    /**
     * Returns the cache schedule object OR false if caching is not being used or has not been generated.
     *
     * @since Totara 2.7.29, 2.9.21, 9.9, 10
     * @return bool|stdClass A cacheschedule record from the database for this report or false if caching
     *    is not enabled or this report is not cached.
     */
    private function get_cache_schedule() {
        global $CFG;
        if (empty($CFG->enablereportcaching) || !$this->is_cached() || !$this->cacheschedule) {
            return false;
        }
        return $this->cacheschedule;
    }

    /**
     * This function builds the main SQL query used to get the data for the page
     *
     * @param boolean $countonly If true returns SQL to count results, otherwise the
     *                           query requests the fields needed for columns too.
     * @param boolean $filtered If true, includes any active filters in the query,
     *                           otherwise returns results without filtering
     * @param boolean $allowcache If true tries to use cache for query
     * @return array containing the full SQL query, SQL params, and cache meta information
     */
    function build_query($countonly = false, $filtered = false, $allowcache = true) {
        global $CFG;

        if ($allowcache && !empty($CFG->enablereportcaching)) {
            $cached = $this->build_cache_query($countonly, $filtered);
            if ($cached[0] != '') {
                return $cached;
            }
        }

        $mode = rb_column::REGULAR;
        if ($this->grouped) {
            $mode = rb_column::REGULARGROUPED;
        }
        $fields = $this->get_column_fields($mode);

        $filter = ($filtered) ? reportbuilder::FILTER : reportbuilder::FILTERNONE;
        $joins = $this->collect_joins($filter, $countonly);

        list($where, $group, $having, $sqlparams, $allgrouped) = $this->collect_restrictions($filtered);

        // Addglobal restriction params.
        $params = $this->get_global_restriction_parameters();
        $sqlparams = array_merge($sqlparams, $params);

        // apply any SQL specified by the source
        if (!empty($this->src->sourcewhere)) {
            $where[] = $this->src->sourcewhere;

            if (!empty($this->src->sourceparams)) {
                $sqlparams = array_merge($sqlparams, $this->src->sourceparams);
            }
        }
        $sql = $this->collect_sql($fields, $this->src->base, $joins, $where, $group, $having, $countonly, $allgrouped);

        return array($sql, $sqlparams, array());
    }

    /**
     * Add counts indicating how many records match each option in the sidebar.
     * Only filter types which define get_showcount_params will show anything.
     *
     * @param type $mform form to add the counts onto, which already has filters added
     */
    public function add_filter_counts($mform) {
        global $DB;

        // The counts do not make much sense if we aggregate rows,
        // better not show it at all and it also allows us to keep this code as-is,
        // this prevents performance problems too.
        $showcountfilters = array();
        foreach ($this->columns as $column) {
            if ($column->aggregate) {
                return;
            }
        }

        $iscached = $this->is_cached();
        $isgrouped = $this->grouped;
        $filters = $this->get_sidebar_filters();
        $fields = array();
        $groupfields = array();
        $extrajoins = array();

        // Find all the showcount filters.
        foreach ($filters as $filter) {
            $showcountparams = $filter->get_showcount_params();
            if ($showcountparams !== false) {
                $showcountfilters[] = $filter;

                if ($iscached) {
                    // Get these extra fields from the base query.
                    $fields[] = $filter->fieldalias;
                } else {
                    // Get any required fields from the base query.
                    if (isset($showcountparams['basefields'])) {
                        $fields = array_merge($fields, $showcountparams['basefields']);
                    }
                    if ($isgrouped && isset($showcountparams['basegroups'])) {
                        $groupfields = array_merge($groupfields, $showcountparams['basegroups']);
                    }
                    if ($isgrouped) {
                        $fields[] = "{$filter->field} AS {$filter->fieldalias}";
                        $groupfields[] = "{$filter->field}";
                    }

                    // Compile a list of extra joins (which will supply the fields above) that should be added to the base query.
                    if (isset($showcountparams['dependency']) && $showcountparams['dependency'] != 'base') {
                        $dependency = $this->get_single_join($showcountparams['dependency'], 'filtercount');
                        $this->get_dependency_joins($extrajoins, $dependency);
                        $extrajoins[] = $dependency;
                    }
                    if ($isgrouped and $filter->joins != 'base') {
                        $extrajoins[] = $this->get_single_join($filter->joins, 'filtercount');
                    }
                }

                // Temporarily deactivate the filter so that it is not included in the base sql query.
                $filter->save_temp_data(null);
            }
        }

        // If the base query uses grouping then we need to include all column fields (so that each field can be grouped).
        if ($isgrouped && !$iscached) {
            $fields = array_unique(array_merge($fields, $this->get_column_fields(rb_column::REGULARGROUPED)));
        }

        // If there are none then return, because we do not want to generate an empty query.
        if (empty($showcountfilters)) {
            return;
        }

        // Get all joins for required child tables and active filters.
        if (!$iscached) {
            // Grouped reports will include all joins in the base query.
            $basejoins = $this->collect_joins(self::FILTER, !$isgrouped);
            $joins = array_merge($basejoins, $extrajoins);
        } else {
            $joins = array();
        }

        // Get all conditions for active filters (except the ones we deactivated).
        list($where, $group, $having, $sqlparams, $allgrouped) = $this->collect_restrictions(true, $iscached);

        if ($isgrouped && !empty($groupfields)) {
            $group = array_unique(array_merge($group, $groupfields));
        }

        // Apply any SQL specified by the source.
        if (!$iscached && !empty($this->src->sourcewhere)) {
            $where[] = $this->src->sourcewhere;

            if (!empty($this->src->sourceparams)) {
                $sqlparams = array_merge($sqlparams, $this->src->sourceparams);
            }
        }

        // Get the base sql query with all other joins and (active) filters applied.
        if ($iscached) {
            $base = $this->get_cache_table();
        } else {
            $base = $this->src->base;
        }
        $basesql = $this->collect_sql($fields, $base, $joins, $where, $group, $having, false, $allgrouped);

        // Restore all saved filters before we start constructing the main query (must restore ALL filters before the next loop).
        foreach ($showcountfilters as $filter) {
            $filter->restore_temp_data();
        }

        $countscolumns = array();
        $filtersplustotalscolumns = array("filters.*");
        $filterscolumns = array("base.id");
        $showcountjoins = array();

        // Get sql snipets and params for each showcount filter.
        foreach ($showcountfilters as $filter) {
            list($addcountscolumns, $addfiltersplustotalscolumn, $addfilterscolumns, $addshowcountjoins, $addsqlparams) =
                    $filter->get_counts_sql($showcountfilters);
            $countscolumns = array_merge($countscolumns, $addcountscolumns);
            $filtersplustotalscolumns[] = $addfiltersplustotalscolumn;
            $filterscolumns = array_merge($filterscolumns, $addfilterscolumns);
            $showcountjoins = array_merge($showcountjoins, $addshowcountjoins);
            $sqlparams = array_merge($sqlparams, $addsqlparams);
        }

        // Remove duplicate joins.
        $uniqueshowcountjoins = array_unique($showcountjoins);

        // Only run the count sql if there is something to count.
        if (!empty($countscolumns)) {
            // Construct the main query.
            $sql = "SELECT\n" . implode(",\n", $countscolumns) . "\nFROM\n(\n" .
                   "   SELECT " . implode(",\n", $filtersplustotalscolumns) . "\n   FROM\n   (\n" .
                   "      SELECT " . implode(",\n", $filterscolumns) . "\n      FROM (\n\n" . $basesql . "\n      ) base\n" .
                             implode("\n", $uniqueshowcountjoins) . "\n      GROUP BY base.id\n" .
                   "   ) filters\n" . ") filtersplustotals";
            $counts = $DB->get_record_sql($sql, $sqlparams);

            // Put the counts into the form.
            foreach ($showcountfilters as $filter) {
                $filter->set_counts($mform, $counts);
            }
        }
    }

    /**
     * Return SQL snippet for field name depending on report cache settings.
     *
     * This is intended to be used during post_config.
     */
    public function get_field($type, $value, $field) {
        if ($this->is_cached()) {
            return $type . '_' . $value;
        }
        return $field;
    }

    /**
     * Get joins used for query building
     *
     * @param int $filtered reportbuilder::FILTERNONE - for no filter joins,
     *             reportbuilder::FILTER - for enabled filters, reportbuilder::FILTERALL - for all filters
     * @param bool $countonly If true prune joins that don't influent on resulting count
     * @return array of {@link rb_join} objects
     */
    protected function collect_joins($filtered, $countonly = false) {
        // get the joins needed to display requested columns and do filtering and restrictions
        $columnjoins = $this->get_column_joins();

        // if we are only counting, don't need all the column joins. Remove
        // any that don't affect the count
        if ($countonly && !$this->grouped) {
            $columnjoins = $this->prune_joins($columnjoins);
        }
        if ($filtered == reportbuilder::FILTERALL) {
            $filterjoins = $this->get_all_filter_joins();
        } else if ($filtered == reportbuilder::FILTER) {
            $filterjoins = $this->get_filter_joins();
        } else {
            $filterjoins = array();
        }
        $paramjoins = $this->get_param_joins(true);
        $contentjoins = $this->get_content_joins();
        $sourcejoins = $this->get_source_joins();
        $globalrestrictionjoins = $this->get_global_restriction_joins();

        $joins = array_merge($columnjoins, $filterjoins, $paramjoins, $contentjoins, $sourcejoins, $globalrestrictionjoins);

        // sort the joins to remove duplicates and resolve any dependencies
        $joins = $this->sort_joins($joins);
        return $joins;
    }

    /**
     * Get all restrictions to filter query
     *
     * @param bool $cache
     * @return array of arrays of strings array(where, group, having, bool allgrouped)
     */
    protected function collect_restrictions($filtered, $cache = false) {
        global $DB;
        $where = array();
        $group = array();
        $having = array();
        $sqlparams = array();
        list($restrictions, $contentparams) = $this->get_content_restrictions($cache);
        if ($restrictions != '') {
            $where[] = $restrictions;
            $sqlparams = array_merge($sqlparams, $contentparams);
        }
        unset($contentparams);

        if ($filtered === true) {
            list($sqls, $filterparams) = $this->fetch_sql_filters();
            if (isset($sqls['where']) && $sqls['where'] != '') {
                $where[] = $sqls['where'];
            }
            if (isset($sqls['having']) && $sqls['having'] != '') {
                $having[] = $sqls['having'];
            }
            $sqlparams = array_merge($sqlparams, $filterparams);
            unset($filterparams);
        }

        list($paramrestrictions, $paramparams) = $this->get_param_restrictions($cache);
        if ($paramrestrictions != '') {
            $where[] = $paramrestrictions;
            $sqlparams = array_merge($sqlparams, $paramparams);
        }
        unset($paramparams);

        list($postconfigrestrictions, $postconfigparams) = $this->get_post_config_restrictions();
        if ($postconfigrestrictions != '') {
            $where[] = $postconfigrestrictions;
            $sqlparams = array_merge($sqlparams, $postconfigparams);
        }
        unset($postconfigparams);

        $allgrouped = true;

        if ($this->grouped) {
            $group = array();
            $groupbymode = ($cache ? rb_column::GROUPBYCACHE : rb_column::GROUPBYREGULAR);

            foreach ($this->columns as $column) {
                if ($column->grouping !== 'none') {
                    // We still need to add extrafields to the GROUP BY if there is a displayfunc.
                    if ($column->extrafields && $column->get_displayfunc()) {
                        $fields = $column->get_extra_fields($groupbymode);
                        foreach ($fields as $field) {
                            if (!in_array($field, $group)) {
                                $group[] = $field;
                                $allgrouped = false;
                            }
                        }
                    }

                } else if ($column->transform) {
                    $allgrouped = false;
                    $group = array_merge($group, $column->get_fields($this->src, $groupbymode, true));

                } else if ($column->aggregate) {
                    // No need to add GROUP BY for extra fields
                    // because the display functions in aggregations do not need extra columns.

                } else { // Column grouping is 'none'.
                    $allgrouped = false;
                    $group = array_merge($group, $column->get_fields($this->src, $groupbymode, true));
                }
            }
        }

        return array($where, $group, $having, $sqlparams, $allgrouped);
    }

    /**
     * Compile SQL query from prepared parts
     *
     * @param array $fields
     * @param string $base
     * @param array $joins
     * @param array $where
     * @param array $group
     * @param array $having
     * @param bool $countonly
     * @param bool $allgrouped
     * @return string
     */
    protected function collect_sql(array $fields, $base, array $joins, array $where = null,
                                    array $group = null, array $having = null, $countonly = false,
                                    $allgrouped = false) {

        if ($countonly && !$this->grouped) {
            $selectsql = "SELECT COUNT(*) ";
        } else {
            $baseid = ($this->grouped) ? "min(base.id) AS id" : "base.id";
            array_unshift($fields, $baseid);
            $selectsql = "SELECT " . implode($fields, ",\n     ") . " \n";

        }
        $joinssql = (count($joins) > 0) ? $this->get_join_sql($joins) : '';

        $fromsql = "FROM {$base} base\n    " . $joinssql;

        $wheresql = (count($where) > 0) ? "WHERE " . implode("\n    AND ", $where) . "\n" : '';

        $groupsql = '';
        if ($group && !$allgrouped) {
            $groupsql = ' GROUP BY ' . implode(', ', $group) . ' ';
        }

        $havingsql = '';
        if ($having) {
            $havingsql = ' HAVING ' . implode(' AND ', $having) . "\n";
        }

        if ($countonly && $this->grouped) {
            $sql = "SELECT COUNT(*) FROM ($selectsql $fromsql $wheresql $groupsql $havingsql) AS query";
        } else {
            $sql = "$selectsql $fromsql $wheresql $groupsql $havingsql";
        }
        return $sql;
    }

    /**
     * Sets the filtered count (filtered report)
     *
     * Additionally if no filters are used then this sets the full count also given they would be identical.
     *
     * @since Totara 2.7.29, 2.9.21, 9.9, 10
     * @param int $count
     */
    private function set_filtered_count($count) {
        $this->_filteredcount = (int)$count;
        if ($this->_fullcount === null && $this->can_display_total_count() && !$this->are_any_filters_in_use()) {
            // There are no filters in use, fullcount and filtered count are going to be the same.
            $this->_fullcount = $this->_filteredcount;
        }
    }

    /**
     * Sets the full count (unfiltered report)
     *
     * Additionally if no filters are used then this sets filtered count also given they would be identical.
     *
     * @since Totara 2.7.29, 2.9.21, 9.9, 10
     * @param int $count
     */
    private function set_full_count($count) {
        $this->_fullcount = (int)$count;
        if ($this->_filteredcount === null && !$this->are_any_filters_in_use()) {
            // There are no filters in use, fullcount and filtered count are going to be the same.
            $this->_filteredcount = $this->_fullcount;
        }
    }

    /**
     * Return the total number of records in this report (after any
     * restrictions have been applied but before any filters)
     *
     * @return integer Record count
     */
    function get_full_count() {
        global $CFG;

        if (!$this->can_access()) {
            throw new moodle_exception('nopermission', 'totara_reportbuilder');
        }

        // Don't do the calculation if the results are initially hidden.
        if ($this->is_initially_hidden()) {
            return 0;
        }

        if (!$this->can_display_total_count()) {
            // Return null if we cannot display the total count, its better than 0 in the situation that the calling code code
            // didn't check if the total count can be displayed before asking for it because the report that is being
            // displayed likely still has records.
            debugging('Please check if the total count is available before attempting to get it. Call can_display_total_count() to check.', DEBUG_DEVELOPER);
            return null;
        }

        // Use cached value if present.
        if (empty($this->_fullcount)) {
            list($sql, $params) = $this->build_query(true);
            try {
                $reportdb = $this->get_report_db();
                $this->set_full_count($reportdb->count_records_sql($sql, $params));
            } catch (dml_read_exception $e) {
                // We are wrapping this exception to provide a more user friendly error message.
                if ($this->is_cached()) {
                    $message = 'error:problemobtainingcachedreportdata';
                } else {
                    $message = 'error:problemobtainingreportdata';
                }
                print_error($message, 'totara_reportbuilder', $e->getMessage(), $e->debuginfo);
            }
        }
        return $this->_fullcount;
    }

    /**
     * Return the number of filtered records in this report
     *
     * @param bool $nocache Ignore cache
     * @return integer Filtered record count
     */
    public function get_filtered_count($nocache = false) {
        global $CFG;

        if (!$this->can_access()) {
            throw new moodle_exception('nopermission', 'totara_reportbuilder');
        }

        // Don't do the calculation if the results are initially hidden.
        if ($this->is_initially_hidden()) {
            return 0;
        }

        // Use cached value if present.
        if (empty($this->_filteredcount) || $nocache) {
            list($sql, $params) = $this->build_query(true, true);
            try {
                $reportdb = $this->get_report_db();
                $this->set_filtered_count($reportdb->count_records_sql($sql, $params));
            } catch (dml_read_exception $e) {
                // We are wrapping this exception to provide a more user friendly error message.
                if ($this->is_cached()) {
                    $message = 'error:problemobtainingcachedreportdata';
                } else {
                    $message = 'error:problemobtainingreportdata';
                }
                print_error($message, 'totara_reportbuilder', '', $e->getMessage(), $e->debuginfo);
            }
        }
        return $this->_filteredcount;
    }

    /**
     * Exports the data from the current results, maintaining
     * sort order and active filters but removing pagination
     *
     * @param string $format Format for the export ods/csv/xls
     * @return void No return but initiates save dialog
     */
    function export_data($format) {
        if (!$this->can_access()) {
            throw new moodle_exception('nopermission', 'totara_reportbuilder');
        }

        // Release session lock and make sure abort is not ignored.
        \core\session\manager::write_close();
        ignore_user_abort(false);

        $format = \totara_core\tabexport_writer::normalise_format($format);
        if ($format === 'fusion') {
            $this->download_fusion(); // Redirect.
            die;
        }

        $formats = \totara_core\tabexport_writer::get_export_classes();
        if (!isset($formats[$format])) {
            // Unfortunately there is really nothing we can do here. A download is expected, but we
            // can't provide one.
            throw new coding_exception('Invalid format '.$format);
        }
        $writerclass = $formats[$format];

        $fullname = strtolower(preg_replace(array('/[^a-zA-Z\d\s\-_]/', '/[\s-]/'), array('', '_'), format_string($this->fullname)));
        $filename = clean_filename($fullname . '_report');

        $source = new \totara_reportbuilder\tabexport_source($this);

        /** @var \totara_core\tabexport_writer $writer */
        $writer = new $writerclass($source);

        // Log export event.
        \totara_reportbuilder\event\report_exported::create_from_report($this, $format)->trigger();

        $writer->send_file($filename);
        die;
    }

    /**
     * Display the results table
     *
     * @param bool $return If set to true HTML will be returned instead of echoed out.
     * @return string|void No return value but prints the current data table
     */
    public function display_table($return = false) {
        global $SESSION, $OUTPUT, $PAGE;

        if (!$this->can_access()) {
            throw new moodle_exception('nopermission', 'totara_reportbuilder');
        }

        $initiallyhidden = $this->is_initially_hidden();

        if (!defined('SHOW_ALL_PAGE_SIZE')) {
            define('SHOW_ALL_PAGE_SIZE', 9999);
        }
        if (defined('DEFAULT_PAGE_SIZE')) {
            $this->recordsperpage = DEFAULT_PAGE_SIZE;
        }
        $perpage   = optional_param('perpage', $this->recordsperpage, PARAM_INT);

        $columns = $this->columns;
        $shortname = $this->shortname;

        if (count($columns) == 0) {
            $html = html_writer::tag('p', get_string('error:nocolumnsdefined', 'totara_reportbuilder'));
            if ($return) {
                return $html;
            }
            echo $html;
            return;
        }

        $graph = null;
        if (!totara_feature_disabled('reportgraphs')) {
            $graph = new \totara_reportbuilder\local\graph($this);
            if (!$graph->is_valid()) {
                $graph = null;
            }
        }

        $tablecolumns = array();
        $tableheaders = array();
        foreach ($columns as $column) {
            $type = $column->type;
            $value = $column->value;
            if ($column->display_column(false)) {
                $tablecolumns[] = "{$type}_{$value}"; // used for sorting
                $tableheaders[] = $this->format_column_heading($column, false);
            }
        }

        // Arrgh, the crazy table outputs each row immediately...
        ob_start();

        $classes = '';

        // If we're displaying the sidebar filters we need the content to be responsive.
        if ($this->get_sidebar_filters()) {
            $classes = ' rb-has-sidebar';
        }

        // If it's an embedded report, put the shortname in the class. Can be used in css/js to select the specific report.
        if ($this->embedded) {
            $classes .= ' embeddedshortname_' . $shortname;
        }

        // Prevent notifications boxes inside the table.
        echo $OUTPUT->container_start('nobox rb-display-table-container no-overflow' . $classes, $this->_id);

        // Output cache information if needed.
        $cacheschedule = $this->get_cache_schedule();
        if ($cacheschedule) {
            $lastreport = userdate($cacheschedule->lastreport);
            $nextreport = userdate($cacheschedule->nextreport);

            $html = html_writer::start_tag('div', array('class' => 'noticebox'));
            $html .= get_string('report:cachelast', 'totara_reportbuilder', $lastreport);
            $html .= html_writer::empty_tag('br');
            $html .= get_string('report:cachenext', 'totara_reportbuilder', $nextreport);
            $html .= html_writer::end_tag('div');
            echo $html;
        }

        // Start the table.
        $table = new totara_table($this->get_uniqueid('rb'));

        if (!$this->hidetoolbar && $this->toolbarsearch && $this->has_toolbar_filter()) {
            $toolbarsearchtext = isset($SESSION->reportbuilder[$this->get_uniqueid()]['toolbarsearchtext']) ?
                    $SESSION->reportbuilder[$this->get_uniqueid()]['toolbarsearchtext'] : '';
            $mform = new report_builder_toolbar_search_form($this->get_current_url(),
                    array('toolbarsearchtext' => $toolbarsearchtext), 'post', '', null, true, null, 'toolbarsearch');
            $table->add_toolbar_content($mform->render());

            if ($this->embedded && $content = $this->embedobj->get_extrabuttons()) {
                $table->add_toolbar_content($content, 'right');
            }
        }

        $showhidecolumn = array();
        if (isset($SESSION->rb_showhide_columns[$shortname])) {
            $showhidecolumn = $SESSION->rb_showhide_columns[$shortname];
        }
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($this->get_current_url());
        foreach ($columns as $column) {
            if ($column->display_column()) {
                $ident = "{$column->type}_{$column->value}";
                // Assign $type_$value class to each column.
                $classes = $ident;
                // Apply any column-specific class.
                if (is_array($column->class)) {
                    foreach ($column->class as $class) {
                        $classes .= ' ' . $class;
                    }
                }
                $table->column_class($ident, $classes);
                // Apply any column-specific styling.
                if (is_array($column->style)) {
                    foreach ($column->style as $property => $value) {
                        $table->column_style($ident, $property, $value);
                    }
                }
                if (isset($showhidecolumn[$ident])) {
                    // Session show/hide is set, so use it, and ignore column default.
                    if ((int)$showhidecolumn[$ident] == 0) {
                        $table->column_style($ident, 'display', 'none');
                    }
                } else {
                    // No session set, so use default show/hide value.
                    if ($column->hidden != 0) {
                        $table->column_style($ident, 'display', 'none');
                    }
                }

                // Disable sorting on column where indicated.
                if ($column->nosort) {
                    $table->no_sorting($ident);
                }
            }
        }
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', $shortname);
        $table->set_attribute('class', 'logtable generalbox reportbuilder-table');
        $table->set_attribute('data-source', clean_param(get_class($this->src), PARAM_ALPHANUMEXT));
        $table->set_control_variables(array(
            TABLE_VAR_SORT    => 'ssort',
            TABLE_VAR_HIDE    => 'shide',
            TABLE_VAR_SHOW    => 'sshow',
            TABLE_VAR_IFIRST  => 'sifirst',
            TABLE_VAR_ILAST   => 'silast',
            TABLE_VAR_PAGE    => 'spage'
        ));
        $table->sortable(true, $this->defaultsortcolumn, $this->defaultsortorder); // sort by name by default
        $table->set_pagesize($perpage);
        $table->pageable(true);
        $table->setup();
        $table->initialbars(true);

        if ($initiallyhidden) {
            $table->pagesize($perpage, $this->get_filtered_count());
            $table->set_no_records_message(get_string('initialdisplay_pending', 'totara_reportbuilder'));
        } else {
            $records = $this->get_data_for_table($table);
            $count = $this->get_filtered_count();

            $table->pagesize($perpage, $count);
            $table->add_toolbar_pagination('right', 'both');
            if ($this->is_report_filtered()) {
                $table->set_no_records_message(get_string('norecordswithfilter', 'totara_reportbuilder'));
            } else {
                $table->set_no_records_message(get_string('norecordsinreport', 'totara_reportbuilder'));
            }

            $require_complete_graph = ($count <= $perpage);

            $location = 0;
            foreach ($records as $record) {
                $record_data = $this->src->process_data_row($record, 'html', $this);
                foreach ($record_data as $k => $v) {
                    if ((string)$v === '') {
                        // We do not want empty cells in HTML table.
                        $record_data[$k] = '&nbsp;';
                    }
                }
                if (++$location == $count % $perpage || $location == $perpage) {
                    $table->add_data($record_data, 'last');
                } else {
                    $table->add_data($record_data);
                }

                if ($graph and !$require_complete_graph) {
                    $graph->add_record($record);
                }
            }

            // Close the recordset.
            $records->close();

            if ($graph and $require_complete_graph) {
                $records = $this->get_data(
                    $this->get_report_sort($table),
                    0,
                    $graph->get_max_records(),
                    self::FETCHMETHOD_STANDARD_RECORDSET
                );
                foreach ($records as $record) {
                    $graph->add_record($record);
                }
                $records->close();
            }
        }

        // The rows are already displayed.
        $table->finish_html();

        // end of .nobox div
        echo $OUTPUT->container_end();

        $tablehtml = ob_get_clean();

        $this->are_any_filters_in_use();

        if ($graph and $graphdata = $graph->fetch_svg()) {
            if (core_useragent::check_browser_version('MSIE', '6.0') and !core_useragent::check_browser_version('MSIE', '9.0')) {
                // See http://partners.adobe.com/public/developer/en/acrobat/PDFOpenParameters.pdf
                $svgurl = new moodle_url('/totara/reportbuilder/ajax/graph.php', array('id' => $this->_id, 'sid' => $this->_sid));
                if ($this->globalrestrictionset) {
                    // Add the global restriction ids.
                    $restrictionids = $this->globalrestrictionset->get_current_restriction_ids();
                    if ($restrictionids) {
                        $svgurl->param('globalrestrictionids', implode(',', $restrictionids));
                    }
                }
                $svgurl = $svgurl . '#toolbar=0&navpanes=0&scrollbar=0&statusbar=0&viewrect=20,20,400,300';
                $nopdf = get_string('error:nopdf', 'totara_reportbuilder');
                $attrs = array('type' => 'application/pdf', 'data' => $svgurl, 'width'=> '100%', 'height' => '400');
                $objhtml = html_writer::tag('object', $nopdf, $attrs);
                $tablehtml = html_writer::div($objhtml, 'rb-report-pdfgraph') . $tablehtml;
            } else {
                // The SVGGraph supports only one SVG per page when embedding directly,
                // it should be fine here because there are no blocks on this page.
                $tablehtml = html_writer::div($graphdata, 'rb-report-svggraph') . $tablehtml;
            }
        } else {
            // Keep the instantfilter.js happy, we use it with side filter js.
            if (core_useragent::check_browser_version('MSIE', '6.0') and !core_useragent::check_browser_version('MSIE', '9.0')) {
                // Support MSIE 6-7-8.
                $tablehtml = html_writer::div('', 'rb-report-pdfgraph') . $tablehtml;
            } else {
                // All browsers, except MSIE 6-7-8.
                $tablehtml = html_writer::div('', 'rb-report-svggraph') . $tablehtml;
            }
        }

        $jsmodule = array(
            'name' => 'totara_reportbuilder_expand',
            'fullpath' => '/totara/reportbuilder/js/expand.js',
            'requires' => array('json'));
        $PAGE->requires->js_init_call('M.totara_reportbuilder_expand.init', array(), true, $jsmodule);

        if ($return) {
            return $tablehtml;
        }
        echo $tablehtml;

    }

    /**
     * Gets a counted recordset for the given query, or displays a friendly error.
     *
     * Wrapped into a separate function so that we can ensure that all errors given when producing the data are friendly.
     *
     * @deprecated since Totara 12.4 as it is no longer needed. See get_data_for_table();
     * @since Totara 2.7.29, 2.9.21, 9.9, 10
     * @throws moodle_exception If the report query fails a moodle_exception with a friendly message is generated instead
     *      of a dml_read_exception.
     * @param string $sql
     * @param array $params
     * @param int $limitfrom
     * @param int $limitnum
     * @param bool $setfilteredcount If set to true after fetching the recordset the filtered count for this report will be set.
     *      By proxy if the fitlered count is set and there are no filters applied then the full count will also be set.
     * @return counted_recordset
     */
    private function get_counted_recordset_sql($sql, array $params = null, $limitfrom = 0, $limitnum = 0, $setfilteredcount = false) {
        global $CFG;

        try {

            $reportdb = $this->get_report_db();
            $recordset = $reportdb->get_counted_recordset_sql($sql, $params, $limitfrom, $limitnum);

        } catch (dml_read_exception $e) {

            // We are wrapping this exception to provide a more user friendly error message.
            if ($this->is_cached()) {
                $message = 'error:problemobtainingcachedreportdata';
            } else {
                $message = 'error:problemobtainingreportdata';
            }
            throw new moodle_exception($message, 'totara_reportbuilder', '', $e->getMessage(), $e->debuginfo);

        }

        if ($setfilteredcount) {
            $this->set_filtered_count($recordset->get_count_without_limits());
        }

        return $recordset;
    }

    /**
     * Returns a recordset containing data ready to be used in the given Totara Table.
     *
     * @since Totara 12.4
     * @param totara_table $table
     * @return moodle_recordset|counted_recordset
     */
    private function get_data_for_table(totara_table $table): moodle_recordset {
        $orderby = $this->get_report_sort($table);
        $limitfrom = $table->get_page_start();
        if ($limitfrom === '') {
            $limitfrom = 0;
        }

        $limitnum = $table->get_page_size();
        if ($limitnum === '') {
            $limitnum = 0;
        }
        return $this->get_data($orderby, (int)$limitfrom, (int)$limitnum);
    }

    /**
     * Gets data for this report.
     *
     * @since Totara 12.4
     * @param string $orderby
     * @param int $limitfrom
     * @param int $limitnum
     * @param int $method One of self::FETCHMETHOD_USE_*
     * @return moodle_recordset|counted_recordset
     */
    private function get_data(string $orderby = '', int $limitfrom = 0, int $limitnum = 0, int $method = null): moodle_recordset {
        list($sql, $params, $cache) = $this->build_query(false, true);
        $sql .= $orderby;

        if ($method === null) {
            $method = $this->get_fetch_method();
        }

        try {
            $reportdb = $this->get_report_db();
            if ($method === self::FETCHMETHOD_COUNTED_RECORDSET) {
                // This also sets the filtered count so that when we use it later it doesn't execute the query again!
                $recordset = $reportdb->get_counted_recordset_sql($sql, $params, $limitfrom, $limitnum, $count);
                $this->set_filtered_count($count);
            } else {
                $recordset = $reportdb->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
            }
        } catch (dml_exception $e) {
            // We are wrapping this exception to provide a more user friendly error message.
            if ($this->is_cached()) {
                $message = 'error:problemobtainingcachedreportdata';
            } else {
                $message = 'error:problemobtainingreportdata';
            }
            throw new moodle_exception($message, 'totara_reportbuilder', '', $e->getMessage(), $e->debuginfo);
        }

        return $recordset;
    }

    /**
     * If a redirect url has been specified in the source then output a redirect link.
     */
    public function display_redirect_link() {
        if (isset($this->src->redirecturl)) {
            if (isset($this->src->redirectmessage)) {
                $message = '&laquo; ' . $this->src->redirectmessage;
            } else {
                $message = '&laquo; ' . get_string('selectitem', 'totara_reportbuilder');
            }
            echo html_writer::link($this->src->redirecturl, $message);
        }
    }

    /**
     *
     */
    public function get_expand_content($expandname) {
        $func = 'rb_expand_' . $expandname;
        if (method_exists($this->src, $func)) {
            return $this->src->$func();
        }
    }

    /**
     * Indicates that report instance has grouping by one of used columns, filters, etc not including aggregation explicitly set by
     * the user
     *
     * @return bool
     */
    public function is_internally_grouped() {
        return $this->pregrouped;
    }

    /**
     * Determine if the report should be hidden due to the initialdisplay setting.
     */
    public function is_initially_hidden() {
        if (isset($this->_isinitiallyhidden)) {
            return $this->_isinitiallyhidden;
        }

        $searchedstandard = optional_param_array('submitgroupstandard', array(), PARAM_ALPHANUM);
        $searchedsidebar = optional_param_array('submitgroupsidebar', array(), PARAM_ALPHANUM);
        $toolbarsearch = optional_param('toolbarsearchbutton', false, PARAM_TEXT);
        $ssort = optional_param('ssort', false, PARAM_TEXT);
        // Totara hack to overcome limitations of the pagination library:
        // the pagination bar with 1st page link has a $spage=0 param which is returning 1st page of the report.
        // we have to know exactly if the pagination bar was used.
        $spage = optional_param('spage', '', PARAM_TEXT);
        // If $spage is empty, means we used report title to see the report,
        // if $spage is equal to 0, means we used 1st page link of the pagination bar.
        $spage = ($spage === '' ? false : true);
        $overrideinitial = isset($searchedstandard['addfilter']) || isset($searchedsidebar['addfilter']) ||
            $toolbarsearch || $ssort || $spage;

        $globalinitialdisplay = get_config('totara_reportbuilder', 'globalinitialdisplay');
        $initialdisplay = $this->initialdisplay == RB_INITIAL_DISPLAY_HIDE || ($globalinitialdisplay && !$this->embedded);
        $sizeoffilters = sizeof($this->filters) + sizeof($this->searchcolumns);
        $this->_isinitiallyhidden = ($initialdisplay && !$overrideinitial && !$this->is_report_filtered() && $sizeoffilters > 0);

        return $this->_isinitiallyhidden;
    }

    /**
     * Get column identifiers of columns that should be hidden on page load
     * The hidden columns are stored in the session
     *
     * @return array of column identifiers, usable by js selectors
     */
    function js_get_hidden_columns() {
        global $SESSION;
        $cols = array();

        $shortname = $this->shortname;
        // javascript to hide columns based on session variable
        if (isset($SESSION->rb_showhide_columns[$this->get_uniqueid('rb')])) {
            foreach ($this->columns as $column) {
                $ident = "{$column->type}_{$column->value}";
                if (isset($SESSION->rb_showhide_columns[$this->get_uniqueid('rb')][$ident])) {
                    if ($SESSION->rb_showhide_columns[$this->get_uniqueid('rb')][$ident] == 0) {
                        $cols[] = "#{$shortname} .{$ident}";
                    }
                }
            }
        }

        return $cols;
    }

    /**
     * Look up the sort keys and make sure they still exist in table
     * (could have been deleted in report builder)
     *
     * @return true May unset flexible table sort keys if they are not
     *              found in the column list
     */
    function check_sort_keys() {
        global $SESSION;
        $sortarray = isset($SESSION->flextable[$this->get_uniqueid('rb')]['sortby']) ?
                $SESSION->flextable[$this->get_uniqueid('rb')]['sortby'] : null;
        if (is_array($sortarray)) {
            foreach ($sortarray as $sortelement => $unused) {
                // see if sort element is in columns array
                $set = false;
                foreach ($this->columns as $col) {
                    if ($col->type . '_' . $col->value == $sortelement) {
                        $set = true;
                    }
                }
                // if it's not remove it from sort SESSION var
                if ($set === false) {
                    unset($SESSION->flextable[$this->get_uniqueid('rb')]['sortby'][$sortelement]);
                }
            }
        }
        return true;
    }

    /**
     * Returns a menu that when selected, takes the user to the specified saved search
     *
     * @return string HTML to display a pulldown menu with saved search options
     */
    function view_saved_menu() {
        global $USER, $OUTPUT;
        $id = $this->_id;
        $sid = $this->_sid;

        $common = new moodle_url($this->get_current_url());

        $savedoptions = $this->get_saved_searches($id, $USER->id);
        if (count($savedoptions) > 0) {
            $select = new single_select($common, 'sid', $savedoptions, $sid);
            $select->label = get_string('viewsavedsearch', 'totara_reportbuilder');
            $select->formid = 'viewsavedsearch';
            return $OUTPUT->render($select);
        } else {
            return '';
        }
    }

    /**
     * Returns an array of available saved seraches for this report and user
     * @param int $reportid look for saved searches for this report
     * @param int $userid Check for saved searches belonging to this user
     * @return array search id => search name
     */
    function get_saved_searches($reportid, $userid) {
        global $DB;
        $savedoptions = array();
        // Are there saved searches for this report and user?
        $saved = $DB->get_records('report_builder_saved', array('reportid' => $reportid, 'userid' => $userid));
        foreach ($saved as $item) {
            $savedoptions[$item->id] = format_string($item->name);
        }
        // Are there public saved searches for this report?
        $saved = $DB->get_records('report_builder_saved', array('reportid' => $reportid, 'ispublic' => 1));
        foreach ($saved as $item) {
            $savedoptions[$item->id] = format_string($item->name);
        }
        return $savedoptions;
    }

    /**
     * Diplays a table containing the save search button and pulldown
     * of existing saved searches (if any)
     *
     * @return string HTML to display the table
     */
    public function display_saved_search_options() {
        global $PAGE;

        if (!isloggedin() or isguestuser()) {
            // No saving for guests, sorry.
            return '';
        }

        $output = $PAGE->get_renderer('totara_reportbuilder');

        $savedbutton = $output->save_button($this);
        $savedmenu = $this->view_saved_menu();

        // no need to print anything
        if (strlen($savedmenu) == 0 && strlen($savedbutton) == 0) {
            return '';
        }

        $controls = html_writer::start_tag('div', array('id' => 'rb-search-controls'));

        if (strlen($savedbutton) != 0) {
            $controls .= $savedbutton;
        }
        if (strlen($savedmenu) != 0) {
            $managesearchbutton = $output->manage_search_button($this);
            $controls .= html_writer::tag('div', $savedmenu, array('id' => 'rb-search-menu'));
            $controls .=  html_writer::tag('div', $managesearchbutton, array('id' => 'manage-saved-search-button'));;
        }

        $controls .= html_writer::end_tag('div');
        return $controls;

    }

    /**
     * Returns HTML for a button that when clicked, takes the user to a page which
     * allows them to edit this report
     *
     * @return string HTML to display the button
     */
    function edit_button() {
        global $OUTPUT;
        $context = context_system::instance();
        $capability = $this->embedded ? 'totara/reportbuilder:manageembeddedreports' : 'totara/reportbuilder:managereports';
        if (has_capability($capability, $context)) {
            return $OUTPUT->single_button(new moodle_url('/totara/reportbuilder/general.php', array('id' => $this->_id)), get_string('editthisreport', 'totara_reportbuilder'), 'get');
        } else {
            return '';
        }
    }

    /* Download current table to Google Fusion
     * @param array $fields Array of column headings
     * @param string $query SQL query to run to get results
     * @param integer $count Number of filtered records in query
     * @param array $restrictions Array of strings containing info
     *                            about the content of the report
     * @return Returns never
     */
    function download_fusion() {
        $jump = new moodle_url('/totara/reportbuilder/fusionexporter.php', array('id' => $this->_id, 'sid' => $this->_sid));
        redirect($jump->out());
        die;
    }

    /**
     * Returns array of content options allowed for this report's source
     *
     * @return array An array of content option names
     */
    function get_content_options() {

        $contentoptions = array();
        if (isset($this->contentoptions) && is_array($this->contentoptions)) {
            foreach ($this->contentoptions as $option) {
                $contentoptions[] = $option->classname;
            }
        }
        return $contentoptions;
    }


    ///
    /// Functions for Editing Reports
    ///


    /**
     * Parses the filter options data for this source into a data structure
     * suitable for an HTML select pulldown.
     *
     * @return array An Array with $type-$value as key and $label as value
     */
    public function get_filters_select($onlyinstant = false) {
        $ret = array();
        if (!isset($this->filteroptions)) {
            return $ret;
        }

        $filters = $this->filteroptions;

        foreach ($filters as $filter) {
            if (!$onlyinstant || in_array($filter->filtertype, array('date', 'select', 'menuofchoices', 'multicheck'))) {
                $section = $this->get_type_heading($filter->type);
                $key = $filter->type . '-' . $filter->value;
                $ret[$section][$key] = format_string($filter->label);
            }
        }
        return $ret;
    }

    /**
     * Parses the search columns data for this source into a data structure
     * suitable for an HTML select pulldown.
     *
     * @return array An Array with $type-$value as key and $label as value
     */
    public function get_search_columns_select() {
        $ret = array();
        if (!isset($this->columnoptions)) {
            return $ret;
        }

        $columnoptions = $this->columnoptions;

        foreach ($columnoptions as $columnoption) {
            if ($columnoption->is_searchable()) {
                $section = $this->get_type_heading($columnoption->type);
                $key = $columnoption->type . '-' . $columnoption->value;
                $ret[$section][$key] = format_string($columnoption->name);
            }
        }
        return $ret;
    }

    public function get_all_filters_select() {
        // Standard filters.
        $allstandardfilters = array_merge(
                array(get_string('new') => array(0 => get_string('addanotherfilter', 'totara_reportbuilder'))),
                $this->get_filters_select());
        $unusedstandardfilters = $allstandardfilters;
        foreach ($allstandardfilters as $okey => $optgroup) {
            foreach ($optgroup as $typeval => $filtername) {
                $typevalarr = explode('-', $typeval);
                foreach ($this->filters as $curfilter) {
                    if (($curfilter->region == rb_filter_type::RB_FILTER_REGION_STANDARD ||
                         $curfilter->region == rb_filter_type::RB_FILTER_REGION_SIDEBAR) &&
                         $curfilter->type == $typevalarr[0] && $curfilter->value == $typevalarr[1]) {
                        unset($unusedstandardfilters[$okey][$typeval]);
                    }
                }
            }
        }

        // Sidebar filters.
        $allsidebarfilters = array_merge(
                array(get_string('new') => array(0 => get_string('addanotherfilter', 'totara_reportbuilder'))),
                $this->get_filters_select(true));
        $unusedsidebarfilters = $allsidebarfilters;
        foreach ($allsidebarfilters as $okey => $optgroup) {
            foreach ($optgroup as $typeval => $filtername) {
                $typevalarr = explode('-', $typeval);
                foreach ($this->filters as $curfilter) {
                    if (($curfilter->region == rb_filter_type::RB_FILTER_REGION_STANDARD ||
                         $curfilter->region == rb_filter_type::RB_FILTER_REGION_SIDEBAR) &&
                         $curfilter->type == $typevalarr[0] && $curfilter->value == $typevalarr[1]) {
                        unset($unusedsidebarfilters[$okey][$typeval]);
                    }
                }
            }
        }

        // Search columns.
        $allsearchcolumns = array_merge(
            array(get_string('new') => array(0 => get_string('addanothersearchcolumn', 'totara_reportbuilder'))),
            $this->get_search_columns_select());
        // Remove already-added search columns from the new search column selectors.
        $unusedsearchcolumns = $allsearchcolumns;
        foreach ($allsearchcolumns as $okey => $optgroup) {
            foreach ($optgroup as $typeval => $searchcolumnname) {
                $typevalarr = explode('-', $typeval);
                foreach ($this->searchcolumns as $cursearchcolumn) {
                    if ($cursearchcolumn->type == $typevalarr[0] && $cursearchcolumn->value == $typevalarr[1]) {
                        unset($unusedsearchcolumns[$okey][$typeval]);
                    }
                }
            }
        }

        return compact('allstandardfilters', 'unusedstandardfilters',
                       'allsidebarfilters', 'unusedsidebarfilters',
                       'allsearchcolumns', 'unusedsearchcolumns');
    }

    /**
     * Returns whether this report is using a disabled filter
     * @return bool
     */
    function has_disabled_filters() {
        $this->fetch_sql_filters(); //trigger the disabled filter check
        return $this->_hasdisabledfilter;
    }

    /**
     * Parses the column options data for this source into a data structure
     * suitable for an HTML select pulldown
     *
     * @return array An array with $type-$value as key and an object with
     *               a name and any additional properties as value
     */
    public function get_columns_select() {
        $columns = $this->columnoptions;
        $result = [];
        if (!isset($this->columnoptions)) {
            return $result;
        }

        $deprecated_section = get_string('type_deprecated', 'totara_reportbuilder');
        $deprecated = [];
        foreach ($columns as $column) {
            // don't include unselectable columns
            if (!$column->selectable) {
                continue;
            }

            $section = $this->get_type_heading($column->type);
            $key = $column->type . '-' . $column->value;
            if ($column->deprecated) {
                $deprecated[$key] = new stdClass();
                $deprecated[$key]->name = get_string('deprecated', 'totara_reportbuilder', format_string($column->name));
                $deprecated[$key]->attributes = ['deprecated' => true, 'issubquery' => $column->issubquery];
            } else {
                $result[$section][$key] = new stdClass();
                $result[$section][$key]->name = format_string($column->name);
                $result[$section][$key]->attributes = ['deprecated' => false, 'issubquery' => $column->issubquery];
            }
        }

        // Add deprecated column options into their own group at the end of all options.
        if (!empty($deprecated)) {
            $result[$deprecated_section] = $deprecated;
        }

        return $result;
    }

    /**
     * Given a column id, sets the default visibility to show or hide
     * for that column on current report
     *
     * @param integer $cid ID of the column to be changed
     * @param integer $hide 0 to show column, 1 to hide it
     * @return boolean True on success, false otherwise
     */
    function showhide_column($cid, $hide) {
        global $DB;

        $col = $DB->get_record('report_builder_columns', array('id' => $cid));
        if (!$col) {
            return false;
        }

        $todb = new stdClass();
        $todb->id = $cid;
        $todb->hidden = $hide;
        $DB->update_record('report_builder_columns', $todb);

        $this->columns = $this->get_columns();
        return true;
    }

    /**
     * Given a column id, removes that column from the current report
     *
     * @param integer $cid ID of the column to be removed
     * @return boolean True on success, false otherwise
     */
    function delete_column($cid) {
        global $DB;

        $id = $this->_id;
        $sortorder = $DB->get_field('report_builder_columns', 'sortorder', array('id' => $cid));
        if (!$sortorder) {
            return false;
        }
        $transaction = $DB->start_delegated_transaction();

        $graphseries = $DB->get_field('report_builder_graph', 'series', array('reportid' => $id));
        if ($graphseries) {
            $column = $DB->get_record('report_builder_columns', array('id' => $cid), 'type, value');
            $source = implode('-', array($column->type, $column->value));
            $datasources = json_decode($graphseries, true);
            if (in_array($source, $datasources)) {
                totara_set_notification(get_string('error:graphdeleteseries', 'totara_reportbuilder'));
                return false;
            }
        }

        $DB->delete_records('report_builder_columns', array('id' => $cid));
        $allcolumns = $DB->get_records('report_builder_columns', array('reportid' => $id));
        foreach ($allcolumns as $column) {
            if ($column->sortorder > $sortorder) {
                $todb = new stdClass();
                $todb->id = $column->id;
                $todb->sortorder = $column->sortorder - 1;
                $DB->update_record('report_builder_columns', $todb);
            }
        }
        $transaction->allow_commit();

        $this->columns = $this->get_columns();
        return true;
    }

    /**
     * Given a filter id, removes that filter from the current report and
     * updates the sortorder for other filters
     *
     * @param integer $fid ID of the filter to be removed
     * @return boolean True on success, false otherwise
     */
    function delete_filter($fid) {
        global $DB;

        $id = $this->_id;

        $sortorder = $DB->get_field('report_builder_filters', 'sortorder', array('id' => $fid));
        if (!$sortorder) {
            return false;
        }

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('report_builder_filters', array('id' => $fid));
        $allfilters = $DB->get_records('report_builder_filters', array('reportid' => $id));
        foreach ($allfilters as $filter) {
            if ($filter->sortorder > $sortorder) {
                $todb = new stdClass();
                $todb->id = $filter->id;
                $todb->sortorder = $filter->sortorder - 1;
                $DB->update_record('report_builder_filters', $todb);
            }
        }

        $transaction->allow_commit();

        $this->filters = $this->get_filters();
        return true;
    }

    /**
     * Given a search column id, removes that search column from the current report
     *
     * @param integer $searchcolumnid ID of the search column to be removed
     * @return boolean True on success, false otherwise
     */
    public function delete_search_column($searchcolumnid) {
        global $DB;

        $DB->delete_records('report_builder_search_cols', array('id' => $searchcolumnid));

        $this->searchcolumns = $this->get_search_columns();
        return true;
    }

    /**
     * Given a column id and a direction, moves a column up or down
     *
     * @param integer $cid ID of the column to be moved
     * @param string $updown String 'up' or 'down'
     * @return boolean True on success, false otherwise
     */
    function move_column($cid, $updown) {
        global $DB;

        $id = $this->_id;

        // assumes sort order is well behaved (no gaps)
        if (!$itemsort = $DB->get_field('report_builder_columns', 'sortorder', array('id' => $cid))) {
            return false;
        }
        if ($updown == 'up') {
            $newsort = $itemsort - 1;
        } else if ($updown == 'down') {
            $newsort = $itemsort + 1;
        } else {
            // invalid updown string
            return false;
        }
        if ($neighbour = $DB->get_record('report_builder_columns', array('reportid' => $id, 'sortorder' => $newsort))) {
            $transaction = $DB->start_delegated_transaction();
            // swap sort orders
            $todb = new stdClass();
            $todb->id = $cid;
            $todb->sortorder = $neighbour->sortorder;
            $todb2 = new stdClass();
            $todb2->id = $neighbour->id;
            $todb2->sortorder = $itemsort;
            $DB->update_record('report_builder_columns', $todb);
            $DB->update_record('report_builder_columns', $todb2);
            $transaction->allow_commit();
        } else {
            // no neighbour
            return false;
        }
        $this->columns = $this->get_columns();
        return true;
    }


    /**
     * Given a filter id and a direction, moves a filter up or down
     *
     * @param integer $fid ID of the filter to be moved
     * @param string $updown String 'up' or 'down'
     * @return boolean True on success, false otherwise
     */
    function move_filter($fid, $updown) {
        global $DB;

        $id = $this->_id;

        // assumes sort order is well behaved (no gaps)
        if (!$itemsort = $DB->get_field('report_builder_filters', 'sortorder', array('id' => $fid))) {
            return false;
        }
        if ($updown == 'up') {
            $newsort = $itemsort - 1;
        } else if ($updown == 'down') {
            $newsort = $itemsort + 1;
        } else {
            // invalid updown string
            return false;
        }
        if ($neighbour = $DB->get_record('report_builder_filters', array('reportid' => $id, 'sortorder' => $newsort))) {
            $transaction = $DB->start_delegated_transaction();
            // swap sort orders
            $todb = new stdClass();
            $todb->id = $fid;
            $todb->sortorder = $neighbour->sortorder;
            $todb2 = new stdClass();
            $todb2->id = $neighbour->id;
            $todb2->sortorder = $itemsort;
            $DB->update_record('report_builder_filters', $todb);
            $DB->update_record('report_builder_filters', $todb2);
            $transaction->allow_commit();
        } else {
            // no neighbour
            return false;
        }
        $this->filters = $this->get_filters();
        return true;
    }

    /**
     * Method for obtaining a report builder setting
     *
     * @param integer $reportid ID for the report to obtain a setting for
     * @param string $type Identifies the class using the setting
     * @param string $name Identifies the particular setting
     * @return mixed The value of the setting $name or null if it doesn't exist
     */
    public static function get_setting($reportid, $type, $name) {
        global $DB;
        return $DB->get_field('report_builder_settings', 'value', array('reportid' => $reportid, 'type' => $type, 'name' => $name));
    }

    /**
     * Return an associative array of all settings of a particular type
     *
     * @param integer $reportid ID of the report to get settings for
     * @param string $type Identifies the class to get settings from
     * @return array Associative array of name|value settings
     */
    static function get_all_settings($reportid, $type) {
        global $DB;

        $settings = array();
        $records = $DB->get_records('report_builder_settings', array('reportid' => $reportid, 'type' => $type));
        foreach ($records as $record) {
            $settings[$record->name] = $record->value;
        }
        return $settings;
    }

    /**
     * Method for updating a setting for a particular report
     *
     * Will create a DB record if no setting is found
     *
     * @param integer $reportid ID of the report to update the settings of
     * @param string $type Identifies the class to be updated
     * @param string $name Identifies the particular setting to update
     * @param string $value The new value of the setting
     * @return boolean True if the setting could be updated or created
     */
    static function update_setting($reportid, $type, $name, $value) {
        global $DB;

        if ($record = $DB->get_record('report_builder_settings', array('reportid' => $reportid, 'type' => $type, 'name' => $name))) {
            // update record
            $todb = new stdClass();
            $todb->id = $record->id;
            $todb->value = $value;
            $DB->update_record('report_builder_settings', $todb);
        } else {
            // insert record
            $todb = new stdClass();
            $todb->reportid = $reportid;
            $todb->type = $type;
            $todb->name = $name;
            $todb->value = $value;
            $DB->insert_record('report_builder_settings', $todb);
        }
        $DB->set_field('report_builder', 'timemodified', time(), array('id' => $reportid));
        return true;
    }

    /**
     * Determines if this report currently has any active filters or not
     *
     * This is done by fetching the filtering SQL to see if it is set yet
     *
     * @return boolean True if one or more filters are currently active
     */
    function is_report_filtered() {
        $filters = $this->fetch_sql_filters();
        if (isset($filters[0]['where']) && $filters[0]['where'] != '') {
            return true;
        }
        if (isset($filters[0]['having']) && $filters[0]['having'] != '') {
            return true;
        }
        return false;
    }

    /**
     * Setter for post_config_restrictions property
     *
     * This is an array of the form:
     *
     * $restrictions = array(
     *     "sql_where_snippet",
     *     array('paramkey' => 'paramvalue')
     * );
     *
     * i.e. it provides both a string of SQL and any parameters used by that string.
     *
     * @param array Restrictions to be added to the query WHERE clause.
     */
    public function set_post_config_restrictions($restrictions) {
        $this->_post_config_restrictions = $restrictions;
    }

    /**
     * Getter for post_config_restrictions.
     */
    public function get_post_config_restrictions() {
        if (empty($this->_post_config_restrictions)) {
            return array('', array());
        }
        return $this->_post_config_restrictions;
    }

    /**
     * Calculate the sql and params for visibility of courses, programs or certifications.
     *
     * This function performs checks to ensure that the required columns have been defined. If they haven't,
     * you'll get an exception when the report is instantiated.
     *
     * @param string $type course, program or certification.
     * @param string $table Table alias matching $type
     * @param int $userid The user that the results should be restricted for. Defaults to current user.
     * @param bool $showhidden If using normal visibility, show items even if they are hidden.
     * @param string $fieldid Field name of the id in $table
     * @param string $fieldvisible
     * @param string $fieldaudiencevisible
     * @return array
     */
    public function post_config_visibility_where($type, $table, $userid = null, $showhidden = false, $fieldid = 'id',
                                                 $fieldvisible = 'visible', $fieldaudiencevisible = 'audiencevisible') {
        // Check that the required columns are all defined.
        if (empty($this->requiredcolumns['visibility-' . $fieldid]) ||
            $this->requiredcolumns['visibility-' . $fieldid]->field != $table . '.' . $fieldid) {
            throw new moodle_exception('Report is missing required column visibility id or field is incorrect');
        }

        if (empty($this->requiredcolumns['visibility-' . $fieldvisible]) ||
            $this->requiredcolumns['visibility-' . $fieldvisible]->field != $table . '.' . $fieldvisible) {
            throw new moodle_exception('Report is missing required column visibility visible or field is incorrect');
        }

        if (empty($this->requiredcolumns['visibility-' . $fieldaudiencevisible]) ||
            $this->requiredcolumns['visibility-' . $fieldaudiencevisible]->field != $table . '.' . $fieldaudiencevisible) {
            throw new moodle_exception('Report is missing required column visibility audiencevisible or field is incorrect');
        }

        if (empty($this->requiredcolumns['ctx-id']) ||
            $this->requiredcolumns['ctx-id']->field != 'ctx.id') {
            throw new moodle_exception('Report is missing required column ctx id or field is incorrect');
        }

        if ($type == 'program' || $type == 'certification') {
            if (empty($this->requiredcolumns[$table . '-available']) ||
                $this->requiredcolumns[$table . '-available']->field != $table . '.available') {
                throw new moodle_exception("Report is missing required column {$table} available or field is incorrect");
            }

            if (empty($this->requiredcolumns[$table . '-availablefrom']) ||
                $this->requiredcolumns[$table . '-availablefrom']->field != $table . '.availablefrom') {
                throw new moodle_exception("Report is missing required column {$table} availablefrom or field is incorrect");
            }

            if (empty($this->requiredcolumns[$table . '-availableuntil']) ||
                $this->requiredcolumns[$table . '-availableuntil']->field != $table . '.availableuntil') {
                throw new moodle_exception("Report is missing required column {$table} availableuntil or field is incorrect");
            }
        }

        // Calculate the correct field names including table alias.
        $id = $this->get_field('visibility', $fieldid, $table . '.' . $fieldid);
        $vis = $this->get_field('visibility', $fieldvisible, $table . '.' . $fieldvisible);
        $audvis = $this->get_field('visibility', $fieldaudiencevisible, $table . '.' . $fieldaudiencevisible);

        // Get the sql and params and return them.
        return totara_visibility_where($userid, $id, $vis, $audvis, $table, $type, $this->is_cached(), $showhidden);
    }

    /**
     * Private method to preserve backwards compatibility of is_ignored() in rb_base_source classes.
     * Calls to this method should be replaced with direct calls to is_source_ignored() once
     * deprecated is_ignored() is completely removed from the code base.
     *
     * @deprecated since Totara 12.3
     *
     * @param string $source
     *
     * @return bool Whether current report source should be ignored.
     */
    private static function is_source_class_ignored(string $source) {
        $classname = 'rb_source_' . $source;
        if (!class_exists($classname)) {
            return true; // Ignore non-existing report source classes.
        }

        $reflection = new ReflectionClass($classname);
        $oldmethod = $reflection->getMethod('is_ignored');
        if ($oldmethod->class != 'rb_base_source') {
            // Intentionally leaving no debugging notices in stable branches.
            $src = self::get_source_object($source);
            return $src->is_ignored();
        }
        return $classname::is_source_ignored();
    }

    /**
     * Private method to preserve backwards compatibility of is_ignored() in rb_base_embedded classes.
     * Calls to this method should be replaced with direct calls to is_report_ignored() once
     * deprecated is_ignored() is completely removed from the code base.
     *
     * @deprecated since Totara 12.3
     *
     * @param string $classname
     *
     * @return bool Whether current embedded report should be ignored.
     */
    private static function is_embedded_class_ignored(string $classname) {
        $reflection = new ReflectionClass($classname);
        $oldmethod = $reflection->getMethod('is_ignored');
        if ($oldmethod->class != 'rb_base_embedded') {
            // Intentionally leaving no debugging notices in stable branches.
            $embed = new $classname([]); // Don't need initialise it with any data for is_ignored() check.
            return $embed->is_ignored();
        }
        return $classname::is_report_ignored();
    }

} // End of reportbuilder class

class ReportBuilderException extends \Exception { }

/**
 * Returns the proper SQL to create table based on a query
 * @param string $table
 * @param string $select SQL select statement
 * @param array $params SQL params
 * @return bool success
 */
function sql_table_from_select($table, $select, array $params) {
    global $DB;
    $tablename = trim($table, '{}');
    $table = '{' . $tablename . '}'; // Make sure this is valid table with correct prefix.
    $hashtablename = substr(md5($table), 0, 15);
    switch ($DB->get_dbfamily()) {
        case 'mysql':
            $columnssql = "SHOW COLUMNS FROM \"{$table}\"";
            $indexsql = "CREATE INDEX rb_cache_{$hashtablename}_%1\$s ON {$table} (%2\$s)";
            $indexlongsql = "CREATE INDEX rb_cache_{$hashtablename}_%1\$s ON {$table} (%2\$s(%3\$d))";
            $fieldname = 'field';

            // Find out if want some special db engine.
            $enginesql = $DB->get_dbengine() ? " ENGINE = " . $DB->get_dbengine() : '';

            // Do we know collation?
            $collation = $DB->get_dbcollation();
            $charset = $DB->get_charset();
            $collationsql = "DEFAULT CHARACTER SET {$charset} DEFAULT COLLATE = {$collation}";
            $rowformat = "ROW_FORMAT = Compressed";

            $sql = "CREATE TABLE \"{$table}\" $enginesql $collationsql $rowformat $select";
            $trans = $DB->start_delegated_transaction();
            $result = $DB->execute($sql, $params);
            $trans->allow_commit();
            break;
        case 'mssql':
            $viewname = 'tmp_'.$hashtablename;
            $viewsql = "CREATE VIEW $viewname AS $select";
            $DB->execute($viewsql, $params);

            $sql = "SELECT * INTO {$table} FROM $viewname";
            $result = $DB->execute($sql);

            $removeviewsql = "DROP VIEW $viewname";
            $DB->execute($removeviewsql);

            $columnssql = "SELECT sc.name, sc.system_type_id, sc.max_length, st.name as field_type FROM sys.columns sc
                    LEFT JOIN sys.types st ON (st.system_type_id = sc.system_type_id
                        AND st.name <> 'sysname' AND st.name <> 'geometry' AND st.name <> 'hierarchyid')
                    WHERE sc.object_id = OBJECT_ID('{$table}')";
            $indexsql = "CREATE INDEX rb_cache_{$hashtablename}_%1\$s ON {$table} (%2\$s)";
            $fieldname = 'name';
            break;
        case 'postgres':
        default:
            $sql = "CREATE TABLE \"{$table}\" AS $select";
            $columnssql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name ='{$table}'";
            $indexsql = "CREATE INDEX rb_cache_{$hashtablename}_%1\$s ON {$table} (%2\$s)";
            $fieldname = 'column_name';
            $result = $DB->execute($sql, $params);
            break;
    }
    $DB->reset_caches(array($tablename));

    if (!$result) {
        return false;
    }

    // Create indexes
    $indexcount = 0;
    $resetcaches = false;
    $fields = $DB->get_records_sql($columnssql);
    foreach ($fields as $field) {
        $hashfieldname = substr(md5($field->$fieldname), 0, 15);
        $sql = sprintf($indexsql, $hashfieldname, $field->$fieldname);

        // db engines specifics
        switch ($DB->get_dbfamily()) {
            // NOTE: Continue inside switch needs to use "2" because switch behaves like a looping structure.
            case 'mysql':
                if ($indexcount > 62) {
                    // MySQL has limit on the number of indexes per table.
                    break 2;
                }
                // Do not index fields with size 0
                if (strpos($field->type, '(0)') !== false) {
                    continue 2;
                }
                if (preg_match('/varchar\(([0-9]*)\)/', $field->type, $matches)) {
                    if ($matches[1] > 255); {
                        // Bad luck, we cannot create indexes on large mysql varchar fields.
                        continue 2;
                    }
                }
                if (strpos($field->type, 'blob') !== false || strpos($field->type, 'text') !== false) {
                    // Index only first 255 symbols (mysql maximum = 767)
                    $sql = sprintf($indexlongsql, $hashfieldname, $field->$fieldname, 255);
                }
            break;
            case 'mssql':
                if ($field->field_type == 'image' || $field->field_type == 'binary') { // image
                    continue 2;
                }
                if ($field->field_type == 'text' || $field->field_type == 'ntext'
                        || ($field->field_type == 'nvarchar' && ($field->max_length == -1 || $field->max_length > 450))) {
                    $altersql = "ALTER TABLE {$table} ALTER COLUMN {$field->name} NVARCHAR(450)"; //Maximum index size = 900 bytes or 450 unicode chars
                    try {
                        // Attempt to convert field to indexable
                        $resetcaches = true;
                        $DB->execute($altersql);
                    } catch (dml_write_exception $e) {
                        // Recoverable exception
                        // Field has data longer than maximum index, proceed unindexed
                        continue 2;
                    }
                }
            break;
            case 'postgres':
                if ($field->data_type == 'unknown') {
                    $altersql = "ALTER TABLE {$table} ALTER COLUMN {$field->column_name} type varchar(255)";
                    $resetcaches = true;
                    $DB->execute($altersql);
                }
                if ($field->data_type == 'text') {
                    // Not creating indexes on text fields
                    continue 2;
                }
            break;
        }
        $indexcount++;
        $resetcaches = true;
        $DB->execute($sql);
    }
    if ($resetcaches) {
        $DB->reset_caches(array($tablename));
    }

    return true;
}

/**
 * Schedule reporting cache
 *
 * @global object $DB
 * @param int $reportid report id
 * @param array|stdClass $form data from form element
 * @return type
 */
function reportbuilder_schedule_cache($reportid, $form = array()) {
    global $DB;
    if (is_object($form)) {
        $form = (array)$form;
    }
    $cache = $DB->get_record('report_builder_cache', array('reportid' => $reportid), '*', IGNORE_MISSING);
    if (!$cache) {
        $cache = new stdClass();
    }
    $cache->reportid = $reportid;
    $schedule = new scheduler($cache, array('nextevent' => 'nextreport'));
    $schedule->from_array($form);

    if (!isset($cache->id)) {
        $result = $DB->insert_record('report_builder_cache', $cache);
    } else {
        $result = $DB->update_record('report_builder_cache', $cache);
    }
    return $result;
}

/**
 * Shift next scheduled execution if report was generated after scheduled time
 *
 * @param int $reportid Report id
 * @return boolean is operation success
 */
function reportbuilder_fix_schedule($reportid) {
    global $DB;

    $cache = $DB->get_record('report_builder_cache', array('reportid' => $reportid), '*', IGNORE_MISSING);
    if (!$cache) {
        return false;
    }

    $schedule = new scheduler($cache, array('nextevent' => 'nextreport'));
    if ($schedule->get_scheduled_time() < $cache->lastreport) {
        $schedule->next(time(), true, core_date::get_server_timezone());
    }

    if ($schedule->is_changed()) {
        $DB->update_record('report_builder_cache', $cache);
    }
    return true;
}

/**
 * Purge cache to force report update during next load
 * Caution: this function is used in db/upgrade.php, so on any scheme old behaviour for that upgrade must be
 * maintained.
 *
 * @param int|object $cache either data from rb cache table or report id
 * @param bool $unschedule If true drops scheduling as well
 */
function reportbuilder_purge_cache($cache, $unschedule = false) {
    global $DB;
    if (is_number($cache)) {
        $cache = $DB->get_record('report_builder_cache', array('reportid' => $cache));
    }
    if (!is_object($cache) || !isset($cache->reportid)) {
        return false;
    }
    if ($cache->cachetable) {
        sql_drop_table_if_exists($cache->cachetable);
    }
    if ($unschedule) {
        $DB->delete_records('report_builder_cache', array('reportid' => $cache->reportid));
        $DB->set_field('report_builder', 'cache', 0, array('id' => $cache->reportid));
    } else {
        $cache->cachetable = null;
        $cache->queryhash = null;
        $DB->update_record('report_builder_cache', $cache);
    }
}

/**
 * Purge all caches for report builder
 *
 * @param bool $unschedule Turn off caching after purge for all reports
 */
function reportbuilder_purge_all_cache($unschedule = false) {
    global $DB;
    try {
        $caches = $DB->get_records('report_builder_cache');
        foreach ($caches as $cache) {
            reportbuilder_purge_cache($cache, $unschedule);
        }
    } catch (dml_exception $e) {
        // This error is possible during installation process
        return;
    }
}


/**
 * Set flag to report that it is changed and cache settings are out of date or fail
 *
 * @param mixed stdClass|int $report Report id or report_builder_cache record object
 * @param int $flag Change flag - just changed or fail
 * @return bool result
 */
function reportbuilder_set_status($reportcache, $flag = RB_CACHE_FLAG_CHANGED) {
    global $DB;
    $reportid = 0;
    if (is_object($reportcache)) {
        $reportid = $reportcache->reportid;
        $reportcache->changed = $flag;
    } else if (is_numeric($reportcache)) {
        $reportid = $reportcache;
    }
    if (!$reportid) return false;

    $sql = 'UPDATE {report_builder_cache} SET changed = ? WHERE reportid = ?';
    $result = $DB->execute($sql, array($flag, $reportid));
    return $result;
}

/**
 * Report cache (re-)generation.
 *
 * NOTE: calling code must make sure current user is allowed to regenerate the cache.
 *
 * @int $reportid Report id
 * @return bool Is cache generated
 */
function reportbuilder_generate_cache($reportid) {
    global $DB;

    $success = false;
    $oldtable = '';

    $rawreport = $DB->get_record('report_builder', array('id' => $reportid), '*', MUST_EXIST);

    // Prepare record for cache
    $rbcache = $DB->get_record('report_builder_cache', array('reportid' => $reportid), '*', IGNORE_MISSING);
    if (!$rbcache) {
        $cache = new stdClass();
        $cache->reportid = $reportid;
        $cache->frequency = 0;
        $cache->schedule = 0;
        $cache->changed = 0;
        $cache->cachetable = null;
        $cache->genstart = 0;
        $cache->queryhash = null;
        $cache->id = $DB->insert_record('report_builder_cache', $cache);
        $rbcache = $DB->get_record('report_builder_cache', array('reportid' => $reportid), '*', MUST_EXIST);
    } else {
        $oldtable = $rbcache->cachetable;
    }
    $DB->set_field('report_builder_cache', 'genstart', time(), ['id' => $rbcache->id]);

    try {
        // Instantiate.
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($reportid, $config, false); // No permission check here, it is the responsibility of calling code.

        // Get caching query.
        list($query, $params) = $report->build_create_cache_query();
        $queryhash = sha1($query.serialize($params));

        $uniqid = uniqid();
        $newtable = "{report_builder_cache_{$reportid}_{$uniqid}}";
        $result = sql_table_from_select($newtable, $query, $params);

        if ($result) {
            $rbcache->cachetable = $newtable;
            $rbcache->lastreport = time();
            $rbcache->queryhash = $queryhash;
            $rbcache->changed = 0;
            $rbcache->genstart = 0;
            $DB->update_record('report_builder_cache', $rbcache);
            $success = true;
            if ($oldtable) {
                sql_drop_table_if_exists($oldtable);
            }
        }
    } catch (dml_exception $e) {
        debugging('Problem creating cache table '.$e->getMessage());
    }

    if (!$success) {
        // Clean up.
        sql_drop_table_if_exists($rbcache->cachetable);

        $rbcache->cachetable = null;
        $rbcache->genstart = 0;
        $rbcache->changed = RB_CACHE_FLAG_FAIL;
        $DB->update_record('report_builder_cache', $rbcache);
    }

    return $success;
}

/**
 *  Process Scheduled report and email or store it.
 *
 *  NOTE: diagnostic output is sent to cron console.
 *
 *  @param stdClass $sched Object containing data from schedule table
 *
 *  @return boolean True if email was successfully sent or file stored in filesystem, false on error or any problem
 */
function reportbuilder_send_scheduled_report($sched) {
    global $CFG, $DB, $USER;
    require_once($CFG->dirroot . '/totara/reportbuilder/email_setting_schedule.php');

    // Do not modify the parameter, the schedule class did its own magic in there already!
    $sched = clone($sched);

    if (!CLI_SCRIPT) {
        throw new coding_exception('reportbuilder_send_scheduled_report() can be used from cron task and tests only!');
    }

    if ($sched->userid != $USER->id) {
        throw new coding_exception('reportbuilder_send_scheduled_report() requires $USER->id to be the same as sched->userid!');
    }
    $user = $USER;

    if ($user->deleted or $user->suspended) {
        throw new coding_exception('reportbuilder_send_scheduled_report() requires active user!');
    }

    if (!$reportrecord = $DB->get_record('report_builder', array('id' => $sched->reportid))) {
        mtrace("Error: Scheduled report {$sched->id} references non-existent report {$sched->reportid}");
        return false;
    }

    // Make sure the source exists and is not ignored.
    $alluserreports = reportbuilder::get_user_generated_reports();
    if (!isset($alluserreports[$sched->reportid])) {
        mtrace("Error: Scheduled report {$sched->id} references invalid report {$sched->reportid}");
        return false;
    }

    // Don't send/store the report if the user doesn't have permission to view it.
    if (!reportbuilder::is_capable($sched->reportid, $sched->userid)) {
        mtrace("Error: Scheduled report {$sched->id} references report {$sched->reportid} that cannot be accessed by user {$sched->userid}");
        return false;
    }

    $saved = null;
    if ($sched->savedsearchid) {
        // Note: we must replicate the access control here to prevent fatal errors later when calling reportbuilder constructor.
        if (!$saved = $DB->get_record('report_builder_saved', array('id' => $sched->savedsearchid))) {
            mtrace("Error: Scheduled report {$sched->id} uses invalid saved search {$sched->savedsearchid}");
            return false;
        }
        if ($saved->ispublic == 0 and $sched->userid != $saved->userid) {
            mtrace("Error: Scheduled report {$sched->id} uses non-public saved search {$sched->savedsearchid} of other user");
            return false;
        }
    }

    $format = \totara_core\tabexport_writer::normalise_format($sched->format);
    $options = reportbuilder_get_export_options(null, false);
    $formats = \totara_core\tabexport_writer::get_export_classes();
    if (!isset($formats[$format]) or !isset($options[$format])) {
        mtrace("Error: Scheduled report {$sched->id} uses unknown or disabled format '{$sched->format}'");
        return false;
    }
    $writerclassname = $formats[$format];

    if ($sched->exporttofilesystem == REPORT_BUILDER_EXPORT_SAVE or
        $sched->exporttofilesystem == REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE) {

        // Make sure we can actually export the data.
        $exportsetting = get_config('reportbuilder', 'exporttofilesystem');
        if (!$exportsetting) {
            if ($sched->exporttofilesystem == REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE) {
                $sched->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
            } else {
                mtrace("Error: Scheduled report {$sched->id} is set to export to filesystem only");
                return false;
            }
        }
    }

    try {
        $report = reportbuilder_get_schduled_report($sched, $reportrecord);
    } catch (moodle_exception $e) {
        if ($e->errorcode === "nopermission") {
            mtrace("Error: Scheduled report {$sched->id} could not be created because user is not allowed to access it");
            return false;
        } else {
            mtrace("Error: Scheduled report {$sched->id} could not be created, unknown exception: " . get_class($e));
            return false;
        }
    }
    $tempfile = reportbuilder_export_schduled_report($sched, $report, $writerclassname);
    if (!$tempfile) {
        mtrace("Error: Scheduled report {$sched->id} could not be created");
        return false;
    }

    if ($sched->exporttofilesystem == REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE or $sched->exporttofilesystem == REPORT_BUILDER_EXPORT_SAVE) {
        $reportfilepathname = reportbuilder_get_export_filename($report, $sched->userid, $sched->id) . '.' . $writerclassname::get_file_extension();
        // Do not crete the file again, just copy it.
        $exported = copy($tempfile, $reportfilepathname);
        @chmod($reportfilepathname, (fileperms(dirname($reportfilepathname)) & 0666));
        if ($exported) {
            mtrace("Scheduled report {$sched->id} was saved in file system");
        } else {
            mtrace("Scheduled report {$sched->id} could not be saved in file system");
        }
    }

    $attachmentfilename = 'report.' . $writerclassname::get_file_extension();

    $reporturl = reportbuilder_get_report_url($reportrecord);
    if ($sched->savedsearchid != 0) {
        $reporturl .= '&sid=' . $sched->savedsearchid;
    }
    $messagedetails = new stdClass();
    $messagedetails->reportname = format_string($reportrecord->fullname);
    $messagedetails->exporttype = $writerclassname::get_export_option_name();
    $messagedetails->reporturl = $reporturl;
    $messagedetails->scheduledreportsindex = $CFG->wwwroot . '/my/reports.php#scheduled';
    $messagedetails->sender = \fullname($user);

    $schedule = new scheduler($sched, array('nextevent' => 'nextreport'));
    $messagedetails->schedule = $schedule->get_formatted($user);

    $subject = format_string($reportrecord->fullname) . ' ' . get_string('report', 'totara_reportbuilder');

    if ($sched->savedsearchid != 0) {
        $messagedetails->savedtext = get_string('savedsearchmessage', 'totara_reportbuilder', $saved->name);
    } else {
        $messagedetails->savedtext = '';
    }

    $message = get_string('scheduledreportmessage', 'totara_reportbuilder', $messagedetails);
    // Markdown format is compatible with plain text lang packs and allows limited html markup too.
    $messagehtml = markdown_to_html($message);
    $messageplain = html_to_text($messagehtml);

    $fromaddress = core_user::get_noreply_user();
    $emailedcount = 0;
    $failedcount = 0;

    if ($sched->exporttofilesystem == REPORT_BUILDER_EXPORT_EMAIL
        or $sched->exporttofilesystem == REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE) {

        // Get all emails set in the schedule report.
        $scheduleemail = new email_setting_schedule($sched->id);
        $systemusers   = $scheduleemail->get_all_system_users_to_email();
        $externalusers = email_setting_schedule::get_external_users_to_email($sched->id);

        // Sending email to all system users.
        foreach ($systemusers as $userto) {
            $result = email_to_user($userto, $fromaddress, $subject, $messageplain, $messagehtml, $tempfile, $attachmentfilename);
            if ($result) {
                $emailedcount++;
            } else {
                $failedcount++;
            }
        }

        // Sending email to external users.
        foreach ($externalusers as $email) {
            $userto = \totara_core\totara_user::get_external_user($email);
            $result = email_to_user($userto, $fromaddress, $subject, $messageplain, $messagehtml, $tempfile, $attachmentfilename);
            if ($result) {
                $emailedcount++;
            } else {
                $failedcount++;
            }
        }

        if ($emailedcount) {
            mtrace("Scheduled report {$sched->id} was emailed to {$emailedcount} users");
        } else {
            if (!$failedcount) {
                mtrace("Scheduled report {$sched->id} was not emailed to any users");
            }
        }
        if ($failedcount) {
            mtrace("Error: Scheduled report {$sched->id} was not emailed to $failedcount users");
        }
    }

    @unlink($tempfile);

    return (!$failedcount);
}

/**
 * Create instance of reportbuilder object.
 *
 * @param stdClass $sched
 * @param stdClass $reportrecord
 * @return reportbuilder
 */
function reportbuilder_get_schduled_report(stdClass $sched, stdClass $reportrecord) {
    if ($sched->reportid != $reportrecord->id) {
        throw new coding_exception('Invalid parameters');
    }
    $allrestr = rb_global_restriction_set::create_from_ids(
        $reportrecord, rb_global_restriction_set::get_user_all_restrictions_ids($sched->userid, true)
    );

    $config = new rb_config();
    $config->set_sid($sched->savedsearchid)
        ->set_reportfor($sched->userid)
        ->set_embeddata(['userid' => $sched->userid])
        ->set_global_restriction_set($allrestr);
    return reportbuilder::create($sched->reportid, $config);
}

/**
 * Creates an export of a report in specified format (xls, csv or ods).
 *
 * @param stdClass $sched schedule record
 * @param reportbuilder $report
 * @param string $writerclass class extending \totara_core\tabexport_writer
 *
 * @return string temporary file path
 */
function reportbuilder_export_schduled_report(stdClass $sched, reportbuilder $report, $writerclass) {
    global $USER;

    if ($sched->userid != $USER->id) {
        throw new coding_exception('$USER->id must matchs $sched->userid');
    }

    $source = new \totara_reportbuilder\tabexport_source($report);

    /** @var \totara_core\tabexport_writer $writer */
    $writer = new $writerclass($source);

    // TODO: TL-6751 move this to a request dir, but first make sure the email attachments support it
    do {
        $tempfilepathname = make_temp_directory('reportbuilderexport') . '/'
            . md5(uniqid($USER->id, true)) . '.' . $writer::get_file_extension();
    } while (file_exists($tempfilepathname));

    $writer->save_file($tempfilepathname);

    return $tempfilepathname;
}

/**
 * Get all scheduled reports that don't have any recipients
 * @return array of report ids and
 */
function reportbuilder_get_all_scheduled_reports_without_recipients() {
    global $DB;

    $sql = '
      SELECT
        rbs.id, rbs.userid,
        count(rbsea.id) AS audience_cnt,
        count(rbsee.id) AS external_cnt,
        count(rbses.id) AS systemuser_cnt
      FROM {report_builder_schedule} rbs
      LEFT JOIN {report_builder_schedule_email_audience} rbsea ON (rbsea.scheduleid = rbs.id)
      LEFT JOIN {report_builder_schedule_email_external} rbsee ON (rbsee.scheduleid = rbs.id)
      LEFT JOIN {report_builder_schedule_email_systemuser} rbses ON (rbses.scheduleid = rbs.id)
      GROUP BY rbs.id, rbs.userid
      HAVING count(rbsea.id) = 0 AND count(rbsee.id) = 0 AND count(rbses.id) = 0
    ';

    return $DB->get_records_sql($sql, array());
}

/**
 * Checks if username directory under given path exists
 * If it does not it creates it and returns fullpath with filename
 * userdir + report fullname + time created + schedule id
 * without the actual file extension.
 *
 * @param stdClass|reportbuilder $report
 * @param int $userid
 * @param int $scheduleid
 *
 * @return string full path name to export file (without extension)
 */
function reportbuilder_get_export_filename($report, $userid, $scheduleid) {
    global $DB;
    $reportfilename = format_string($report->fullname) . '_' .
            userdate(time(), get_string('datepickerlongyearphpuserdate', 'totara_core')) . '_' . $scheduleid;
    $reportfilename = clean_param($reportfilename, PARAM_FILE);
    $username = $DB->get_field('user', 'username', array('id' => $userid));

    // Validate directory.
    $path = get_config('reportbuilder', 'exporttofilesystempath');
    if (!empty($path)) {

        // Check path format.
        if (DIRECTORY_SEPARATOR == '\\') {
            $pattern = '/[^a-zA-Z0-9\/_\\\\\\:\-\.]/i';
        } else {
            $pattern = '/[^a-zA-Z0-9\/_\-\.]/i';
        }

        if (preg_match($pattern, $path)) {
            mtrace(get_string('error:notapathexportfilesystempath', 'totara_reportbuilder'));
        } else if (!is_dir($path)) {
            mtrace(get_string('error:notdirexportfilesystempath', 'totara_reportbuilder'));
        } else if (!is_writable($path)) {
            mtrace(get_string('error:notwriteableexportfilesystempath', 'totara_reportbuilder'));
        }
    }

    $dir = $path . DIRECTORY_SEPARATOR . $username;
    if (!file_exists($dir)) {
        mkdir($dir, (fileperms($path) & 02777));
    }
    $reportfilepathname = $dir . DIRECTORY_SEPARATOR . $reportfilename;

    return $reportfilepathname;
}

/**
 * Given a report database record, return the URL to the report
 *
 * For use when a reportbuilder object is not available. If a reportbuilder
 * object is being used, call {@link reportbuilder->report_url()} instead
 *
 * @param object $report Report builder database object. Must contain id, shortname and embedded parameters
 *
 * @return string URL of the report provided or false
 */
function reportbuilder_get_report_url($report) {
    global $CFG;
    if ($report->embedded == 0) {
        return $CFG->wwwroot . '/totara/reportbuilder/report.php?id=' . $report->id;
    } else {
        // use report shortname to find appropriate embedded report object
        if ($embedclass = reportbuilder::get_embedded_report_class($report->shortname)) {
            $embed = new $embedclass([]);
            return $CFG->wwwroot . $embed->url;
        } else {
            return $CFG->wwwroot;
        }
    }

}

/**
 * Generate object used to describe an embedded report
 *
 * This method returns a new instance of an embedded report object
 * Given an embedded report name, it finds the class, includes it then
 * calls the class passing in any data provided. The object created
 * by that call is returned, or false if something went wrong.
 *
 * @deprecated since Totara 12.3
 *
 * @param string $embedname Shortname of embedded report
 *                          e.g. X from rb_X_embedded.php
 * @param array $data Associative array of data needed by source (optional)
 *
 * @return object Embedded report object
 */
function reportbuilder_get_embedded_report_object($embedname, $data=array()) {
    if ($embedclass = reportbuilder::get_embedded_report_class($embedname)) {
        return new $embedclass($data);
    }

    // file or class not found
    return false;
}


/**
 * Generate actual embedded report
 *
 * This function is an alias to "new reportbuilder()", for use within embedded report pages. The embedded object
 * will be created within the reportbuilder constructor.
 *
 * @deprecated since Totara 12
 *
 * @param string $embedname Shortname of embedded report
 *                          e.g. X from rb_X_embedded.php
 * @param array $data Associative array of data needed by source (optional)
 * @param bool $nocache Disable cache
 * @param int $sid saved search id
 * @param rb_global_restriction_set $globalrestrictionset global report restrictions info
 *
 * @return reportbuilder Embedded report
 */
function reportbuilder_get_embedded_report($embedname, $data = array(), $nocache = false, $sid = 'nosidsupplied',
        rb_global_restriction_set $globalrestrictionset = null) {
    debugging('Function reportbuilder_get_embedded_report is deprecated since Totara 12. Please use reportbuilder::create_embedded() instead.', DEBUG_DEVELOPER);
    if ($sid === 'nosidsupplied') {
        debugging('Call to reportbuilder_get_embedded_report without supplying $sid is probably an error - if you
            want to save searches on your embedded report then you must pass in $sid here, otherwise pass 0 to remove
            this warning', DEBUG_DEVELOPER);
        $sid = 0;
    }

    $config = new rb_config();
    $config->set_sid($sid)
        ->set_nocache($nocache)
        ->set_embeddata($data)
        ->set_global_restriction_set($globalrestrictionset);
    return reportbuilder::create_embedded($embedname, $config);
}

/**
 * Returns an array of all embedded reports found in the filesystem, sorted by name
 *
 * Looks in the totara/reportbuilder/embedded/ directory and creates a new
 * object for each embedded report definition found. These are returned
 * as an array, sorted by the report fullname
 *
 * @return array Array of embedded report objects
 */
function reportbuilder_get_all_embedded_reports() {
    global $CFG;

    $embedded = array();
    $sourcepaths = reportbuilder::find_source_dirs();
    $sourcepaths[] = $CFG->dirroot . '/totara/reportbuilder/embedded/';
    foreach ($sourcepaths as $sourcepath) {
        if ($dh = opendir($sourcepath)) {
            while(($file = readdir($dh)) !== false) {
                if (is_dir($file) || !preg_match('|^rb_(.*)_embedded\.php$|', $file, $matches)) {
                    continue;
                }
                $name = $matches[1];
                $embedclass = reportbuilder::get_embedded_report_class($name);
                if ($embedclass) {
                    $embedded[] = new $embedclass([]);
                }
            }
            closedir($dh);
        }
    }
    // sort by fullname before returning
    usort($embedded, 'reportbuilder_sortbyfullname');
    return $embedded;
}

/**
 * Return object with cached record for report or false if not found
 *
 * @param int $reportid
 */
function reportbuilder_get_cached($reportid) {
    global $DB;
    $sql = "SELECT rbc.*, rb.cache, rb.fullname, rb.shortname, rb.embedded
            FROM {report_builder} rb
            LEFT JOIN {report_builder_cache} rbc ON rbc.reportid = rb.id
            WHERE rb.cache = 1
              AND rb.id = ?";
    return $DB->get_record_sql($sql, array($reportid));
}

/**
 * Get all reports with enabled caching
 *
 * @return array of stdClass
 */
function reportbuilder_get_all_cached() {
    global $DB, $CFG;
    if (empty($CFG->enablereportcaching)) {
        return array();
    }
    $sql = "SELECT rbc.*, rb.cache, rb.fullname, rb.shortname, rb.embedded
            FROM {report_builder} rb
            LEFT JOIN {report_builder_cache} rbc
                ON rb.id = rbc.reportid
            WHERE rb.cache = 1";
    $caches = $DB->get_records_sql($sql);
    $result = array();
    foreach ($caches as $c) {
        $result[$c->reportid] = $c;
    }
    return $result;
}
/**
 * Function for sorting by report fullname, used in usort as callback
 *
 * @param object $a The first array element
 * @param object $a The second array element
 *
 * @return integer 1, 0, or -1 depending on sort order
 */
function reportbuilder_sortbyfullname($a, $b) {
    return strcmp($a->fullname, $b->fullname);
}


/**
 * Returns the ID of an embedded report from its shortname, creating if necessary
 *
 * To save on db calls, you need to pass an array of the existing embedded
 * reports to this method, in the format key=id, value=shortname.
 *
 * If the shortname doesn't exist in the array provided this method will
 * create a new embedded report and return the new ID generated or false
 * on failure
 *
 * @param string $shortname The shortname you need the ID of
 * @param array $embedded_ids Array of embedded report IDs and shortnames
 *
 * @return int|false ID of the requested embedded report, or false if it could not be generated
 */
function reportbuilder_get_embedded_id_from_shortname($shortname, $embedded_ids) {
    // return existing ID if a database record exists already
    if (is_array($embedded_ids)) {
        foreach ($embedded_ids as $id => $embed_shortname) {
            if (is_string($embed_shortname)) {
                if ($shortname == $embed_shortname) {
                    return $id;
                }
            } else if (isset($embed_shortname->shortname) && $shortname === $embed_shortname->shortname) {
                return $id;
            }
        }
    }
    // otherwise, create a new embedded report and return the new ID
    // returns false if creation fails
    $embedclass = reportbuilder::get_embedded_report_class($shortname);
    $error = null;
    return reportbuilder_create_embedded_record($shortname, new $embedclass([]), $error);
}

/**
 * Creates a database entry for an embedded report when it is first viewed
 * so the settings can be edited
 *
 * @param string $shortname The unique name for this embedded report
 * @param object $embed An object containing the embedded reports settings
 * @param string &$error Error string to return on failure
 *
 * @return boolean ID of new database record, or false on failure
 */
function reportbuilder_create_embedded_record($shortname, $embed, &$error) {
    global $DB;
    $error = null;

    // check input
    if (!isset($shortname)) {
        $error = 'Bad shortname';
        return false;
    }
    if (!isset($embed->source)) {
        $error = 'Bad source';
        return false;
    }
    if (!isset($embed->filters) || !is_array($embed->filters)) {
        $embed->filters = array();
    }
    if (!isset($embed->columns) || !is_array($embed->columns)) {
        $error = 'Bad columns';
        return false;
    }
    if (!isset($embed->toolbarsearchcolumns) || !is_array($embed->toolbarsearchcolumns)) {
        $embed->toolbarsearchcolumns = array();
    }
    // hide embedded reports from report manager by default
    $embed->hidden = isset($embed->hidden) ? $embed->hidden : 1;
    $embed->accessmode = isset($embed->accessmode) ? $embed->accessmode : 0;
    $embed->contentmode = isset($embed->contentmode) ? $embed->contentmode : 0;

    $embed->accesssettings = isset($embed->accesssettings) ? $embed->accesssettings : array();
    $embed->contentsettings = isset($embed->contentsettings) ? $embed->contentsettings : array();

    $embed->defaultsortcolumn = isset($embed->defaultsortcolumn) ? $embed->defaultsortcolumn : '';
    $embed->defaultsortorder = isset($embed->defaultsortorder) ? $embed->defaultsortorder : SORT_ASC;

    $todb = new stdClass();
    $todb->shortname = $shortname;
    $todb->fullname = $embed->fullname;
    $todb->source = $embed->source;
    $todb->hidden = 1; // hide embedded reports by default
    $todb->accessmode = $embed->accessmode;
    $todb->contentmode = $embed->contentmode;
    $todb->embedded = 1;
    $todb->defaultsortcolumn = $embed->defaultsortcolumn;
    $todb->defaultsortorder = $embed->defaultsortorder;

    if (isset($embed->recordsperpage)) {
        $todb->recordsperpage = $embed->recordsperpage;
    }

    // Note: embedded reports are not expected to have global restrictions for performance reasons.
    $todb->globalrestriction = reportbuilder::GLOBAL_REPORT_RESTRICTIONS_DISABLED;

    if (isset($embed->initialdisplay)) {
        $todb->initialdisplay = $embed->initialdisplay;
    }

    $transaction = $DB->start_delegated_transaction();

    try {
        $newid = $DB->insert_record('report_builder', $todb);
        // Add columns.
        $so = 1;
        foreach ($embed->columns as $column) {
            $todb = new stdClass();
            $todb->reportid = $newid;
            $todb->type = $column['type'];
            $todb->value = $column['value'];
            $todb->heading = $column['heading'];
            $todb->sortorder = $so;
            $todb->customheading = 0; // Initially no columns are customised.
            $todb->hidden = isset($column['hidden']) ? $column['hidden'] : 0;
            $DB->insert_record('report_builder_columns', $todb);
            $so++;
        }
        // Add filters.
        $so = 1;
        foreach ($embed->filters as $filter) {
            $todb = new stdClass();
            $todb->reportid = $newid;
            $todb->type = $filter['type'];
            $todb->value = $filter['value'];
            $todb->advanced = isset($filter['advanced']) ? $filter['advanced'] : 0;
            $todb->defaultvalue = !empty($filter['defaultvalue']) ? serialize($filter['defaultvalue']) : null;
            if (isset($filter['fieldname'])) {
                $todb->filtername = $filter['fieldname'];
                $todb->customname =  1;
            } else {
                $todb->filtername = '';
                $todb->customname =  0;
            }
            $todb->sortorder = $so;
            $todb->region = isset($filter['region']) ? $filter['region'] : rb_filter_type::RB_FILTER_REGION_STANDARD;
            $DB->insert_record('report_builder_filters', $todb);
            $so++;
        }
        // Add toolbar search columns.
        foreach ($embed->toolbarsearchcolumns as $toolbarsearchcolumn) {
            $todb = new stdClass();
            $todb->reportid = $newid;
            $todb->type = $toolbarsearchcolumn['type'];
            $todb->value = $toolbarsearchcolumn['value'];
            $DB->insert_record('report_builder_search_cols', $todb);
        }
        // Add content restrictions.
        foreach ($embed->contentsettings as $option => $settings) {
            $classname = $option . '_content';
            if (class_exists('rb_' . $classname)) {
                foreach ($settings as $name => $value) {
                    if (!reportbuilder::update_setting($newid, $classname, $name, $value)) {
                            throw new moodle_exception('Error inserting content restrictions');
                        }
                }
            }
        }
        // add access restrictions
        foreach ($embed->accesssettings as $option => $settings) {
            $classname = $option . '_access';
            if (class_exists($classname)) {
                foreach ($settings as $name => $value) {
                    if (!reportbuilder::update_setting($newid, $classname, $name, $value)) {
                            throw new moodle_exception('Error inserting access restrictions');
                        }
                }
            }
        }

        // Thanks to is_capable() we cannot get the instance of report here and trigger the event,
        // if necessary we could add a new event class here later.
        //\totara_reportbuilder\event\report_created::create_from_report($report, true)->trigger();

        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
        $error = $e->getMessage();
        return false;
    }

    return $newid;
}


/**
 * Attempt to ensure an SQL named param is unique by appending a random number value
 * and keeping records of other param names
 *
 * @param string $name the param name to make unique
 * @return string the unique string
 */
function rb_unique_param($name) {
    global $DB;
    return $DB->get_unique_param($name);
}

/**
 * Helper function for renaming the data in the columns/filters table
 *
 * Useful when a field is renamed and the report data needs to be updated
 *
 * @param string $table Table to update, either 'filters' or 'columns'
 * @param string $source Name of the source or '*' to update all sources
 * @param string $oldtype The type of the item to change
 * @param string $oldvalue The value of the item to change
 * @param string $newtype The new type of the item
 * @param string $newvalue The new value of the item
 *
 * @return boolean Result from the update query or true if no data to update
 */
function reportbuilder_rename_data($table, $source, $oldtype, $oldvalue, $newtype, $newvalue) {
    global $DB;

    if ($source == '*') {
        $sourcesql = '';
        $params = array();
    } else {
        $sourcesql = ' AND rb.source = :source';
        $params = array('source' => $source);
    }

    $sql = "SELECT rbt.id FROM {report_builder_{$table}} rbt
        JOIN {report_builder} rb
        ON rbt.reportid = rb.id
        WHERE rbt.type = :oldtype AND rbt.value = :oldvalue
        $sourcesql";
    $params['oldtype'] = $oldtype;
    $params['oldvalue'] = $oldvalue;

    $items = $DB->get_fieldset_sql($sql, $params);

    if (!empty($items)) {
        list($insql, $params) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED);
        $sql = "UPDATE {report_builder_{$table}}
            SET type = :newtype, value = :newvalue
            WHERE id $insql";
        $params['newtype'] = $newtype;
        $params['newvalue'] = $newvalue;
        $DB->execute($sql, $params);
    }
    return true;
}

/**
 * Returns available export options for reportbuilder.
 *
 * @param string $currentoption optional option that is displayed even if not enabled in settings
 * @param bool $includefusion
 * @return array (export format => localised name of export option)
 */
function reportbuilder_get_export_options($currentoption = null, $includefusion = false) {
    $exportoptions = get_config('reportbuilder', 'exportoptions');
    $exportoptions = !empty($exportoptions) ? explode(',', $exportoptions) : array();

    $enabled = array();
    foreach ($exportoptions as $option) {
        $option = \totara_core\tabexport_writer::normalise_format($option);
        if ($option) {
            $enabled[$option] = true;
        }
    }

    $select = array();
    $alloptions = \totara_core\tabexport_writer::get_export_options();
    foreach ($alloptions as $type => $name) {
        if (!isset($enabled[$type])) {
            continue;
        }
        $select[$type] = $name;
    }

    // Fusion is not a real plugin yet.
    if ($includefusion and isset($enabled['fusion'])) {
        $select['fusion'] = get_string('exportfusion', 'totara_reportbuilder');
    }

    // Add current option,
    // this allows existing scheduled reports to work even if export options change.
    if ($currentoption) {
        if (isset($alloptions[$currentoption]) and !isset($select[$currentoption])) {
            $select[$currentoption] = $alloptions[$currentoption];
        }
    }

    return $select;
}

/**
* Serves reportbuilder file type files. Required for M2 File API
*
* @param object $course
* @param object $cm
* @param object $context
* @param string $filearea
* @param array $args
* @param bool $forcedownload
* @param array $options
* @return bool false if file not found, does not return if found - just send the file
*/
function totara_reportbuilder_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/totara_reportbuilder/$filearea/$args[0]/$args[1]";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    // finally send the file
    send_stored_file($file, 86400, 0, true, $options); // download MUST be forced - security!
}

/**
 * Get extrafield alias.
 * Hash type and value so it works when caching reports in MySQL
 * (current restriction in MySQL: fieldname cannot be longer than 64 chars)
 *
 * @param string $type column type of this option in the report
 * @param string $value column value of this option in the report
 * @param string $name the field name
 * @return string $extrafieldalias
 */
function reportbuilder_get_extrafield_alias($type, $value, $name) {
    $typevalue = "{$type}_{$value}";
    $hashtypevalue = substr(md5($typevalue), 0, 10);
    $extrafieldalias = "ef_{$hashtypevalue}_{$name}";

    return $extrafieldalias;
}

/**
 * Deletes a report and any associated data
 *
 * @param integer $id ID of the report to delete
 *
 * @return boolean True if report was successfully deleted
 */
function reportbuilder_delete_report($id) {
    global $DB;

    if (!$id) {
        return false;
    }

    $transaction = $DB->start_delegated_transaction();

    // Delete report source cache.
    reportbuilder_purge_cache($id, true);

    // Delete graph related data.
    $DB->delete_records('report_builder_graph', array('reportid' => $id));
    // Delete scheduling related data.
    $select = "scheduleid IN (SELECT s.id FROM {report_builder_schedule} s WHERE s.reportid = ?)";
    $DB->delete_records_select('report_builder_schedule_email_audience', $select, array($id));
    $DB->delete_records_select('report_builder_schedule_email_systemuser', $select, array($id));
    $DB->delete_records_select('report_builder_schedule_email_external', $select, array($id));
    $DB->delete_records('report_builder_schedule', array('reportid' => $id));
    // Delete search related data.
    $DB->delete_records('report_builder_search_cols', array('reportid' => $id));
    // Delete any columns.
    $DB->delete_records('report_builder_columns', array('reportid' => $id));

    // Delete any filters.
    $DB->delete_records('report_builder_filters', array('reportid' => $id));
    // Delete any content and access settings.
    $DB->delete_records('report_builder_settings', array('reportid' => $id));
    // Delete any saved searches.
    $DB->delete_records('report_builder_saved', array('reportid' => $id));

    // Delete the report.
    $DB->delete_records('report_builder', array('id' => $id));

    // all okay commit changes
    $transaction->allow_commit();

    return true;
}

/**
 * Set default restrictive access for new report
 * @param int $reportid
 */
function reportbuilder_set_default_access($reportid) {
    global $DB;
    $accessdata = new stdClass();
    $accessdata->role_enable = 1;
    $accessdata->role_context = 'site';

    if ($managerroleid = $DB->get_field('role', 'id', array('shortname' => 'manager'))) {
        $accessdata->role_activeroles = array($managerroleid => 1);
    }
    $acess = new \totara_reportbuilder\rb\access\role();
    $acess->form_process($reportid, $accessdata);
}

/**
 * Makes clone of report
 *
 * @param reportbuilder $report Original report instance
 * @param string $clonename Name of clone
 *
 * @return int Id of new report if report was successfully cloned
 */
function reportbuilder_clone_report(reportbuilder $report, $clonename) {
    global $DB;

    $transaction = $DB->start_delegated_transaction();
    $reportid = $report->_id;

    // Copy report.
    $reportrec = $DB->get_record('report_builder', array('id' => $reportid));
    $embedded = $report->embedded;

    $reportrec->id = null;
    $reportrec->cache = 0;
    $reportrec->embedded = 0;
    $reportrec->timemodified = time();
    $reportrec->fullname = $clonename;
    // NOTE: do not change the global restriction flag here.

    if ($embedded) {
        $reportrec->accessmode = REPORT_BUILDER_ACCESS_MODE_ANY;
    }

    // Search for shortname.
    $count = 1;
    while ($DB->get_field('report_builder', 'shortname', array('shortname' => $reportrec->shortname . $count),
            IGNORE_MISSING)) {
        $count++;
    }
    $reportrec->shortname .= $count;

    $cloneid = $DB->insert_record('report_builder', $reportrec);

    // Restrict acces to Site Manager only for embedded reports.
    if ($embedded) {
        reportbuilder_set_default_access($cloneid);
    }

    // Copy columns.
    $colrecs = $DB->get_records('report_builder_columns', array('reportid' => $reportid));
    foreach ($colrecs as $colrec) {
        $colrec->id = null;
        $colrec->reportid = $cloneid;
        $DB->insert_record('report_builder_columns', $colrec);
    }

    // Copy search columns.
    $searchcolrecs = $DB->get_records('report_builder_search_cols', array('reportid' => $reportid));
    foreach ($searchcolrecs as $searchcolrec) {
        $searchcolrec->id = null;
        $searchcolrec->reportid = $cloneid;
        $DB->insert_record('report_builder_search_cols', $searchcolrec);
    }

    // Copy filters.
    $filterrecs = $DB->get_records('report_builder_filters', array('reportid' => $reportid));
    foreach ($filterrecs as $filterrec) {
        $filterrec->id = null;
        $filterrec->reportid = $cloneid;
        $DB->insert_record('report_builder_filters', $filterrec);
    }

    // Copy settings.
    $settingsrecs = $DB->get_records('report_builder_settings', array('reportid' => $reportid));
    foreach ($settingsrecs as $settingsrec) {
        $settingsrec->id = null;
        $settingsrec->reportid = $cloneid;
        $DB->insert_record('report_builder_settings', $settingsrec);
    }

    // Copy graph.
    $graphrecs = $DB->get_records('report_builder_graph', array('reportid' => $reportid));
    foreach ($graphrecs as $graphrec) {
        $graphrec->id = null;
        $graphrec->reportid = $cloneid;
        $graphrec->timemodified = time();
        $DB->insert_record('report_builder_graph', $graphrec);
    }

    // All okay commit changes.
    $transaction->allow_commit();

    return $cloneid;
}
