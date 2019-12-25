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
class orgtarget extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
    	global $DB, $CFG;
        
    	$sql = "SELECT COUNT(prog_comp.userid) AS certiftotusers 
                FROM {job_assignment} ja 
                INNER JOIN {prog_completion} prog_comp ON prog_comp.userid = ja.userid 
                INNER JOIN {prog} p ON p.id = prog_comp.programid 
                INNER JOIN {org} o ON o.id = ja.organisationid 
                INNER JOIN {course_categories} cc ON cc.id = p.category 
                WHERE ja.organisationid = ? AND prog_comp.status = ? AND cc.idnumber = ?
                ";
        $certifcompuser = $DB->get_record_sql($sql, array($row->id,'3','compliance'));
        if($certifcompuser->certiftotusers > 0) {
            $valtaget = $certifcompuser->certiftotusers;
        } else {
             $sql = "SELECT COUNT(prog_comp.userid) AS certiftotusers 
                FROM {job_assignment} ja 
                INNER JOIN {prog_completion} prog_comp ON prog_comp.userid = ja.userid 
                INNER JOIN {prog} p ON p.id = prog_comp.programid 
                INNER JOIN {org} o ON o.id = ja.organisationid 
                INNER JOIN {course_categories} cc ON cc.id = p.category 
                WHERE o.parentid = ? AND prog_comp.status = ? AND cc.idnumber = ?
                ";
            $certifcompuser = $DB->get_record_sql($sql, array($row->id,'3','compliance'));
            $valtaget = $certifcompuser->certiftotusers;

        }
        
        if($row->org_membercount > 0) {
            $allcertifmem = $row->org_membercount;
        } else {
            $allcertifmem = $row->org_membercountcumulative;
        }

        $a = ($valtaget/$allcertifmem)*100.0;
        if($a >= 80) {
            return '<div style="width:100%;background-color:green;text-align:center;color:#FFF;">'.$a. " %</div>";
        } else {
            return '<div style="width:100%;background-color:red;text-align:center;color:#FFF;">'.$a. " %</div>";
        }
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
