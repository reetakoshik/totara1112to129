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
 * @package enrol_flatfile
 */

namespace enrol_flatfile\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * Class pending_enrolments
 *
 * This concerns data that was loaded from a 'flatfile' enrolment file, but for which the start date is in the future.
 *
 * Data that goes into the user_enrolments table once the enrolment is finalised (no longer pending) is dealt with by
 * the general course_enrolments item.
 */
class pending_enrolments extends item {

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
        return 200;
    }

    /**
     * This item allows purging of data.
     *
     * @param int $userstatus
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * This item allows exporting of data.
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * This item allows counting of data.
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * This item can be executed within course, category and system contexts.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_COURSE, CONTEXT_COURSECAT, CONTEXT_SYSTEM];
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

        $contextjoin  = self::get_courses_context_join($context, 'ef.courseid');

        $sql = 'SELECT ef.id
                  FROM {enrol_flatfile} ef
                  ' . $contextjoin . '
                 WHERE ef.userid = :userid';
        $params = ['userid' => $user->id];
        $ids = $DB->get_fieldset_sql($sql, $params);
        $DB->delete_records_list('enrol_flatfile', 'id', $ids);

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

        $contextjoin  = self::get_courses_context_join($context, 'ef.courseid');

        $sql = 'SELECT ef.*
                  FROM {enrol_flatfile} ef
                  ' . $contextjoin . '
                 WHERE ef.userid = :userid';
        $params = ['userid' => $user->id];
        $export->data = $DB->get_records_sql($sql, $params);

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

        $contextjoin  = self::get_courses_context_join($context, 'ef.courseid');

        $sql = 'SELECT COUNT(ef.id)
                  FROM {enrol_flatfile} ef
                  ' . $contextjoin . '
                 WHERE ef.userid = :userid';
        $params = ['userid' => $user->id];
        return $DB->count_records_sql($sql, $params);
    }
}