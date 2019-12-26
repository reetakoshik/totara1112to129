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
 * @author David Curry <david.curry@totaralms.com>
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

use mod_facetoface\seminar_event;
use mod_facetoface\seminar;
use mod_facetoface\signup\state\requested;
use mod_facetoface\signup\state\waitlisted;
use mod_facetoface\signup_helper;

defined('MOODLE_INTERNAL') || die();

class signup extends \moodleform {
    function definition() {
        global $USER;
        $mform =& $this->_form;
        /**
         * @var \mod_facetoface\signup $signup
         */
        $signup = $this->_customdata['signup'];
        $seminarevent = $signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();
        $managerids   = \totara_job\job_assignment::get_all_manager_userids($USER->id);

        $approvaltype = $seminar->get_approvaltype();
        $approvaladmins = $seminar->get_approvaladmins();

        $manager_title = '';
        // Get default data, user's manager/s who was/were assigned through job assignment/s, if exists.
        if (!empty($managerids)) {
            $managers = array();
            foreach ($managerids as $managerid) {
                $manager = \core_user::get_user($managerid);
                $managers[] = fullname($manager);
            }
            $manager_title = implode(', ', $managers);
        }

        $mform->addElement('hidden', 's', $seminarevent->get_id());
        $mform->setType('s', PARAM_INT);
        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_BOOL);

