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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form\clientaction\onchange_submit;
use totara_form\form\element\radios;
use totara_form\form\group\section;

/**
 * Onchange submit client action radio  test form.
 *
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @copyright 2018 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class clientaction_onchange_submit_radios extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Onchange submit client radios action test';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'radios_3' => 1,
            'radios_6' => 'b',
            'radios_frozen_with_current_data' => 'c',
        ];
    }

    /**
     * Defines this form.
     */
    public function definition() {
        $options = [
            'one',
            'two',
            'three'
        ];
        $options2 = [
            'a' => 'one',
            'b' => 'two',
            'c' => 'three'
        ];

        $this->model->add(new section('radios_tests', 'radios tests'));

        $this->model->add(new radios('radios_1', 'radios without clientaction', $options));

        $radios = $this->model->add(new radios('radios_2', 'radios', $options));
        $this->model->add_clientaction(new onchange_submit($radios));
        $radios->set_attribute('horizontal', true);

        $radios = $this->model->add(new radios('radios_3', 'radios ignore empty', $options));
        $this->model->add_clientaction((new onchange_submit($radios))->ignore_empty_values());

        $radios = $this->model->add(new radios('radios_4', 'radios ignored values (one)', $options));
        $this->model->add_clientaction((new onchange_submit($radios))->add_ignored_value('0'));

        $radios = $this->model->add(new radios('radios_5', 'radios custom values', $options2));
        $this->model->add_clientaction(new onchange_submit($radios));

        $radios = $this->model->add(new radios('radios_6', 'radios custom values and ignored one', $options2));
        $this->model->add_clientaction((new onchange_submit($radios))->add_ignored_value('a'));

        $radios = $this->model->add(new radios('radios_7', 'radios custom values and ignore current', $options2));
        $this->model->add_clientaction((new onchange_submit($radios))->add_ignored_value('b'));

        $items = $this->model->get_items()[0]->get_items();
        $defaultdata = clientaction_onchange_submit_radios::get_current_data_for_test();

        $this->add_required_elements();
    }

}