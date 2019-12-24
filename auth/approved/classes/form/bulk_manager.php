<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_approved
 */

namespace auth_approved\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

/**
 * Bulk manager change.
 */
final class bulk_manager extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $currentdata = $this->_customdata['currentdata'];
        $parameters = $this->_customdata['parameters'];

        $bulkcount = count($parameters['requestids']);

        $mform->addElement('hidden', 'bulkaction');
        $mform->setType('bulkaction', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'bulktime');
        $mform->setType('bulktime', PARAM_INT);

        $mform->addElement('static', 'managerselector',
            get_string('bulkactionmanagerselect', 'auth_approved', $bulkcount),
            \html_writer::tag('span', '', array('class' => '', 'id' => 'managertitle')) .
            \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('choosemanager', 'totara_job'), 'id' => 'show-manager-dialog'))
        );

        $mform->addElement('hidden', 'managerjaid');
        $mform->setType('managerjaid', PARAM_INT);

        $this->add_action_buttons(true, get_string('bulkactionmanager', 'auth_approved'));

        $this->set_data($currentdata);
    }

    public function definition_after_data() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/job/lib.php");
        $mform = $this->_form;

        $managerjaid = $mform->getElementValue('managerjaid');
        if ($managerjaid) {
            $managerja = \totara_job\job_assignment::get_with_id($managerjaid);
            // Get the fields required to display the name of a user.
            $usernamefields = get_all_user_name_fields(true);
            $manager = $DB->get_record('user', array('id' => $managerja->userid), $usernamefields);
            // Get the manager name.
            $managername = totara_job_display_user_job($manager, $managerja, true);

            /** @var \MoodleQuickForm_static $managerselector */
            $managerselector = $mform->getElement('managerselector');
            $managerselector->setText(
                \html_writer::tag('span', $managername, array('class' => 'nonempty', 'id' => 'managertitle')) .
                \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('choosemanager', 'totara_job'), 'id' => 'show-manager-dialog'))
            );
        }
    }
}
