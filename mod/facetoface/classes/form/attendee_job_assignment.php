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
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @package totara
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class attendee_job_assignment extends \moodleform {

    public function definition() {
        $mform = & $this->_form;
        $jobassignments = $this->_customdata['jobassignments'];
        $fullname = $this->_customdata['fullname'];
        $selectedjaid = $this->_customdata['selectedjaid'];
        $userid = $this->_customdata['userid'];
        $sessionid = $this->_customdata['sessionid'];

        $mform->addElement('header', 'userjobassignmentheader', get_string('userjobassignmentheading', 'facetoface', $fullname));

        if (count($jobassignments) == 0) {
            $mform->addElement('static', null, null, get_string('nojobassignment', 'mod_facetoface'));
            $mform->createElement('cancel');
        } else {
            $mform->addElement('hidden', 'id', $userid);
            $mform->setType('id', PARAM_INT);

            $mform->addElement('hidden', 's', $sessionid);
            $mform->setType('s', PARAM_INT);

            // Support for add attendees lists.
            if (!empty($this->_customdata['listid'])) {
                $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
                $mform->setType('listid', PARAM_INT);
            }

            $mform->addElement('html', \html_writer::tag('p', '&nbsp;', array('id' => 'attendee_note_err', 'class' => 'error')));

            $jobassignselectelement = $mform->addElement('select', 'selectjobassign', get_string('selectjobassignment', 'mod_facetoface'));

            foreach ($jobassignments as $jobassignment) {
                $label = \position::job_position_label($jobassignment);
                $jobassignselectelement->addOption($label, $jobassignment->id);
            }
            $jobassignselectelement->setSelected($selectedjaid);

            $mform->setType('selectjobassign', PARAM_INT);

            $this->add_action_buttons(true, get_string('updatejobassignment', 'facetoface'));
        }
    }
}
