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
class datepicker_export extends export_helper {

    public function export_data(\stdClass $answerrow, \stdClass $question) {
        $answerfield = 'data_' . $question->id;
        $data = $answerrow->$answerfield;

        if (is_null($data) || $data == "" || $data == 0) {
            return get_string('noanswer', 'totara_question');
        }

        $param1 = json_decode($question->param1);

        if ($param1->withtime) {
            $format = get_string('strfdateattime', 'langconfig');

            if (!empty($param1->withtimezone)) {
                $answerfield .= 'tz';
                $timezone = $answerrow->$answerfield;
                return userdate($data, $format, $timezone) . ' ' . $timezone;
            }
        } else {
            $format = get_string('strfdateshortmonth', 'langconfig');
        }
        return userdate($data, $format);
    }
}
