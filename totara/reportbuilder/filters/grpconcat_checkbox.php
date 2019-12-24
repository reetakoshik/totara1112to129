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
require_once($CFG->dirroot . '/totara/reportbuilder/filters/select.php');

/**
 * NOTE: This filter assumes that simple mode is enabled, see the
 *       add_job_custom_field_filters() function for an example.
 */
class rb_filter_grpconcat_checkbox extends rb_filter_select {
    /**
     * Override the conditions to be used in the SQL where clause
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        global $DB;

        $value = explode(',', $data['value']);
        $field = $this->get_field();

        if (count($value) == 1 && current($value) == '') {
            // return 1=1 instead of TRUE for MSSQL support
            return array(' 1=1 ', array());
        } else {
            $operator = current($value);
            $uniqueparam = rb_unique_param('cbfilter');
            $likesql = '';
            $likeparam = array();

            switch ($operator) {
                case 0:
                    // [0] => All No - !like(Yes) && !like(linespacer).
                    $likesql = $DB->sql_like($field, ":{$uniqueparam}1", true, true, true);
                    $likeparam["{$uniqueparam}1"] = '%' . $DB->sql_like_escape('1') . '%';
                    // We also have to exclude the formatting character '-'.
                    $likesql .= " AND " . $DB->sql_like($field, ":{$uniqueparam}2", true, true, true);
                    $likeparam["{$uniqueparam}2"] = '%' . $DB->sql_like_escape('-') . '%';
                    break;
                case 1:
                    // [1] => All Yes - !like(No) && !like(linespacer).
                    $likesql = $DB->sql_like($field, ":{$uniqueparam}1", true, true, true);
                    $likeparam["{$uniqueparam}1"] = '%' . $DB->sql_like_escape('0') . '%';
                    // We also have to exclude the formatting character '-'.
                    $likesql .= " AND " . $DB->sql_like($field, ":{$uniqueparam}2", true, true, true);
                    $likeparam["{$uniqueparam}2"] = '%' . $DB->sql_like_escape('-') . '%';
                    break;
                case 2:
                    // [2] => Any No - like(No).
                    $likesql = $DB->sql_like($field, ":{$uniqueparam}", true, true, false);
                    $likeparam["{$uniqueparam}"] = '%' . $DB->sql_like_escape('0') . '%';
                    break;
                case 3:
                    // [3] => Any Yes - like(Yes).
                    $likesql = $DB->sql_like($field, ":{$uniqueparam}", true, true, false);
                    $likeparam["{$uniqueparam}"] = '%' . $DB->sql_like_escape('1') . '%';
                    break;
            }
            return array($likesql, $likeparam);
        }
    }
}
