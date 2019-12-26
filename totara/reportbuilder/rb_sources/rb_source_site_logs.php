<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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

defined('MOODLE_INTERNAL') || die();

class rb_source_site_logs extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'auser');

        $this->base = '{log}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_site_logs');
        $this->usedcomponents[] = 'tool_log';
        $this->usedcomponents[] = 'totara_cohort';

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {

        $joinlist = array(
            // no none standard joins
        );

        // include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'base', 'course');
        // requires the course join
        $this->add_core_course_category_tables($joinlist,
            'course', 'category');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'base', 'course');
        $this->add_totara_cohort_course_tables($joinlist, 'base', 'course');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $columnoptions = array(
            new rb_column_option(
                'log',
                'time',
                get_string('time', 'rb_source_site_logs'),
                'base.time',
                array('displayfunc' => 'nice_datetime', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'log',
                'ip',
                get_string('ip', 'rb_source_site_logs'),
                'base.ip',
                array('displayfunc' => 'ip_lookup_link')
            ),
            new rb_column_option(
                'log',
                'module',
                get_string('module', 'rb_source_site_logs'),
                'base.module',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'log',
                'cmid',
                get_string('cmid', 'rb_source_site_logs'),
                'base.cmid',
                array('displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'log',
                'action',
                get_string('action', 'rb_source_site_logs'),
                $DB->sql_concat('base.module', "' '", 'base.action'),
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'log',
                'actionlink',
                get_string('actionlink', 'rb_source_site_logs'),
                $DB->sql_concat('base.module', "' '", 'base.action'),
                array(
                    'displayfunc' => 'log_link_action',
                    'defaultheading' => get_string('action', 'rb_source_site_logs'),
                    'extrafields' => array('log_module' => 'base.module', 'log_url' => 'base.url')
                )
            ),
            new rb_column_option(
                'log',
                'url',
                get_string('url', 'rb_source_site_logs'),
                'base.url',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'log',
                'info',
                get_string('info', 'rb_source_site_logs'),
                'base.info',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
        );

        // include some standard columns
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_totara_cohort_course_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'log',     // type
                'action',  // value
                get_string('action', 'rb_source_site_logs'),  // label
                'text',    // filtertype
                array()    // options
            )
        );

        // include some standard filters
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('date', 'rb_source_site_logs'),
            'base.time'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',       // parameter name
                'base.userid',  // field
                null            // joins
            ),
            new rb_param_option(
                'courseid',
                'base.course'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'log',
                'value' => 'time',
            ),
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allpositionnames',
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allorganisationnames',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'log',
                'value' => 'ip',
            ),
            array(
                'type' => 'log',
                'value' => 'actionlink',
            ),
            array(
                'type' => 'log',
                'value' => 'info',
            ),
        );

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'log',
                'value' => 'action',
                'advanced' => 1,
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_category',
                'value' => 'path',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allpositions',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allorganisations',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }


    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            /*
            // array of rb_column objects, e.g:
            new rb_column(
                '',         // type
                '',         // value
                '',         // heading
                '',         // field
                array(),    // options
            )
            */
        );
        return $requiredcolumns;
    }

    //
    //
    // Source specific column display methods
    //
    //

    /**
     * Convert a site log action into a link to that page
     *
     * @deprecated Since Totara 12.0
     * @param $action
     * @param $row
     * @return string
     */
    function rb_display_link_action($action, $row) {
        debugging('rb_source_site_logs::rb_display_link_action has been deprecated since Totara 12.0. Use tool_log\rb\display\log_link_action::display', DEBUG_DEVELOPER);
        global $CFG;
        $url = $row->log_url;
        $module = $row->log_module;
        require_once($CFG->dirroot . '/course/lib.php');
        $logurl = make_log_url($module, $url);
        return html_writer::link(new moodle_url($logurl), $action);
    }

    /**
     * Convert IP address into a link to IP lookup page
     *
     * @deprecated Since Totara 12.0
     * @param $ip
     * @param $row
     * @return string
     */
    function rb_display_iplookup($ip, $row) {
        debugging('rb_source_site_logs::rb_display_iplookup has been deprecated since Totara 12.0. Use ip_lookup_link::display', DEBUG_DEVELOPER);
        global $CFG;
        if (!isset($ip) || $ip == '') {
            return '';
        }
        $params = array('id' => $ip);
        if (isset($row->user_id)) {
            $params['user'] = $row->user_id;
        }
        $url = new moodle_url('/iplookup/index.php', $params);
        return html_writer::link($url, $ip);
    }


    //
    //
    // Source specific filter display methods
    //
    //

    // add methods here with [name] matching filter option filterfunc
    //function rb_filter_[name]() {
        // should return an associative array
        // suitable for use in a select form element
    //}

} // end of rb_source_site_logs class

