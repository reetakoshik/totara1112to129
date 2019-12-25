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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/admin/forms.php');
require_once($CFG->dirroot . "/admin/tool/totara_sync/locallib.php");

$elementname = required_param('element', PARAM_TEXT);

if (!$element = totara_sync_get_element($elementname)) {
    print_error('elementnotfound', 'tool_totara_sync');
}

admin_externalpage_setup('syncelement'.$elementname);

$task = $element->get_dedicated_scheduled_task();
list($complexscheduling, $scheduleconfig) = get_schedule_form_data($task);

$form = new totara_sync_element_settings_form($FULLME, ['element' => $element, 'complexscheduling' => $complexscheduling]);

/// Process actions
if ($data = $form->get_data()) {

    $element->save_configuration($data);

    $sourceurl = new moodle_url(
        '/admin/tool/totara_sync/admin/sourcesettings.php',
        ['element' => $element->get_name(), 'source' => $data->{'source_' . $element->get_name()}]
    );

    totara_set_notification(
        get_string('settingssavedlinktosource', 'tool_totara_sync', $sourceurl->out()),
        $FULLME,
        ['class' => 'notifysuccess']
    );
}

$currentvalues = get_config($element->get_classname());
$currentvalues->{'source_' . $element->get_name()} = get_config('totara_sync', 'source_' . $element->get_name());

if (!empty($currentvalues->notifytypes)) {
    $currentvalues->notifytypes = explode(',', $currentvalues->notifytypes);
    foreach ($currentvalues->notifytypes as $index => $issuetype) {
        $currentvalues->notifytypes[$issuetype] = 1;
        unset($currentvalues->notifytypes[$index]);
    }
}

if ($element->use_fileaccess_defaults()) {
    $currentvalues->fileaccess = totara_sync_element_settings_form::USE_DEFAULT;
}

// Set schedule form elements.
$currentvalues->schedulegroup = $scheduleconfig;
$currentvalues->cronenable = $task->get_disabled() ? false : true;

/// Set form data
$form->set_data($currentvalues);


/// Output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("settings:{$elementname}", 'tool_totara_sync'));

$form->display();

// Hack to hide static elements describing default text and scheduling.
// Static elements can't be hidden by the mforms hideIf functionality.
// If and when replacing this area with Totara forms, use the hidden if functionality in that (making sure it does
// work for static elements). And then this js can be scrapped.
// The elements affected are named 'notifcationdefaults', 'scheduledefaultsetting' and 'advancedschedule' in the
// totara_sync_element_settings_form mform.
$js = '

var notificationUseDefaults = document.getElementById("id_notificationusedefaults");
if (notificationUseDefaults) {

    var notificationDefaultText = document.getElementById("fitem_id_notifcationdefaults");
    
    // On load.
    if (!notificationUseDefaults.checked) {
        notificationDefaultText.classList.add("hidden");
    }
    notificationUseDefaults.addEventListener("change", function() {
        notificationDefaultText.classList.toggle("hidden");
    });
}

var scheduleUseDefaults = document.getElementById("id_scheduleusedefaults");
if (scheduleUseDefaults) {

    var scheduleDefaultText = document.getElementById("fitem_id_scheduledefaultsetting");
    var scheduleAdvancedText = document.getElementById("fitem_id_advancedschedule");
    
    if (scheduleAdvancedText) {
        // On load.
        if (scheduleUseDefaults.checked) {
            scheduleAdvancedText.classList.add("hidden");
        }
        scheduleUseDefaults.addEventListener("change", function() {
            scheduleAdvancedText.classList.toggle("hidden");
        }); 
    }
    
    // On load.
    if (!scheduleUseDefaults.checked) {
        scheduleDefaultText.classList.add("hidden");
    }
    scheduleUseDefaults.addEventListener("change", function() {
        scheduleDefaultText.classList.toggle("hidden");
    }); 
}
';
$PAGE->requires->js_amd_inline($js);

echo $OUTPUT->footer();

