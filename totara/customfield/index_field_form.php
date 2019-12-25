<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_customfield
 */

require_once($CFG->dirroot.'/lib/formslib.php');

class field_form extends moodleform {

    var $field;

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $datasent = $this->_customdata;

        $prefix      = $datasent['prefix'];
        $class       = $datasent['class'];
        $typeid      = $datasent['typeid'];
        $tableprefix = $datasent['tableprefix'];
        require_once($CFG->dirroot.'/totara/customfield/field/'.$datasent['datatype'].'/define.class.php');
        $newfield = 'customfield_define_'.$datasent['datatype'];
        $this->field = new $newfield();

        $strrequired = get_string('customfieldrequired', 'totara_customfield');

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'editfield');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'datatype', $datasent['datatype']);
        $mform->setType('datatype', PARAM_ALPHA);
        $mform->addElement('hidden', 'prefix', $datasent['prefix']);
        $mform->setType('prefix', PARAM_ALPHA);
        $mform->addElement('hidden', 'class', $datasent['class']);
        $mform->setType('class', PARAM_ALPHA);
        $mform->addElement('hidden', 'typeid', $datasent['typeid']);
        $mform->setType('typeid', PARAM_INT);
        $mform->addElement('hidden', 'tableprefix', $datasent['tableprefix']);
        $mform->setType('tableprefix', PARAM_ALPHANUMEXT);

        $this->field->define_form($mform, $typeid, $tableprefix);

        // Add additional fields.
        $additionalfields = $datasent['additionalelements'];
        foreach ($additionalfields as $additionalfield) {
            $mform->addElement($additionalfield['element'], $additionalfield['name'], $additionalfield['label']);
            $mform->setType($additionalfield['name'], $additionalfield['type']);
            $mform->setDefault($additionalfield['name'], $additionalfield['defaultvalue']);
        }

        $this->add_action_buttons(true);
    }


/// alter definition based on existing or submitted data
    function definition_after_data () {
        $mform =& $this->_form;
        $this->field->define_after_data($mform);
    }


/// perform some moodle validation
    function validation($data, $files) {

        $errors = $this->field->define_validate($data, $files, $data['typeid'], $data['tableprefix']);

        $hook = new \totara_customfield\hook\field_form_validation($data, $errors);
        $hook->execute();

        return $errors;
    }

    // Double-check that filepickers and URL fields have unique set to off.
    function set_data($field) {
        $this->field->define_load_preprocess($field);
        if (($field->datatype == 'file' || $field->datatype == 'url') && $field->forceunique == 1) {
            $field->forceunique = 0;
        }
        // If the field is locked then it cannot be required and vice versa.
        $values = $this->_form->getSubmitValues();
        if (!empty($values)) {
            if (isset($values['locked']) && !isset($values['required'])) {
                $field->required = 0;
            } else if (isset($values['required']) && !isset($values['locked'])){
                $field->locked = 0;
            }
        }
        parent::set_data($field);

        $hook = new \totara_customfield\hook\field_form_set_data($this, $this->_customdata);
        $hook->execute();
    }
}
