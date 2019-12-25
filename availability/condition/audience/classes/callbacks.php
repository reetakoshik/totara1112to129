<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package availability_audience
 */

namespace availability_audience;

defined('MOODLE_INTERNAL') || die();

/**
 * Callbacks for the availability audience plugin.
 */
class callbacks {

    /**
     * Callback to delete condition if the audience it uses gets
     * deleted
     *
     * @param \core\event\cohort_deleted $event Event data
     */
    public static function cohort_deleted(\core\event\cohort_deleted $event) {
        global $DB;

        // Cohort id.
        $cohortid = $event->objectid;

        $audiencetypelikesql = $DB->sql_like('availability', ':likeparam');
        $audienceidlikesql = $DB->sql_like('availability', ':likeparam2');
        $params = array('likeparam' => '%"type":"audience"%', 'likeparam2' => '%"cohort":"'.$cohortid.'"%');

        $sql = "SELECT id, course, availability FROM {course_modules} WHERE availability != '' AND {$audiencetypelikesql} AND {$audienceidlikesql}";

        $module_records = $DB->get_records_sql($sql, $params);

        $updated_records = array();
        $courses = array();

        foreach ($module_records as $record) {
            $availability = $record->availability;
            $availability_data = json_decode($availability);
            $changed = false;

            foreach ($availability_data->c as $key => $condition) {
                if ($condition->type == 'audience' && $condition->cohort == $cohortid) {
                    unset($availability_data->c[$key]);
                    unset($availability_data->showc[$key]);
                    $changed = true;
                }
            }

            // The condition has changed.
            if ($changed) {
                // Reindex arrays
                $availability_data->c = array_values($availability_data->c);
                $availability_data->showc = array_values($availability_data->showc);

                if (!empty($availability_data->c)) {
                    $encoded = json_encode($availability_data);
                } else {
                    $encoded = '';
                }

                $updated_records[] = array('id' => $record->id, 'availability' => $encoded);
                $courses[$record->course] = $record->course;
            }
        }

        // Update course module records
        foreach ($updated_records as $update) {
            // Do this fast!
            $DB->update_record_raw('course_modules', $update, true);
        }

        // Rebuild the course caches for any of the courses
        // we changed
        foreach ($courses as $courseid) {
            rebuild_course_cache($courseid, true);
        }

        unset($updated_records);
        unset($courses);
    }
}
