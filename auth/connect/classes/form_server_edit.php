<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

class auth_connect_form_server_edit extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $server = $this->_customdata;

        $mform->addElement('static', 'servername', get_string('name'));
        $mform->addElement('static', 'serveridnumber', get_string('idnumber'));
        $mform->addElement('static', 'serverurl', get_string('url'));

        $mform->addElement('textarea', 'servercomment', get_string('comment', 'auth_connect'));
        $mform->setType('servercomment', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();

        $this->set_data($server);
    }
}
