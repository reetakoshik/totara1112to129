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

class repository_opensesame_form_register_new extends moodleform {
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('text', 'tenantname', get_string('tenantname', 'repository_opensesame'), array('size' => '255'));
        $mform->setType('tenantname', PARAM_NOTAGS);
        $mform->addRule('tenantname', get_string('required'), 'required', null, 'client');
        $mform->setDefault('tenantname', $CFG->orgname);

        $mform->addElement('hidden', 'tenanttype');
        $mform->setType('tenanttype', PARAM_RAW);
        $mform->addElement('hidden', 'tenantdemosecret');
        $mform->setType('tenantdemosecret', PARAM_RAW);

        if (defined('REPOSITORY_OPENSESAME_DEMO_SECRET')) {
            $mform->setDefault('tenanttype', 'Demo');
            $mform->setDefault('tenantdemosecret', REPOSITORY_OPENSESAME_DEMO_SECRET);
        } else {
            $mform->setDefault('tenanttype', 'Prod');
            $mform->setDefault('tenantdemosecret', '');
        }

        $mform->addElement('hidden', 'action', 'new');
        $mform->setType('action', PARAM_ALPHA);

        $this->add_action_buttons(true);
    }
}
