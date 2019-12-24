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
 * @package badges
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Badge award criteria -- award on cohort membership
 *
 */
class award_criteria_cohort extends award_criteria {

    /* @var int Criteria [BADGE_CRITERIA_TYPE_COHORT] */
    public $criteriatype = BADGE_CRITERIA_TYPE_COHORT;

    public $required_param = 'cohort';
    public $optional_params = array();

    /**
     * Get criteria details for displaying to users.
     *
     * @return string
     */
    public function get_details($short = '') {
        global $DB, $OUTPUT;
        $output = array();
        $missing = 0;

        foreach ($this->params as $p) {
            $cohortname = $DB->get_field('cohort', 'name', array('id' => $p['cohort']));
            if (!$cohortname) {
                $missing++;
            } else {
                $output[] = html_writer::tag('strong', '"' . $cohortname . '"');
            }
        }

        if ($missing == 1) {
            $output[] = $OUTPUT->error_text(get_string('error:missingcohort', 'badges'));
        } else if ($missing) {
            $output[] = $OUTPUT->error_text(get_string('error:missingcohortplural', 'badges', $missing));
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
     * @param object form object.
     * @return array Containing a flag to indicate if any cohorts where found and a string to display if none.
     */
    public function get_options(&$mform) {
        global $DB;
        $none = false;

        $mform->addElement('header', 'first_header', $this->get_title());
        $mform->addHelpButton('first_header', 'criteria_' . $this->criteriatype, 'badges');

        // Get cohorts.
        $cohorts = $DB->get_records_menu('cohort', array(), 'name ASC', 'id, name');
        if (!empty($cohorts)) {
            $select = array();
            $selected = array();
            foreach ($cohorts as $cid => $cohortname) {
                $select[$cid] = format_string($cohortname, true);
            }

            if ($this->id !== 0) {
                $selected = array_keys($this->params);
            }
            $settings = array('multiple' => 'multiple', 'size' => 20, 'class' => 'selectcohort');
            $mform->addElement('select', 'cohort_cohorts', get_string('addcohort', 'badges'), $select, $settings);
            $mform->addRule('cohort_cohorts', get_string('requiredcohort', 'badges'), 'required');
            $mform->addHelpButton('cohort_cohorts', 'addcohort', 'badges');

            if ($this->id !== 0) {
                $mform->setDefault('cohort_cohorts', $selected);
            }
        } else {
            $mform->addElement('static', 'nocohorts', '', get_string('error:nocohorts', 'badges'));
            $none = true;
        }

        // Add aggregation.
        if (!$none) {
            $mform->addElement('header', 'aggregation', get_string('method', 'badges'));
            $agg = array();
            $agg[] =& $mform->createElement('radio', 'agg', '', get_string('allmethodcohort', 'badges'), 1);
            $agg[] =& $mform->createElement('radio', 'agg', '', get_string('anymethodcohort', 'badges'), 2);
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
     * @param $params criteria params
     */
    public function save($params = array()) {
        $cohorts = $params['cohort_cohorts'];
        unset($params['cohort_cohorts']);
        foreach ($cohorts as $cohortid) {
            $params["cohort_{$cohortid}"] = $cohortid;
        }

        parent::save($params);
    }

    /**
     * Review this criteria and decide if the user has completed.
     *
     * @param int $userid User whose criteria completion needs to be reviewed.
     * @param bool $filtered An additional parameter indicating that user list
     *        has been reduced and some expensive checks can be skipped.
     *
     * @return bool Whether criteria is complete
     */
    public function review($userid, $filtered = false) {
        global $DB;
        $overall = false;

        foreach ($this->params as $param) {
            $cohort = $DB->get_record('cohort', array('id' => $param['cohort']));

            // Extra check in case a cohort was deleted while badge is still active.
            if (!$cohort) {
                if ($this->method == BADGE_CRITERIA_AGGREGATION_ALL) {
                    return false;
                } else {
                    continue;
                }
            }

            if ($this->method == BADGE_CRITERIA_AGGREGATION_ALL) {
                if (cohort_is_member($cohort->id, $userid)) {
                    $overall = true;
                    continue;
                } else {
                    return false;
                }
            } else if ($this->method == BADGE_CRITERIA_AGGREGATION_ANY) {
                if (cohort_is_member($cohort->id, $userid)) {
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
        $missing = 0;

        // Check how many programs might be missing.
        foreach ($this->params as $id => $param) {
            if (!$DB->record_exists('cohort', array('id' => $id))) {
                $missing++;
            }
        }

        // If there's only 1 criterion in the badge, or there only 1
        // missing from a set but all must be available flag an error.
        if ($missing == 1 && (count($this->params) == 1 || $this->method == BADGE_CRITERIA_AGGREGATION_ALL)) {
            return array(false, get_string('error:invalidparamcohort', 'badges'));
        // If there's any criteria missing when all must present or, all
        // criteria are missing when any must be present flag an error.
        } else if ($missing && ($this->method == BADGE_CRITERIA_AGGREGATION_ALL || $this->method == BADGE_CRITERIA_AGGREGATION_ANY && $missing == count($this->params))) {
            return array(false, get_string('error:invalidparamcohorts', 'badges', $missing));
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
        // a list of newly qualifying members of an audience.
        foreach ($this->params as $param) {
            $field_param = $DB->get_unique_param('cohort');
            $table_alias = 'cm' . preg_replace('/.*(\d+)$/', '\\1', $field_param);
            $join .= 'JOIN {cohort_members} ' . $table_alias .' ON u.id = ' . $table_alias . '.userid ';
            $where .= $this->method == BADGE_CRITERIA_AGGREGATION_ANY ? ' OR' : ' AND';
            $where .= ' ' . $table_alias . '.cohortid = :' . $field_param;
            $params[$field_param] = $param['cohort'];
        }

        // If this aggregation is for ANY audience then we need to
        // manipulate $where to make sure the SQL is correct.
        if ($this->method == BADGE_CRITERIA_AGGREGATION_ANY) {
            $where = 'AND (' . substr ($where, 4) . ')';
        }

        return array($join, $where, $params);
    }
}
