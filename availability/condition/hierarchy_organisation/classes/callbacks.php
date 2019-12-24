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
 * @package availability_hierarchy_organisation
 */

namespace availability_hierarchy_organisation;

defined('MOODLE_INTERNAL') || die();

/**
 * Callbacks for the availability hierarchy organisation.
 */
class callbacks {

    /**
     * Callback to delete the condition if the Organisation is uses gets
     * deleted.
     *
     * @param \hierarchy_organisation\event\organisation_deleted $event Event data
     */
    public static function organisation_deleted(\hierarchy_organisation\event\organisation_deleted $event) {
        global $DB;

        // Organisation ID.
        $orgid = $event->objectid;

        $orgtypelikesql = $DB->sql_like('availability', ':likeparam');
        $orgidlikesql = $DB->sql_like('availability', ':likeparam2');
        $params = array('likeparam' => '%"type":"hierarchy_organisation"%', 'likeparam2' => '%"organisation":"'.$orgid.'"%');

        $sql = "SELECT * FROM {course_modules} WHERE availability != '' AND {$orgtypelikesql} AND {$orgidlikesql}";

        $module_records = $DB->get_records_sql($sql, $params);

        $updated_records = array();
        $courses = array();

        foreach ($module_records as $record) {
            $availability = $record->availability;
            $availability_data = json_decode($availability);
            $changed = false;

            foreach ($availability_data->c as $key => $condition) {
                if ($condition->type == 'hierarchy_organisation' && $condition->organisation == $orgid) {
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
            $DB->update_record('course_modules', $update, true);
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
