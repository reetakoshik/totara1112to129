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
 * @package core_message
 */

namespace core_message\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of purging, exporting and counting the instant messages sent by the given user.
 * Instant messages were split up into sent and received to make it more flexible. Messages sent by other users
 * to the given user shouldn't be deleted in some cases as it's data generated by others. But still,
 * there should be the option to delete them.
 */
class instant_messages_sent extends item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdataiteminstant_messages_sent', 'message'];
    }

    /**
     * Returns sort order.
     * @return int
     */
    public static function get_sortorder() {
        return 736; // Order just before Received.
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
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $eventtypes = ['instantmessage'];

        $transaction = $DB->start_delegated_transaction();

        $helper = new messages_purging_helper();
        $helper->delete_sent_messages($user->id, $eventtypes);

        $transaction->allow_commit();

        return item::RESULT_STATUS_SUCCESS;
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
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new export();

        // Load all unread messages.
        $messages = $DB->get_records_sql(
            'SELECT *
                FROM {message}
                WHERE useridfrom = ? AND eventtype = ?
                ORDER BY useridto, timecreated ASC',
            [$user->id, 'instantmessage']
        );
        $export->data['unread'] = array_map(function($item) { return (array)$item; }, $messages);

        // Load all read messages.
        $messagesread = $DB->get_records_sql(
            'SELECT *
                FROM {message_read}
                WHERE useridfrom = ? AND eventtype = ?
                ORDER BY useridto, timecreated ASC',
            [$user->id, 'instantmessage']
        );
        $export->data['read'] = array_map(function($item) { return (array)$item; }, $messagesread);

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
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $countmessages = $DB->count_records('message', ['useridfrom' => $user->id, 'eventtype' => 'instantmessage']);
        $countmessagesread = $DB->count_records('message_read', ['useridfrom' => $user->id, 'eventtype' => 'instantmessage']);

        return $countmessages + $countmessagesread;
    }

}