<?php
/*
 * This file is part of Totara Learn
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\form;

use totara_form\form\clientaction\onchange_reload;
use totara_form\form\element\text;
use totara_form\form\group\section;
use totara_form\form\element\radios;
use totara_form\form\element\select;
use totara_form\form\element\hidden;

defined('MOODLE_INTERNAL') || die();

final class create_course_form extends \totara_form\form {

    const CREATE_COURSE_MULTI_ACTIVITY = 1;
    const CREATE_COURSE_SINGLE_ACTIVITY = 2;


    public function definition() {
        if ($this->parameters['totalselected'] == 1) {
            $legend = get_string("itemselected", "totara_contentmarketplace");
        } else {
            $legend = get_string("itemselected_plural", "totara_contentmarketplace", (string) $this->parameters['totalselected']);
        }
        /** @var section $selectedcourses */
        $selectedcourses = $this->model->add(new section('selectedcourses', $legend));
        $selectedcourses->set_collapsible(false);

        $selection = new listeditor('selection', '', $this->parameters['courses']);
        $selectedcourses->add($selection);
        $this->model->add_clientaction(new onchange_reload($selection));

        /** @var section $settings */
        $settings = $this->model->add(new section('settings', get_string('coursesettings', 'totara_contentmarketplace')));
        $settings->set_collapsible(false);

        if ($this->parameters['totalselected'] == 1) {
            $multiactivitylabel = get_string('createmultipleactivitycourse', 'totara_contentmarketplace');
            $singleactivitylabel = get_string('createsingleactivitycourse', 'totara_contentmarketplace');
        } else {
            $multiactivitylabel = get_string('createmultipleactivitiescourse', 'totara_contentmarketplace');
            $singleactivitylabel = get_string('createsingleactivitycourses', 'totara_contentmarketplace');
        }
        $options = [
            self::CREATE_COURSE_MULTI_ACTIVITY => $multiactivitylabel,
            self::CREATE_COURSE_SINGLE_ACTIVITY => $singleactivitylabel,
        ];
        $create = new radios('create', get_string('course_creation', 'contentmarketplace_goone'), $options);
        $create->set_attribute('required', 1);
        $create->add_help_button('course_creation', 'contentmarketplace_goone');
        $settings->add($create);
        $this->model->add_clientaction(new onchange_reload($create));

        if ($this->parameters['totalselected'] == 1 || $this->parameters['create'] == self::CREATE_COURSE_MULTI_ACTIVITY) {

            $suffix = $this->parameters['totalselected'] == 1 ? '_' . $this->parameters['selection'][0] : '';

            $fullname = new text('fullname' . $suffix, get_string('fullnamecourse'), PARAM_TEXT);
            $fullname->set_attribute('required', 1);
            $fullname->set_attribute('size', 300);
            $fullname->add_help_button('fullnamecourse');
            $settings->add($fullname);

            $shortname = new text('shortname' . $suffix, get_string('shortnamecourse'), PARAM_TEXT);
            $shortname->set_attribute('required', 1);
            $shortname->set_attribute('size', 300);
            $shortname->add_help_button('shortnamecourse');
            $settings->add($shortname);

            $category = new select('category' . $suffix, get_string('coursecategory'), $this->parameters['categorylist']);
            $category->set_attribute('required', 1);
            $category->add_help_button('coursecategory');
            $settings->add($category);

            $submitlabel = get_string('createandview', 'totara_contentmarketplace');

        } else {

            foreach ($this->parameters['selection'] as $id) {
                /** @var section $course */
                $course = $this->model->add(new section('course_' . $id, $this->parameters['section_' . $id]));
                $course->set_collapsible(false);

                $fullname = new text('fullname_' . $id, get_string('fullnamecourse'), PARAM_TEXT);
                $fullname->set_attribute('required', 1);
                $fullname->set_attribute('size', 300);
                $fullname->add_help_button('fullnamecourse');
                $course->add($fullname);

                $shortname = new text('shortname_' . $id, get_string('shortnamecourse'), PARAM_TEXT);
                $shortname->set_attribute('required', 1);
                $shortname->set_attribute('size', 300);
                $shortname->add_help_button('shortnamecourse');
                $course->add($shortname);

                $category = new select('category_' . $id, get_string('coursecategory'), $this->parameters['categorylist']);
                $category->set_attribute('required', 1);
                $category->add_help_button('coursecategory');
                $course->add($category);
            }

            $submitlabel = get_string("createcourses", "totara_contentmarketplace", (string)count($this->parameters['selection']));
        }

        $this->model->add(new hidden('mode', PARAM_ALPHAEXT));
        $this->model->add_action_buttons(true, $submitlabel);
    }

    public static function get_form_controller() {
        return new create_course_controller;
    }

    protected function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        if ($data['create'] == self::CREATE_COURSE_MULTI_ACTIVITY && count($data['selection']) > 1) {
            $elementname = 'category';
            if ($data[$elementname] == 0) {
                $errors[$elementname] = get_string('missingcoursecategory', 'totara_contentmarketplace');
            }
        } else {
            foreach ($data['selection'] as $id) {
                $elementname = 'category_' . $id;
                if ($data[$elementname] == 0) {
                    $errors[$elementname] = get_string('missingcoursecategory', 'totara_contentmarketplace');
                }
            }
        }

        if ($data['create'] == self::CREATE_COURSE_MULTI_ACTIVITY && count($data['selection']) > 1) {
            if ($course = self::get_existing_course($data['shortname'])) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        } else {
            $names = [];
            foreach ($data['selection'] as $id) {
                $elementname = 'shortname_' . $id;
                $shortname = $data[$elementname];
                if ($course = self::get_existing_course($shortname)) {
                    $errors[$elementname] = get_string('shortnametaken', '', $course->fullname);
                } elseif (array_key_exists($shortname, $names)) {
                    // Shortname is not unique within form
                    $errors[$elementname] = get_string(
                        'shortnamenotuniquewithinform',
                        'totara_contentmarketplace',
                        $names[$shortname]
                    );
                } else {
                    $names[$shortname] = $data['fullname_' . $id];
                }
            }
        }

        return $errors;
    }

    public static function get_existing_course($shortname) {
        global $DB;
        return $DB->get_record('course', array('shortname' => $shortname), '*', IGNORE_MULTIPLE);
    }

}
