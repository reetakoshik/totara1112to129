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
 * Provides {@link tool_policy\form\commentview} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");


defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form for editing a policy document version.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class commentview extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG, $DB;
 
        $mform = $this->_form; // Don't forget the underscore! 
        $formdata = $this->_customdata['formdata'];
        $mform->addElement('hidden', 'commentpolicyversionid', $formdata->id);
        // Form definition with new course defaults.
        //$mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('header','generalcomment', get_string('generalcomment', 'tool_policy'));
        
       
        $mform->addElement('html', '<div class="assigntochangelog"> <img src="'.$CFG->wwwroot.'/admin/tool/policy/pix/f2.svg" class="imgsize"> By hartzel kuriel <span class="assigndate">'.date('m/d/Y').'</span> <br> <span><b>Assign to</b> change from <b>hartzel kuriel</b> to <b>reeta kaushik</b><span></div>');

        $mform->addElement('editor', 'comment', 'Comments');
        $mform->addRule('comment', null, 'required', null, 'client');
         $mform->setDefault('comment', array('text' => $formdata->comment, 'format' => $formdata->comment['format'], 'itemid' => $formdata->comment['itemid']));

        $achoices = [];
        $achoices[0] = 'Please select user';
        $users = $DB->get_records_sql("SELECT id, firstname, lastname FROM {user} WHERE deleted = '0' AND suspended = '0' AND id <> 1");
        foreach($users as $user) {
            $achoices[$user->id] = $user->firstname. ' ' . $user->lastname;
        }

        $mform->addElement('html', '<div class="assigntocstatus">'); 
        $mform->addElement('select', 'assignto', get_string('assignto', 'tool_policy'), $achoices);
        $mform->setDefault('assignto',$formdata->assignto);

        $schoices = [];
        $schoices[0] = 'authoring';
        $schoices[1] = 'reviewing';
        $schoices[2] = 'approving';
        $schoices[3] = 'approved';
        $mform->addElement('select', 'cstatus', get_string('cstatus', 'tool_policy'), $schoices);
        $mform->setDefault('cstatus',$formdata->cstatus);
        $mform->addElement('html', '</div>'); 
        
        // Add "Save" button and, optionally, "Save as draft".
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('save', 'tool_policy'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        //$this->set_data($formdata);
           
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}