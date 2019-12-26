<?php
/*
 * This file is part of Totara LMS
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
require_once($CFG->libdir . '/adminlib.php');

$managercomponent = optional_param('managercomponent', '', PARAM_COMPONENT);
$manager = optional_param('manager', '', PARAM_ALPHANUMEXT);
$workflowcomponent = optional_param('workflowcomponent', '', PARAM_COMPONENT);
$workflow = optional_param('workflow', '', PARAM_ALPHANUMEXT);
$action = optional_param('action', '', PARAM_ALPHA);

admin_externalpage_setup('manageworkflows');

if ($action && $managercomponent && $manager && $workflowcomponent && $workflow) {
    require_sesskey();

    $classname = "{$workflowcomponent}\\workflow\\{$managercomponent}\\{$manager}\\{$workflow}";
    if (!class_exists($classname)) {
        print_error('error:noworkflowclass', 'totara_workflow', '', $classname);
    }
    /** @var \totara_workflow\workflow\base $workflow */
    $workflow = $classname::instance();
    if ($action == 'enable') {
        $workflow->enable();
        totara_set_notification(
            get_string('workflowxenabled', 'totara_workflow', $workflow->get_name()),
            $PAGE->url,
            ['class' => 'notifysuccess']
        );
    } else if ($action == 'disable') {
        $workflow->disable();
        totara_set_notification(
            get_string('workflowxdisabled', 'totara_workflow', $workflow->get_name()),
            $PAGE->url,
            ['class' => 'notifysuccess']
        );
    } else {
        print_error('error:invalidaction', 'totara_workflow', '', $action);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('manageworkflows', 'totara_workflow'));

$workflow_managers = \totara_workflow\workflow_manager\base::get_all_workflow_manager_classes();
$contextdata = [];
$contextdata['workflow_managers'] = [];
foreach ($workflow_managers as $workflow_manager) {
    /** @var \totara_workflow\workflow_manager\base $wm */
    $wm = new $workflow_manager();
    $contextdata['workflow_managers'][] = $wm->export_for_template($OUTPUT);
}
echo $OUTPUT->render_from_template('totara_workflow/manage_workflows', $contextdata);

echo $OUTPUT->footer();
