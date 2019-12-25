<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/totara/dashboard/lib.php');
require_once($CFG->dirroot.'/blocks/carrousel/lib.php');


class carrousel_block_edit_form extends moodleform 
{
    
    public function definition() 
    {
        $mform = & $this->_form;
        $settings = $this->_customdata['settings'];

        $mform->addElement('text', 'scroll_duration', get_string('scroll_duration', 'block_carrousel'));
    	$mform->setType('scroll_duration', PARAM_FLOAT);
    	$mform->addRule('scroll_duration', null, 'numeric');
        $defaulrDuration = isset($settings->scroll_duration) ? $settings->scroll_duration : 2;
        $mform->setDefault('scroll_duration', $defaulrDuration);
        $mform->addHelpButton('scroll_duration', 'scroll_duration', 'block_carrousel');

        $mform->addElement('hidden', 'blockid', $settings->block_id);
        $mform->setType('blockid', PARAM_INT);

        $this->add_action_buttons(true, get_string('save', 'admin'));

        $this->set_data($this->_customdata['settings']);
    }

}


