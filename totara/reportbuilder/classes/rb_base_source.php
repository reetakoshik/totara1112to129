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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Abstract base class to be extended to create report builder sources
 */
abstract class rb_base_source {
    use \core_user\rb\source\report_trait;
    use \totara_customfield\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\base_deprecated_t12_trait; // To be deleted in T13.

    // ==== Properties required to be specified in every source ====

    /** @var string the base database table (or query), must be set */
    public $base = null;

    /** @var string name of the source, must be set */
    public $sourcetitle = null;

    /** @var rb_column_option[] list of available columns */
    public $columnoptions = array();

    // ==== Optional properties to be defined in sources ====

    /** @var rb_join[] list of joins */
    public $joinlist = array();

    /** @var rb_filter_option[] list of available filters */
    public $filteroptions = array();

    /** @var rb_content_option[] list fo available content restrictions  */
    public $contentoptions = array();

    /** @var rb_param_option[] list fo available parameters */
    public $paramoptions = array();

    /** @var array list of default columns in new reports based on the source */
    public $defaultcolumns = array();

    /** @var array list of default filters in new reports based on the source */
    public $defaultfilters = array();

    /** @var bool true if source is selectable for new reports */
    public $selectable = true;

    /** @var bool true if reports with this source can be scheduled */
    public $scheduleable = true;

    /** @var bool true if reports with this source are compatible with report caching */
    public $cacheable = true;

    /** @var string extra WHERE statement for source */
    public $sourcewhere = '';

    /** @var string[] joins required for where, ignored if sourcewhere not specified */
    public $sourcejoins = array();

    /** @var array sql parameters for sourcewhere */
    public $sourceparams = array();

    /** @var rb_column[] columns that must be always present (not recommended to be used) */
    public $requiredcolumns = array();

    // === Internal properties, do not modify in child classes directly ===

    /** @var bool Used in default pre_display_actions function. */
    public $needsredirect = false;

    /** @var string|\moodle_url Used in default pre_display_actions function. */
    public $redirecturl;

    /** @var string Used in default pre_display_actions function. */
    public $redirectmessage;

    /** @var string[] of components used for lookup of /rb/ classes */
    protected $usedcomponents = array();

    /** @var rb_global_restriction_set with active restrictions, ignore if null */
    protected $globalrestrictionset = null;

    /** @var rb_join[] list of global report restriction joins  */
    public $globalrestrictionjoins = array();

    /** @var array named query params used in global restriction joins */
    public $globalrestrictionparams = array();

    /**
     * @var string $uniqueseperator - A string unique enough to use as a separator for textareas
     */
    protected $uniquedelimiter = '^|:';

    /** @var array list of methods that are called at the end of constructor */
    private $finalisation_methods = array();

