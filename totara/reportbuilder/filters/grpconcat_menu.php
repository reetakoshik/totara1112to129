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
 * TODO - This filter used to be "Equals" but is now "Contains", it would be nice to add a selector with more options.
 *
 * NOTE: This filter assumes that simple mode is enabled, see the
 *       add_job_custom_field_filters() function for an example.
 */
class rb_filter_grpconcat_menu extends rb_filter_select {

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        global $DB;

        $value = $data['value'];
        $field = $this->get_field();
        $likeparam = array();

        $uniqueparam = rb_unique_param('mnfilter');
        $likesql = $DB->sql_like($field, ":{$uniqueparam}", true, true, false);
        $likeparam["{$uniqueparam}"] = '%' . $DB->sql_like_escape($value) . '%';

        return array($likesql, $likeparam);
    }
}
