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

require_once('rb_source_goal_details.php');

class rb_source_goal_status_history extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    public $shortname;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '(SELECT gih.id, gih.scope, goal.id AS itemid, goal.fullname, gr.userid, gih.scalevalueid,
                               gih.timemodified, gih.usermodified,
                               ' . $DB->sql_concat('goal.id', "'_'", 'gih.scope') . ' AS itemandscope
                          FROM {goal_item_history} gih
                          JOIN {goal_record} gr ON gih.itemid = gr.id AND gih.scope = ' . goal::SCOPE_COMPANY . '
                          JOIN {goal} goal ON gr.goalid = goal.id
                         UNION
                        SELECT gih.id, gih.scope, gp.id AS itemid, gp.name AS fullname, gp.userid, gih.scalevalueid,
                               gih.timemodified, gih.usermodified,
                               ' . $DB->sql_concat('gp.id', "'_'", 'gih.scope') . ' AS itemandscope
                          FROM {goal_item_history} gih
                          JOIN {goal_personal} gp ON gih.itemid = gp.id AND gih.scope = ' . goal::SCOPE_PERSONAL . ')';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_goal_status_history');
        $this->shortname = 'goal_status_history';
        $this->usedcomponents[] = 'totara_hierarchy';

        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('goals');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {
        $joinlist = array(
            new rb_join(
                'scalevalue',
                'LEFT',
                '{goal_scale_values}',
                'scalevalue.id = base.scalevalueid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'usermodified',
                'LEFT',
                '{user}',
                'usermodified.id = base.usermodified',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }


    protected function define_columnoptions() {
        global $DB;
        $usernamefields = totara_get_all_user_name_fields_join('usermodified');

        $columnoptions = array(
            new rb_column_option(
                'item',
                'itemandscope',
                '',
                'base.itemandscope',
                array('selectable' => false)
            ),
            new rb_column_option(
                'item',
                'scalevalueid',
                '',
                'base.scalevalueid',
                array('selectable' => false)
            ),
            new rb_column_option(
                'item',
                'fullname',
                get_string('goalnamecolumn', 'rb_source_goal_status_history'),
                'base.fullname',
                array('defaultheading' => get_string('goalnameheading', 'rb_source_goal_status_history'),
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'capability' => 'totara/hierarchy:viewallgoals',
                      'displayfunc' => 'format_string'
                    )
            ),
            new rb_column_option(
                'item',
                'scope',
                get_string('goalscopecolumn', 'rb_source_goal_status_history'),
                'base.scope',
                array('defaultheading' => get_string('goalscopeheading', 'rb_source_goal_status_history'),
                      'displayfunc' => 'goal_scope')
            ),
            new rb_column_option(
                'history',
                'scalevalue',
                get_string('goalscalevaluecolumn', 'rb_source_goal_status_history'),
                'scalevalue.name',
                array('joins' => 'scalevalue',
                      'defaultheading' => get_string('goalscalevalueheading', 'rb_source_goal_status_history'),
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'history',
                'timemodified',
                get_string('goaltimemodifiedcolumn', 'rb_source_goal_status_history'),
                'base.timemodified',
                array('defaultheading' => get_string('goaltimemodifiedheading', 'rb_source_goal_status_history'),
                      'displayfunc' => 'nice_datetime',
                      'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'history',
                'usermodifiednamelink',
                get_string('goalusermodifiedcolumn', 'rb_source_goal_status_history'),
                $DB->sql_concat_join("' '", $usernamefields),
                array('defaultheading' => get_string('goalusermodifiedheading', 'rb_source_goal_status_history'),
                      'joins' => 'usermodified',
                      'displayfunc' => 'user_link',
                      'extrafields' => array_merge(array('id' => 'usermodified.id'), $usernamefields),
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }


    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'item',
                'scope',
                get_string('goalscopecolumn', 'rb_source_goal_status_history'),
                'select',
                array('selectfunc' => 'scope')
            ),
            new rb_filter_option(
                'item',
                'itemandscope',
                get_string('goalcompanynamecolumn', 'rb_source_goal_status_history'),
                'select',
                array('selectfunc' => 'company_goal')
            ),
            new rb_filter_option(
                'item',
                'scalevalueid',
                get_string('goalscalevaluecolumn', 'rb_source_goal_status_history'),
                'select',
                array('selectfunc' => 'scalevalue')
            ),
            new rb_filter_option(
                'history',
                'timemodified',
                get_string('goaltimemodifiedcolumn', 'rb_source_goal_status_history'),
                'date',
                array('includetime' => true)
            )
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

        return $filteroptions;
    }


    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'itemandscope',
                'base.itemandscope',
                null,
                'string'
            ),
            new rb_param_option(
                'userid',
                'base.userid'
            )
        );

        return $paramoptions;
    }


    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('modifieddate', 'rb_source_goal_status_history'),
            'base.timemodified'
        );

        return $contentoptions;
    }


    /**
     * Filter scope.
     *
     * @return array
     */
    public function rb_filter_scope() {
        $scopes = array(goal::SCOPE_COMPANY => get_string('goalscopecompany', 'rb_source_goal_status_history'),
                        goal::SCOPE_PERSONAL => get_string('goalscopepersonal', 'rb_source_goal_status_history'));
        return $scopes;
    }


    /**
     * Filter item.
     *
     * @return array
     */
    public function rb_filter_company_goal() {
        global $DB;

        $goals = array();

        $sql = 'SELECT goal.id, goal.fullname
                  FROM {goal} goal';

        $goallist = $DB->get_records_sql($sql);

        foreach ($goallist as $goal) {
            $goals[$goal->id . '_' . goal::SCOPE_COMPANY] = $goal->fullname;
        }

        return $goals;
    }

    /**
     * Filter scale value (status).
     *
     * @return array
     */
    public function rb_filter_scalevalue() {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/totara/hierarchy/prefix/goal/lib.php");

        $scalevalues = array();

        $sql = 'SELECT gsv.*, gs.name AS scalename
                  FROM {goal_scale_values} gsv
                  JOIN {goal_scale} gs
                    ON gsv.scaleid = gs.id
                 ORDER BY gs.name, gsv.sortorder';

        $goalscalevalues = $DB->get_records_sql($sql);

        foreach ($goalscalevalues as $goalscalevalue) {
            $scalevalues[$goalscalevalue->id] = format_string($goalscalevalue->scalename) . ': ' . format_string($goalscalevalue->name);
        }

        return $scalevalues;
    }

    /**
     * Display goal scope
     *
     * @deprecated Since Totara 12.0
     * @param $scope
     * @return mixed
     */
    public function rb_display_scope($scope) {
        debugging('rb_source_goal_status_history::rb_display_scope has been deprecated since Totara 12.0. Use totara_hierarchy\rb\display\goal_scope::display', DEBUG_DEVELOPER);
        $scopes = $this->rb_filter_scope();

        return $scopes[$scope];
    }

    /**
     * Display users fullname with link
     * @deprecated Since Totara 12.0
     * @param $user
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_fullname_link_user($user, $row, $isexport = false) {
        debugging('rb_source_goal_status_history::rb_display_fullname_link_user has been deprecated since Totara 12.0. Use totara_reportbuilder\rb\display\user_link::display', DEBUG_DEVELOPER);
        $user = fullname($row);
        return $this->rb_display_link_user($user, $row, $isexport);
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink'
            ),
            array(
                'type' => 'item',
                'value' => 'fullname'
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
