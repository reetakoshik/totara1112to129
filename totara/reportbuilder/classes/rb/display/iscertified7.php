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
class iscertified7 extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) { 
    	global $DB, $CFG;
               //echo '<pre>' ;print_r($row);echo '</pre>';//die('test123');
        $compid=$DB->get_record_sql("SELECT cc.id FROM {course_categories} cc WHERE cc.idnumber='compliance'");
        /*$sql = "SELECT p.id, p.fullname
      FROM {prog} p
      INNER JOIN {course_categories} cc ON cc.id = p.category
      WHERE p.certifid IS NOT NULL AND cc.parent = '".$compid->id."' AND p.certifid = '".$row->prog_id."'";
      $certifname = $DB->get_record_sql($sql);*/
      if($row->prog_id == 37) {
      echo $sql = "SELECT p.id, p.fullname
      FROM {prog} p
      INNER JOIN {course_categories} cc ON cc.id = p.category
      WHERE p.certifid IS NOT NULL AND cc.path LIKE '/".$compid->id."/%' AND p.certifid = '".$row->prog_id."'";
      $certifname = $DB->get_record_sql($sql);
      return $certifname->fullname;
  		}
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {

        return true;
    }
}
