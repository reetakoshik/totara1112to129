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
 * @package mod_workshop
 */

namespace mod_workshop\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Class assessments
 *
 * For deleting assessments created by the target user. These will be assessments of other user's submissions
 * and any assessments of the user's own submissions.
 */
class assessments extends item {

    /**
     * This item allows purging.
     *
     * @param int $userstatus
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
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
     * This item allows counting.
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * This item can be executed within module contexts parent contexts above that.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
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

        $join = self::get_activities_join($context, 'workshop', 'wsub.workshopid');
        $assessments = $DB->get_records_sql(
            "SELECT wa.id, ctx.id AS ctxid
               FROM {workshop_submissions} wsub
             {$join}
               JOIN {workshop_assessments} wa
                 ON wsub.id = wa.submissionid
              WHERE wa.reviewerid = :reviewerid",
                ['reviewerid' => $user->id]
        );

        $assessmentids = array_keys($assessments);

        $DB->delete_records_list('workshop_grades', 'assessmentid', $assessmentids);
        $fs = get_file_storage();
        foreach ($assessments as $assessment) {
            $fs->delete_area_files($assessment->ctxid, 'mod_workshop', 'overallfeedback_content', $assessment->id);
            $fs->delete_area_files($assessment->ctxid, 'mod_workshop', 'overallfeedback_attachment', $assessment->id);
        }
        $DB->delete_records_list('workshop_assessments', 'id', $assessmentids);

        $join = self::get_activities_join($context, 'workshop', 'wagg.workshopid');
        $aggregationids = $DB->get_fieldset_sql(
            "SELECT wagg.id
               FROM {workshop_aggregations} wagg
             {$join}
              WHERE wagg.userid = :userid",
            ['userid' => $user->id]
        );

        $DB->delete_records_list('workshop_aggregations', 'id', $aggregationids);

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

        $fs = get_file_storage();
        $export = new \totara_userdata\userdata\export();

        $join = self::get_activities_join($context, 'workshop', 'wsub.workshopid');
        $assessments = $DB->get_records_sql(
            "SELECT wa.*, ctx.id AS ctxid
               FROM {workshop_submissions} wsub
             {$join}
               JOIN {workshop_assessments} wa
                 ON wsub.id = wa.submissionid
              WHERE wa.reviewerid = :reviewerid",
            ['reviewerid' => $user->id]
        );

        foreach($assessments as $assessment) {
            $assessment->grades = $DB->get_records('workshop_grades', ['assessmentid' => $assessment->id]);
            $assessment->files = [];

            $files = $fs->get_area_files($assessment->ctxid, 'mod_workshop', 'overallfeedback_content', $assessment->id, null, false);
            foreach($files as $file) {
                $assessment->files[$file->get_filepath()][] = $export->add_file($file);
            }

            $files = $fs->get_area_files($assessment->ctxid, 'mod_workshop', 'overallfeedback_attachment', $assessment->id, null, false);
            foreach($files as $file) {
                $assessment->files[$file->get_filepath()][] = $export->add_file($file);
            }

            unset($assessment->ctxid);
        }

        $join = self::get_activities_join($context, 'workshop', 'wagg.workshopid');
        $aggregations = $DB->get_records_sql(
            "SELECT wagg.*
               FROM {workshop_aggregations} wagg
             {$join}
              WHERE wagg.userid = :userid",
            ['userid' => $user->id]
        );

        $export->data['assessments'] = $assessments;
        $export->data['aggregations'] = $aggregations;

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

        $join = self::get_activities_join($context, 'workshop', 'wsub.workshopid');
        return $DB->count_records_sql(
            "SELECT COUNT(wa.id)
               FROM {workshop_submissions} wsub
             {$join}
               JOIN {workshop_assessments} wa
                 ON wsub.id = wa.submissionid
              WHERE wa.reviewerid = :reviewerid",
            ['reviewerid' => $user->id]
        );
    }
}
