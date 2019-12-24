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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\clientaction;

/**
 * Enables an element to support the onchange client action.
 *
 * Not all elements can support the onchange client action due to requirements placed upon the client action in JavaScript.
 * In order to prevent confusion and ensure that developers only use the client action in situations it will work all elements
 * that support the client action must implement this class.
 * It places no requirements on the element in PHP, however the element must call the form.change event in JavaScript and it
 * must return a valid relevant value when queried in JavaScript.
 *
 * @since Totara 9.10, 10
 * @package totara_form
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
interface supports_onchange_clientactions {
    // Nothing is required, simply implementing this interface enables use of the onchange client actions.
    // However if you wish you can define the following method which will be used if developers require the client action
    // to ignore empty values.
    //
    // /**
    //  * Returns an array of strings that are considered empty for this element.
    //  * @return string[]
    //  */
    // public function get_empty_values() {
    //     return ['', '0'];
    // }
}