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
 * @package totara_program
 */

namespace totara_program\userdata;

use context;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of purging, exporting and counting certification completion.
 * This only targets certifications. Program completion is handled by {@link \totara_program\userdata\completion}.
 * History and logs are included within this item.
 */
abstract class base_assignment_completion extends item {

    /**
     * Certifications are basically programs therefore we can treat them mostly the same
     * with some minor difference. That's why we have this flag here.
     *
     * @var bool
     */
    protected static $iscertification = false;

    /**
     * Although we return course_modules_completion, it's the course completions that are the main
     * subject of this class. So the module context level is not applicable here.
     *
     * @return array
     */
    final public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_PROGRAM];
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    final public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    final public static function is_exportable() {
        return true;
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    final public static function is_countable() {
        return true;
    }

    /**
     * @param target_user $user
     * @param context $context
     * @return array
     */
    protected static function get_assigned_programids(target_user $user, context $context): array {
        global $DB;

        $contextsql = self::get_context_sql($context, 'p');
        $certificationsql = self::get_certification_sql('p');

        // Get all program ids for the users assignments.
        $sql = "
            SELECT p.id
              FROM {prog_user_assignment} pa
              JOIN {prog} p ON pa.programid = p.id $certificationsql $contextsql
             WHERE pa.userid = :userid
        ";
        $params = ['userid' => $user->id];

        return $DB->get_fieldset_sql($sql, $params);
    }

    /**
     * @param context $context
     * @return string
     */
    protected static function get_context_sql(context $context, string $tablealias): string {
        $contextsql = '';
        if ($context->contextlevel == CONTEXT_PROGRAM) {
            $contextsql = "AND $tablealias.id = " . $context->instanceid;
        } else if ($context->contextlevel == CONTEXT_COURSECAT) {
            $contextsql = "AND $tablealias.category = " . $context->instanceid;
        }
        return $contextsql;
    }

    /**
     * @param string $tablealias
     * @return string
     */
    private static function get_certification_sql(string $tablealias): string {
        if (static::$iscertification) {
            $certificationsql = "AND $tablealias.certifid IS NOT NULL";
        } else {
            $certificationsql = "AND $tablealias.certifid IS NULL";
        }
        return $certificationsql;
    }

    /**
     * @param target_user $user
     * @param array $programids
     */
    protected static function unassign_from_programs(target_user $user, array $programids) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');

        list($sqlprogramids, $params) = $DB->get_in_or_equal($programids, SQL_PARAMS_NAMED);

        $select = "programid $sqlprogramids AND userid = :userid";

        $params['userid'] = $user->id;

        // Delete all the program exceptions for the user.
        $DB->delete_records_select('prog_exception', $select, $params);

        // Delete all the program extensions for the user.
        $DB->delete_records_select('prog_extension', $select, $params);

        // Delete any future assignments for the user.
        $DB->delete_records_select('prog_future_user_assignment', $select, $params);

        // Delete all the program user assignments for the user.
        $DB->delete_records_select('prog_user_assignment', $select, $params);

        // Delete all the individual assignments for the user.
        $select = "programid $sqlprogramids AND assignmenttype = ".ASSIGNTYPE_INDIVIDUAL." AND assignmenttypeid = :userid";
        $DB->delete_records_select('prog_assignment', $select, $params);

        // Delete all the program message logs for the user.
        $select = "userid = :userid AND messageid IN (SELECT id FROM {prog_message} WHERE programid $sqlprogramids)";
        $DB->delete_records_select('prog_messagelog', $select, $params);
    }

    /**
     * @param target_user $user
     * @param array $programids
     */
    protected static function purge_program_completion(target_user $user, array $programids) {
        global $DB;

        list($sqlinorequal, $params) = $DB->get_in_or_equal($programids, SQL_PARAMS_NAMED);

        // All certification completion resides in prog_completion linked to the certification table.
        $select = "userid = :userid AND programid $sqlinorequal";
        $params['userid'] = $user->id;
        $DB->delete_records_select('prog_completion', $select, $params);
        $DB->delete_records_select('prog_completion_history', $select, $params);
        $DB->delete_records_select('prog_completion_log', $select, $params);
    }

    /**
     * Counts the number of program assignments that a user has within the given context.
     *
     * @param target_user $user
     * @param context $context
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context) {
        global $DB;

        $contextsql = self::get_context_sql($context, 'prog');
        $certificationsql = self::get_certification_sql('prog');

        // All certification completion resides in prog_completion linked to the certification table.
        $sql = "
            SELECT COUNT(assign.id)
              FROM {prog_user_assignment} assign
              JOIN {prog} prog ON assign.programid = prog.id $certificationsql $contextsql
             WHERE assign.userid = :userid
        ";

        $params = ['userid' => $user->id];
        return $DB->count_records_sql($sql, $params);
    }

}
