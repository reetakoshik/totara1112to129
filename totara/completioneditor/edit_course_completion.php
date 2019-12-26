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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

global $DB, $PAGE;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/completioneditor/classes/course_editor.php');

$section = optional_param('section', 'overview', PARAM_ALPHA);
$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$deletehistory = optional_param('deletehistory', false, PARAM_BOOL);
$deleteorphanedcritcomplid = optional_param('deleteorphanedcritcomplid', 0, PARAM_INT);
$deletecoursecompletion = optional_param('deletecoursecompletion', 0, PARAM_INT);
$chid = optional_param('chid', 0, PARAM_INT);
$criteriaid = optional_param('criteriaid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

// Access control.
require_login($course);
$coursecontext = \context_course::instance($courseid);
require_capability('totara/completioneditor:editcoursecompletion', $coursecontext);

// Guest user can't be edited.
if ($userid == guest_user()->id) {
    throw new exception("Guest user completion data cannot be edited");
}

// Set up the page.
$url = new moodle_url('/totara/completioneditor/edit_course_completion.php',
    array('section' => $section, 'courseid' => $courseid, 'userid' => $userid));
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->fullname);
$PAGE->set_url($url);

/* @var \totara_completioneditor\output\course_renderer $output */
$output = $PAGE->get_renderer('totara_completioneditor', 'course');

// Add an item to the navbar to make it unique.
$PAGE->navbar->add(get_string('coursecompletionedit', 'totara_completioneditor'));

navigation_node::override_active_url(new moodle_url('/totara/completioneditor/course_completion.php',
    array('courseid' => $courseid)));

$now = time();

