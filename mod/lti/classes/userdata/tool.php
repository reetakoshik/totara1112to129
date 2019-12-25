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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_lti
 */

namespace mod_lti\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;


defined('MOODLE_INTERNAL') || die();

/**
 * Handler for submissions via the LTI tool.
 */
class tool extends item {
    /**
     * {@inheritDoc}
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
    }


    /**
     * {@inheritDoc}
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function purge(target_user $user, \context $context) {
        $userid = $user->get_user_record()->id;
        $submissions = self::submissions($context, $userid);

        self::purge_user_submissions($submissions);
        self::purge_user_submissions_history($context, $userid);
        self::purge_user_gradebook($submissions);

        return self::RESULT_STATUS_SUCCESS;
    }


    /**
     * Returns a list of submission details that need to be deleted.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     *
     * @return array a list of submissions. Each submission object comprises the
     *         submission id and full details of the associated lti instance.
     */
    private static function submissions(\context $context, int $userid) {
        global $DB;

        $module = 'lti';
        $params = ['userid' => $userid];

        $target = 'submission';
        $contextjoin = self::get_activities_join($context, $module, "$target.ltiid", $module);
        $filter = "
            SELECT $module.*, $target.id as submissionid
              FROM {lti_submission} $target
              $contextjoin
             WHERE $target.userid = :userid
        ";

        return $DB->get_records_sql($filter, $params);
    }


    /**
     * Deletes user submissions.
     *
     * @param array $submissions submissions to be deleted as returned by the
     *        submissions() method.
     */
    private static function purge_user_submissions(array $submissions) {
        global $DB;

        $ids = [];
        foreach ($submissions as $submission) {
            $ids[] = $submission->submissionid;
        }

        $DB->delete_records_list("lti_submission", "id", $ids);
    }

    /**
     * Deletes historic submissions records.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_submissions_history(\context $context, int $userid) {
        global $DB;

        $contextjoin = self::get_activities_join($context, 'lti', 'l.id');
        $select = "ltiid IN (SELECT l.id FROM {lti} l {$contextjoin}) AND userid = :userid";
        $DB->delete_records_select('lti_submission_history', $select, ['userid' => $userid]);
    }

    /**
     * Deletes grades from the gradebook.
     *
     * @param array $submissions submissions to be deleted as returned by the
     *        submissions() method.
     */
    private static function purge_user_gradebook(array $submissions) {
        foreach ($submissions as $lti) {
            lti_grade_item_delete($lti);
        }
    }


    /**
     * {@inheritDoc}
     */
    public static function is_exportable() {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $module = 'lti';
        $params = ['userid' => $user->get_user_record()->id];

        $target = 'submission';
        $contextjoin = self::get_activities_join($context, $module, "$target.ltiid", $module);
        $filter = "
            SELECT $target.id, $target.datesubmitted, $target.gradepercent, $module.toolurl, course.fullname
              FROM {lti_submission} $target
              $contextjoin
              JOIN {course} course ON course.id = $module.course
             WHERE $target.userid = :userid
        ";

        $export = new \totara_userdata\userdata\export();
        foreach ($DB->get_records_sql($filter, $params) as $submission) {
            $export->data[] = [
                'course' => $submission->fullname,
                'lti url' => $submission->toolurl,
                'grade' => sprintf("%.02f%%", $submission->gradepercent),
                'submission time' => $submission->datesubmitted
            ];
        }

        return $export;
    }


    /**
     * {@inheritDoc}
     */
    public static function is_countable() {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $module = 'lti';
        $params = ['userid' => $user->get_user_record()->id];

        $target = 'submission';
        $contextjoin = self::get_activities_join($context, $module, "$target.ltiid", $module);
        $filter = "
            SELECT COUNT($target.id)
              FROM {lti_submission} $target
              $contextjoin
             WHERE $target.userid = :userid
        ";

        return $DB->count_records_sql($filter, $params);
    }
}
