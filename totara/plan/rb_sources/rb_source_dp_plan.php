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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/plan/lib.php');

/**
 * A report builder source for development plans
 */
class rb_source_dp_plan extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    /**
     * Constructor
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{dp_plan}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_plan');
        $this->usedcomponents[] = 'totara_plan';
        parent::__construct();
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('learningplans');
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

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = array();

        $joinlist[] = new rb_join(
                'template',
                'LEFT',
                '{dp_template}',
                'base.templateid = template.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array()
        );

        $this->add_core_user_tables($joinlist, 'base','userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
                'plan',
                'name',
                get_string('planname', 'rb_source_dp_plan'),
                'base.name',
                array(
                    'defaultheading' => get_string('plan', 'rb_source_dp_plan'),
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'planlink',
                get_string('plannamelink', 'rb_source_dp_plan'),
                'base.name',
                array(
                    'defaultheading' => get_string('plan', 'rb_source_dp_plan'),
                    'displayfunc' => 'plan_link',
                    'extrafields' => array( 'plan_id' => 'base.id' )
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'description',
                get_string('description', 'rb_source_dp_plan'),
                'base.description',
                array(
                    'defaultheading' => get_string('description', 'rb_source_dp_plan'),
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                            'filearea' => '\'dp_plan\'',
                            'component' => '\'totara_plan\'',
                            'fileid' => 'base.id'
                    ),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'startdate',
                get_string('planstartdate', 'rb_source_dp_plan'),
                'base.startdate',
                array(
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'enddate',
                get_string('planenddate', 'rb_source_dp_plan'),
                'base.enddate',
                array(
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'timecompleted',
                get_string('timecompleted', 'rb_source_dp_plan'),
                'base.timecompleted',
                array(
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'status',
                get_string('planstatus', 'rb_source_dp_plan'),
                'base.status',
                array(
                    'displayfunc' => 'plan_status'
                )
        );

        $columnoptions[] = new rb_column_option(
                'template',
                'name',
                get_string('templatename', 'rb_source_dp_plan'),
                'template.fullname',
                array(
                    'defaultheading' => get_string('plantemplate', 'rb_source_dp_plan'),
                    'joins' => 'template',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
        );
        $columnoptions[] = new rb_column_option(
                'template',
                'startdate',
                get_string('templatestartdate', 'rb_source_dp_plan'),
                'template.startdate',
                array(
                    'joins' => 'template',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'template',
                'enddate',
                get_string('templateenddate', 'rb_source_dp_plan'),
                'template.enddate',
                array(
                    'joins' => 'template',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
                'plan',
                'name',
                get_string('planname', 'rb_source_dp_plan'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'template',
                'name',
                get_string('templatename', 'rb_source_dp_plan'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'plan',
                'description',
                get_string('plandescription', 'rb_source_dp_plan'),
                'textarea'
        );

        $filteroptions[] = new rb_filter_option(
                'plan',
                'startdate',
                get_string('planstartdate', 'rb_source_dp_plan'),
                'date'
        );

        $filteroptions[] = new rb_filter_option(
                'plan',
                'enddate',
                get_string('planenddate', 'rb_source_dp_plan'),
                'date'
        );

        $filteroptions[] = new rb_filter_option(
                'plan',
                'timecompleted',
                get_string('plancompletiondate', 'rb_source_dp_plan'),
                'date'
        );

        $filteroptions[] = new rb_filter_option(
                'plan',
                'status',
                get_string('planstatus', 'rb_source_dp_plan'),
                'select',
                array(
                    'selectfunc' => 'plan_status',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

        return $filteroptions;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        global $CFG;

        $paramoptions = array();
        require_once($CFG->dirroot.'/totara/plan/lib.php');

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelinkicon',
            ),
            array(
                'type' => 'plan',
                'value' => 'planlink',
            ),
            array(
                'type' => 'template',
                'value' => 'name',
            ),
            array(
                'type' => 'plan',
                'value' => 'startdate',
            ),
            array(
                'type' => 'plan',
                'value' => 'enddate',
            ),
            array(
                'type' => 'plan',
                'value' => 'status',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                    'type' => 'user',
                    'value' => 'fullname',
                    'advanced' => 0,
                ),
            array(
                    'type' => 'plan',
                    'value' => 'name',
                    'advanced' => 0,
                ),
            array(
                    'type' => 'plan',
                    'value' => 'startdate',
                    'advanced' => 1,
                ),
            array(
                    'type' => 'plan',
                    'value' => 'enddate',
                    'advanced' => 1,
                ),
            array(
                    'type' => 'plan',
                    'value' => 'status',
                    'advanced' => 0,
                ),
            );

        return $defaultfilters;
    }
}
