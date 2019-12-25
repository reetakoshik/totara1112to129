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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\dataholder_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\users;
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;

class users_in_roles extends dataholder_factory {

    public static function get_dataholders(): array {
        global $DB;

        $dataholders = [];

        $rolessql = "SELECT DISTINCT r.id, r.name, r.sortorder
                       FROM {role} r
                       JOIN {role_context_levels} rcl ON (rcl.contextlevel = :course_context_level AND r.id = rcl.roleid)
                   ORDER BY r.sortorder ASC";
        $roleparams = ['course_context_level' => CONTEXT_COURSE];
        $roles = $DB->get_records_sql($rolessql, $roleparams);
        $roles = role_fix_names($roles);

        foreach ($roles as $role) {
            $key = 'u_i_r_' . $role->shortname;
            $paramroleid = $key . '_roleid';
            $paramcontextlevel = $key . '_ctxlvl';

            $userids = $DB->sql_group_concat_unique($key . '_ra.userid', ',');

            $dataholders[] = new dataholder(
                $key,
                $role->localname, // Has been format_stringed in role_fix_names().
                [
                    formatter::TYPE_PLACEHOLDER_TEXT => new users(
                        $key . '.userids'
                    ),
                ],
                [
                    $key =>
                        "LEFT JOIN (SELECT {$key}_ctx.instanceid AS courseid, {$userids} AS userids
                                      FROM {role_assignments} {$key}_ra
                                      JOIN {context} {$key}_ctx ON {$key}_ctx.id = {$key}_ra.contextid
                                     WHERE {$key}_ra.roleid = :{$paramroleid} AND {$key}_ctx.contextlevel = :{$paramcontextlevel}
                                  GROUP BY {$key}_ctx.instanceid) {$key}
                           ON {$key}.courseid = base.id",
                ],
                [
                    $paramroleid => $role->id,
                    $paramcontextlevel => CONTEXT_COURSE,
                ]
            );
        }

        return $dataholders;
    }
}
