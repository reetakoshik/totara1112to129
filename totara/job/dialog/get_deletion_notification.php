<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_job
 */

//define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/job/lib.php');

require_login();
require_sesskey();

$userid = required_param('userid', PARAM_INT);
$jobassignmentid = required_param('jobassignmentid', PARAM_INT);
$jobassignmenttext = required_param('jobassignmenttext', PARAM_TEXT);

// Load the user, we need to verify the user is a valid user.
$targetuser = core_user::get_user($userid, '*', MUST_EXIST);

// They have to be able to view the job assignments.
if (!totara_job_can_view_job_assignments($targetuser)) {
    // Generic error - we don't want to give too much away. Unless debugging is on.
    throw new moodle_exception('error', 'error', '', null, 'No permission to view job assignments.');
}

$jobassignment = \totara_job\job_assignment::get_with_id($jobassignmentid);
if ($jobassignment->userid != $targetuser->id) {
    // Generic error - we don't want to give too much away. Unless debugging is on.
    throw new moodle_exception('error', 'error', '', null, 'Given Job Assignment does not belong to the given user.');
}

// Get number of users that will be affected by deleting this job assignment.
$staffcount = \totara_job\job_assignment::get_count_managed_users($jobassignmentid);
$tempstaffcount = \totara_job\job_assignment::get_count_temp_managed_users($jobassignmentid);

$note = get_string('confirmdeletejobassignment', 'totara_job', $jobassignmenttext);
if ($staffcount || $tempstaffcount) {
    $note .= html_writer::empty_tag('br') . get_string('warningstaffaffectednote', 'totara_job');
    if ($staffcount && $tempstaffcount) {
        $a = new stdClass();
        $a->countstaffassigned = $staffcount;
        $a->counttempstaffassigned = $tempstaffcount;
        $note .= get_string('warningallstafftypeassigned', 'totara_job', $a);
    } else {
        $key = $staffcount ?  'staff' :  'tempstaff';
        $note .= get_string('warning'.$key.'assigned', 'totara_job', ${$key . 'count'});
    }
}
$data = $OUTPUT->notification($note, \core\notification::ERROR);

echo $data;
