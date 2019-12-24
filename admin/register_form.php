<?php
/*
 * This file is part of Totara Learn
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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

class register_form extends moodleform {
    function definition () {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('totararegistration', 'totara_core'));

        $choices = array(
            '' => get_string('choosedots'),
            'demo' => get_string('sitetypedemo', 'totara_core'),
            'development' => get_string('sitetypedevelopment', 'totara_core'),
            'qa' => get_string('sitetypeqa', 'totara_core'),
            'production' => get_string('sitetypeproduction', 'totara_core'),
        );
        $mform->addElement('select', 'sitetype', get_string('sitetype', 'totara_core'), $choices);
        $mform->addHelpButton('sitetype', 'sitetype', 'totara_core');
        if (isset($CFG->config_php_settings['sitetype'])) {
            $mform->hardFreeze('sitetype');
            $mform->setConstant('sitetype', $CFG->sitetype);
        } else {
            $mform->addRule('sitetype', get_string('required'), 'required', null, 'client');
        }

        $mform->addElement('text', 'registrationcode', get_string('registrationcode', 'totara_core'));
        $mform->addHelpButton('registrationcode', 'registrationcode', 'totara_core');
        $mform->setType('registrationcode', PARAM_RAW);
        if (isset($CFG->config_php_settings['registrationcode'])) {
            $mform->hardFreeze('registrationcode');
            $mform->setConstant('registrationcode', $CFG->registrationcode);
        }

        if (empty($CFG->registered)) {
            $datasent = get_string('never');
        } else {
            $datasent = userdate($CFG->registered, get_string('strftimedatetimelong', 'langconfig'));
        }
        $mform->addElement('static', 'lastsent', get_string('totararegistrationlastsent', 'totara_core'), $datasent);

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_ALPHA);

        $mform->addElement('header', 'registrationinfo', get_string('registrationinformation', 'admin'));
        $data = get_registration_data();
        foreach ($data as $key => $value) {
            if ($key === 'sitetype' or $key === 'registrationcode') {
                continue;
            }
            $module = (strpos($key, 'totara') === 0 || ($key == 'debugstatus') || ($key == 'edition')) ? 'totara_core' : 'admin';
            $mform->addElement('static', $key, get_string($key, $module));
        }

        $mform->setExpanded('settingsheader', true, true);
        $mform->setExpanded('registrationinfo', false, true);

        // Show the save button only if there is something to change!
        if (!isset($CFG->config_php_settings['sitetype']) or !isset($CFG->config_php_settings['registrationcode'])) {
            $this->add_action_buttons(false, get_string('save', 'admin'));
        }
    }

    function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        if (isset($CFG->config_php_settings['sitetype'])) {
            $sitetype = $CFG->sitetype;
        } else {
            $sitetype = $data['sitetype'];
        }
        if (!$sitetype) {
            $errors['sitetype'] = get_string('required');
        }

        if (isset($CFG->config_php_settings['registrationcode'])) {
            $registrationcode = $CFG->registrationcode;
        } else {
            $registrationcode = trim($data['registrationcode']);
        }
        if ($registrationcode !== '') {
            if (!is_valid_registration_code_format($registrationcode)) {
                $errors['registrationcode'] = get_string('registrationcodeinvalid', 'totara_core');
            }
        }
        if ($sitetype === 'production' and $registrationcode === '') {
            $errors['registrationcode'] = get_string('required');
        }

        return $errors;
    }
}

