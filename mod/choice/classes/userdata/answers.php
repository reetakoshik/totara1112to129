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
 * @package mod_choice
 */

namespace mod_choice\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * Class answers
 *
 * This item applies to users' answers in choice activities.
 *
 * Events:
 *   - During purge, the \mod_choice\event\answer_deleted event will be triggered just prior to deletion
 *     of the record.
 *
 * Completion:
 *   - Lib functions for this module update completion when an answer is deleted. This is inconsistent with
 *     other activities. We will not update completion after purging answers.
 *
 * Caches:
 *   - None.
 *
 * Files:
 *   - None.
 *
 * @package mod_choice
 */
class answers extends item {

    /**
     * This item allows counting of data.
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
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
     * Returns the context levels this item can be executed in.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_MODULE, CONTEXT_COURSE, CONTEXT_COURSECAT, CONTEXT_SYSTEM];
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g. system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $answerssql = 'SELECT ca.*, cm.id AS cmid
                         FROM {choice_answers} ca '
                       . self::get_activities_join($context, 'choice', 'ca.choiceid')
                    . ' WHERE ca.userid = :userid';

        $answers = $DB->get_records_sql($answerssql, ['userid' => $user->id]);

        $choices = [];
        $cms = [];
        $courses = [];

        foreach ($answers as $answer) {
            if (!isset($choices[$answer->choiceid])) {
                $choices[$answer->choiceid] = $DB->get_record('choice', ['id' => $answer->choiceid]);
                $cms[$answer->cmid] = $DB->get_record('course_modules', ['id' => $answer->cmid]);
            }
            if (!isset($courses[$cms[$answer->cmid]->course])) {
                $courses[$cms[$answer->cmid]->course] = $DB->get_record('course', ['id' => $cms[$answer->cmid]->course]);
            }

            $choice = $choices[$answer->choiceid];
            $cm = $cms[$answer->cmid];
            $course = $courses[$cm->course];

            // The cmid property is not part of the choice_answers record.
            unset($answer->cmid);

            // The event is fired first, before deleting the records. This replicates the behaviour in choice_delete_responses().
            \mod_choice\event\answer_deleted::create_from_object($answer, $choice, $cm, $course)->trigger();
            $DB->delete_records('choice_answers', ['id' => $answer->id]);
        }

        // The lib.php function choice_delete_responses() also updates completion. This is unusual as completion is generally
        // dealt with separately. So here we need to choose whether to be consistent with the choice module or the rest of
        // the activity purge items. Given that completion is also covered by a separate item,
        // we'll choose to exclude modifying completion.

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting e.g. system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new export();

        $answerssql = 'SELECT ca.*
                         FROM {choice_answers} ca '
                       . self::get_activities_join($context, 'choice', 'ca.choiceid')
                    . ' WHERE ca.userid = :userid';

        $export->data = $DB->get_records_sql($answerssql, ['userid' => $user->id]);

        return $export;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting e.g. system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $answerssql = 'SELECT COUNT(ca.id)
                         FROM {choice_answers} ca '
                       . self::get_activities_join($context, 'choice', 'ca.choiceid')
                    . ' WHERE ca.userid = :userid';

        return $DB->count_records_sql($answerssql, ['userid' => $user->id]);
    }
}
