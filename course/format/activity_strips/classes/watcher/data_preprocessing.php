<?php

namespace format_activity_strips\watcher;
  
class data_preprocessing 
{
    public static function execute(\format_activity_strips\hook\data_preprocessing $hook)
    {
    	global $DB;

    	$cmid = isset($_GET['update']) ? $_GET['update'] : 0;
        $cmid = !$cmid && isset($_POST['coursemodule']) ? $_POST['coursemodule'] : $cmid;
    	$record = $DB->get_record('display_options', ['cmid' => $cmid]);
  		$twofa = $record && $record->enable_twofa ? 1 : 0;

  		$hook->default_values['twofa'] = $twofa;
    }
}