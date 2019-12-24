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

use totara_form\form\element\datetime;
use totara_form\form\group\section;
use totara_form\form\clientaction\hidden_if;

/**
 * Date/time test form
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_datetime extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Basic datetime element';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'datetime_with_current_data' => '1457388300', // Is 2016-03-07T22:05:00.000.
            'datetime_frozen_with_current_data' => '1466681220', // Is 2016-06-23T11:27.
        ];
    }

    /**
     * Defines the test form
     */
    public function definition() {

        $this->model->add(new datetime('datetime_basic', 'Basic datetime'));
        $datetime_required = $this->model->add(new datetime('datetime_required', 'Required basic datetime'));
        $datetime_required->set_attribute('required', true);
        $datetime_required->add_help_button('cachejs', 'core_admin'); // Just a random help string.
        $this->model->add(new datetime('datetime_with_current_data', 'datetime with current data'))->add_help_button('cachejs', 'core_admin'); // Just a random help string.;
        $this->model->add(new datetime('datetime_frozen_empty', 'Empty frozen datetime'))->set_frozen(true);
        $this->model->add(new datetime('datetime_frozen_with_current_data', 'Frozen datetime with current data'))->set_frozen(true);

        $section = $this->model->add(new section('test_hiddenif', 'Testing Hiddenif'));
        $hiddenif_primary = $section->add(new datetime('hiddenif_primary', 'Hidden if reference', 'Pacific/Auckland'));
        $hiddenif_secondary_a = $section->add(new datetime('hiddenif_secondary_a', 'Visible when \'Testing Hiddenif\' is not empty'));
        $hiddenif_secondary_b = $section->add(new datetime('hiddenif_secondary_b', 'Visible when \'Testing Hiddenif\' is empty'));
        $hiddenif_secondary_e = $section->add(new datetime('hiddenif_secondary_e', 'Visible when \'Testing Hiddenif\' is not filled'));
        $hiddenif_secondary_f = $section->add(new datetime('hiddenif_secondary_f', 'Visible when \'Testing Hiddenif\' is filled'));

        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_a))->is_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_b))->not_empty($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_e))->is_filled($hiddenif_primary);
        $this->model->add_clientaction(new hidden_if($hiddenif_secondary_f))->not_filled($hiddenif_primary);

        $section = $this->model->add(new section('test_hiddenif_required', 'Testing Hiddenif with \'Required basic datetime\''));
        $hiddenif_required_a = $section->add(new datetime('hiddenif_required_a', 'Visible when \'Required basic datetime\' is not empty'));
        $hiddenif_required_b = $section->add(new datetime('hiddenif_required_b', 'Visible when \'Required basic datetime\' is empty'));
        $this->model->add_clientaction(new hidden_if($hiddenif_required_a))->is_empty($datetime_required);
        $this->model->add_clientaction(new hidden_if($hiddenif_required_b))->not_empty($datetime_required);

        $this->add_required_elements();
    }

    /**
     * Post submit pre display formatting.
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function process_after_submit(\stdClass $data) {
        $format = 'Y/m/d H:i';

        $fields = array(
            'datetime_basic',
            'datetime_required',
            'datetime_with_current_data',
            'datetime_frozen_empty',
            'datetime_frozen_with_current_data',
            'hiddenif_primary',
            'hiddenif_secondary_a',
            'hiddenif_secondary_b',
            'hiddenif_secondary_e',
            'hiddenif_secondary_f',
            'hiddenif_required_a',
            'hiddenif_required_b',

        );
        foreach ($fields as $field) {
            if (!empty($data->$field)) {
                $data->$field .= ' (' . date($format, $data->$field) . ' ' . \core_date::get_user_timezone() . ')';
            }
        }

        return parent::process_after_submit($data);
    }


}
