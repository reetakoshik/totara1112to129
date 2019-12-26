<?php
/*
 * This file is part of Totara LMS
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\condition;

use mod_facetoface\signup;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract Condition. All conditions in booking states must extend it
 */
abstract class condition {
    /**
     * @var signup instance to be tested for condition
     */
    protected $signup = null;

    /**
     * Condition constructor.
     * @param signup $signup
     */
    public function __construct(signup $signup) {
        $this->signup = $signup;
    }


    /**
     * Is condition passing
     * @return bool
     */
    abstract public function pass(): bool;

    /**
     * Get description of condition
     * @return string
     */
    abstract public static function get_description(): string;

    /**
     * Return explanation why condition has not passed
     * Override only when message would make sense for user
     * @return array
     */
    public function get_failure(): array {
        return [];
    }
}
