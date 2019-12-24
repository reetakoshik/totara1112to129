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
class certifstatus7 extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
    	global $DB;
    	static $cerval = -1;
        $parentid = $row->course_category_parentid ? $row->course_category_parentid : 0;
        $parentcatids = $DB->get_records_sql("SELECT id FROM {course_categories} WHERE parent = '".$parentid."'");
        
        $arr = array();
        foreach($parentcatids as $catid) {
            $sql = "SELECT cer_comp.id, cer_comp.status 
                FROM {certif_completion} cer_comp
                INNER JOIN {certif} cer ON cer.id = cer_comp.certifid
                INNER JOIN {prog} pr ON pr.certifid = cer.id
                WHERE pr.category = '".$catid->id."'";
            $statues = $DB->get_records_sql($sql);

            foreach($statues as $st) {
                $arr[$catid->id][] = $st->status;
            }

        }
   
        $a = array();
   foreach($arr as $key => $val) {

        $arrcount[$key] = array_count_values($arr[$key]);
        $certcatname = $DB->get_record_sql("SELECT name FROM {course_categories} WHERE id = '".$key."'");
        $a['Not Started'][$certcatname->name] += $arrcount[$key][1];

        $a['In Progress'][$certcatname->name] += $arrcount[$key][2];

        $a['Certified'][$certcatname->name] += $arrcount[$key][3];
   }
    
        ksort($arrcount);
        
        if($row->certcompletion_statussecond == 3) {
        	$cerval++;
            $c = '';
            foreach($a['Certified'] as $key => $val) {
                $c .= $key." (".$a['Certified'][$key]."), ";
            }
        	return $c;
        } elseif($row->certcompletion_statussecond == 1) {
        	$cerval++;
        	$c = '';
            foreach($a['Not Started'] as $key => $val) {
                $c .= $key." (".$a['Not Started'][$key]."), ";
            }
            return $c;
        } else {
        	$cerval++;	
            $c = '';
            foreach($a['In Progress'] as $key => $val) {
                $c .= $key." (".$a['In Progress'][$key]."), ";
            }
            return $c;
        } 
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
