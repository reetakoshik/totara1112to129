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
 * @package totara_question
 */

namespace totara_question\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Class export_helper.
 *
 * @package totara_question
 */
abstract class review_export extends export_helper {

    protected static $scales = [];

    public function export_data(\stdClass $answerrow, \stdClass $question) {
        global $DB;

        $result = [];

        $prefix = static::$prefix;
        $answerfield = static::$ansfield;

        // Make sure we've initialised the prefix for scales.
        if (!isset(static::$scales[$prefix])) {
            static::$scales[$prefix] = [];
        }

        $questfieldid = $question->id;
        $answerfieldid = $answerrow->{$answerfield};

        $items = $this->get_items($questfieldid, $answerfieldid);

        // If no items have been selected then there can't be any answers.
        if (empty($items)) {
            return get_string('nothingselected', 'totara_question');
        }

        // If the question's scale isn't already loaded then load it now - these are the sub-questions.
        if (!empty($question->param1)) {
            if (empty(static::$scales[$question->param1])) {
                $params = [$prefix . 'scaleid' => $question->param1];
                static::$scales[$question->param1] = $DB->get_records($prefix . '_scale_value', $params, 'id');
            }
            $subquestions = static::$scales[$question->param1];
        }

        $uniquekey = $DB->sql_concat('rd.itemid', "'_'", 'COALESCE(rd.scope, 0)', "'_'", "rd.{$prefix}scalevalueid");

        $answerssql =
            "SELECT {$uniquekey} AS uniquekey, rd.content AS answer
               FROM {{$prefix}_review_data} rd
              WHERE rd.{$prefix}questfieldid = :qfid AND rd.{$answerfield} = :afid
              ORDER BY itemid, {$prefix}scalevalueid";
        $answers = $DB->get_records_sql($answerssql, ['qfid' => $questfieldid, 'afid' => $answerfieldid]);

        foreach ($items as $item) {
            $exportitem = new \stdClass();
            $exportitem->item = $item->name;
            $result[] = $exportitem;

            if (empty($subquestions)) {
                // This question has no subquestions, so there is just one answer to the main question per item.
                $uniquekey = $item->id . '_0';
                if (empty($answers[$uniquekey]) || is_null($answers[$uniquekey]->answer) || $answers[$uniquekey]->answer == "") {
                    $exportitem->answer = get_string('noanswer', 'totara_question');
                } else {
                    $exportitem->answer = $answers[$uniquekey]->answer;
                }

            } else {
                // This question has subquestions, so add each subquestion and answer to the result, for each item.
                $exportitem->subquestions = [];

                foreach ($subquestions as $subquestion) {
                    $exportsubquestion = new \stdClass();
                    $exportsubquestion->question = $subquestion->name;
                    $exportitem->subquestions[] = $exportsubquestion;

                    $uniquekey = $item->id . '_' . $subquestion->id;
                    if (empty($answers[$uniquekey]) || is_null($answers[$uniquekey]->answer) || $answers[$uniquekey]->answer == "") {
                        $exportsubquestion->answer = get_string('noanswer', 'totara_question');
                    } else {
                        $exportsubquestion->answer = $answers[$uniquekey]->answer;
                    }

                }
            }
        }

        return $result;
    }

    abstract public function get_items(int $questfieldid, int $answerfieldid) : array;
}
