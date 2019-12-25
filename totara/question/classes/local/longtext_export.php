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
class longtext_export extends export_helper {

    public function export_data(\stdClass $answerrow, \stdClass $question) {
        $answerfield = 'data_' . $question->id;
        $data = $answerrow->$answerfield;

        if (is_null($data) || $data == "") {
            return get_string('noanswer', 'totara_question');
        }

        return $data;
    }

    public function export_files(int $questionid, int $itemid) {
        $prefix = static::$prefix;

        $fs = get_file_storage();
        $systemcontext = \context_system::instance();
        $files = $fs->get_area_files($systemcontext->id, "totara_{$prefix}", "quest_{$questionid}", $itemid, "timemodified", false);

        return $files;
    }
}
