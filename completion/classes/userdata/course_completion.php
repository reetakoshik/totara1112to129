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
 * @package core_completion
 */

namespace core_completion\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

/**
 * Class course_completion
 *
 * This primarily covers course completions, hence it only applies for course contexts and above. However, it does deal
 * with course completion history and activity (course module) completion as well, since these are not currently being
 * dealt with on their own.
 *
 * Events:
 *   - Normally a course_module_completion_updated event would be triggered if a course module was changed. This event is
 *     not fired here as we are removing the record altogether. The event is designed for when the record is changed but
 *     not removed, which we cannot do.
 *
 * Caches:
 *   - Caches will be purged for the user/course combination after purging. This applies to the completion and coursecompletion
 *     application caches.
 *
 * @package core_completion
 */
class course_completion extends \totara_userdata\userdata\item {

    public static function is_countable() {
        return true;
    }

    public static function is_exportable() {
        return true;
    }

    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Although we return course_modules_completion, it's the course completions that are the main
     * subject of this class. So the module context level is not applicable here.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
    }

    protected static function purge(\totara_userdata\userdata\target_user $user, \context $context) {
        global $DB;

        // We are touching too many records to put these operations inside a transaction.
        // However, if any given query fails, you should not end up with fatal errors.
        // Starting from modules and working upwards should also reduce but not eliminate
        // the chance of re-completion.

        // Part 1: Get the module completions for this user in the given context and delete them.
        // Putting them into batches does not seem necessary here.
        $usermodulecompletions = $DB->get_fieldset_sql(
                'SELECT cmc.id
                       FROM {course_modules_completion} cmc
                       JOIN {course_modules} cm
                         ON cm.id = cmc.coursemoduleid
                       ' . self::get_courses_context_join($context, 'cm.course') . '
                      WHERE cmc.userid = :userid',
                ['userid' => $user->id]
        );
        $DB->delete_records_list('course_modules_completion', 'id', $usermodulecompletions);

        // Part 2: Get all the courses on the site and delete any records that contain them and the user.
        // The main reason for this is that a user could eventually have a huge amount of records in their
        // log table. If we're fetching courses first before deleting those, we may as well follow this
        // process for the other records that take a course id.
        $courseids = $DB->get_fieldset_sql(
                'SELECT c.id 
                       FROM {course} c 
                       ' . self::get_courses_context_join($context, 'c.id')
        );

        $completioncache = \cache::make('core', 'completion');
        $coursecompletioncache = \cache::make('core', 'coursecompletion');

        // Do this batch by batch in case there are a lot of courses on the site.
        $coursebatches = array_chunk($courseids, $DB->get_max_in_params());
        foreach ($coursebatches as $coursebatch) {
            list($courseinsql, $courseinparams) = $DB->get_in_or_equal($coursebatch, SQL_PARAMS_NAMED);
            $usercourseparams = array_merge(['userid' => $user->id], $courseinparams);

            $DB->delete_records_select(
                    'course_completion_crit_compl',
                    'userid = :userid AND course ' . $courseinsql,
                    $usercourseparams
            );

            $DB->delete_records_select(
                    'course_completions',
                    'userid = :userid AND course ' . $courseinsql,
                    $usercourseparams
            );

            $DB->delete_records_select(
                    'course_completion_history',
                    'userid = :userid AND courseid ' . $courseinsql,
                    $usercourseparams
            );

            $DB->delete_records_select(
                    'course_completion_log',
                    'userid = :userid AND courseid ' . $courseinsql,
                    $usercourseparams
            );

            self::purge_data_block_totara_stats_completion($courseinsql, $usercourseparams);

            // Delete cache keys for completion and course completion. Both caches use same key format.
            $cachekeys = [];
            foreach($coursebatch as $courseid) {
                $cachekeys[] = $user->id . '_' . $courseid;
            }
            $completioncache->delete_many($cachekeys);
            $coursecompletioncache->delete_many($cachekeys);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new export();

        $export->data['course_completions'] = $DB->get_records_sql(
                'SELECT cc.*
                       FROM {course_completions} cc 
                       ' . self::get_courses_context_join($context, 'cc.course') . '
                      WHERE cc.userid = ?',
                [$user->id]
        );

        $export->data['course_completion_crit_compl'] = $DB->get_records_sql(
                'SELECT critcomp.*
                       FROM {course_completion_crit_compl} critcomp 
                       ' . self::get_courses_context_join($context, 'critcomp.course') . '
                      WHERE critcomp.userid = ?',
                [$user->id]
        );

        $export->data['course_modules_completion'] = $DB->get_records_sql(
                'SELECT cmc.*
                       FROM {course_modules_completion} cmc
                       JOIN {course_modules} cm
                         ON cm.id = cmc.coursemoduleid
                       ' . self::get_courses_context_join($context, 'cm.course') . '
                      WHERE cmc.userid = ?',
                [$user->id]
        );

        $export->data['course_completion_history'] = $DB->get_records_sql(
                'SELECT cch.*
                       FROM {course_completion_history} cch 
                       ' . self::get_courses_context_join($context, 'cch.courseid') . '
                      WHERE cch.userid = ?',
                [$user->id]
        );

        $export->data['course_completion_log'] = $DB->get_records_sql(
                'SELECT ccl.*
                       FROM {course_completion_log} ccl 
                       ' . self::get_courses_context_join($context, 'ccl.courseid') . '
                      WHERE ccl.userid = ?',
                [$user->id]
        );

        $export->data['block_totara_stats'] = self::get_export_data_block_totara_stats_completion($user, $context);

        return $export;
    }

    /**
     * Counts the number of course completion records that a user has within the given context.
     *
     * @param target_user $user
     * @param \context $context
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records_sql(
                'SELECT COUNT(cc.id)
                       FROM {course_completions} cc
                       ' . self::get_courses_context_join($context, 'cc.course') . '
                      WHERE cc.userid = ?',
                [$user->id]
        );
    }

    /**
     * This exists to separate out logic for purging data in the totara stats block.
     * This is separate data which may be given its own item one day.
     *
     * @param string $courseinsql
     * @param array $usercourseparams
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function purge_data_block_totara_stats_completion(string $courseinsql, array $usercourseparams) {
        global $CFG, $DB;

        // It was overkill to deal with this block in its own setting. So we're deleting its completion data here.
        require_once($CFG->dirroot . '/blocks/totara_stats/locallib.php');
        list($eventtypeinsql, $eventtypeinparams) = $DB->get_in_or_equal([
                STATS_EVENT_TIME_SPENT,
                STATS_EVENT_COURSE_STARTED,
                STATS_EVENT_COURSE_COMPLETE
        ],
                SQL_PARAMS_NAMED
        );

        $DB->delete_records_select(
                'block_totara_stats',
                'userid = :userid AND data2 ' . $courseinsql . ' AND eventtype ' . $eventtypeinsql,
                array_merge($usercourseparams, $eventtypeinparams)
        );
    }

    /**
     * This exists to separate out logic for exporting data in the totara stats block.
     * This is separate data which may be given its own item one day.
     *
     * @param target_user $user
     * @param \context $context
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function get_export_data_block_totara_stats_completion(target_user $user, \context $context) {
        global $CFG, $DB;

        // It was overkill to deal with this block in its own setting. So we're exporting its completion data here.
        require_once($CFG->dirroot . '/blocks/totara_stats/locallib.php');
        list($eventtypeinsql, $eventtypeinparams) = $DB->get_in_or_equal([
                STATS_EVENT_TIME_SPENT,
                STATS_EVENT_COURSE_STARTED,
                STATS_EVENT_COURSE_COMPLETE
        ],
                SQL_PARAMS_NAMED
        );

        return $DB->get_records_sql(
                'SELECT bts.*
                       FROM {block_totara_stats} bts 
                       ' . self::get_courses_context_join($context, 'bts.data2') . '
                      WHERE bts.userid = :userid
                        AND bts.eventtype ' . $eventtypeinsql,
                array_merge(['userid' => $user->id], $eventtypeinparams)
        );
    }
}
