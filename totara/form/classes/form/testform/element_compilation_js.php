<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form_controller;

/**
 * Element compilation JS test form.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_compilation_js extends element_compilation {

    /**
     * The form name.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Compilation of elements loaded in JS';
    }

    /**
     * Returns true if the form should be initialised in JS.
     */
    public static function initialise_in_js() {
        return true;
    }

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|form_controller
     */
    public static function get_form_controller() {
        return new element_compilation_js_controller;
    }
}
