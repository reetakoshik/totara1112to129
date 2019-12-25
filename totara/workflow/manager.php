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

require(__DIR__ . '/../../config.php');

$component = required_param('component', PARAM_COMPONENT);
$manager = required_param('manager', PARAM_ALPHANUMEXT);

$managerclass = "{$component}\\workflow_manager\\{$manager}";
if (!class_exists($managerclass)) {
    print_error('error:nomanagerclass', 'totara_workflow', '', $managerclass);
}

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$pageparams = [
    'component' => $component,
    'manager' => $manager,
];
$PAGE->set_url(new \moodle_url('/totara/workflow/manager.php', $pageparams));
$PAGE->set_pagelayout('noblocks');

/** @var \totara_workflow\workflow_manager\base $wm */
$wm = new $managerclass();
$params = $wm->get_workflow_manager_data();
$wm->set_params($params);
$workflows = $wm->get_workflows(false);

// Security note: $wm->get_workflows(false) returns empty array when user cannot access the workflow manager.

if (count($workflows) === 0) {
    print_error('error:noworkflows', 'totara_workflow');
}

if (count($workflows) === 1) {
    $workflow = reset($workflows);
    redirect($workflow->get_url());
}

$data['workflows'] = [];
foreach ($workflows as $workflow) {
    $data['workflows'][] = $workflow->export_for_template($OUTPUT);
}

echo $OUTPUT->header();

echo $OUTPUT->heading($wm->get_name());

$template = $wm->get_workflow_template();
echo $OUTPUT->render_from_template($template, $data);

echo $OUTPUT->footer();
