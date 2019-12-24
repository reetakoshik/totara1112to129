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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\userdata;

defined('MOODLE_INTERNAL') || die();

/**
 * Basic class for representation of user data stored in Totara.
 *
 * NOTE: Interfaces are not used intentionally because this class is extended in 3rd party code.
 *       If you are wondering why all constants are defined here, the reason is to simplify writing of item classes.
 */
abstract class item {
    /** Execution was successful. */
    public const RESULT_STATUS_SUCCESS = -1;
    /** Execution ended with error. */
    public const RESULT_STATUS_ERROR = -2;
    /** Action could not be executed */
    public const RESULT_STATUS_SKIPPED = -3;
    /** Action execution was cancelled */
    public const RESULT_STATUS_CANCELLED = -4;
    /** Action execution timed out */
    public const RESULT_STATUS_TIMEDOUT = -5;

    /**
     * Get item's real Frankenstyle component name (core subsystem or plugin).
     *
     * @return string
     */
    public static final function get_component() {
        $classname = get_called_class();
        $parts = explode('\\', $classname);
        return reset($parts);
    }

    /**
     * Returns class name without namespace.
     *
     * The only valid class namespace is 'component_name\userdata'.
     *
     * @return string
     */
    public static final function get_name() {
        $classname = get_called_class();
        $parts = explode('\\', $classname);
        return end($parts);
    }

    /**
     * Returns human readable name of this item.
     *
     * @return string string
     */
    public static final function get_fullname() {
        list($identifier, $component) = static::get_fullname_string();
        return get_string($identifier, $component);
    }

    /**
     * Is help string available?
     *
     * @return bool
     */
    public static final function help_available() {
        list($identifier, $component) = static::get_fullname_string();
        return get_string_manager()->string_exists($identifier . '_help', $component);
    }

    /**
     * Is the given context level compatible with this item?
     *
     * @param int $contextlevel
     * @return bool
     */
    public static final function is_compatible_context_level($contextlevel) {
        return in_array($contextlevel, static::get_compatible_context_levels());
    }

