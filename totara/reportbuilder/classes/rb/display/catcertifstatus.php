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
class catcertifstatus extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
    	global $DB, $CFG;
        //echo '<pre>';print_r($row);echo '</pre>';die('test123');
    	static $cerval = -1;
        $categoryid = $row->course_category_id ? $row->course_category_id : 0;
        if($CFG->dbtype == 'pgsql') {
            $sql = "SELECT SUM(CAST (pid.data AS INTEGER)) AS pidcount FROM {prog_info_field} AS pif INNER JOIN {prog_info_data} AS pid ON pid.fieldid = pif.id INNER JOIN {prog} AS p ON p.id = pid.programid WHERE pif.shortname = 'goal' AND p.category='".$row->course_category_id."'";
        } else {
            $sql= "SELECT pid.id, SUM(pid.data) AS pidcount FROM {prog_info_field} AS pif INNER JOIN {prog_info_data} AS pid ON pid.fieldid = pif.id INNER JOIN {prog} AS p ON p.id = pid.programid WHERE pif.shortname = 'goal' AND p.category='".$row->course_category_id."'";
        }
        $goal=$DB->get_record_sql($sql);
        //print_r($goal); die();
        if($categoryid > 0) {
            $statues = $DB->get_records_sql("SELECT cer_comp.id, cer_comp.status 
                    FROM {certif_completion} cer_comp
                    INNER JOIN {certif} cer ON cer.id = cer_comp.certifid
                    INNER JOIN {prog} pr ON pr.certifid = cer.id
                    WHERE pr.category = '".$categoryid."'");
            $arr = array();
            foreach($statues as $st) {
                $arr[] = $st->status;
            }
            
            $arrcount = array_count_values($arr);
            
            ksort($arrcount);
            
            if($row->certcompletion_statussecond == 3) {
            	$cerval++;
            } elseif($row->certcompletion_statussecond == 1) {
            	$cerval++;
            } else {
            	$cerval++;
            }
               if($arrcount[3] >= $goal->pidcount) {
                    return 'True';
                }else{
                    return 'False';
                }
                //return $arrcount[3];
        } else {
            return 'False';
        }
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
