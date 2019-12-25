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
 * @package block_comments
 */

namespace block_comments\userdata;

use block_comments\event\comment_deleted;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Purge, export and counting of comments entered by the comment block.
 */
class comments extends \totara_userdata\userdata\item {

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
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
     * Purge user data for this item.
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

        $comments = self::get_comments($user, $context);
        $commentids = array_keys($comments);

        if (!empty($commentids)) {
            $DB->delete_records_list('comments', 'id', $commentids);

            // Fire events for the comments deleted.
            foreach ($comments as $comment) {
                $event = comment_deleted::create(
                    array(
                        'context' => \context::instance_by_id($comment->contextid),
                        'objectid' => $comment->id,
                        'other' => [
                            'itemid' => $comment->itemid
                        ]
                    ));
                $event->add_record_snapshot('comments', $comment);
                $event->trigger();
            }
        }

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
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        $export = new export();
        $export->data = array_values(self::get_comments($user, $context));
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
        return count(self::get_comments($user, $context));
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return array
     */
    private static function get_comments(target_user $user, \context $context): array {
        global $DB;

        // The comments are stored either with
        // contextlevel user, system or course.
        // User contextlevel is included in system for this item.
        $join = '';
        if ($context->contextlevel == CONTEXT_COURSE) {
            $join = "JOIN {context} ctx
                       ON c.contextid = ctx.id
                            AND ctx.contextlevel = " . CONTEXT_COURSE . "
                            AND ctx.path = '" . $context->path . "'";
        } else if ($context->contextlevel == CONTEXT_COURSECAT) {
            $join = "JOIN {context} ctx
                       ON c.contextid = ctx.id
                            AND ctx.contextlevel = " . CONTEXT_COURSE . "
                            AND ctx.path LIKE '{$context->path}/%'";
        }

        $sql = "
            SELECT c.*
              FROM {comments} c
              $join
             WHERE c.userid = :userid AND c.component = 'block_comments'
        ";

        return $DB->get_records_sql($sql, ['userid' => $user->id]);
    }

}