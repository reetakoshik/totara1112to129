<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

namespace totara_appraisal;

defined('MOODLE_INTERNAL') || die();

class current_stage_editor {

    /**
     * Finds which stage the given users are currently on.
     *
     * @param int $appraisalid
     * @param int[] $userids
     * @return array of stdClass with userid, name (of stage), timecompleted, status and jobassignmentid
     */
    public static function get_stages_for_users(int $appraisalid, array $userids) {
        global $DB;

        if (empty($userids)) {
            return array();
        }

        list($userssql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $sql = "SELECT aua.userid, stage.name, aua.timecompleted, aua.status, aua.jobassignmentid
                  FROM {appraisal_user_assignment} aua
                  JOIN {appraisal_stage} stage ON aua.activestageid = stage.id
                 WHERE aua.userid {$userssql} AND aua.appraisalid = :appraisalid";

        $params['appraisalid'] = $appraisalid;
        $currentstages = $DB->get_records_sql($sql, $params);

        return $currentstages;
    }

    /**
     * @param int $appraisalid
     * @param int $learnerid
     * @param int $roleassignmentid either a role assignment id or -1 to indicate all roles
     * @param int $stageid
     */
    public static function set_stage_for_role_assignment(int $appraisalid, int $learnerid, int $roleassignmentid, int $stageid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/totara/appraisal/lib.php');

        $transaction = $DB->start_delegated_transaction();

        // Main appraisal user assignment record needs to be reset.
        $userassignment = $DB->get_record(
            'appraisal_user_assignment',
            array('userid' => $learnerid, 'appraisalid' => $appraisalid),
            'id'
        );
        $userassignment->activestageid = $stageid;
        $userassignment->timecompleted = null;
        $userassignment->status = \appraisal::STATUS_ACTIVE;
        $DB->update_record('appraisal_user_assignment', $userassignment);

        // Find all stages from the target stage up.
        $stages = \appraisal_stage::get_stages($appraisalid);
        $stageidstodelete = [];
        $deleting = false;
        foreach ($stages as $stage) {
            if ($stage->id == $stageid) {
                $deleting = true;
            }
            if ($deleting) {
                $stageidstodelete[] = $stage->id;
            }
        }

        if (empty($stageidstodelete)) {
            throw new \coding_exception("Cannot find any stage to unlock");
        }

        list($insql, $inparams) = $DB->get_in_or_equal($stageidstodelete, SQL_PARAMS_NAMED);

        if ($roleassignmentid != -1) {
            // All stage completion records for the role.
            $select = "appraisalstageid {$insql} AND appraisalroleassignmentid = :roleassignmentid";
            $DB->delete_records_select(
                'appraisal_stage_data',
                $select,
                array_merge(array('roleassignmentid' => $roleassignmentid), $inparams)
            );
        } else {
            // All stage completion records for all roles.
            $select = "appraisalstageid {$insql}
                   AND appraisalroleassignmentid IN
                        (SELECT id
                           FROM {appraisal_role_assignment}
                          WHERE appraisaluserassignmentid = :userassignmentid)";
            $DB->delete_records_select(
                'appraisal_stage_data',
                $select,
                array_merge(array('userassignmentid' => $userassignment->id), $inparams)
            );
        }

        // All affected stages scheduled messages for the learner.
        $select = "userid = :userid AND eventid IN (SELECT id FROM {appraisal_event} WHERE appraisalstageid {$insql})";
        $DB->delete_records_select(
            'appraisal_user_event',
            $select,
            array_merge(array('userid' => $learnerid), $inparams)
        );

        if ($roleassignmentid != -1) {
            // Change the active page for the role (this is a nicety).
            $DB->set_field(
                'appraisal_role_assignment',
                'activepageid',
                null, // The first page will be automatically selected.
                array('id' => $roleassignmentid)
            );
        } else {
            // Change the active page for all roles (this is a nicety).
            $DB->set_field(
                'appraisal_role_assignment',
                'activepageid',
                null, // The first page will be automatically selected.
                array('appraisaluserassignmentid' => $userassignment->id)
            );
        }

        $transaction->allow_commit();
    }
}
