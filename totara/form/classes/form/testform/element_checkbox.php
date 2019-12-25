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

use totara_form\form\element\checkbox;
use totara_form\form\group\section;
use totara_form\form\clientaction\hidden_if;

/**
 * Checkbox test form
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_checkbox extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Basic checkbox element';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'checkbox_with_current_data' => 'yes',
            'checkbox_frozen_with_current_data' => 'true',
        ];
    }

    /**
     * Defines this form.
     */
    public function definition() {

        $this->model->add(new checkbox('checkbox_basic', 'Basic checkbox'));
        $checkbox_required = $this->model->add(new checkbox('checkbox_required', 'Required basic checkbox'));
        $checkbox_required->set_attribute('required', true);
        $checkbox_required->add_help_button('cachejs', 'core_admin'); // Just a random help string.
        $this->model->add(new checkbox('checkbox_with_current_data', 'Checkbox with current data', 'yes', 'no'))->add_help_button('cachejs', 'core_admin'); // Just a random help string.;
        $this->model->add(new checkbox('checkbox_frozen_empty', 'Empty frozen checkbox'))->set_frozen(true);
        $this->model->add(new checkbox('checkbox_frozen_with_current_data', 'Frozen checkbox with current data', 'true', 'false'))->set_frozen(true);

        $section = $this->model->add(new section('test_hiddenif', 'Testing Hiddenif'));
        $hiddenif_primary = $section->add(new checkbox('hiddenif_primary', 'Hidden if reference'));
        $hiddenif_secondary_a = $section->add(new checkbox('hiddenif_secondary_a', 'A is visible when test is checked', '1', '0'));
        $hiddenif_secondary_b = $section->add(new checkbox('hiddenif_secondary_b', 'B is visible when test is not checked', 'true', 'false'));
        $hiddenif_secondary_c = $section->add(new checkbox('hiddenif_secondary_c', 'C is visible when test is not checked', 'YES', 'NO'));
        $hiddenif_secondary_d = $section->add(new checkbox('hiddenif_secondary_d', 'D is visible when test is checked', 'New Zealand', 'United Kingdom'));
        $hiddenif_secondary_e = $section->add(new checkbox('hiddenif_secondary_e', 'E is visible when test is not checked', '0', ''));
        $hiddenif_secondary_f = $section->add(new checkbox('hiddenif_secondary_f', 'F is visible when test is checked', '0', '1'));

        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_a))->is_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_b))->not_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_c))->is_equal($hiddenif_primary, '1');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_d))->not_equals($hiddenif_primary, '1');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_e))->is_filled($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_f))->not_filled($hiddenif_primary);

        $section = $this->model->add(new section('test_hiddenif_required', 'Testing Hiddenif with required'));
        $hiddenif_required_a = $section->add(new checkbox('hiddenif_required_a', 'G is visible when required checkbox is checked'));
        $hiddenif_required_b = $section->add(new checkbox('hiddenif_required_b', 'H is visible when required checkbox is not checked'));
        $this->model->add_clientaction(new hidden_if($hiddenif_required_a))->is_empty($checkbox_required);
        $this->model->add_clientaction(new hidden_if($hiddenif_required_b))->not_empty($checkbox_required);

        $this->add_required_elements();
    }

}
