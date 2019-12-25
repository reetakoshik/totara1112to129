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

/**
 * Switches any expired courses with their new courses in recurring programs as necessary
 */
class switch_recurring_courses_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('switchrecurringcourses', 'totara_program');
    }

    /**
     * Checks if the enrolenddates in any courses in recurring programs have expired
     * and therefore need to be switched over as the new recurring course in the
     * program.
     *
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

        $recurring_programs = prog_get_recurring_programs();
        $program_plugin = enrol_get_plugin('totara_program');
        // Function get_archetype_roles returns an array, get the first element of it.
        $studentroles = get_archetype_roles('student');
        $studentrole = array_shift($studentroles);
        $now = time();
        foreach ($recurring_programs as $program) {

            $content = $program->get_content();
            $coursesets = $content->get_course_sets();

            // Retrieve the recurring course set.
            $courseset = $coursesets[0];

            // Retrieve the recurring course.
            $course = $courseset->course;

            // If the start date of the recurring course is in the future then
            // we don't need to switch over yet.
            if ($course->startdate > $now) {
                continue;
            }

            // Check that the next course has been created for this program.
            if ($recurrence_rec = $DB->get_record('prog_recurrence', array('programid' => $program->id,
                'currentcourseid' => $course->id))) {

                // Check that the next course actually exists.
                if ($newcourse = $DB->get_record('course', array('id' => $recurrence_rec->nextcourseid))) {
                    // Add the program enrolment plugin to this course.
                    $instanceid = $program_plugin->add_instance($newcourse);
                    $instance = $DB->get_record('enrol', array('id' => $instanceid));

                    // Before we set the new course in the program, we have to first save the history
                    // record of any users who have not completed the current course and notify
                    // those users that the course has been changed so that they can complete
                    // the course independently. They can view the record of their complete/incomplete
                    // recurring program history via a link in their record of learning.

                    // Query to retrieve all the users and their completion status.
                    $sql = "SELECT pc.id, completionid, completionstatus, u.*
                            FROM (SELECT DISTINCT
                                    userid AS id,
                                    id AS completionid,
                                    status AS completionstatus
                                FROM {prog_completion}
                                WHERE programid = ?
                                AND coursesetid = ?) pc
                            JOIN {user} u ON pc.id = u.id
                            WHERE u.username <> 'guest'
                            AND u.deleted = 0
                            AND u.confirmed = 1";

                    // Get all the users matching the query.
                    $users = $DB->get_records_sql($sql, array($program->id, 0));
                    foreach ($users as $user) {
                        // Enrol all users assigned to the program in the new course.
                        $program_plugin->enrol_user($instance, $user->id, $studentrole->id);

                        // Handle history and messaging for users who did not complete.
                        if ($user->completionstatus == STATUS_PROGRAM_INCOMPLETE) {
                            $transaction = $DB->start_delegated_transaction();
                            // Copy the existing completion records for the user in to the
                            // history table so that we have a record to show that the
                            // course has not been completed.
                            $select = "programid = ? AND userid = ? AND coursesetid = 0";
                            $params = array($program->id, $user->id);
                            $completion_records_history = $DB->get_records_select('prog_completion', $select, $params);
                            $backup_success = true;
                            foreach ($completion_records_history as $completion_record) {
                                // We need to store the id of the course that belonged to this recurring program at the time
                                // it was added to the history table so that we can report on the course history later if necessary.
                                $completion_record->recurringcourseid = $course->id;
                                $backup_success = $DB->insert_record('prog_completion_history', $completion_record);
                            }
                            $transaction->allow_commit();

                            // Send a message to the user to let them know that the course
                            // has changed and that they haven't completed it.
                            $stringmanager = get_string_manager();
                            $messagedata = new \stdClass();
                            $messagedata->userto = $user;
                            // Stop user from emailing themselves, use support instead.
                            $messagedata->userfrom = \core_user::get_support_user();
                            $messagedata->subject = $stringmanager->get_string('z:incompleterecurringprogramsubject',
                                    'totara_program', null, $user->lang);
                            $messagedata->fullmessage = $stringmanager->get_string('z:incompleterecurringprogrammessage',
                                    'totara_program', null, $user->lang);
                            $messagedata->contexturl = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
                            $messagedata->contexturlname = $stringmanager->get_string('launchcourse', 'totara_program',
                                    null, $user->lang);
                            $messagedata->icon = 'program-update';
                            $messagedata->msgtype = TOTARA_MSG_TYPE_PROGRAM;
                            $result = tm_alert_send($messagedata);
                        }
                    }

                    // Now we can make the next course visible and set it as the current course in the program.
                    $courseset->course = $newcourse;
                    $DB->update_record('course', (object)array('id' => $newcourse->id, 'visible' => true));
                    $courseset->save_set();
                }

                // Delete the record from the recurrence table (otherwise the system
                // won't create a new copy of the recurring course when this one
                // expires in the future).
                $DB->delete_records('prog_recurrence', array('programid' => $program->id, 'currentcourseid' => $course->id));
            }
        }
    }
}

