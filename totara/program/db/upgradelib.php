<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@toraralearning.com>
 * @package totara_program
 */

/**
 * Timestarted was being set on assignment, making it more
 * of a timecreated. There is an upgrade creating the timecreated
 * column and moving the data across to it, then attempting to set
 * the programs timestarted based off of the programs content
 * course_completions.timestarted field.
 */
function totara_program_fix_timestarted() {
    global $DB;

    $sql = "SELECT * FROM {prog_completion} WHERE coursesetid <> 0";
    $coursesetcompl = $DB->get_records_sql($sql);
    foreach ($coursesetcompl as $cscomp) {
        // Check each course in the courseset, and get the minimum time started.
        // If there is no time started then just leave it as 0.
        $startsql = "SELECT MIN(comp.timestarted)
                       FROM {prog_courseset_course} crs
                 INNER JOIN {course_completions} comp
                         ON comp.course = crs.courseid
                      WHERE comp.timestarted > 0
                        AND comp.userid = :uid
                        AND crs.coursesetid = :csid
                   GROUP BY crs.coursesetid";
        $startparams = array('uid' => $cscomp->userid, 'csid' => $cscomp->coursesetid);
        $minstart = $DB->get_field_sql($startsql, $startparams);

        if (!empty($minstart)) {
            $cscomp->timestarted = $minstart > $cscomp->timecreated ? $minstart : $cscomp->timecreated;
            $DB->update_record('prog_completion', $cscomp);
        }
    }

    // Now we have the timestarted for the coursesets we use that to get the program timestarted.
    $sql = "SELECT * FROM {prog_completion} WHERE coursesetid = 0";
    $progcompl = $DB->get_records_sql($sql);
    foreach ($progcompl as $pcomp) {
        // Check each courseset in the program, and get the minimum time started.
        // If there is no time started then just leave it as 0.
        $startsql = "SELECT MIN(pc.timestarted)
                       FROM {prog_completion} pc
                      WHERE pc.coursesetid > 0
                        AND pc.timestarted > 0
                        AND pc.programid = :pid
                        AND pc.userid = :uid
                   GROUP BY pc.programid";
        $startparams = array('uid' => $pcomp->userid, 'pid' => $pcomp->programid);
        $minstart = $DB->get_field_sql($startsql, $startparams);

        if (!empty($minstart)) {
            $pcomp->timestarted = $minstart > $pcomp->timecreated ? $minstart : $pcomp->timecreated;
            $DB->update_record('prog_completion', $pcomp);
        }
    }

    return true;
}

/**
 * Remove orphaned courseset completions from the program completion table
 */
function totara_program_remove_orphaned_courseset_completions() {
    global $DB;

    // Remove orphaned courseset completion records
    $deletecompletionsql = 'DELETE FROM {prog_completion}
                             WHERE coursesetid <> 0
                               AND coursesetid NOT IN ( SELECT id
                                                          FROM {prog_courseset}
                                                      )
                           ';

    $DB->execute($deletecompletionsql);

    return true;
}
