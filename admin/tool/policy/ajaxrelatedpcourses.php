<?php
require(__DIR__.'/../../../config.php');
global $DB;
$searchtxt = $_REQUEST['searchtxt'];
$coursetxt = $_REQUEST['coursetxt'];
$editversionid = $_REQUEST['editversionid'];
if(isset($searchtxt)) {
$courses = $DB->get_records_sql("SELECT c.id, c.fullname, cc.name FROM {course} c
	                         INNER JOIN {course_categories} cc ON cc.id = c.category 
	                              WHERE fullname LIKE '%".$searchtxt."%' ORDER BY cc.id");
$coursearr = array();
foreach ($courses as $course) {
	$coursearr[$course->name][] = array('catname' => $course->name, 'coursename' => $course->fullname, 'courseid' => $course->id);
}

echo json_encode($coursearr);
} else if($coursetxt == 'course' && !empty($editversionid)) {
$relatedcourse = $DB->get_record_sql("SELECT id, relatedcourse FROM {tool_policy_versions} WHERE id = '".$editversionid."'");
if(!empty($relatedcourse)) {
	$courses = explode(",", $relatedcourse->relatedcourse);
	$coursearr = array();
	foreach($courses as $c) {
		$course = $DB->get_record_sql("SELECT id, fullname FROM {course} WHERE id = '".$c."'");
		$coursearr[] = array('id' => $course->id, 'fullname' => $course->fullname);
	}
	echo json_encode($coursearr);
} 
} else {
	echo '0';
}



?>