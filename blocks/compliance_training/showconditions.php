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
 $PAGE->set_url('/blocks/compliance_training/showconditions.php');
 $PAGE->set_pagelayout('standard');
 $PAGE->set_title(get_string('showquestionncond', 'block_compliance_training'));
 $PAGE->set_heading(get_string('showquestionncond', 'block_compliance_training'));
 $PAGE->requires->jquery();
 $deleteid=optional_param('deleteid',0,PARAM_INT);
 $PAGE->requires->css( new moodle_url($CFG->wwwroot .'/blocks/compliance_training/styles1.css'), true);
 
 $settingsnode = $PAGE->settingsnav->add(get_string('showquestionncond', 'block_compliance_training'));
 $editurl1 = new moodle_url('/blocks/compliance_training/showconditions.php');
 $editnode = $settingsnode->add(get_string('showquestionncond', 'block_compliance_training'), $editurl1);
 $editnode->make_active();
 
if($deleteid>0){
	$conditions= array('conditionid'=>$deleteid);
	$DB->delete_records('block_questionnerie_setting',$conditions);
}
echo $OUTPUT->header(); //display header      
$admin=get_admin();
if($admin->id==$USER->id){   
echo '<div style="text-align:right;"><a href="'.$CFG->wwwroot.'/blocks/compliance_training/questionnairesett.php">'.get_string('showconditions', 'block_compliance_training').'</a></div>';

$table = new html_table();
$head = array(get_string('name', 'block_compliance_training'), get_string('conditions', 'block_compliance_training'), get_string('edit', 'block_compliance_training'), get_string('delete', 'block_compliance_training'));

$conditions = $DB->get_records_sql("SELECT conditionid, questionnerieid, conditioname FROM {block_questionnerie_setting}");

$table->head = $head;
foreach($conditions as $condid) {
	$condname = '<strong>'.$condid->conditioname.'</strong>';
    $delete= new moodle_url('/blocks/compliance_training/showconditions.php',array('deleteid'=>$condid->conditionid));
    $edit= new moodle_url('/blocks/compliance_training/questionnairesett.php',array('quesid'=> $condid->questionnerieid, 'editid'=>$condid->conditionid));

	$table->data[] = array($condname, 'Condition '. $condid->conditionid,'<a href="'.$edit.'">'.get_string('edit', 'block_compliance_training').'</a>', '<a href="'.$delete.'">'.get_string('delete', 'block_compliance_training').'</a>');
}

echo html_writer::table($table);
}else{
	echo get_string('notadmin', 'block_compliance_training');
}
echo $OUTPUT->footer(); //display footer
?>