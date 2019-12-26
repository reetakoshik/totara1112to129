<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_program
 */

namespace totara_program\assignment;

class manager extends base {

    const ASSIGNTYPE_MANAGER = 6;

    public function get_type(): int {
        return self::ASSIGNTYPE_MANAGER;
    }

    public function get_name() : string {
        global $DB, $CFG;

        // Include job lib so we can use display function
        require_once($CFG->dirroot . '/totara/job/lib.php');

        $namefields = get_all_user_name_fields(true, 'u');
        $params = ['assignmentid' => $this->instanceid];

        $record = $DB->get_record_sql("
                            SELECT {$namefields}, ja.fullname as jobfullname, ja.idnumber as jobidnumber
                            FROM {user} u
                            JOIN {job_assignment} ja
                            ON ja.userid = u.id
                            WHERE ja.id = :assignmentid",
            $params);

        $job = new \stdClass();
        $job->idnumber = $record->jobidnumber;
        $job->fullname = $record->jobfullname;

        $displayjob = totara_job_display_user_job($record, $job);

        return $displayjob;
    }

    /**
     * Get number of users in the management structure
     *
     * @return int
     */
    public function get_user_count(): int {
        global $DB;

        $path = $DB->get_field('job_assignment', 'managerjapath', ['id' => $this->instanceid]);

        if ($this->includechildren == 1 && isset($path)) {
            // For a manager's entire team.
            $where = $DB->sql_like('ja.managerjapath', '?');
            $path = $DB->sql_like_escape($path);
            $params = array($path . '/%');
        } else {
            // For a manager's direct team.
            $where = "ja.managerjaid = ?";
            $params = array($this->instanceid);
        }

        $sql = "SELECT COUNT(DISTINCT ja.userid) AS id
                FROM {job_assignment} ja
                INNER JOIN {user} u ON (ja.userid = u.id AND u.deleted = 0)
                WHERE {$where}";

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Gets user ids of all indirect staff (staff who are not immediately under the selected
     * manager)
     *
     * @return array
     */
    public function get_children(): array {
        global $DB;

        $selecteditem = $this->instanceid; // This is the ID of the job_assignment record

        $path = $DB->get_field('job_assignment', 'managerjapath', ['id' => $this->instanceid]);

        $where = $DB->sql_like('ja.managerjapath', ':managerpath');
        $where .= " AND ja.managerjaid != :managerjaid";
        $path = $DB->sql_like_escape($path);
        $params = ['managerpath' => $path . '/%', 'managerjaid' => $this->instanceid];

        $sql = "SELECT ja.id, ja.userid
                FROM {job_assignment} ja
                INNER JOIN {user} u ON (ja.userid = u.id AND u.deleted = 0)
                WHERE {$where}";

        $records = $DB->get_records_sql_menu($sql, $params);

        return $records;
    }
}
