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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package availability_audience
 */


namespace availability_audience;

defined('MOODLE_INTERNAL') || die();


use section_info;
use stdClass;


/**
 * Class of loading the audiences for section availabilty,
 * base on the json data (from section availability),
 * this util class will query the db to get the name for it
 *
 * Class section_util
 * @package availability_audience
 */
class section_util
{
    /**
     * @var section_info
     */
    private $section;

    /**
     * section_util constructor.
     * @param section_info $section
     */
    public function __construct(section_info $section) {
        $this->section = $section;
    }

    /**
     * Method of loading the cohort availabilities from
     * the course section. It returns an array of
     * standard php object with cohort id and name
     *
     * Since the section availabilities could be something
     * else different than the cohort, therefore
     * it will skip those availibilities and only query
     * for the cohort
     *
     * @return stdClass[]
     */
    public function load_cohort_availabilities() {
        global $DB;

        if (!isset($this->section->availability)) {
            return array();
        }

        $data = json_decode($this->section->availability, true);
        if (!$data || !isset($data['c'])) {
            // No point to procceed if the $data does not have the condition
            return array();
        }

        $cohortids = array();
        // Retrieving all the possible cohort id here, since there is a rule of the restriction
        // set, might need to walk thrue it recursively.
        array_walk_recursive($data['c'], function($value, $key) use (&$cohortids) {
            if ($key == "cohort") {
                $cohortids[] = $value;
            }
        });

        $records = null;
        if (!empty($cohortids)) {
            list($insql, $params) = $DB->get_in_or_equal($cohortids, SQL_PARAMS_QM);
            $sql = "SELECT id, name FROM {cohort} WHERE id {$insql}";
            $records = $DB->get_records_sql($sql, $params);
        }

        return $records ? array_values($records) :  array();
    }
}