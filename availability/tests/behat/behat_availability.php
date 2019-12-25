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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability
 */

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

class behat_availability extends behat_base {

    /**
     * Rewinds activity completion dates for a particular user and course.
     *
     * @Given /^I rewind completion dates for "([^"]*)" in "([^"]*)" by "([0-9]*)" "([^"]*)"$/
     *
     * @param string $username
     * @param string $coursefullname
     * @param string $amount The amount of days, weeks, years etc.
     * @param string $period Days, weeks or years
     */
    public function rewind_activity_completion_for_user_in_course($username, $coursefullname, $amount, $period) {
        global $DB;

        // Get seconds to rewind completion by.
        switch ($period) {
            case "days":
                $rewind = $amount * DAYSECS;
                break;
            case "weeks":
                $rewind = $amount * WEEKSECS;
                break;
            case "years":
                $rewind = $amount * YEARSECS;
                break;
            default:
                $rewind = 0;
        }

        $course = $DB->get_record("course", array("fullname" => $coursefullname), 'id', MUST_EXIST);
        $userid = $DB->get_field("user", "id", array("username" => $username), MUST_EXIST);

        $sql = "SELECT cmc.* 
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm
                    ON cm.id = cmc.coursemoduleid
                 WHERE cmc.userid = :userid
                   AND cm.course = :courseid";

        $params = array('userid' => $userid, 'courseid' => $course->id);

        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $newtime = $record->timemodified - $rewind;
            $newtime = $newtime < 0 ? 0 : $newtime;
            $record->timemodified = $newtime;
            $DB->update_record("course_modules_completion", $record);
        }

        $completion = new completion_info($course);
        $completion->invalidatecache();
    }

}
