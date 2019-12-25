
<?php 
use tool_policy\api;
use tool_policy\policy_version;

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
global $DB, $USER , $OUTPUT;

$position = $_POST['position'];

$policy=$DB->get_records_sql('SELECT DISTINCT p.* FROM `mdl_tool_policy` AS p INNER JOIN `mdl_tool_policy_versions` AS pv ON p.`id`= pv.`policyid` WHERE pv.`parentpolicy`=0 ORDER BY p.`sortorder` ASC');

 
if(!empty($policy)){
	//echo"<pre>"; print_r($policy);
	//print_r($position);
//$policy = api::list_policies(null,false);
if (count($policy)==count($position)){
	$first_names = array_column($policy, 'id');
	$finalval = array_combine($position, $first_names);
	//print_r($finalval);
    foreach($finalval as $k => $p) {
     $DB->set_field('tool_policy', 'sortorder', $k,array('id'=>$p) );
    }	
}
}
print_r($finalval);
