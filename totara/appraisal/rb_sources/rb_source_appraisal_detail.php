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

global $CFG;
require_once($CFG->dirroot . '/totara/appraisal/rb_sources/rb_source_appraisal.php');
require_once($CFG->dirroot . '/totara/appraisal/lib.php');

class rb_source_appraisal_detail extends rb_source_appraisal {
    public $shortname;

    /**
     * Stored during post_params() so that it can be used later when generating columns.
     *
     * @var int
     */
    public $appraisalid;

    // Cache for multi choice value names. The report gets the ids of the choices and the display functions convert them to names.
    public static $appraisalmultichoicenamecache = array();

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        parent::__construct($groupid, $globalrestrictionset);

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_appraisal_detail');
        $this->shortname = 'appraisal_detail';
        $this->cacheable = false;
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('appraisals');
    }

    protected function define_columnoptions() {
        $extendedcolumnoptions = array(
            new rb_column_option(
                'rolelearner',
                'answers',
                get_string('answersfromlearner', 'rb_source_appraisal_detail'),
                'rolelearner.data_',
                array('joins' => 'rolelearner',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'answers')
            ),
            new rb_column_option(
                'rolelearner',
                'numericanswers',
                get_string('numericanswersfromlearner', 'rb_source_appraisal_detail'),
                'rolelearner.data_',
                array('joins' => 'rolelearner',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'numericanswers')
            ),
            new rb_column_option(
                'rolelearner',
                'totals',
                get_string('totalsfromlearner', 'rb_source_appraisal_detail'),
                'rolelearner.data_',
                array('joins' => 'rolelearner',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'totals')
            ),
            new rb_column_option(
                'rolemanager',
                'answers',
                get_string('answersfrommanager', 'rb_source_appraisal_detail'),
                'rolemanager.data_',
                array('joins' => 'rolemanager',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'answers')
            ),
            new rb_column_option(
                'rolemanager',
                'numericanswers',
                get_string('numericanswersfrommanager', 'rb_source_appraisal_detail'),
                'rolemanager.data_',
                array('joins' => 'rolemanager',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'numericanswers')
            ),
            new rb_column_option(
                'rolemanager',
                'totals',
                get_string('totalsfrommanager', 'rb_source_appraisal_detail'),
                'rolemanager.data_',
                array('joins' => 'rolemanager',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'totals')
            ),
            new rb_column_option(
                'roleteamlead',
                'answers',
                get_string('answersfromteamlead', 'rb_source_appraisal_detail'),
                'roleteamlead.data_',
                array('joins' => 'roleteamlead',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'answers')
            ),
            new rb_column_option(
                'roleteamlead',
                'numericanswers',
                get_string('numericanswersfromteamlead', 'rb_source_appraisal_detail'),
                'roleteamlead.data_',
                array('joins' => 'roleteamlead',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'numericanswers')
            ),
            new rb_column_option(
                'roleteamlead',
                'totals',
                get_string('totalsfromteamlead', 'rb_source_appraisal_detail'),
                'roleteamlead.data_',
                array('joins' => 'roleteamlead',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'totals')
            ),
            new rb_column_option(
                'roleappraiser',
                'answers',
                get_string('answersfromappraiser', 'rb_source_appraisal_detail'),
                'roleappraiser.data_',
                array('joins' => 'roleappraiser',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'answers')
            ),
            new rb_column_option(
                'roleappraiser',
                'numericanswers',
                get_string('numericanswersfromappraiser', 'rb_source_appraisal_detail'),
                'roleappraiser.data_',
                array('joins' => 'roleappraiser',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'numericanswers')
            ),
            new rb_column_option(
                'roleappraiser',
                'totals',
                get_string('totalsfromappraiser', 'rb_source_appraisal_detail'),
                'roleappraiser.data_',
                array('joins' => 'roleappraiser',
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'totals')
            ),
            new rb_column_option(
                'roleall',
                'answersall',
                get_string('answersfromall', 'rb_source_appraisal_detail'),
                'roleall.data_',
                array('joins' => array('rolelearner', 'rolemanager', 'roleteamlead', 'roleappraiser'),
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'allroleanswers')
            ),
            new rb_column_option(
                'roleall',
                'numericanswersall',
                get_string('numericanswersfromall', 'rb_source_appraisal_detail'),
                'roleall.data_',
                array('joins' => array('rolelearner', 'rolemanager', 'roleteamlead', 'roleappraiser'),
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'allrolenumericanswers')
            ),
            new rb_column_option(
                'roleall',
                'totalsall',
                get_string('totalsfromall', 'rb_source_appraisal_detail'),
                'roleall.data_',
                array('joins' => array('rolelearner', 'rolemanager', 'roleteamlead', 'roleappraiser'),
                      'capability' => 'totara/appraisal:viewallappraisals',
                      'columngenerator' => 'allroletotals')
            )
        );

        return array_merge($extendedcolumnoptions, parent::define_columnoptions());
    }

    /**
     * Set up some extra joins that could not be done in the constructor.
     *
     * @param reportbuilder $report
     */
    public function post_params(reportbuilder $report) {
        $this->appraisalid = $report->get_param_value('appraisalid');

        $this->set_redirect(new moodle_url('/totara/appraisal/rb_sources/appraisaldetailselector.php',
                array('detailreportid' => $report->_id)),
                get_string('selectappraisal', 'totara_appraisal'));

        if ($this->appraisalid) {
            // Set up joins specific to this appraisal.
            $table = "{appraisal_quest_data_{$this->appraisalid}}";
        } else {
            // Either the user needs to be redirected to the report selection page.
            $this->needs_redirect();
            // Or we are on the column editing page and need to provide placeholder joins so that column validation doesn't fail.
            $table = "";
        }

        // Configure the appraisal-specific joins.
        $extendedjoinlist = array(
            new rb_join(
                'rolelearner',
                'LEFT',
                $table,
                'rolelearner.appraisalroleassignmentid = aralearner.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'aralearner'
            ),
            new rb_join(
                'rolemanager',
                'LEFT',
                $table,
                'rolemanager.appraisalroleassignmentid = aramanager.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'aramanager'
            ),
            new rb_join(
                'roleteamlead',
                'LEFT',
                $table,
                'roleteamlead.appraisalroleassignmentid = arateamlead.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'arateamlead'
            ),
            new rb_join(
                'roleappraiser',
                'LEFT',
                $table,
                'roleappraiser.appraisalroleassignmentid = araappraiser.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'araappraiser'
            )
        );
        $this->joinlist = array_merge($this->joinlist, $extendedjoinlist);
    }


    private function make_question_column($type, $value, $heading, $field, $columnoption, $hidden) {
        return new rb_column(
                $type,
                $value,
                $heading,
                $field,
                array(
                    'joins' => $columnoption->joins,
                    'displayfunc' => $columnoption->displayfunc,
                    'extrafields' => $columnoption->extrafields,
                    'required' => false,
                    'capability' => $columnoption->capability,
                    'noexport' => $columnoption->noexport,
                    'grouping' => $columnoption->grouping,
                    'nosort' => $columnoption->nosort,
                    'style' => $columnoption->style,
                    'class' => array('verticaltableheading'),
                    'hidden' => $hidden,
                    'customheading' => 1,
                    'transform' => $columnoption->transform,
                    'aggregate' => $columnoption->aggregate,
                ));
    }


    private function generator_answers($questionrecords, $columnoption, $hidden) {
        $results = array();

        foreach ($questionrecords as $questionrecord) {
            $question = new appraisal_question($questionrecord->id);
            $results = array_merge($results, $this->get_columns_for_question($question, $columnoption, $hidden));
        }

        return $results;
    }


    public function rb_cols_generator_answers($columnoption, $hidden) {
        if (empty($this->appraisalid)) {
            return array();
        }

        $roles = array_flip(appraisal::get_roles());
        $role = $roles[$columnoption->type];

        $questionrecords = appraisal_question::fetch_appraisal($this->appraisalid, $role,
                appraisal::ACCESS_CANANSWER);

        return $this->generator_answers($questionrecords, $columnoption, $hidden);
    }


    public function rb_cols_generator_numericanswers($columnoption, $hidden) {
        if (empty($this->appraisalid)) {
            return array();
        }

        $roles = array_flip(appraisal::get_roles());
        $role = $roles[$columnoption->type];

        $questionrecords = appraisal_question::fetch_appraisal($this->appraisalid, $role,
                appraisal::ACCESS_CANANSWER, array('ratingcustom', 'ratingnumeric'));

        return $this->generator_answers($questionrecords, $columnoption, $hidden);
    }


    private function generator_allroleanswers($allquestionrecords, $columnoption, $hidden) {
        $appraisal = new appraisal($this->appraisalid);
        $roles = $appraisal->get_roles_involved(appraisal::ACCESS_CANANSWER);
        $allroles = appraisal::get_roles();

        // Get questions that can be answered by each role (we get all, but we might not use all).
        $questionrecords = array();
        foreach ($roles as $role) {
            $questionrecords[$role] = appraisal_question::fetch_appraisal($this->appraisalid, $role, appraisal::ACCESS_CANANSWER);
        }

        $results = array();

        // For each question, we see which roles can answer and show those columns.
        foreach ($allquestionrecords as $allquestionrecord) {
            $question = new appraisal_question($allquestionrecord->id);

            // Find out which role will be displayed last.
            $lastincludedrole = 0;
            foreach ($roles as $role) {
                if (isset($questionrecords[$role][$allquestionrecord->id])) {
                    $lastincludedrole = $role;
                }
            }

            // Display each role.
            $originaltype = $columnoption->type;
            $originalfield = $columnoption->field;
            foreach ($roles as $role) {
                if (isset($questionrecords[$role][$allquestionrecord->id])) {
                    $columnoption->type = $allroles[$role];
                    $columnoption->field = $allroles[$role] . ".data_";
                    $results = array_merge($results,
                            $this->get_columns_for_question($question, $columnoption, $hidden, ($role == $lastincludedrole)));
                }
            }
            $columnoption->type = $originaltype;
            $columnoption->field = $originalfield;
        }

        return $results;
    }


    public function rb_cols_generator_allroleanswers($columnoption, $hidden) {
        if (empty($this->appraisalid)) {
            return array();
        }

        // Get all questions.
        $allquestionrecords = appraisal_question::fetch_appraisal($this->appraisalid);

        return $this->generator_allroleanswers($allquestionrecords, $columnoption, $hidden);
    }


    public function rb_cols_generator_allrolenumericanswers($columnoption, $hidden) {
        if (empty($this->appraisalid)) {
            return array();
        }

        // Get all numeric questions.
        $allquestionrecords = appraisal_question::fetch_appraisal($this->appraisalid, null,
                null, array('ratingcustom', 'ratingnumeric'));

        return $this->generator_allroleanswers($allquestionrecords, $columnoption, $hidden);
    }


    public function rb_cols_generator_totals($columnoption, $hidden) {
        global $DB;

        if (empty($this->appraisalid)) {
            return array();
        }

        $roles = array_flip(appraisal::get_roles());
        $role = $roles[$columnoption->type];

        $questionrecords = appraisal_question::fetch_appraisal($this->appraisalid, $role,
                appraisal::ACCESS_CANANSWER, array('ratingcustom', 'ratingnumeric'));

        $field = '';
        $maximum = 0;
        $minimum = 0;
        foreach ($questionrecords as $questionrecord) {
            $question = new appraisal_question($questionrecord->id);

            $element = $question->get_element();
            $fieldsuffix = ($element instanceof question_ratingcustom) ? 'score' : '';

            if ($field != '') {
                $field .= ' + ';
            }
            $field .= 'COALESCE(' . $DB->sql_cast_char2int( $columnoption->field . $question->id . $fieldsuffix) . ',0)';
            $maximum += $question->get_element()->get_max();
            $minimum += $question->get_element()->get_min();
        }

        $rolename = get_string($columnoption->type, 'totara_appraisal');

        $results = array();

        if (!empty($questionrecords)) {
            $newcolumn1 =
                $this->make_question_column(
                    $columnoption->type,
                    $columnoption->value . 'total',
                    get_string('overalltotal', 'rb_source_appraisal_detail', $rolename),
                    '(' . $field . ')',
                    $columnoption,
                    $hidden
                );
            $results[] = $newcolumn1;
        }

        $newcolumn2 =
            $this->make_question_column(
                $columnoption->type,
                $columnoption->value . 'minimum',
                get_string('overallminimum', 'rb_source_appraisal_detail', $rolename),
                '(' . $minimum . ')',
                $columnoption,
                $hidden
            );
        $results[] = $newcolumn2;

        $newcolumn3 =
            $this->make_question_column(
                $columnoption->type,
                $columnoption->value . 'maximum',
                get_string('overallmaximum', 'rb_source_appraisal_detail', $rolename),
                '(' . $maximum . ')',
                $columnoption,
                $hidden
            );
        $results[] = $newcolumn3;

        return $results;
    }


    public function rb_cols_generator_allroletotals($columnoption, $hidden) {
        if (empty($this->appraisalid)) {
            return array();
        }

        $appraisal = new appraisal($this->appraisalid);
        $roles = $appraisal->get_roles_involved(appraisal::ACCESS_CANANSWER);
        $allroles = appraisal::get_roles();

        $results = array();
        $originaltype = $columnoption->type;
        $originalfield = $columnoption->field;
        foreach ($roles as $role) {
            $columnoption->type = $allroles[$role];
            $columnoption->field = $allroles[$role] . ".data_";
            $results = array_merge($results, $this->rb_cols_generator_totals($columnoption, $hidden));
        }
        $columnoption->type = $originaltype;
        $columnoption->field = $originalfield;

        return $results;
    }


    private function get_columns_for_question($question, $columnoption, $hidden, $includesummary = true) {
        global $DB, $FILEPICKER_OPTIONS;

        $role = $columnoption->type;
        $rolename = get_string($role, 'totara_appraisal');
        $a = new stdClass();
        $a->questionname = $question->name;
        $a->rolename = $rolename;

        $results = array();
        $datatype = $question->get_element()->get_type();
        switch ($datatype) {
            case 'text':
                $newcolumn =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . "_" . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $columnoption->field . $question->id,
                        $columnoption,
                        $hidden
                    );
                $results[] = $newcolumn;
                break;

            case 'longtext':
                $newcolumn =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . "_" . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $columnoption->field . $question->id,
                        $columnoption,
                        $hidden
                    );
                $newcolumn->displayfunc = 'appraisal_longtext';
                $results[] = $newcolumn;
                break;

            case 'ratingcustom':
                $newcolumn1 =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . "_" . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $columnoption->field . $question->id . 'score',
                        $columnoption,
                        $hidden
                    );
                $results[] = $newcolumn1;

                if ($includesummary) {
                    $max = $question->get_element()->get_max();
                    $newcolumn2 =
                        $this->make_question_column(
                            $columnoption->type,
                            $columnoption->value . '_' . $question->id . '_maximum',
                            get_string('ratingmaximum', 'rb_source_appraisal_detail', $question->name),
                            '(' . $max . ')',
                            $columnoption,
                            $hidden
                        );
                    $results[] = $newcolumn2;
                }
                break;

            case 'ratingnumeric':
                $newcolumn1 =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . "_" . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $columnoption->field . $question->id,
                        $columnoption,
                        $hidden
                    );
                $results[] = $newcolumn1;

                if ($includesummary) {
                    $max = $question->get_element()->get_max();
                    $newcolumn2 =
                        $this->make_question_column(
                            $columnoption->type,
                            $columnoption->value . '_' . $question->id . '_maximum',
                            get_string('ratingmaximum', 'rb_source_appraisal_detail', $question->name),
                            '(' . $max . ')',
                            $columnoption,
                            $hidden
                        );
                    $results[] = $newcolumn2;
                }
                break;

            case 'multichoicesingle':
                // The column points to the data in the joined table.
                $newcolumn =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . '_' . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $columnoption->field . $question->id,
                        $columnoption,
                        $hidden
                    );
                $newcolumn->displayfunc = 'appraisal_multichoice_single';
                $results[] = $newcolumn;
                break;

            case 'multichoicemulti':
                // Join the scale value table, set its name to the question id.
                $joinname = 'scalevalue' . $columnoption->type . $question->id;
                $this->joinlist[] =
                    new rb_join(
                        $joinname,
                        'LEFT',
                        "(SELECT asd.appraisalroleassignmentid, " . $DB->sql_group_concat('appraisalscalevalueid', ',', 'appraisalscalevalueid') . " AS ids " .
                           "FROM {appraisal_scale_data} asd " .
                          "WHERE appraisalquestfieldid = {$question->id} " .
                          "GROUP BY asd.appraisalroleassignmentid)",
                        $joinname . '.appraisalroleassignmentid = ' . $columnoption->type . '.appraisalroleassignmentid',
                        REPORT_BUILDER_RELATION_ONE_TO_ONE,
                        $columnoption->type
                    );

                // The column points to the data in the joined table.
                $newcolumn =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . '_' . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $joinname . '.ids',
                        $columnoption,
                        $hidden
                    );
                $newcolumn->joins = array($joinname);
                $newcolumn->displayfunc = 'appraisal_multichoice_multi';
                $results[] = $newcolumn;
                break;

            case 'datepicker':
                $withtime = (bool)$question->get_element()->with_time();
                $displayfunc = $withtime ? 'nice_datetime' : 'nice_date';
                $newcolumn =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . '_' . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $columnoption->field . $question->id,
                        $columnoption,
                        $hidden
                    );
                $newcolumn->displayfunc = $displayfunc;
                $results[] = $newcolumn;
                break;

            case 'fileupload':
                // Join the scale value table, set its name to the question id.
                $joinname = 'file' . $columnoption->type . $question->id;
                $file = $DB->sql_concat('f.contextid', "'/'", 'f.component', "'/'", 'f.filearea', 'f.filepath', 'f.itemid',
                        "'/'", 'f.filename');
                $file = $DB->sql_group_concat($file, ', ');
                $this->joinlist[] =
                    new rb_join(
                        $joinname,
                        'LEFT',
                        "(SELECT f.itemid AS appraisalroleassignmentid, {$file} AS files " .
                           "FROM {files} f " .
                          "WHERE f.contextid = {$FILEPICKER_OPTIONS['context']->id} " .
                            "AND f.component = 'totara_appraisal' " .
                            "AND f.filearea = 'quest_{$question->id}' " .
                            "AND f.filename != '.' " .
                          "GROUP BY f.itemid)",
                        $joinname . '.appraisalroleassignmentid = ' . $columnoption->type . '.appraisalroleassignmentid',
                        REPORT_BUILDER_RELATION_ONE_TO_ONE,
                        $columnoption->type
                    );

                // The column points to the data in the joined table.
                $newcolumn =
                    $this->make_question_column(
                        $columnoption->type,
                        $columnoption->value . '_' . $question->id,
                        get_string('answerbyrole', 'rb_source_appraisal_detail', $a),
                        $joinname . '.files',
                        $columnoption,
                        $hidden
                    );
                $newcolumn->joins = array($joinname);
                $newcolumn->displayfunc = 'appraisal_fileupload';
                $results[] = $newcolumn;
                break;

            case '--review--'; // See bugzilla T-11123.
            case 'compfromplan':
            case 'coursefromplan':
            case 'evidencefromplan':
            case 'goals':
            case 'objfromplan':
            case 'progfromplan':
            case '--dontinclude--':
            case 'fixedtext':
            case 'fixedimage':
            case 'userinfo':
            default:
                break;
        }
        return $results;
    }

    /**
     * Display response to the longtext question type
     *
     * @deprecated Since Totara 12.0
     * @param $value
     * @param $unused
     * @param bool $isexport
     * @return mixed|string
     */
    public function rb_display_longtext($value, $unused, $isexport = false) {
        debugging('rb_source_appraisal_detail::rb_display_longtext has been deprecated since Totara 12.0. Use totara_appraisal\rb\display\appraisal_longtext::display', DEBUG_DEVELOPER);
        $cleanvalue = clean_param($value, PARAM_TEXT);
        if ($isexport) {
            return $cleanvalue;
        } else if (strlen($cleanvalue) > 15) {
            return substr($cleanvalue, 0, 15) . "...";
        } else {
            return $cleanvalue;
        }
    }

    /**
     * Display response to the fileupload question type
     *
     * @deprecated Since Totara 12.0
     * @param $value
     * @return string
     */
    public function rb_display_fileupload($value) {
        debugging('rb_source_appraisal_detail::rb_display_fileupload has been deprecated since Totara 12.0. Use totara_appraisal\rb\display\appraisal_fileupload::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if (empty($value)) {
            return '';
        }

        $files = explode(',', $value);

        $list = array();
        foreach ($files as $file) {
            $url = new moodle_url('/pluginfile.php/' . $file, array('forcedownload' => 1));
            $filename = basename($file);
            $icon = mimeinfo("icon", $filename);
            $pic = $OUTPUT->pix_icon("f/{$icon}", $filename);
            $list[] = $OUTPUT->action_link($url, $pic . $filename, null, array('class' => "icon"));
        }

        return implode(html_writer::empty_tag('br'), $list);
    }

    /**
     * Populates the multi choice name cache.
     *
     * Just using a static variable as a cache because it's quick and easy and only costs one query to populate.
     */
    public function populate_multichoice_name_cache() {
        global $DB;

        $sql = "SELECT DISTINCT asv.id, asv.name
                  FROM {appraisal_scale_value} asv
                  JOIN {appraisal_quest_field} aqf
                    ON " . $DB->sql_cast_char2int('aqf.param1') . " = asv.appraisalscaleid
                   AND aqf.datatype IN ('multichoicesingle', 'multichoicemulti')
                  JOIN {appraisal_stage_page} asp
                    ON asp.id = aqf.appraisalstagepageid
                  JOIN {appraisal_stage} ast
                    ON ast.id = asp.appraisalstageid
                 WHERE ast.appraisalid = :appraisalid";
        $params = array('appraisalid' => $this->appraisalid);
        self::$appraisalmultichoicenamecache[$this->appraisalid] = $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Reset the appraisal details multi-choice name cache. Called during testing to prevent leaking between tests.
     */
    public static function reset_cache() {
        self::$appraisalmultichoicenamecache = array();
    }

    /**
     * Display response to the multichoicesingle question type
     *
     * @deprecated Since Totara 12.0
     * @param $id
     * @return string
     */
    public function rb_display_multichoicesingle($id) {
        debugging('rb_source_appraisal_detail::rb_display_multichoicesingle has been deprecated since Totara 12.0. Use totara_appraisal\rb\display\appraisal_multichoice_single::display', DEBUG_DEVELOPER);
        if (empty($id)) {
            return '';
        }

        // Cache option names.
        if (empty(self::$appraisalmultichoicenamecache[$this->appraisalid])) {
            $this->populate_multichoice_name_cache();
        }

        return self::$appraisalmultichoicenamecache[$this->appraisalid][$id];
    }

    /**
     * Display response to the multichoicemulti question type
     *
     * @deprecated Since Totara 12.0
     * @param $idscommalist
     * @return string
     */
    public function rb_display_multichoicemulti($idscommalist) {
        debugging('rb_source_appraisal_detail::rb_display_multichoicemulti has been deprecated since Totara 12.0. Use totara_appraisal\rb\display\appraisal_multichoice_multi::display', DEBUG_DEVELOPER);
        if (empty($idscommalist)) {
            return '';
        }

        // Cache option names.
        if (empty(self::$appraisalmultichoicenamecache[$this->appraisalid])) {
            $this->populate_multichoice_name_cache();
        }

        $ids = explode(',', $idscommalist);

        $result = array();
        foreach ($ids as $id) {
            $result[] = self::$appraisalmultichoicenamecache[$this->appraisalid][$id];
        }

        return implode(', ', $result);
    }

    public function get_required_jss() {
        $jsdetails = new stdClass();
        $jsdetails->initcall = 'M.totara_reportbuilder_verticaltableheadings.init';
        $jsdetails->jsmodule = array('name' => 'totara_reportbuilder_verticaltableheadings',
            'fullpath' => '/totara/reportbuilder/js/verticaltableheadings.js');
        return array($jsdetails);
    }
}
