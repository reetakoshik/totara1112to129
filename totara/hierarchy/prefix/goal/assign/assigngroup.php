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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

/**
 * Test assignment class library - this file is called by the javascript module passing the module, itemid, grouptype
 * and action (add, remove, edit) and then calls the correct class functions to provide/collect content from the dialog
 */
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/goal/assign/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/goal/lib.php');

$module = required_param('module', PARAM_COMPONENT);
if ($module !== 'goal') {
    throw new invalid_parameter_exception("Invalid module name, must be goal");
}

$grouptype = required_param('grouptype', PARAM_ALPHA);
$itemid = required_param('itemid', PARAM_INT);
$add = optional_param('add', false, PARAM_BOOL);

require_login();
require_sesskey();

// Check if Goals are enabled.
goal::check_feature_enabled();

$sitecontext = context_system::instance();
require_capability("totara/hierarchy:managegoalassignments", $sitecontext);

$hierarchy = hierarchy::load_hierarchy($module);
if (!$item = $hierarchy->get_item($itemid)) {
    print_error('error:invaliditemid', 'totara_hierarchy');
}

$PAGE->set_context($sitecontext);

// Determine if this assignment is allowed, instantiate the appropriate group class.
$grouptypes = totara_assign_goal::get_assignable_grouptypes();
if (!in_array($grouptype, $grouptypes)) {
    $a = new stdClass();
    $a->grouptype = $grouptype;
    $a->module = $module;
    print_error('error:assignmentgroupnotallowed', 'totara_core', null, $a);
}

$baseclass = new totara_assign_goal($module, $item);
$grouptypeobj = $baseclass->load_grouptype($grouptype);

// Handle new assignments.
$urlparams = array('module' => $module, 'grouptype' => $grouptype, 'itemid' => $itemid, 'add' => $add);
if ($add) {
    $out = '';
    // Is there any valid data?
    $listofvalues = required_param('selected', PARAM_SEQUENCE);
    if (!empty($listofvalues) && $grouptypeobj->validate_item_selector($listofvalues)) {
        $urlparams['includechildren'] = optional_param('includechildren', false, PARAM_BOOL);
        $urlparams['listofvalues'] = explode(',', $listofvalues);
        $grouptypeobj->handle_item_selector($urlparams);
        // Get the fully updated list of all assignments.
        $currentassignments = $baseclass->get_current_assigned_groups();
        // Pass to the module renderer.
        $output = $PAGE->get_renderer("totara_hierarchy");
        $out .= $output->goal_view_assignments($item, true, $currentassignments, true);

    }
    echo "DONE$out";
    exit();
}

// Display the dialog.
$grouptypeobj->generate_item_selector($urlparams);
