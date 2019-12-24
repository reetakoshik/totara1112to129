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
 * Core functions for personal goal userdata items.
 */
class personal_helper {

    /**
     * Purge a user's data relating to personal goals.
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


        $systemcontext = \context_system::instance();

        // Get the ids for all of the user's personal goals.
        $goals = $DB->get_records('goal_personal', ['userid' => $user->id]);
        if (!empty($goals)) {
            $transaction = $DB->start_delegated_transaction();
            $fs = get_file_storage();

            // Clear out any historical scale change information and custom fields.
            foreach ($goals as $goal) {
                $DB->delete_records('goal_item_history', ['scope' => \goal::SCOPE_PERSONAL, 'itemid' => $goal->id]);

                // Check the context exists just in case they were deleted a while ago.
                if (!empty($user->contextid)) {
                    // Clear out any files in the goal description.
                    $fs->delete_area_files($user->contextid, 'totara_hierarchy', 'goal', $goal->id);
                }

                // Note: The field is called goal_userid, but it is actually a foreignkey on the goal_personal.id field.
                $dataids = $DB->get_fieldset_select('goal_user_info_data', 'id', 'goal_userid = :gid', ['gid' => $goal->id]);
                if (!empty($dataids)) {

                    // Do a quick loop through and clear any files related to the customfield data.
                    foreach ($dataids as $dataid) {
                        $fs->delete_area_files($systemcontext->id, 'totara_customfield', 'goal_user', $dataid); // Textareas.
                        $fs->delete_area_files($systemcontext->id, 'totara_customfield', 'goal_user_filemgr', $dataid); // Files.
                    }

                    // Clear out any extra params for the data.
                    list($dinsql, $dparams) = $DB->get_in_or_equal($dataids, SQL_PARAMS_NAMED, 'data');
                    $DB->delete_records_select('goal_user_info_data_param', "dataid {$dinsql}", $dparams);

                    // Now delete the custom field data itself.
                    $DB->delete_records('goal_user_info_data', ['goal_userid' => $goal->id]);
                }
            }

            // Finally delete all the goals.
            $DB->delete_records('goal_personal', ['userid' => $user->id]);

            // Throw a deletion event for all the personal goals.
            foreach ($goals as $goal) {
                \hierarchy_goal\event\personal_deleted::create_from_instance($goal)->trigger();
            }

            $transaction->allow_commit();
        }

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export a user's data relating to personal goals.
     *
     * @param target_user $user
     * @param \context $context
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    public static function export(target_user $user, \context $context) {
        global $DB;

        $fs = get_file_storage();
        $systemcontext = \context_system::instance();
        $export = new \totara_userdata\userdata\export();

        $itemdata = [];
        $goals = $DB->get_records('goal_personal', ['userid' => $user->id]);
        foreach ($goals as $goal) {
            $itemdata = (array)$goal;
            $itemdata['files'] = ['desc' => [], 'cust' => []];

            // Get any scale value change history for the goal.
            if (!empty($goal->scaleid)) {
                $itemdata['history'] = $DB->get_records('goal_item_history', ['scope' => \goal::SCOPE_PERSONAL, 'itemid' => $goal->id]);
            }

            if (!empty($user->contextid)) {
                // Include any files from the goal description.
                $files = $fs->get_area_files($user->contextid, 'totara_hierarchy', 'goal', $goal->id, "timemodified", false);
                foreach ($files as $file) {
                     $itemdata['files']['desc'][] = $export->add_file($file);
                }
            }

            // Get any related custom field information.
            if (!empty($goal->typeid)) {
                $sql = "SELECT d.id, f.shortname, d.data
                          FROM {goal_user_info_data} d
                          JOIN {goal_user_info_field} f
                            ON f.id = d.fieldid
                         WHERE d.goal_userid = :gid";
                $fields = $DB->get_records_sql($sql, ['gid' => $goal->id]);
                foreach ($fields as $field) {
                    $fieldname = 'cf_'.$field->shortname;
                    $itemdata[$fieldname] = ['shortname' => $field->shortname, 'data' => $field->data];
                    $itemdata[$fieldname]['params'] = $DB->get_records('goal_user_info_data_param', ['dataid' => $field->id]);

                    // Get any files associated with the custom field, either in a text area or a file upload.
                    $fs1 = $fs->get_area_files($systemcontext->id, 'totara_customfield', 'goal_user', $field->id, "timemodified", false);
                    $fs2 = $fs->get_area_files($systemcontext->id, 'totara_customfield', 'goal_user_filemgr', $field->id, "timemodified", false);
                    $files = array_merge($fs1, $fs2);
                    foreach ($files as $file) {
                        $itemdata['files']['cust'][] = $export->add_file($file);
                    }
                }
            }

            // Write goal to export.
            $export->data[] = $itemdata;
        }

        return $export;
    }

    /**
     * Count a user's data relating to personal goals.
     *
     * @param target_user $user
     * @param \context $context
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    public static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('goal_personal', ['userid' => $user->id]);
    }
}
