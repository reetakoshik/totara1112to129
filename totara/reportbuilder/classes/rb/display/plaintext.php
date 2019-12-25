<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for plain text such as idnumbers.
 * Note that the value is not altered in any way, all this does
 * is escaping of special characters in each export format.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */
class plaintext extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if ($format === 'html') {
            $html = s($value);
            $html = str_replace("\n", '</br>', $html);
            return $html;
        }
        if ($format === "excel" or $format === "ods") {
            return array('string', $value, null);
        }
        // Other tabexport plugins will do their own escaping.
        return $value;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
