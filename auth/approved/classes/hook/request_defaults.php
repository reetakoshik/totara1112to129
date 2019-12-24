<?php
/*
 * This file is part of Totara LMS
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
 *
 * @package auth_approved
 */

namespace auth_approved\hook;

/**
 * Hook for altering of default data for \auth_approved\form\signup form
 * when user is signing up for a new account.
 */
class request_defaults extends \totara_core\hook\base {
    /**
     * The defaults array for the sign up form.
     * @var array
     */
    public $defaults;

    /**
     * @param array[] $defaults
     */
    public function __construct(array $defaults) {
        $this->defaults = $defaults;
    }
}