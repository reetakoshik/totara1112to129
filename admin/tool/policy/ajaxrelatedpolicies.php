<?php
require(__DIR__.'/../../../config.php');
global $DB;
$searchtxt = $_REQUEST['searchtxt'];
$policytxt = $_REQUEST['policytxt'];
$editversionid = $_REQUEST['editversionid'];
if(isset($searchtxt)) {
$policies = $DB->get_records_sql("SELECT pv.id, pv.name FROM {tool_policy} p
	                         INNER JOIN {tool_policy_versions} pv ON pv.id = p.currentversionid 
	                              WHERE pv.name LIKE '%".$searchtxt."%' ORDER BY pv.id");
$policyarr = array();
foreach ($policies as $policy) {
	$policyarr[] = array('policyname' => $policy->name, 'policyid' => $policy->id);
}

echo json_encode($policyarr);
} else if($policytxt == 'policy' && !empty($editversionid)) {
$relatedpolicy = $DB->get_record_sql("SELECT id, relatedpolicy FROM {tool_policy_versions} WHERE id = '".$editversionid."'");
if(!empty($relatedpolicy)) {
	$policies = explode(",", $relatedpolicy->relatedpolicy);
	$policyarr = array();
	foreach($policies as $p) {
		$policy = $DB->get_record_sql("SELECT id, name FROM {tool_policy_versions} WHERE id = '".$p."'");
		$policyarr[] = array('id' => $policy->id, 'name' => $policy->name);
	}
	echo json_encode($policyarr);
} 
}
else {
	echo '0';
}




?>