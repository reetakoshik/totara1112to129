<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

require_once(__DIR__ . '/cohort.php');

/**
 * Generic filter for correlated subquery searches via cohort fields.
 */
class rb_filter_correlated_subquery_cohort extends rb_filter_cohort {
    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array
     */
    function get_sql_filter($data) {
        global $DB;

        $items = explode(',', $data['value']);
        $searchfield = $this->get_field();

        // don't filter if none selected
        if (empty($items)) {
            // return 1=1 instead of TRUE for MSSQL support
            return array(" 1=1 ", array());
        }

        $items = array_map('intval', $items);
        list($select, $params) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED, 'cid');

        // Any cohort from the list will do.
        $sql = " EXISTS(SELECT 'x'
                          FROM {cohort_members} cm
                         WHERE cm.userid = {$searchfield} AND cm.cohortid {$select}) ";

        return array($sql, $params);
    }
}
