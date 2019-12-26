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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package    totara
 * @subpackage completionimport
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/completionimport/lib.php');

/**
 * A report builder source for Certifications
 */
class rb_source_completionimport_course extends rb_base_source {
    /**
     * Constructor
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Restrictions applied in joinlist with additional required join.

        $this->base = '{totara_compl_import_course}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = array();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_completionimport_course');
        $this->usedcomponents[] = 'totara_completionimport';
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
    // Methods for defining contents of source.
    //
    //

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $DB
     * @return array
     */
    protected function define_joinlist() {

        // Add support for user restrictions.
        // This will show only existing allowed users if their username is not changed.
        if ($this->can_global_report_restrictions_be_used()) {
            $this->globalrestrictionjoins[] = new rb_join(
                "realuser",
                "INNER",
                "{user}",
                "base.username = realuser.username",
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            );

            // Apply global user restrictions.
            $this->add_global_report_restriction_join('realuser', 'id');
        }

        $joinlist = array();

        // Join to the user table on the username field, this is the user for whom completion has been imported.
        // Ironically we need this join to use the default alias, because report builder assumes if you join to the user
        // table then you magically want user custom fields.
        // LAME!
        $joinlist[] = new rb_join(
            'auser',
            'LEFT',
            '{user}',
            'auser.username = base.username',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'base'
        );

        // The id of the user who performed the user is stored in importuserid.
        $joinlist[] = new rb_join(
            'importuser',
            'LEFT',
            '{user}',
            'importuser.id = base.importuserid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'base'
        );

        $joinlist[] = new rb_join(
            'dp_plan_evidence',
            'LEFT',
            '{dp_plan_evidence}',
            'dp_plan_evidence.id = base.evidenceid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
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
        global $DB;

        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
                'base',
                'id',
                get_string('columnbaseid', 'rb_source_completionimport_course'),
                'base.id',
                array('displayfunc' => 'integer')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'rownumber',
                get_string('columnbaserownumber', 'rb_source_completionimport_course'),
                'base.rownumber',
                array('dbdatatype' => 'integer',
                      'displayfunc' => 'integer')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'importerrormsg',
                get_string('columnbaseimporterrormsg', 'rb_source_completionimport_course'),
                'base.importerrormsg',
                array(
                    'displayfunc' => 'completionimport_error_message',
                )
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'importevidence',
                get_string('columnbaseimportevidence', 'rb_source_completionimport_course'),
                'base.importevidence',
                array(
                    'displayfunc' => 'yes_or_no',
                )
        );

        $columnoptions[] = new rb_column_option(
                'importuser',
                'userfullname',
                get_string('columnbaseimportuserfullname', 'rb_source_completionimport_course'),
                $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('importuser', null, true)),
                array(
                    'joins' => 'importuser',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'extrafields' => totara_get_all_user_name_fields_join('importuser'),
                    'displayfunc' => 'user'
                )
        );

