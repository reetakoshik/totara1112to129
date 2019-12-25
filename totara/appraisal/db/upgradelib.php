<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

require_once($CFG->dirroot.'/totara/appraisal/lib.php');

// TL-15900 Update team leaders in dynamic appraisals.
// Due to a bug in job assignments, the timemodified was not being updated when the
// manager path was updated. After fixing it, we need to make sure that all appraisal
// team leaders will be updated in dynamic appraisals. Just reduce the user assignment
// jobassignmentlastmodified field where the current team lead doesn't match.
function totara_appraisal_upgrade_update_team_leaders() {
    global $DB;

    // A team leader job assignment exists, but no team leader has been assigned in the appraisal.
    $sql = "UPDATE {appraisal_user_assignment}
               SET jobassignmentlastmodified = 0
             WHERE jobassignmentlastmodified > 0
               AND EXISTS (SELECT 1
                     FROM {job_assignment} learnerja
                     JOIN {job_assignment} managerja
                       ON learnerja.managerjaid = managerja.id
                     JOIN {job_assignment} teamleadja
                       ON managerja.managerjaid = teamleadja.id
                    WHERE learnerja.id = {appraisal_user_assignment}.jobassignmentid)
           AND NOT EXISTS (SELECT 1
                     FROM {appraisal_role_assignment} teamleadra
                    WHERE teamleadra.appraisaluserassignmentid = {appraisal_user_assignment}.id
                      AND teamleadra.appraisalrole = :teamleaderrole)";
    $DB->execute($sql, array('teamleaderrole' => appraisal::ROLE_TEAM_LEAD));

    // A team leader has been assigned in the appraisal, but no team leader job assignment exists.
    $sql = "UPDATE {appraisal_user_assignment}
               SET jobassignmentlastmodified = 0
             WHERE jobassignmentlastmodified > 0
               AND EXISTS (SELECT 1
                     FROM {appraisal_role_assignment} teamleadra
                    WHERE teamleadra.appraisaluserassignmentid = {appraisal_user_assignment}.id
                      AND teamleadra.appraisalrole = :teamleaderrole
                      AND teamleadra.userid <> 0)
           AND NOT EXISTS (SELECT 1
                     FROM {job_assignment} learnerja
                     JOIN {job_assignment} managerja
                       ON learnerja.managerjaid = managerja.id
                     JOIN {job_assignment} teamleadja
                       ON managerja.managerjaid = teamleadja.id
                    WHERE learnerja.id = {appraisal_user_assignment}.jobassignmentid)";
    $DB->execute($sql, array('teamleaderrole' => appraisal::ROLE_TEAM_LEAD));

    // Both exist, but they don't have matching users.
    $sql = "UPDATE {appraisal_user_assignment}
               SET jobassignmentlastmodified = 0
             WHERE jobassignmentlastmodified > 0
               AND EXISTS (SELECT 1
                     FROM {job_assignment} learnerja
                     JOIN {job_assignment} managerja
                       ON learnerja.managerjaid = managerja.id
                     JOIN {job_assignment} teamleadja
                       ON managerja.managerjaid = teamleadja.id
                     JOIN {appraisal_role_assignment} teamleadra
                       ON teamleadra.appraisalrole = :teamleaderrole
                    WHERE learnerja.id = {appraisal_user_assignment}.jobassignmentid
                      AND teamleadra.appraisaluserassignmentid = {appraisal_user_assignment}.id
                      AND (teamleadja.userid <> teamleadra.userid AND teamleadja.userid IS NOT NULL OR
                           teamleadra.userid = 0))";
    $DB->execute($sql, array('teamleaderrole' => appraisal::ROLE_TEAM_LEAD));
}

/**
 * TL-16443 Make all multichoice questions use int for param1.
 *
 * Whenever someone created a new scale for their question, it would store it as an integer in the param1 text field.
 * However, when using an existing scale, it would record the scale id with quotes around it. This caused a failure
 * in some sql. To make everything consistent and easier to process, we're changing them all to integers in text
 * fields, without quotes.
 */
function totara_appraisal_upgrade_fix_inconsistent_multichoice_param1() {
    global $DB;

    list($sql, $params) = $DB->sql_text_replace('param1', '"', '', SQL_PARAMS_NAMED);

    $sql = "UPDATE {appraisal_quest_field}
               SET {$sql}
             WHERE datatype IN ('multichoicemulti', 'multichoicesingle')
               AND " . $DB->sql_like('param1', ':colon', true, true, true) . "
               AND " . $DB->sql_like('param1', ':bracket', true, true, true) . "
               AND " . $DB->sql_like('param1', ':braces', true, true, true);
    $params['colon'] = '%:%';
    $params['bracket'] = '%[%';
    $params['braces'] = '%{%';

    $DB->execute($sql, $params);
}

/**
 * TL-17131 Appraisal snapshots not deleted when user is deleted.
 *
 * Clears any appraisal snapshots from the files table; these were previously
 * not removed when a learner's appraisal itself was deleted.
 */
function totara_appraisal_remove_orphaned_snapshots() {
    global $DB;

    // When an appraisal is deleted, records in the appraisal_role_assignment
    // table are removed but snapshot entries in the files table still link to
    // these records via the files.itemid column. So this code removes all the
    // dangling snapshot entries.

    $sql = "
      SELECT f.component, f.filearea, f.itemid
        FROM {files} f
       WHERE f.component = 'totara_appraisal'
         AND f.filearea like 'snapshot%'
         AND NOT EXISTS (
             SELECT 1
               FROM {appraisal_role_assignment} a
              WHERE a.id = f.itemid
       )
    ";

    $context = context_system::instance()->id;
    $fs = get_file_storage();
    $results = $DB->get_recordset_sql($sql);

    foreach($results as $rs) {
        $fs->delete_area_files($context, $rs->component, $rs->filearea, $rs->itemid);
    }
    $results->close();
}
