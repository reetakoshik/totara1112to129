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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_badges
 */

namespace core_badges\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles connected backpacks belonging to the user.
 *
 * Equivalent code:
 *   - Purging: badges/mybackpack.php (no api)
 *
 * Notes:
 *   - The badge API only allows a single connected backpack, however the database doesn't enforce this.
 *     As such this userdata item takes the precaution and deals with multiple.
 *
 * Events:
 *   - There are no events associated with disconnecting a backpack,
 *
 * Files:
 *   - There are no files associated with connected backpacks.
 *
 * Caches:
 *   - There is a cache used to store badges within collections being imported from connected backpacks.
 *         core,externalbadges
 *     The key is the user->id.
 *
 * @package core_badge
 */
class connectedbackpacks extends item {

    /**
     * Returns an array of compatible context levels when looking at issued badges.
     *
     * @return int[] One or more CONTEXT_
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM];
    }

    /**
     * Can you disconnect connected backpacks.
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge connected backpack information.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/badgeslib.php');

        // We fetch multiple backpacks. The system actually only allows one to be created.
        // However it doesn't enforce this. To be sure we fully clean up backpacks we'll trust there is one, and suspect there is more.
        $backpacks = $DB->get_records('badge_backpack', ['userid' => $user->id]);
        if (empty($backpacks)) {
            return item::RESULT_STATUS_SUCCESS;
        }

        list($select, $params) = $DB->get_in_or_equal(array_keys($backpacks));

        // No need for transactions
        $DB->delete_records_select('badge_external', 'backpackid ' . $select, $params);
        $DB->delete_records_select('badge_backpack', 'id ' . $select, $params);

        $badgescache = \cache::make('core', 'externalbadges');
        $badgescache->delete($user->id);

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can connected backpack information be exported.
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export connected backpacks.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export an export object, with data containing an array of badges, and files containing the
     *     downloadable badges.
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $backpacks = $DB->get_records('badge_backpack', ['userid' => $user->id], 'id', 'id, email, backpackurl, backpackuid');
        foreach ($backpacks as &$backpack) {
            $collections = $DB->get_records('badge_external', ['backpackid' => $backpack->id], 'collectionid', 'collectionid');
            unset($backpack->id); // We don't need this any more, and we don't want it in the export.
            $backpack->connectedcollections = array_keys($collections);
        }

        $result = new export();
        $result->data = $backpacks;

        return $result;
    }

    /**
     * Can connected backpacks be counted.
     * How much data is there?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count of the backpacks a user has connected to.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;
        return $DB->count_records('badge_backpack', ['userid' => $user->id]);
    }

}
