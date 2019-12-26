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

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Submissions made by the target user.
 */
class submissions extends \totara_userdata\userdata\item {

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
     * This item is compatible the module context level and all parent context levels to that.
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

        // Get all submissions where user has authorid in workshop_submissions.

        $join = self::get_activities_join($context, 'workshop', 'wsub.workshopid');
        $submissions = $DB->get_records_sql(
            "SELECT wsub.id, wsub.workshopid, wsub.authorid, wsub.title, ctx.id AS ctxid
               FROM {workshop_submissions} wsub
             {$join}
              WHERE wsub.authorid = :authorid",
            ['authorid' => $user->id]
        );

        $purged_workshops = [];
        foreach ($submissions as $submission) {

            $fs = get_file_storage();
            // Copied from \workshop::delete_submission().
            $assessments = $DB->get_fieldset_select('workshop_assessments', 'id', 'submissionid = :submissionid', ['submissionid' => $submission->id]);

            // Copied from delete assessments.
            $DB->delete_records_list('workshop_grades', 'assessmentid', $assessments);
            foreach ($assessments as $itemid) {
                $fs->delete_area_files($submission->ctxid, 'mod_workshop', 'overallfeedback_content', $itemid);
                $fs->delete_area_files($submission->ctxid, 'mod_workshop', 'overallfeedback_attachment', $itemid);
            }
            $DB->delete_records_list('workshop_assessments', 'id', $assessments);

            $fs->delete_area_files($submission->ctxid, 'mod_workshop', 'submission_content', $submission->id);
            $fs->delete_area_files($submission->ctxid, 'mod_workshop', 'submission_attachment', $submission->id);

            $DB->delete_records('workshop_submissions', ['id' => $submission->id]);

            // We're saving the workshops we've purged aggregations for so we don't repeat ourselves.
            if (!isset($purged_workshops[$submission->workshopid])) {
                $purged_workshops[$submission->workshopid] = $DB->get_record('workshop', ['id' => $submission->workshopid]);
            }

            $workshoprecord = $purged_workshops[$submission->workshopid];

            // Event information.
            $params = [
                'contextid' => $submission->ctxid,
                'courseid' => $workshoprecord->course,
                'relateduserid' => $submission->authorid,
                'other' => [
                    'submissiontitle' => $submission->title
                ]
            ];
            $params['objectid'] = $submission->id;
            $event = \mod_workshop\event\submission_deleted::create($params);
            $event->add_record_snapshot('workshop', $workshoprecord);
            $event->trigger();

            // The workshop API does not run workshop_update_grades() when deleting submissions or assessments.
            // That is only run during the phase switch.
            // So we're not updating grades after purging.
        }

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
        $submissions = $DB->get_records_sql(
            "SELECT wsub.*, ctx.id AS ctxid
               FROM {workshop_submissions} wsub
             {$join}
              WHERE wsub.authorid = :authorid",
            ['authorid' => $user->id]
        );

        foreach($submissions as $submission) {
            $submission->assessments = $DB->get_records(
                'workshop_assessments',
                ['submissionid' => $submission->id],
                '',
                // Excluding feedbackreviewer, that should relate only to the reviewer and not the user who made the submission.
                'id, submissionid, grade, gradinggrade, gradinggradeover, gradinggradeoverby, feedbackauthor'
                );

            foreach($submission->assessments as $assessment) {
                $assessment->grades = $DB->get_records('workshop_grades', ['assessmentid' => $assessment->id]);
                $assessment->files = [];

                $files = $fs->get_area_files($submission->ctxid, 'mod_workshop', 'overallfeedback_content', $assessment->id, null, false);
                foreach($files as $file) {
                    $assessment->files[$file->get_filepath()][] = $export->add_file($file);
                }

                $files = $fs->get_area_files($submission->ctxid, 'mod_workshop', 'overallfeedback_attachment', $assessment->id, null, false);
                foreach($files as $file) {
                    $assessment->files[$file->get_filepath()][] = $export->add_file($file);
                }
            }

            $submission->files = [];

            $files = $fs->get_area_files($submission->ctxid, 'mod_workshop', 'submission_content', $submission->id, null, false);
            foreach($files as $file) {
                $submission->files[$file->get_filepath()][] = $export->add_file($file);
            }

            $files = $fs->get_area_files($submission->ctxid, 'mod_workshop', 'submission_attachment', $submission->id, null, false);
            foreach($files as $file) {
                $submission->files[$file->get_filepath()][] = $export->add_file($file);
            }

            $export->data[] = $submission;
        }

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
            "SELECT COUNT(wsub.id)
               FROM {workshop_submissions} wsub
             {$join}
              WHERE wsub.authorid = :authorid",
            ['authorid' => $user->id]
        );
    }
}