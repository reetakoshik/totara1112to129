<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Class describing column display formatting.
 */
class orgidname extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
    	global $DB, $CFG;
        //echo '<pre>';print_r($row);echo '</pre>';
    	$orgname = $DB->get_record_sql("SELECT id, fullname, depthlevel FROM {org} WHERE id = '".$row->id."'");
        $depth = $orgname->depthlevel;
        $mleft = 25 * $depth;
        $mleft .= 'px';
        return "<div class='ft ft-square-small' style='margin-left:$mleft;color: #287b7c;font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,\"Helvetica Neue\",Arial,sans-serif;font-size: 14px;'>".$orgname->fullname. "</div>";
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
