<?php

namespace format_activity_strips\watcher;
  
class activity_definition 
{
    public static function execute(\format_activity_strips\hook\activity_definition $hook)
    {
    	global $DB, $CFG;

        if (!$CFG->totara_enable_2fa) {
            return;
        }

    	$cmid = isset($_GET['update']) ? $_GET['update'] : 0;
    	$cmid = !$cmid && isset($_POST['coursemodule']) ? $_POST['coursemodule'] : $cmid;
  		$record = $DB->get_record('display_options', ['cmid' => $cmid]);
  		$twofa = $record && $record->enable_twofa ? 1 : 0;

        $hook->mform->addElement('checkbox', 'twofa', get_string('twofa-enable', 'format_activity_strips'));
        $hook->mform->setDefault('twofa', $twofa);
    }
}