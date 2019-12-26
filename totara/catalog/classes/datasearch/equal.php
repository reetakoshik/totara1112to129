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

class equal extends filter {

    protected function validate_current_data($data) {
        if (is_null($data) || is_int($data) || is_string($data) || is_bool($data)) {
            return $data;
        }

        throw new \coding_exception('equal filter only accepts null, int, string or bool data');
    }

    /**
     * Equal is active if a value has been specified.
     *
     * @return bool
     */
    public function is_active(): bool {
        return !is_null($this->currentdata);
    }

    protected function make_compare(\stdClass $source): array {
        global $DB;

        if (!$this->is_active()) {
            throw new \coding_exception('Tried to apply \'equal\' filter with no value specified');
        }

        $uniqueparam = $DB->get_unique_param(substr($this->alias, 0, 20));
        $where = "{$source->filterfield} = :{$uniqueparam}";
        $params = [$uniqueparam => $this->currentdata];

        return [$where, $params];
    }
}