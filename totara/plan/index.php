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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

$planuser = optional_param('userid', $USER->id, PARAM_INT); // show plans for this user

// Permission checks.
$role = ($planuser == $USER->id) ? 'learner' : 'manager';
$can_access = dp_can_view_users_plans($planuser);
$can_manage = dp_can_manage_users_plans($planuser);
$can_create = has_capability('totara/plan:manageanyplan', \context_system::instance()) ? true : dp_role_is_allowed_action($role, 'create');

if (!$can_access) {
    print_error('error:nopermissions', 'totara_plan');
}

//
// Display plan list
//
$context = context_system::instance();
$PAGE->set_url('/totara/plan/index.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

if ($role == 'manager') {
    $PAGE->set_totara_menu_selected('\totara_core\totara\menu\myteam');
} else {
    $PAGE->set_totara_menu_selected('\totara_plan\totara\menu\learningplans');
}

$heading = get_string('learningplans', 'totara_plan');
$pagetitle = get_string('learningplans', 'totara_plan');

dp_get_plan_base_navlinks($planuser);

// Plan menu
dp_display_plans_menu($planuser,0,$role);

$PAGE->set_title($heading);
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

if ($planuser != $USER->id) {
    echo dp_display_user_message_box($planuser);
}

echo $OUTPUT->heading($heading);

echo $OUTPUT->container_start('', 'dp-plans-description');

if ($planuser == $USER->id) {
    $planinstructions = get_string('planinstructions', 'totara_plan') . ' ';
} else {
    $user = $DB->get_record('user', array('id' => $planuser));
    $userfullname = fullname($user);
    $planinstructions = get_string('planinstructionsuser', 'totara_plan', $userfullname) . ' ';
}
if ($can_manage && $can_create) {
    $planinstructions .= get_string('planinstructions_add', 'totara_plan');
} else {
    $planinstructions .= get_string('planinstructions_noadd', 'totara_plan');
}

\totara_plan\event\plan_list_viewed::create_from_userid($planuser)->trigger();

echo html_writer::tag('p', $planinstructions, array('class' => 'instructional_text'));

if ($can_manage && $can_create) {
    $renderer = $PAGE->get_renderer('totara_plan');
    echo $renderer->print_add_plan_button($planuser);
}
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('', 'dp-plans-list-active-plans');
echo dp_display_plans($planuser, array(DP_PLAN_STATUS_APPROVED), array('enddate', 'status'), get_string('activeplans', 'totara_plan'));
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('', 'dp-plans-list-unapproved-plans');
echo dp_display_plans($planuser, array(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_STATUS_PENDING),
    array('status'), get_string('unapprovedplans', 'totara_plan'));
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('', 'dp-plans-list-completed-plans');
echo dp_display_plans($planuser, DP_PLAN_STATUS_COMPLETE, array('completed'), get_string('completedplans', 'totara_plan'));
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
