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
 * @author Ryan Lafferty <ryanl@learningpool.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_goal_custom extends rb_base_source {
    public $shortname;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = "(SELECT g.id, g.fullname AS name, g.description, gua.userid,
                    'company' AS personalcompany, '' as context, COALESCE(t.fullname, 'notype') AS typename,
                    g.targetdate, gr.scalevalueid
                FROM {goal} g JOIN {goal_user_assignment} gua ON g.id = gua.goalid
                LEFT JOIN {goal_type} t ON t.id = g.typeid
                JOIN {goal_scale_assignments} gsa ON g.frameworkid = gsa.frameworkid
                JOIN {goal_record} gr ON gua.userid=gr.userid AND g.id = gr.goalid
                UNION
                SELECT gp.id, gp.name, gp.description, gp.userid,
                    'personal' AS personalcompany, 'context_user' AS context, COALESCE(ut.fullname, 'notype') AS typename,
                    gp.targetdate, gp.scalevalueid
                FROM {goal_personal} gp
                LEFT JOIN {goal_user_type} ut ON ut.id = gp.typeid
                WHERE deleted = 0)";
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->embeddedparams = $this->define_embeddedparams();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_goal_custom');
        $this->shortname = 'goal_custom';
        $this->cacheable = false;
        $this->usedcomponents[] = 'totara_hierarchy';

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
        return !totara_feature_visible('goals');
    }

    protected function define_joinlist() {
        $joinlist = array(
            // This join is required to keep the joining of company personal goal custom fields happy.
            new rb_join(
                'goal',
                'LEFT',
                '{goal}',
                'base.id = goal.id AND personalcompany = \'company\'',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),

            // This join is required to keep the joining of personal goal custom fields happy.
            new rb_join(
                'goal_personal',
                'LEFT',
                '{goal_personal}',
                'base.id = goal_personal.id AND personalcompany = \'personal\'',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'buser',
                'INNER',
                '{user}',
                'base.userid = buser.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'user_type',
                'LEFT',
                '{goal_user_type}',
                'base.typeid = user_type.id AND personalcompany = \'personal\'',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'goal_type',
                'LEFT',
                '{goal_type}',
                'base.typeid = goal_type.id AND personalcompany = \'company\'',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'goal_scale_values',
                'LEFT',
                '{goal_scale_values}',
                'base.scalevalueid = goal_scale_values.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        );
        $this->add_core_user_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    public function post_params(reportbuilder $report) {
        global $DB;

        $custompersonalgoals = $DB->get_records('goal_user_info_field', array('hidden' => 0));

        foreach ($custompersonalgoals as $custompersonalgoal) {
            $this->joinlist[] =
                new rb_join(
                    "goal_user_goalrecord" . $custompersonalgoal->id,
                    "LEFT",
                    "(SELECT *
                        FROM {goal_user_info_data}
                       WHERE fieldid = {$custompersonalgoal->id}
                        )",
                    "goal_user_goalrecord" . $custompersonalgoal->id . ".goal_userid = base.id AND personalcompany='personal' "
                );
        }

        $customcompanygoals = $DB->get_records('goal_type_info_field', array('hidden' => 0));

        foreach ($customcompanygoals as $customcompanygoal) {
            $this->joinlist[] =
                new rb_join(
                    "goal_type_goalrecord" . $customcompanygoal->id,
                    "LEFT",
                    "(SELECT *
                        FROM {goal_type_info_data}
                       WHERE fieldid = {$customcompanygoal->id}
                        )",
                    "goal_type_goalrecord" . $customcompanygoal->id . ".goalid = base.id AND personalcompany='company' "
                );
        }
    }

    protected function define_columnoptions() {
        $columnoptions = array(
            new rb_column_option(
                'goal',
                'goalname',
                get_string('goalname', 'rb_source_goal_custom'),
                'base.name',
                array('displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'goal',
                'goaldescription',
                get_string('goaldescription', 'rb_source_goal_custom'),
                'base.description',
                array('displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'component' => '\'totara_hierarchy\'',
                        'filearea' => '\'goal\'',
                        'context' => 'base.context',
                        'fileid' => 'base.id',
                        'recordid' => 'base.userid'
                    ),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'goal',
                'personalcompany',
                get_string('personalcompany', 'rb_source_goal_custom'),
                'base.personalcompany',
                array(
                    'displayfunc' => 'goal_personal_company'
                )
            ),
            new rb_column_option(
                'goal',
                'typename',
                get_string('typename', 'rb_source_goal_custom'),
                'base.typename',
                array(
                    'displayfunc' => 'goal_type_name'
                )
            ),
            new rb_column_option(
                'goal',
                'allpersonalgoalcustomfields',
                get_string('allpersonalgoalcustomfields', 'rb_source_goal_custom'),
                'allpersonalgoalcustomfields_',
                array(
                    'columngenerator' => 'allpersonalgoalcustomfields'
                )
            ),
            new rb_column_option(
                'goal',
                'allcompanygoalcustomfields',
                get_string('allcompanygoalcustomfields', 'rb_source_goal_custom'),
                'allcompanygoalcustomfields_',
                array(
                    'columngenerator' => 'allcompanygoalcustomfields'
                )
            ),
            new rb_column_option(
                'goal',
                'targetdate',
                get_string('targetdate', 'rb_source_goal_custom'),
                'base.targetdate',
                array(
                    'displayfunc' => 'nice_date'
                )
            ),
            new rb_column_option(
                'goal',
                'scalevaluename',
                get_string('status', 'rb_source_goal_custom'),
                'goal_scale_values.name',
                array(
                    'joins' => 'goal_scale_values',
                    'displayfunc' => 'format_string'
                )
            ),
        );

        $this->add_core_user_columns($columnoptions);

        return $columnoptions;
    }


    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'buser');

        return $contentoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'goal',
                'goalname',
                get_string('goalname', 'rb_source_goal_custom'),
                'text'
            ),
            new rb_filter_option(
                'goal',
                'goaldescription',
                get_string('goaldescription', 'rb_source_goal_custom'),
                'text'
            ),
            new rb_filter_option(
                'goal',
                'personalcompany',
                get_string('personalcompany', 'rb_source_goal_custom'),
                'select',
                array(
                    'selectfunc' => 'personal_company'
                )
            ),
            new rb_filter_option(
                'goal',
                'typename',
                get_string('typename', 'rb_source_goal_custom'),
                'multicheck',
                array(
                    'selectfunc' => 'goal_type'
                )
            )
        );
        $this->add_core_user_filters($filteroptions);
        return $filteroptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.userid'
            )
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
                'type' => 'goal',
                'value' => 'goalname'
            ),
            array(
                'type' => 'goal',
                'value' => 'personalcompany'
            ),
            array(
                'type' => 'goal',
                'value' => 'typename'
            )

        );

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array();

        return $defaultfilters;
    }

    protected function define_embeddedparams() {
        $embeddedparams = array();

        return $embeddedparams;
    }

    /**
     * Display the user type name
     *
     * @deprecated Since Totara 12.0
     * @param $type
     * @param $row
     * @return string
     */
    public function rb_display_user_type_name($type, $row) {
        debugging('rb_source_goal_custom::rb_display_user_type_name has been deprecated since Totara 12.0. Please use totara_hierarchy\rb\display\goal_type_name::display', DEBUG_DEVELOPER);
        if ($type === 'notype') {
            return get_string('notype', 'rb_source_goal_custom');
        } else {
            return $type;
        }
    }

    /**
     * Display if personal or company goal
     *
     * @deprecated Since Totara 12.0
     * @param $type
     * @param $row
     * @return string
     */
    public function rb_display_personal_company($type, $row) {
        debugging('rb_source_goal_custom::rb_display_personal_company has been deprecated since Totara 12.0. Please use totara_hierarchy\rb\display\goal_personal_company::display', DEBUG_DEVELOPER);
        if ($type === 'company') {
            return get_string('company', 'rb_source_goal_custom');
        } else {
            return get_string('personal', 'rb_source_goal_custom');
        }
    }

    public function rb_filter_personal_company() {
        return array('company' => get_string('company', 'rb_source_goal_custom'),
                     'personal' => get_string('personal', 'rb_source_goal_custom')
        );
    }

    public function rb_filter_goal_type() {
        global $DB;

        $sql = "SELECT t.fullname AS typename FROM {goal_type} t
                UNION
                SELECT ut.fullname AS typename FROM {goal_user_type} ut";

        $goaltypes = $DB->get_fieldset_sql($sql);
        $goalarray = array();

        foreach ($goaltypes as $goaltype) {
            $goalarray[$goaltype] = $goaltype;
        }
        $goalarray["notype"] = get_string('notype', 'rb_source_goal_custom');

        return $goalarray;
    }

    public function rb_cols_generator_allpersonalgoalcustomfields($columnoption, $hidden) {
        global $DB;

        $custompersonalgoals = $DB->get_records('goal_user_info_field', array('hidden' => 0));

        $results = array();
        foreach ($custompersonalgoals as $custompersonalgoal) {

            $results[] = $this->create_column($custompersonalgoal, $hidden, 'goal_user');
        }
        return $results;
    }

    public function rb_cols_generator_allcompanygoalcustomfields($columnoption, $hidden) {
        global $DB;

        $customcompanygoals = $DB->get_records('goal_type_info_field', array('hidden' => 0));

        $results = array();
        foreach ($customcompanygoals as $customcompanygoal) {
            $results[] = $this->create_column($customcompanygoal, $hidden, 'goal_type');
        }
        return $results;
    }

    public function create_column($customgoal, $hidden, $type) {
        $displayfunc = '';
        $multi = '';
        $extrafields = '';
        $outputformat = '';

        switch($customgoal->datatype) {
            case 'checkbox':
                $displayfunc = "yes_or_no";
                break;
            case 'multiselect':
                $multi = "_text";
                $displayfunc = "customfield_multiselect_text";
                $extrafields = array(
                    "{$type}_all_custom_field_{$customgoal->id}_text_json" => "{$type}_goalrecord{$customgoal->id}.data"
                );
                break;
            case 'datetime':
                if ($customgoal->param3) {
                    $displayfunc = 'nice_datetime';
                } else {
                    $displayfunc = 'nice_date';
                }
                break;
            case 'file':
                $displayfunc = 'customfield_file';
                $extrafields = array(
                    "{$type}_all_custom_field_{$customgoal->id}_itemid" => "{$type}_goalrecord{$customgoal->id}.id"
                );
                break;
            case 'textarea':
                $displayfunc = 'customfield_textarea';
                $extrafields = array(
                    "{$type}_all_custom_field_{$customgoal->id}_itemid" => "{$type}_goalrecord{$customgoal->id}.id"
                );
                break;
            case 'url':
                $displayfunc = 'customfield_url';
                break;
            case 'location':
                $displayfunc = 'customfield_location';
                $outputformat = 'text';
                break;
        }

        return new rb_column(
            $type,
            'all_custom_field_'.$customgoal->id . $multi,
            $customgoal->fullname,
            $type . "_goalrecord" . $customgoal->id . ".data",
            array(
                'joins' => array($type . "_goalrecord" . $customgoal->id),
                'hidden' => $hidden,
                'displayfunc' => $displayfunc,
                'extrafields' => $extrafields,
                'outputformat' => $outputformat
            )
        );
    }
}
