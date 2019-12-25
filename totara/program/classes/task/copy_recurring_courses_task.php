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
 * Makes copies of any recurring courses as necessary
 */
class copy_recurring_courses_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('copyrecurringcoursestask', 'totara_program');
    }

    /**
     * Checks if any courses in recurring programs are due to have new copies made
     * based on the enrolment end dates of the course. If any are found that need to
     * be copied, a backup and restore is carried out and a record is added to the
     * 'prog_recurrence' table to enable the system to know that the course has been
     * copied.
     *
     */
    public function execute() {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Don't run programs cron if programs are disabled.
        if (totara_feature_disabled('programs')) {
            return false;
        }

        $recurring_programs = prog_get_recurring_programs();
        $now = time();
        foreach ($recurring_programs as $program) {

            $content = $program->get_content();
            $coursesets = $content->get_course_sets();

            // Retrieve the recurring course set.
            $courseset = $coursesets[0];

            // Retrieve the recurring course.
            $course = $courseset->course;

            // If the start date of the recurring course is too far in the
            // future (based on the recurcreatetime value set by the program creator)
            // we don't need to create the new course yet.
            if (($course->startdate + $courseset->recurrencetime - $now) > $courseset->recurcreatetime) {
                continue;
            }

            // Check if a course has already been created for this program. If so,
            // and the course actually exists, we don't need to do anything.
            if ($recurrence_rec = $DB->get_record('prog_recurrence', array('programid' => $program->id,
                'currentcourseid' => $course->id))) {
                if ($DB->record_exists('course', array('id' => $recurrence_rec->nextcourseid))) {
                    continue;
                } else {
                    // This means the next course must have been deleted so we need to create a new one.
                    $DB->delete_records('prog_recurrence', array('programid' => $program->id, 'currentcourseid' => $course->id));
                }
            }

            // So if processing has reached this far it means the existing course
            // needs to be backed up and restored to a new course.

            // Backup course.
            $bc = new \backup_controller(\backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,\ backup::MODE_GENERAL, $USER->id);
            $bc->update_plan_setting('userscompletion', 0);

            // Set userinfo to false to avoid restoring grades into the new course.
            $plan = $bc->get_plan();
            $settings = $plan->get_settings();
            $sections = $DB->get_fieldset_select('course_sections', 'id', 'course = :cid', array('cid' => $course->id));

            foreach ($sections as $section) {
                $settings["section_{$section}_userinfo"]->set_value(false);
            }

            $bc->execute_plan();
            $debugging = debugging();
            if ($backupfile = $bc->get_results()) {
                if ($debugging) {
                    mtrace("Course '{$course->fullname}' with id {$course->id} successfully backed up");
                }

                $backupfile = $backupfile['backup_destination'];
                $bc->destroy();

                $fullname = $course->fullname;
                if (preg_match('/ ([0-9]{2}\/[0-9]{2}\/[0-9]{4})$/', $fullname)) {
                    $fullname = substr($fullname, 0, -11);
                }
                $shortname = $course->shortname;
                if (preg_match('/\-([0-9]{2}\/[0-9]{2}\/[0-9]{4})$/', $shortname)) {
                    $shortname = substr($shortname, 0, -12);
                }

                $context = \context_course::instance($course->id);

                // Unzip backup to a temporary folder.
                $tempfolder = time() . $USER->id;
                $fulltempdir = make_temp_directory('/backup/' . $tempfolder);
                $backupfile->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $fulltempdir);

                // Execute in transaction to prevent course creation if restore fails.
                $transaction = $DB->start_delegated_transaction();

                if ($newcourseid = \restore_dbops::create_new_course($fullname, $shortname, $course->category)) {
                    $rc = new \restore_controller($tempfolder, $newcourseid, \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE,
                        $USER->id, \backup::TARGET_NEW_COURSE);
                    $rc->execute_precheck();
                    $rc->execute_plan();

                    // Update properties of a new course.
                    $newstartdate = $now + $courseset->recurcreatetime;
                    $datestr = userdate($newstartdate, '%d/%m/%Y', null, false);
                    $DB->update_record('course', (object)array(
                        'id' => $newcourseid,
                        'shortname' => $shortname . '-' . trim($datestr),
                        'fullname' => $fullname . ' ' . trim($datestr),
                        'icon' => $course->icon,
                        'startdate' => $newstartdate,
                        'visible' => false
                    ));

                    // Update enrolment dates for each user.
                    $enrolments = $DB->get_records_sql("
                            SELECT uenr.id
                            FROM
                                {user_enrolments} uenr
                            INNER JOIN {enrol} enr
                                ON uenr.enrolid = enr.id
                            WHERE enr.courseid = ?", array($newcourseid));

                    foreach ($enrolments as $enrolment) {
                        $DB->update_record('user_enrolments', (object)array(
                            'id' => $enrolment->id,
                            'timestart' => $newstartdate
                        ));
                    }

                    $DB->set_field('course_completions', 'timeenrolled', $newstartdate,
                                   array('course' => $newcourseid));

                    if ($debugging) {
                        mtrace("Course '{$fullname}' with id {$newcourseid} was successfully restored");
                    }

                    $transaction->allow_commit();

                    // Create a new record to enable the system to find the new course
                    // when it is time to switch the old course for the new course
                    // in the recurring program.
                    $new_recurrence_rec = new \stdClass();
                    $new_recurrence_rec->programid = $program->id;
                    $new_recurrence_rec->currentcourseid = $course->id;
                    $new_recurrence_rec->nextcourseid = $newcourseid;
                    $DB->insert_record('prog_recurrence', $new_recurrence_rec);
                } else {
                    if ($debugging) {
                        mtrace("Backup file was NOT successfully restored because a new course could not be created to complete the restore");
                    }
                }
            } else {
                if ($debugging) {
                    mtrace("Course with id {$course->id} was NOT backed up");
                }
            }
        }
    }
}

