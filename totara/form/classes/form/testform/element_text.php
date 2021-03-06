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

use totara_form\form\element\text;
use totara_form\form\group\section;
use totara_form\form\clientaction\hidden_if;

/**
 * Text test form
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_text extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Basic text element';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'text_with_current_data' => 'Cheerios',
            'text_frozen_with_current_data' => 'Sausage rolls',
        ];
    }

    /**
     * Defines the test form
     */
    public function definition() {

        $this->model->add(new text('text_basic', 'Basic text', PARAM_RAW));
        $text_required = $this->model->add(new text('text_required', 'Required basic text', PARAM_RAW));
        $text_required->set_attribute('required', true);
        $text_required->add_help_button('cachejs', 'core_admin'); // Just a random help string.
        $this->model->add(new text('text_with_current_data', 'Text with current data', PARAM_RAW))->add_help_button('cachejs', 'core_admin'); // Just a random help string.;
        $this->model->add(new text('text_frozen_empty', 'Empty frozen text', PARAM_RAW))->set_frozen(true);
        $this->model->add(new text('text_frozen_with_current_data', 'Frozen text with current data', PARAM_RAW))->set_frozen(true);

        $section = $this->model->add(new section('test_hiddenif', 'Testing Hiddenif'));
        $hiddenif_primary = $section->add(new text('hiddenif_primary', 'Hidden if reference', PARAM_RAW));
        $hiddenif_secondary_a = $section->add(new text('hiddenif_secondary_a', 'Visible when test is not empty', PARAM_RAW));
        $hiddenif_secondary_b = $section->add(new text('hiddenif_secondary_b', 'Visible when test is empty', PARAM_RAW));
        $hiddenif_secondary_c = $section->add(new text('hiddenif_secondary_c', 'Visible when test is not equal to \'Behat\'', PARAM_RAW));
        $hiddenif_secondary_d = $section->add(new text('hiddenif_secondary_d', 'Visible when test equals \'Behat\'', PARAM_RAW));
        $hiddenif_secondary_e = $section->add(new text('hiddenif_secondary_e', 'Visible when test is not filled', PARAM_RAW));
        $hiddenif_secondary_f = $section->add(new text('hiddenif_secondary_f', 'Visible when test is filled', PARAM_RAW));

        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_a))->is_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_b))->not_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_c))->is_equal($hiddenif_primary, 'Behat');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_d))->not_equals($hiddenif_primary, 'Behat');
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_e))->is_filled($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_f))->not_filled($hiddenif_primary);

        $section = $this->model->add(new section('test_hiddenif_required', 'Testing Hiddenif with required'));
        $hiddenif_required_a = $section->add(new text('hiddenif_required_a', 'Visible when required text is not empty', PARAM_RAW));
        $hiddenif_required_b = $section->add(new text('hiddenif_required_b', 'Visible when required text is empty', PARAM_RAW));
        $this->model->add_clientaction(new hidden_if($hiddenif_required_a))->is_empty($text_required);
        $this->model->add_clientaction(new hidden_if($hiddenif_required_b))->not_empty($text_required);

        $this->add_required_elements();
    }


}
