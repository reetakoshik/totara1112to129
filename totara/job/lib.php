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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_job
 */

/**
 * Returns true if the current user can view the given users job assignments.
 *
 * @param stdClass $user
 * @param stdClass $course The course the user course profile is being viewed - if their is one.
 * @return bool
 */
function totara_job_can_view_job_assignments(stdClass $user, stdClass $course = null) {
    global $USER;

    $systemcontext = context_system::instance();

    if (empty($user->id)) {
        // Not a real boy... I mean user.
        return false;
    }
    if (!isloggedin()) {
        // Obviously no.
        return false;
    }
    if (isguestuser($user)) {
        // Guests don't have job assignments.
        return false;
    }

    try {
        $usercontext = context_user::instance($user->id);
    } catch(Exception $e) {
        // user deleted
        return false;
    }

    if (!empty($USER->id) && ($user->id == $USER->id) && has_capability('totara/hierarchy:viewposition', $systemcontext)) {
        // Can view own profile.
        return true;
    } else if (!empty($course) && has_capability('moodle/user:viewdetails', context_course::instance($course->id))) {
        // Has permission to
        return true;
    } else if (has_capability('moodle/user:viewdetails', $usercontext)) {
        return true;
    }
    return false;
}

/**
 * Calculates if the current user can edit job assignments for the specified user.
 *
 * @param int $userid The user ID whose job assignments are being edited
 * @return bool True if the current user is allowed to edit the job assignments
 */
function totara_job_can_edit_job_assignments($userid) {
    global $USER;

    if (empty($userid)) {
        // Not a real boy... I mean user.
        return false;
    }
    if (!isloggedin()) {
        // Obviously no.
        return false;
    }
    if (isguestuser($userid)) {
        // Guests don't have job assignments.
        return false;
    }

    try {
        $usercontext = context_user::instance($userid);
    } catch(Exception $e) {
        // user deleted
        return false;
    }

    // Can assign this particular user's job assignments.
    if (has_capability('totara/hierarchy:assignuserposition', $usercontext)) {
        return true;
    }

    // Editing own job assignments and have capability to assign own job assignments.
    if ($USER->id == $userid && has_capability('totara/hierarchy:assignselfposition', context_system::instance())) {
        return true;
    }

    return false;
}

/**
 * Display job assignment information in the user's profile.
 *
 * @global core_renderer $OUTPUT
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser is the user viewing profile, current user ?
 * @param stdClass|null $course course object
 *
 * @return bool
 */
function totara_job_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course = null) {
    global $CFG, $OUTPUT;

    // Check if the current user can view the specified user's job assignment details.
    $canview = totara_job_can_view_job_assignments($user, $course);

    if (!$canview) {
        // User not allowed to see user details.
        return false;
    }

    // Add category.
    $category = new core_user\output\myprofile\category('jobassignment', get_string('jobassignments', 'totara_job'), 'contact');
    $tree->add_category($category);

    // Check if the current user can add, update and delte the specified user's job assignment details.
    $canedit = totara_job_can_edit_job_assignments($user->id);
    $allowmultiple = !empty($CFG->totara_job_allowmultiplejobs);

    $courseid = !empty($course->id) ? $course->id : null;

    // A bit of a hack here.
    // We are going to display a list in a single node.
    // This is cheap and easy and works around the tragic design of the my profile navigation.
    $data = new \stdClass;
    $data->jobcount = 0;
    $data->hasjobs = false;
    $data->jobs = [];
    $data->canedit = $canedit;
    $data->canadd = false;
    $data->userid = $user->id;
    $data->allowmultiple = $allowmultiple;
    $data->addurl = new moodle_url('/totara/job/jobassignment.php', array('userid' => $user->id));
    foreach (\totara_job\job_assignment::get_all($user->id) as $jobassignment) {
        $jobdata = $jobassignment->export_for_template($OUTPUT, $courseid);

        // Action icons.
        $icon_movedown = new \core\output\flex_icon('move-down');
        $icon_moveup = new \core\output\flex_icon('move-up');
        $icon_delete = new \core\output\flex_icon('delete');

        // The reason that these icons are here and not in the job assignment template data is because
        // the icons belong to the management template, and are not individually useful to the job.
        $icon_movedown->customdata['alt'] = get_string('movedownxjobassignment', 'totara_job', $jobdata->fullname);
        $icon_moveup->customdata['alt'] = get_string('moveupxjobassignment', 'totara_job', $jobdata->fullname);
        $icon_delete->customdata['alt'] = get_string('deletexjobassignment', 'totara_job', $jobdata->fullname);

        $jobdata->canedit = $canedit;
        $jobdata->icon_movedown = [
            'template' => $icon_movedown->get_template(),
            'context' => $icon_movedown->export_for_template($OUTPUT)
        ];
        $jobdata->icon_moveup = [
            'template' => $icon_moveup->get_template(),
            'context' => $icon_moveup->export_for_template($OUTPUT)
        ];
        $jobdata->icon_delete = [
            'template' => $icon_delete->get_template(),
            'context' => $icon_delete->export_for_template($OUTPUT)
        ];
        $data->jobs[] = $jobdata;
        $data->hasjobs = true;
        $data->jobcount ++;
    }
    $data->canadd = $canedit && ($data->jobcount === 0 || $allowmultiple);
    $node = new core_user\output\myprofile\node(
        'jobassignment',
        'jobassignment_list' . $user->id,
        null, null, null,
        $OUTPUT->render_from_template('totara_job/job_management_listing', $data)
    );
    $tree->add_node($node);

    return true;
}

/**
 * Displays a users job title, if no job is provided then an appropriate "no jobs" string is given.
 *
 * Todo: We need the ability to create a non-saved job assignment entry before enforcing type for $jobassignment.
 *
 * @param stdClass $user
 * @param \totara_job\job_assignment|stdClass $jobassignment The job assignment to show - or null if there is not one.
 *    Typically this is a job_assignment instance, but in rare cases may be a "dummy" stdClass object.
 * @param bool $canviewemail
 * @param bool $createjob
 * @return string
 */
function totara_job_display_user_job(stdClass $user, $jobassignment = null, $canviewemail = false, $createjob = false) {

    $a = new stdClass();
    $a->fullname = fullname($user);
    $stringkey = 'dialogmanager';

    if ($canviewemail and !empty($user->email)) {
        $a->email = $user->email;
        $stringkey .= 'email';
    }

    if ($createjob) {
        $stringkey .= 'addemptyjob';
        return get_string($stringkey, 'totara_job', $a);
    }

    if (empty($jobassignment->fullname)) {
        if (isset($jobassignment->idnumber)) {
            // Get the default job name for the user's current language.
            $a->job = get_string('jobassignmentdefaultfullname', 'totara_job', $jobassignment->idnumber);
        } else {
            // No fullname or idnumber. Job does not exist.
            $stringkey .= 'needsjobentry';
            return get_string($stringkey, 'totara_job', $a);
        }
    } else {
        $a->job = $jobassignment->fullname;
    }

    $stringkey .= 'job';
    return get_string($stringkey, 'totara_job', $a);
}
