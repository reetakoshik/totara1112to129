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
 * @package totara_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once('HTML/QuickForm/Renderer/QuickHtml.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->dirroot . '/totara/certification/edit_completion_history_form.php');

// Check if certifications are enabled.
check_certification_enabled();

if (empty($CFG->enableprogramcompletioneditor)) {
    print_error('error:completioneditornotenabled', 'totara_program');
}

$id = required_param('id', PARAM_INT); // Program id.
$userid = required_param('userid', PARAM_INT);
$chid = optional_param('chid', 0, PARAM_INT); // Cert completion history id, 0 indicates creating a new record.

require_login();

$program = new program($id);
$programcontext = $program->get_context();

require_capability('totara/program:editcompletion', $programcontext);

$certification = $DB->get_record('certif', array('id' => $program->certifid));
$currentcompl = $DB->get_record('certif_completion', array('certifid' => $program->certifid, 'userid' => $userid));

if (!$certification) {
    print_error(get_string('nocertifdetailsfound', 'totara_certification'));
}

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$returnurl = new moodle_url('/totara/certification/edit_completion.php',
    array('id' => $id, 'userid' => $userid));
$PAGE->set_program($program);

if ($chid) {
    $thisurl = new moodle_url('/totara/certification/edit_completion_history.php',
        array('id' => $id, 'userid' => $userid, 'chid' => $chid));

    // Load all the data about the user and certification.
    $certcomplhistory = $DB->get_record('certif_completion_history', array('id' => $chid));

    if (empty($certcomplhistory) || $userid != $certcomplhistory->userid || $program->certifid != $certcomplhistory->certifid) {
        totara_set_notification(get_string('error:impossibledatasubmitted', 'totara_program'),
            $returnurl,
            array('class' => 'notifyproblem'));
    }
} else {
    $thisurl = new moodle_url('/totara/certification/edit_completion_history.php',
        array('id' => $id, 'userid' => $userid));

    // Set up new completion record. Default to "Certified" state, because that should be what people usually want to do.
    $now = time();
    $certcomplhistory = new stdClass();
    $certcomplhistory->id = 0;
    $certcomplhistory->certifid = $program->certifid;
    $certcomplhistory->userid = $userid;
    $certcomplhistory->certifpath = CERTIFPATH_RECERT;
    $certcomplhistory->status = CERTIFSTATUS_COMPLETED;
    $certcomplhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
    $certcomplhistory->timecompleted = $now;
    $certcomplhistory->timewindowopens = $now;
    $certcomplhistory->timeexpires = $now;
    $certcomplhistory->baselinetimeexpires = $now;
    $certcomplhistory->timemodified = time();
    $certcomplhistory->unassigned = 0;
}

$currentformdata = new stdClass();
$currentformdata->state = certif_get_completion_state($certcomplhistory);
$errors = certif_get_completion_errors($certcomplhistory, null);
$currentformdata->inprogress = ($certcomplhistory->status == CERTIFSTATUS_INPROGRESS) ? 1 : 0;
$currentformdata->status = $certcomplhistory->status;
$currentformdata->renewalstatus = $certcomplhistory->renewalstatus;
$currentformdata->certifpath = $certcomplhistory->certifpath;
$currentformdata->timecompleted = $certcomplhistory->timecompleted;
$currentformdata->timewindowopens = $certcomplhistory->timewindowopens;
$currentformdata->timeexpires = $certcomplhistory->timeexpires;
$currentformdata->baselinetimeexpires = $certcomplhistory->baselinetimeexpires;
$currentformdata->unassigned = $certcomplhistory->unassigned;

// Masquerade as the completion page for the sake of navigation.
$PAGE->navigation->override_active_url(new moodle_url('/totara/program/completion.php', array('id' => $id)));
// Add an item to the navbar to make it unique.
$PAGE->navbar->add(get_string('completionaddhistory', 'totara_program'));

$currentlyassigned = !empty($currentcompl) ? 1 : 0;
$editformcustomdata = array(
    'id' => $id,
    'userid' => $userid,
    'showinitialstateinvalid' => (($currentformdata->state == CERTIFCOMPLETIONSTATE_INVALID) || !empty($errors)),
    'certification' => $certification,
    'originalstate' => $currentformdata->state,
    'chid' => $chid,
    'assigned' => $currentlyassigned
);
$editform = new certif_edit_completion_history_form($thisurl, $editformcustomdata, 'post', '',
    array('id' => 'form_certif_completion'));

// Process any actions submitted.
if ($editform->is_cancelled()) {
    totara_set_notification(get_string('completionupdatecancelled', 'totara_program'), $returnurl,
        array('class' => 'notifysuccess'));
}

if ($submitted = $editform->get_data() and isset($submitted->savechanges)) {
    $certcomplhistory = certif_process_submitted_edit_completion_history($submitted);
    $newstate = certif_get_completion_state($certcomplhistory);
    $errors = certif_get_completion_errors($certcomplhistory, null);

    if ($newstate == CERTIFCOMPLETIONSTATE_INVALID || !empty($errors)) {
        totara_set_notification(get_string('error:impossibledatasubmitted', 'totara_program'),
            $thisurl,
            array('class' => 'notifyproblem'));
    }

    if ($certcomplhistory->id) {
        $message = 'Completion history manually edited';
    } else {
        $message = 'Completion history manually created';
    }
    if (certif_write_completion_history($certcomplhistory, $message)) {
        totara_set_notification(get_string('completionchangessaved', 'totara_program'),
            $returnurl,
            array('class' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('error:impossibledatasubmitted', 'totara_program'),
            $thisurl,
            array('class' => 'notifyproblem'));
    }
}

// Set up the page.
$PAGE->set_url($thisurl);
$PAGE->set_title($program->fullname);
$PAGE->set_heading($program->fullname);

// Display.
$heading = get_string('completionsforuserinprog', 'totara_program',
    array('user' => fullname($user), 'prog' => format_string($program->fullname)));

// Javascript includes.
// Init form core js before certification.
$args = $editform->_form->getLockOptionObject();
if (count($args[1]) > 0) {
    $PAGE->requires->js_init_call('M.form.initFormDependencies', $args, false, moodleform::get_js_module());
}
$jsmodule = array(
    'name' => 'totara_editcertcompletion',
    'fullpath' => '/totara/certification/edit_completion.js');
$PAGE->requires->js_init_call('M.totara_editcertcompletion.init', array(), false, $jsmodule);
$PAGE->requires->strings_for_js(
    array('notapplicable', 'perioddays', 'periodweeks', 'periodmonths', 'periodyears'), 'totara_certification');
$PAGE->requires->strings_for_js(
    array('bestguess'), 'totara_program');

echo $OUTPUT->header();
echo $OUTPUT->container_start('editcompletion');
echo $OUTPUT->heading($heading);

$editform->set_data($currentformdata);
$editform->validate_defined_fields(true);
$editform->display();

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