    /**
     * Returns join for all courses in given context.
     *
     * @param \context $context
     * @param string $courseidfield sql field used directly in SQL join
     * @param string $contextalias context table alias
     * @return null|string SQL join fragment
     */
    public static final function get_courses_context_join(\context $context, $courseidfield, $contextalias = 'ctx') {
        // NOTE: it is safe to user context properties in SQL code directly.

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = {$courseidfield} AND {$contextalias}.contextlevel = " . CONTEXT_COURSE . ")";
        }

        if ($context->contextlevel == CONTEXT_COURSECAT) {
            return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = {$courseidfield} AND {$contextalias}.contextlevel = " . CONTEXT_COURSE . " AND {$contextalias}.path LIKE '{$context->path}/%')";
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = {$courseidfield} AND {$contextalias}.contextlevel = " . CONTEXT_COURSE . " AND {$contextalias}.id = {$context->id})";
        }

        // Wrong context, return valid no match join.
        return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = -1)";
    }

    /**
     * Returns join for all activities in given context.
     *
     * @param \context $context
     * @param string $activitycmidfield sql field used directly in SQL join
     * @param string $contextalias context table alias
     * @return null|string SQL join fragment
     */
    public static final function get_activities_context_join(\context $context, $activitycmidfield, $contextalias = 'ctx') {
        // NOTE: it is safe to user context properties in SQL code directly.

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = {$activitycmidfield} AND {$contextalias}.contextlevel = " . CONTEXT_MODULE . ")";
        }

        if ($context->contextlevel == CONTEXT_COURSECAT or $context->contextlevel == CONTEXT_COURSE) {
            return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = {$activitycmidfield} AND {$contextalias}.contextlevel = " . CONTEXT_MODULE . " AND {$contextalias}.path LIKE '{$context->path}/%')";
        }

        if ($context->contextlevel == CONTEXT_MODULE) {
            return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = {$activitycmidfield} AND {$contextalias}.contextlevel = " . CONTEXT_MODULE . " AND {$contextalias}.id = {$context->id})";
        }

        // Wrong context, return valid no match join.
        return "JOIN {context} {$contextalias} ON ({$contextalias}.instanceid = -1)";
    }

    /**
     * Returns join for all activities in given context.
     *
     * @param \context $context
     * @param string $module module name such as 'forum', 'glossary' - used directly in SQL
     * @param int $activityidfield sql field used directly in SQL join
     * @param string $activityalias activity table alias
     * @param string $cmalias course module table alias
     * @param string $modulesalias modules table alias
     * @param string $contextalias context table alias
     * @return null|string SQL join fragment
     */
    public static final function get_activities_join(\context $context, $module, $activityidfield, $activityalias = 'act', $cmalias = 'cm', $modulesalias = 'modules', $contextalias = 'ctx') {
        $module = clean_param($module, PARAM_ALPHANUMEXT);

        return "JOIN {{$module}} {$activityalias} ON {$activityalias}.id = $activityidfield
                JOIN {modules} {$modulesalias} ON ({$modulesalias}.name = '{$module}')
                JOIN {course_modules} {$cmalias} ON ({$cmalias}.module = {$modulesalias}.id AND {$cmalias}.instance = {$activityalias}.id)
                ". self::get_activities_context_join($context, "{$cmalias}.id", $contextalias);
    }

    /**
     * Execute purge of user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    public static final function execute_purge(target_user $user, \context $context) {
        global $DB;

        // Purge cannot run in transaction, it could lock up everything or run out of memory.
        $DB->transactions_forbidden();

        if (!static::is_purgeable($user->status)) {
            // This method should not have been called!
            return self::RESULT_STATUS_ERROR;
        }
        if (!static::is_compatible_context_level($context->contextlevel)) {
            // Somebody tries to call this method directly without doing context checks.
            return self::RESULT_STATUS_ERROR;
        }

        $result = static::purge($user, $context);

        // Make sure there are no open transactions left!
        if ($DB->is_transaction_started()) {
            $DB->force_transaction_rollback();
            throw new \coding_exception('Transaction was not committed in purge() method');
        }

        if ($result !== self::RESULT_STATUS_SUCCESS and $result !== self::RESULT_STATUS_ERROR and $result !== self::RESULT_STATUS_SKIPPED) {
            throw new \coding_exception('Invalid result returned from purge() method');
        }

        return $result;
    }

    /**
     * Execute export of user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    public static final function execute_export(target_user $user, \context $context) {
        if (!static::is_exportable()) {
            // This method should not have been called!
            return self::RESULT_STATUS_ERROR;
        }
        if (!static::is_compatible_context_level($context->contextlevel)) {
            // Somebody tries to call this method directly without doing context checks.
            return self::RESULT_STATUS_ERROR;
        }

        $result = static::export($user, $context);
        if ($result !== self::RESULT_STATUS_ERROR and $result !== self::RESULT_STATUS_SKIPPED and !($result instanceof export)) {
            throw new \coding_exception('Invalid result returned from execute() method');
        }
        return $result;
    }

    /**
     * Execute counting of user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int integer is the count >= 0, negative number is error self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    public static final function execute_count(target_user $user, \context $context) {
        if (!static::is_countable()) {
            // This method should not have been called!
            return self::RESULT_STATUS_ERROR;
        }
        if (!static::is_compatible_context_level($context->contextlevel)) {
            // Somebody tries to call this method directly without doing context checks.
            return self::RESULT_STATUS_ERROR;
        }

        $result = static::count($user, $context);
        if ($result === self::RESULT_STATUS_ERROR or $result === self::RESULT_STATUS_SKIPPED) {
            return $result;
        }
        if (!is_number($result)) {
            throw new \coding_exception('Invalid result returned from count() method - not a number');
        }
        $result = (int)$result;
        if ($result < 0) {
            throw new \coding_exception('Invalid result returned from count() method: ' . $result);
        }
        return $result;
    }

    // =============================================================================================================
    // ======================= All following methods should be overridden to define user data items. ===============
    // =============================================================================================================

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {

        // NOTE: override with custom string in case of local customisations to work around lang pack changes in plugins.

        return ['userdataitem' . self::get_name(), self::get_component()];
    }

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     *
     * NOTE: this can be overridden to move item to a different form group in UI,
     *       for example local plugins and items to standard activities
     *       or blocks may move items to their related plugins.
     */
    public static function get_main_component() {

        // NOTE: override to move item to different component in user interfaces.

        return self::get_component();
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        // NOTE: if not overridden the order in related main component will be pretty much random.
        return 10000;
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM];
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    abstract public static function is_purgeable(int $userstatus);

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        throw new \coding_exception('purge() method must be overridden for all user data items that allow purging');
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    abstract public static function is_exportable();

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        throw new \coding_exception('export() method must be overridden for all user data items that allow exporting');
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    abstract public static function is_countable();

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        throw new \coding_exception('count() method must be overridden for all user data items that allow counting');
    }
}
