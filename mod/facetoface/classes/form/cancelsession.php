<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Keelin Devenney <keelin@learningpool.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

use mod_facetoface\seminar_event;

defined('MOODLE_INTERNAL') || die();

class cancelsession extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        /**
         * @var seminar_event $seminarevent
         */
        $seminarevent = $this->_customdata['seminarevent'];

        $mform->addElement('hidden', 's', $seminarevent->get_id());
        $mform->setType('s', PARAM_INT);
        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_BOOL);

        $session = $seminarevent->to_record();
        customfield_load_data($session, 'facetofacesessioncancel', 'facetoface_sessioncancel');
        customfield_definition($mform, $session, 'facetofacesessioncancel', 0, 'facetoface_sessioncancel');

        $mform->addElement('html', get_string('cancelsessionconfirm', 'facetoface')); // Instructions.

        // We don't use add_action_buttons here because we want to set the cancel button label to No.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('yes'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton', get_string('no'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
