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

namespace totara_catalog;

/**
 * A simple storage structure representing an optional param which can be provided in a page request.
 */
class optional_param {

    /** @var string */
    public $key;

    /** @var mixed */
    public $default;

    /** @var string */
    public $type;

    /** @var bool */
    public $multiplevalues;

    /**
     * @param string $key
     * @param mixed $default
     * @param string $type
     * @param bool $multiplevalues
     */
    public function __construct(string $key, $default, string $type, bool $multiplevalues = false) {
        $this->key = $key;
        $this->default = $default;
        $this->type = $type;
        $this->multiplevalues = $multiplevalues;
    }
}
