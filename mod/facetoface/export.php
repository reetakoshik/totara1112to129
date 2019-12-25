<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/customfield/field/location/field.class.php');

$f = required_param('f', PARAM_INT); // facetoface ID
$format = required_param('download', PARAM_ALPHA); // download attendance

$seminar = new \mod_facetoface\seminar($f);
if (!$course = $DB->get_record('course', array('id' => $seminar->get_course()))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance('facetoface', $seminar->get_id(), $seminar->get_course())) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/facetoface:view', $context);
require_capability('mod/facetoface:viewattendees', $context);

$timenow = time();
$timeformat = str_replace(' ', '_', get_string('strftimedate', 'langconfig'));
$downloadfilename = clean_filename($seminar->get_name().'_'.userdate($timenow, $timeformat));

$dateformat = 0;
if ('ods' === $format) {
    // OpenDocument format (ISO/IEC 26300)
    require_once($CFG->dirroot.'/lib/odslib.class.php');
    $downloadfilename .= '.ods';
    $workbook = new MoodleODSWorkbook('-');
} else {
    // Excel format
    require_once($CFG->dirroot.'/lib/excellib.class.php');
    $downloadfilename .= '.xls';
    $workbook = new MoodleExcelWorkbook('-');
    $dateformat = $workbook->add_format();
    $dateformat->set_num_format(MoodleExcelWorkbook::NUMBER_FORMAT_STANDARD_DATE);
}

$workbook->send($downloadfilename);
$worksheet = $workbook->add_worksheet('attendance');
$coursecontext = \context_course::instance($seminar->get_course());

$pos=0;
$customfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
foreach ($customfields as $field) {
    if (!empty($field->showinsummary)) {
        $worksheet->write_string(0, $pos++, $field->fullname);
    }
}
$worksheet->write_string(0, $pos++, get_string('sessionstartdateshort', 'facetoface'));
$worksheet->write_string(0, $pos++, get_string('sessionfinishdateshort', 'facetoface'));
$worksheet->write_string(0, $pos++, get_string('room', 'facetoface'));
$worksheet->write_string(0, $pos++, get_string('timestart', 'facetoface'));
$worksheet->write_string(0, $pos++, get_string('timefinish', 'facetoface'));
$worksheet->write_string(0, $pos++, get_string('duration', 'facetoface'));
$worksheet->write_string(0, $pos++, get_string('status', 'facetoface'));

if ($trainerroles = facetoface_get_trainer_roles($context)) {
    foreach ($trainerroles as $role) {
        $worksheet->write_string(0, $pos++, get_string('role').': '.$role->localname);
    }
}

$userfields = facetoface_get_userfields();
foreach ($userfields as $shortname => $fullname) {
    $worksheet->write_string(0, $pos++, $fullname);
}

$selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
if (!empty($selectjobassignmentonsignupglobal)) {
    $worksheet->write_string(0, $pos++, get_string('selectedjobassignment', 'mod_facetoface'));
}

$worksheet->write_string(0, $pos++, get_string('attendance', 'facetoface'));
$worksheet->write_string(0, $pos++, get_string('datesignedup', 'facetoface'));

facetoface_write_activity_attendance($worksheet, $coursecontext, 1, $seminar->get_id(), null, '', '', $dateformat);

$workbook->close();
