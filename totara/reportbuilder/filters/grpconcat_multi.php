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
require_once($CFG->dirroot . '/totara/reportbuilder/filters/multicheck.php');

/**
 * NOTE: This filter assumes that simple mode is enabled, see the
 *       add_job_custom_field_filters() function for an example.
 *
 *       This filter also assumes that concat = true since that's what it is for.
 *
 */
class rb_filter_grpconcat_multi extends rb_filter_multicheck {

    /**
     * Returns the condition to be used with SQL where
     *
     * @param array $data filter settings
     * @param bool $usefieldalias use fieldalias rather than field - used for counting with grouped reports
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data, $usefieldalias = false) {
        global $DB;

        $operator = $data['operator'];
        $items    = $data['value'];
        $query = $usefieldalias ? $this->fieldalias : $this->field;
        $uniqueparam = rb_unique_param('mcfilter');

        switch($operator) {
            case 1:
                $glue = ' OR ';
                break;
            case 2:
                $glue = ' AND ';
                break;
            default:
                // return 1=1 instead of TRUE for MSSQL support
                return array(' 1=1 ', array());
        }

        // Query is of the form "1|2|3|4", by concatenating pipes to
        // either end we can match any item with a single LIKE, instead
        // of having to handle end matches separately.
        if ($this->options['concat']) {
            $query = $DB->sql_concat("'|'", $query, "'|'");
        }

        $res = array();
        $params = array();
        if (is_array($items)) {
            $count = 1;
            foreach ($items as $id => $selected) {
                $uqparam = $uniqueparam . '_' . $count;
                if ($selected) {
                    $filter = "( " . $DB->sql_like($query, ":{$uqparam}") . ") \n";
                    $params[$uqparam] = '%' . $DB->sql_like_escape($id) . '%';
                    $res[] = $filter;

                    $count++;
                }
            }
        }

        // No options selected.
        if (count($res) == 0) {
            return array(' 1=0 ', array());
        }

        return array('(' . implode($glue, $res) . ')', $params);
    }
}

