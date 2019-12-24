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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/program/edit_completion_history_form.php');

// Check if programs are enabled.
check_program_enabled();

if (empty($CFG->enableprogramcompletioneditor)) {
    print_error('error:completioneditornotenabled', 'totara_program');
}

$id = required_param('id', PARAM_INT); // Program id.
$userid = required_param('userid', PARAM_INT);
$chid = optional_param('chid', 0, PARAM_INT); // Program completion history id, 0 indicates creating a new record.

require_login();

$program = new program($id);
$programcontext = $program->get_context();

require_capability('totara/program:editcompletion', $programcontext);

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$url = new moodle_url('/totara/program/edit_completion_history.php', array('id' => $id, 'userid' => $userid, 'chid' => $chid));

// Prepare the form.
$PAGE->set_program($program);
$customdata = array(
    'id' => $id,
    'userid' => $userid,
    'chid' => $chid);
$form = new prog_edit_completion_history_form($url, $customdata, 'post', '', array('name' => 'form_completion_history'));

// Process any actions submitted.
if ($form->is_cancelled()) {
    $url = new moodle_url('/totara/program/edit_completion.php', array('id' => $id, 'userid' => $userid));
    totara_set_notification(get_string('completionupdatecancelled', 'totara_program'), $url,
        array('class' => 'notifysuccess'));
}

if ($submitted = $form->get_data() and isset($submitted->savechanges)) {
    $url = new moodle_url('/totara/program/edit_completion.php', array('id' => $id, 'userid' => $userid));

    if ($chid) {
        // Verify that the chid to be updated belongs to the specified user and program.
        if (!$DB->record_exists('prog_completion_history', array('id' => $chid, 'programid' => $id, 'userid' => $userid))) {
            totara_set_notification(get_string('error:impossibledatasubmitted', 'totara_program'),
                $url,
                array('class' => 'notifyproblem'));
        }

        $updatedrecord = new stdClass();
        $updatedrecord->id = $chid;
        $updatedrecord->timecompleted = $submitted->timecompleted;
        $DB->update_record('prog_completion_history', $updatedrecord);

        // Record the change in the program completion log.
        $description = 'Completion history manually edited<br>' .
            '<ul><li>ID: ' . $chid . '</li>' .
            '<li>Completion date: ' . prog_format_log_date($updatedrecord->timecompleted) . '</li></ul>';
        prog_log_completion(
            $id,
            $userid,
            $description
        );

    } else {
        $newrecord = new stdClass();
        $newrecord->programid = $id;
        $newrecord->userid = $userid;
        $newrecord->status = STATUS_PROGRAM_COMPLETE;
        $newrecord->timecompleted = $submitted->timecompleted;
        $newrecord->timemodified = time();
        $newrecord->unassigned = 0;
        $newchid = $DB->insert_record('prog_completion_history', $newrecord);

        // Record the change in the program completion log.
        $description = 'Completion history manually added<br>' .
            '<ul><li>ID: ' . $newchid . '</li>' .
            '<li>Completion date: ' . prog_format_log_date($newrecord->timecompleted) . '</li></ul>';
        prog_log_completion(
            $id,
            $userid,
            $description
        );
    }

    totara_set_notification(get_string('completionchangessaved', 'totara_program'),
        $url,
        array('class' => 'notifysuccess'));
}

// Masquerade as the completion page for the sake of navigation.
$PAGE->navigation->override_active_url(new moodle_url('/totara/program/completion.php', array('id' => $id)));
// Add an item to the navbar to make it unique.
$PAGE->navbar->add(get_string('completionaddhistory', 'totara_program'));

// Set up the page.
$PAGE->set_url($url);
$PAGE->set_title($program->fullname);
$PAGE->set_heading($program->fullname);

// Display.
$heading = get_string('completionsforuserinprog', 'totara_program',
    array('user' => fullname($user), 'prog' => format_string($program->fullname)));

echo $OUTPUT->header();
echo $OUTPUT->container_start('editcompletion');
echo $OUTPUT->heading($heading);


$record = $DB->get_record('prog_completion_history', array('id' => $chid));
$currentformdata = new stdClass();
if ($record) {
    $currentformdata->timecompleted = $record->timecompleted;
} else {
    $currentformdata->timecompleted = null;
}

$form->set_data($currentformdata);
$form->display();

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