if (!empty($deletecoursecompletion)) {
    require_sesskey();

    // Delete course completion and then reload the page.

    // Validate that the user is not assigned.
    if (is_enrolled($coursecontext, $user)) {
        redirect($url, get_string('error:impossibledatasubmitted', 'totara_completioneditor'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    \core_completion\helper::delete_course_completion($courseid, $userid, 'Course completion manually deleted');
    redirect($url, get_string('coursecompletiondeleted', 'totara_completioneditor'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

if (!empty($deletehistory)) {
    require_sesskey();

    // Delete history and then reload the page.

    // Validate that the record to be deleted matches the course and user.
    $historycompletion = \core_completion\helper::load_course_completion_history($chid);
    if ($historycompletion->courseid != $courseid || $historycompletion->userid != $userid) {
        redirect($url, get_string('error:impossibledatasubmitted', 'totara_completioneditor'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    \core_completion\helper::delete_course_completion_history($chid, 'History manually deleted');
    redirect($url, get_string('coursecompletionhistorydeleted', 'totara_completioneditor'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

if (!empty($deleteorphanedcritcomplid)) {
    require_sesskey();

    // Delete the orphaned crit_compl record and then reload the page.

    // Validate that the record to be deleted matches the course and user.
    $critcompl = $DB->get_record('course_completion_crit_compl',
        array('id' => $deleteorphanedcritcomplid, 'userid' => $userid, 'course' => $courseid));
    if (empty($critcompl)) {
        redirect($url, get_string('error:impossibledatasubmitted', 'totara_completioneditor'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    \core_completion\helper::delete_criteria_completion($deleteorphanedcritcomplid, 'Orphaned crit compl manually deleted');
    redirect($url, get_string('coursecompletionorphanedcritcompldeleted', 'totara_completioneditor'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// Load the form with the current data.
list($currentdata, $params) = \totara_completioneditor\form\course_completion_controller::
    get_current_data_and_params($section, $courseid, $userid, $chid, $criteriaid, $cmid);
$form = new \totara_completioneditor\form\course_completion($currentdata, $params);

// Process form submission.
if ($form->is_cancelled()) {

    if ($section == 'current') {
        $url->param('section', 'overview');
    }

    if ($section == 'edithistory') {
        $url->param('section', 'history');
    }

    if ($section == 'editcriteria' || $section == 'editmodule') {
        $url->param('section', 'criteria');
    }

    // Reload the form with the original data.
    redirect($url, get_string('completionupdatecancelled', 'totara_completioneditor'),
        null, \core\output\notification::NOTIFY_SUCCESS);

} else if ($data = $form->get_data()) {

    if (!empty($data->coursecompletionhistoryadd)) {
        // Go to add history form.

        $addhistoryurl = new moodle_url('/totara/completioneditor/edit_course_completion.php',
            array('courseid' => $courseid, 'userid' => $userid, 'section' => 'edithistory'));
        redirect($addhistoryurl);
    }

    if (!empty($data->savehistory)) {
        // Save the history completion record.

        if (empty($chid)) {
            // Add a new history record.
            $historycompletion = new stdClass();
            $historycompletion->courseid = $courseid;
            $historycompletion->userid = $userid;
            $message = 'History manually created';
        } else {
            // Update an existing history record.

            $historycompletion = \core_completion\helper::load_course_completion_history($chid);
            $message = 'History manually edited';
        }

        $historycompletion->timecompleted = !empty($data->timecompleted) ? $data->timecompleted : null;
        $historycompletion->grade = is_numeric($data->grade) ? $data->grade : null;

        \core_completion\helper::write_course_completion_history($historycompletion, $message);

        $url->param('section', 'history');
        redirect($url, get_string('completionchangessaved', 'totara_completioneditor'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

    $ismodule = !empty($cmid);

    if (!empty($data->savemodule) || !empty($data->savecriteria)) {
        // Get all the data, whether it's just one or the other or both records.
        list($cmc, $cccc) = \totara_completioneditor\course_editor::get_module_and_criteria_from_data($data);

        $cmcerrors = !empty($cmc) ? \core_completion\helper::get_module_completion_errors($cmc) : array();
        $ccccerrors = !empty($cccc) ? \core_completion\helper::get_criteria_completion_errors($cccc) : array();

        if (!empty($cmcerrors) || !empty($ccccerrors)) {
            redirect($url, get_string('error:impossibledatasubmitted', 'totara_completioneditor'),
                null, \core\output\notification::NOTIFY_ERROR);
        }

        if (!empty($cmc)) {
            if (empty($cmc->id)) {
                \core_completion\helper::write_module_completion($cmc, 'Module completion manually created');
            } else {
                \core_completion\helper::write_module_completion($cmc, 'Module completion manually updated');
            }
        }

        if (!empty($cccc)) {
            if (empty($cccc->id)) {
                \core_completion\helper::write_criteria_completion($cccc, 'Crit compl manually created', true);
            } else {
                \core_completion\helper::write_criteria_completion($cccc, 'Crit compl manually updated', true);
            }
        }

        $url->param('section', 'criteria');
        redirect($url, get_string('completionchangessaved', 'totara_completioneditor'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if (!empty($data->savecurrent)) {
        // Save the current course completion changes.
        $coursecompletion = \totara_completioneditor\course_editor::get_current_completion_from_data($data);

        if (\core_completion\helper::write_course_completion($coursecompletion, 'Completion manually edited')) {
            $url->remove_params(['section']);
            redirect($url, get_string('completionchangessaved', 'totara_completioneditor'),
                null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect($url, get_string('error:impossibledatasubmitted', 'totara_completioneditor'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
    }
}

// Load js.
$PAGE->requires->strings_for_js(
    array('coursecompletiondelete', 'coursecompletionhistorydelete', 'coursecompletionorphanedcritcompldelete'),
    'totara_completioneditor'
);
$PAGE->requires->js_call_amd('totara_completioneditor/edit_course_completion', 'init');

// Display.
$heading = get_string('completionsforuserin', 'totara_completioneditor',
    array('user' => fullname($user), 'object' => format_string($course->fullname)));
echo $output->header();
echo $output->heading($heading);
if (!is_enrolled($coursecontext, $user)) {
    echo $output->not_enrolled_notification($params['hascoursecompletion'], $courseid, $userid);
}
echo $output->editor_tabs($section, $courseid, $userid);
echo $form->render();
echo $output->footer();
