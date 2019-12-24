<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_notes
 */

namespace core_notes\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

require_once($CFG->dirroot . '/notes/lib.php');

/**
 * Userdata item for core_notes.
 * This is the user data for notes about a user that are hidden from that user.
 */
class notes_purge extends item {

    /**
     * There is no notes component to show the option in so show it in the user component.
     *
     * @return string
     */
    public static function get_main_component() {
        return 'core_user';
    }


    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 1000;
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
     * Gets the notes in the context for the user.
     * System context includes all notes and
     * course context doesnt include site notes event though it has a courseid field
     *
     * @param target_user $user
     * @param \context $context
     * @return array
     */
    private static function get_notes(target_user $user, \context $context): array {
        global $DB;
        $join = self::get_courses_context_join($context, 'p.courseid');
        $sql = "SELECT p.id FROM {post} p $join WHERE p.module = 'notes' AND p.userid = :userid";
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            $sql .= ' AND p.publishstate != \'' . NOTES_STATE_SITE . '\'';
        }
        return $DB->get_fieldset_sql($sql, ['userid' => $user->id]);
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
     * Purges the comments about this user
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        foreach (self::get_notes($user, $context) as $noteid) {
            note_delete(intval($noteid));
        }
        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return false;
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
        return count(self::get_notes($user, $context));
    }
}
