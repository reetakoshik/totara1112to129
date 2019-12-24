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

class recurrence_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('recurrencetask', 'totara_program');
    }

    /**
     * Check if any users are due to re-take any recurring programs
     *
     * Note: This should be done before program_cron_user_assignments() as the
     * recurrence task removes assignments so that they can be re-assigned if necessary
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Don't run programs cron if programs and certifications are disabled.
        if (totara_feature_disabled('programs') &&
            totara_feature_disabled('certifications')) {
            return false;
        }

        $recurring_programs = prog_get_recurring_programs();

        foreach ($recurring_programs as $program) {

            $content = $program->get_content();
            $coursesets = $content->get_course_sets();

            // Retrieve the recurring course set.
            $courseset = $coursesets[0];

            // Retrieve the recurring course.
            $recurringcourse = $courseset->course;

            $now = time();
            $recurrencetime = $courseset->recurrencetime;
            $recurrencetime_comparison = $now - $recurrencetime;

            // Query to retrieve all the users assigned to this program (i.e. as
            // part of their required learning) who have completed the program
            // and whose completion dates are beyond the recurrence time period.
            $sql = "SELECT pcpua.id, completionid, userassignmentid, assignmentid, u.*
                    FROM {user} u
                    JOIN (SELECT DISTINCT
                            pc.userid AS id,
                            pc.id AS completionid,
                            pua.id AS userassignmentid,
                            pua.assignmentid
                        FROM {prog_completion} pc
                        JOIN {prog_user_assignment} pua
                        ON pc.userid = pua.userid
                        WHERE pc.programid = ?
                        AND pc.status = ?
                        AND pc.coursesetid = ?
                        AND pc.timecompleted < ?) AS pcpua
                    ON u.id = pcpua.id";

            // Get all the users matching the query.
            $users = $DB->get_records_sql($sql, array($program->id, STATUS_PROGRAM_COMPLETE, 0, $recurrencetime_comparison));
            foreach ($users as $user) {

                $transaction = $DB->start_delegated_transaction();

                // Copy the existing completion records for the user in to a
                // history table so that we have a record of past completions.
                $select = "programid = ? AND userid = ?";
                $params = array($program->id, $user->id);
                $completion_records_history = $DB->get_records_select('prog_completion', $select, $params);
                $backup_success = true;
                foreach ($completion_records_history as $completion_record) {
                    // We need to store the id of the course that belonged to this recurring program at the time
                    // it was added to the history table so that we can report on the course history later if necessary.
                    $completion_record->recurringcourseid = $recurringcourse->id;

                    $DB->insert_record('prog_completion_history', $completion_record);
                }

                // Delete all the previous completion records for this user in this program.
                // A new completion record will be added when the user is re-assigned when the
                // assignments cron task runs.
                $DB->delete_records('prog_completion', array('programid' => $program->id, 'userid' => $user->id));

                // Delete the user's assignment record for this program.
                // This will be re-created and the user will be re-assigned to the program
                // when the assignments cron task runs.
                $DB->delete_records('prog_user_assignment', array('programid' => $program->id, 'userid' => $user->id));

                $transaction->allow_commit();
            }
        }
    }
}

