<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_calendar
 */

namespace core_calendar\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

require_once($CFG->dirroot . "/calendar/lib.php");

/**
 * This is the item for the events a user has added to their calendar
 * They are personal data and about the user so exporting them and purging
 */
class events extends item {

    /**
     * Gets the user events of a target user.
     *
     * @param target_user $user
     * @return array
     */
    private static function get_all_user_events(target_user $user): array {
        global $DB;
        $sql = "userid = :userid AND ((courseid = 0 AND groupid = 0) OR eventtype = :userevent)";
        $params = [
            'userid' => $user->id,
            'userevent' => 'user'
        ];
        return $DB->get_records_select('event', $sql, $params, 'id DESC');
    }

    /**
     * Put the item in the user category as there isn't a calender category.
     *
     * @return string
     */
    public static function get_main_component() {
        return 'core_user';
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
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;
        $events = self::get_all_user_events($user);
        if (empty($events)) {
            return self::RESULT_STATUS_SUCCESS;
        }
        if ($user->status != target_user::STATUS_DELETED) {
            foreach ($events as $event) {
                $eventobject = \calendar_event::load($event);
                $eventobject->delete(false);
            }
        } else {
            $ids = array_keys($events);
            list($insql, $params) = $DB->get_in_or_equal($ids);
            $DB->delete_records_select('event', "id $insql", $params);
            foreach ($ids as $id) {
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                    $user->contextid,
                    'calendar',
                    'event_description',
                    $id
                );
                foreach ($files as $file) {
                    $file->delete();
                }
            }
        }
        return self::RESULT_STATUS_SUCCESS;
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
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        $export = new export();
        $events = self::get_all_user_events($user);
        // If we have the users context id then we can delete the files.
        if ($user->status != target_user::STATUS_DELETED) {
            $fs = get_file_storage();
            foreach ($events as $event) {
                $event->files = [];
                $files = $fs->get_area_files(
                    $user->contextid,
                    'calendar',
                    'event_description',
                    $event->id,
                    '',
                    false
                );
                foreach ($files as $file) {
                    $event->files[] = (object)$export->add_file($file);
                }
                $export->data[] = $event;
            }
        } else {
            $export->data = array_values($events);
        }
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
        return count(self::get_all_user_events($user));
    }
}