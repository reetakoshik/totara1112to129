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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');
require_once($CFG->dirroot.'/totara/cohort/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/goal/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/goal/assign/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/organisation/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
require_once($CFG->dirroot.'/totara/core/lib/assign/lib.php');

// Params.

// Assign to id.
$assignto = required_param('assignto', PARAM_INT);

// Assignment module type 'pos/org/cohort'.
$assigntype = required_param('assigntype', PARAM_INT);

// Parent id.
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id.
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html.
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// Include child hierarchy items?
$includechildren = optional_param('includechildren', false, PARAM_BOOL);

require_login();
$context = context_system::instance();

$manager = false;
$self = false;

if ($assigntype == GOAL_ASSIGNMENT_INDIVIDUAL) {
    $user_context = context_user::instance($assignto);
    $manager = \totara_job\job_assignment::is_managing($USER->id, $assignto) && has_capability('totara/hierarchy:managestaffcompanygoal', $user_context);
    $self = $USER->id == $assignto && has_capability('totara/hierarchy:manageowncompanygoal', $user_context);
    $admin = has_capability('totara/hierarchy:managegoalassignments', $user_context);
} else {
    // You must have some form of managegoals permission to see this page.
    $admin = has_capability('totara/hierarchy:managegoalassignments', $context);
}

if (!($admin || $manager || $self)) {
    print_error('error:findgoals', 'totara_hierarchy');
}

// Check if Goals are enabled.
goal::check_feature_enabled();


$strfindgoals = get_string('findgoals', 'totara_hierarchy');

// String of params needed in non-js url strings.
$urlparams = array('assignto' => $assignto,
                   'assigntype' => $assigntype,
                   'frameworkid' => $frameworkid,
                   'includechildren' => $includechildren,
                   'sesskey' => sesskey());

$type = goal::goal_assignment_type_info($assigntype);

// Set up the page.
$PAGE->set_url(new moodle_url('/totara/hierarchy/prefix/goal/assign/find.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_totara_menu_selected('\totara_hierarchy\totara\menu\mygoals');
$PAGE->set_title($strfindgoals);
$PAGE->set_heading($strfindgoals);

// Set up some variables.
if ($assigntype == GOAL_ASSIGNMENT_INDIVIDUAL) {
    // Things have to be done slightly differently for individuals.
    $sql = "SELECT ga.*, g.fullname
            FROM {goal_user_assignment} ga
                LEFT JOIN {goal} g
                ON g.id = ga.goalid
            WHERE ga.userid = ?
            AND ga.assigntype = ?";

    if (!$currentlyassigned = $DB->get_records_sql($sql, array($assignto, $assigntype))) {
        $currentlyassigned = array();
    }
} else {
    $module = new $type->fullname();

    // Load module item.
    if (!$moditem = $module->get_item($assignto)) {
        // Throw a fit if the item does not exist.
        print_error($type->fullname . 'notfound', 'totara_hierarchy');
    }

    // Currently assigned goals.
    if (!$currentlyassigned = goal::get_modules_assigned_goals($assigntype, $assignto)) {
        $currentlyassigned = array();
    }
}

// Currently assigned goals formatted for dialog handler.
$already_assigned = array();
foreach ($currentlyassigned as $assigned) {
    $assignment = new stdClass();
    $assignment->id = $assigned->goalid;
    $assignment->fullname = $assigned->fullname;
    $already_assigned[$assignment->id] = $assignment;

}

// Load dialog content generator.
if ($assigntype == GOAL_ASSIGNMENT_POSITION) {
    $dialog = new pos_goal_assign_ui_picker_hierarchy('goal', $frameworkid);
} else if ($assigntype == GOAL_ASSIGNMENT_ORGANISATION) {
    $dialog = new org_goal_assign_ui_picker_hierarchy('goal', $frameworkid);
} else {
    $dialog = new totara_dialog_content_hierarchy_multi('goal', $frameworkid, false, true);
}

// Toggle treeview only display.
$dialog->show_treeview_only = $treeonly;

// Load items to display.
$dialog->load_items($parentid);

// Set disabled items.
$dialog->disabled_items = $already_assigned;

// Set title.
$dialog->selected_title = 'itemstoadd';

if (isset($includechildren)) {
    $dialog->includechildren = $includechildren;
}

// Additional url parameters.
$dialog->urlparams = array('assignto' => $assignto, 'assigntype' => $assigntype, 'includechildren' => $includechildren);

// Display.
echo $dialog->generate_markup();

