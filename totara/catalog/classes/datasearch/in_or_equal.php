<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\datasearch;

defined('MOODLE_INTERNAL') || die();

class in_or_equal extends filter {

    protected function validate_current_data($data) {
        if (is_null($data)) {
            return $data;
        }

        if (!is_array($data)) {
            throw new \coding_exception('in or equal search filter only accepts null or array data of int, string or bool');
        }

        foreach ($data as $datum) {
            if (!(is_int($datum) || is_string($datum) || is_bool($datum))) {
                throw new \coding_exception('in or equal search filter only accepts int, string or bool data in an array');
            }
        }

        return $data;
    }

    /**
     * In or equal is active if one or more values have been specified.
     *
     * @return bool
     */
    public function is_active(): bool {
        if (!is_array($this->currentdata)) {
            return false;
        };

        return !empty($this->currentdata);
    }

    protected function make_compare(\stdClass $source): array {
        global $DB;

        if (!$this->is_active()) {
            throw new \coding_exception('Tried to apply \'in_or_equal\' filter with no values specified');
        }

        list($sql, $params) = $DB->get_in_or_equal($this->currentdata, SQL_PARAMS_NAMED, substr($this->alias, 0, 20));
        $where = "{$source->filterfield} {$sql}";

        return [$where, $params];
    }
}