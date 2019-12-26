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
class goal_achieve extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
    	global $DB;
    	$customfieldid = $DB->get_record_sql("SELECT id FROM {prog_info_field} WHERE shortname = 'goal'");
//echo '<pre>';print_r($row);echo '</pre>';

    	$customfieldkey = "prog_custom_field_".$customfieldid->id;
    	if($row->certcompletion_iscertified >= $row->$customfieldkey && $row->certcompletion_status !=2 && $row->certcompletion_status!=4) {
    		//echo '<pre>';print_r($row);echo '</pre>';
    		//echo $row->certcompletion_iscertified .'>='. $row->$customfieldkey;
    		return 'True';
    	} else if ($row->certcompletion_status ==2) {
    		return 'False (Inprogress)';
    	}else if ($row->certcompletion_status ==4) {
            return 'False (Expired)';
        }else{
            return 'False';
        }
    	//certcompletion_iscertified1
    	//echo '<pre>';print_r($row);echo '</pre>'; die("test1234");
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
