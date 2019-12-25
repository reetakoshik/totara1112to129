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

use totara_form\form\clientaction\onchange_reload;
use totara_form\form\element\checkbox;
use totara_form\form\element\static_html;
use totara_form\form\group\section;
use totara_form\form_controller;

/**
 * Onchange reload client action test form.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class clientaction_onchange_reload_checkbox extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Onchange reload client checkbox action test';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'checkbox_3' => 1,
            'checkbox_6' => 'banana',
            'checkbox_frozen_with_current_data' => 'true',
        ];
    }

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|form_controller
     */
    public static function get_form_controller() {
        return new clientaction_onchange_reload_controller_checkbox();
    }

    /**
     * Defines this form.
     */
    public function definition() {

        $this->model->add(new section('checkbox_tests', 'Checkbox tests'));

        $this->model->add(new checkbox('checkbox_1', 'Checkbox without clientaction'));

        $checkbox = $this->model->add(new checkbox('checkbox_2', 'Checkbox'));
        $this->model->add_clientaction(new onchange_reload($checkbox));

        $checkbox = $this->model->add(new checkbox('checkbox_3', 'Checkbox ignore empty'));
        $this->model->add_clientaction((new onchange_reload($checkbox))->ignore_empty_values());

        $checkbox = $this->model->add(new checkbox('checkbox_4', 'Checkbox ignored values'));
        $this->model->add_clientaction((new onchange_reload($checkbox))->add_ignored_value('0'));

        $checkbox = $this->model->add(new checkbox('checkbox_5', 'Checkbox custom values', 'banana', 'apple'));
        $this->model->add_clientaction(new onchange_reload($checkbox));

        $checkbox = $this->model->add(new checkbox('checkbox_6', 'Checkbox custom values and ignored unchecked', 'banana', 'apple'));
        $this->model->add_clientaction((new onchange_reload($checkbox))->add_ignored_value('apple'));

        $checkbox = $this->model->add(new checkbox('checkbox_7', 'Checkbox custom values and ignored checked', 'banana', 'apple'));
        $this->model->add_clientaction((new onchange_reload($checkbox))->add_ignored_value('banana'));

        $items = $this->model->get_items()[0]->get_items();
        $defaultdata = clientaction_onchange_reload_checkbox::get_current_data_for_test();

        foreach ($items as $item) {
            if (isset($defaultdata[$item->get_name()]) && $item->get_data()[$item->get_name()] !== $defaultdata[$item->get_name()]) {
                $this->model->add(new static_html($item->get_name() . '_reloaded', $item->get_name() . ' unchecked', 'success'));
            } else if ($item->get_data()[$item->get_name()] !== 'apple' && $item->get_data()[$item->get_name()] !== '0') {
                $this->model->add(new static_html($item->get_name() . '_reloaded', $item->get_name() . ' checked', 'unchecked'));
            } else {
                $this->model->add(new static_html($item->get_name() . '_reloaded', $item->get_name() . ' unchanged', 'success'));
            }
        }

        $this->add_required_elements();

    }

}