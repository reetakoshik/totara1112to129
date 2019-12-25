<?php
/* Redirect to the user's job assignment information
 * @deprecated since 9.0
 */

/**
 * DEPRECATED FILE
 *
 * Deprecated from 9.0 and will be removed in a future release. Viewing user positions now needs
 * to be done via multiple job assignments (/totara/job/jobassignment.php?jobassignmentid=)
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');

// Get input parameters
$userid     = required_param('user', PARAM_INT);               // user id
$courseid   = optional_param('course', SITEID, PARAM_INT);     // course id

require_login();
error_log('/user/positions.php has been deprecated. Please update your code to use the multiple job assignment interface.');
debugging('/user/positions.php has been deprecated. Please update your code to use the multiple job assignment interface.', DEBUG_DEVELOPER);

// Not doing much here
// Simply trying to find the correct url to redirect to

// $USER is the searched for user - redirect to own profile page
// Else Find first job assignment where
//    $USER is a temp manager of the searched for user
//    $USER is a manager of the searched for user
//    searched for user has a job assignment
// And redirect to totara/job/jobassignment.php for this jobassignment id

$ja = array();
if ($userid == $USER->id) {
    $ja = $DB->get_records('job_assignment', array('userid' => $userid), 'sortorder', 'id', 0, 1);
    if (empty($ja)) {
        redirect(new moodle_url('/user/profile.php', array('id' => $userid)));
    }
}

if (empty($ja)) {
    $sql = "SELECT staffja.id
            FROM {job_assignment} staffja
            JOIN {job_assignment} managerja ON staffja.tempmanagerjaid = managerja.id
            WHERE staffja.userid = :userid
              AND managerja.userid = :tempmanagerid
            ORDER BY staffja.sortorder";
    $ja = $DB->get_records_sql($sql, array('userid' => $userid, 'tempmanagerid' => $USER->id), 0, 1);
}
if (empty($ja)) {
    $sql = "SELECT staffja.id
            FROM {job_assignment} staffja
            JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
            WHERE staffja.userid = :userid
              AND managerja.userid = :managerid
            ORDER BY staffja.sortorder";
    $ja = $DB->get_records_sql($sql, array('userid' => $userid, 'managerid' => $USER->id), 0, 1);
}
if (empty($ja)) {
    $sql = "SELECT id
            FROM {job_assignment}
            WHERE userid = :userid
            ORDER BY sortorder";
    $ja = $DB->get_records_sql($sql, array('userid' => $userid), 0, 1);
}
if (empty($ja)) {
    redirect(new moodle_url('/user/profile.php', array('id' => $userid)));
}

$jaid = array_shift($ja)->id;
redirect(new moodle_url('/totara/job/jobassignment.php', array('jobassignmentid' => $jaid, 'course' => $courseid)));
