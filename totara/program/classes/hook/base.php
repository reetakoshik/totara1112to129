<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\hook;

use totara_core\hook\base as base_hook;

defined('MOODLE_INTERNAL') || die();

abstract class base extends base_hook {
    /**
     * @var int
     */
    private $programid;

    /**
     * Toggle this value to true, if this hook is for completing certification form, instead of program.
     *
     * @var bool
     */
    private $iscertif;

    /**
     * base constructor.
     *
     * @param int $programid
     */
    public function __construct(int $programid) {
        $this->programid = $programid;
        $this->iscertif = false;
    }

    /**
     * Returning the program's id. If there is none program's id, then zero will be returned.
     *
     * @return int
     */
    final public function get_programid(): int {
        return $this->programid;
    }

    /**
     * @param bool $value
     * @return void
     */
    final public function set_certification(bool $value = true): void {
        $this->iscertif = $value;
    }

    /**
     * @return bool
     */
    final public function is_certification(): bool {
        return $this->iscertif;
    }
}