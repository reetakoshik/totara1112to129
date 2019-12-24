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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_certification
 */

require_once($CFG->libdir . "/formslib.php");

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

class prog_edit_completion_history_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;

        $id = $this->_customdata['id'];
        $userid = $this->_customdata['userid'];
        $chid = $this->_customdata['chid'];

        $mform->addElement('date_time_selector', 'timecompleted', get_string('completiontimecompleted', 'totara_program'));

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'chid', $chid);
        $mform->setType('chid', PARAM_INT);

        $mform->addElement('static', 'datewarning', '', get_string('completionchangedatewarning', 'totara_program'));

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
