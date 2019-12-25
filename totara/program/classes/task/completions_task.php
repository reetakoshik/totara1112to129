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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_program
 */

namespace totara_program\task;

class completions_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('completionstask', 'totara_program');
    }

    /**
     * Determine whether or not any users have completed any programs
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Don't run programs cron if programs and certifications are disabled.
        if (totara_feature_disabled('programs') &&
            totara_feature_disabled('certifications')) {
            return false;
        }

        // Query to retrive any users who are registered on the program
        $sql = "SELECT pc.id, pc.programid, pc.userid
                  FROM {prog_completion} pc
                 WHERE pc.coursesetid = 0
                   AND pc.userid IN ( SELECT pua.userid
                                        FROM {prog_user_assignment} pua
                                       WHERE pua.programid = pc.programid
                                         AND pua.userid = pc.userid
                                         AND pua.exceptionstatus <> :raised
                                         AND pua.exceptionstatus <> :dismissed
                                       UNION
                                      SELECT pln.userid
                                        FROM {dp_plan_program_assign} ppa
                                  INNER JOIN {dp_plan} pln
                                          ON pln.id = ppa.planid
                                       WHERE pln.userid = pc.userid
                                         AND ppa.programid = pc.programid
                                         AND ppa.approved >= :dpappr
                                         AND pln.status >= :dpstat
                                    )
                   AND pc.status = :stat
              ORDER BY pc.programid";

        $params = array(
            'raised' => PROGRAM_EXCEPTION_RAISED,
            'dismissed' => PROGRAM_EXCEPTION_DISMISSED,
            'dpappr' => DP_APPROVAL_APPROVED,
            'dpstat' => DP_PLAN_STATUS_APPROVED,
            'stat' => STATUS_PROGRAM_INCOMPLETE
        );

        $records = $DB->get_records_sql($sql, $params);
        $program = null;
        foreach ($records as $record) {
            if (empty($program) || $program->id != $record->programid) {
                $program = new \program($record->programid);
            }

            prog_update_completion($record->userid, $program, null, false);
        }

        return true;
    }
}
