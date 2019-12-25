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
 * Check if any courses in recurring programs that were not completed when
 * the recurring course was switched to a newer version of the course
 * have subsequently been completed and mark them as complete in the
 * history table.
 */
class recurrence_history_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('recurrencehistorytask', 'totara_program');
    }

    /**
     * Finds any users in the 'prog_completion_history' table who have incomplete
     * recurring programs and checks if the course that belonged to the program at
     * the time when the entry was added to the table has since been completed.
     *
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

        $history_records = $DB->get_records('prog_completion_history', array('status' => STATUS_PROGRAM_INCOMPLETE));

        foreach ($history_records as $history_record) {

            if ($course = $DB->get_record('course', array('id' => $history_record->recurringcourseid))) {

                // Create a new completion object for this course.
                $completion_info = new \completion_info($course);
                // Check if the course is complete.
                if ($completion_info->is_course_complete($history_record->userid)) {
                    $history_record->status = STATUS_PROGRAM_COMPLETE;
                    $history_record->timecompleted = time();
                    $DB->update_record('prog_completion_history', $history_record);
                }
            }
        }
    }
}

