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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

use \csv_import_reader as csv;

final class import_helper {

    /**
     * If user's choice is 'automatic' delimiter lets try to find out
     * @param $formdata users to add to seminar event via file
     *      @var s seminar event id
     *      @var listid list id
     *      @var content file content
     *      @var delimiter
     *      data via file
     * @return string
     */
    public static function csv_detect_delimiter($formdata): ?string {
        // User's choice is auto detect delimiter, lets try it, if failed, return false.
        $detectdelimiter = function($delimiters, $content) {
            foreach($delimiters as $name => $delimiter) {
                $arraydata = str_getcsv($content, $delimiter);
                if (count($arraydata) > 1) {
                    return $name;
                }
            }
            // We can find it, return error
            return false;
        };
        $thedelimiter = $formdata->delimiter == 'auto' ?
            $detectdelimiter(csv::get_delimiter_list(), $formdata->content) :
            $formdata->delimiter;
        return $thedelimiter;
    }

    /**
     * Return a list of csv delimiters use in seminar event using in UI.
     *
     * @return array
     */
    public static function csv_get_delimiter_list(): array {

        $delimiteroptions['auto'] = get_string('delimiter:auto', 'mod_facetoface');
        $delimiterlist = csv::get_delimiter_list();
        // Build delimiter list for UI.
        foreach ($delimiterlist as $name => $delimiter) {
            $delimiteroptions[$name] = get_string('delimiter:'.$name, 'mod_facetoface');
        }

        return $delimiteroptions;
    }
}