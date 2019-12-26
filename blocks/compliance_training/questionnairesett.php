<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yashco Systems <reeta.yashco@gmail.com>
 * @package totara
 * @subpackage block_compliance_training
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/compliance_training/locallib.php');
global $DB, $OUTPUT, $PAGE, $USER, $CFG ;

require_login();
//set page url
$PAGE->set_url('/blocks/compliance_training/questionnairesett.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('questionnairesettingspage', 'block_compliance_training'));
$PAGE->set_heading(get_string('questionnairesettings', 'block_compliance_training'));
$PAGE->set_context(context_system::instance());
$PAGE->requires->jquery();
$PAGE->requires->css( new moodle_url($CFG->wwwroot .'/blocks/compliance_training/styles1.css'), true);
$PAGE->requires->js_call_amd('block_compliance_training/questionnairesett', 'init');
$settingsnode = $PAGE->settingsnav->add(get_string('questionnairesettings', 'block_compliance_training'));
$editurl1 = new moodle_url('/blocks/compliance_training/questionnairesett.php');
$editnode = $settingsnode->add(get_string('questionnairesettings', 'block_compliance_training'), $editurl1);
$editnode->make_active();
$CFG->cachejs= false;
$editid=optional_param('editid',0,PARAM_INT);
echo $OUTPUT->header();
$questionnaire = $DB->get_records_sql("SELECT q.id, q.name FROM {questionnaire} q 
                                   INNER JOIN {course} c ON c.id = q.course
                                   INNER JOIN {course_categories} cc ON cc.id = c.category
                                        WHERE cc.idnumber = ?",array('compliance'));
if(isset($_REQUEST['quesid'])) {
	$quesid = $_REQUEST['quesid'];
} else {
	$quesid = 0;
}

if(isset($_POST['savequescond']) && $editid === 0) {
	$conditionid = $DB->get_record_sql("SELECT id, conditionid FROM {block_questionnerie_setting} ORDER BY id DESC", null, $limitfrom=0,$limitto=1);
	if(empty($conditionid)) {
		$condid = 1;
	} else {
		$condid = $conditionid->conditionid + 1;
	}
	$certifids = implode(',', $_POST['certifids']);
	$orgids = implode(',', $_POST['orgids']);
	$conditioname = $_POST['conditioname'];
	foreach($_POST['qno_'] as $key => $val) {
		$value = null;
		if(is_array($val)) {
			
			$value = implode(',', $val);
		} else {
			if($val != '0') {
				$value = $val;
			}
		}
		
		
		if($_POST['extcond_'][$key] != '0') {
			$extcondition = $_POST['extcond_'][$key];
		} else {
			$extcondition = null;
		}
		
		if($value != null) {
			$record = new \stdClass();
			$record->questionnerieid = $_POST['qname'];
			$record->questionid = $key;
			$record->value = $value;
			$record->userid = $USER->id;
			$record->conditionid = $condid;
			$record->certifids = $certifids;
			$record->orgids = $orgids;
			$record->ext_cond = $extcondition;
			$record->conditioname = $conditioname;
			$DB->insert_record('block_questionnerie_setting', $record);
			
		}
	}
	
	echo '<div style="background-color:green;">'.get_string('recordsave', 'block_compliance_training').'</div>';
} else if(isset($_POST['updatequescond']) && $editid > 0) {
	$conditionid = $DB->get_records_sql("SELECT id FROM {block_questionnerie_setting} WHERE conditionid = ?", array($editid));
	$condid = $editid;
	$certifids = implode(',', $_POST['certifids']);
	$orgids = implode(',', $_POST['orgids']);
	$conditioname = $_POST['conditioname'];
	foreach($_POST['qno_'] as $key => $val) {
		$value = null;
		if(is_array($val)) {
			
			$value = implode(',', $val);
		} else {
			if($val != '0') {
				$value = $val;
			}
		}
		
		
		if($_POST['extcond_'][$key] != '0') {
			$extcondition = $_POST['extcond_'][$key];
		} else {
			$extcondition = null;
		}
		
		if($value != null) {
			$updateid = $DB->get_record_sql("SELECT id FROM {block_questionnerie_setting} WHERE questionnerieid = ? AND questionid = ? AND conditionid = ?", array($_POST['qname'], $key, $condid));
			
			$record = new \stdClass();

			$record->questionnerieid = $_POST['qname'];
			$record->questionid = $key;
			$record->value = $value;
			$record->userid = $USER->id;
			$record->conditionid = $condid;
			$record->certifids = $certifids;
			$record->orgids = $orgids;
			$record->ext_cond = $extcondition;
			$record->conditioname = $conditioname;
			if(!empty($updateid->id)) {
			$record->id = $updateid->id;
			$DB->update_record('block_questionnerie_setting', $record);
			} else {
			$DB->insert_record('block_questionnerie_setting', $record);
			}
			
		}
	}
	
	echo '<div style="background-color:green;">'.get_string('recordupdate', 'block_compliance_training').'</div>';
}

$editconditioname = null;
$editcertifs = array();
$editorgids = array();
$editvalues = array();
$editextcond = array();
if($editid > 0 && isset($editid)) {
	$editdata = $DB->get_records_sql("SELECT * FROM {block_questionnerie_setting} WHERE conditionid = ?", array($editid));
	//echo '<pre>';print_r($editdata);echo '</pre>';
	foreach($editdata as $data) {
			$editconditioname = $data->conditioname;
			$editcertifs1 = $data->certifids;
			$editorgids1 = $data->orgids;
			$editvalues[$data->questionid] = $data->value;
			$editextcond[$data->questionid] = $data->ext_cond;
	}
	$editcertifs = explode(',', $editcertifs1);
	$editorgids = explode(',', $editorgids1);
}

echo '<div style="text-align:right;"><a href="'.$CFG->wwwroot.'/blocks/compliance_training/showconditions.php">'.get_string('showconditions', 'block_compliance_training').'</a></div>';
$admin = get_admin();
if($admin->id == $USER->id) {
echo '<form method="post" action="'.$editurl1.'" >';
echo '<input type="hidden" name="quesid" value="'.$quesid.'">';
echo '<input type="hidden" name="editid" value="'.$editid.'">';
echo '<strong>'.get_string('selectquestionnerie', 'block_compliance_training').'</strong>';
echo "<br>";
echo '<select name="qname" onchange="changequestionnaire(this.value);">';
echo '<option value="0">'.get_string('selectquestionnerie', 'block_compliance_training').'</option>';
foreach($questionnaire as $ques) {
	$selected = '';
	if($quesid == $ques->id) {
		$selected = ' selected="selected"';
	}
	echo '<option value="'.$ques->id.'" '.$selected.'>'.$ques->name.'</option>';
}
echo '</select>';


if($quesid > 0) {
	$qquestions = $DB->get_records_sql("SELECT qq.id, qq.name, qq.content, qq.type_id FROM {questionnaire_question} qq INNER JOIN {questionnaire_question_type} qqt ON qqt.typeid = qq.type_id WHERE name IS NOT NULL AND survey_id = ? AND deleted = ? AND qqt.type IN ('Yes/No', 'Radio Buttons', 'Dropdown Box')",array($quesid,'n'));
	
	$responseid = $DB->get_record_sql("SELECT qa.id FROM {questionnaire_attempts} qa INNER JOIN {questionnaire_response} qr ON qr.id = qa.rid WHERE qa.qid = ? AND qa.userid = ? ORDER BY qa.id DESC",array( $quesid, $admin->id), $limitfrom=0,$limitto=1);
	
	$questionnaire_response_bool = $DB->get_records_sql("SELECT question_id, choice_id FROM {questionnaire_response_bool} WHERE response_id = ?",array($responseid->id));
	
	$compid=$DB->get_record_sql("SELECT cc.id FROM {course_categories} cc WHERE cc.idnumber=?",array('compliance'));
	$certifs = $DB->get_records_sql("SELECT p.id, p.fullname, cc.name, cc.id AS catid
      FROM {prog} p
      INNER JOIN {course_categories} cc ON cc.id = p.category
      WHERE p.certifid IS NOT NULL AND cc.path LIKE '/".$compid->id."/%' GROUP BY cc.id, p.id");
	echo '<br><br>';
	echo '<strong>'.get_string('addcondition', 'block_compliance_training').'</strong>';
	echo '<br>';
	echo '<input type="text" name="conditioname" placeholder="'.get_string('enterconditionname', 'block_compliance_training').'" value="'.$editconditioname.'">';
	echo '<br><br>';
	echo '<strong>'.get_string('selectcertificate', 'block_compliance_training').'</strong>';
	echo "<br>";
	echo '<select name="certifids[]" multiple>';
	echo '<option value="0">'.get_string('selectcertificate', 'block_compliance_training').'</option>';
	$catid = 0;
	foreach($certifs as $certif) {

		if($certif->catid != $catid) {
			echo '<optgroup label="'.$certif->name.'">';
		}
		$selected = '';
		if(in_array($certif->id, $editcertifs)) {
			$selected = ' selected="selected"';
		}
		echo '<option value="'.$certif->id.'" '.$selected.'>'.$certif->fullname.'</option>';
		if($certif->catid != $catid) {
			echo '</optgroup>';
		}
		$catid = $certif->catid;
	}
	echo '</select>';

	$orgs = $DB->get_records('org', null, 'path', 'id, fullname, depthlevel, parentid, path');
	echo '<br><br>';
	echo '<strong>'.get_string('selectorg', 'block_compliance_training').'</strong>';
	echo "<br>";
	echo '<select name="orgids[]" multiple>';
	echo '<option value="0">'.get_string('selectorg', 'block_compliance_training').'</option>';
	foreach($orgs as $org) {
		$selected = '';
		$px = $org->depthlevel * 25;
		$px = $px.'px;';
		$selected = '';
		if(in_array($org->id, $editorgids)) {
			$selected = ' selected="selected"';
		}
		echo '<option style="margin-left:'.$px.'" value="'.$org->id.'" '.$selected.'>'.$org->fullname.'</option>';
	}
	echo '</select>';

	$table = '';
	$table = '<table border="1">
	              <tr>
	                  <th>'.get_string('answers', 'block_compliance_training').'</th>
	                  <th>'.get_string('extracondition', 'block_compliance_training').'</th>
	                  <th>'.get_string('questioncontent', 'block_compliance_training').'</th>
	                  <th>'.get_string('questionname', 'block_compliance_training').'</th>
	              </tr>';
	foreach($qquestions as $ques) {
		$select = '';
		if($ques->type_id == 6) {
			$options = $DB->get_records_sql("SELECT id, content FROM {questionnaire_quest_choice} WHERE question_id = ?",array($ques->id));
			$select = '<select name="qno_['.$ques->id.'][]" multiple>';
			$select .=  '<option value="0">'.get_string('selectoption', 'block_compliance_training').'</option>';
			foreach($options as $option) {
				$editvaluesarr = explode(',', $editvalues[$ques->id]);
				$selected = '';
				if(in_array($option->id, $editvaluesarr)) {
					$selected = ' selected="selected"';
				}
				$select .=  '<option value="'.$option->id.'" '.$selected.'>'.$option->content.'</option>';
			}
			$select .=  '</select>';
		} else if($ques->type_id == 4) {
			$options = $DB->get_records_sql("SELECT id, content FROM {questionnaire_quest_choice} WHERE question_id = '".$ques->id."'");
			$editvaluesarr = explode(',', $editvalues[$ques->id]);
			$select = '<select name="qno_['.$ques->id.']" >';
			$select .=  '<option value="0">'.get_string('selectoption', 'block_compliance_training').'</option>';
			foreach($options as $option) {
				$selected = '';
				if(in_array($option->id, $editvaluesarr)) {
					$selected = ' selected="selected"';
				}
				$select .=  '<option value="'.$option->id.'" '.$selected.'>'.$option->content.'</option>';
			}
			$select .=  '</select>';
		} else if($ques->type_id == 1) {
			$selectedy = '';
			$selectedn = '';
			if($editvalues[$ques->id] == 'y') {
				$selectedy = ' selected="selected"';
			} else if($editvalues[$ques->id] == 'n') {
				$selectedn = ' selected="selected"';
			}
			$select = '<select name="qno_['.$ques->id.']" >';
			$select .=  '<option value="0">'.get_string('selectoption', 'block_compliance_training').'</option>';
				$select .=  '<option value="y" '.$selectedy.'>Yes</option>';
				$select .=  '<option value="n" '.$selectedn.'>No</option>';
			$select .=  '</select>';
		}

			$selectedand = '';
			$selectedor = '';
			if($editextcond[$ques->id] == 'and') {
				$selectedand = ' selected="selected"';
			} else if($editextcond[$ques->id] == 'or') {
				$selectedor = ' selected="selected"';
			}
			$select2 = '<select name="extcond_['.$ques->id.']" >';
			$select2 .=  '<option value="0">'.get_string('selectcondition', 'block_compliance_training').'</option>';
				$select2 .=  '<option value="and" '.$selectedand.'>AND</option>';
				$select2 .=  '<option value="or" '.$selectedor.'>OR</option>';
			$select2 .=  '</select>';
		
			$table .= '<tr>
							<td>'.$select.'</td>
							<td>'.$select2.'</td>
							<td>'.$ques->content.'</td>
							<td>'.$ques->name.'</td>
					   </tr>';
	}
	$savebtn = '';
	if(isset($editid) && $editid > 0) {
		$savebtn = ' name="updatequescond" value="'.get_string('editcondition', 'block_compliance_training').'"';
	} else {
		$savebtn = ' name="savequescond" value="'.get_string('savecondition', 'block_compliance_training').'"';
	}
	$table .= '<tr><td colspan="4"><input type="submit" '.$savebtn.' /></td></tr></table>';
echo $table;	
echo '</form>';

	
} 
} else {
	echo get_string('notadmin', 'block_compliance_training');
}
echo $OUTPUT->footer(); 
?>