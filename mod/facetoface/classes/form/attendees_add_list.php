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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

/**
 * Add users to facetoface session via input
 */
class attendees_add_list extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('header', 'addattendees', get_string('addattendees', 'facetoface'));

        $options = array(
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
            'username' => get_string('username')
        );
        $mform->addElement('select', 'idfield', get_string('useridentifier', 'facetoface'), $options);
        $mform->addelement('static', 'useraddcomment', get_string('userstoadd', 'facetoface'), get_string('userstoaddcomment', 'facetoface'));
        $mform->addElement('textarea', 'csvinput', '');

        $mform->addElement('advcheckbox', 'ignoreconflicts', get_string('allowscheduleconflicts', 'facetoface'));
        $mform->setType('ignoreconflicts', PARAM_BOOL);

        $this->add_action_buttons(true, get_string('continue'));
    }
}