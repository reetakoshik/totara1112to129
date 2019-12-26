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

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->dirroot}/lib/formslib.php");
require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

class editroom extends \moodleform {

    /**
     * Definition of the room form
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        /**
         * @var \mod_facetoface\room $room
         */
        $room = $this->_customdata['room'];
        $seminar = empty($this->_customdata['seminar']) ? null : $this->_customdata['seminar'];
        $event = empty($this->_customdata['event']) ? null : $this->_customdata['event'];
        $editoroptions = $this->_customdata['editoroptions'];

        $modconfig = has_capability('totara/core:modconfig', \context_system::instance());

        $mform->addElement('hidden', 'id', $room->get_id());
        $mform->setType('id', PARAM_INT);

        if (!empty($seminar)) {
            $mform->addElement('hidden', 'f', $seminar->get_id());
            $mform->setType('f', PARAM_INT);
        }
        if (!empty($event)) {
            $mform->addElement('hidden', 's', $event->get_id());
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

        customfield_definition($mform, (object)['id' => $room->get_id()], 'facetofaceroom', 0, 'facetoface_room');

        if ($modconfig and !empty($seminar) and $room->get_custom()) {
            $mform->addElement('advcheckbox', 'notcustom', get_string('publishreuse', 'mod_facetoface'));
            // Disable if does not seem to work in dialog forms, back luck.
        }

        if (!empty($room) && $room->exists()) {
            $mform->addElement('header', 'versions', get_string('versioncontrol', 'mod_facetoface'));

            $created = new \StdClass();
            $created->user = get_string('unknownuser');
            $usercreated = $room->get_usercreated();
            if (!empty($usercreated)) {
                $created->user = \html_writer::link(
                    new \moodle_url('/user/view.php', array('id' => $usercreated)),
                    fullname($DB->get_record('user', ['id' => $usercreated], '*', MUST_EXIST))
                );
            }
            $created->time = empty($room->get_timecreated()) ? '' : userdate($room->get_timecreated());
            $mform->addElement(
                    'static',
                    'versioncreated',
                    get_string('created', 'mod_facetoface'),
                    get_string('timestampbyuser', 'mod_facetoface', $created)
            );

            if (!empty($room->get_timemodified()) and $room->get_timemodified() != $room->get_timecreated()) {
                $modified = new \stdClass();
                $modified->user = get_string('unknownuser');
                $usermodified = $room->get_usermodified();
                if (!empty($usermodified)) {
                    $modified->user = \html_writer::link(
                        new \moodle_url('/user/view.php', array('id' => $usermodified)),
                        fullname($DB->get_record('user', ['id' => $usermodified], '*', MUST_EXIST))
                    );
                }
                $modified->time = empty($room->get_timemodified()) ? '' : userdate($room->get_timemodified());
                $mform->addElement(
                        'static',
                        'versionmodified',
                        get_string('modified'),
                        get_string('timestampbyuser', 'mod_facetoface', $modified)
                );
            }
        }

        if (empty($seminar)) {
            $label = null;
            if (!$room->get_id()) {
                $label = get_string('addroom', 'facetoface');
            }
            $this->add_action_buttons(true, $label);
        }

        $formdata = (object)[
            'id' => $room->get_id(),
            'name' => $room->get_name(),
            'roomcapacity' => $room->get_capacity(),
            'allowconflicts' => $room->get_allowconflicts(),
            'description_editor' => ['text' => $room->get_description()],
            'notcustom' => $room->get_custom() ? 0 : 1,
            'description' => $room->get_description(),
            'descriptionformat' => FORMAT_HTML,
        ];

        customfield_load_data($formdata, 'facetofaceroom', 'facetoface_room');
        $formdata = file_prepare_standard_editor($formdata, 'description', $editoroptions, $editoroptions['context'],
            'mod_facetoface', 'room', $room->get_id());

        $this->set_data($formdata);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $room = $this->_customdata['room'];

        if ((int)$data['roomcapacity'] <= 0) {
            // Client side JS validation does not work much in the hacky dialog forms - do it on server side!
            $errors['roomcapacity'] = get_string('required');
        }

        if ($room->get_id() and $room->get_allowconflicts() and $data['allowconflicts'] == 0) {
            // Make sure there are no existing conflicts before we switch the setting!

            if ($room->has_conflicts()) {
                $errors['allowconflicts'] = get_string('error:roomconflicts', 'mod_facetoface');
            }
        }

        return $errors;
    }
}
