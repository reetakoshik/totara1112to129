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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form\form\element;

/**
 * Yes/No element.
 *
 * Note: by default nothing is selected and user is not required to select an option.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class yesno extends radios {
    /**
     * Yes/No element.
     *
     * @param string $name
     * @param string $label
     */
    public function __construct($name, $label) {
        if (func_num_args() > 2) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        $options = array(
            '1' => get_string('yes'),
            '0' => get_string('no'),
        );
        parent::__construct($name, $label, $options);
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $result = parent::export_for_template($output);
        $result['form_item_template'] = 'totara_form/element_yesno';
        $result['amdmodule'] = 'totara_form/form_element_yesno';
        return $result;
    }
}
