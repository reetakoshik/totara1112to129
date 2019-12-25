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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package enrol_paypal
 */

namespace enrol_paypal\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use context;

defined('MOODLE_INTERNAL') || die();

/**
 * This concerns data that is saved for successful transactions via paypal as well as some pending or unsuccessful transactions.
 */
class transaction_information extends item {

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     *
     * NOTE: this can be overridden to move item to a different form group in UI,
     *       for example local plugins and items to standard activities
     *       or blocks may move items to their related plugins.
     */
    public static function get_main_component() {
        return 'core_enrol';
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 300;
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
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
    }

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
    protected static function purge(target_user $user, context $context) {
        global $DB;

        $records = self::get_transactions($user, $context);
        if (!empty($records)) {
            $ids = array_keys($records);
            $DB->delete_records_list('enrol_paypal', 'id', $ids);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();
        $records = self::get_transactions($user, $context);
        foreach ($records as $record) {
            $record = (array)$record;
            // We don't export some of the information to
            // keep the risk of abuse low.
            unset($record['business']);
            unset($record['memo']);
            unset($record['parent_txn_id']);
            unset($record['receiver_email']);
            unset($record['receiver_id']);
            unset($record['userid']);
            $export->data[] = $record;
        }

        return $export;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context) {
        return count(self::get_transactions($user, $context));
    }

    /**
     * @param target_user $user
     * @param context $context
     * @return \stdClass[]
     */
    private static function get_transactions(target_user $user, context $context): array {
        global $DB;

        $contextjoin = self::get_courses_context_join($context, 'ep.courseid');
        $sql = "SELECT ep.*
                FROM {enrol_paypal} ep
                $contextjoin
                WHERE ep.userid = :userid";
        $params = ['userid' => $user->id];

        return $DB->get_records_sql($sql, $params);
    }

}
