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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_program
 */

namespace totara_program\task;

/**
 * Process any user assignment changes that have been deferred until cron.
 */
class assignments_deferred_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('assignmentsdeferredtask', 'totara_program');
    }

    /**
     * Updates the learner assignments for all programs that have had user assignments changes deferred.
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

        // Get all programs that have the updateassignments flag set.
        $program_records = $DB->get_records('prog', array('assignmentsdeferred' => 1));
        foreach ($program_records as $program_record) {
            $program = new \program($program_record->id);
            $program->update_learner_assignments(true);
        }
    }
}
