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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage plan
 */

/**
 * Page for adding a plan
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/plan/edit_form.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

global $USER;

require_login();

$userid = required_param('userid', PARAM_INT); // user id
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/totara/plan/add.php', array('userid' => $userid));
$PAGE->set_pagelayout('report');
$ownplan = ($userid == $USER->id);
$menuitem = ($ownplan) ? '\totara_plan\totara\menu\learningplans' : '\totara_core\totara\menu\myteam';
$PAGE->set_totara_menu_selected($menuitem);

///
/// Permission checks
///

$role = $ownplan ? 'learner' : 'manager';
$can_manage = dp_can_manage_users_plans($userid);
$can_create = has_capability('totara/plan:manageanyplan', \context_system::instance()) ? true : dp_role_is_allowed_action($role, 'create');

if (!$can_manage || !$can_create) {
    print_error('error:nopermissions', 'totara_plan');
}

///
/// Data and actions
///
$currenturl = qualified_me();
$allplansurl = "{$CFG->wwwroot}/totara/plan/index.php?userid={$userid}";

$obj = new stdClass();
$obj->id = 0;
$obj->description = '';
$obj->descriptionformat = FORMAT_HTML;
$obj = file_prepare_standard_editor($obj, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                    'totara_plan', 'dp_plan', $obj->id);

$form = new plan_edit_form($currenturl, array('action' => 'add', 'role' => $role, 'can_manage' => true));
$form->set_data(array('userid' => $userid));

if ($form->is_cancelled()) {
    redirect($allplansurl);
}

// Handle form submit
if ($data = $form->get_data()) {
    if (isset($data->submitbutton)) {
        $transaction = $DB->start_delegated_transaction();
        // Set up the plan
        $newid = $DB->insert_record('dp_plan', $data);
        $data->id = $newid;
        $plan = new development_plan($newid);
        // Update plan status and plan history
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_CREATE);

        $components = $plan->get_components();

        foreach ($components as $componentname => $stuff) {
            $component = $plan->get_component($componentname);
            if ($component->get_setting('enabled')) {

                // Automatically add items from this component
                $component->plan_create_hook();
            }

            //Free memory
            unset($component);
        }

        $transaction->allow_commit();

        // Send out a notification?
        if ($plan->is_active()) {
            if ($role == 'manager') {
                $manager = clone($USER);
                $a = new stdClass();
                $a->plan = format_string($plan->name);
                $a->manager = fullname($manager);
                $plan->send_alert_to_learner($manager, 'learningplan-update.png','plan-add-learner-short','plan-add-learner-long', $a);
            }
        }
        $data = file_postupdate_standard_editor($data, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_plan', 'dp_plan', $data->id);
        $DB->set_field('dp_plan', 'description', $data->description, array('id' => $data->id));
        $viewurl = "{$CFG->wwwroot}/totara/plan/view.php?id={$newid}";
        \totara_plan\event\plan_created::create_from_plan($plan)->trigger();

        // Free memory
        unset($plan);

        totara_set_notification(get_string('plancreatesuccess', 'totara_plan'), $viewurl, array('class' => 'notifysuccess'));
    }
}


///
/// Display
///
$heading = get_string('createnewlearningplan', 'totara_plan');
$pagetitle = format_string(get_string('learningplan', 'totara_plan').': '.$heading);
dp_get_plan_base_navlinks($userid);
$PAGE->navbar->add($heading);

$templates = dp_get_templates();
foreach ($templates as $template) {
    $template->enddate = usergetdate($template->enddate);
}
$args = array('args' => $templates);

$PAGE->requires->js_call_amd('totara_plan/templates', 'init', $args);

// Plan menu
dp_display_plans_menu($userid);

$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

// Plan page content
echo $OUTPUT->container_start('', 'dp-plan-content');

if ($USER->id != $userid) {
    echo dp_display_user_message_box($userid);
}

echo $OUTPUT->heading($heading);

echo html_writer::tag('p', get_string('createplan_instructions', 'totara_plan'));

$form->display();

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
