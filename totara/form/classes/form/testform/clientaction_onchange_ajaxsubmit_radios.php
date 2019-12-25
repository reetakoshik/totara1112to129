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

use totara_form\form\clientaction\onchange_ajaxsubmit;
use totara_form\form\element\radios;
use totara_form\form\element\static_html;
use totara_form\form\group\section;
use totara_form\form_controller;

/**
 * Onchange ajaxsubmit client radio action test form.
 *
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @copyright 2018 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class clientaction_onchange_ajaxsubmit_radios extends form {

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Onchange ajaxsubmit client radios action test';
    }

    /**
     * Returns the current data for this form.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [];
    }

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|form_controller
     */
    public static function get_form_controller() {
        return new clientaction_onchange_ajaxsubmit_controller_radios();
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

        $this->model->add(new section('radios_tests', 'radios tests'));

        $this->model->add(new radios('radios_1', 'radios without clientaction', $options));

        $radio = $this->model->add(new radios('radios_2', 'radio', $options));
        $this->model->add_clientaction(new onchange_ajaxsubmit($radio));

        if (!empty($radio->get_data()['radios_2'])) {
            $this->model->add(new static_html('radios_2_reloaded', 'radios 2 submit via ajax', 'Success!'));
        }

        $this->add_required_elements();

    }

}