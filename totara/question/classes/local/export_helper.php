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
abstract class export_helper {

    protected static $prefix = '';
    protected static $ansfield = '';
    private static $exporters = [];

    /**
     * Create and return the required export helper.
     *
     * @param $prefix - The name of the item containing the question i.e. appraisal or feedback360
     * @param $ansfield - The name of the field used to link back to the role/response i.e. appraisalroleassignmentid
     * @param $questiontype - The type of the question i.e. ratingnumeric or longtext
     * @return export_helper|null - An instance of the question specific export helper, or null if it doesn't exist
     */
    public static function create(string $prefix, string $ansfield, string $questiontype) {

        static::$prefix = $prefix;
        static::$ansfield = $ansfield;

        $classname = "totara_question\\local\\" . $questiontype . '_export';

        if (empty(static::$exporters[$classname])) {
            if (class_exists($classname)) {
                static::$exporters[$classname] = new $classname;
            } else {
                static::$exporters[$classname] = null;
            }
        }

        return static::$exporters[$classname];
    }

    /**
     * @param \stdClass $data
     * @param \stdClass $question
     * @return mixed
     */
    abstract public function export_data(\stdClass $data, \stdClass $question);

    /**
     * Question types that might contain files should override this function
     *
     * @param int $questionid
     * @param int $itemid
     * @return boolean|array - either false if there are no files, or an array of files.
     */
    public function export_files(int $questionid, int $itemid) {
        return false;
    }
}
