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
class iscertified6 extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
    	global $DB, $CFG;
        
    	static $cerval = -1;
        $iscertify4 = $row->certcompletion_iscertified;
        
        $categoryid = $row->course_category_id ? $row->course_category_id : 0;
        $certifs    = $DB->get_records_sql("SELECT p.id, p.fullname, pc.status, pid.data FROM {prog} p
           INNER JOIN {prog_completion} pc ON pc.programid = p.id
           INNER JOIN {prog_info_data} pid ON pid.programid = p.id
           INNER JOIN {prog_info_field} pif ON pif.id = pid.fieldid
           WHERE p.category = ? AND p.certifid IS NOT NULL", array($categoryid));
        $certifname = array();
            
            if($iscertify4 == '0') {
                foreach($certifs as $data) {
                    $totcertuser = $DB->get_record_sql("SELECT COUNT(status) AS cstatus FROM {prog_completion} WHERE programid = '".$data->id."' AND status = '1'");
                    if( $data->data > (int)$totcertuser->cstatus) {
                        $certifname[$data->id] = 1;
                    }
                }
                $a = array_sum($certifname);
                return $a;
            } elseif($iscertify4 == '1') {
                foreach($certifs as $data) {
                    $totcertuser = $DB->get_record_sql("SELECT COUNT(status) AS cstatus FROM {prog_completion} WHERE programid = '".$data->id."' AND status = '3'");
                    if($totcertuser->cstatus >= $data->data) {
                        $certifname[$data->id] = 1;
                    } 
                    
                }
                $b = array_sum($certifname);
                return $b;
            }
        
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
