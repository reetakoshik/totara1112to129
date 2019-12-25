<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara_generator
 */

class totara_generator_util {
    /*
     * Get a random record from a table. This is used for creating
     * random structures within the test site generator.
     *
     * @param string Table name to get a random id from.
     * @param boolean Include the possibility of return a NULL value.
     * @param array integer $exclude_ids List of Ids to exclude.
     * @return integer Course category id.
     */
    public static function get_random_record_id($table, $include_null = false, $exclude_ids = array()) {
        global $DB;

        // Get a list of ids from the table as an array.
        $records = $DB->get_fieldset_select($table, 'id', false);
        $result = null;
        // Only continue if we have some records to work with.
        if (!empty($records)) {
            // Exclude any ids we know we don't want.
            if ($exclude_ids) {
                $records = array_diff($records, $exclude_ids);
            }
            $records = array_values($records);
            // Include a NULL as a possible result, if required.
            if ($include_null) {
                $records[] = null;
            }
            // If we've got some data, get a random id from the records.
            $count = count($records);
            if ($count === 0) {
                throw new coding_exception("No non-excluded records found in table '$table', cannot get random id");
            } else if ($count === 1) {
                $result = reset($records);
            } else {
                $index = rand(0, count($records) - 1);
                $result = $records[$index];
            }
        } else {
            if (!$include_null) {
                throw new coding_exception("No records found in table '$table', cannot get random id");
            }
        }

        return $result;
    }

    /*
     * Convert the test site generator size to its equivalent name.
     *
     * @param integer $size The size of the data to be created
     * @return string The name of the size.
     */
    public static function get_size_name($size) {
        return get_string('shortsize_' . $size, 'tool_generator');
    }

    /*
     * Give a boolean response based on a percentage on whether
     * an act should be taken. This is used to create random
     * test data by the test site generator.
     *
     * @param integer Percentage chance of act happening
     * @return boolean If the act should be executed
     */
    public static function get_random_act($percentage) {
        $random = rand(1, 100);
        $result = ($random <= $percentage);
        return $result;
    }

    /*
    * Get the number from the matching record so we can use
    * it in a sequence of records created for testing.
    *
    * @param string $table The database table
    * @param string $field The database table field name
    * @param string $match The string that should match the field data
    * @return int The last generated numeric value.
    */
    public static function get_next_record_number($table, $field, $match) {
        global $DB;
        // Get a list of records that match the default name.
        $params = array();
        $params[$field] = $DB->sql_like_escape($match) . '%';
        $like = $DB->sql_like($field, ":{$field}");
        $records = $DB->get_records_select($table, $like, $params, NULL, "id,{$field}" );
        // Store the highest number when we find it..
        $number = 0;
        if ($records) {
            $prefixnchars = strlen($match);
            // Loop through the records to find the highest number.
            foreach ($records as $record) {
                $suffix = trim(substr($record->$field, $prefixnchars));
                // Make sure we have an integer and it's greater than zero
                // and greater than any previous number we've found.
                $suffix_val = intval($suffix);
                if (($suffix_val > $number) && $suffix == $suffix_val) {
                    $number = $suffix;
                }
            }
        }
        // Increment and return the next number in the sequence.
        return ++$number;
    }

    /*
    * Create a short name from a long name by removing any
    * characters that aren't digits or uppercase letters.
    *
    * @param string long name
    * @return string short name
    */
    public static function create_short_name($name) {
        return preg_replace('/[^A-Z0-9]/', '', core_text::strtotitle(core_text::strtolower($name)));
    }
}
