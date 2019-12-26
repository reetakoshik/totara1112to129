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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class attendee_note extends \moodleform {

    public function definition() {

        $mform = & $this->_form;
        $attendeenote = $this->_customdata['attendeenote'];
        $userfullname = fullname($attendeenote);

        $mform->addElement('header', 'usernoteheader', get_string('usernoteheading', 'facetoface', $userfullname));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $attendeenote->submissionid);

        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'return', $this->_customdata['return']);
        $mform->setType('return', PARAM_ALPHA);

        $signup = new \stdClass();
        $signup->id = $attendeenote->submissionid;
        customfield_definition($mform, $signup, 'facetofacesignup', 0, 'facetoface_signup');
        $mform->removeElement('customfields');

        $submittitle = get_string('savenote', 'facetoface');
        $this->add_action_buttons(true, $submittitle);
    }
}