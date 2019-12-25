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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_plan.class.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_goals.class.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_required_learning.class.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_plan_evidence.class.php');
require_once($CFG->dirroot.'/totara/appraisal/lib.php');

// Check if Appraisals are enabled.
appraisal::check_feature_enabled();

$questionid = required_param('id', PARAM_INT);
$roleassignmentid = required_param('answerid', PARAM_INT);
$planid = optional_param('planid', 0, PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);
// Only return generated tree html.
$treeonly = optional_param('treeonly', false, PARAM_BOOL);
// Should we show hidden frameworks?
$showhidden = optional_param('showhidden', false, PARAM_BOOL);
// No javascript parameters.
$nojs = optional_param('nojs', false, PARAM_BOOL);

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/totara/appraisal/ajax/reviewselect.php', array(
    'id' => $questionid,
    'answerid' => $roleassignmentid,
    'subjectid' => $subjectid
)));

require_sesskey();
require_login(null, false, null, false, true);

$question = new appraisal_question($questionid);
$datatype = $question->get_element()->datatype;


$roleassignment = new appraisal_role_assignment($roleassignmentid);
if ($roleassignment->userid != $USER->id) {
    // This script is for the user in a given role assignment to select their own answer.
    // If they're the manager, then the manager's role assignment id should have been supplied.
    // So if the supplied role assignment is not for the current user, something's wrong.
    print_error('invalidaccess');
}

if ($planid == 0 && $datatype != 'goals' && $datatype != 'requiredlearning') {
    list($usql, $params) = $DB->get_in_or_equal([DP_PLAN_STATUS_APPROVED, DP_PLAN_STATUS_COMPLETE]);
    $params[] = $subjectid;
    $plan = $DB->get_record_select('dp_plan', "status {$usql} AND userid = ?", $params, '*', IGNORE_MULTIPLE);
    if ($plan) {
        $planid = $plan->id;
    } else {
        echo get_string('noobjectives', 'totara_appraisal');
        die;
    }
}

if (!$roleassignment) {
    echo get_string('noassignments', 'totara_appraisal');
} else {
    // Display page.
    if (!$nojs) {
        $alreadyselected = $question->get_element()->get_already_selected($planid);
        // Load dialog content generator.
        if ($datatype == 'goals') {
            $frameworkid = optional_param('frameworkid', goal::SCOPE_COMPANY, PARAM_INT);
            $dialog = new totara_dialog_content_goals($frameworkid, false, $subjectid);

            /* Determine which goal types should be selectable. If this dialog is being loaded then at least
               one type should be selectable, otherwise it throws an error. */
            $dialog->showcompany = $question->get_element()->can_select_company();
            $dialog->showpersonal = $question->get_element()->can_select_personal();
            if (!$dialog->showcompany && !$dialog->showpersonal) {
                print_error('error:goalselectionmustallowsomething', 'totara_question');
            } else if (!$dialog->showcompany && $frameworkid == goal::SCOPE_COMPANY) {
                $dialog->set_framework(goal::SCOPE_PERSONAL);
            } else if (!$dialog->showpersonal && $frameworkid == goal::SCOPE_PERSONAL) {
                $dialog->set_framework(goal::SCOPE_COMPANY);
            }

            $dialog->requireevidence = false;
            $dialog->disable_picker = !$dialog->display_picker;
            $dialog->load_items(0, $subjectid);
        } else if ($datatype == 'requiredlearning') {
            $dialog = new totara_dialog_content_required_learning($subjectid);
            $dialog->load_items();
        } else {
            if ($datatype == 'evidencefromplan') {
                $dialog = new totara_dialog_content_plan_evidence('evidence', $planid, $showhidden, $subjectid);
            } else {
                $dialog = new totara_dialog_content_plan($question->get_element()->component, $planid, $showhidden, $subjectid);
            }
            $dialog->load_items(0, DP_APPROVAL_APPROVED);
            $dialog->urlparams = array('planid' => $planid);
        }
        $dialog->searchparams = array('id' => $questionid, 'answerid' => $roleassignmentid, 'subjectid' => $subjectid);
        $dialog->show_treeview_only = $treeonly;
        $dialog->selected_title = 'itemstoadd';
        $dialog->disabled_items = $alreadyselected;

        $dialog->lang_file = 'totara_appraisal';
        $dialog->string_nothingtodisplay = 'error:dialognotreeitems' . $datatype;


        echo $dialog->generate_markup();
    }
}