        $mform->addElement('static', 'approvalerrors');
        // Do nothing if approval is set to none or role.
        if ($approvaltype == \mod_facetoface\seminar::APPROVAL_SELF) {
            global $PAGE;

            $url = new \moodle_url('/mod/facetoface/attendees/ajax/signup_tsandcs.php', array('s' => $seminarevent->get_id()));
            $tandcurl = \html_writer::link($url, get_string('approvalterms', 'mod_facetoface'), array("class"=>"tsandcs ajax-action"));

            $PAGE->requires->strings_for_js(array('approvalterms', 'close'), 'mod_facetoface');
            $PAGE->requires->yui_module('moodle-mod_facetoface-signupform', 'M.mod_facetoface.signupform.init');

            $mform->addElement('checkbox', 'authorisation', get_string('selfauthorisation', 'mod_facetoface'),
                               get_string('selfauthorisationdesc', 'mod_facetoface', $tandcurl));
            $mform->addRule('authorisation', get_string('required'), 'required', null, 'client', true);
            $mform->addHelpButton('authorisation', 'selfauthorisation', 'facetoface');
        } else if ($approvaltype == \mod_facetoface\seminar::APPROVAL_MANAGER) {
            $mform->addElement('hidden', 'managerid');
            $mform->setType('managerid', PARAM_INT);

            $select = get_config(null, 'facetoface_managerselect');

            if ($select) {
                $mform->addElement(
                    'static',
                    'managerselector',
                    get_string('manager', 'totara_job'),
                    \html_writer::tag('span', format_string($manager_title), ['id' => 'managertitle'])
                    . \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('choosemanager', 'totara_job'), 'id' => 'show-manager-dialog'))
                );
                $mform->addHelpButton('managerselector', 'choosemanager', 'totara_job');
                if (!empty($managerids)) {
                    $mform->addElement('static', 'managerjadesc', '', get_string('alljamanagersdesc', 'mod_facetoface'));
                }
            } else {
                // Display the average manager approval string.
                $mform->addElement('static', 'managername', get_string('managername', 'mod_facetoface'), $manager_title);
                $mform->setType('managername', PARAM_TEXT);
                $mform->addHelpButton('managername', 'managername', 'facetoface');
            }
        } else if ($approvaltype == \mod_facetoface\seminar::APPROVAL_ADMIN) {
            $mform->addElement('hidden', 'managerid');
            $mform->setType('managerid', PARAM_INT);

            $select = get_config(null, 'facetoface_managerselect');
            if ($select) {
                $mform->addElement(
                    'static',
                    'managerselector',
                    get_string('manager', 'totara_job'),
                    \html_writer::tag('span', format_string($manager_title), ['id' => 'managertitle'])
                    . \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('choosemanager', 'totara_job'), 'id' => 'show-manager-dialog'))
                );

                $mform->addHelpButton('managerselector', 'choosemanager', 'totara_job');

                if (!empty($managerids)) {
                    $mform->addElement('static', 'managerjadesc', '', get_string('alljamanagersdesc', 'mod_facetoface'));
                }
            } else {
                // Display the average manager&admin approval string.
                $mform->addElement('static', 'managername', get_string('managername', 'mod_facetoface'), $manager_title);
                $mform->setType('managername', PARAM_TEXT);
                $mform->addHelpButton('managername', 'managername', 'facetoface');
            }

            // Display a list of approval administrators.
            $approvallist = \html_writer::start_tag('ul', array('class' => 'approvallist'));

            // System approvers.
            $sysapps = get_users_from_config(get_config(null, 'facetoface_adminapprovers'), 'mod/facetoface:approveanyrequest');
            foreach ($sysapps as $approver) {
                if (!empty($approver)) {
                    $approvallist .= \html_writer::tag('li', fullname($approver));
                }
            }

            // Activity approvers.
            $actapps = explode(',', $approvaladmins);
            foreach ($actapps as $approverid) {
                if (!empty($approverid)) {
                     $approver = \core_user::get_user($approverid);
                     $approvallist .= \html_writer::tag('li', fullname($approver));
                }
            }
            $approvallist .= \html_writer::end_tag('ul');

            $mform->addElement('static', 'approvalusers', get_string('approvalusers', 'mod_facetoface'), $approvallist);
            $mform->setType('approvalusers', PARAM_TEXT);
            $mform->addHelpButton('approvalusers', 'approvalusers', 'facetoface');
        }

        if ($seminarevent->is_discountcost()) {
            $mform->addElement('text', 'discountcode', get_string('discountcode', 'facetoface'), 'size="6"');
            $mform->addHelpButton('discountcode', 'discountcodelearner', 'facetoface');
        } else {
            $mform->addElement('hidden', 'discountcode', '');
        }
        $mform->setType('discountcode', PARAM_TEXT);

        $signuprec = new \stdClass();
        $signuprec->id = 0;
        customfield_definition($mform, $signuprec, 'facetofacesignup', 0, 'facetoface_signup');
        // To avoid crashing the form check if 'customfields' element exists, it may happened when all custom fields removed.
        if ($mform->elementExists('customfields')) {
            $mform->removeElement('customfields');
        }

        if (facetoface_is_notification_active(MDL_F2F_CONDITION_BOOKING_CONFIRMATION, $seminar->get_id(), true)) {
            $options = array(MDL_F2F_BOTH => get_string('notificationboth', 'facetoface'),
                             MDL_F2F_TEXT => get_string('notificationemail', 'facetoface'),
                             MDL_F2F_NONE => get_string('notificationnone', 'facetoface'),
                             );
            $mform->addElement('select', 'notificationtype', get_string('notificationtype', 'facetoface'), $options);
            $mform->addHelpButton('notificationtype', 'notificationtype', 'facetoface');
            $mform->addRule('notificationtype', null, 'required', null, 'client');
            $mform->setDefault('notificationtype', MDL_F2F_BOTH);
        } else {
            $mform->addElement('hidden', 'notificationtype', MDL_F2F_NONE);
        }
        $mform->setType('notificationtype', PARAM_INT);

        self::add_jobassignment_selector($mform, $seminar);

        if (signup_helper::expected_signup_state($signup) instanceof waitlisted
            && $seminarevent->is_waitlisteveryone()) {
            $mform->addElement('static', 'youwillbeaddedtothewaitinglist', '',
                get_string('youwillbeaddedtothewaitinglist', 'facetoface'));
        }
        // Even if approval is required, it makes sense to inform user that most likely they will end-up in waiting list.
        if ($seminarevent->get_free_capacity() < 1) {
            $mform->addElement('static', 'userwillbewaitlisted','',
                get_string('userwillbewaitlisted', 'facetoface')
            );
        }

        $signupstr = signup_helper::expected_signup_state($signup)->get_action_label();
        $this->add_action_buttons(true, $signupstr);
    }

    /**
     * Add form element for job assignment seleciton
     * @param $mform
     * @param seminar $seminar
     */
    public static function add_jobassignment_selector ($mform, seminar $seminar) {
        global $USER;

        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        if (empty($selectjobassignmentonsignupglobal)) {
            return;
        }

        if (empty($seminar->get_selectjobassignmentonsignup())) {
            return;
        }

        $controlname = 'selectedjobassignment_'.$seminar->get_id();

        $jobassignments = \totara_job\job_assignment::get_all($USER->id, $seminar->is_manager_required());

        if (count($jobassignments) > 1) {
            $posselectelement = $mform->addElement('select', $controlname, get_string('selectjobassignment', 'mod_facetoface'));
            $mform->addHelpButton($controlname, 'selectedjobassignment', 'mod_facetoface');
            $mform->setType($controlname, PARAM_INT);

            foreach ($jobassignments as $jobassignment) {
                $label = \position::job_position_label($jobassignment);
                $posselectelement->addOption($label, $jobassignment->id);
            }
        }
    }

    /**
     * (deprecated) Add form element for job assignment seleciton
     * @param $mform
     * @param $f2fid
     * @param $session
     * @throws \coding_exception
     * @throws \dml_exception
     * @deprecated since Totara 12.0
     */
    public static function add_jobassignment_selection_formelem ($mform, $f2fid, $session = null) {
        global $DB, $USER;

        debugging('Argument add_jobassignment_selection_formelem is deprecated. Use add_jobassignment_selector');

        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        if (empty($selectjobassignmentonsignupglobal)) {
            return;
        }

        $facetoface = $DB->get_record('facetoface', array('id' => $f2fid));

        if (empty($facetoface->selectjobassignmentonsignup)) {
            return;
        }

        $controlname = 'selectedjobassignment_'.$f2fid;

        $managerrequired = facetoface_manager_needed($facetoface);
        $jobassignments = \totara_job\job_assignment::get_all($USER->id, $managerrequired);

        if (count($jobassignments) > 1) {
            $posselectelement = $mform->addElement('select', $controlname, get_string('selectjobassignment', 'mod_facetoface'));
            $mform->addHelpButton($controlname, 'selectedjobassignment', 'mod_facetoface');
            $mform->setType($controlname, PARAM_INT);

            foreach ($jobassignments as $jobassignment) {
                $label = \position::job_position_label($jobassignment);
                $posselectelement->addOption($label, $jobassignment->id);
            }
        }
    }

    function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);
        /**
         * @var \mod_facetoface\signup $signup
         */
        $signup = $this->_customdata['signup'];
        $seminarevent = $signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();

        // Manager validation if approval type requires it.
        if ($seminar->is_manager_required()) {
            $manager = isset($data['managerid']) ? $data['managerid'] : null;
            if (empty($manager) && !\totara_job\job_assignment::has_manager($USER->id)) {
                $errors['managername'] = get_string('error:missingrequiredmanager', 'mod_facetoface');
            }
            $select = get_config(null, 'facetoface_managerselect');
            $managerids   = \totara_job\job_assignment::get_all_manager_userids($USER->id);
            if (empty($manager) && $select && empty($managerids)) {
                $errors['managerselector'] = get_string('error:missingselectedmanager', 'mod_facetoface');
            }
        }

        // Ensure user doesn't select themselves (by hacking the form).
        if (isset($data['managerid']) and $data['managerid'] == $USER->id) {
            $errors['managerselector'] = get_string('error');
        }

        return $errors;
    }
}