    /**
     * Class constructor
     *
     * Call from the constructor of all child classes with:
     *
     *  parent::__construct()
     *
     * to ensure child class has implemented everything necessary to work.
     */
    public function __construct() {
        // Extending classes should add own component to this array before calling parent constructor,
        // this allows us to lookup display classes at more locations.
        $this->usedcomponents[] = 'totara_reportbuilder';
        $this->usedcomponents[] = 'totara_customfield';

        // Check that child classes implement required properties.
        if (!$this->base) {
            $a = new stdClass();
            $a->property = 'base';
            $a->class = get_class($this);
            throw new ReportBuilderException(get_string('error:propertyxmustbesetiny', 'totara_reportbuilder', $a));
        }
        if (!$this->columnoptions) {
            debugging('No columns options defined in report source', DEBUG_DEVELOPER);
        }
        if (!isset($this->sourcetitle)) {
            debugging('No sourcetitle defined in report source', DEBUG_DEVELOPER);
            $this->sourcetitle = static::class;
        }

        // Check array types.
        $properties = array(
            'columnoptions',
            'joinlist',
            'contentoptions',
            'paramoptions',
            'defaultcolumns',
            'defaultfilters',
            'requiredcolumns',
        );
        foreach ($properties as $property) {
            if ($this->{$property} === null) {
                //NOTE: we should fix the sources, continue for now.
                $this->{$property} = array();
                continue;
            }
            if (!is_array($this->{$property})) {
                debugging("Invalid value in {$property}, it must be an array");
                $this->{$property} = array();
            }
        }

        // Make sure that there are no column options using subqueries if report is grouped.
        if ($this->get_grouped_column_options()) { // Deprecated since Totara 12
            foreach ($this->columnoptions as $k => $option) {
                if ($option->issubquery) {
                    unset($this->columnoptions[$k]);
                    // Also remove relevant filter if present.
                    foreach ($this->filteroptions as $fk => $foptions) {
                        if ($foptions->type === $option->type and $foptions->value === $option->value) {
                            if (empty($foptions->field)) {
                                unset($this->filteroptions[$fk]);
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Let traits do their finalisation.
        foreach ($this->finalisation_methods as $method => $unused) {
            $this->{$method}();
        }

        // Use magic to insert Totara custom field stuff.
        $this->add_totara_customfield_base();

        // basic sanity checking of joinlist
        $this->validate_joinlist();
    }

    /**
     * Is this report source usable?
     *
     * Override and return true if the source should be hidden
     * in all user interfaces. For example when the source
     * requires some subsystem to be enabled.
     *
     * @deprecated since Totara 12.3
     * @return bool
     */
    public function is_ignored() {
        // NOTE: override in source if required.
        return false;
    }

    /**
     * Is this report source usable?
     *
     * Override and return true if the source should be hidden
     * in all user interfaces. For example when the source
     * requires some subsystem to be enabled.
     *
     * @return bool
     */
    public static function is_source_ignored() {
        return false;
    }

        /**
     * Are the global report restrictions implemented in the source?
     *
     * Return values mean:
     *   - true: this report source supports global report restrictions.
     *   - false: this report source does NOT support global report restrictions.
     *   - null: this report source has not been converted to use global report restrictions yet.
     *
     * @return null|bool
     */
    public function global_restrictions_supported() {
        // Null means not converted yet, override in sources with true or false.
        // NOTE: always override in source.
        return null;
    }

    /**
     * Set redirect url and (optionally) message for use in default pre_display_actions function.
     *
     * When pre_display_actions is call it will redirect to the specified url (unless pre_display_actions
     * is overridden, in which case it performs those actions instead).
     *
     * @param mixed $url moodle_url or url string
     * @param string $message
     */
    protected function set_redirect($url, $message = null) {
        $this->redirecturl = $url;
        $this->redirectmessage = $message;
    }


    /**
     * Set whether redirect needs to happen in pre_display_actions.
     *
     * @param bool $truth true if redirect is needed
     */
    protected function needs_redirect($truth = true) {
        $this->needsredirect = $truth;
    }


    /**
     * Default pre_display_actions - if needsredirect is true then redirect to the specified
     * page, otherwise do nothing.
     *
     * This function is called after post_config and before report data is generated. This function is
     * not called when report data is not generated, such as on report setup pages.
     * If you want to perform a different action after post_config then override this function and
     * set your own private variables (e.g. to signal a result from post_config) in your report source.
     */
    public function pre_display_actions() {
        if ($this->needsredirect && isset($this->redirecturl)) {
            if (isset($this->redirectmessage)) {
                totara_set_notification($this->redirectmessage, $this->redirecturl, array('class' => 'notifymessage'));
            } else {
                redirect($this->redirecturl);
            }
        }
    }

    /**
     * Check the joinlist for invalid dependencies and duplicate names
     *
     * @return True or throws exception if problem found
     */
    private function validate_joinlist() {
        $joinlist = $this->joinlist;
        $joins_used = array();

        // don't let source define join with same name as an SQL
        // reserved word
        $reserved_words = sql_generator::getAllReservedWords();
        $reserved_words = array_keys($reserved_words);

        foreach ($joinlist as $item) {
            // check join list for duplicate names
            if (in_array($item->name, $joins_used)) {
                $a = new stdClass();
                $a->join = $item->name;
                $a->source = get_class($this);
                throw new ReportBuilderException(get_string('error:joinxusedmorethanonceiny', 'totara_reportbuilder', $a));
            } else {
                $joins_used[] = $item->name;
            }

            if (in_array($item->name, $reserved_words)) {
                $a = new stdClass();
                $a->join = $item->name;
                $a->source = get_class($this);
                throw new ReportBuilderException(get_string('error:joinxisreservediny', 'totara_reportbuilder', $a));
            }
        }

        foreach ($joinlist as $item) {
            // check that dependencies exist
            if (isset($item->dependencies) &&
                is_array($item->dependencies)) {

                foreach ($item->dependencies as $dep) {
                    if ($dep == 'base') {
                        continue;
                    }
                    if (!in_array($dep, $joins_used)) {
                        $a = new stdClass();
                        $a->join = $item->name;
                        $a->source = get_class($this);
                        $a->dependency = $dep;
                        throw new ReportBuilderException(get_string('error:joinxhasdependencyyinz', 'totara_reportbuilder', $a));
                    }
                }
            } else if (isset($item->dependencies) &&
                $item->dependencies != 'base') {

                if (!in_array($item->dependencies, $joins_used)) {
                    $a = new stdClass();
                    $a->join = $item->name;
                    $a->source = get_class($this);
                    $a->dependency = $item->dependencies;
                    throw new ReportBuilderException(get_string('error:joinxhasdependencyyinz', 'totara_reportbuilder', $a));
                }
            }
        }
        return true;
    }

    /**
     * Add a finalisation method to be called at the end of source constructor.
     *
     * This is intended for trait to finish their setup or add validation,
     * usually needed when a trait keeps internal state.
     *
     * @param string $method
     */
    public function add_finalisation_method($method) {
        if (!method_exists($this, $method)) {
            throw new coding_exception('Invalid report source finalisation method');
        }
        $this->finalisation_methods[$method] = true;
    }

    //
    //
    // General purpose source specific methods
    //
    //

    /**
     * Returns a new rb_column object based on a column option from this source
     *
     * If $heading is given use it for the heading property, otherwise use
     * the default heading property from the column option
     *
     * @param string $type The type of the column option to use
     * @param string $value The value of the column option to use
     * @param int $transform
     * @param int $aggregate
     * @param string $heading Heading for the new column
     * @param boolean $customheading True if the heading has been customised
     * @return rb_column A new rb_column object with details copied from this rb_column_option
     */
    public function new_column_from_option($type, $value, $transform, $aggregate, $heading=null, $customheading = true, $hidden=0) {
        $columnoptions = $this->columnoptions;
        $joinlist = $this->joinlist;
        if ($coloption =
            reportbuilder::get_single_item($columnoptions, $type, $value)) {

            // make sure joins are defined before adding column
            if (!reportbuilder::check_joins($joinlist, $coloption->joins)) {
                $a = new stdClass();
                $a->type = $coloption->type;
                $a->value = $coloption->value;
                $a->source = get_class($this);
                throw new ReportBuilderException(get_string('error:joinsfortypexandvalueynotfoundinz', 'totara_reportbuilder', $a));
            }

            if ($heading === null) {
                $heading = ($coloption->defaultheading !== null) ?
                    $coloption->defaultheading : $coloption->name;
            }

            return new rb_column(
                $type,
                $value,
                $heading,
                $coloption->field,
                array(
                    'joins' => $coloption->joins,
                    'displayfunc' => $coloption->displayfunc,
                    'extrafields' => $coloption->extrafields,
                    'required' => false,
                    'capability' => $coloption->capability,
                    'noexport' => $coloption->noexport,
                    'grouping' => $coloption->grouping,     // Deprecated since Totara 12
                    'grouporder' => $coloption->grouporder, // Deprecated since Totara 12
                    'nosort' => $coloption->nosort,
                    'style' => $coloption->style,
                    'class' => $coloption->class,
                    'hidden' => $hidden,
                    'customheading' => $customheading,
                    'transform' => $transform,
                    'aggregate' => $aggregate,
                    'extracontext' => $coloption->extracontext
                )
            );
        } else {
            $a = new stdClass();
            $a->type = $type;
            $a->value = $value;
            $a->source = get_class($this);
            throw new ReportBuilderException(get_string('error:columnoptiontypexandvalueynotfoundinz', 'totara_reportbuilder', $a));
        }
    }

    /**
     * Returns list of used components.
     *
     * The list includes frankenstyle component names of the
     * current source and all parents.
     *
     * @return string[]
     */
    public function get_used_components() {
        return $this->usedcomponents;
    }

    //
    //
    // Generic column display methods
    //
    //

    /**
     * Format row record data for display.
     *
     * @param stdClass $row
     * @param string $format
     * @param reportbuilder $report
     * @return array of strings usually, values may be arrays for Excel format for example.
     */
    public function process_data_row(stdClass $row, $format, reportbuilder $report) {
        $results = array();
        $isexport = ($format !== 'html');

        foreach ($report->columns as $column) {
            if (!$column->display_column($isexport)) {
                continue;
            }

            $type = $column->type;
            $value = $column->value;
            $field = strtolower("{$type}_{$value}");

            if (!property_exists($row, $field)) {
                $results[] = get_string('unknown', 'totara_reportbuilder');
                continue;
            }

            $classname = $column->get_display_class($report);
            $results[] = $classname::display($row->$field, $format, $row, $column, $report);
        }

        return $results;
    }

    /**
     * Expanding content to display when clicking a program.
     * Will be placed inside a table cell which is the width of the table.
     * Call required_param to get any param data that is needed.
     * Make sure to check that the data requested is permitted for the viewer.
     *
     * @return string
     */
    public function rb_expand_prog_details() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');
        require_once($CFG->dirroot . '/totara/program/renderer.php');

        $progid = required_param('expandprogid', PARAM_INT);
        $userid = $USER->id;

        if (!$program = new program($progid)) {
            ajax_result(false, get_string('error:programid', 'totara_program'));
            exit();
        }

        if (!$program->is_viewable()) {
            ajax_result(false, get_string('error:inaccessible', 'totara_program'));
            exit();
        }

        $formdata = $DB->get_record('prog', array('id' => $progid));

        $phelper = new programcat_helper();
        $formdata->summary = $phelper->get_program_formatted_summary(new program_in_list($formdata));

        $formdata->assigned = $DB->record_exists('prog_user_assignment', array('userid' => $userid, 'programid' => $progid));

        $mform = new report_builder_program_expand_form(null, (array)$formdata);

        return $mform->render();
    }

    /**
     * Helper function to convert a language code to a human-readable string
     *
     * @param string $code Language code
     * @return string
     */
    public function language_code_to_name($code) {
        global $CFG;
        static $languages = array();
        $strmgr = get_string_manager();
        // Populate the static variable if empty
        if (count($languages) == 0) {
            // Return all languages available in system (adapted from stringmanager->get_list_of_translations()).
            $langdirs = get_list_of_plugins('', '', $CFG->langotherroot);
            $langdirs = array_merge($langdirs, array("{$CFG->dirroot}/lang/en"=>'en'));
            $curlang = current_language();
            // Loop through all langs and get info.
            foreach ($langdirs as $lang) {
                if (isset($languages[$lang])){
                    continue;
                }
                if (strstr($lang, '_local') !== false) {
                    continue;
                }
                if (strstr($lang, '_utf8') !== false) {
                    continue;
                }
                $string = $strmgr->load_component_strings('langconfig', $lang);
                if (!empty($string['thislanguage'])) {
                    $languages[$lang] = $string['thislanguage'];
                    // If not the current language, provide the English translation also.
                    if(strpos($lang, $curlang) === false) {
                        $languages[$lang] .= ' ('. $string['thislanguageint'] .')';
                    }
                }
                unset($string);
            }
        }

        if (empty($code)) {
            return get_string('notspecified', 'totara_reportbuilder');
        }
        if (strpos($code, '_') !== false) {
            list($langcode, $langvariant) = explode('_', $code);
        } else {
            $langcode = $code;
        }

        // Now see if we have a match in "localname (English)" format.
        if (isset($languages[$code])) {
            return $languages[$code];
        } else {
            // Not an installed language - may have been uninstalled, as last resort try the get_list_of_languages silly function.
            $langcodes = $strmgr->get_list_of_languages();
            if (isset($langcodes[$langcode])) {
                $a = new stdClass();
                $a->code = $langcode;
                $a->name = $langcodes[$langcode];
                return get_string('uninstalledlanguage', 'totara_reportbuilder', $a);
            } else {
                return get_string('unknownlanguage', 'totara_reportbuilder', $code);
            }
        }
    }

    //
    //
    // Generic select filter methods
    //
    //

    function rb_filter_yesno_list() {
        $yn = array();
        $yn[1] = get_string('yes');
        $yn[0] = get_string('no');
        return $yn;
    }

    function rb_filter_modules_list() {
        global $DB, $OUTPUT, $CFG;

        $out = array();
        $mods = $DB->get_records('modules', array('visible' => 1), 'id', 'id, name');
        foreach ($mods as $mod) {
            if (get_string_manager()->string_exists('pluginname', $mod->name)) {
                $mod->localname = get_string('pluginname', $mod->name);
            }
        }

        core_collator::asort_objects_by_property($mods, 'localname');

        foreach ($mods as $mod) {
            if (file_exists($CFG->dirroot . '/mod/' . $mod->name . '/pix/icon.gif') ||
                file_exists($CFG->dirroot . '/mod/' . $mod->name . '/pix/icon.png')) {
                $icon = $OUTPUT->pix_icon('icon', $mod->localname, $mod->name) . '&nbsp;';
            } else {
                $icon = '';
            }

            $out[$mod->name] = $icon . $mod->localname;
        }
        return $out;
    }

    function rb_filter_organisations_list($report) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');

        $contentmode = $report->contentmode;
        $contentoptions = $report->contentoptions;
        $reportid = $report->_id;

        // show all options if no content restrictions set
        if ($contentmode == REPORT_BUILDER_CONTENT_MODE_NONE) {
            $hierarchy = new organisation();
            $hierarchy->make_hierarchy_list($orgs, null, true, false);
            return $orgs;
        }

        $baseorg = null; // default to top of tree

        $localset = false;
        $nonlocal = false;
        // are enabled content restrictions local or not?
        if (isset($contentoptions) && is_array($contentoptions)) {
            foreach ($contentoptions as $option) {
                $name = $option->classname;
                $classname = 'rb_' . $name . '_content';
                $settingname = $name . '_content';
                if (class_exists($classname)) {
                    if ($name == 'completed_org' || $name == 'current_org') {
                        if (reportbuilder::get_setting($reportid, $settingname, 'enable')) {
                            $localset = true;
                        }
                    } else {
                        if (reportbuilder::get_setting($reportid, $settingname, 'enable')) {
                            $nonlocal = true;
                        }
                    }
                }
            }
        }

        if ($contentmode == REPORT_BUILDER_CONTENT_MODE_ANY) {
            if ($localset && !$nonlocal) {
                // only restrict the org list if all content restrictions are local ones
                if ($orgid = $DB->get_field('job_assignment', 'organisationid', array('userid' => $USER->id))) {
                    $baseorg = $orgid;
                }
            }
        } else if ($contentmode == REPORT_BUILDER_CONTENT_MODE_ALL) {
            if ($localset) {
                // restrict the org list if any content restrictions are local ones
                if ($orgid = $DB->get_field('job_assignment', 'organisationid', array('userid' => $USER->id))) {
                    $baseorg = $orgid;
                }
            }
        }

        $hierarchy = new organisation();
        $hierarchy->make_hierarchy_list($orgs, $baseorg, true, false);

        return $orgs;

    }

    function rb_filter_positions_list() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

        $hierarchy = new position();
        $hierarchy->make_hierarchy_list($positions, null, true, false);

        return $positions;

    }

    function rb_filter_course_categories_list() {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');
        $cats = coursecat::make_categories_list();

        return $cats;
    }


    function rb_filter_competency_type_list() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

        $competencyhierarchy = new competency();
        $unclassified_option = array(0 => get_string('unclassified', 'totara_hierarchy'));
        $typelist = $unclassified_option + $competencyhierarchy->get_types_list();

        return $typelist;
    }


    function rb_filter_position_type_list() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

        $positionhierarchy = new position();
        $unclassified_option = array(0 => get_string('unclassified', 'totara_hierarchy'));
        $typelist = $unclassified_option + $positionhierarchy->get_types_list();

        return $typelist;
    }


    function rb_filter_organisation_type_list() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');

        $organisationhierarchy = new organisation();
        $unclassified_option = array(0 => get_string('unclassified', 'totara_hierarchy'));
        $typelist = $unclassified_option + $organisationhierarchy->get_types_list();

        return $typelist;
    }

