<?php
require(__DIR__.'/../../../config.php');
global $DB;
$searchtxt = $_REQUEST['searchtxt'];
$audiencetxt = $_REQUEST['audiencetxt'];
$editversionid = $_REQUEST['editversionid'];
if(isset($searchtxt)) {
$audiences = $DB->get_records_sql("SELECT id, name
	                               FROM {cohort}
	                              WHERE name LIKE '%".$searchtxt."%' ORDER BY id");
$audiencearr = array();
foreach ($audiences as $aud) {
	$audiencearr[] = array('name' => $aud->name, 'id' => $aud->id);
}
echo json_encode($audiencearr);

} else if($audiencetxt == 'audience' && !empty($editversionid)) {
$relatedaudiences = $DB->get_record_sql("SELECT id, relatedaudiences FROM {tool_policy_versions} WHERE id = '".$editversionid."'");
if(!empty($relatedaudiences)) {
	$audiences = explode(",", $relatedaudiences->relatedaudiences);
	$audiencearr = array();
	foreach($audiences as $a) {
		$aud = $DB->get_record_sql("SELECT id, name FROM {cohort} WHERE id = '".$a."'");
		$audiencearr[] = array('id' => $aud->id, 'name' => $aud->name);
	}
	echo json_encode($audiencearr);
}
} else {
	echo '0';
}

?>