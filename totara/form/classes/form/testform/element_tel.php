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

use totara_form\form\element\tel;
use totara_form\form\group\section;
use totara_form\form\clientaction\hidden_if;

/**
 * Tel element text form.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_tel extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Basic tel element';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'tel_with_current_data' => '202-555-0167',
            'tel_frozen_with_current_data' => '+1-202-555-0149',
        ];
    }

    /**
     * Defines the test form
     */
    public function definition() {

        $this->model->add(new tel('tel_basic', 'Basic tel'));
        $tel_required = $this->model->add(new tel('tel_required', 'Required basic tel'));
        $tel_required->set_attribute('required', true);
        $tel_required->add_help_button('cachejs', 'core_admin'); // Just a random help string.
        $this->model->add(new tel('tel_with_current_data', 'tel with current data'))->add_help_button('cachejs', 'core_admin'); // Just a random help string.;
        $this->model->add(new tel('tel_frozen_empty', 'Empty frozen tel'))->set_frozen(true);
        $this->model->add(new tel('tel_frozen_with_current_data', 'Frozen tel with current data'))->set_frozen(true);

        $section = $this->model->add(new section('test_hiddenif', 'Testing Hiddenif'));
        $hiddenif_primary = $section->add(new tel('hiddenif_primary', 'Hidden if reference'));
        $hiddenif_secondary_a = $section->add(new tel('hiddenif_secondary_a', 'Visible when test is not empty'));
        $hiddenif_secondary_b = $section->add(new tel('hiddenif_secondary_b', 'Visible when test is empty'));
        $hiddenif_secondary_c = $section->add(new tel('hiddenif_secondary_c', 'Visible when test is not equal to \'202-555-0191\''));
        $hiddenif_secondary_d = $section->add(new tel('hiddenif_secondary_d', 'Visible when test equals \'202-555-0191\''));
        $hiddenif_secondary_e = $section->add(new tel('hiddenif_secondary_e', 'Visible when test is not filled'));
        $hiddenif_secondary_f = $section->add(new tel('hiddenif_secondary_f', 'Visible when test is filled'));

        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_a))->is_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_b))->not_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_c))->is_equal($hiddenif_primary, '202-555-0191');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_d))->not_equals($hiddenif_primary, '202-555-0191');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_e))->is_filled($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_f))->not_filled($hiddenif_primary);

        $section = $this->model->add(new section('test_hiddenif_required', 'Testing Hiddenif with required'));
        $hiddenif_required_a = $section->add(new tel('hiddenif_required_a', 'Visible when required tel is not empty'));
        $hiddenif_required_b = $section->add(new tel('hiddenif_required_b', 'Visible when required tel is empty'));
        $this->model->add_clientaction(new hidden_if($hiddenif_required_a))->is_empty($tel_required);
        $this->model->add_clientaction(new hidden_if($hiddenif_required_b))->not_empty($tel_required);

        $this->add_required_elements();
    }


}
