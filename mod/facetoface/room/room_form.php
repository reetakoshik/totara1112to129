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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->dirroot}/lib/formslib.php");
require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php'); // TODO: TL-9425 this hack is unacceptable.

class mod_facetoface_room_form extends moodleform {

    /**
     * Definition of the room form
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $room = $this->_customdata['room'];
        $facetoface = $this->_customdata['facetoface'];
        $session = $this->_customdata['session'];
        $modconfig = has_capability('totara/core:modconfig', context_system::instance());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if ($facetoface) {
            $mform->addElement('hidden', 'f', $facetoface->id);
            $mform->setType('f', PARAM_INT);
        }
        if ($session) {
            $mform->addElement('hidden', 's', $session->id);
            $mform->setType('s', PARAM_INT);
        }

        $mform->addElement('text', 'name', get_string('roomnameedit', 'facetoface'), array('size' => '45'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $roomnamelength = 100;
        $mform->addRule('name', get_string('roomnameedittoolong', 'facetoface', $roomnamelength), 'maxlength', $roomnamelength);

        // This form is loaded as ajax into page that has "capacity" so give it different name to avoid conflicts.
        $mform->addElement('text', 'roomcapacity', get_string('roomcapacity', 'facetoface'));
        $mform->setType('roomcapacity', PARAM_INT);
        $mform->addRule('roomcapacity', null, 'required', null, 'client');
        $mform->addRule('roomcapacity', null, 'numeric', null, 'client');

        $mform->addElement('advcheckbox', 'allowconflicts', get_string('allowroomconflicts', 'mod_facetoface'));
        $mform->addHelpButton('allowconflicts', 'allowroomconflicts', 'mod_facetoface');

        $mform->addElement('editor', 'description_editor', get_string('roomdescriptionedit', 'facetoface'), null, $this->_customdata['editoroptions']);

        customfield_definition($mform, $room, 'facetofaceroom', 0, 'facetoface_room');

        if ($modconfig and $facetoface and $room->custom) {
            $mform->addElement('advcheckbox', 'notcustom', get_string('publishreuse', 'mod_facetoface'));
            // Disable if does not seem to work in dialog forms, back luck.
        }

        if ($room->id) {
            $mform->addElement('header', 'versions', get_string('versioncontrol', 'mod_facetoface'));

            $created = new stdClass();
            $created->user = get_string('unknownuser');
            if (!empty($room->usercreated)) {
                $created->user = html_writer::link(
                    new moodle_url('/user/view.php', array('id' => $room->usercreated)),
                    fullname($DB->get_record('user', array('id' => $room->usercreated)))
                );
            }
            $created->time = empty($room->timecreated) ? '' : userdate($room->timecreated);
            $mform->addElement(
                    'static',
                    'versioncreated',
                    get_string('created', 'mod_facetoface'),
                    get_string('timestampbyuser', 'mod_facetoface', $created)
            );

            if (!empty($room->timemodified) and $room->timemodified != $room->timecreated) {
                $modified = new stdClass();
                $modified->user = get_string('unknownuser');
                if (!empty($room->usermodified)) {
                    $modified->user = html_writer::link(
                        new moodle_url('/user/view.php', array('id' => $room->usermodified)),
                        fullname($DB->get_record('user', array('id' => $room->usermodified)))
                    );
                }
                $modified->time = empty($room->timemodified) ? '' : userdate($room->timemodified);
                $mform->addElement(
                        'static',
                        'versionmodified',
                        get_string('modified'),
                        get_string('timestampbyuser', 'mod_facetoface', $modified)
                );
            }
        }

        if (!$facetoface) {
            $label = null;
            if (!$room->id) {
                $label = get_string('addroom', 'facetoface');
            }
            $this->add_action_buttons(true, $label);
        }

        $this->set_data($room);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $room = $this->_customdata['room'];

        if ((int)$data['roomcapacity'] <= 0) {
            // Client side JS validation does not work much in the hacky dialog forms - do it on server side!
            $errors['roomcapacity'] = get_string('required');
        }

        if ($room->id and $room->allowconflicts == 1 and $data['allowconflicts'] == 0) {
            // Make sure there are no existing conflicts before we switch the setting!

            if (facetoface_room_has_conflicts($room->id)) {
                $errors['allowconflicts'] = get_string('error:roomconflicts', 'mod_facetoface');
            }
        }

        return $errors;
    }
}
