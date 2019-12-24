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

use totara_form\form\element\select;
use totara_form\form\group\section;
use totara_form\form\clientaction\hidden_if;

/**
 * Select test form
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_select extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Basic select element';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'select_with_current_data' => 'yes',
            'select_frozen_with_current_data' => 'true',
        ];
    }

    /**
     * Defines the test form
     */
    public function definition() {

        $defaultoptions = array('' => 'Choose...', '1' => 'Yes', '3' => 'No');

        $this->model->add(new select('select_basic', 'Basic select', $defaultoptions));
        $select_required = $this->model->add(new select('select_required', 'Required basic select', $defaultoptions));
        $select_required->set_attribute('required', true);
        $select_required->add_help_button('cachejs', 'core_admin'); // Just a random help string.
        $this->model->add(new select('select_with_current_data', 'select with current data', ['whatever' => 'Yeah?', 'yes' => 'Oh yea!', 'nah' => 'Never!']))->add_help_button('cachejs', 'core_admin'); // Just a random help string.;
        $this->model->add(new select('select_frozen_empty', 'Empty frozen select', $defaultoptions))->set_frozen(true);
        $this->model->add(new select('select_frozen_with_current_data', 'Frozen select with current data', ['true' => '1', 'false' => '0']))->set_frozen(true);
        $this->model->add(new select('select_with_html_labels', 'Select with HTML labels', ['1' => '<b style="color:blue">Bold &amp; blue</b>', '2' => '<i style="color:green">Italic & green</i>']));

        $options = array('' => get_string('choosedots'), '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six');
        $groupedselect = new select('select_grouped', 'Select with groups', $options);
        $optgroups = array(
            'Odd' => array('1', '3', '5'),
            'Even' => array('2', '4', '6'),
            'Primes' => array('2', '3', '5', 'bogus'),
            'Missing' => array('10'),
        );
        $groupedselect->set_optgroups($optgroups);
        $this->model->add($groupedselect);

        $section = $this->model->add(new section('test_hiddenif', 'Testing Hiddenif'));
        $hiddenif_primary = $section->add(new select('hiddenif_primary', 'Hidden if reference', ['a' => 'Alpha', 'b' => 'Bravo', 'c' => 'Charlie']));
        $hiddenif_secondary_a = $section->add(new select('hiddenif_secondary_a', 'A is visible when test is selected', $defaultoptions));
        $hiddenif_secondary_b = $section->add(new select('hiddenif_secondary_b', 'B is visible when test is not selected', ['true' => '1', 'false' => '0']));
        $hiddenif_secondary_c = $section->add(new select('hiddenif_secondary_c', 'C is visible when test is not selected', ['0' => 'true', '1' => 'false']));
        $hiddenif_secondary_d = $section->add(new select('hiddenif_secondary_d', 'D is visible when test is selected', ['New Zealand' => 'NZ', 'United Kingdom' => 'UK']));
        $hiddenif_secondary_e = $section->add(new select('hiddenif_secondary_e', 'E is visible when test is not selected', ['0' => 'Yes', '' => 'No']));
        $hiddenif_secondary_f = $section->add(new select('hiddenif_secondary_f', 'F is visible when test is selected', ['x' => 'X', 'Y' => 'y']));

        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_a))->is_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_b))->not_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_c))->is_equal($hiddenif_primary, 'a');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_d))->not_equals($hiddenif_primary, 'c');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_e))->is_filled($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_f))->not_filled($hiddenif_primary);

        $section = $this->model->add(new section('test_hiddenif_required', 'Testing Hiddenif with required'));
        $hiddenif_required_a = $section->add(new select('hiddenif_required_a', 'G is visible when required select is not selected', $defaultoptions));
        $hiddenif_required_b = $section->add(new select('hiddenif_required_b', 'H is visible when required select is selected', $defaultoptions));
        $this->model->add_clientaction(new hidden_if($hiddenif_required_a))->is_empty($select_required);
        $this->model->add_clientaction(new hidden_if($hiddenif_required_b))->not_empty($select_required);

        $this->add_required_elements();
    }

}
