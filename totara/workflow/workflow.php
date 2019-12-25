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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_workflow
 */

require('../../config.php');

$managercomponent = required_param('managercomponent', PARAM_COMPONENT);
$manager = required_param('manager', PARAM_ALPHANUMEXT);
$workflowcomponent = required_param('workflowcomponent', PARAM_COMPONENT);
$workflow = required_param('workflow', PARAM_ALPHANUMEXT);

$workflowclass = "{$workflowcomponent}\\workflow\\{$managercomponent}\\{$manager}\\{$workflow}";
if (!class_exists($workflowclass)) {
    print_error('error:noworkflowclass', 'totara_workflow', '', $workflowclass);
}

require_login();

$context = \context_system::instance();
$PAGE->set_context($context);
$pageparams = [
    'managercomponent' => $managercomponent,
    'manager' => $manager,
    'workflowcomponent' => $workflowcomponent,
    'workflow' => $workflow,
];
$PAGE->set_url(new \moodle_url('/totara/workflow/workflow.php', $pageparams));
$PAGE->set_pagelayout('noblocks');

/** @var \totara_workflow\workflow\base $workflow */
$workflow = $workflowclass::instance();
$params = $workflow->get_workflow_manager_data();
$workflow->set_params($params);

if (!$workflow->is_available()) {
    print_error('accessdenied', 'admin');
    die;
}

$formclass = $workflow->get_form_name();
$currentdata = $workflow->get_current_data();
/** @var \totara_workflow\form\workflow_form $form */
$form = new $formclass($currentdata, ['workflow' => $workflow]);

if ($data = $form->get_data()) {
    $files = (array)$form->get_files();
    $workflow->process_form($data, $files);
    die;
}

echo $OUTPUT->header();

echo $OUTPUT->heading($workflow->get_name());

echo $form->render();

echo $OUTPUT->footer();
