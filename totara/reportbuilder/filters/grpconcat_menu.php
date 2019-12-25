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

class rb_filter_grpconcat_menu extends rb_filter_select {

    /**
     * Constructor
     *
     * @param string  $type         The filter type (from the db or embedded source)
     * @param string  $value        The filter value (from the db or embedded source)
     * @param integer $advanced     If the filter should be shown by default (0) or only
     *                              when advanced options are shown (1)
     * @param integer $region       Which region this filter appears in.
     * @param reportbuilder $report The report this filter is for
     * @param array   $defaultvalue Default value for the filter
     */
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        // Always simple mode to ensure single value select.
        $this->options['simplemode'] = true;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        global $DB;

        $value = $data['value'];
        $field = $this->get_field();

        if ($value == '') {
            // return 1=1 instead of TRUE for MSSQL support
            return [' 1=1 ', []];
        }

        $likeparam = array();
        $uniqueparam = rb_unique_param('mnfilter');
        $likesql = $DB->sql_like($field, ":{$uniqueparam}", true, true, false);
        $likeparam["{$uniqueparam}"] = '%' . $DB->sql_like_escape($value) . '%';

        return array($likesql, $likeparam);
    }
}
