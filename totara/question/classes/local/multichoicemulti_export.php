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
 * @package totara_question
 */

namespace totara_question\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Class export_helper.
 *
 * @package totara_question
 */
class multichoicemulti_export extends export_helper {

    private static $scales = [];

    /**
     * Get the appropriately formatted answer to a custom rating question
     *
     * @param object $answerrow  - The quest_data record containing answers to the question
     * @param object $question   - The quest_field record containing extra question information
     * @return mixed array|string - The none string, or an array containing all of the selected answers
     */
    public function export_data(\stdClass $answerrow, \stdClass $question) {
        global $DB;

        // Make sure we've initialised the prefix for scales.
        $prefix = static::$prefix;
        if (!isset(static::$scales[$prefix])) {
            static::$scales[$prefix] = [];
        }

        // Make sure we've filled scales with the appropriate data.
        $scaleid = $question->param1;
        if (!isset(static::$scales[$prefix][$scaleid])) {
            $tablename = "{$prefix}_scale_value";
            $fieldid = "{$prefix}scaleid";
            $scalevalues = $DB->get_records($tablename, [$fieldid => $scaleid]);

            static::$scales[$prefix][$scaleid] = $scalevalues;
        }

        // Multichoicemulti question save answers in a slightly different way, so we'll need a DB query.
        $ansfield = static::$ansfield;
        $ansparams = [
            "{$ansfield}" => $answerrow->$ansfield,
            "{$prefix}questfieldid" => $question->id
        ];
        $answers = $DB->get_records("{$prefix}_scale_data", $ansparams);

        $data = [];
        foreach ($answers as $answer) {
            $field = "{$prefix}scalevalueid";
            $data[] = static::$scales[$prefix][$scaleid][$answer->$field]->name;
        }

        if (empty($data)) {
            return get_string('noanswer', 'totara_question');
        }

        return $data;
    }
}
