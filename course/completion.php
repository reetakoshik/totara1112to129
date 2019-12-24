<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit course completion settings
 *
 * @package     core_completion
 * @category    completion
 * @copyright   2009 Catalyst IT Ltd
 * @author      Aaron Barnes <aaronb@catalyst.net.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_self.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_duration.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_grade.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
require_once $CFG->libdir.'/gradelib.php';
require_once($CFG->dirroot.'/course/completion_form.php');

$id = required_param('id', PARAM_INT);       // course id

// Perform some basic access control checks.
if ($id) {

    if($id == SITEID){
        // Don't allow editing of 'site course' using this form.
        print_error('cannoteditsiteform');
    }

    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    require_login($course);
    $coursecontext = context_course::instance($course->id);
    require_capability('moodle/course:update', $coursecontext);

} else {
    require_login();
    print_error('needcourseid');
}

// Form unlocked override
$unlockdelete = optional_param('unlockdelete', false, PARAM_BOOL);
$unlockonly = optional_param('unlockonly', false, PARAM_BOOL);

// Load completion object
$completion = new completion_info($course);


// Set up the page.
$PAGE->set_course($course);
$PAGE->set_url('/course/completion.php', array('id' => $course->id));
$PAGE->set_title($course->shortname);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('admin');

// Create the settings form instance.
$form = new course_completion_form('completion.php?id='.$id, compact('course', 'unlockdelete', 'unlockonly'));

/// set data
$currentdata = array('criteria_course_value' => array());

// grab all course criteria and add to data array
// as they are a special case
foreach ($completion->get_criteria(COMPLETION_CRITERIA_TYPE_COURSE) as $criterion) {
    $currentdata['criteria_course_value'][] = $criterion->courseinstance;
}

$form->set_data($currentdata);


// now override defaults if course already exists
if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
} else if ($data = $form->get_data()) {


    // Process criteria unlocking if requested.
    if (completion_can_unlock_data($course->id)) {
        // Check and reload if the user clicked one of the unlock buttons.
        if (!empty($data->settingsunlockdelete)) {
            redirect(new moodle_url('/course/completion.php', array('id' => $course->id, 'unlockdelete' => 1)));
        } else if (!empty($data->settingsunlock)) {
            redirect(new moodle_url('/course/completion.php', array('id' => $course->id, 'unlockonly' => 1)));
        }

        // Check if the form was submitted while unlocked.
        if ($unlockdelete) {
            // The "Unlock and delete" button was clicked, so log and delete the course completion data.
            \totara_core\event\course_completion_reset::create_from_course($course)->trigger();
            $completion->delete_course_completion_data();
        } else if ($unlockonly) {
            // The "Unlock without deleting" button was clicked, so just log it and continue.
            \totara_core\event\course_completion_unlocked::create_from_course($course)->trigger();
        }
    } else if ($completion->is_course_locked(false)) {
        // Abort saving changes if the course is locked (and it wasn't unlocked above).
        print_error('coursecompletionislocked');
    }

/// process data if submitted
    // Loop through each criteria type and run update_config
    $transaction = $DB->start_delegated_transaction();

    global $COMPLETION_CRITERIA_TYPES;
    foreach ($COMPLETION_CRITERIA_TYPES as $type) {

        $class = 'completion_criteria_'.$type;
        $criterion = new $class();
        $criterion->update_config($data);
    }

    $transaction->allow_commit();

    // Handle overall aggregation.
    $aggdata = array(
        'course'        => $data->id,
        'criteriatype'  => null
    );
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->overall_aggregation);
    $aggregation->save();

    // Handle activity aggregation.
    if (empty($data->activity_aggregation)) {
        $data->activity_aggregation = 0;
    }

    $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->activity_aggregation);
    $aggregation->save();

    // Handle course aggregation.
    if (empty($data->course_aggregation)) {
        $data->course_aggregation = 0;
    }

    $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_COURSE;
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->course_aggregation);
    $aggregation->save();

    // Handle role aggregation.
    if (empty($data->role_aggregation)) {
        $data->role_aggregation = 0;
    }

    $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ROLE;
    $aggregation = new completion_aggregation($aggdata);
    $aggregation->setMethod($data->role_aggregation);
    $aggregation->save();

    // Update course total passing grade
    if (!empty($data->criteria_grade)) {
        if ($grade_item = grade_category::fetch_course_category($course->id)->grade_item) {
            $grade_item->gradepass = $data->criteria_grade_value;
            if (method_exists($grade_item, 'update')) {
                $grade_item->update('course/completion.php');
            }
        }
    }

    // TOTARA performance improvement - invalidate static caching of course information.
    completion_criteria_activity::invalidatecache();
    completion_criteria_course::invalidatecache();

    // Trigger an event for course module completion changed.
    $event = \core\event\course_completion_updated::create(
        array(
            'courseid' => $course->id,
            'context' => context_course::instance($course->id)
        )
    );
    $event->trigger();

    // Update reaggregation flag on all existing user course_completion records, so they'll be updated on cron.
    $sql = "UPDATE {course_completions}
               SET reaggregate = :now
             WHERE course = :courseid
               AND status < :statuscomplete";
    $params = array('now' => time(), 'courseid' => $course->id, 'statuscomplete' => COMPLETION_STATUS_COMPLETE);
    $DB->execute($sql, $params);

    // Bulk start users (creates course_completion records for all active participants who don't already have records).
    completion_start_user_bulk($course->id);

    // Invalidate the completion cache
    $info = new completion_info($course);
    $info->invalidatecache();

    // Redirect to the course main page.
    $url = new moodle_url('/course/view.php', array('id' => $course->id));
    totara_set_notification(get_string('completioncriteriachanged', 'core_completion'), $url, array('class' => 'notifysuccess'));
}

// Print the form.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editcoursecompletionsettings', 'core_completion'));

$form->display();

echo $OUTPUT->footer();
