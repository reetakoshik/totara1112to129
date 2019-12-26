<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides {@link tool_policy\form\policydoc} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\form;

use context_system;
use html_writer;
use moodleform;
use core_user;
use tool_policy\api;
use tool_policy\policy_version;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form for editing a policy document version.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class messages extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        global $DB ,$CFG, $USER;
        $mform = $this->_form;
        $policyid = optional_param('policyid', null, PARAM_INT);
        $formdata = $this->_customdata['formdata'];
       
        $mform->addElement('header','general', get_string('general', 'form'));
        
        $mform->addElement('hidden', 'policyid', $policyid);
        $mform->addElement('hidden', 'messageid', $formdata->id);

        $mform->addElement('text', 'subject', get_string('subject', 'tool_policy'), ['maxlength' => 1333]);
        $mform->settype('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

          $mform->addElement('editor', 'message',  get_string("message", "tool_policy"), ['rows' => 7],
            api::policy_summary_field_options());
        // $mform->addElement('textarea', 'message', get_string("message", "tool_policy"), 'wrap="virtual" rows="20" cols="50"');
        $mform->settype('message', PARAM_TEXT);
        $mform->addRule('message', null, 'required', null, 'client');

        
        $statusgrp = [
                $mform->createElement('checkbox', 'sendmanager', '', get_string('sendmanager', 'tool_policy')),
                $mform->createElement('static', 'sendmanagerinfo', '',
                    html_writer::div(get_string('sendmanagerinfo', 'tool_policy'), 'muted text-muted')),
            ];
            $mform->addGroup($statusgrp, null, get_string('sendmanagerlabel', 'tool_policy'), ['<br>'], false);


        $mform->addElement('text', 'managersubject', get_string('managersubject', 'tool_policy'), ['maxlength' => 1333]);
        $mform->settype('managersubject', PARAM_TEXT);
        $mform->addRule('managersubject', null, 'required', null, 'client');
        $mform->addRule('managersubject', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');
       

        $mform->addElement('editor', 'managermessage', get_string("managermessage", "tool_policy"), ['rows' => 7],
            api::policy_summary_field_options());
        // $mform->addElement('textarea', 'managermessage', get_string("managermessage", "tool_policy"), 'wrap="virtual" rows="20" cols="50"');
        $mform->settype('managermessage', PARAM_TEXT);
        $mform->addRule('managermessage', null, 'required', null, 'client');
        $mform->setDefault('managermessage',$formdata->managermessage);

        // Add "Save" button and, optionally, .
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('save', 'tool_policy'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $this->set_data($formdata);
        
    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    // public function validation($data, $files) {
    //     $errors = parent::validation($data, $files);
    //     if (!empty($data['minorchange']) && !empty($data['saveasdraft'])) {
    //         // If minorchange is checked and "save as draft" is pressed - return error.
    //         $errors['minorchange'] = get_string('errorsaveasdraft', 'tool_policy');
    //     }
    //     return $errors;
    // }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    // public function get_data() {
    //     if ($data = parent::get_data()) {
    //         if (!empty($data->saveasdraft)) {
    //             $data->status = policy_version::STATUS_DRAFT;
    //         }
    //     }
    //     return $data;
    // }
}   