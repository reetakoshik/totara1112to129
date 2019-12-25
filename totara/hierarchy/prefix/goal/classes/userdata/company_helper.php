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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_hierarchy
 */

namespace hierarchy_goal\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * Core functions for company goal userdata items.
 */
class company_helper {

    /**
     *
     * Purge a user's data relating to company goals.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    public static function purge(target_user $user, \context $context) {
        global $DB;

        // Store the goal assignments before deletion so we can throw events.
        $goals = $DB->get_records('goal_user_assignment', ['userid' => $user->id]);
        $records = $DB->get_records('goal_record', ['userid' => $user->id]);


        foreach ($records as $record) {
            $DB->delete_records('goal_item_history', ['itemid' => $record->id, 'scope' => \goal::SCOPE_COMPANY]);
        }

        // We don't need to do anything to the company goal itself, that is sitedata, we just need to remove user assignments and records.
        $DB->delete_records('goal_record', ['userid' => $user->id]);
        $DB->delete_records('goal_user_assignment', ['userid' => $user->id]);

        foreach ($goals as $goal) {
            // Throw this event mainly for consistency and logging.
            if ($goal->assigntype == GOAL_ASSIGNMENT_INDIVIDUAL) {
               \hierarchy_goal\event\assignment_user_deleted::create_from_instance($goal)->trigger();
            }
        }

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export a user's data relating to company goals.
     *
     * @param target_user $user
     * @param \context $context
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    public static function export(target_user $user, \context $context) {
        global $DB;

        $export = new \totara_userdata\userdata\export();

        // Get all of the active goal user assignments.
        $active = [];
        $goals = $DB->get_records('goal_user_assignment', ['userid' => $user->id]);
        foreach ($goals as $goaldata) {
            $records = $DB->get_records('goal_record', ['userid' => $user->id, 'goalid' => $goaldata->goalid]);

            $goaldata->scaledata = [];
            foreach ($records as $record) {
                // Include record and any relevant scale history data.
                $scalehistory = $DB->get_records('goal_item_history', ['itemid' => $record->id, 'scope' => \goal::SCOPE_COMPANY]);
                $record->scalehistory = $scalehistory;

                $goaldata->scaledata[] = $record;
            }

            $active[] = $goaldata;
        }

        // Include the old scale data for goals the user is no longer assigned to.
        $deleted = $DB->get_records('goal_record', ['userid' => $user->id, 'deleted' => 1]);
        foreach ($deleted as $record) {
                $scalehistory = $DB->get_records('goal_item_history', ['itemid' => $record->id, 'scope' => \goal::SCOPE_COMPANY]);
                $record->scalehistory = $scalehistory;
        }
        $export->data = ['active' => $active, 'deleted' => $deleted];

        return $export;
    }

    /**
     * Count a user's data relating to company goals.
     *
     * @param target_user $user
     * @param \context $context
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    public static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('goal_user_assignment', ['userid' => $user->id]);
    }
}
