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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form\element\hidden;

/**
 * Abstract test form class that faciliates acceptance testing of forms and elements.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
abstract class form extends \totara_form\form {

    /**
     * Returns the name for this form. You should override this to describe breifly the purpose of the test form class.
     *
     * @return string
     */
    public static function get_form_test_name() {
        // You should override this.
        return 'Unnamed test';
    }

    /**
     * An array of any current data to pass into the form once it is constructed.
     *
     * @return array
     */
    public static function get_current_data_for_test() {
        return [];
    }

    /**
     * Any parameters to pass to the test form when it is being constructed.
     *
     * @return array
     */
    public static function get_params_for_test() {
        return [];
    }

    /**
     * Process data after the submission, prior to it being checked.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function process_after_submit(\stdClass $data) {
        return $data;
    }

    /**
     * Returns true if the form should be initialised in JS.
     */
    public static function initialise_in_js() {
        return false;
    }

    /**
     * Adds elements required by the test form architecture.
     *
     * @throws \coding_exception
     */
    protected function add_required_elements() {
        $this->model->add(new hidden('form_select', PARAM_RAW));
        $this->model->add_action_buttons(false, 'Save changes');
    }

    /**
     * Takes a non-scalar value and formats it as a string for display in the test form.
     *
     * @throws \coding_exception If the method needs to be overridden by the test form.
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public static function format_for_display($name, $value) {
        if (is_null($value)) {
            return "--null--";
        } else if (is_scalar($value)) {
            return s($value);
        } else if (is_array($value)) {
            $assoc = self::is_associative_array($value);
            $parts = [];
            foreach ($value as $k => $v) {
                if ($assoc) {
                    $parts[] = "{$k} => '" . self::format_for_display($name, $v) . "'";
                } else {
                    $parts[] = "'" . self::format_for_display($name, $v) . "'";
                }
            }
            // We don't use var_export here because it looks crap.
            return '[ '.join(' , ', $parts).' ]';
        }
        throw new \coding_exception('You must format the non-scalar value for '.(string)$name, 'JSON encoded value: '.json_encode($value));
    }

    /**
     * Helper function to check if the given array is associative or not.
     *
     * @param array $array
     * @return bool Returns true if the given array is associative.
     */
    protected static function is_associative_array(array $array) {
        return (array_keys($array) !== range(0, count($array) - 1));
    }

}
