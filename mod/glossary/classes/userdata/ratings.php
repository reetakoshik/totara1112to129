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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_glossary
 */
namespace mod_glossary\userdata;

use \context;
use \context_module;
use \rating_manager;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

/**
 * Users ratings of others glossary entries.
 */
class ratings extends item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['ratings', 'core_rating'];
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 300;
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     *
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
     * @param \context    $context restriction for purging e.g., system context for everything, course context for
     *                             purging one course
     *
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/rating/lib.php');
        require_once($CFG->dirroot . '/mod/glossary/lib.php');

        $rm = new rating_manager();

        $sql = self::build_query(
            "SELECT DISTINCT r.contextid, r.itemid, g.id AS glossaryid, g.assessed, cm.idnumber AS cmidnumber, cm.id AS cmid ",
            self::get_activities_join($context, 'glossary', 'e.glossaryid', 'g')
        );

        $ratings = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        $glossaries = array();
        foreach ($ratings as $rating) {
            if ($rating->assessed) {
                $glossaries[$rating->glossaryid] = $rating->cmidnumber;
            }
            // Delete ratings.
            $glossarycontext = context_module::instance($rating->cmid);
            $rm->delete_ratings((object)[
                'contextid' => $glossarycontext->id,
                'component' => 'mod_glossary',
                'ratingarea' => 'entry',
                'userid' => $user->id]
            );
        }
        $ratings->close();

        // Exported entries.
        $sql = self::build_query(
            "SELECT DISTINCT r.contextid, r.itemid, g.id AS glossaryid, g.assessed, cm.idnumber AS cmidnumber, cm.id AS cmid ",
            self::get_activities_join($context, 'glossary', 'e.sourceglossaryid', 'g')
        );
        $ratings = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($ratings as $rating) {
            if ($rating->assessed) {
                $glossaries[$rating->glossaryid] = $rating->cmidnumber;
            }
            // Delete ratings.
            $glossarycontext = context_module::instance($rating->cmid);
            $rm->delete_ratings((object)[
                'contextid' => $glossarycontext->id,
                'component' => 'mod_glossary',
                'ratingarea' => 'entry',
                'userid' => $user->id]
            );
        }
        $ratings->close();

        // Regrade all affected glossaries that are assessed.
        foreach ($glossaries as $gid => $cmidnumber) {
            $glosssary = $DB->get_record('glossary', ['id' => $gid]);
            $glosssary->cmidnumber = $cmidnumber;
            glossary_update_grades($glosssary);
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
     * @param \context    $context restriction for exporting i.e., system context for everything and course context for
     *                             course export
     *
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or
     *                                              self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        global $DB;

        $export = new export();

        $sql = self::build_query(
            "SELECT r.*, g.id AS glossaryid ",
            self::get_activities_join($context, 'glossary', 'e.glossaryid', 'g')
        );

        $ratings = $DB->get_records_sql($sql, ['userid' => $user->id]);

        // Exported entries.
        $sql = self::build_query(
            "SELECT r.*, g.id AS glossaryid ",
            self::get_activities_join($context, 'glossary', 'e.sourceglossaryid', 'g')
        );
        $exportratings = $DB->get_records_sql($sql, ['userid' => $user->id]);

        foreach (array_merge($ratings, $exportratings) as $rating) {
            $value = $rating->rating;
            if ($rating->scaleid < 0) {
                $scalerecord = $DB->get_record('scale', ['id' => abs($rating->scaleid)]);
                if ($scalerecord) {
                    $scalearray = explode(',', $scalerecord->scale);
                    if (isset($scalearray[$value - 1])) {
                        $value = $scalearray[$value - 1];
                    }
                }
            }
            $export->data[] = array('glossaryid' => $rating->glossaryid, 'entryid' => $rating->itemid, 'value' => $value);;
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
     * @param \context    $context restriction for counting i.e., system context for everything and course context for
     *                             course data
     *
     * @return int  integer is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or
     *              self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $sql = self::build_query(
            "SELECT COUNT(r.id)",
            self::get_activities_join($context, 'glossary', 'e.glossaryid', 'g')
        );
        $count = $DB->count_records_sql($sql, ['userid' => $user->id]);

        // Exported entries.
        $sql = self::build_query(
            "SELECT COUNT(r.id)",
            self::get_activities_join($context, 'glossary', 'e.sourceglossaryid', 'g')
        );

        return $count + $DB->count_records_sql($sql, ['userid' => $user->id]);
    }

    /**
     * Build the query
     *
     * @param string $select
     * @param string $join
     *
     * @return string
     */
    private static function build_query(string $select, string $join): string {
        return "$select
                FROM {rating} r
                  JOIN {glossary_entries} e ON e.id = r.itemid
                 $join
                 WHERE r.userid = :userid
                    AND r.component = 'mod_glossary'
                    AND r.ratingarea = 'entry'
                    AND r.contextid = ctx.id";
    }
}
