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
 * @author Riana Rossouw <riana.rossouw@@totaralearning.com>
 * @package tool_monitor
 */

namespace tool_monitor\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use tool_monitor\subscription;

defined('MOODLE_INTERNAL') || die();


/**
 * This item takes care of exporting, counting and purging of users' subscriptions to monitored events.
 */
final class subscriptions extends item {
    /**
     * Although we return event monitoring subscriptions, users can subscribe to the events on site level
     * or specific course level. Therefore we also include the course context levels here.
     *
     * @return array
     */
    final public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
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
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
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

        // Deleted users should not have any subscription rows, but purging in any case just to be sure
        $sql = "SELECT s.id "
                . self::get_subscriptions_join_sql($user, $context)
                . " WHERE s.userid = :userid";
        $params = ['userid' => $user->id];

        $rows = $DB->get_records_sql($sql, $params);
        $subscriptionrows = $DB->get_fieldset_sql($sql, $params);
        $DB->delete_records_list('tool_monitor_subscriptions', 'id', $subscriptionrows);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        if ($user->status === target_user::STATUS_DELETED) {
            // Only exporting for active or suspended users.
            // When a user is deleted, the subscriptions are also deleted so chances of needing it in future is slim
            return item::RESULT_STATUS_SKIPPED;
        }

        // Although we don't need all these columns, using the columnlist used in subscription_manager::get_subscription_join_rule_sql
        // to allow us to get a subscription instance later on
        $sql = "SELECT s.*, r.description, r.descriptionformat, r.name, r.userid as ruleuserid, r.courseid as rulecourseid,
                       r.plugin, r.eventname, r.template, r.templateformat, r.frequency, r.timewindow "
                . self::get_subscriptions_join_sql($user, $context)
                . " WHERE s.userid = :userid";
        $params = ['userid' => $user->id];

        $subscriptionrows = $DB->get_records_sql($sql, $params);

        $result = [];
        foreach ($subscriptionrows as $row) {
            $subscription = new subscription($row);

            $result[] = [
                'id' => $row->id,
                'courseid' => $row->courseid,
                'ruleid' => $row->ruleid,
                'rulename' => $row->name,
                'cmid' => $row->cmid,
                'plugin' => $subscription->get_plugin_name(),
                'eventname' => $subscription->get_event_name(),
                'threshold' => $subscription->get_filters_description(),
            ];
        }

        $export = new \totara_userdata\userdata\export();
        $export->data = $result;
        return $export;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $sql = "SELECT count(s.id) "
                . self::get_subscriptions_join_sql($user, $context)
                . " WHERE s.userid = :userid";
        $params = ['userid' => $user->id];

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Return the sql join clause to retrieve affected user subscriptions
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return string
     */
    protected static function get_subscriptions_join_sql(target_user $user, \context $context) {
        global $DB;

        $sql = "FROM {tool_monitor_subscriptions} s
                JOIN {tool_monitor_rules} r
                  ON r.id = s.ruleid ";

        // Rule subscription can be for a specific course or on 'site' level (courseid == 0)
        // Only use course context criteria if we are using non-system context
        if ($context->contextlevel == CONTEXT_COURSECAT || $context->contextlevel == CONTEXT_COURSE) {
            $sql .= self::get_courses_context_join($context, 'r.courseid');
        }

        return $sql;
    }
}