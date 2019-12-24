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

class availability_checks_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('availabilitycheckstask', 'totara_program');
    }

    /**
     * Checks whether programs are available or not
     * if they are switched to unavailable checks course enrolments
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

        $now = time();
        $debugging = debugging();
        $unavailable = $DB->get_records('prog', array('available' => AVAILABILITY_NOT_TO_STUDENTS));
        $available = $DB->get_records('prog', array('available' => AVAILABILITY_TO_STUDENTS));
        $program_plugin = enrol_get_plugin('totara_program');

        // Check unavailable programs haven't become available.
        foreach ($unavailable as $program) {
            if (CLI_SCRIPT && $debugging) {
                mtrace("Checking if Program-{$program->id} is still unavailable...");
            }

            if ((empty($program->availablefrom) || $program->availablefrom < $now) &&
                (empty($program->availableuntil) || $program->availableuntil > $now)) {

                if (CLI_SCRIPT && $debugging) {
                    mtrace("Marking Program-{$program->id} as available.");
                }

                // Mark program as available.
                $program->available = AVAILABILITY_TO_STUDENTS;
                $DB->update_record('prog', $program);
            }
        }

        // Check available programs haven't become unavailable.
        foreach ($available as $program) {
            if (CLI_SCRIPT && $debugging) {
                mtrace("Checking if Program-{$program->id} is still available...");
            }

            if ((!empty($program->availablefrom) && $program->availablefrom >= $now) ||
                (!empty($program->availableuntil) && $program->availableuntil <= $now)) {

                if (CLI_SCRIPT && $debugging) {
                    mtrace("Marking Program-{$program->id} as unavailable...");
                }

                // Mark program as unavailable.
                $program->available = AVAILABILITY_NOT_TO_STUDENTS;
                $DB->update_record('prog', $program);

                // Update course enrolments for the program.
                prog_update_available_enrolments($program_plugin, $program->id, $debugging);
            }
        }
    }
}

