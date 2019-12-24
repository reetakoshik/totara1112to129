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
 * Tidy up enrolment plugins on courses.
 */
class clean_enrolment_plugins_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanenrolmentpluginstask', 'totara_program');
    }

    /**
     * Checks if the enrolment plugin is enabled in any courses which are part of programs and ensures
     * the plugin is enabled (when required) or removed (if no longer required)
     *
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Don't run programs cron if programs and certifications are disabled.
        if (totara_feature_disabled('programs') && totara_feature_disabled('certifications')) {
            // Note that managers may deleted the program enrol instances manually if necessary.
            return false;
        }

        // Get program enrolment plugin.
        /* @var \enrol_totara_program_plugin $program_plugin */
        $program_plugin = enrol_get_plugin('totara_program');

        // Fix user enrolments for all programs.
        if (!prog_update_available_enrolments($program_plugin, null, debugging())) {
            return false;
        };

        // Fix courses that are in a courseset but do not have the enrolment plugin.
        $program_courses = prog_get_courses_associated_with_programs();

        // Now we remove totara_program entries for courses that are NOT in coursesets -
        // Need to check if they are linked to a program via a competency.
        $params = array('totara_program');
        if (count($program_courses) > 0) {
            list($notinsql, $notinparams) = $DB->get_in_or_equal(array_keys($program_courses), SQL_PARAMS_QM, 'param', false);
            $courseidclause = " AND courseid $notinsql";
            $params = array_merge($params, $notinparams);
        } else {
            $courseidclause = '';
        }
        $sql = "SELECT DISTINCT courseid
                    FROM {enrol}
                    WHERE enrol = ?
                    $courseidclause";
        $unused_program_courses = $DB->get_recordset_sql($sql, $params);
        foreach ($unused_program_courses as $course) {
            $instance = $program_plugin->get_instance_for_course($course->courseid);
            if ($instance) {
                $program_plugin->delete_instance($instance);
            }
        }
    }
}

