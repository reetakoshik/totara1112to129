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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_quiz
 */

namespace mod_quiz\userdata;

use context_module;
use question_definition;
use question_engine;
use question_manually_gradable;
use quiz_attempt;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This item is responsible for purging, exporting and counting all quiz attempts.
 */
class attempts extends item {

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
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $deletedattempts = [];

        $transaction = $DB->start_delegated_transaction();

        // Load all quizs including the module ids
        // for the given contexts where the user has attempts in.
        $join = self::get_activities_join($context, 'quiz', 'q.id');
        $sql = "
            SELECT q.*, cm.id AS cmid
              FROM {quiz} q
              $join
             WHERE q.id IN (
                SELECT qa.quiz
                  FROM {quiz_attempts} qa
                 WHERE qa.userid = :userid
             )
        ";
        $params = ['userid' => $user->id];
        $quizs = $DB->get_records_sql($sql, $params);

        foreach ($quizs as $quiz) {
            // There's the api method quiz_delete_attempt(). The api method is designed for deleting
            // only single attempts and to avoid too much regrading we reproduce the functionality here but
            // regrading only once per quiz and firing the events after all purging has been done.

            $params = ['quiz' => $quiz->id, 'userid' => $user->id];
            $attempts = $DB->get_records('quiz_attempts', $params);
            foreach ($attempts as $attempt) {
                question_engine::delete_questions_usage_by_activity($attempt->uniqueid);
                $deletedattempts[] = [
                    'attempt' => $attempt,
                    'quiz' => $quiz
                ];
            }

            $attemptids = array_keys($attempts);
            list($idinsql, $params) = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED);
            $params['userid'] = $user->id;

            // Delete all attempts and grades for this quiz.
            $DB->delete_records_select('quiz_attempts', "id $idinsql AND userid = :userid", $params);
            $DB->delete_records('quiz_grades', ['quiz' => $quiz->id, 'userid' => $user->id]);

            // Update grades in central gradebook.
            quiz_update_grades($quiz, $user->id);
        }

        $transaction->allow_commit();

        // Fire events for all attempts deleted.
        self::fire_attempt_deleted_events($deletedattempts);

        return self::RESULT_STATUS_SUCCESS;
    }


    /**
     * Fire the attempt_deleted event for all attempts previously deleted
     *
     * @param \stdClass[] $deletedattempts
     * @return void
     */
    private static function fire_attempt_deleted_events(array $deletedattempts) {
        foreach ($deletedattempts as $deletedattempt) {
            $attempt = $deletedattempt['attempt'];
            $quiz = $deletedattempt['quiz'];
            // Don't fire events for previews.
            if ($attempt->preview) {
                continue;
            }
            $params = [
                'objectid' => $attempt->id,
                'relateduserid' => $attempt->userid,
                'context' => context_module::instance($quiz->cmid),
                'other' => [
                    'quizid' => $quiz->id
                ]
            ];
            $event = \mod_quiz\event\attempt_deleted::create($params);
            $event->add_record_snapshot('quiz_attempts', $attempt);
            $event->trigger();
        }
    }

    /**
     * Can user data of this item data be exported from system?
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
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');

        $export = new export();

        $join = self::get_activities_join($context, 'quiz', 'qa.quiz', 'q');
        // Load all quizs for the given contexts where the user has attempts in.
        $sql = "
            SELECT qa.*, cm.id as cmid
              FROM {quiz_attempts} qa
              $join
             WHERE qa.userid = :userid
        ";
        $params = ['userid' => $user->id];
        $attempts = $DB->get_records_sql($sql, $params);

        $fs = get_file_storage();

        $quizs = [];
        $exportattempts = [];
        foreach ($attempts as $attempt) {
            $quizattempt = quiz_attempt::create($attempt->id);
            if (!isset($quizs[$attempt->quiz])) {
                $quizs[$attempt->quiz] = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
            }
            $quiz = $quizs[$attempt->quiz];
            $modulecontext = context_module::instance($attempt->cmid);

            if ($attempt->state != quiz_attempt::FINISHED) {
                $feedback = '-';
            } else {
                $feedback = quiz_report_feedback_for_grade(
                    quiz_rescale_grade($attempt->sumgrades, $quiz, false),
                    $quizs[$attempt->quiz]->id,
                    $modulecontext
                );
            }

            $questions = [];
            $slots = $quizattempt->get_slots();
            foreach ($slots as $slot) {
                $qa = $quizattempt->get_question_attempt($slot);
                $rightanswer = '';
                if ($attempt->state == quiz_attempt::FINISHED) {
                    $rightanswer = $qa->get_right_answer_summary();
                }
                $question = [
                    'question' => $qa->get_question_summary(),
                    'mark' => $qa->get_current_manual_mark(),
                    'response' => $qa->get_response_summary(),
                    'state' => $qa->get_state_string(true),
                    'summary' => $qa->summarise_action($qa->get_last_step()),
                    'rightanswer' => $rightanswer,
                    'files' => []
                ];

                // Get all files associated with this question attempt.
                foreach ($qa->get_step_iterator() as $step) {
                    foreach (question_engine::get_all_response_file_areas() as $filearea) {
                        $files = $fs->get_area_files(
                            $modulecontext->id,
                            'question',
                            $filearea,
                            $step->get_id(),
                            'id, filename',
                            false
                        );
                        foreach ($files as $file) {
                            $question['files'][] = $export->add_file($file);
                        }
                    }
                }

                $questions[] = $question;
            }

            $exportattempts[] = [
                'id' => $quizattempt->get_attemptid(),
                'state' => quiz_attempt::state_name($quizattempt->get_state()),
                'grade' => quiz_rescale_grade($attempt->sumgrades, $quiz, true),
                'summark' => $quizattempt->get_sum_marks(),
                'timestart' => $attempt->timestart,
                'timefinish' => $attempt->timefinish,
                'feedback' => $feedback,
                'questions' => $questions
            ];
        }

        $export->data = $exportattempts;

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
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'quiz', 'qa.quiz', 'q');
        // Load all quizs for the given contexts where the user has attempts in.
        $sql = "
            SELECT COUNT(qa.id)
              FROM {quiz_attempts} qa
              $join
             WHERE qa.userid = :userid
        ";
        $params = ['userid' => $user->id];
        return $DB->count_records_sql($sql, $params);
    }

}