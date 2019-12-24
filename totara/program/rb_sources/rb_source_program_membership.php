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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/program/lib.php');

class rb_source_program_membership extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = $this->define_base();
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_program_membership');

        parent::__construct();
    }

    private function define_base() {
        global $DB;

        $uniqueid = $DB->sql_concat_join("','", array('userid', 'programid'));

        return "(SELECT " . $uniqueid . " AS id, userid, programid
                   FROM (SELECT pc.userid, pc.programid
                           FROM {prog_completion} pc
                          WHERE pc.coursesetid = 0
                          UNION
                         SELECT pch.userid, pch.programid
                           FROM {prog_completion_history} pch
                          WHERE pch.coursesetid = 0) pcall)";
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {
        $joinlist = array();

        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        $this->add_program_table_to_joinlist($joinlist, 'base', 'programid');

        $joinlist[] = new rb_join(
            'prog_completion',
            'LEFT',
            '{prog_completion}',
            "prog_completion.userid = base.userid AND
             prog_completion.programid = base.programid AND
             prog_completion.coursesetid = 0",
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        // This join is required to keep the joining of program custom fields happy.
        $joinlist[] =  new rb_join(
            'prog',
            'LEFT',
            '{prog}',
            'prog.id = base.programid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $this->add_user_fields_to_columns($columnoptions);
        $this->add_program_fields_to_columns($columnoptions);

        $columnoptions[] = new rb_column_option(
            'progmembership',
            'status',
            get_string('status', 'rb_source_program_membership'),
            'prog_completion.status',
            array(
                'joins' => 'prog_completion',
                'displayfunc' => 'prog_status',
            )
        );
        $columnoptions[] = new rb_column_option(
            'progmembership',
            'isassigned',
            get_string('isassigned', 'rb_source_program_membership'),
            'CASE WHEN prog_completion.id IS NOT NULL THEN 1 ELSE 0 END',
            array(
                'joins' => 'prog_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
            )
        );
        $columnoptions[] = new rb_column_option(
            'progmembership',
            'editcompletion',
            get_string('editcompletion', 'rb_source_program_membership'),
            'base.id',
            array(
                'displayfunc' => 'edit_completion',
                'extrafields' => array(
                    'userid' => 'base.userid',
                    'progid' => 'base.programid',
                ),
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_user_fields_to_filters($filteroptions);
        $this->add_program_fields_to_filters($filteroptions);

        $filteroptions[] = new rb_filter_option(
            'progmembership',
            'status',
            get_string('status', 'rb_source_program_membership'),
            'select',
            array(
                'selectfunc' => 'status',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        return $filteroptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'programid',
                'base.programid',
                'base'
            ),
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'progmembership',
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
                'type' => 'progmembership',
                'value' => 'status',
                'advanced' => 0,
            ),
        );
        return $defaultfilters;
    }

    public function rb_filter_status() {
        $out = array();
        $out[STATUS_PROGRAM_INCOMPLETE] = get_string('incomplete', 'totara_program');
        $out[STATUS_PROGRAM_COMPLETE] = get_string('complete', 'totara_program');

        return $out;
    }

    public function rb_display_prog_status($status, $row) {
        switch ($status) {
            case STATUS_PROGRAM_INCOMPLETE:
                return get_string('incomplete', 'totara_program');
            case STATUS_PROGRAM_COMPLETE:
                return get_string('complete', 'totara_program');
            default:
                return get_string('error:invalidstatus', 'totara_program');

        }
    }

    public function rb_display_edit_completion($id, $row, $isexport) {
        // Ignores $id == prog_completion id, because the user might have been unassigned and only history records exist.
        if ($isexport) {
            return get_string('editcompletion', 'rb_source_program_membership');
        }

        $url = new moodle_url('/totara/program/edit_completion.php',
            array('id' => $row->progid, 'userid' => $row->userid));
        return html_writer::link($url, get_string('editcompletion', 'rb_source_program_membership'));
    }

    /**
     * Returns expected result for column_test.
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        return 2;
    }
}
