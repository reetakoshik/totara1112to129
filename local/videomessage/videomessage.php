<?php
/**
 * My Media version file.
 *
 * @package    local_videomessage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2014 Remote Learner.net Inc http://www.remote-learner.net
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/videomessage/lib.php');

// print_r("I am in require_once");
// die();
global $USER,$PAGE,$OUTPUT,$CFG,$DB;

require_login();

$context = context_user::instance($USER->id);
require_capability('local/mymedia:view', $context);

$PAGE->set_context(context_system::instance());
$site = get_site();
$header  = format_string($site->shortname).": videomessage";

$PAGE->set_url('/local/videomessage/videomessage.php');
$PAGE->set_pagetype('videomessage-index');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($header);
$PAGE->set_heading($header);

$pageclass = 'local-videomessage-body';
$PAGE->add_body_class($pageclass);

echo $OUTPUT->header();

require_once("$CFG->libdir/formslib.php");
 
class simplehtml_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('text', 'email', 'To'); // Add elements to your form
        $mform->setType('email', PARAM_NOTAGS);                   //Set type of element
        $mform->addRule('email', null, 'required', null,null);
		
        //$mform->setDefault('email', 'Please enter email');        //Default value
        $btnadd = array();
        $btnadd[] = $mform->createElement('button', 'addaudience', 'Add Audience', 'id="add-audience"');

        $btnadd[] = $mform->createElement('button', 'addusers', 'Add Users', 'id="add-users"');
        $mform->addGroup($btnadd, 'addemails', '', ' ', false);
        $attributes=array('size'=>'20');
        $mform->addElement('text', 'subject', 'Subject', $attributes);
        $mform->addElement('editor', 'mailmessage', 'Message', ['rows' => 7],
            videomessage_summary_field_options());
        $buttonarray=array();
		$buttonarray[] = $mform->createElement('submit', 'sendemail', get_string('savechanges'));
		// $buttonarray[] = $mform->createElement('reset', 'resetbutton', get_string('revert'));
		$buttonarray[] = $mform->createElement('cancel');
		$mform->addGroup($buttonarray, 'sendmessage', '', ' ', false);
            
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
//Instantiate simplehtml_form 
$mform = new simplehtml_form();
 
//Form processing and displaying is done here
if ($mform->is_cancelled()) {

	
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
	//print_r($fromform); 
	$record = new \stdClass();
	$record->email = $fromform->email;
	$record->subject  = $fromform->subject;
	$record->mailmessage = $fromform->mailmessage['text'];

	$DB->insert_record('videomessage', $record, $returnid=true, $bulk=false);
	print_r("<div> email has been sent </div>");
	// echo "fromform";
	// echo "Message sent";
  //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.
 
  //Set default data (if any)
  $mform->set_data($toform);
  //displays the form
  $mform->display();
}

echo $OUTPUT->footer();