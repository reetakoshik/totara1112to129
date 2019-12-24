<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_appraisal
 */

global $CFG, $OUTPUT, $PAGE;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('manageappraisals');
require_capability('totara/appraisal:unlockstages', \context_system::instance());

$title = get_string('edit_current_stage', 'totara_appraisal');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$appraisalid = required_param('appraisalid', PARAM_INT);
$learnerid = required_param('learnerid', PARAM_INT);

list($currentdata, $params) =
    \totara_appraisal\form\edit_current_stage::get_current_data_and_params($appraisalid, $learnerid);

$form = new \totara_appraisal\form\edit_current_stage($currentdata, $params);

// Process form submission.
if ($form->is_cancelled()) {
    redirect(
        new moodle_url('/totara/appraisal/learners.php', array('appraisalid' => $appraisalid)),
        get_string('edit_current_stage_cancelled', 'totara_appraisal'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} else if ($data = $form->get_data()) {
    $roleassignmentid = $data->roleassignmentid;
    $stageid = $data->stageid;

    \totara_appraisal\current_stage_editor::set_stage_for_role_assignment(
        $appraisalid,
        $learnerid,
        $roleassignmentid,
        $stageid
    );

    redirect(
        new moodle_url('/totara/appraisal/learners.php', array('appraisalid' => $appraisalid)),
        get_string('edit_current_stage_completed', 'totara_appraisal'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo $form->render();

echo $OUTPUT->footer();
