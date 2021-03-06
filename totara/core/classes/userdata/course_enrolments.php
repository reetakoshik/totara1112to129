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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;

/**
 * Class course_enrolments
 *
 * Allows for purge, export and count of core enrolment data generated by enrolment plugins.
 *
 * This mainly deals with data that is used by enrolment plugins in general.
 * If a plugin has data in a table specific only to that plugin:
 * - For purging, that data must be deleted when unenrolling via the standard enrolment API or the plugin may need its own item.
 * - For exporting, the additional data will not be exported by this item. The plugin would require its own item if the data
 *   should be exported.
 *
 * There are additional areas dealt with by this item as they do not make sense to have as their own item and are closely
 * tied to enrolments:
 * - Groups
 * - Reminder sent records
 */
class course_enrolments extends item {

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     *
     * NOTE: this can be overridden to move item to a different form group in UI,
     *       for example local plugins and items to standard activities
     *       or blocks may move items to their related plugins.
     */
    public static function get_main_component() {
        return 'core_enrol';
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 100;
    }

    /**
     * Compatible with system, category and course contexts.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
    }

    /**
     * This item allows counting.
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * This item allows exporting.
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * This item allows purging regardless of user status.
     *
     * @param int $userstatus
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

        $plugins = enrol_get_plugins(false);
        $contextjoin = self::get_courses_context_join($context, 'e.courseid');

        $pluginsql = "SELECT e.*
                      FROM {enrol} e
                      JOIN {user_enrolments} ue
                      ON (ue.enrolid = e.id)
                      {$contextjoin}
                      WHERE ue.userid = :userid
                      AND e.enrol = :enrolname";
        $params = ['userid' => $user->id];

        foreach($plugins as $plugin) {
            $pluginparams = array_merge($params, ['enrolname' => $plugin->get_name()]);
            $rs = $DB->get_recordset_sql($pluginsql, $pluginparams);
            foreach ($rs as $instance) {
                $plugin->unenrol_user($instance, $user->id);
            }
            $rs->close();
        }

        // We delete records of reminder_sent here as they make little sense as a standalone item.
        $sentsql = "SELECT rs.id
                    FROM {reminder_sent} rs
                    JOIN {reminder} r
                    ON r.id = rs.reminderid
                    " . self::get_courses_context_join($context, 'r.courseid') . "
                    WHERE rs.userid = :userid";
        $sentids = $DB->get_fieldset_sql($sentsql, $params);
        $DB->delete_records_list('reminder_sent', 'id', $sentids);

        // Replicating behaviour of delete_user(), remaining records are cleaned up for plugins that don't remove them properly.
        $remainingsql = "SELECT ue.id
                         FROM {enrol} e
                         JOIN {user_enrolments} ue
                         ON (ue.enrolid = e.id)
                         {$contextjoin}
                         WHERE ue.userid = :userid";

        $DB->delete_records_list(
            'user_enrolments',
            'id',
            $DB->get_fieldset_sql($remainingsql, $params)
        );

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new export();

        $contextjoin = self::get_courses_context_join($context, 'e.courseid');
        $sql = "SELECT ue.*, e.courseid, course.fullname
                FROM {enrol} e
                JOIN {user_enrolments} ue
                ON (ue.enrolid = e.id)
                {$contextjoin}
                JOIN {course} course
                ON course.id = e.courseid
                WHERE ue.userid = :userid";
        $export->data['enrolments'] = $DB->get_records_sql($sql, ['userid' => $user->id]);
        $export->data['groups'] = [];
        foreach ($export->data['enrolments'] as $enrolment) {
            // We could save them as part of a hierarchy under the 'enrolments' array, but really they're a separate
            // set of data that relate to the each course rather than each enrolment.
            $export->data['groups'][$enrolment->courseid] = groups_get_user_groups($enrolment->courseid, $user->id);
        }

        // We export records of reminder_sent here as they make little sense as a standalone item.
        $sentsql = "SELECT rs.*, r.title
                    FROM {reminder_sent} rs
                    JOIN {reminder} r
                    ON r.id = rs.reminderid
                    " . self::get_courses_context_join($context, 'r.courseid') . "
                    WHERE rs.userid = :userid";
        $export->data['reminders'] = $DB->get_records_sql($sentsql, ['userid' => $user->id]);

        return $export;
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

        $contextjoin = self::get_courses_context_join($context, 'e.courseid');
        $sql = "SELECT COUNT(ue.id)
                FROM {enrol} e
                JOIN {user_enrolments} ue
                ON (ue.enrolid = e.id)
                {$contextjoin}
                WHERE ue.userid = :userid";
        $params = ['userid' => $user->id];

        return $DB->count_records_sql($sql, $params);
    }
}