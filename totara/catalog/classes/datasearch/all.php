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

/**
 * Class all.
 *
 * Use this class to specify some sql in your datasearch without actually removing any
 * records from the result.
 *
 * @package totara_catalog\datasearch
 */
class all extends filter {

    protected function validate_current_data($data) {
        return $data;
    }

    /**
     * All is always active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return true;
    }

    protected function make_compare(\stdClass $source): array {
        return ["", []];
    }
}