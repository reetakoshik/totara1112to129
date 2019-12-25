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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\task;

use coding_exception;
use moodle_recordset;
use stdClass;
use totara_job\job_assignment;

/**
 * Update competency evidence
 */
class update_competencies_task extends \core\task\scheduled_task {

    /**
     * @var int
     */
    private $timestarted = 0;

    /**
     * @var array|stdClass[]
     */
    private $scale_values;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatecompetenciestask', 'totara_hierarchy');
    }

    /**
     * Update competency evidence
     *
     * The order in which we do things is important
     *  1) update all competency items evidence
     *  2) aggregate competency hierarchy depth levels
     *
     * @return void
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/evidence/evidence.php');
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/evidence/lib.php');
        require_once($CFG->dirroot.'/totara/plan/lib.php');

        $this->timestarted = time();
        $this->run_evidence_type_aggregation_methods();
        $this->load_scale_values();

        $users = $this->load_users();
        $framework_depthlevels = $this->load_framework_depthlevels();
        foreach ($users as $user) {
            $userid = (int)$user->id;

            // Get user's current first job assignment position and organisation (if any).
            $job_assignment = job_assignment::get_first($userid, false);

            foreach ($framework_depthlevels as $record) {
                $frameworkid = (int)$record->frameworkid;
                $depthlevel = (int)$record->depthlevel;

                // Load all records which are needed for aggregation grouped by competency.
                $evidence_records = $this->load_evidence_records_to_aggregate($userid, $frameworkid, $depthlevel);
                if (!empty($evidence_records)) {
                    if (debugging()) {
                        mtrace("Aggregating competency evidence for depth level $depthlevel and frameworkid $frameworkid for user $userid");
                    }

                    // Aggregate this framework and depth level.
                    $this->aggregate_competency_evidence_items($userid, $evidence_records, $job_assignment);
                }
            }
            $this->mark_as_aggregated($userid);
        }
    }

    /**
     * Run installed competency evidence type's aggregation methods
     *
     * Loop through each installed evidence type and run the
     * cron() method if it exists
     *
     * @deprecated since Totara 12.0
     *
     * @return void
     */
    protected function competency_cron_evidence_items() {
        throw new coding_exception('The method update_competencies_task->competency_cron_evidence_items() was deprecated due to a refactoring.');
    }

    /**
     * Run installed competency evidence type's aggregation methods
     *
     * Loop through each installed evidence type and run the
     * cron() method if it exists
     *
     * @return void
     */
    private function run_evidence_type_aggregation_methods(): void {
        global $CFG, $COMPETENCY_EVIDENCE_TYPES;

        // Process each evidence type.
        foreach ($COMPETENCY_EVIDENCE_TYPES as $type) {
            $object = '\competency_evidence_type_'.$type;
            $source = $CFG->dirroot.'/totara/hierarchy/prefix/competency/evidenceitem/type/'.$type.'.php';

            if (!file_exists($source)) {
                continue;
            }

            require_once($source);
            $class = new $object();

            // Run the evidence type's cron method, if it has one.
            if (method_exists($class, 'cron')) {
                if (debugging()) {
                    mtrace('Running '.$object.'->cron()');
                }
                $class->cron();
            }
        }
    }

    /**
     * Load all scale values and store it in class property for later use
     *
     * @return void
     */
    private function load_scale_values(): void {
        global $DB;

        // Grab all competency scale values.
        $this->scale_values = $DB->get_records('comp_scale_values');
    }

    /**
     * Load all users who have competency records to reaggregate in the given framework and depth
     *
     * @return moodle_recordset
     */
    private function load_users(): moodle_recordset {
        global $DB, $COMP_AGGREGATION;

        $sql = "
            SELECT DISTINCT cr.userid as id
            FROM {comp_record} cr 
            INNER JOIN {comp} c ON cr.competencyid = c.id
            WHERE cr.reaggregate > 0
                AND cr.reaggregate <= :timestarted
                AND cr.manual = 0
                AND c.aggregationmethod <> :aggregationmethod
            ORDER BY cr.userid
        ";

        $params = [
            'timestarted' => $this->timestarted,
            'aggregationmethod' => $COMP_AGGREGATION['OFF']
        ];

        return $DB->get_recordset_sql($sql, $params);
    }

    /**
     * @return array|stdClass[]
     */
    private function load_framework_depthlevels(): array {
        global $DB;

        // Loop through each depth level, lowest levels first, processing individually.
        $depthkey = $DB->sql_concat_join(
            "'|'",
            [
                $DB->sql_cast_2char('depthlevel'),
                $DB->sql_cast_2char('frameworkid')
            ]
        );
        $sql = "SELECT DISTINCT {$depthkey} AS depthkey, depthlevel, frameworkid
                FROM {comp}
                ORDER BY frameworkid, depthlevel DESC";

        return $DB->get_records_sql($sql);
    }

    /**
     * @param int $userid
     * @param int $frameworkid
     * @param int $depthlevel
     * @return array
     */
    private function load_evidence_records_to_aggregate(int $userid, int $frameworkid, int $depthlevel): array {
        global $DB, $COMP_AGGREGATION;

        // Grab all competency evidence items for a depth level
        //
        // A little discussion on what is happening in this horrendous query:
        // In order to keep the number of queries run down, we try grab everything
        // we need in one query, and in an intelligent order.
        //
        // By running a query for each depth level, starting at the "lowest" depth
        // we are using up-to-date data when aggregating any competencies with children.
        //
        // This query will return a group of rows for every competency a user needs
        // reaggregating in. The SQL knows the user needs reaggregating by looking
        // for a competency_evidence field with the reaggregate field set.
        //
        // The group of rows for each competency/user includes one for each of the
        // evidence items, or child competencies for this competency. If either the
        // evidence item or the child competency has data relating to this particular
        // user's competency state in it, we try grab that data too and add it to the
        // related row.
        //
        // Cols returned:
        // evidenceid = the user's competency evidence record id
        // userid = the userid this all relates to
        // competencyid = the competency id
        // path = the competency's path, shows competency and parents, / delimited
        // aggregationmethod = the competency's aggregation method
        // proficienyexpected = the proficiency scale value for this competencies scale
        // itemid = the competencies evidence item id (if we are selecting an evidence item)
        // itemstatus = the competency evidence item status for this user
        // itemproficiency = the competency evidence item proficiency for this user
        // itemmodified = the competency evidence item's last modified time
        // childid = the competencies child id (this is either a child comp or an evidence item)
        // childmodified = the child competency evidence last modified time
        // childproficieny = the child competency evidence proficieny for this user.
        //
        $sql = "
            SELECT DISTINCT
                cr.id AS evidenceid,
                cr.userid,
                cr.proficiency AS currentproficiency,
                c.id AS competencyid,
                c.path,
                c.aggregationmethod,
                proficient.proficient AS proficiencyexpected,
                cc.evidenceid AS itemid,
                ccr.status AS itemstatus,
                ccr.proficiencymeasured AS itemproficiency,
                ccr.timemodified AS itemmodified,
                cc.childid AS childid,
                chldcr.timemodified AS childmodified,
                chldcr.proficiency AS childproficiency
            FROM
                (
                    SELECT
                        id AS evidenceid,
                        competencyid,
                        NULL AS childid
                    FROM
                        {comp_criteria}
                    UNION
                    SELECT
                        NULL AS evidenceid,
                        parentid AS competencyid,
                        id AS childid
                    FROM
                        {comp}
                    WHERE
                        parentid <> 0
                    AND frameworkid = :frameworkid
                    AND depthlevel <> :depthlevel
                ) cc
            INNER JOIN {comp} c ON cc.competencyid = c.id
            INNER JOIN {comp_record} cr ON cr.competencyid = c.id
            INNER JOIN {comp_scale_assignments} csa ON c.frameworkid = csa.frameworkid
            INNER JOIN
            (
                SELECT csv.scaleid, csv.id AS proficient
                FROM {comp_scale_values} csv
                INNER JOIN
                (
                    SELECT scaleid, MAX(sortorder) AS maxsort
                    FROM {comp_scale_values}
                    WHERE proficient = 1
                    GROUP BY scaleid
                ) grouped
                ON csv.scaleid = grouped.scaleid AND csv.sortorder = grouped.maxsort
            ) proficient
            ON csa.scaleid = proficient.scaleid
            LEFT JOIN {comp_criteria_record} ccr ON cc.evidenceid = ccr.itemid AND cr.userid = ccr.userid
            LEFT JOIN {comp_record} chldcr ON chldcr.competencyid = cc.childid AND cr.userid = chldcr.userid
            WHERE
                cr.reaggregate > 0
            AND cr.reaggregate <= :timestarted
            AND cr.manual = 0
            AND c.depthlevel = :depthlevel1
            AND c.aggregationmethod <> :aggregationmethod
            AND cr.userid = :userid
            ORDER BY
                competencyid
        ";

        $params = [
            'frameworkid' => $frameworkid,
            'depthlevel' => $depthlevel,
            'depthlevel1' => $depthlevel,
            'timestarted' => $this->timestarted,
            'aggregationmethod' => $COMP_AGGREGATION['OFF'],
            'userid' => $userid
        ];

        $rs = $DB->get_recordset_sql($sql, $params);

        // As we want to do the aggregation by competency
        // we group all evidence records first.
        $records_grouped = [];
        foreach ($rs as $record) {
            $record = (object)$record;
            if (!isset($records_grouped[$record->competencyid])) {
                $records_grouped[$record->competencyid] = [];
            }
            $records_grouped[$record->competencyid][] = $record;
        }
        $rs->close();

        return $records_grouped;
    }

    /**
     * @deprecated since Totara 12.0
     *
     * @param   $timestarted    int         Time we started aggregating
     * @param   $depth          object      Depth level record
     * @return  void
     * @throws coding_exception
     */
    protected function competency_cron_aggregate_evidence($timestarted, $depth) {
        throw new coding_exception('This method was replaced by aggregate_competency_evidence_items() which is now called for each user and depth/framework combination.');
    }

    /**
     * Aggregate competency's evidence items
     *
     * @param int $userid
     * @param array $evidence_records
     * @param job_assignment|null $job_assignment
     * @return void
     */
    private function aggregate_competency_evidence_items(int $userid, array $evidence_records, ?job_assignment $job_assignment): void {
        global $COMP_AGGREGATION;

        foreach ($evidence_records as $competencyid => $records) {
            if (debugging()) {
                mtrace('Aggregating competency items evidence for user '.$userid.' for competency '.$competencyid);
            }
            $aggregated_status = null;
            foreach ($records as $record) {
                // Get proficiency.
                $proficiency = max($record->itemproficiency, $record->childproficiency);
                if (!isset($this->scale_values[$record->proficiencyexpected])) {
                    if (debugging()) {
                        mtrace('Could not find proficiency expected scale value');
                    }
                    $aggregated_status = null;
                    break;
                }

                // Get item's scale value.
                $item_value = null;
                if (isset($this->scale_values[$proficiency])) {
                    $item_value = $this->scale_values[$proficiency];
                }

                // Get item's current proficiency scale value so we know if a proficiency has been set.
                $current_value = null;
                if (isset($this->scale_values[$record->currentproficiency])) {
                    $current_value = $this->scale_values[$record->currentproficiency];
                }

                // Get the competencies minimum proficiency.
                $min_value = $this->scale_values[$record->proficiencyexpected];

                // Flag to break out of aggregation loop (if we already have enough info).
                $stop_agg = false;

                // Handle different aggregation types.
                switch ($record->aggregationmethod) {
                    case $COMP_AGGREGATION['ALL']:
                        if (!$item_value || $item_value->proficient == 0) {
                            // Learner is not yet proficient so no action required.
                            $aggregated_status = null;
                            $stop_agg = true;
                        } else if ($current_value && $current_value->proficient == 1) {
                            // If a proficiency level has already been set - don't update it.
                            $aggregated_status = null;
                            $stop_agg = true;
                        } else {
                            // User is now proficient so set status to minimum proficiency value.
                            $aggregated_status = $min_value->id;
                        }
                        break;
                    case $COMP_AGGREGATION['ANY']:
                        if ($current_value && $current_value->proficient == 1) {
                            // Proficiency level has already been set - don't update it.
                            $aggregated_status = null;
                            $stop_agg = true;
                        } else if ($item_value && $item_value->proficient == 1) {
                            // User is now proficient, so set their proficiency value.
                            $aggregated_status = $min_value->id;
                            $stop_agg = true;
                        }
                        break;
                    default:
                        if (debugging()) {
                            mtrace('Aggregation method not supported: ' . $record->aggregationmethod);
                            mtrace('Skipping user...');
                        }
                        $aggregated_status = null;
                        $stop_agg = true;
                }

                if ($stop_agg) {
                    break;
                }
            }

            // If aggregated status is not null, update competency evidence.
            if ($aggregated_status !== null) {
                $this->update_competency_evidence($userid, $competencyid, $aggregated_status, $job_assignment);
            }
        }
    }

    /**
     * @param int $userid
     * @param int $competencyid
     * @param int $status
     * @param job_assignment|null $jobassignment
     */
    private function update_competency_evidence(int $userid, int $competencyid, int $status, ?job_assignment $jobassignment): void {
        if (debugging()) {
            mtrace('Update proficiency to ' . $status);
        }

        // Update the competency evidence.
        $details = new stdClass();
        if ($jobassignment) {
            $details->positionid = $jobassignment->positionid;
            $details->organisationid = $jobassignment->organisationid;
        }

        // Because we do not want to send the alerts, set $notify to false.
        // We also pass null as the component as we don't have that here.
        hierarchy_add_competency_evidence($competencyid, $userid, $status, null, $details, true, false);
        // Hook for plan auto completion.
        dp_plan_item_updated($userid, 'competency', $competencyid);
    }

    /**
     * @param int $userid
     */
    private function mark_as_aggregated(int $userid): void {
        global $DB;

        // Mark only aggregated evidence as aggregated.
        if (debugging()) {
            mtrace('Mark all aggregated evidence for user '.$userid.' as aggregated');
        }

        $sql = "
            UPDATE {comp_record}
            SET reaggregate = 0
            WHERE reaggregate <= :timestarted
              AND reaggregate > 0
              AND userid = :userid
        ";

        $params = [
            'timestarted' => $this->timestarted,
            'userid' => $userid,
        ];
        $DB->execute($sql, $params);
    }

}
