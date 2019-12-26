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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage cohort
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page
}

global $CFG;
require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * A report builder source for a cohort's "learning items", which includes "enrolled items", i.e. courses & programs that
 * the cohort's members should be enrolled in
 */
class rb_source_cohort_associations extends rb_base_source {
    /**
     * Constructor
     * @global object $CFG
     */
    public function __construct() {
        $this->base = "(SELECT e.id, e.customint1 AS cohortid, e.courseid AS instanceid,
                c.fullname AS name, c.icon, " . COHORT_ASSN_ITEMTYPE_COURSE . " AS instancetype,
                0 AS duedate, 0 AS completionevent, 0 AS completioninstance
            FROM {enrol} e
            JOIN {course} c ON e.courseid = c.id
            WHERE e.enrol = 'cohort'
            UNION ALL
            SELECT pa.id, pa.assignmenttypeid AS cohortid, p.id AS instanceid,
                p.fullname AS name, p.icon, " . COHORT_ASSN_ITEMTYPE_PROGRAM . " AS instancetype,
                pa.completiontime AS duedate, pa.completionevent AS completionevent, pa.completioninstance AS completioninstance
            FROM {prog_assignment} pa
            JOIN {prog} p ON pa.programid = p.id
            WHERE pa.assignmenttype = " . ASSIGNTYPE_COHORT . " AND p.certifid IS NULL
            UNION ALL
            SELECT pa.id, pa.assignmenttypeid AS cohortid, p.id AS instanceid,
                p.fullname AS name, p.icon, " . COHORT_ASSN_ITEMTYPE_CERTIF . " AS instancetype,
                pa.completiontime AS duedate, pa.completionevent AS completionevent, pa.completioninstance AS completioninstance
            FROM {prog_assignment} pa
            JOIN {prog} p ON pa.programid = p.id
            WHERE pa.assignmenttype = " . ASSIGNTYPE_COHORT . " AND p.certifid IS NOT NULL)";
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_cohort_associations');
        $this->usedcomponents[] = 'totara_cohort';
        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $CFG
     * @return array
     */
    private function define_joinlist() {
        global $CFG;

        $joinlist = array();

        $joinlist[] = new rb_join(
            'cohort',
            'INNER',
            '{cohort}',
            'base.cohortid = cohort.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE
        );

        $joinlist[] = new rb_join(
            'associations',
            'LEFT',
            '{cohort_visibility}',
            'base.cohortid = associations.cohortid',
            REPORT_BUILDER_RELATION_MANY_TO_ONE
        );

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
            'associations',
            'name',
            get_string('associationname', 'totara_cohort'),
            'base.name',
            array('dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'type',
            get_string('associationtype', 'totara_cohort'),
            'base.instancetype',
            array('displayfunc'=>'cohort_association_type')
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'nameiconlink',
            get_string('associationnameiconlink', 'totara_cohort'),
            'base.name',
            array(
                'displayfunc'=>'cohort_association_name_icon_link',
                'extrafields'=>array(
                    'insid'=> 'base.instanceid',
                    'icon' => 'base.icon',
                    'type' => 'base.instancetype'
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'actionsenrolled',
            get_string('associationactionsenrolled', 'totara_cohort'),
            'base.id',
            array(
                'displayfunc' => 'cohort_association_actions_enrolled',
                'extrafields' => array('cohortid' => 'base.cohortid', 'type' => 'base.instancetype'),
                'nosort' => true
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'name',
            get_string('name', 'totara_cohort'),
            'cohort.name',
            array('joins' => 'cohort',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'idnumber',
            get_string('idnumber', 'totara_cohort'),
            'cohort.idnumber',
            array('joins' => 'cohort',
                  'displayfunc' => 'plaintext',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'programcompletionlink',
            get_string('assignmentduedate', 'totara_program'),
            'base.duedate',
            array(
                'displayfunc' => 'cohort_association_duedate',
                'extrafields' => [
                    'programid' => 'base.instanceid',
                    'completionevent' => 'base.completionevent',
                    'completioninstance' => 'base.completioninstance',
                    'type' => 'base.instancetype',
                    'cohortid' => 'base.cohortid'
                ]
            )
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'programviewduedateslink',
            get_string('actualduedate', 'totara_program'),
            'base.id',
            array(
                'displayfunc' => 'cohort_program_view_duedate_link',
                'extrafields' => array(
                    'type' => 'base.instancetype',
                    'programid' => 'base.instanceid'
                )
            )
        );

        return $columnoptions;
    }


    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        global $CFG;
        $filteroptions = array();
        $filteroptions[] = new rb_filter_option(
            'associations',
            'name',
            get_string('associationname', 'totara_cohort'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'associations',
            'type',
            get_string('associationtype', 'totara_cohort'),
            'select',
            array(
                'selectchoices' => array(
                    COHORT_ASSN_ITEMTYPE_COURSE => get_string('associationcoursesonly', 'totara_cohort'),
                    COHORT_ASSN_ITEMTYPE_PROGRAM  => get_string('associationprogramsonly', 'totara_cohort'),
                    COHORT_ASSN_ITEMTYPE_CERTIF  => get_string('associationcertificationsonly', 'totara_cohort'),
                ),
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'cohort',
            'name',
            get_string('name', 'totara_cohort'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'cohort',
            'idnumber',
            get_string('idnumber', 'totara_cohort'),
            'text'
        );
        return $filteroptions;
    }


    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'associations',
                'value' => 'name',
            ),
            array(
                'type' => 'associations',
                'value' => 'type',
            )
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array();
        $defaultfilters[] = array(
            'type' => 'associations',
            'value' => 'name',
            'advanced' => 0,
        );
        $defaultfilters[] = array(
            'type' => 'associations',
            'value' => 'type',
            'advanced' => 0,
        );

        return $defaultfilters;
    }
    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();
        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array();
        $paramoptions[] = new rb_param_option(
            'cohortid',
            'base.cohortid'
        );
        $paramoptions[] = new rb_param_option(
            'type',
            'base.instancetype'
        );
        return $paramoptions;
    }

    /**
     * Helper function to display a string describing the learning item's type
     *
     * @deprecated Since Totara 12.0
     * @param int $instancetype
     * @param object $row
     * @return str
     */
    public function rb_display_associationtype($instancetype, $row) {
        debugging('rb_source_cohort_associations::rb_display_associationtype has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_association_type::display', DEBUG_DEVELOPER);
        switch ($instancetype) {
            case COHORT_ASSN_ITEMTYPE_COURSE:
                $ret = get_string('course');
                break;
            case COHORT_ASSN_ITEMTYPE_PROGRAM:
                $ret = get_string('program', 'totara_program');
                break;
            case COHORT_ASSN_ITEMTYPE_CERTIF:
                $ret = get_string('certification', 'totara_program');
                break;
            default:
                $ret = '';
        }
        return $ret;
    }

    /**
     * Helper function to display the learning item's name, with its icon and a link to it
     *
     * @deprecated Since Totara 12.0
     * @param str $instancename
     * @param object $row
     * @return str
     */
    public function rb_display_associationnameiconlink($instancename, $row) {
        debugging('rb_source_cohort_associations::rb_display_associationnameiconlink has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_association_name_icon_link::display', DEBUG_DEVELOPER);
        if (empty($instancename)) {
            return '';
        }
        if ($row->type == COHORT_ASSN_ITEMTYPE_COURSE) {
            $url = new moodle_url('/course/view.php', array('id' => $row->insid));
        } else {
            $url = new moodle_url('/totara/program/view.php', array('id' => $row->insid));
        }
        return html_writer::link($url, format_string($instancename));
    }

    /**
     * Create the association delete link
     *
     * @deprecated Since Totara 12.0
     * @param $associationid
     * @param $row
     * @return string
     */
    private function cohort_association_delete_link($associationid, $row) {
        debugging('rb_source_cohort_associations::cohort_association_delete_link has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $OUTPUT;

        static $strdelete = false;
        if ($strdelete === false) {
            $strdelete = get_string('deletelearningitem', 'totara_cohort');
        }
        $delurl = new moodle_url('/totara/cohort/dialog/updatelearning.php',
            array('cohortid' => $row->cohortid,
            'type' => $row->type,
            'd' => $associationid,
            'sesskey' => sesskey()));
        return html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', $strdelete), array('title' => $strdelete, 'class' => 'learning-delete'));
    }

    /**
     * Helper function to display the action links for the "enrolled learning" page
     *
     * @deprecated Since Totara 12.0
     * @param int $associationid
     * @param object $row
     * @return str
     */
    public function rb_display_associationactionsenrolled($associationid, $row) {
        debugging('rb_source_cohort_associations::rb_display_associationactionsenrolled has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_association_actions_enrolled::display', DEBUG_DEVELOPER);
        static $canedit = null;
        if ($canedit === null) {
            $canedit = has_capability('moodle/cohort:manage', context_system::instance());
        }

        if ($canedit) {
            global $PAGE;

            //Require JS to intercept the delete call
            $jsmodule = array(
                'name' => 'totara_cohortlearning',
                'fullpath' => '/totara/cohort/dialog/learningitem.js',
                'requires' => array('json'));
            $PAGE->requires->js_init_call('M.totara_cohortlearning.init', array(), false, $jsmodule);
            $PAGE->requires->strings_for_js(array('assignenrolledlearningcourse', 'assignenrolledlearningprogram',
                'assignenrolledlearningcertification', 'deletelearningconfirm', 'savinglearning'),
                'totara_cohort');
            return $this->cohort_association_delete_link($associationid, $row);
        }
        return '';
    }

    /**
     * Helper function to display the "Set due date" link for a program (should only be used with enrolled items)
     *
     * @param $instanceid
     * @param $row
     * @return string
     * @deprecated Since 11; replaced by totara/cohort/classes/rb/display/cohort_association_duedate class.
     */
    public function rb_display_programcompletionlink($instanceid, $row) {
        // NB: no debugging() call here even though this function is deprecated.
        // It is needed as a workaround (see cohort_association_duedate class
        // notes). Other callers should not be using this function at all.

        static $canedit = null;
        if ($canedit === null) {
            $canedit = has_capability('moodle/cohort:manage', context_system::instance());
        }

        if ($canedit && ($row->type == COHORT_ASSN_ITEMTYPE_PROGRAM || $row->type == COHORT_ASSN_ITEMTYPE_CERTIF)) {
            return totara_cohort_program_completion_link($row->cohortid, $instanceid);
        }
        return get_string('na', 'totara_cohort');
    }

    /**
     * Helper function to display the "View date" link for a program (should only be used with enrolled items)
     *
     * @deprecated Since Totara 12.0
     * @param $assignmentid
     * @param $row
     * @return string
     */
    public function rb_display_programviewduedatelink($assignmentid, $row) {
        debugging('rb_source_cohort_associations::rb_display_programviewduedatelink has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_program_view_duedate_link::display', DEBUG_DEVELOPER);
        static $canedit = null;
        if ($canedit === null) {
            $canedit = has_capability('moodle/cohort:manage', context_system::instance());
        }

        if ($canedit && ($row->type == COHORT_ASSN_ITEMTYPE_PROGRAM || $row->type == COHORT_ASSN_ITEMTYPE_CERTIF)) {
            $viewsql = new moodle_url('/totara/program/assignment/duedates_report.php',
                array('programid' => $row->programid, 'assignmentid' => $assignmentid));
            return html_writer::link($viewsql, get_string('viewdates', 'totara_program'),
                array('class' => 'assignment-duedates'));
        }
        return get_string('na', 'totara_cohort');
    }
}