    function rb_filter_course_languages() {
        global $DB;
        $out = array();
        $langs = $DB->get_records_sql("SELECT DISTINCT lang
            FROM {course} ORDER BY lang");
        foreach ($langs as $row) {
            $out[$row->lang] = $this->language_code_to_name($row->lang);
        }

        return $out;
    }

    /**
     *
     * @return array possible course types
     */
    public function rb_filter_course_types() {
        global $TOTARA_COURSE_TYPES;
        $coursetypeoptions = array();
        foreach ($TOTARA_COURSE_TYPES as $k => $v) {
            $coursetypeoptions[$v] = get_string($k, 'totara_core');
        }
        asort($coursetypeoptions);
        return $coursetypeoptions;
    }

    /*
     * Generate a list of options fo the plan status menu.
     * @return array plan status menu options.
     */
    public function rb_filter_plan_status() {
        return array (
            DP_PLAN_STATUS_UNAPPROVED => get_string('unapproved', 'totara_plan'),
            DP_PLAN_STATUS_PENDING => get_string('pendingapproval', 'totara_plan'),
            DP_PLAN_STATUS_APPROVED => get_string('approved', 'totara_plan'),
            DP_PLAN_STATUS_COMPLETE => get_string('complete', 'totara_plan')
        );
    }

    //
    //
    // Methods for adding commonly used data to source definitions
    //
    //

    //
    // Wrapper functions to add columns/fields/joins in one go
    //
    //

    /**
     * Returns true if global report restrictions can be used with this source.
     *
     * @return bool
     */
    protected function can_global_report_restrictions_be_used() {
        global $CFG;
        return (!empty($CFG->enableglobalrestrictions) && $this->global_restrictions_supported()
                && $this->globalrestrictionset);
    }

    /**
     * Returns global restriction SQL fragment that can be used in complex joins for example.
     *
     * @return string SQL fragment
     */
    protected function get_global_report_restriction_query() {
        // First ensure that global report restrictions can be used with this source.
        if (!$this->can_global_report_restrictions_be_used()) {
            return '';
        }

        list($query, $parameters) = $this->globalrestrictionset->get_join_query();

        if ($parameters) {
            $this->globalrestrictionparams = array_merge($this->globalrestrictionparams, $parameters);
        }

        return $query;
    }

    /**
     * Adds global restriction join to the report.
     *
     * @param string $join Name of the join that provides the 'user id' field
     * @param string $field Name of user id field to join on
     * @param mixed $dependencies join dependencies
     * @return bool
     */
    protected function add_global_report_restriction_join($join, $field, $dependencies = 'base') {
        // First ensure that global report restrictions can be used with this source.
        if (!$this->can_global_report_restrictions_be_used()) {
            return false;
        }

        list($query, $parameters) = $this->globalrestrictionset->get_join_query();

        if ($query === '') {
            return false;
        }

        static $counter = 0;
        $counter++;
        $joinname = 'globalrestrjoin_' . $counter;

        $this->globalrestrictionjoins[] = new rb_join(
            $joinname,
            'INNER',
            "($query)",
            "$joinname.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            $dependencies
        );

        if ($parameters) {
            $this->globalrestrictionparams = array_merge($this->globalrestrictionparams, $parameters);
        }

        return true;
    }

    /**
     * Get global restriction join SQL to the report. All parameters will be inline.
     *
     * @param string $join Name of the join that provides the 'user id' field
     * @param string $field Name of user id field to join on
     * @return string
     */
    protected function get_global_report_restriction_join($join, $field) {
        // First ensure that global report restrictions can be used with this source.
        if (!$this->can_global_report_restrictions_be_used()) {
            return  '';
        }

        list($query, $parameters) = $this->globalrestrictionset->get_join_query();

        if (empty($query)) {
            return '';
        }

        if ($parameters) {
            $this->globalrestrictionparams = array_merge($this->globalrestrictionparams, $parameters);
        }

        static $counter = 0;
        $counter++;
        $joinname = 'globalinlinerestrjoin_' . $counter;

        $joinsql = " INNER JOIN ($query) $joinname ON ($joinname.id = $join.$field) ";
        return $joinsql;
    }

    public function rb_filter_recertifydatetype() {
        return array(
            CERTIFRECERT_COMPLETION => get_string('editdetailsrccmpl', 'totara_certification'),
            CERTIFRECERT_EXPIRY => get_string('editdetailsrcexp', 'totara_certification'),
            CERTIFRECERT_FIXED => get_string('editdetailsrcfixed', 'totara_certification')
        );
    }

    /**
     * Converts a list to an array given a list and a separator
     * duplicate values are ignored
     *
     * Example;
     * list_to_array('some-thing-some', '-'); =>
     * array('some' => 'some', 'thing' => 'thing');
     *
     * @param string $list List of items
     * @param string $sep Symbol or string that separates list items
     * @return array $result array of list items
     */
    function list_to_array($list, $sep) {
        $base = explode($sep, $list);
        return array_combine($base, $base);
    }

    /**
     * Dynamically add all customfields to columns
     * It uses additional suffix 'all' for column names generation . This means, that if some customfield column was generated using
     * the same suffix it will be shadowed by this method.
     * @param rb_column_option $columnoption should have public string property "type" which value is the type of customfields to show
     * @param bool $hidden should all these columns be hidden
     * @return array
     */
    public function rb_cols_generator_allcustomfields(rb_column_option $columnoption, $hidden) {
        $result = array();
        $columnoptions = array();

        // add_custom_fields_for requires only one join.
        if (!empty($columnoption->joins) && !is_string($columnoption->joins)) {
            throw new coding_exception('allcustomfields column generator requires none or only one join as string');
        }

        $join = empty($columnoption->joins) ? 'base' : $columnoption->joins;

        $this->add_totara_customfield_component($columnoption->type, $join, $columnoption->field, $this->joinlist,
                                                $columnoptions, $this->filteroptions, 'all', true);
        foreach ($columnoptions as $option) {
            $result[] = new rb_column(
                    $option->type,
                    $option->value,
                    $option->name,
                    $option->field,
                    (array)$option
            );
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function define_columnoptions() {
        // NOTE: override in source if used.
        return array();
    }

    /**
     * @return array
     */
    protected function define_filteroptions() {
        // NOTE: override in source if used.
        return array();
    }

    /**
     * @return array
     */
    protected function define_defaultcolumns() {
        // NOTE: override in source if used.
        return array();
    }

    /**
     * @return array
     */
    protected function define_defaultfilters() {
        // NOTE: override in source if used.
        return array();
    }

    /**
     * @return array
     */
    protected function define_contentoptions() {
        // NOTE: override in source if used.
        return array();
    }

    /**
     * @return array
     */
    protected function define_paramoptions() {
        // NOTE: override in source if used.
        return array();
    }

    /**
     * @return array
     */
    protected function define_requiredcolumns() {
        // NOTE: override in source if used.
        return array();
    }

    /**
     * Called after parameters have been read, allows the source to configure itself,
     * such as source title, additional tables, column definitions, etc.
     *
     * If post_params fails it needs to set redirect.
     *
     * @param reportbuilder $report
     */
    public function post_params(reportbuilder $report) {
        // NOTE: override in source if required.
    }

    /**
     * This method is called at the very end of reportbuilder class constructor
     * right before marking it ready.
     *
     * This method allows sources to add extra restrictions by calling
     * the following method on the $report object:
     *  {@link $report->set_post_config_restrictions()}    Extra WHERE clause
     *
     * If post_config fails it needs to set redirect.
     *
     * NOTE: do NOT modify the list of columns here.
     *
     * @param reportbuilder $report
     */
    public function post_config(reportbuilder $report) {
        // NOTE: override in source if required.
    }

    /**
     * Returns an array of js objects that need to be included with this report.
     *
     * @return array(object)
     */
    public function get_required_jss() {
        // NOTE: override in source if required.
        return array();
    }

    protected function get_advanced_aggregation_classes($type) {
        global $CFG;

        $classes = array();

        foreach (scandir("{$CFG->dirroot}/totara/reportbuilder/classes/rb/{$type}") as $filename) {
            if (substr($filename, -4) !== '.php') {
                continue;
            }
            if ($filename === 'base.php') {
                continue;
            }
            $name = str_replace('.php', '', $filename);
            $classname = "\\totara_reportbuilder\\rb\\{$type}\\$name";
            if (!class_exists($classname)) {
                debugging("Invalid aggregation class $name found", DEBUG_DEVELOPER);
                continue;
            }
            $classes[$name] = $classname;
        }

        return $classes;
    }

    /**
     * Get list of allowed advanced options for each column option.
     *
     * @return array of group select column values that are grouped
     */
    public function get_allowed_advanced_column_options() {
        $allowed = array();

        foreach ($this->columnoptions as $option) {
            $key = $option->type . '-' . $option->value;
            $allowed[$key] = array('');

            $classes = $this->get_advanced_aggregation_classes('transform');
            foreach ($classes as $name => $classname) {
                if ($classname::is_column_option_compatible($option)) {
                    $allowed[$key][] = 'transform_'.$name;
                }
            }

            $classes = $this->get_advanced_aggregation_classes('aggregate');
            foreach ($classes as $name => $classname) {
                if ($classname::is_column_option_compatible($option)) {
                    $allowed[$key][] = 'aggregate_'.$name;
                }
            }
        }
        return $allowed;
    }

    /**
     * Get list of deprecated columns.
     *
     * @return array of column options that are deprecated
     */
    public function get_deprecated_column_options() {
        $deprecated = array();
        foreach ($this->columnoptions as $option) {
            if ($option->deprecated) {
                $deprecated[$option->type . '-' . $option->value] = true;
            }
        }
        return $deprecated;
    }

    /**
     * Returns list of advanced aggregation/transformation options.
     *
     * @return array nested array suitable for groupselect forms element
     */
    public function get_all_advanced_column_options() {
        $advoptions = array();
        $advoptions[get_string('none')][''] = '-';
        foreach (\totara_reportbuilder\rb\transform\base::get_options() as $key => $options) {
            $advoptions[$key] = array();
            foreach ($options as $optionkey => $value) {
                $advoptions[$key]['transform_' . $optionkey] = $value;
            }
        }
        foreach (\totara_reportbuilder\rb\aggregate\base::get_options() as $key => $options) {
            $advoptions[$key] = array();
            foreach ($options as $optionkey => $value) {
                $advoptions[$key]['aggregate_' . $optionkey] = $value;
            }
        }
        return $advoptions;
    }

    /**
     * Set up necessary $PAGE stuff for columns.php page.
     */
    public function columns_page_requires() {
        \totara_reportbuilder\rb\aggregate\base::require_column_heading_strings();
        \totara_reportbuilder\rb\transform\base::require_column_heading_strings();
    }

    /**
     * Allows report source to override page header in reportbuilder exports.
     *
     * @param reportbuilder $report
     * @param string $format 'html', 'text', 'excel', 'ods', 'csv' or 'pdf'
     * @return mixed|null must be possible to cast to string[][]
     */
    public function get_custom_export_header(reportbuilder $report, $format) {
        // NOTE: override in source if required.
        return null;
    }

    /**
     * Get the uniquedelimiter.
     *
     * @return string
     */
    public function get_uniquedelimiter() {
        return $this->uniquedelimiter;
    }

    /**
     * Inject column_test data into database.
     *
     * @codeCoverageIgnore
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
       if (!PHPUNIT_TEST) {
           throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
       }
       // Nothing to do by default.
    }

    /**
     * Returns expected result for column_test.
     *
     * @codeCoverageIgnore
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        return 1;
    }

}
