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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort;

defined('MOODLE_INTERNAL') || die();

/**
 * Cohort learning plan class that deals with the creation of learning plans for cohorts.
 */
class learning_plan_helper {

    /**
     * Get count or list of user ids from the audience who require a learning plan to be created
     *
     * @param learning_plan_config $config
     * @param bool $countonly - boolean, to return count only of affected users
     * @return int|array
     */
    public static function get_affected_users(learning_plan_config $config, $countonly = false) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/cohort/lib.php");

        // Calculate the number of affected users.
        if ($countonly === true) {
            $sql = 'SELECT COUNT(DISTINCT cm.userid)';
        } else {
            $sql = 'SELECT DISTINCT cm.userid';
        }
        $sql .= '
            FROM {cohort_members} cm
            WHERE';
        $params = array();
        // Are we excluding anyone at all?
        if ($config->excludecreatedmanual || $config->excludecreatedauto || $config->excludecompleted) {
            $planwhere = 'p.templateid = :plantemplate';
            $params['plantemplate'] = $config->plantemplateid;
            $whereclauses = array();

            $createdby = array();
            if ($config->excludecreatedmanual) {
                $createdby[] = PLAN_CREATE_METHOD_MANUAL;
            }
            if ($config->excludecreatedauto) {
                $createdby[] = PLAN_CREATE_METHOD_COHORT;
            }
            if (!empty($createdby)) {
                list($insql, $inparams) = $DB->get_in_or_equal($createdby, SQL_PARAMS_NAMED);
                $whereclauses[] = " p.createdby $insql";
                $params = array_merge($params, $inparams);
            }

            if ($config->excludecompleted) {
                $whereclauses[] = ' p.status = :status ';
                $params['status'] = DP_PLAN_STATUS_COMPLETE;
            }

            // Build the clauses where sql.
            if (!empty($whereclauses)) {
                $planwhere .= ' AND (' . join(' OR ', $whereclauses) . ')';
            }

            // Add the exclusion SQL clause.
            $sql .= '
                    NOT EXISTS
                        (SELECT p.userid
                        FROM {dp_plan} p
                        WHERE ' . $planwhere . ' AND cm.userid = p.userid)
                    AND ';
        }

        $where = ' cm.cohortid = :cohortid';
        $params['cohortid'] = $config->cohortid;

        $sql .= $where;

        if ($countonly === true) {
            return $DB->count_records_sql($sql, $params);
        } else {
            return $DB->get_records_sql($sql, $params);
        }
    }

    /**
     * Create learning plans based on conditions of the config
     *
     * @param learning_plan_config $config
     * @param int $userid Optional, the id of the user who created the plan.
     * @return int the count of plans created
     */
    public static function create_plans(learning_plan_config $config, int $userid = 0) {
        global $DB, $USER;

        $now = time();
        $createdplancount = 0;

        // Get affected members.
        $affected_members = learning_plan_helper::get_affected_users($config);

        if (!$affected_members) {
            // Add to the history log.
            self::log_learning_plan_changes($config, $now, $createdplancount, $userid);
            return $createdplancount;
        }

        // Get details of template.
        $plantemplate = $DB->get_record('dp_template', array('id' => $config->plantemplateid));

        $newplanids = array();
        $transaction = $DB->start_delegated_transaction();

        foreach ($affected_members as $member) {
            $plan = new \stdClass();
            $plan->templateid = $plantemplate->id;
            $plan->name = $plantemplate->fullname;
            $plan->startdate = $now;
            $plan->enddate = $plantemplate->enddate;
            $plan->userid = $member->userid;
            $plan->status = $config->planstatus;
            $plan->createdby = PLAN_CREATE_METHOD_COHORT;

            $newplanids[] = $DB->insert_record('dp_plan', $plan);
            unset($plan);
        }

        $plan_history_records = array();

        foreach ($newplanids as $planid) {
            $history = new \stdClass;
            $history->planid = $planid;
            $history->status = $config->planstatus;
            $history->reason = DP_PLAN_REASON_CREATE;
            $history->timemodified = time();
            $history->usermodified = ($userid ? $userid : $USER->id);

            $plan_history_records[] = $history;
        }

        // Batch insert history records.
        $DB->insert_records_via_batch('dp_plan_history', $plan_history_records);

        // Since all plans are the same template the components list will be the same for all.
        $components = array();

        foreach ($newplanids as $planid) {
            $plan = new \development_plan($planid);

            if (!$components) {
                $components = $plan->get_components();
            }

            foreach ($components as $componentname => $details) {
                $component = $plan->get_component($componentname);
                if ($component->get_setting('enabled')) {

                    // Automatically add items from this component.
                    $component->plan_create_hook();
                }

                // Free memory.
                unset($component);
            }

            // Free memory.
            unset($plan);
            $createdplancount++;
        }

        self::log_learning_plan_changes($config, $now, $createdplancount, $userid);

        $transaction->allow_commit();

        return $createdplancount;
    }

    /**
     * Writes a log entry for the current state of affairs.
     *
     * @param learning_plan_config $config
     * @param float $now Timmestamp to indicate when the plans where created.
     * @param int $affectedcount The number of users affected.
     * @param int $userid Optional, the id of the user who created the plan.
     */
    protected static function log_learning_plan_changes(learning_plan_config $config, $now, $affectedcount, int $userid = 0) {
        global $DB, $USER;

        // Add record to history table.
        $history = new \stdClass();
        $history->cohortid = $config->cohortid;
        $history->templateid = $config->plantemplateid;
        $history->usercreated = ($userid ? $userid : $USER->id);
        $history->timecreated = $now;
        $history->planstatus = $config->planstatus;
        $history->affectedusers = $affectedcount;
        $history->manual = $config->excludecreatedmanual;
        $history->auto = $config->excludecreatedauto;
        $history->completed = $config->excludecompleted;
        $DB->insert_record('cohort_plan_history', $history);
    }
}
