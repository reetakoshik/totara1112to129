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
class rb_source_cohort_associations_visible extends rb_base_source {
    /**
     * Constructor
     * @global object $CFG
     */
    public function __construct() {
        $this->base = '{cohort_visibility}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_cohort_associations_visible');
        $this->usedcomponents[] = 'totara_cohort';
        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        global $CFG;
        return empty($CFG->audiencevisibility);
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
        global $DB;

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
            'INNER',
            "(SELECT c.id, c.id AS instanceid,
                c.fullname AS name, c.icon, c.shortname, " . COHORT_ASSN_ITEMTYPE_COURSE . " AS instancetype, c.audiencevisible
            FROM {course} c

            UNION

            SELECT p.id, p.id AS instanceid,
            p.fullname AS name, p.icon, p.shortname, " .
            " CASE WHEN p.certifid > 0 THEN " . COHORT_ASSN_ITEMTYPE_CERTIF .
            " ELSE " . COHORT_ASSN_ITEMTYPE_PROGRAM .
            " END AS instancetype, " .
            "p.audiencevisible
            FROM {prog} p)",
            'base.instancetype = associations.instancetype AND base.instanceid = associations.instanceid',
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
            'associations.name',
            array('joins' => 'associations',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text')
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'status',
            get_string('visibility', 'totara_cohort'),
            'associations.audiencevisible',
            array(
                'joins' => 'associations',
                'displayfunc' => 'cohort_visibility_status',
                'extrafields' => array(
                    'insid' => 'base.instanceid',
                    'type' => 'base.instancetype',
                    'id' => 'base.id',
                    'cohortid' => 'base.cohortid',
                    'instanceshortname' => 'associations.shortname'
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'type',
            get_string('associationtype', 'totara_cohort'),
            'base.instancetype',
            array('displayfunc' => 'cohort_association_type')
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'nameiconlink',
            get_string('associationnameiconlink', 'totara_cohort'),
            'associations.name',
            array(
                'joins' => 'associations',
                'displayfunc' => 'cohort_association_name_icon_link',
                'extrafields' => array(
                    'insid' => 'base.instanceid',
                    'icon' => 'associations.icon',
                    'type' => 'base.instancetype'
                )
            )
        );
        $columnoptions[] = new rb_column_option(
            'associations',
            'actionsvisible',
            get_string('associationactionsvisible', 'totara_cohort'),
            'base.id',
            array(
                'displayfunc' => 'cohort_association_actions_visible',
                'extrafields' => array('cohortid' => 'base.cohortid', 'type' => 'base.instancetype'),
                'nosort' => true,
                'noexport' => true
            )
        );
        $columnoptions[] = new rb_column_option(
            'cohort',
            'name',
            get_string('name', 'totara_cohort'),
            'cohort.name',
            array('joins' => 'cohort',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text')
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
            ),
            array(
                'type' => 'associations',
                'value' => 'status',
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
        debugging('rb_source_cohort_associations_visible::rb_display_associationtype has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_association_type::display', DEBUG_DEVELOPER);
        switch ($instancetype) {
            case COHORT_ASSN_ITEMTYPE_COURSE:
                $ret = get_string('course');
                break;
            case COHORT_ASSN_ITEMTYPE_PROGRAM:
                $ret = get_string('program', 'totara_program');
                break;
            case COHORT_ASSN_ITEMTYPE_CERTIF:
                $ret = get_string('certification', 'totara_certification');
                break;
            default:
                $ret = '';
        }
        return $ret;
    }

    /**
     * Helper function to display visible learning status
     *
     * @deprecated Since Totara 12.0
     * @param int $status
     * @param object $row
     * @return string
     */
    public function rb_display_visibility_status($status, $row) {
        debugging('rb_source_cohort_associations_visible::rb_display_visibility_status has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_visibility_status::display', DEBUG_DEVELOPER);
        global $CFG, $COHORT_VISIBILITY;

        if (empty($CFG->audiencevisibility)) {
            return $COHORT_VISIBILITY[$status];
        }

        $type = $row->type;
        if ($type == COHORT_ASSN_ITEMTYPE_COURSE) {
            $hascapability = has_capability('moodle/course:update', context_course::instance($row->insid));
        } else if (in_array($type, array(COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_ITEMTYPE_CERTIF))) {
            $programcontext = context_program::instance($row->insid);
            $hascapability = has_capability('totara/program:configuredetails', $programcontext);
        }

        if (isset($hascapability) && $hascapability) { // Has capability to change the visibility of learning contents.
            $output = html_writer::start_tag('form', array('id' => 'changevisibilityaudience' . $row->insid));
            $output .= html_writer::select($COHORT_VISIBILITY,
                '_'.$type.'_'.$row->insid,
                $status,
                false);
            $output .= html_writer::end_tag('form');
        } else {
            $output = $COHORT_VISIBILITY[$status];
        }

        return $output;
    }

    /**
     * Helper function to display the learning item's name, with its icon and a link to it.
     *
     * @deprecated Since Totara 12.0
     * @param str $instancename
     * @param object $row
     * @return str html link
     */
    public function rb_display_associationnameiconlink($instancename, $row) {
        debugging('rb_source_cohort_associations_visible::rb_display_associationnameiconlink has been deprecated since Totara 12.0, Use totara_cohort\rb\display\cohort_association_name_icon_link::display', DEBUG_DEVELOPER);
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
     * Create delete icon and link
     *
     * @deprecated Since Totara 12.0
     * @param $associationid
     * @param $row
     * @return mixed
     */
    private function cohort_association_delete_link($associationid, $row) {
        debugging('rb_source_cohort_associations_visible::rb_display_associationnameiconlink has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $OUTPUT;

        static $strdelete = false;
        if ($strdelete === false) {
            $strdelete = get_string('deletelearningitem', 'totara_cohort');
        }
        $delurl = new moodle_url('/totara/cohort/dialog/updatelearning.php',
            array('cohortid' => $row->cohortid,
            'type' => $row->type,
            'd' => $associationid,
            'v' => COHORT_ASSN_VALUE_VISIBLE,
            'sesskey' => sesskey()));
        return $OUTPUT->action_icon($delurl, new pix_icon('t/delete', $strdelete), null, array('title' => $strdelete, 'class' => 'learning-delete'));
    }

    /**
     * Helper function to display the action links for the "visible learning" page
     *
     * @deprecated Since Totara 12.0
     * @param int $associationid
     * @param object $row
     * @return str
     */
    public function rb_display_associationactionsvisible($associationid, $row) {
        debugging('rb_source_cohort_associations_visible::rb_display_associationactionsvisible has been deprecated since Totara 12.0, Use totara_cohort\rb\display\cohort_association_actions_visible:display', DEBUG_DEVELOPER);
        static $canedit = null;
        if ($canedit === null) {
            $canedit = has_capability('moodle/cohort:manage', context_system::instance());
        }

        if ($canedit) {
            return $this->cohort_association_delete_link($associationid, $row);
        }
        return '';
    }

    public function get_required_jss() {
        global $COHORT_VISIBILITY;

        $jsdetails = new stdClass();
        $jsdetails->initcall = 'M.totara_cohortvisiblelearning.init';
        $jsdetails->jsmodule = array(
            'name' => 'totara_cohortvisiblelearning',
            'fullpath' => '/totara/cohort/visiblelearning.js',
            'requires' => array('json'));
        $jsdetails->args = array('args' => '{"cohort_visibility":' . json_encode($COHORT_VISIBILITY) . '}');
        $jsdetails->strings = array('error' => ['invalidentry'], 'totara_cohort' => ['error:badresponsefromajax', 'savingrule', 'deletelearningconfirm']);

        return array($jsdetails);
    }
}
