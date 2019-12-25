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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

namespace totara_appraisal\userdata;

use totara_question\local\export_helper;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This is the export-only user data item for the user's appraisal content. Override to either include or exclude hidden answers.
 */
abstract class appraisal_export extends item {
    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return false;
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
     * @param bool true to include answers from other roles which the learner would not normally see, otherwise false
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export_conditional(target_user $user, bool $includehidden) {
        global $DB;

        $export = new export();

        $appraisalsql =
            "SELECT app.id, app.name AS appraisalname, aua.timecompleted, aua.status, ja.id AS jobassignmentidnumber
               FROM {appraisal_user_assignment} aua
               JOIN {appraisal} app ON app.id = aua.appraisalid
          LEFT JOIN {job_assignment} ja ON aua.jobassignmentid = ja.id
              WHERE aua.userid = :userid
              ORDER BY app.id";
        $appraisals = $DB->get_records_sql($appraisalsql, ['userid' => $user->id]);

        if ($includehidden) {
            $excludehiddensql = "";
            $excludehiddenparams = [];
        } else {
            $excludehiddensql =
                "AND (role.appraisalrole = :rolelearner1 OR
                      EXISTS (SELECT 1
                                FROM {appraisal_quest_field_role} rights
                               WHERE rights.appraisalrole = :rolelearner2
                                 AND rights.appraisalquestfieldid = quest.id
                                 AND rights.rights & :accessother1 = :accessother2))";
            $excludehiddenparams = [
                'rolelearner1' => \appraisal::ROLE_LEARNER,
                'rolelearner2' => \appraisal::ROLE_LEARNER,
                'accessother1' => \appraisal::ACCESS_CANVIEWOTHER,
                'accessother2' => \appraisal::ACCESS_CANVIEWOTHER,
            ];
        }

        $questionssql =
            "SELECT role.id AS uniqueid, quest.id, quest.name AS questname, quest.datatype,
                    quest.param1, quest.param2, quest.param3, quest.param4, quest.param5,
                    stage.name AS stagename, stage.id AS stageid, page.name AS pagename, page.id AS pageid,
                    role.appraisalrole, role.rights
               FROM {appraisal_quest_field_role} role
               JOIN {appraisal_quest_field} quest ON quest.id = role.appraisalquestfieldid
               JOIN {appraisal_stage_page} page ON page.id = quest.appraisalstagepageid
               JOIN {appraisal_stage} stage ON stage.id = page.appraisalstageid
              WHERE stage.appraisalid = :appraisalid
                    {$excludehiddensql}
              ORDER BY stage.timedue, page.sortorder, quest.sortorder, role.appraisalrole";
        $questionsparams = $excludehiddenparams;

        foreach ($appraisals as $appraisal) {
            $exportappraisal = new \stdClass();
            $exportappraisal->appraisalname = $appraisal->appraisalname;
            $exportappraisal->status = $appraisal->status;
            $exportappraisal->timecompleted = $appraisal->timecompleted;
            $exportappraisal->jobassignmentidnumber = $appraisal->jobassignmentidnumber;

            $exportappraisal->stages = [];

            $questionsparams['appraisalid'] = $appraisal->id;
            $questions = $DB->get_records_sql($questionssql, $questionsparams);

            $question = reset($questions);
            if (empty($question)) {
                $export->data[] = $exportappraisal;
                continue;
            }

            $answerssql =
                "SELECT ara.appraisalrole, aqd.*
                   FROM {appraisal_quest_data_{$appraisal->id}} aqd
                   JOIN {appraisal_role_assignment} ara ON ara.id = aqd.appraisalroleassignmentid
                   JOIN {appraisal_user_assignment} aua ON aua.id = ara.appraisaluserassignmentid
                  WHERE aua.userid = :userid AND aua.appraisalid = :appraisalid";

            $answers = $DB->get_records_sql($answerssql, ['userid' => $user->id, 'appraisalid' => $appraisal->id]);

            // Set up the initial stage, page and question containers.
            $currentstage = new \stdClass();
            $currentstage->stagename = $question->stagename;
            $currentstage->pages = [];

            $currentpage = new \stdClass();
            $currentpage->pagename = $question->pagename;
            $currentpage->questions = [];

            $currentquest = new \stdClass();
            $currentquest->questionname = $question->questname;
            $currentquest->roleanswers = [];

            do {
                // If the question exporter type hasn't been defined then skip the question.
                $exportquestion = export_helper::create('appraisal', 'appraisalroleassignmentid', $question->datatype);

                if (!empty($exportquestion)) {
                    if (!empty($answers[$question->appraisalrole])) {
                        // Add the answers from the current question's role into the question, nicely formatted.

                        $answerrow = $answers[$question->appraisalrole];

                        $answer = new \stdClass();

                        $answer->data = $exportquestion->export_data($answerrow, $question);

                        // Add any relevant files to the answer.
                        if ($files = $exportquestion->export_files($question->id, $answerrow->appraisalroleassignmentid)) {
                            $answer->files = [];
                            foreach ($files as $file) {
                                $answer->files[] = $export->add_file($file);
                            }
                        }

                        $currentquest->roleanswers['role ' . $question->appraisalrole] = $answer;

                    } else {
                        $currentquest->roleanswers['role ' . $question->appraisalrole] = get_string('none', 'totara_appraisal');
                    }
                }

                // Work out if we're changing question, page or stage, or if we're at the end.
                $nextquestion = next($questions);

                if (empty($nextquestion)) {
                    // End of the appraisal, so add the current stuff into the export and finish.
                    $currentpage->questions[] = $currentquest;
                    $currentstage->pages[] = $currentpage;
                    $exportappraisal->stages[] = $currentstage;

                } else if ($nextquestion->stageid != $question->stageid) {
                    // Stage has finished. Add the current stuff into the export data and make new stuff.
                    $currentpage->questions[] = $currentquest;
                    $currentstage->pages[] = $currentpage;
                    $exportappraisal->stages[] = $currentstage;

                    $currentstage = new \stdClass();
                    $currentstage->stagename = $nextquestion->stagename;
                    $currentstage->pages = [];

                    $currentpage = new \stdClass();
                    $currentpage->pagename = $nextquestion->pagename;
                    $currentpage->questions = [];

                    $currentquest = new \stdClass();
                    $currentquest->questionname = $nextquestion->questname;
                    $currentquest->roleanswers = [];

                } else if ($nextquestion->pageid != $question->pageid) {
                    // Page has finished. Add the current page into the stage and make new page.
                    $currentpage->questions[] = $currentquest;
                    $currentstage->pages[] = $currentpage;

                    $currentpage = new \stdClass();
                    $currentpage->pagename = $nextquestion->pagename;
                    $currentpage->questions = [];

                    $currentquest = new \stdClass();
                    $currentquest->questionname = $nextquestion->questname;
                    $currentquest->roleanswers = [];

                } else if ($nextquestion->id != $question->id) {
                    // Question has finished. Add the current question into the page and make new question.
                    $currentpage->questions[] = $currentquest;

                    $currentquest = new \stdClass();
                    $currentquest->questionname = $nextquestion->questname;
                    $currentquest->roleanswers = [];
                } // Else it is another answer, to the same question, from a different role.

                $question = $nextquestion;
            } while (!empty($question));

            $export->data[] = $exportappraisal;
        }

        return $export;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
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
     * @return int  integer is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('appraisal_user_assignment', array('userid' => $user->id));
    }
}
