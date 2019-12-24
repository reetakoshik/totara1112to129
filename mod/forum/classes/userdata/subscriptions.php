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
 * @package mod_forum
 */

namespace mod_forum\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of purging, exporting and counting the subscriptions (forums and discussions) made by the user.
 */
class subscriptions extends item {

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
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

        $join = self::get_activities_join($context, 'forum', 'fs.forum');

        // Delete any forum subscriptions.
        $sql = "SELECT fs.id
                  FROM {forum_subscriptions} fs
                  $join
                 WHERE fs.userid = :userid";
        $subids = $DB->get_fieldset_sql($sql, ['userid' => $user->id]);
        if (!empty($subids)) {
            list($idsinsql, $params) = $DB->get_in_or_equal($subids, SQL_PARAMS_NAMED);
            $select = "userid = :userid AND id $idsinsql";
            $params['userid'] = $user->id;
            $DB->delete_records_select('forum_subscriptions', $select, $params);
        }

        // Delete any discussion subscriptions.
        $sql = "SELECT fs.id
                  FROM {forum_discussion_subs} fs
                  $join
                 WHERE fs.userid = :userid";
        $discussionsubids = $DB->get_fieldset_sql($sql, ['userid' => $user->id]);
        if (!empty($discussionsubids)) {
            list($idsinsql, $params) = $DB->get_in_or_equal($discussionsubids, SQL_PARAMS_NAMED);
            $select = "userid = :userid AND id $idsinsql";
            $params['userid'] = $user->id;
            $DB->delete_records_select('forum_discussion_subs', $select, $params);
        }

        // Delete any subscriptions mail digests.
        $sql = "SELECT fs.id
                  FROM {forum_digests} fs
                  $join
                 WHERE fs.userid = :userid";
        $digestids = $DB->get_fieldset_sql($sql, ['userid' => $user->id]);
        if (!empty($digestids)) {
            list($idsinsql, $params) = $DB->get_in_or_equal($digestids, SQL_PARAMS_NAMED);
            $select = "userid = :userid AND id $idsinsql";
            $params['userid'] = $user->id;
            $DB->delete_records_select('forum_digests', $select, $params);
        }

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

        $join = self::get_activities_join($context, 'forum', 'fs.forum');
        $sql = "SELECT fs.*
                  FROM {forum_subscriptions} fs
                  $join
                 WHERE fs.userid = :userid";
        $forumsubs = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $join = self::get_activities_join($context, 'forum', 'fds.forum');
        $sql = "SELECT fds.*
                  FROM {forum_discussion_subs} fds
                  $join
                 WHERE fds.userid = :userid";
        $discussionsubs = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $export = new export();
        $export->data['forum_subscriptions'] = $forumsubs;
        $export->data['discussion_subscriptions'] = $discussionsubs;

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

        $join = self::get_activities_join($context, 'forum', 'fs.forum');
        $sql = "SELECT COUNT(fs.id)
                  FROM {forum_subscriptions} fs
                  $join
                 WHERE fs.userid = :userid";
        $forumsubs = $DB->count_records_sql($sql, ['userid' => $user->id]);

        $join = self::get_activities_join($context, 'forum', 'fds.forum');
        $sql = "SELECT COUNT(fds.id)
                  FROM {forum_discussion_subs} fds
                  $join
                 WHERE fds.userid = :userid";
        $discussionsubs = $DB->count_records_sql($sql, ['userid' => $user->id]);

        return $forumsubs + $discussionsubs;
    }

}
