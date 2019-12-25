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
 * @subpackage block_metrics_compliance
 */
 require_once(dirname(__FILE__) . '/../../config.php');
 
 global $DB, $OUTPUT, $PAGE, $USER, $CFG ;

 require_login();
 //set page url
 $PAGE->set_url('/blocks/activity_status/index.php');
 $PAGE->set_pagelayout('standard');
 $PAGE->set_title('Activity Status');
 $PAGE->set_heading('Activity Status');
 $settingsnode = $PAGE->settingsnav->add('activity_status');
 $editurl = new moodle_url('/blocks/activity_status/index.php');
 $editnode = $settingsnode->add('Activity Status Page', $editurl);
 $editnode->make_active();
 $PAGE->requires->jquery();
echo $OUTPUT->header();
echo '<div><h2>Change activity plugin status</h2></div><br><br>';
$modules = $DB->get_records('modules', null, '', 'id, name, visible');
$table = new html_table();
$table->head = array('S.No.', 'Module Name', 'Enable/Disable Status');
$i = 1;
foreach($modules as $module) {
	$redclassname = '';
	if($module->visible == 1) {
		$alt = "Click Disable";
		$title = 'Enabled';
	} else {
		$alt = "Click Enable";
		$title = 'Disabled';
		$redclassname = 'btn-cancel';
	}
	$status = '<a class="btn '.$redclassname.'" href="#" title="'.$alt.'" onclick="change_mod_status(\''.$module->id.'\',\''.$title.'\', \''.$module->name.'\');">'.$title.'</a>';
	$table->data[] = array($i, $module->name, $status);
	$i++;
}
echo html_writer::table($table);
echo $OUTPUT->footer();
?>
<script type="text/javascript">
	function change_mod_status(moduleid, status, modname) {
		if(status == 'Enabled') {
			var confirmStatus = 'Are you sure you want to disable "'+ modname +'" activity!!!';
		} else {
			var confirmStatus = 'Are you sure you want to enable "'+ modname +'" activity!!!';
		}
		if(confirm(confirmStatus)) {
			var wwwroot = "<?php echo $CFG->wwwroot; ?>";
			var saveData = $.ajax({
			type: 'POST',
			url: wwwroot + "/blocks/activity_status/ajax_change_modulestatus.php",
			data: {moduleid:moduleid},
			//dataType: "text",
				success: function(resultData) {
					alert("Status Changed!!!");
					location.reload();
				}
			});
			saveData.error(function() { alert("Something went wrong"); });
		}
	}
</script>