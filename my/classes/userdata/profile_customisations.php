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
 * @package core_my
 */

namespace core_my\userdata;

require_once($CFG->dirroot . '/my/lib.php');

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * There is no personal data to export in the dashboard.
 * So Just reset the edited dashboards (user-profile page) to their original state.
 */
class profile_customisations extends item {

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     *
     * NOTE: this can be overridden to move item to a different form group in UI,
     *       for example local plugins and items to standard activities
     *       or blocks may move items to their related plugins.
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
        if ($user->status != target_user::STATUS_DELETED) {
            $purged = my_reset_page($user->id, MY_PAGE_PUBLIC, 'user-profile');
            if (!$purged) {
                return self::RESULT_STATUS_ERROR;
            }
        } else {

            $page = $DB->get_record('my_pages', [
                'userid' => $user->id,
                'private' => MY_PAGE_PUBLIC
            ], 'id');

            // If there are no pages to delete then just return.
            if (!$page) {
                return self::RESULT_STATUS_SUCCESS;
            }

            // This will delete all the blocks in the dashboard in case they weren't deleted by delete_user().
            // Warning this will fail if the block initialisation loads the users context.
            if (isset($user->contextid)) {
                $blocks = $DB->get_records('block_instances', [
                    'parentcontextid' => $user->contextid,
                    'pagetypepattern' => 'user-profile'
                ]);
                foreach ($blocks as $block) {
                    if (is_null($block->subpagepattern) || $block->subpagepattern == $page->id) {
                        blocks_delete_instance($block);
                    }
                }
                $DB->delete_records('block_positions', [
                    'subpage' => $page->id,
                    'pagetype' => 'user-profile',
                'contextid' => $user->contextid]);
            }

            $DB->delete_records('my_pages', ['id' => $page->id]);

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
     * How much data is there?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     * There can only be at most 1.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        return (int)(my_get_page($user->id,  MY_PAGE_PUBLIC)->userid != null);
    }
}