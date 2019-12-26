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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_comp_status_history extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{comp_record_history}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_comp_status_history');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    public static function is_source_ignored() {
        return !totara_feature_visible('competencies');
    }

    protected function define_joinlist() {
        $joinlist = array(
            new rb_join(
                'competency',
                'LEFT',
                '{comp}',
                'competency.id = base.competencyid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'scalevalue',
                'LEFT',
                '{comp_scale_values}',
                'scalevalue.id = base.proficiency',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'usermodified',
                'LEFT',
                '{user}',
                'usermodified.id = base.usermodified',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'completion_organisation',
                'LEFT',
                '{org}',
                'completion_organisation.id = user.organisationid', // TODO - remove this.
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'completion_position',
                'LEFT',
                '{pos}',
                'completion_position.id = user.positionid', // TODO - remove this.
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $usednamefields = totara_get_all_user_name_fields_join('usermodified', null, true);
        $allnamefields = totara_get_all_user_name_fields_join('usermodified');

        $columnoptions = array(
            new rb_column_option(
                'competency',
                'competencyid',
                get_string('compidcolumn', 'rb_source_comp_status_history'),
                'base.competencyid',
                array('selectable' => false)
            ),
            new rb_column_option(
                'history',
                'scalevalueid',
                get_string('compscalevalueidcolumn', 'rb_source_comp_status_history'),
                'base.proficiency',
                array('selectable' => false)
            ),
            new rb_column_option(
                'competency',
                'fullname',
                get_string('compnamecolumn', 'rb_source_comp_status_history'),
                'competency.fullname',
                array('defaultheading' => get_string('compnameheading', 'rb_source_comp_status_history'),
                      'joins' => 'competency',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'history',
                'scalevalue',
                get_string('compscalevaluecolumn', 'rb_source_comp_status_history'),
                'scalevalue.name',
                array('joins' => 'scalevalue',
                      'defaultheading' => get_string('compscalevalueheading', 'rb_source_comp_status_history'),
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'history',
                'proficientdate',
                get_string('proficientdate', 'rb_source_competency_evidence'),
                'base.timeproficient',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'history',
                'timemodified',
                get_string('comptimemodifiedcolumn', 'rb_source_comp_status_history'),
                'base.timemodified',
                array('defaultheading' => get_string('comptimemodifiedheading', 'rb_source_comp_status_history'),
                      'displayfunc' => 'nice_datetime',
                      'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'history',
                'usermodifiednamelink',
                get_string('compusermodifiedcolumn', 'rb_source_comp_status_history'),
                $DB->sql_concat_join("' '", $usednamefields),
                array('defaultheading' => get_string('compusermodifiedheading', 'rb_source_comp_status_history'),
                      'joins' => 'usermodified',
                      'displayfunc' => 'user_link',
                      'extrafields' => array_merge(array('id' => 'usermodified.id', 'deleted' => 'usermodified.deleted'),
                                                   $allnamefields)
                )
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'competency',
                'competencyid',
                get_string('compnamecolumn', 'rb_source_comp_status_history'),
                'hierarchy_multi',
                array(
                    'hierarchytype' => 'comp'
                )
            ),
            new rb_filter_option(
                'history',
                'timemodified',
                get_string('comptimemodifiedcolumn', 'rb_source_comp_status_history'),
                'date',
                array('includetime' => true)
            ),
            new rb_filter_option(
                'history',
                'proficientdate',
                get_string('proficientdate', 'rb_source_competency_evidence'),
                'date',
                array()
            ),

        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

        return $filteroptions;
    }


    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'completed_org',
            get_string('completedorg', 'rb_source_competency_evidence'),
            'completion_organisation.path',
            'completion_organisation'
        );

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completiondate', 'rb_source_competency_evidence'),
            'base.timemodified'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.userid'
            ),
            new rb_param_option(
                'competencyid',
                'base.competencyid'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink'
            ),
            array(
                'type' => 'competency',
                'value' => 'fullname'
            ),
            array(
                'type' => 'history',
                'value' => 'scalevalue'
            ),
            array(
                'type' => 'history',
                'value' => 'timemodified'
            ),
            array(
                'type' => 'history',
                'value' => 'usermodifiednamelink'
            )
        );
        return $defaultcolumns;
    }


}
