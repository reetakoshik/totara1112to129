<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_reportbuilder
 */

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/filters/date.php');

/**
 * Generic filter based on multiple dates.
 */
class rb_filter_grpconcat_date extends rb_filter_date {

    private $datefield, $datejoin;

    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        if (!isset($this->options['prefix']) || !isset($this->options['datefield'])) {
            throw new ReportBuilderException('concatenated date filters must have a \'datefield\' set that maps
                to a field in the joins table. And a \'datejoin\' which defines the table with more
                information on the associated object');
        }

        $this->prefix = $this->options['prefix'];
        $this->datefield = $this->options['datefield'];
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        $unique = rb_unique_param('grpdt_flt');
        $userfield = $this->get_field();

        $daysbefore = $data['daysbefore'];
        $daysafter = $data['daysafter'];
        $precond = ' (1 = 1) ';
        $postcond = ' (1 = 1) ';
        $params = array();

        $fromsql = '';
        switch ($this->prefix) {
            case 'job':
                $fromsql = " FROM {job_assignment} ja ";
                $field = "ja.{$this->datefield}";
                break;
            case 'org':
                $jobfield = 'organisationid';
            case 'pos':
                $jobfield = !isset($jobfield) ? 'positionid' : $jobfield;
                $fromsql = " FROM {job_assignment} ja
                       INNER JOIN {{$this->prefix}} pre
                               ON ja.{$jobfield} = pre.id
                       INNER JOIN {{$this->prefix}_type_info_field} field
                               ON field.typeid = pre.typeid
                              AND field.shortname = '{$this->datefield}'
                       INNER JOIN {{$this->prefix}_type_info_data} data
                               ON data.fieldid = field.id
                              AND data.{$jobfield} = pre.id ";
                $field = 'data.data';
                break;
            default:
                throw new ReportBuilderException("Invalid prefix ($this->prefix) passed through group concat dates filter. Accepted prefix values are 'job/pos/org'");
                break;
        }

        // The days before/after take precedence so only check these ones if they arent enabled.
        if (empty($daysbefore) && empty($daysafter)) {
            if (!empty($data['before_applied']) and !empty($data['before'])) {
                $precond = " ( {$field} <= :{$unique}_pre ) ";
                $params["{$unique}_pre"] = $data['before'];
            }

            if (!empty($data['after_applied']) and !empty($data['after'])) {
                $postcond = " ( {$field} >= :{$unique}_post ) ";
                $params["{$unique}_post"] = $data['after'];
            }
        } else {
            $datetodayobj = new DateTime('now', core_date::get_user_timezone_object());
            $datetodayobj->setTime(0, 0, 0);
            $datetoday = $datetodayobj->getTimestamp();

            if (!empty($daysbefore)) {
                $interval = new DateInterval('P' . $daysbefore . 'D');
                $datebefore = $datetodayobj->sub($interval)->getTimestamp();

                $precond = " ( {$field} <= :{$unique}b1 AND {$field} >= :{$unique}b2 ) ";
                $params["{$unique}b1"] = $datetoday;
                $params["{$unique}b2"] = $datebefore;
            }

            if (!empty($daysafter)) {
                $interval = new DateInterval('P' . $daysafter . 'D');
                $dateafter = $datetodayobj->add($interval)->getTimestamp();

                $postcond = " ( {$field} >= :{$unique}a1 AND {$field} <= :{$unique}a2 ) ";
                $params["{$unique}a1"] = $datetoday;
                $params["{$unique}a2"] = $dateafter;
            }
        }

        // If there is still nothing to filter on, just return true.
        if (empty($params)) {
            return array(' 1=1 ', array());
        } else {
            $uniquenull = rb_unique_param('fdnotnull');
            $params[$uniquenull] = 0;

            $sql = " EXISTS ( SELECT 1
                             {$fromsql}
                               WHERE ja.userid = {$userfield}
                                 AND {$field} != :{$uniquenull}
                                 AND ( {$precond} AND {$postcond} )
                            )";
            return array($sql, $params);
        }
    }
}
