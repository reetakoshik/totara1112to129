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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_feedback360
 */

namespace totara_feedback360\userdata;

use totara_userdata\userdata\export;
use totara_question\local\export_helper as qhelper;

defined('MOODLE_INTERNAL') || die();

class feedback360_helper {
    private static $questions = [];

    /**
     * Fill a resp assignment with all relevant information.
     *
     * @param export $export         - An instance of \totara_userdata\userdata\export, required to handle files correctly
     * @param object $userassignment - A database record from feedback360_user_assignment relating to the resp assignment
     * @param object $respassignment - A database record from feedback360_resp_assignment
     * @param bool  $anonymize       - Whether to anonymise uses of userid in the record
     * @return array                 - The respassignment with appropriate information added
     */
    public static function export_resp_assignment(export $export, $userassignment, $respassignment, $anonymize = false) {
        global $DB;

        // Add the email as an identifier, but don't include the emailtoken.
        if (!empty($respassignment->feedback360emailassignmentid)) {
            $respassignment->email = $DB->get_field('feedback360_email_assignment', 'email', ['id' => $respassignment->feedback360emailassignmentid]);
        }

        // If anonymous redact any fields that could be used to identify the user.
        if ($anonymize) {
            $respassignment->userid = get_string('anonymoususer', 'totara_feedback360');

            if (!empty($respassignment->email)) {
                $respassignment->email = get_string('anonymoususer', 'totara_feedback360');
            }
        }

        // Cache the questions for when we are doing multiple resp assignments per user assignment.
        $fb360id =  $userassignment->feedback360id;
        if (empty(static::$questions[$fb360id])) {
            static::$questions[$fb360id] = $DB->get_records('feedback360_quest_field', ['feedback360id' => $fb360id], 'sortorder');
        }

        $anstable = "feedback360_quest_data_{$fb360id}";
        $ansdata = $DB->get_record($anstable, ['feedback360respassignmentid' => $respassignment->id]);
        if (!empty($ansdata)) {
            $answers = [];
            foreach (static::$questions[$fb360id] as $question) {
                $qhelper = qhelper::create('feedback360', 'feedback360respassignmentid', $question->datatype);
                if (!empty($qhelper)) {
                    $answer = new \stdClass();
                    $answer->question = $question->name;
                    $answer->answer = $qhelper->export_data($ansdata, $question);

                    if ($files = $qhelper->export_files($question->id, $respassignment->id)) {
                        $answer->files = [];
                        foreach ($files as $file) {
                            $answer->files[] = $export->add_file($file);
                        }
                    }

                    $answers[] = $answer;
                }
            }
            $respassignment->content = $answers;
        } else {
            $respassignment->content = get_string('pending', 'totara_feedback360');
        }

        return $respassignment;
    }
}
