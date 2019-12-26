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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_program
 */

require_once(__DIR__ . '/../../config.php');
require_once('HTML/QuickForm/Renderer/QuickHtml.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->dirroot . '/totara/program/edit_completion_form.php');

// Check if programs are enabled.
check_program_enabled();

if (empty($CFG->enableprogramcompletioneditor)) {
    print_error('error:completioneditornotenabled', 'totara_program');
}

$id = required_param('id', PARAM_INT); // Program id.
$userid = required_param('userid', PARAM_INT);

require_login();

$program = new program($id);
$programcontext = $program->get_context();

require_capability('totara/program:editcompletion', $programcontext);

if ($program->is_certif()) {
    print_error('error:notaprogram', 'totara_program');
}

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$url = new moodle_url('/totara/program/edit_completion.php', array('id' => $id, 'userid' => $userid));
$PAGE->set_program($program);

// Load all the data about the user and program.
$progcompletion = prog_load_completion($id, $userid, false);
$exceptions = $DB->get_records('prog_exception', array('programid' => $id, 'userid' => $userid));

$history = $DB->get_records('prog_completion_history',
    array('userid' => $userid, 'programid' => $id), 'timecompleted DESC');

$sql = "SELECT pcl.id, pcl.timemodified, pcl.changeuserid, pcl.description, " . get_all_user_name_fields(true, 'usr') . "
          FROM {prog_completion_log} pcl
          LEFT JOIN {user} usr ON usr.id = pcl.changeuserid
         WHERE (pcl.userid = :userid OR pcl.userid IS NULL) AND pcl.programid = :programid
         ORDER BY pcl.id DESC";
$transactions = $DB->get_records_sql($sql, array('userid' => $userid, 'programid' => $id));

if ($dismissedexceptions = $program->check_user_for_dismissed_exceptions($userid)) {
    $resetexception = optional_param('resetexception', 0, PARAM_INT);

    if ($resetexception) {
        // Remove the exception status on the user assignment.
        $exmanager = new prog_exceptions_manager($id);
        $exmanager->override_dismissed_exception($userid);

        $urlparams = array('id' => $id, 'userid' => $userid);
        $redirecturl = new moodle_url('/totara/program/edit_completion.php', $urlparams);

        totara_set_notification(get_string('exceptionoverridden', 'totara_program'), $redirecturl, array('class' => 'notifysuccess'));
    }
}

if ($progcompletion && empty($exceptions) && !$dismissedexceptions) {
    $errors = prog_get_completion_errors($progcompletion);
    $currentformdata = new stdClass();
    $currentformdata->status = $progcompletion->status;
    // Fix stupid timedue should be -1 for not set problem.
    $currentformdata->timeduenotset = ($progcompletion->timedue == COMPLETION_TIME_NOT_SET) ? 'yes' : 'no';
    $currentformdata->timedue = ($progcompletion->timedue == COMPLETION_TIME_NOT_SET) ? 0 : $progcompletion->timedue;
    $currentformdata->timecompleted = $progcompletion->timecompleted;

    // Prepare the form.
    $problemkey = prog_get_completion_error_problemkey($errors);
    $customdata = array(
        'id' => $id,
        'userid' => $userid,
        'showinitialstateinvalid' => !empty($errors),
        'hascurrentrecord' => !empty($progcompletion),
        'status' => $progcompletion->status,
        'solution' => prog_get_completion_error_solution($problemkey, $id, $userid, true),
    );
    $editform = new prog_edit_completion_form($url, $customdata, 'post', '', array('id' => 'form_prog_completion'));

    // Process any actions submitted.
    $deletehistory = optional_param('deletehistory', 0, PARAM_INT);
    if ($deletehistory) {
        $chid = required_param('chid', PARAM_INT);

        // Validate that the record to be deleted matches the program and user.
        $params = array('id' => $chid, 'programid' => $id, 'userid' => $userid);
        if (!$DB->record_exists('prog_completion_history', $params)) {
            totara_set_notification(get_string('error:impossibledatasubmitted', 'totara_program'),
                $url,
                array('class' => 'notifyproblem'));
        }

        $DB->delete_records('prog_completion_history', array('id' => $chid));

        // Record the change in the program completion log.
        prog_log_completion(
            $id,
            $userid,
            'Completion history deleted, ID: ' . $chid
        );

        totara_set_notification(get_string('completionhistorydeleted', 'totara_program'),
            $url,
            array('class' => 'notifysuccess'));

    } else if ($submitted = $editform->get_data() and !empty($submitted->savechanges)) {
        $newprogcompletion = prog_process_submitted_edit_completion($submitted);

        if (prog_write_completion($newprogcompletion, 'Completion manually edited')) {
            if ($progcompletion->status == STATUS_PROGRAM_COMPLETE && $newprogcompletion->status == STATUS_PROGRAM_INCOMPLETE) {
                prog_reset_course_set_completions($id, $userid);
            }

            // Trigger an event to notify any listeners that the user state has been edited.
            $event = \totara_program\event\program_completionstateedited::create(
                array(
                    'objectid' => $id,
                    'context' => context_program::instance($id),
                    'userid' => $userid,
                    'other' => array(
                        'oldstate' => $progcompletion->status,
                        'newstate' => $newprogcompletion->status,
                        'changedby' => $USER->id
                    ),
                )
            );
            $event->trigger();

            totara_set_notification(get_string('completionchangessaved', 'totara_program'),
                $url,
                array('class' => 'notifysuccess'));
        } else {
            totara_set_notification(get_string('error:impossibledatasubmitted', 'totara_program'),
                $url,
                array('class' => 'notifyproblem'));
        }
    }

    // Init form core js.
    $args = $editform->_form->getLockOptionObject();
    if (count($args[1]) > 0) {
        $PAGE->requires->js_init_call('M.form.initFormDependencies', $args, false, moodleform::get_js_module());
    }
}

// Mark the program progressinfo cache stale to ensure progress is re-read from database on next view
\totara_program\progress\program_progress_cache::mark_progressinfo_stale($id, $userid);

// Masquerade as the completion page for the sake of navigation.
$PAGE->navigation->override_active_url(new moodle_url('/totara/program/completion.php', array('id' => $id)));
// Add an item to the navbar to make it unique.
$PAGE->navbar->add(get_string('editcompletion', 'totara_program'));

// Set up the page.
$PAGE->set_url($url);
$PAGE->set_title($program->fullname);
$PAGE->set_heading($program->fullname);

// Display.
$heading = get_string('completionsforuserinprog', 'totara_program',
    array('user' => fullname($user), 'prog' => format_string($program->fullname)));

// Javascript includes.
if (isset($editform)) {
    $jsmodule = array(
        'name' => 'totara_editprogcompletion',
        'fullpath' => '/totara/program/edit_completion.js');
    $PAGE->requires->js_init_call('M.totara_editprogcompletion.init', array(), false, $jsmodule);
    $PAGE->requires->strings_for_js(array('bestguess', 'confirmdeletecompletion'), 'totara_program');
}

$PAGE->requires->strings_for_js(array('fixconfirmone', 'fixconfirmtitle'), 'totara_program');
$PAGE->requires->js_call_amd('totara_program/check_completion', 'init');

echo $OUTPUT->header();
echo $OUTPUT->container_start('editcompletion');
echo $OUTPUT->heading($heading);

$completionurl = new moodle_url('/totara/program/completion.php', array('id' => $id));
echo html_writer::tag('ul', html_writer::tag('li', html_writer::link($completionurl,
    get_string('completionreturntoprogram', 'totara_program'))));

// Display if and how this user is assigned.
echo $OUTPUT->notification($program->display_completion_record_reason($user), 'notifymessage');

// If the program completion record is missing but should be there then provide a link to fix it.
$missingcompletionrs = prog_find_missing_completions($program->id, $userid);
if ($missingcompletionrs->valid()) {
    $solution = prog_get_completion_error_solution('error:missingprogcompletion', $program->id, $userid, true);
    echo $OUTPUT->notification(html_writer::span($solution, 'problemsolution'), 'notifyproblem');
}
$missingcompletionrs->close();

// If the program completion record exists when it shouldn't then provide a link to fix it.
$unassignedincompletecompletionrs = prog_find_unassigned_incomplete_completions($program->id, $userid);
if ($unassignedincompletecompletionrs->valid()) {
    $solution = prog_get_completion_error_solution('error:unassignedincompleteprogcompletion', $program->id, $userid, true);
    echo $OUTPUT->notification(html_writer::span($solution, 'problemsolution'), 'notifyproblem');
}
$unassignedincompletecompletionrs->close();

// If the certification completion record exists when it shouldn't then provide a link to fix it.
$orphanedexceptionsrs = prog_find_orphaned_exceptions($program->id, $userid, 'program');
if ($orphanedexceptionsrs->valid()) {
    $problemkey = 'error:orphanedexception';
    $solution = get_string($problemkey, 'totara_program') .
        html_writer::empty_tag('br') .
        prog_get_completion_error_solution($problemkey, $program->id, $userid, true);
    echo $OUTPUT->notification(html_writer::span($solution, 'problemsolution'), 'notifyproblem');
}
$orphanedexceptionsrs->close();

// Display the edit completion record form.
if (isset($editform)) {
    $editform->set_data($currentformdata);
    $editform->validate_defined_fields(true);
    $editform->display();
} else if ($dismissedexceptions) {
    $urlparams = array('id' => $id, 'userid' => $userid, 'resetexception' => 1);
    $exceptionurl = new moodle_url('/totara/program/edit_completion.php', $urlparams);

    echo $OUTPUT->notification(get_string('userhasdismissedexception', 'totara_program'), 'notifymessage');
    echo $OUTPUT->single_button($exceptionurl, get_string('overrideandassign', 'totara_program'));
} else if (!empty($exceptions)) {
    echo $OUTPUT->notification(get_string('fixexceptionbeforeeditingcompletion', 'totara_program'), 'notifyproblem');
}

$historyformcustomdata = array(
    'id' => $id,
    'userid' => $userid,
    'history' => $history,
    'transactions' => $transactions,
);
$historyurl = new moodle_url('/totara/program/edit_completion_history.php', array('id' => $id, 'userid' => $userid));
$historyform = new prog_edit_completion_history_and_transactions_form($historyurl, $historyformcustomdata,
    'post', '', array('id' => 'form_prog_completion_history_and_transactions'));
$historyform->display();

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
