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
class subcatpercentiscertif extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
        global $DB, $CFG;
        static $cerval = -1;
        static $c = 0;
        static $d = 0;
        
        $parentid = $row->course_category_parentid ? $row->course_category_parentid : 0;
        $parentcatids = $DB->get_records_sql("SELECT id FROM {course_categories} WHERE parent = '".$parentid."'");
        
        $arr = array();
        $pcatcount = 0;
        foreach($parentcatids as $catid) {
            if($CFG->dbtype == 'pgsql') {
                $sql = "SELECT SUM(CAST (pid.data AS INTEGER)) AS pidcount FROM {prog_info_field} AS pif INNER JOIN {prog_info_data} AS pid ON pid.fieldid = pif.id INNER JOIN {prog} AS p ON p.id = pid.programid WHERE pif.shortname = 'goal' AND p.category='".$catid->id."'";
            } else {
                $sql= "SELECT pid.id, SUM(pid.data) AS pidcount FROM {prog_info_field} AS pif INNER JOIN {prog_info_data} AS pid ON pid.fieldid = pif.id INNER JOIN {prog} AS p ON p.id = pid.programid WHERE pif.shortname = 'goal' AND p.category='".$catid->id."'";
            }
            $goal=$DB->get_record_sql($sql);
            $pcatcount += $goal->pidcount;
            $sql = "SELECT cer_comp.id, cer_comp.status 
                FROM {certif_completion} cer_comp
                INNER JOIN {certif} cer ON cer.id = cer_comp.certifid
                INNER JOIN {prog} pr ON pr.certifid = cer.id
                WHERE pr.category = '".$catid->id."'";
            $statues = $DB->get_records_sql($sql);

            foreach($statues as $st) {
                $arr[$catid->id][] = $st->status;
            }
            $subcatids[$catid->id] = $catid->id;
        }

        $a = array();
        foreach($arr as $key => $val) {
            $arrcount[$key] = array_count_values($arr[$key]);
        }

        ksort($arrcount);
        $percentprog = count($arrcount);
        foreach($arrcount as $key => $val) {
            //echo $key.'<br>';
            $catname = $DB->get_record_sql("SELECT id, name FROM {course_categories} WHERE id = '".$key."'");
            $b = ($arrcount[$key][3] + $arrcount[$key][2] + $arrcount[$key][1]);
            if(($subcatids[$key] == $key) && empty($arrcount[$key][1]) && empty($arrcount[$key][2])) {
                ++$c;
                $e[$catname->id] = $catname->name;
            } else {
            if($d <= $percentprog)
                ++$d;
                $f[$catname->id] = $catname->name;
            }
        }

        if($row->certcompletion_iscertified == 1) {
            $categorynames1 = implode(', ', $e);
            return $categorynames1;
        } elseif($row->certcompletion_iscertified == 0) {
            $categorynames2 = implode(', ', $f);
            return $categorynames2;
        }

    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
