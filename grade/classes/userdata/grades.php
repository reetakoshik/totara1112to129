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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_grades
 */

namespace core_grades\userdata;


use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use core_grades\userdata\helpers;

/**
 * User-data grades item.
 * Counts, exports and purges grades and grades history for the user.
 *
 * Note: It doesn't include advanced grading comments here, as it is used only in grading assignments => it handled by
 *  "singleassignments" user-data item.
 *
 * @package core_grades\userdata
 */
class grades extends item {

    /**
     * @var array Compatible contexts
     */
    protected static $contexts = [
        CONTEXT_SYSTEM,
        CONTEXT_COURSECAT,
        CONTEXT_COURSE,
        CONTEXT_MODULE
    ];

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return static::$contexts;
    }

    /**
     * Get main component for this item
     *
     * @return string
     */
    public static function get_main_component() {
        return 'core_grades';
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
     * Purge data
     *
     * @param target_user $user
     * @param \context $context
     * @return int
     */
    protected static function purge(target_user $user, \context $context) {
        return (new helpers\grades($context, $user))->purge();
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data.
     *
     * @param target_user $user
     * @param \context $context
     * @return int|export
     */
    protected static function export(target_user $user, \context $context) {
        $item = new helpers\grades($context, $user);
        $export = new export();
        $export->data = $item->export();

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
     * Count how much data is there
     *
     * @param target_user $user
     * @param \context $context
     * @return int
     */
    protected static function count(target_user $user, \context $context) {
        return (new helpers\grades($context, $user))->count();
    }
}