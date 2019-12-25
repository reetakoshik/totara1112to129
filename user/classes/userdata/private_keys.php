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
 * @package core_user
 */

namespace core_user\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This item takes care of exporting, counting and purging user_private_keys.
 * The private_keys for example are used in published grades. There they can already be seen by the user in the key manager
 * (Grades -> Export) and they only allow viewing a specific subset of data. So the keys are security
 * related and therefore we don't export them to keep the risk as small as possible.
 */
class private_keys extends item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdataitem-user-private_keys', 'core'];
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        // Keys are no personal data and security related so we don't export them.
        return false;
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSE, CONTEXT_COURSECAT];
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Execute user data purging for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        // Get records matching given context.
        $keys = self::get_private_keys($user, $context);
        // Nothing to delete.
        if (empty($keys)) {
            return item::RESULT_STATUS_SUCCESS;
        }

        // Create IN () part and params.
        list($idsinsql, $params) = $DB->get_in_or_equal(array_keys($keys), SQL_PARAMS_NAMED);

        // Delete private keys.
        $params['userid'] = $user->id;
        $DB->execute("DELETE FROM {user_private_key} WHERE userid = :userid AND id $idsinsql", $params);

        return item::RESULT_STATUS_SUCCESS;
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
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        // Get records matching given context.
        return count(self::get_private_keys($user, $context));
    }

    /**
     * Load all private keys matching the given user and context
     *
     * @param target_user $user
     * @param \context $context
     * @return \stdClass[]
     */
    private static function get_private_keys(target_user $user, \context $context): array {
        global $DB;

        // Get records matching given context.
        $join = self::get_courses_context_join($context, 'upk.instance');
        $sql = 'SELECT upk.* FROM {user_private_key} upk '.$join.' AND upk.userid = :userid';
        $keys = $DB->get_records_sql($sql, ['userid' => $user->id]);

        return $keys;
    }

}