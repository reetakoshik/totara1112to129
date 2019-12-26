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

class like_or extends filter {

    /** @var string */
    private $likeprefix = '';

    /** @var string */
    private $likesuffix = '';

    /**
     * @param string $prefix Added to the start of the selected values, e.g. '%'.
     * @param string $suffix Added to the end of the selected values, e.g. '%'.
     */
    public function set_prefix_and_suffix(string $prefix = '', string $suffix = '') {
        $this->likeprefix = $prefix;
        $this->likesuffix = $suffix;
    }

    protected function validate_current_data($data) {
        if (is_null($data)) {
            return $data;
        }

        if (!is_array($data)) {
            throw new \coding_exception('like or search filter only accepts null or array data');
        }

        $encoded = [];
        foreach ($data as $datum) {
            if (!(is_int($datum) || is_string($datum) || is_bool($datum))) {
                throw new \coding_exception('like or search filter only accepts null, int, string or bool data in an array');
            }
            $encoded[] = $this->filter_json_encode($datum);
        }

        return $encoded;
    }

    /**
     * Like or is active if one or more values have been specified.
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
            throw new \coding_exception('Tried to apply \'like_or\' filter with no values specified');
        }

        $matches = [];
        $params = [];

        foreach ($this->currentdata as $selected) {
            $uniqueparam = $DB->get_unique_param(substr($this->alias, 0, 20));
            $matches[] = $DB->sql_like($source->filterfield, ':' . $uniqueparam);
            $params[$uniqueparam] = $this->likeprefix . $selected . $this->likesuffix;
        }

        $where = '(' . implode(' OR ', $matches) . ')';

        return [$where, $params];
    }
}
