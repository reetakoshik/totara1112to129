<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$facetofaceid = required_param('facetofaceid', PARAM_INT); // Necessary when creating new sessions.
$start = required_param('start', PARAM_INT);
$finish = required_param('finish', PARAM_INT);
$sessiondateid = optional_param('sessiondateid', null, PARAM_INT);       // Empty when adding new session.
$timezone = optional_param('timezone', '99', PARAM_TIMEZONE);
$roomid = optional_param('roomid', null, PARAM_INT);
$assetids = optional_param('assetids', null, PARAM_SEQUENCE);

if (!$facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}

if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}

if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

$params = compact('facetofaceid', 'start', 'finish', 'timezone', 'roomid', 'assetids', 'sessiondateid');
$currenturl = new moodle_url('/mod/facetoface/room/ajax/sessiondates.php', $params);

$params['sessionid'] = 0;
if ($sessiondateid) {
    $sessionid = $DB->get_field('facetoface_sessions_dates', 'sessionid', array('id' => $sessiondateid));
    if (!$sessionid || !$session = facetoface_get_session($sessionid)) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    if ($session->facetoface != $facetoface->id) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    $currenturl->param('sessiondateid', $sessiondateid);
    $params['sessionid'] = $sessionid;
}

$context = context_module::instance($cm->id);

ajax_require_login($course, false, $cm);
require_sesskey();
require_capability('mod/facetoface:editevents', $context);

$jsmodule = array(
         'name' => 'totara_f2f_dateintervalkeeper',
         'fullpath' => '/mod/facetoface/js/dateintervalkeeper.js'
);

$PAGE->requires->js_init_call('M.totara_f2f_dateintervalkeeper.init', array(), false, $jsmodule);

$PAGE->requires->strings_for_js(array('save', 'delete'), 'totara_core');
$PAGE->requires->strings_for_js(array('cancel', 'ok', 'edit', 'loadinghelp'), 'moodle');
$PAGE->requires->strings_for_js(array('chooseassets', 'chooseroom', 'dateselect', 'useroomcapacity', 'nodatesyet',
    'createnewasset', 'editasset', 'createnewroom', 'editroom'), 'facetoface');

$form = new \mod_facetoface\form\event_date($currenturl, $params, 'post', '', array('class' => 'dialog-nobind'), true, null, md5($start.$finish));
if ($data = $form->get_data()) {
    // Provide timestamp, timezone values, and rendered dates text.
    $data->html = \mod_facetoface\event_dates::render(
            $data->timestart,
            $data->timefinish,
            $data->sessiontimezone,
            $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones')
    );
    echo json_encode($data);
    exit();
}

$form->display();
echo $PAGE->requires->get_end_code(false);
