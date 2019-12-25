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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package core_user
 */

namespace core_user\userdata;

use context;
use context_helper;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This item takes care of purging, counting and exporting a user's role assignments.
 */
class role_assignments extends item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdataitem-user-role_assignments', 'core'];
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [
            CONTEXT_SYSTEM,
            CONTEXT_USER,
            CONTEXT_COURSECAT,
            CONTEXT_PROGRAM,
            CONTEXT_COURSE,
            CONTEXT_MODULE,
            CONTEXT_BLOCK,
        ];
    }

    /**
     * Returns sort order.
     * @return int
     */
    public static function get_sortorder() {
        return 390;
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return ($userstatus !== target_user::STATUS_DELETED);
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {

        // No purging for deleted users. That must happen on user deletion.
        if ($user->status == target_user::STATUS_DELETED) {
            return self::RESULT_STATUS_ERROR;
        }

        // For system context unassign all roles for this user instead of making role_unassign_all() go through all
        // the subcontexts (which it complains about with a debugging message).
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            role_unassign_all(['userid' => $user->id]);
            return self::RESULT_STATUS_SUCCESS;
        }

        // For course category context role_unassign_all() does not recurse down to module level, so we have to call unassign
        // for all the courses to make sure role assignments for modules get purged.
        if ($context->contextlevel == CONTEXT_COURSECAT) {
            foreach ($context->get_child_contexts() as $childcontext) {
                role_unassign_all(['userid' => $user->id, 'contextid' => $childcontext->id], true);
            }
        }
        role_unassign_all(['userid' => $user->id, 'contextid' => $context->id], true);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        global $DB;

        list($sql, $params) = self::get_subcontext_sql($user, $context, 'ra.id, ra.roleid, r.name, r.shortname, r.description, r.archetype, ra.timemodified, ra.contextid');

        $export = new export();
        $export->data = $DB->get_records_sql($sql, $params);

        // Add context name to the data to make it more meaningful.
        foreach ($export->data as &$record) {
            $context = context_helper::instance_by_id($record->contextid);
            $record->contextname = $context->get_context_name();
        }

        return $export;
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context) {
        global $DB;

        list($sql, $params) = self::get_subcontext_sql($user, $context, 'COUNT(ra.id)');

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Build the sql for exporting and counting, taking subcontexts into account.
     *
     * @param target_user $user
     * @param context $context
     * @param string $select_columns
     * @return array
     */
    private static function get_subcontext_sql(target_user $user, context $context, string $select_columns): array {
        global $DB;

        // For system context just get all role assignments for that user.
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $sql = "SELECT {$select_columns}                    
                      FROM {role_assignments} ra
                      JOIN {role} r ON r.id = ra.roleid
                     WHERE ra.userid = :userid";
            $params = [
                'userid' => $user->id,
            ];
        } else {
            $likepathsql = $DB->sql_like('path', ':path');
            $sql = "SELECT {$select_columns}                    
                      FROM {role_assignments} ra
                      JOIN {context} ctx ON ctx.id = ra.contextid
                      JOIN {role} r ON r.id = ra.roleid
                     WHERE ra.userid = :userid
                       AND (ra.contextid = :contextid OR {$likepathsql})";
            $params = [
                'userid' => $user->id,
                'contextid' => $context->id,
                'path' => $context->path . '/%',
            ];
        }

        return [$sql, $params];
    }
}
