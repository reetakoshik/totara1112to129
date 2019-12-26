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
class policydoc extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        global $DB ,$CFG, $USER;
        $mform = $this->_form;
        $formdata = $this->_customdata['formdata'];
    //print_r($formdata); die();
        
         // die();
        $mform->addElement('header','general', get_string('general', 'form'));

        $options = [];
        $options['en'] = 'English';
        $options['he'] = 'Hebrew';
        $mform->addElement('select', 'primarylang', get_string('primarylanguage', 'tool_policy'), $options);
        
        $parentversion = $DB->get_records_sql("SELECT ev.id, ev.name FROM {tool_policy_versions} ev INNER JOIN {tool_policy} e ON ev.id = e.currentversionid WHERE ev.parentpolicy = '0' ORDER BY ev.id ASC");

       
        $options1 = [];
        $options1[0] = 'Top';
        foreach ($parentversion as $value) {
          $options1[$value->id] = $value->name;
        }
        $mform->addElement('select', 'parentpolicy', get_string('parentpolicy', 'tool_policy'), $options1);

        $mform->addElement('hidden', 'relatedcourse', null, 'id="pcourseids"');
        $mform->settype('relatedcourse', PARAM_RAW);
        $editversionid = 0;
        if(isset($_REQUEST['versionid'])) {
            $editversionid = $_REQUEST['versionid'];
        }
        $mform->addElement('hidden', 'editversionid', null, 'id="editversionid"');
        $mform->settype('editversionid', PARAM_INT);
        $mform->setDefault('editversionid', $editversionid); 
        // $mform->addElement('html', '<input style="display:none;" type="text" name="editversionid" id="editversionid" value="'.$editversionid.'"');
        $mform->addElement('hidden', 'relatedpolicy', null, 'id="policyids"');
        $mform->settype('relatedpolicy', PARAM_RAW);

        $mform->addElement('hidden', 'relatedaudiences', null, 'id="audiencesid"');
        $mform->settype('relatedaudiences', PARAM_RAW);

        $mform->addElement('text', 'name', get_string('policydocname', 'tool_policy'), ['maxlength' => 1333]);
        $mform->settype('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('text', 'docnumber', get_string('documentnumber', 'tool_policy'), ['maxlength' => 1333]);
        $mform->settype('docnumber', PARAM_RAW);
        $mform->addRule('docnumber', null, 'required', null, 'client');
        $mform->addRule('docnumber', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $buttongrp = [
        $mform->createElement('button', 'relatedpolicyid', get_string('relatedpolicy', 'tool_policy'), 'id="create-modal-policy"'),
        $mform->createElement('button', 'relatedcourseid', get_string('relatedcourse', 'tool_policy'), 'id="create-modal"'),
        $mform->createElement('button', 'relatedaudienceid', get_string('relatedaudience', 'tool_policy'), 'id="create-modal-audiences"'),
        ];
        $mform->addGroup($buttongrp, 'buttonar', get_string('relatedresources', 'tool_policy'), array(''), false);


        $mform->addElement('date_selector', 'policyexpdate',get_string('policyexpiary', 'tool_policy'));
        $mform->addElement('hidden', 'audiencexpdate', '1111111111');
        $mform->settype('audiencexpdate', PARAM_INT);
        //$mform->addElement('date_selector', 'audiencexpdate', 'Select Audience Expiary Date', 'style="display:none;"');
        // $mform->addElement('text', 'policyexpdate', get_string('pexpdate', 'tool_policy'),'class="datepicker" data-date-format="mm/dd/yyyy"');
        // $mform->addElement('text', 'audiencexpdate', get_string('audexpdate', 'tool_policy'), 'class="datepicker" data-date-format="mm/dd/yyyy"');
    //     $mform->addElement('static', 'description1', 'Select Policy Expiary Date',
    // '<input name="policyexpdate" class="datepicker" data-date-format="mm/dd/yyyy">');
    //     $mform->addElement('static', 'description2', 'Select Audience Expiary Date',
    // '<input name="audiencexpdate" class="datepicker" data-date-format="mm/dd/yyyy">');
        
        $options = [];
        foreach ([policy_version::TYPE_SITE,
                  policy_version::TYPE_PRIVACY,
                  policy_version::TYPE_THIRD_PARTY,
                  policy_version::TYPE_SAFETY,
                  policy_version::TYPE_FINANCE,
                  policy_version::TYPE_HR,
                  policy_version::TYPE_ISO,
                  policy_version::TYPE_PRODUCTION,
                  policy_version::TYPE_PROCEDURE,
                  policy_version::TYPE_OTHER] as $type) {
            $options[$type] = get_string('policydoctype'.$type, 'tool_policy');
        }
        $mform->addElement('select', 'type', get_string('policydoctype', 'tool_policy'), $options);

        $options = [];
        foreach ([policy_version::AUDIENCE_ALL,
                  policy_version::AUDIENCE_LOGGEDIN,
                  policy_version::AUDIENCE_GUESTS] as $audience) {
            $options[$audience] = get_string('policydocaudience'.$audience, 'tool_policy');
        }
        $mform->addElement('select', 'audience', get_string('policydocaudience', 'tool_policy'), $options);

        if (empty($formdata->id)) {
            $default = userdate(time(), get_string('strftimedate', 'core_langconfig'));
        } else {
            $default = userdate($formdata->timecreated, get_string('strftimedate', 'core_langconfig'));
        }
        $mform->addElement('text', 'revision', get_string('policydocrevision', 'tool_policy'),
            ['maxlength' => 1333, 'placeholder' => $default]);
        $mform->settype('revision', PARAM_TEXT);
        $mform->addRule('revision', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');
        
        $mform->addElement('editor', 'summary_editor', get_string('policydocsummary', 'tool_policy'), ['rows' => 7],
            api::policy_summary_field_options());
        $mform->addRule('summary_editor', null, 'required', null, 'client');

        $mform->addElement('editor', 'content_editor', get_string('policydoccontent', 'tool_policy'), null,
            api::policy_content_field_options());
        $mform->addRule('content_editor', null, 'required', null, 'client');

        $mform->addElement('selectyesno', 'agreementstyle', get_string('policypriorityagreement', 'tool_policy'));

        $mform->addElement('selectyesno', 'optional', get_string('policydocoptional', 'tool_policy'));
        
        if (!$formdata->id || $formdata->status == policy_version::STATUS_DRAFT) {
            // Creating a new version or editing a draft/archived version.
            $mform->addElement('hidden', 'minorchange');
            $mform->setType('minorchange', PARAM_INT);
            $mform->addElement('hidden', 'fromto', $formdata->assignto);
            $mform->setType('fromto', PARAM_INT);
            $statusgrp = [
                $mform->createElement('radio', 'status', '', get_string('status'.policy_version::STATUS_ACTIVE, 'tool_policy'),
                    policy_version::STATUS_ACTIVE),
                $mform->createElement('radio', 'status', '', get_string('status'.policy_version::STATUS_DRAFT, 'tool_policy'),
                    policy_version::STATUS_DRAFT),
                $mform->createElement('static', 'statusinfo', '', html_writer::div(get_string('statusinfo', 'tool_policy'),
                    'muted text-muted')),
            ];
            $mform->addGroup($statusgrp, null, get_string('status', 'tool_policy'), ['<br>'], false);

        } else {
            // Editing an active version.
            $mform->addElement('hidden', 'status', policy_version::STATUS_ACTIVE);
            $mform->setType('status', PARAM_INT);
           
           if(!empty($formdata->clone)){
            $mform->addElement('hidden', 'clone', $formdata->clone);
            $mform->setType('clone', PARAM_INT);
            }
             $statusgrp = [
                $mform->createElement('checkbox', 'minorchange', '', get_string('minorchange', 'tool_policy')),
                $mform->createElement('static', 'minorchangeinfo', '',
                    html_writer::div(get_string('minorchangeinfo', 'tool_policy'), 'muted text-muted')),
            ];

            $mform->addGroup($statusgrp, null, get_string('status', 'tool_policy'), ['<br>'], false);
            $mform->addElement('hidden', 'fromto', $formdata->assignto);
            $mform->setType('fromto', PARAM_INT);
        }
       
       $mform->addElement('header','generalcomment', get_string('generalcomment', 'tool_policy'));
       
       $mform->addElement('hidden', 'fromto', $formdata->assignto);
            $mform->setType('fromto', PARAM_INT);



       if(!empty($formdata->policyid)){
        //print_r($formdata->policyid);
        //die($formdata->fromto);

         $cmnt = $DB->get_records_sql("SELECT pc.assignto,pc.fromto,pc.commentext FROM {tool_policy_comment} AS pc INNER JOIN {tool_policy_versions} AS pv on pv.id=pc.policyversionid where pv.policyid =".$formdata->policyid);
        //echo "result for policydoc";
         //print_r($cmnt);

         foreach ($cmnt as $cmntkey => $cmntvalue) {
        $commentfromuser = core_user::get_user($cmntvalue->fromto);
        $commentassignuser = core_user::get_user($cmntvalue->assignto);
        // if(!empty($commentfromuser))
        // { 
        $mform->addElement('html', '<div class="assigntochangelog"> <img src="'.$CFG->wwwroot.'/admin/tool/policy/pix/f2.svg" class="imgsize">'.get_string('by', 'tool_policy').' '.$commentfromuser->firstname." ".$commentfromuser->lastname.'<span class="assigndate">'.date('m/d/Y').'</span> <br> <span><b>'.get_string('assignto', 'tool_policy').'</b> '.get_string('changefrom', 'tool_policy').' <b>'.$commentfromuser->firstname." ".$commentfromuser->lastname.'</b> '.get_string('to', 'tool_policy').'<b>'.$commentassignuser->firstname." ".$commentassignuser->lastname.'</b><span>
             <span>'.$cmntvalue->commentext.'</span>
            </div>');
        //}
            
         }
       
        
        // $commentfromuser = core_user::get_user($formdata->fromto);
        // $commentassignuser = core_user::get_user($formdata->assignto);
        // $mform->addElement('html', '<div class="assigntochangelog"> <img src="'.$CFG->wwwroot.'/admin/tool/policy/pix/f2.svg" class="imgsize">'.get_string('by', 'tool_policy').' '.$USER->firstname." ".$USER->lastname.'<span class="assigndate">'.date('m/d/Y').'</span> <br> <span><b>'.get_string('assignto', 'tool_policy').'</b> '.get_string('changefrom', 'tool_policy').' <b>'.$commentfromuser->firstname." ".$commentfromuser->lastname.'</b> '.get_string('to', 'tool_policy').'<b>'.$commentassignuser->firstname." ".$commentassignuser->lastname.'</b><span></div>');
         }else{
             $mform->addElement('html', '<div class="assigntochangelog"> <img src="'.$CFG->wwwroot.'/admin/tool/policy/pix/f2.svg" class="imgsize">'.get_string('by', 'tool_policy').' '.$USER->firstname." ".$USER->lastname.'<span class="assigndate">'.date('m/d/Y').'</span> </div>');
         }

        $mform->addElement('editor', 'comment', get_string('comments', 'tool_policy'));
        $mform->addRule('comment', null, 'required', null, 'client');

        $achoices = [];
        $achoices[0] = get_string('pleaseselectuser', 'tool_policy');
        $users = $DB->get_records_sql("SELECT u.id, u.firstname, u.lastname FROM {user} u INNER JOIN {role_assignments} ra ON ra.userid=u.id INNER JOIN {role} r ON r.id=ra.roleid WHERE r.shortname IN ('policyowner','policyeditor') AND u.deleted = '0' AND u.suspended = '0' AND u.id <> 1");
        foreach($users as $user) {
            $achoices[$user->id] = $user->firstname. ' ' . $user->lastname;
        }

        $mform->addElement('html', '<div class="assigntocstatus">'); 
        $mform->addElement('select', 'assignto', get_string('assignto', 'tool_policy'), $achoices);
        $mform->addRule('assignto', null, 'required', null, 'client');
        $mform->setType('assignto', PARAM_INT);

        $schoices = [];
        $schoices[0] = get_string('authoring', 'tool_policy');
        $schoices[1] = get_string('reviewing', 'tool_policy');
        $schoices[2] = get_string('approving', 'tool_policy');
        $schoices[3] = get_string('approved', 'tool_policy');
        $mform->addElement('select', 'cstatus', get_string('cstatus', 'tool_policy'), $schoices);
        $mform->addElement('html', '</div>'); 
         

        // Add "Save" button and, optionally, "Save as draft".
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('save', 'tool_policy'));
        if ($formdata->id && $formdata->status == policy_version::STATUS_ACTIVE) {
            $buttonarray[] = $mform->createElement('submit', 'saveasdraft', get_string('saveasdraft', 'tool_policy'));
        }
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
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['minorchange']) && !empty($data['saveasdraft'])) {
            // If minorchange is checked and "save as draft" is pressed - return error.
            $errors['minorchange'] = get_string('errorsaveasdraft', 'tool_policy');
        }
        return $errors;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        if ($data = parent::get_data()) {
            if (!empty($data->saveasdraft)) {
                $data->status = policy_version::STATUS_DRAFT;
            }
        }
        return $data;
    }
}
