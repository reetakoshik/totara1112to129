<?php
require_once(dirname(__FILE__) . '/../../config.php');

function compliance_condition($userid, $conditionid) {
	global $DB, $USER;
	$staticondid = 0;
	$returnvalue = 0;
    $innerconditions = $DB->get_records_sql("SELECT * FROM {block_questionnerie_setting} WHERE conditionid = ?",array($conditionid));
    foreach($innerconditions as $cond) {
    	$attempt=$DB->get_record_sql("SELECT  MAX(qa.rid) AS responseid FROM {questionnaire_attempts} qa WHERE qa.qid = ? AND qa.userid= ?",array($cond->questionnerieid, $userid));
    	
    	$returnand = 0;
    	if($cond->conditionid == $staticondid || $staticondid == 0) {
    		
    	$type_id = $DB->get_record_sql("SELECT id, type_id FROM {questionnaire_question} WHERE id = ?",array($cond->questionid));
    	if($type_id->type_id == 6) {
				$choiceids = explode(',', $cond->value);
				
				$q3arr = array();
				foreach($choiceids as $ch) {
					$q3arr[] = $ch;
				}
				$q3answer = $DB->get_record_sql("SELECT id, choice_id FROM {questionnaire_resp_single} WHERE question_id = ? AND response_id = ?", array($cond->questionid, $attempt->responseid));
				if(in_array($q3answer->choice_id, $q3arr)) {
					$return = '1';
				}
		} else if($type_id->type_id == 1) {
				$choiceids = explode(',', $cond->value);
				
				$q3arr = array();
				foreach($choiceids as $ch) {
					$q3arr[] = $ch;
				}
				$sql = "SELECT id
				FROM {questionnaire_response_bool} qrb
				WHERE question_id = ? AND choice_id = ? AND response_id = ?";
				$q3answer = $DB->get_record_sql($sql, array($cond->questionid, $cond->value, $attempt->responseid));
				
				if(!empty($q3answer)) {
					$return = '1';
					$returnvalue = $return;
				}
		}
		$staticondid = $cond->conditionid;

		if($return == '1' && ($cond->ext_cond == 'or' || $cond->ext_cond == null)) {
			if($cond->ext_cond == null) {
				$returnvalue = $return;
			} else if($cond->ext_cond == 'or') {
				$returnvalue = $return;
			} else if($cond->ext_cond == 'and' && $returnand != '0') {
				$returnvalue = $return;
			}
			
		} else if($return == '1' && $cond->ext_cond == 'and') {
			$returnand = $return;
			$returnvalue = $return;
		}

		}
		
    }

	return $returnvalue;
}


function get_com_subcateogry_name($certif) {
	global $DB, $USER;
	if(!empty($certif)){
 $subcateid = $DB->get_record_sql("SELECT cc.parent,p.category, cc.name, cc.id FROM {prog} p INNER JOIN {course_categories} cc ON p.category=cc.id WHERE p.id='".$certif."'");

 return $subcateid->name;
}
return '0';
}

function get_com_subcateogry_id($certif) {
	global $DB, $USER;
	if(!empty($certif)){
 $subcateid = $DB->get_record_sql("SELECT cc.parent,p.category, cc.name, cc.id FROM {prog} p INNER JOIN {course_categories} cc ON p.category=cc.id WHERE p.id='".$certif."'");

 return array('id' => $subcateid->id, 'name' =>$subcateid->name);
}
return '0';
}
function get_compliance_certifs_condition($conditionid) {
	global $DB;
	$innerconditions = $DB->get_record_sql("SELECT id, certifids FROM {block_questionnerie_setting} WHERE conditionid = ? ORDER BY id DESC",array($conditionid),$limitfrom=0,$limitnum=1);
	return $innerconditions->certifids;
}

function get_compliance_orgids_condition($conditionid) {
	global $DB;
	$innerconditions = $DB->get_record_sql("SELECT id, orgids FROM {block_questionnerie_setting} WHERE conditionid = ? ORDER BY id DESC",array($conditionid),$limitfrom=0,$limitnum=1);
	return $innerconditions->orgids;
}

function compliance_table_cell_color($certificationid, $userid) {
	global $DB;
	
	$certifstatus = $DB->get_record_sql("SELECT id, status FROM {prog_completion} WHERE userid = ? AND programid = ? ORDER BY status DESC",array($userid, $certificationid), $limitfrom=0,$limitnum=1);
	
	if($certifstatus->status == 1 || $certifstatus->status == 4) {
		return $colorcode = '<p style="background-color:#000;text-align:center;color:#FFF;">&nbsp;</p>';
	} else if($certifstatus->status ==  2) {
		return $colorcode = '<p class="colYellow">&nbsp;</p>';
	} else if($certifstatus->status == 3) { //echo $certificationid. ' <br>';die('test123');
		return $colorcode = '<p class="colGreen">&nbsp;</p>';
	} else if($certifstatus->status == 0){
		return $colorcode = '<p class="colRed">&nbsp;</p>';
	}
	
}
?>