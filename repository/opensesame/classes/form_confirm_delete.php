<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

class repository_opensesame_form_confirm_delete extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $config = $this->_customdata;

        $warning = markdown_to_html(get_string('unregisterconfirm', 'repository_opensesame'));

        $mform->addElement('header', 'header', get_string('registration', 'repository_opensesame'));

        $mform->addElement('static', 'sttenantname', get_string('tenantname', 'repository_opensesame'), s($config->tenantname));
        $mform->addElement('static', 'sttenanttype', get_string('tenanttype', 'repository_opensesame'), s($config->tenanttype));
        $mform->addElement('static', 'sttenantid', get_string('tenantid', 'repository_opensesame'), s($config->tenantid));

        $mform->addElement('header', 'header', get_string('unregister', 'repository_opensesame'));

        $mform->addElement('static', 'confirmdelete', '', $warning);

        $mform->addElement('text', 'tenantid', get_string('tenantid', 'repository_opensesame'), array('size' => '100'));
        $mform->setType('tenantid', PARAM_RAW);
        $mform->addRule('tenantid', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'action', 'delete');
        $mform->setType('action', PARAM_ALPHA);

        $this->add_action_buttons(true, get_string('unregister', 'repository_opensesame'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['tenantid'] !== $this->_customdata->tenantid) {
            $errors['tenantid'] = get_string('error');
        }
        return $errors;
    }
}
