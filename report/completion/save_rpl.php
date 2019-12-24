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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage completion
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once("{$CFG->libdir}/completionlib.php");

///
/// Parameters
///
$type       = required_param('type', PARAM_RAW);
$course_id  = required_param('course', PARAM_INT);
$user_id    = required_param('user', PARAM_INT);
$rpl        = optional_param('rpl', '', PARAM_RAW);
$cmid       = optional_param('cmid', '', PARAM_INT);

// Non-js stuff
$redirect   = optional_param('redirect', false, PARAM_BOOL);
$sort       = optional_param('sort', '', PARAM_RAW);
$start      = optional_param('start', '', PARAM_RAW);

///
/// Load data
///
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $user_id), '*', MUST_EXIST);

// Completion info object
$info = new completion_info($course);

// Get completion object
$params = array(
    'userid'    => $user->id,
    'course'    => $course->id
);

// Completion
if ($type == 'course') {
    // Load course completion
    $completion = new completion_completion($params);

} else if (is_numeric($type)) {
    // Load activity completion
    $params['criteriaid'] = (int)$type;
    $completion = new completion_criteria_completion($params);

} else {
    error('Invalid type');
}


///
/// Check permissions
///
require_login($course);

$context = context_course::instance($course->id);
require_capability('report/completion:view', $context);
require_sesskey();

///
/// Check RPL is enabled
///
if ($type == 'course') {
    $rpl_enabled = $CFG->enablecourserpl;
} else {
    $criteria = $completion->get_criteria();
    $rpl_enabled = completion_module_rpl_enabled($criteria->module);
}

if (!$rpl_enabled) {
    print_error('error:rplsaredisabled', 'completion');
}

// Contains the values that will be stored in the DB (course_modules_completion table).
if (!empty($cmid)) {
    $data = new stdClass();
    $data->id = 0;
    $data->userid = $user->id;
    $data->viewed = 0;
    $data->coursemoduleid = $cmid;
    $data->completionstate = strlen($rpl) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    $data->timemodified = time();
    $cm = get_coursemodule_from_id(null, $cmid, $course->id, false, MUST_EXIST);
    $info->internal_set_data($cm, $data);
} else {
    $cmid = null;
}

if (strlen($rpl)) {
    // Complete.
    $completion->rpl = $rpl;
    $completion->mark_complete();
    \report_completion\event\rpl_created::create_from_rpl($user->id, $course->id, $cmid, $type)->trigger();

} else {
    // If no RPL, uncomplete user, and let aggregation do its thing.
    $completion->set_properties($completion, array(
        'timecompleted' => null,
        'reaggregate' => time(),
        'rpl' => null,
        'rplgrade' => null,
        'status' => COMPLETION_STATUS_NOTYETSTARTED
    ));
    $completion->update();
    \report_completion\event\rpl_deleted::create_from_rpl($user->id, $course->id, $cmid, $type)->trigger();

    if ($type == 'course') {
        $completion->aggregate();
    }
}

// Redirect, if requested (not an ajax request)
if ($redirect) {
    $url = new moodle_url('/report/completion/index.php', array('course' => $course_id, 'sort' => $sort, 'start' => $start));
    redirect($url);
}
