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

use context_module;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of purging, exporting and counting the posts (discussions, posts, replies) made by the user.
 * Posts are not deleted but all subjects and contents of the posts are replaced by a placeholder.
 */
class posts extends item {

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

        $join = self::get_activities_join($context, 'forum', 'fd.forum');

        $sql = "SELECT fp.id, fd.forum, fd.course, fd.id as discussionid, cm.id AS cmid
                    FROM {forum_posts} fp
                    JOIN {forum_discussions} fd ON fp.discussion = fd.id
                    $join
                   WHERE fp.userid = :userid";
        $posts = $DB->get_records_sql($sql, ['userid' => $user->id]);

        if (!empty($posts)) {
            list($sqlinorequal, $sqlinorequalparams) = $DB->get_in_or_equal(array_keys($posts), SQL_PARAMS_NAMED);

            // Mark all posts as deleted and empty the content
            $sql = "UPDATE {forum_posts}
                         SET subject = '', message = '', deleted = 1
                       WHERE id $sqlinorequal";
            $DB->execute($sql, $sqlinorequalparams);

            // Empty all names of discussions
            $select = "
                id IN (
                   SELECT discussion
                     FROM {forum_posts}
                    WHERE parent = 0 AND id $sqlinorequal
                )
            ";
            $DB->set_field_select('forum_discussions', 'name', '', $select, $sqlinorequalparams);

            $fs = get_file_storage();
            foreach ($posts as $post) {
                $contextmodule = context_module::instance($post->cmid, IGNORE_MISSING);
                if ($contextmodule) {
                    // Delete attachments.
                    $fs->delete_area_files($contextmodule->id, 'mod_forum', 'attachment', $post->id);
                    $fs->delete_area_files($contextmodule->id, 'mod_forum', 'post', $post->id);
                }
            }
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

        $join = self::get_activities_join($context, 'forum', 'fd.forum');

        $sql = "SELECT fp.*, fd.forum, fd.course, cm.id AS cmid
                    FROM {forum_posts} fp
                    JOIN {forum_discussions} fd ON fp.discussion = fd.id
                    $join
                   WHERE fp.userid = :userid AND fp.deleted <> 1";

        $params = ['userid' => $user->id];
        $posts = $DB->get_records_sql($sql, $params);

        $export = new export();

        $fs = get_file_storage();
        foreach ($posts as $post) {
            $currpost = $post;
            $currpost->attachments = [];
            $currpost->files = [];

            $contextmodule = context_module::instance($post->cmid, IGNORE_MISSING);
            if ($contextmodule) {
                $files = $fs->get_area_files($contextmodule->id, 'mod_forum', 'attachment', $post->id, "timemodified", false);
                foreach ($files as $file) {
                    $currpost->attachments[] = $export->add_file($file);
                }
                $files = $fs->get_area_files($contextmodule->id, 'mod_forum', 'post', $post->id, "timemodified", false);
                foreach ($files as $file) {
                    $currpost->files[] = $export->add_file($file);
                }
            }
            $export->data[] = $currpost;
        }

        return $export;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
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

        $join = self::get_activities_join($context, 'forum', 'fd.forum');

        // Query the posts but ignoring already deleted posts
        $sql = "
            SELECT COUNT(fp.id)
            FROM {forum_posts} fp
            JOIN {forum_discussions} fd ON fp.discussion = fd.id
                $join
            WHERE fp.userid = :userid AND deleted <> 1
         ";

        $count = $DB->count_records_sql($sql, ['userid' => $user->id]);

        return $count;
    }

}
