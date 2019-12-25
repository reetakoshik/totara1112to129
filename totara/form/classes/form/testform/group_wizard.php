<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form_controller;
use totara_form\form\element\filepicker;
use totara_form\form\element\yesno;
use totara_form\form\element\hidden;
use totara_form\form\element\select;
use totara_form\form\element\text;
use totara_form\form\element\textarea;
use totara_form\form\element\radios;
use totara_form\form\group\section;
use totara_form\form\group\wizard;
use totara_form\form\group\wizard_stage;

/**
 * Wizard test form
 *
 * @author    Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @author    Matthias Bonk <matthias.bonk@totaralearning.com>
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   totara_form
 */
abstract class group_wizard extends form {

    abstract public function set_wizard_features(wizard $wizard);

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|form_controller
     */
    public static function get_form_controller() {
        return null;
    }

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return null;
    }

    /**
     * Defines the test form
     */
    public function definition() {
        // This hidden field is necessary for test page integration.
        $this->model->add(new hidden('form_select', PARAM_RAW));

        /** @var wizard $wizard */
        $wizard = $this->model->add(new wizard('my_wizard'));

        $wizard = $this->set_wizard_features($wizard);

        $stage1 = $wizard->add_stage(new wizard_stage('stage1', 'Personal data'));
        $stage1->add(new text('fullname', 'Full name', PARAM_TEXT));
        $stage1->add(new text('preferredname', 'Preferred name', PARAM_TEXT));
        $stage1->add(new select('gender', 'Gender', [
            'notset' => 'Not set',
            'female' => 'Female',
            'male' => 'Male',
            'other' => 'Other',
        ]));
        $stage1->add(new radios('keepalldata', 'Keep all data', [
            'y' => 'Yes',
            'n' => 'No',
            'm' => 'Maybe',
        ]));

        $stage2 = $wizard->add_stage(new wizard_stage('stage2', 'Learning activity'));
        $stage2->add(new text('phonenumber', 'Phone', PARAM_TEXT))->set_attribute('required', true);
        $stage2->add(new textarea('address', 'Address', PARAM_TEXT));
        $stage2->add(new select('country', 'Country', \get_string_manager()->get_list_of_countries(true)));

        $stage3 = $wizard->add_stage(new wizard_stage('stage3', 'Learning records'));
        $section = $stage3->add(new section('test_section', 'Testing Section'));
        $section->add(new yesno('yesno', 'Yes or No?'));

        $stage4 = $wizard->add_stage(new wizard_stage('stage4', 'Other data'));
        $stage4->add(new text('favourite_colour', 'Favourite colour', PARAM_TEXT))->set_attribute('required', true);

        $stage5 = $wizard->add_stage(new wizard_stage('stage5', 'File upload'));
        $stage5->add(new filepicker('filepicker', 'File picker'));

        // Custom submit button label
        $wizard->set_submit_label('Submit me');

        $wizard->finalise();
    }
}
