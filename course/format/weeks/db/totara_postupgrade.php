<?php

/*
 * This file is part of Totara Learn
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package format_weeks
 */

/**
 * Function for Totara specific DB changes to core Moodle plugins.
 *
 * Put code here rather than in db/upgrade.php if you need to change core
 * Moodle database schema for Totara-specific changes.
 *
 * This is executed during EVERY upgrade. Make sure your code can be
 * re-executed EVERY upgrade without problems.
 *
 * You need to increment the upstream plugin version by .01 to get
 * this code executed!
 *
 * Do not use savepoints in this code!
 *
 * @param string $version the plugin version
 */
function xmldb_format_weeks_totara_postupgrade($version) {
    global $DB;

     // If the site was upgraded from Moodle 3.3.1+ the numsections format option does not exist as Moodle removed it.
     // This method finds all courses in 'weeks' format that don't have the 'numsections' course format option
     // and recreates it by using the actual number of sections.

    $sql = "SELECT c.id, count(cs.section) AS sectionsactual
            FROM {course} c
            JOIN {course_sections} cs ON cs.course = c.id
            LEFT JOIN {course_format_options} n ON n.courseid = c.id AND
                n.format = c.format AND
                n.name = 'numsections' AND
                n.sectionid = 0
            WHERE c.format = :format AND cs.section > 0 AND n.id IS NULL
            GROUP BY c.id";

    $format = 'weeks';

    $params = ['format' => $format];

    $actuals = $DB->get_records_sql_menu($sql, $params);

    foreach ($actuals as $courseid => $sectionsactual) {
        $record = (object)[
            'courseid' => $courseid,
            'format' => $format,
            'sectionid' => 0,
            'name' => 'numsections',
            'value' => $sectionsactual
        ];
        $DB->insert_record('course_format_options', $record);
    }
}
