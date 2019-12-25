<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB;
$moduleid = $_REQUEST['moduleid'];
require_login();
if(is_siteadmin()) {
	$status = $DB->get_record_sql("SELECT id, visible FROM {modules} WHERE id = '".$moduleid."'");
	if($status->visible == 1) {
		$currentstatus = 0;
	} else {
		$currentstatus = 1;
	}
	$record = new \stdClass();
	$record->id = $moduleid;
	$record->visible = $currentstatus;
	$DB->update_record('modules', $record);
	echo 'Status Changes successfully!!!';
}