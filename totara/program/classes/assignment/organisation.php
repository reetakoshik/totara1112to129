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

class organisation extends base {

    const ASSIGNTYPE_ORGANISATION = 1;

    public function get_type(): int {
        return self::ASSIGNTYPE_ORGANISATION;
    }

    public function get_name() : string {
        global $DB;

        $org = $DB->get_record('org', ['id' => $this->instanceid]);

        return format_string($org->fullname);
    }

    /**
     * Count of users in this assignment
     *
     * @return int
     */
    public function get_user_count(): int {
        global $DB;

        $path = $DB->get_field('org', 'path', ['id' => $this->instanceid]);

        $where = "ja.organisationid = :organisationid";
        $params = ['organisationid' => $this->instanceid];
        if ($this->includechildren == 1 && isset($path)) {
            $pathlike = $DB->sql_like('path', ':path');
            $params = array_merge($params, ['path' => $path . '/%']);
            $where .= "OR EXISTS (
                    SELECT id FROM {org} o WHERE $pathlike
                    AND ja.organisationid = o.id)";
        }

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM {job_assignment} ja
                INNER JOIN {user} u ON ja.userid = u.id
                WHERE ($where)
                AND u.deleted = 0";

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get an id list of children for this organisation
     *
     * @return array
     */
    public function get_children(): array {
        global $DB;

        $path = $DB->get_field('org', 'path', ['id' => $this->instanceid]);
        $pathlike = $DB->sql_like('path', ':path');

        $sql = "SELECT DISTINCT u.id
                FROM {job_assignment} AS ja
                INNER JOIN {user} AS u ON ja.userid = u.id
                WHERE EXISTS (
                    SELECT id FROM {org} o WHERE $pathlike
                    AND ja.organisationid = o.id
                )
                AND u.deleted = 0";

        $params = ['path' => $path . '/%'];

        $ids = $DB->get_records_sql($sql, $params);
        $ids = array_map(function($o) { return $o->id; }, $ids);

        return $ids;
    }
}
