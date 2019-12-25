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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_user
 */

namespace core_user\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

/**
 * An abstract class to make it easy for plugins and components to clean up user preferences.
 */
abstract class plugin_preferences extends item {

    /**
     * Return all of the preferences set for the user, that belong to the component/plugin.
     *
     * @param int $userid
     * @return string[]
     */
    abstract protected static function get_user_preferences(int $userid): array;

    /**
     * String used for human readable name of user preferences. Defaults to preferences.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdataitem-user-plugin-preferences', 'core'];
    }

    /**
     * Returns all contexts this item is compatible with, just the system context in the case of user preferences.
     *
     * @return array
     */
    final public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM];
    }

    /**
     * Can user data of this item data be purged from system? Yes to all.
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    final public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge user prefernces beloging to this plugin or component.
     *
     * Note this cannot be overridden. All user preferences are handled in the same way.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    final protected static function purge(target_user $user, \context $context) {
        global $DB;

        $preferences = static::get_user_preferences((int)$user->id);

        if (empty($preferences)) {
            // Nothing to do.
            return self::RESULT_STATUS_SUCCESS;
        }

        if ($user->status !== target_user::STATUS_ACTIVE) {
            // If the user is not active (they are suspended or deleted) then just delete the preferences.
            // This may have already happened if they were deleted, but do it anyway to be safe.
            // No need to use the API leading to preferences being marked as changed etc.
            // Shortest possible route here.
            list($select, $params) = $DB->get_in_or_equal($preferences, SQL_PARAMS_NAMED);
            $sql = "userid = :userid AND name {$select}";
            $params['userid'] = $user->id;
            $DB->delete_records_select('user_preferences', $sql, $params);
            return self::RESULT_STATUS_SUCCESS;
        }

        foreach ($preferences as $preference) {
            unset_user_preference($preference, $user->id);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user preferences belonging to this plugin or component be exported? By default yes.
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user preferences belonging to this component or plugin.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    final protected static function export(target_user $user, \context $context) {

        if ($user->status === target_user::STATUS_DELETED) {
            // You can only export for active or suspended users.
            return item::RESULT_STATUS_SKIPPED;
        }

        $export = new export();
        $preferences = get_user_preferences(null, null, $user->id);
        foreach (static::get_user_preferences((int)$user->id) as $preference) {
            if (isset($preferences[$preference])) {
                $export->data[$preference] = $preferences[$preference];
            }
        }
        return $export;
    }

    /**
     * User preferences belonging to this plugin or component can be counted.
     *
     * @return bool
     */
    final public static function is_countable() {
        return true;
    }

    /**
     * Count the user preferences belonging to this component or plugin.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    final protected static function count(target_user $user, \context $context) {
        $preferences = get_user_preferences(null, null, $user->id);
        $count = 0;
        foreach (static::get_user_preferences((int)$user->id) as $preference) {
            if (isset($preferences[$preference])) {
                $count++;
            }
        }
        return $count;
    }

}