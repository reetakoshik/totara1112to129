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

class rb_source_appraisal extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    public $shortname;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = '{appraisal_user_assignment}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->embeddedparams = $this->define_embeddedparams();
        $this->usedcomponents[] = 'totara_appraisal';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_appraisal');
        $this->shortname = 'appraisal_status';

        // Apply global report restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'base');

        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('appraisals');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {
        global $DB;

        $incompleteroles = $DB->sql_group_concat_unique($DB->sql_cast_2char('ara.appraisalrole'), '|');
        $joinlist = array(
            new rb_join(
                'appraisal',
                'LEFT',
                '{appraisal}',
                'appraisal.id = base.appraisalid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'activestage',
                'LEFT',
                '{appraisal_stage}',
                'activestage.id = base.activestageid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'previousstage',
                'LEFT',
                '(SELECT aua.id AS appraisaluserassignmentid, MAX(asd.timecompleted) AS timecompleted
                    FROM {appraisal_stage_data} asd
                    JOIN {appraisal_role_assignment} ara
                      ON asd.appraisalroleassignmentid = ara.id
                    JOIN {appraisal_user_assignment} aua
                      ON ara.appraisaluserassignmentid = aua.id
                   WHERE asd.appraisalstageid != aua.activestageid
                   GROUP BY aua.id)',
                'previousstage.appraisaluserassignmentid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'aralearner',
                'LEFT',
                '(SELECT * FROM {appraisal_role_assignment} WHERE appraisalrole = 1)',
                'aralearner.appraisaluserassignmentid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'aramanager',
                'LEFT',
                '(SELECT * FROM {appraisal_role_assignment} WHERE appraisalrole = 2)',
                'aramanager.appraisaluserassignmentid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'arateamlead',
                'LEFT',
                '(SELECT * FROM {appraisal_role_assignment} WHERE appraisalrole = 4)',
                'arateamlead.appraisaluserassignmentid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'araappraiser',
                'LEFT',
                '(SELECT * FROM {appraisal_role_assignment} WHERE appraisalrole = 8)',
                'araappraiser.appraisaluserassignmentid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'activestageincomplete',
                'LEFT',
                "(SELECT aua.id AS appraisaluserassignmentid, {$incompleteroles} AS incompleteroles
                    FROM {appraisal_role_assignment} ara
                    LEFT JOIN {appraisal_stage_data} asd ON ara.id=asd.appraisalroleassignmentid
                    JOIN {appraisal_user_assignment} aua ON ara.appraisaluserassignmentid = aua.id
                   WHERE asd.timecompleted is null
                   GROUP BY aua.id)",
                'base.id = activestageincomplete.appraisaluserassignmentid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                array('appraisal')
            )
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        $columnoptions = array(
            new rb_column_option(
                'userappraisal',
                'activestageid',
                '',
                'base.activestageid',
                array('selectable' => false)
            ),
            new rb_column_option(
                'userappraisal',
                'timecompleted',
                get_string('userappraisaltimecompletedcolumn', 'rb_source_appraisal'),
                'base.timecompleted',
                array('displayfunc' => 'nice_date',
                      'dbdatatype' => 'timestamp',
                      'defaultheading' => get_string('userappraisaltimecompletedheading', 'rb_source_appraisal'))
            ),
            new rb_column_option(
                'userappraisal',
                'previousstagetimecompleted',
                get_string('userappraisalpreviousstagetimecompletedcolumn', 'rb_source_appraisal'),
                'previousstage.timecompleted',
                array('joins' => array('previousstage'),
                      'displayfunc' => 'nice_date',
                      'dbdatatype' => 'timestamp',
                      'defaultheading' => get_string('userappraisalpreviousstagetimecompletedheading', 'rb_source_appraisal'))
            ),
            new rb_column_option(
                'userappraisal',
                'status',
                get_string('userappraisalstatuscolumn', 'rb_source_appraisal'),
                "CASE WHEN base.status = " . appraisal::STATUS_COMPLETED . " AND base.timecompleted IS NOT NULL THEN 'statuscomplete' " .
                     "WHEN base.status = " . appraisal::STATUS_CLOSED . " AND appraisal.status = " . appraisal::STATUS_ACTIVE . " THEN 'statuscancelled' " .
                     "WHEN base.status = " . appraisal::STATUS_CLOSED . " AND (appraisal.status = " . appraisal::STATUS_CLOSED .
                            " OR appraisal.status = " . appraisal::STATUS_COMPLETED . " ) AND base.timecompleted IS NOT NULL THEN 'statuscancelled' " .
                     "WHEN base.status = " . appraisal::STATUS_CLOSED . " AND (appraisal.status = " . appraisal::STATUS_CLOSED .
                            " OR appraisal.status = " . appraisal::STATUS_COMPLETED . " ) THEN 'statusincomplete' " .
                     "WHEN base.status = " . appraisal::STATUS_ACTIVE . " AND activestage.timedue < " . time() . " AND base.timecompleted IS NULL THEN 'statusoverdue' " .
                     "WHEN base.status = " . appraisal::STATUS_ACTIVE . " AND activestage.timedue >= " . time() . " THEN 'statusontarget' " .
                     "ELSE 'statusdraft' " .
                "END",
                array('joins' => array('appraisal', 'activestage'),
                      'displayfunc' => 'appraisal_user_status',
                      'defaultheading' => get_string('userappraisalstatusheading', 'rb_source_appraisal'))
            ),
            new rb_column_option(
                'userappraisal',
                'activestagename',
                get_string('userappraisalactivestagenamecolumn', 'rb_source_appraisal'),
                'activestage.name',
                array('joins' => 'activestage',
                      'defaultheading' => get_string('userappraisalactivestagenameheading', 'rb_source_appraisal'),
                      'displayfunc' => 'format_string',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'userappraisal',
                'activestagetimedue',
                get_string('userappraisalactivestagetimeduecolumn', 'rb_source_appraisal'),
                'activestage.timedue',
                array('joins' => 'activestage',
                      'displayfunc' => 'nice_date',
                      'dbdatatype' => 'timestamp',
                      'defaultheading' => get_string('userappraisalactivestagetimedueheading', 'rb_source_appraisal'))
            ),
            new rb_column_option(
                'appraisal',
                'name',
                get_string('appraisalnamecolumn', 'rb_source_appraisal'),
                'appraisal.name',
                array('joins' => 'appraisal',
                      'defaultheading' => get_string('appraisalnameheading', 'rb_source_appraisal'),
                      'displayfunc' => 'format_string',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'appraisal',
                'status',
                get_string('appraisalstatuscolumn', 'rb_source_appraisal'),
                'appraisal.status',
                array('joins' => 'appraisal',
                      'displayfunc' => 'appraisal_status',
                      'defaultheading' => get_string('appraisalstatusheading', 'rb_source_appraisal'))
            ),
            new rb_column_option(
                'appraisal',
                'timestarted',
                get_string('appraisaltimestartedcolumn', 'rb_source_appraisal'),
                'appraisal.timestarted',
                array('joins' => 'appraisal',
                      'displayfunc' => 'nice_date',
                      'dbdatatype' => 'timestamp',
                      'defaultheading' => get_string('appraisaltimestartedheading', 'rb_source_appraisal'))
            ),
            new rb_column_option(
                'appraisal',
                'timefinished',
                get_string('appraisaltimefinishedcolumn', 'rb_source_appraisal'),
                'appraisal.timefinished',
                array('joins' => 'appraisal',
                      'displayfunc' => 'nice_date',
                      'dbdatatype' => 'timestamp',
                      'defaultheading' => get_string('appraisaltimefinishedheading', 'rb_source_appraisal'))
            ),
            new rb_column_option(
                'appraisal',
                'activestageincomplete',
                get_string('activestageincomplete', 'rb_source_appraisal'),
                'activestageincomplete.incompleteroles',
                array(
                    'joins' => array(
                        'activestageincomplete',
                        'aralearner',
                        'aramanager',
                        'arateamlead',
                        'araappraiser'
                    ),
                    'displayfunc' => 'appraisal_role_list',
                    'extrafields' => array(
                        'role_1' => 'aralearner.userid',
                        'role_2' => 'aramanager.userid',
                        'role_4' => 'arateamlead.userid',
                        'role_8' => 'araappraiser.userid'
                    ),
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
                'userappraisal',
                'activestageid',
                get_string('userappraisalactivestagenamecolumn', 'rb_source_appraisal'),
                'select',
                array('selectfunc' => 'activestagename')
            ),
            new rb_filter_option(
                'userappraisal',
                'status',
                get_string('userappraisalstatuscolumn', 'rb_source_appraisal'),
                'select',
                array('selectfunc' => 'status')
            ),
            new rb_filter_option(
                'appraisal',
                'status',
                get_string('appraisalstatuscolumn', 'rb_source_appraisal'),
                'select',
                array('selectfunc' => 'appraisalstatus')
            ),
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

        return $filteroptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option('appraisalid', 'base.appraisalid'),
            new rb_param_option('filterstatus',
                "CASE WHEN base.status = " . appraisal::STATUS_COMPLETED . " AND base.timecompleted IS NOT NULL THEN 'statuscomplete' " .
                     "WHEN base.status = " . appraisal::STATUS_CLOSED . " AND appraisal.status = " . appraisal::STATUS_ACTIVE . " THEN 'statuscancelled' " .
                     "WHEN base.status = " . appraisal::STATUS_CLOSED . " AND (appraisal.status = " . appraisal::STATUS_CLOSED .
                            " OR appraisal.status = " . appraisal::STATUS_COMPLETED . " ) THEN 'statusincomplete' " .
                     "WHEN base.status = " . appraisal::STATUS_ACTIVE . " AND activestage.timedue < " . time() . " AND base.timecompleted IS NULL THEN 'statusoverdue' " .
                     "WHEN base.status = " . appraisal::STATUS_ACTIVE . " AND activestage.timedue >= " . time() . " THEN 'statusontarget' " .
                     "ELSE 'statusdraft' " .
                "END", array('appraisal', 'activestage'), 'string')
        );

        return $paramoptions;
    }


    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completiondate', 'rb_source_appraisal'),
            'base.timecompleted'
        );

        return $contentoptions;
    }


    /**
     * Convert status code string to human readable string.
     *
     * @deprecated Since Totara 12.0
     * @param string $status status code string
     * @param object $row other fields in the record (unused)
     *
     * @return string
     */
    public function rb_display_status($status, $row) {
        debugging('rb_source_appraisal::rb_display_status has been deprecated since Totara 12.0. Use totara_appraisal\rb\display\appraisal_user_status::display', DEBUG_DEVELOPER);
        return get_string($status, 'rb_source_appraisal');
    }

    /**
     * Convert appraisal status code string to human readable string.
     *
     * @deprecated Since Totara 12.0
     * @param string $status status code string
     * @param object $row other fields in the record (unused)
     *
     * @return string
     */
    public function rb_display_appraisalstatus($status, $row) {
        debugging('rb_source_appraisal::rb_display_appraisalstatus has been deprecated since Totara 12.0. Use totara_appraisal\rb\display\appraisal_status::display', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot.'/totara/appraisal/lib.php');

        return appraisal::display_status($status);
    }

    /**
     * Filter current stage.
     *
     * @param reportbuilder $report
     * @return array
     */
    public function rb_filter_activestagename($report) {
        global $CFG;
        require_once($CFG->dirroot . "/totara/appraisal/lib.php");

        $stagenames = array();

        $appraisalid = $report->get_param_value('appraisalid');
        if ($appraisalid) {
            $appraisal = new appraisal($appraisalid);
            $stages = appraisal_stage::get_stages($appraisalid);
            foreach ($stages as $stage) {
                $stagenames[$stage->id] = $appraisal->name . ': ' . $stage->name;
            }
        } else {
            $stages = appraisal_stage::get_all_stages();
            foreach ($stages as $stage) {
                $stagenames[$stage->id] = $stage->appraisalname . ': ' . $stage->stagename;
            }
        }

        return $stagenames;
    }

    /**
     * Filter current stage.
     *
     * @return array
     */
    public function rb_filter_status() {
        global $CFG;
        require_once($CFG->dirroot . "/totara/appraisal/lib.php");

        $statuses = array();

        $statuses['statuscomplete'] = get_string('statuscomplete', 'rb_source_appraisal');
        $statuses['statuscancelled'] = get_string('statuscancelled', 'rb_source_appraisal');
        $statuses['statusincomplete'] = get_string('statusincomplete', 'rb_source_appraisal');
        $statuses['statusoverdue'] = get_string('statusoverdue', 'rb_source_appraisal');
        $statuses['statusontarget'] = get_string('statusontarget', 'rb_source_appraisal');

        return $statuses;
    }

    public function rb_filter_appraisalstatus() {
        global $CFG;
        require_once($CFG->dirroot . "/totara/appraisal/lib.php");

        $statuses = array();
        $statuses[appraisal::STATUS_DRAFT] = appraisal::display_status(appraisal::STATUS_DRAFT);
        $statuses[appraisal::STATUS_ACTIVE] = appraisal::display_status(appraisal::STATUS_ACTIVE);
        $statuses[appraisal::STATUS_CLOSED] = appraisal::display_status(appraisal::STATUS_CLOSED);
        $statuses[appraisal::STATUS_COMPLETED] = appraisal::display_status(appraisal::STATUS_COMPLETED);

        return $statuses;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'appraisal',
                'value' => 'name',
            ),
            array(
                'type' => 'userappraisal',
                'value' => 'activestagename',
            ),
            array(
                'type' => 'userappraisal',
                'value' => 'activestagetimedue',
            ),
            array(
                'type' => 'userappraisal',
                'value' => 'timecompleted',
            ),
            array(
                'type' => 'userappraisal',
                'value' => 'status',
            )
        );

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'userappraisal',
                'value' => 'activestageid',
            ),
            array(
                'type' => 'userappraisal',
                'value' => 'status',
            )
        );

        return $defaultfilters;
    }

    protected function define_embeddedparams() {
        $embeddedparams = array();

        return $embeddedparams;
    }

}
