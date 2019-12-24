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

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

require_once($CFG->dirroot . '/notes/lib.php');

/**
 * Userdata item for core_notes.
 * This is a user data item for notes about a user that the user can see.
 */
class notes_export_visible extends item {

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
        return 1010;
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
     * Gets the notes in the context that the user can see.
     * System context includes all notes and
     * course context doesnt include site notes event though it has a courseid field
     *
     * @param target_user $user
     * @param \context $context
     * @return array
     */
    private static function get_notes(target_user $user, \context $context): array {
        global $DB;
        $courseids = [];
        $notes = [];

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $courseids = $DB->get_fieldset_select(
                'post',
                'DISTINCT(courseid)',
                "userid = :userid AND module = 'notes'",
                ['userid' => $user->id]
            );

            // If the user has notes:view in system or a course context then they can see all site notes.
            $sitenotesvisible = has_capability('moodle/notes:view', \context_system::instance(), $user->id);
            if (!$sitenotesvisible) {
                foreach ($courseids as $courseid) {
                    if (has_capability('moodle/notes:view', \context_course::instance($courseid), $user->id)) {
                        $sitenotesvisible = true;
                        break;
                    }
                }
            }
            if ($sitenotesvisible) {
                $notes += note_list(0, $user->id, NOTES_STATE_SITE);
            }
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $courseids[] = $context->instanceid;
        } else if ($context->contextlevel == CONTEXT_COURSECAT) {
            $courseids = \coursecat::get($context->instanceid)->get_courses([
                'recursive' => true,
                'idonly' => true
            ]);
        } else {
            throw new \coding_exception("The context level is not supported");
        }

        foreach ($courseids as $courseid) {
            if (has_capability('moodle/notes:view', \context_course::instance($courseid), $user)) {
                $notes += note_list($courseid, $user->id, NOTES_STATE_DRAFT, $user->id);
                $notes += note_list($courseid, $user->id, NOTES_STATE_PUBLIC);
            }
        }
        return array_values($notes);
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return false;
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
     * Exports user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        $data = new export();
        $data->data = self::get_notes($user, $context);
        return $data;
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