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

defined('MOODLE_INTERNAL') || die();

class signup extends \moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $session = $this->_customdata['session'];
        $facetoface = $this->_customdata['facetoface'];
        $showdiscountcode = $this->_customdata['showdiscountcode'];
        $approvaltype = $facetoface->approvaltype;
        $approvaladmins = $facetoface->approvaladmins;

        $managerids = array();
        $manager_title = '';
        // Get default data, user's manager/s who was/were assigned through job assignment/s, if exists.
        if (!empty($session->managerids)) {
            $managers = array();
            $managerids = $session->managerids;
            foreach ($managerids as $managerid) {
                $manager = \core_user::get_user($managerid);
                $managers[] = fullname($manager);
            }
            $manager_title = implode(', ', $managers);
        }
        // Possibility of new data, learner might remove the existing manager/s and assign a new manager itself.
        $managerid = 0;
        if (!empty($this->_customdata['managerid'])) {
            $managerid = $this->_customdata['managerid'];
            $manager = \core_user::get_user($managerid);
            $manager_title = fullname($manager);
        }

        $mform->addElement('hidden', 's', $session->id);
        $mform->setType('s', PARAM_INT);
        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_BOOL);

        $mform->addElement('static', 'approvalerrors');
        // Do nothing if approval is set to none or role.
        if ($approvaltype == APPROVAL_SELF) {
            global $PAGE;

            $url = new \moodle_url('/mod/facetoface/signup_tsandcs.php', array('s' => $session->id));
            $tandcurl = \html_writer::link($url, get_string('approvalterms', 'mod_facetoface'), array("class"=>"tsandcs ajax-action"));

            $PAGE->requires->strings_for_js(array('approvalterms', 'close'), 'mod_facetoface');
            $PAGE->requires->yui_module('moodle-mod_facetoface-signupform', 'M.mod_facetoface.signupform.init');

            $mform->addElement('checkbox', 'authorisation', get_string('selfauthorisation', 'mod_facetoface'),
                               get_string('selfauthorisationdesc', 'mod_facetoface', $tandcurl));
            $mform->addRule('authorisation', get_string('required'), 'required', null, 'client', true);
            $mform->addHelpButton('authorisation', 'selfauthorisation', 'facetoface');
        } else if ($approvaltype == APPROVAL_MANAGER) {
            $mform->addElement('hidden', 'managerid');
            $mform->setType('managerid', PARAM_INT);
            $mform->setDefault('managerid', $managerid);

            $select = get_config(null, 'facetoface_managerselect');

            $manager_class = '';
            if ($managerid || !empty($managerids)) {
                $manager_class = 'nonempty';
            }

            if ($select) {
                $mform->addElement(
                    'static',
                    'managerselector',
                    get_string('manager', 'totara_job'),
                    \html_writer::tag('span', format_string($manager_title), array('class' => $manager_class, 'id' => 'managertitle'))
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
        } else if ($approvaltype == APPROVAL_ADMIN) {
            $mform->addElement('hidden', 'managerid');
            $mform->setType('managerid', PARAM_INT);
            $mform->setDefault('managerid', $managerid);

            $manager_class = '';
            if ($managerid || !empty($managerids)) {
                $manager_class = 'nonempty';
            }

            $select = get_config(null, 'facetoface_managerselect');
            if ($select) {
                $mform->addElement(
                    'static',
                    'managerselector',
                    get_string('manager', 'totara_job'),
                    \html_writer::tag('span', format_string($manager_title), array('class' => $manager_class, 'id' => 'managertitle'))
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

        if ($showdiscountcode) {
            $mform->addElement('text', 'discountcode', get_string('discountcode', 'facetoface'), 'size="6"');
            $mform->addHelpButton('discountcode', 'discountcodelearner', 'facetoface');
        } else {
            $mform->addElement('hidden', 'discountcode', '');
        }
        $mform->setType('discountcode', PARAM_TEXT);

        $signup = new \stdClass();
        $signup->id = 0;
        customfield_definition($mform, $signup, 'facetofacesignup', 0, 'facetoface_signup');
        // To avoid crashing the form check if 'customfields' element exists, it may happened when all custom fields removed.
        if ($mform->elementExists('customfields')) {
            $mform->removeElement('customfields');
        }

        if (facetoface_is_notification_active(MDL_F2F_CONDITION_BOOKING_CONFIRMATION, $facetoface, true)) {
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

        self::add_jobassignment_selection_formelem($mform, $facetoface->id, $session->id);

        if ($this->_customdata['waitlisteveryone']) {
            $mform->addElement(
                'static',
                'youwillbeaddedtothewaitinglist',
                get_string('youwillbeaddedtothewaitinglist', 'facetoface'),
                ''
            );
        }

        if ($approvaltype == APPROVAL_NONE) {
            $signupstr = 'signup';
        } else if ($approvaltype == APPROVAL_SELF) {
            $signupstr = 'signupandaccept';
        } else {
            $signupstr = 'signupandrequest';
        }
        $this->add_action_buttons(true, get_string($signupstr, 'facetoface'));
    }

    public static function add_jobassignment_selection_formelem ($mform, $f2fid, $sessionid) {
        global $DB, $USER;

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
        $session = $this->_customdata['session'];
        $approvaltype = $this->_customdata['facetoface']->approvaltype;

        // Manager validation if approval type requires it.
        if ($approvaltype == APPROVAL_MANAGER || $approvaltype == APPROVAL_ADMIN) {
            $manager = isset($data['managerid']) ? $data['managerid'] : null;
            $managers = isset($session->managerids) ? $session->managerids : null;
            if (empty($manager) && empty($managers)) {
                $select = get_config(null, 'facetoface_managerselect');
                if ($select) {
                    $errors['managerselector'] = get_string('error:missingselectedmanager', 'mod_facetoface');
                } else if (empty($managers)) {
                    $errors['managername'] = get_string('error:missingrequiredmanager', 'mod_facetoface');
                }
            }
        } else if ($approvaltype == APPROVAL_ROLE) {
            if (!$session->trainerroles || !$session->trainers) {
                $errors['approvalerrors'] = get_string('error:missingrequiredrole', 'facetoface');
            }
        }

        // Ensure user doesn't select themselves (by hacking the form).
        if (isset($data['managerid']) and $data['managerid'] == $USER->id) {
            $errors['managerselector'] = get_string('error');
        }

        return $errors;
    }
}
