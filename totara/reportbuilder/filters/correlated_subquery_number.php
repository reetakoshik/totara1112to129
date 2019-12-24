<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

require_once(__DIR__ . '/number.php');

/**
 * Generic filter for correlated subquery searches via normal number fields.
 *
 * NOTE: this filter requires the following options:
 *        - 'subquery' the correlated subquery with first sprintf placeholder for normal field parameter and second placeholder for search condition
 *        - 'searchfield' the column from subquery used to create search condition via normal number filter
 */
class rb_filter_correlated_subquery_number extends rb_filter_number {
    private $overridenumberfield = null;

    /**
     * Return SQL snippet for field name depending on report cache settings
     */
    public function get_field() {
        if (isset($this->overridenumberfield)) {
            return $this->overridenumberfield;
        }
        return parent::get_field();
    }

    /**
     * Returns the condition to be used with SQL where.
     *
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        $this->overridenumberfield = $this->options['searchfield'];
        list($select, $params) = parent::get_sql_filter($data);
        $this->overridenumberfield = null;

        $select = sprintf($this->options['subquery'], $this->get_field(), $select);

        return array($select, $params);
    }
}
