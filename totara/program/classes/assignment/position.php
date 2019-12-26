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

class position extends base {

    const ASSIGNTYPE_POSITION = 2;

    public function get_type(): int {
        return self::ASSIGNTYPE_POSITION;
    }

    public function get_name() : string {
        global $DB;

        $pos = $DB->get_record('pos', ['id' => $this->instanceid]);

        return format_string($pos->fullname);
    }

    /**
     * Count of users in this assignment
     *
     * @return int
     */
    public function get_user_count(): int {
        global $DB;

        $path = $DB->get_field('pos', 'path', ['id' => $this->instanceid]);

        $where = "ja.positionid = :positionid";
        $params = ['positionid' => $this->instanceid];
        if ($this->includechildren == 1 && isset($path)) {
            $pathlike = $DB->sql_like('path', ':path');
            $params = array_merge($params, ['path' => $path . '/%']);
            $where .= "OR EXISTS (
                    SELECT id FROM {pos} p WHERE $pathlike
                    AND ja.positionid = p.id)";
        }

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM {job_assignment} ja
                INNER JOIN {user} u ON ja.userid = u.id
                WHERE ($where)
                AND u.deleted = 0";

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get a list of userid that are assigned to positions
     * below the selected position in the hierarchy.
     *
     * @return array
     */
    public function get_children(): array {
        global $DB;

        $path = $DB->get_field('pos', 'path', ['id' => $this->instanceid]);
        $pathlike = $DB->sql_like('path', ':path');

        $sql = "SELECT DISTINCT u.id
                FROM {job_assignment} AS ja
                INNER JOIN {user} AS u ON ja.userid = u.id
                WHERE EXISTS (
                    SELECT id FROM {pos} p WHERE $pathlike
                    AND ja.positionid = p.id
                )
                AND u.deleted = 0";

        $params = ['path' => $path . '/%'];

        $ids = $DB->get_records_sql($sql, $params);
        $ids = array_map(function($o) { return $o->id; }, $ids);

        return $ids;
    }
}
