<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package core_badges
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/totara/program/lib.php');

class award_criteria_program extends award_criteria {

    /** @var int Criteria [BADGE_CRITERIA_TYPE_PROGRAM] */
    public $criteriatype = BADGE_CRITERIA_TYPE_PROGRAM;

    /** @var string Required param for program criteria */
    public $required_param = 'program';

    /** @var array Optional params for program criteria */
    public $optional_params = array();

    /**
     * Get criteria details for displaying to users.
     *
     * @param string $short Print short version of criteria.
     * @return string The program list to display.
     */
    public function get_details($short = '') {
        global $DB, $OUTPUT;
        $output = array();
        $missing = 0;

        foreach ($this->params as $p) {
            $progname = $DB->get_field('prog', 'fullname', array('id' => $p['program']));
            if (!$progname) {
                $missing++;
            } else {
                $output[] = html_writer::tag('strong', '"' . $progname . '"');
            }
        }

        if ($missing == 1) {
            $output[] = $OUTPUT->error_text(get_string('error:missingprogram', 'badges'));
        } else if ($missing) {
            $output[] = $OUTPUT->error_text(get_string('error:missingprograms', 'badges', $missing));
        }

        if ($short) {
            return implode(', ', $output);
        } else {
            return html_writer::alist($output, array(), 'ul');
        }
    }


    /**
     * Add appropriate new criteria options to the form.
     *
     * @param $mform Object mform object to add form elements to.
     * @return array Message when there are no programs to add.
     */
    public function get_options(&$mform) {
        global $DB;
        $none = false;

        $mform->addElement('header', 'first_header', $this->get_title());
        $mform->addHelpButton('first_header', 'criteria_' . $this->criteriatype, 'badges');

        // Get programs.
        $programs = $DB->get_records('prog', array(), 'fullname ASC', 'id, fullname');

        if (!empty($programs)) {
            $select = array();
            $selected = array();
            foreach ($programs as $pid => $program) {
                context_helper::preload_from_record($program);
                $context = context_program::instance($pid);
                $select[$pid] = format_string($program->fullname, true, array('context' => $context));
            }

            if ($this->id !== 0) {
                $selected = array_keys($this->params);
            }
            $settings = array('multiple' => 'multiple', 'size' => 20, 'class' => 'selectprogram');
            $mform->addElement('select', 'program_programs', get_string('addprogram', 'badges'), $select, $settings);
            $mform->addRule('program_programs', get_string('requiredprogram', 'badges'), 'required');
            $mform->addHelpButton('program_programs', 'addprogram', 'badges');

            if ($this->id !== 0) {
                $mform->setDefault('program_programs', $selected);
            }
        } else {
            $mform->addElement('static', 'noprograms', '', get_string('error:noprograms', 'badges'));
            $none = true;
        }

        // Add aggregation.
        if (!$none) {
            $mform->addElement('header', 'aggregation', get_string('method', 'badges'));
            $agg = array();
            $agg[] = $mform->createElement('radio', 'agg', '', get_string('allmethodprogram', 'badges'), 1);
            $agg[] = $mform->createElement('radio', 'agg', '', get_string('anymethodprogram', 'badges'), 2);
            $mform->addGroup($agg, 'methodgr', '', array('<br/>'), false);
            if ($this->id !== 0) {
                $mform->setDefault('agg', $this->method);
            } else {
                $mform->setDefault('agg', BADGE_CRITERIA_AGGREGATION_ANY);
            }
        }

        return array($none, get_string('noparamstoadd', 'badges'));
    }

    /**
     * Save criteria records.
     *
     * @param $params array Criteria params
     */
    public function save($params = array()) {
        $programs = $params['program_programs'];
        unset($params['program_programs']);
        foreach ($programs as $programid) {
            $params["program_{$programid}"] = $programid;
        }

        parent::save($params);
    }

    /**
     * Review this criteria and decide if the user has completed
     *
     * @param int $userid User whose criteria completion needs to be reviewed.
     * @param bool $filtered An additional parameter indicating that user list
     *        has been reduced and some expensive checks can be skipped.
     * @return bool Whether criteria is complete
     */
    public function review($userid, $filtered = false) {
        global $DB;
        $overall = false;

        foreach ($this->params as $param) {
            $program = $DB->get_record('prog', array('id' => $param['program']));

            // Extra check in case a program was deleted while badge is still active.
            if (!$program) {
                if ($this->method == BADGE_CRITERIA_AGGREGATION_ALL) {
                    return false;
                } else {
                    continue;
                }
            }

            if ($this->method == BADGE_CRITERIA_AGGREGATION_ALL) {
                if (prog_is_complete($program->id, $userid)) {
                    $overall = true;
                    continue;
                } else {
                    return false;
                }
            } else if ($this->method == BADGE_CRITERIA_AGGREGATION_ANY) {
                if (prog_is_complete($program->id, $userid)) {
                    return true;
                } else {
                    $overall = false;
                    continue;
                }
            }
        }

        return $overall;
    }

    /**
     * Checks criteria for any major problems.
     *
     * @return array A list containing status and an error message (if any).
     */
    public function validate() {
        global $DB;

        list($sql, $params) = $DB->get_in_or_equal(array_keys($this->params));
        $count = $DB->count_records_select('prog', 'id ' . $sql, $params);
        $missing = count($params) - $count;

        // If there's only 1 criterion in the badge, or there only 1
        // missing from a set but all must be available flag an error.
        if ($missing == 1 && (count($this->params) == 1 || $this->method == BADGE_CRITERIA_AGGREGATION_ALL)) {
            return array(false, get_string('error:invalidparamprogram', 'badges'));
        // If there's any criteria missing when all must present or, all
        // criteria are missing when any must be present flag an error.
        } else if ($missing && ($this->method == BADGE_CRITERIA_AGGREGATION_ALL || $this->method == BADGE_CRITERIA_AGGREGATION_ANY && $missing == count($this->params))) {
            return array(false, get_string('error:invalidparamprograms', 'badges', $missing));
        } else {
            return array(true, '');
        }
    }

    /**
     * Returns array with sql code and parameters returning all ids
     * of users who meet this particular criterion.
     *
     * @return array list($join, $where, $params)
     */
    public function get_completed_criteria_sql() {
        global $DB;

        $join = '';
        $where = '';
        $params = array ();

        // Build the joins and where SQL required to retrieve
        // a list of newly qualifying users who have met program
        // completion criteria.
        foreach ($this->params as $param) {
            $field_param = $DB->get_unique_param('program');
            $table_alias = 'pc' . preg_replace('/.*(\d+)$/', '\\1', $field_param);
            $join .= 'JOIN {prog_completion} ' . $table_alias .' ON u.id = ' . $table_alias . '.userid AND ' . $table_alias . '.status = ' . STATUS_PROGRAM_COMPLETE . ' AND ' . $table_alias . '.timecompleted > 0 ';
            $where .= $this->method == BADGE_CRITERIA_AGGREGATION_ANY ? ' OR' : ' AND';
            $where .= ' ' . $table_alias . '.programid = :' . $field_param;
            $params[$field_param] = $param['program'];
        }

        // If this aggregation is for ANY audience then we need to
        // manipulate $where to make sure the SQL is correct.
        if ($this->method == BADGE_CRITERIA_AGGREGATION_ANY) {
            $where = 'AND (' . substr ($where, 4) . ')';
        }

        return array($join, $where, $params);
    }

}
