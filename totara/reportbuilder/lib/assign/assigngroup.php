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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

define('REPORTBUIDLER_MANAGE_REPORTS_PAGE', true);
define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', true); // We are setting up report here, do not accept source params.

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot."/totara/reportbuilder/lib.php");
require_once($CFG->dirroot.'/totara/reportbuilder/lib/assign/lib.php');

$suffix = optional_param('suffix', '', PARAM_ALPHAEXT);
$grouptype = required_param('grouptype', PARAM_ALPHA);
$itemid = required_param('itemid', PARAM_INT);
$add = optional_param('add', false, PARAM_BOOL);

$context = context_system::instance();

require_sesskey();
require_capability('totara/reportbuilder:managereports', $context);
if (!in_array($suffix, array('record', 'user'))) {
    throw new coding_exception('Wrong assignment type for reportbuilder component.');
}
$module = 'reportbuilder';

$urlparams = array(
    'module' => $module,
    'suffix' => $suffix,
    'grouptype' => $grouptype,
    'itemid' => $itemid,
    'add' => $add,
);
$PAGE->set_context($context);

// Try to find the module class library.
$suffixstr = strlen($suffix) ? '_' . $suffix : '';

$assignclassname = "totara_assign_{$module}{$suffixstr}";

$restriction = new rb_global_restriction($itemid);
$assignclass = new $assignclassname($module, $restriction);
$grouptypes = $assignclassname::get_assignable_grouptypes();
if (!in_array($grouptype, $grouptypes)) {
    $a = new stdClass();
    $a->grouptype = $grouptype;
    $a->module = $module;
    print_error('error:assignmentgroupnotallowed', 'totara_core', null, $a);
}
$grouptypeobj = $assignclass->load_grouptype($grouptype);

// Handle new assignments.
if ($add) {
    $out = '';
    // Is there any valid data?
    $listofvalues = required_param('selected', PARAM_SEQUENCE);
    $includechildren = optional_param('includechildren', false, PARAM_BOOL);

    if (!empty($listofvalues) && $grouptypeobj->validate_item_selector($listofvalues)) {
        $urlparams['includechildren'] = $includechildren;
        $urlparams['listofvalues'] = explode(',', $listofvalues);
        $grouptypeobj->handle_item_selector($urlparams);
        // Get the fully updated list of all assignments.
        $currentassignments = $assignclass->get_current_assigned_groups();
        /* @var totara_reportbuilder_renderer|core_renderer $output */
        $output = $PAGE->get_renderer("totara_reportbuilder");
        $out .= $output->display_assigned_groups($currentassignments, $itemid, $suffix);
    }
    echo "DONE{$out}";
    exit();
}

// Display the dialog.
$grouptypeobj->generate_item_selector($urlparams);