        $columnoptions[] = new rb_column_option(
                'importuser',
                'username',
                get_string('columnbaseimportusername', 'rb_source_completionimport_course'),
                'importuser.username',
                array('joins' => 'importuser',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'importuserid',
                get_string('columnbaseimportuserid', 'rb_source_completionimport_course'),
                'base.importuserid',
                array('displayfunc' => 'plaintext')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'timecreated',
                get_string('columnbasetimecreated', 'rb_source_completionimport_course'),
                'base.timecreated',
                array(
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype' => 'timestamp'
                )
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'username',
                get_string('columnbaseusername', 'rb_source_completionimport_course'),
                'base.username',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'courseshortname',
                get_string('columnbasecourseshortname', 'rb_source_completionimport_course'),
                'base.courseshortname',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'courseidnumber',
                get_string('columnbasecourseidnumber', 'rb_source_completionimport_course'),
                'base.courseidnumber',
                array('dbdatatype' => 'char',
                      'displayfunc' => 'plaintext',
                      'outputformat' => 'text')
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'completiondate',
                get_string('columnbasecompletiondate', 'rb_source_completionimport_course'),
                'base.completiondate',
                array('displayfunc' => 'plaintext')
                // NOTE: This is not an integer timestamp in install.xml.
        );

        $columnoptions[] = new rb_column_option(
                'base',
                'grade',
                get_string('columnbasegrade', 'rb_source_completionimport_course'),
                'base.grade',
                array('displayfunc' => 'plaintext')
        );

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
                'base',
                'id',
                get_string('columnbaseid', 'rb_source_completionimport_course'),
                'int'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'rownumber',
                get_string('columnbaserownumber', 'rb_source_completionimport_course'),
                'int'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'importerrormsg',
                get_string('columnbaseimporterrormsg', 'rb_source_completionimport_course'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'importuser',
                'userfullname',
                 get_string('columnbaseimportuserfullname', 'rb_source_completionimport_course'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'importuser',
                'username',
                 get_string('columnbaseimportusername', 'rb_source_completionimport_course'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'importuserid',
                get_string('columnbaseimportuserid', 'rb_source_completionimport_course'),
                'int'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'timecreated',
                get_string('columnbasetimecreated', 'rb_source_completionimport_course'),
                'select',
                array(
                    'selectfunc' => 'timecreated',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'username',
                get_string('columnbaseusername', 'rb_source_completionimport_course'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'courseshortname',
                get_string('columnbasecourseshortname', 'rb_source_completionimport_course'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'courseidnumber',
                get_string('columnbasecourseidnumber', 'rb_source_completionimport_course'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'completiondate',
                get_string('columnbasecompletiondate', 'rb_source_completionimport_course'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'base',
                'grade',
                get_string('columnbasegrade', 'rb_source_completionimport_course'),
                'text'
        );

        return $filteroptions;
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
                'timecreated',
                'base.timecreated'
        );
        $paramoptions[] = new rb_param_option(
                'importuserid',
                'base.importuserid'
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'base',
                'value' => 'id',
            ),
            array(
                'type' => 'base',
                'value' => 'rownumber',
            ),
            array(
                'type' => 'base',
                'value' => 'importerrormsg',
            ),
            array(
                'type' => 'base',
                'value' => 'importevidence',
            ),
            array(
                'type' => 'importuser',
                'value' => 'userfullname',
            ),
            array(
                'type' => 'base',
                'value' => 'timecreated',
            ),
            array(
                'type' => 'base',
                'value' => 'username',
            ),
            array(
                'type' => 'base',
                'value' => 'courseshortname',
            ),
            array(
                'type' => 'base',
                'value' => 'courseidnumber',
            ),
            array(
                'type' => 'base',
                'value' => 'completiondate',
            ),
            array(
                'type' => 'base',
                'value' => 'grade',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'base',
                'value' => 'id',
                'advanced' => 1,
            ),
            array(
                'type' => 'base',
                'value' => 'rownumber',
                'advanced' => 0,
            ),
            array(
                'type' => 'importuser',
                'value' => 'userfullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'base',
                'value' => 'timecreated',
                'advanced' => 0,
            ),
            array(
                'type' => 'base',
                'value' => 'username',
                'advanced' => 1,
            ),
            array(
                'type' => 'base',
                'value' => 'courseshortname',
                'advanced' => 1,
            ),
            array(
                'type' => 'base',
                'value' => 'courseidnumber',
                'advanced' => 1,
            ),
            array(
                'type' => 'base',
                'value' => 'completiondate',
                'advanced' => 1,
            ),
            array(
                'type' => 'base',
                'value' => 'grade',
                'advanced' => 1,
            ),
        );
        return $defaultfilters;
    }

    /**
     * Display the error message
     *
     * @deprecated Since Totara 12.0
     * @param $importerrormsg
     * @param $row
     * @param $isexport
     * @return string
     */
    public function rb_display_importerrormsg($importerrormsg, $row, $isexport) {
        debugging('rb_source_completionimport_course::rb_display_importerrormsg has been deprecated since Totara 12.0. Use totara_completionimport\rb\display\completionimport_error_message::display', DEBUG_DEVELOPER);
        $errors = array();
        $errorcodes = explode(';', $importerrormsg);
        foreach ($errorcodes as $errorcode) {
            if (!empty($errorcode)) {
                $errors[] = get_string($errorcode, 'totara_completionimport');
            }
        }

        if ($isexport) {
            return implode("\n", $errors);
        } else {
            return html_writer::alist($errors);
        }
    }

    public function rb_filter_timecreated() {
        global $DB;

        $out = array();
        $sql = "SELECT DISTINCT timecreated
                FROM {totara_compl_import_course}
                WHERE importerror = :importerror
                ORDER BY timecreated DESC";
        $times = $DB->get_records_sql($sql, array('importerror' => 0));
        foreach ($times as $time) {
            $out[$time->timecreated] = userdate($time->timecreated, get_string('strftimedatetimeshort', 'langconfig'));
        }
        return $out;
    }
}
