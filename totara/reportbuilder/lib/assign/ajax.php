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

/**
 * User Paginator server-side processing
 */

define('AJAX_SCRIPT', true);

define('REPORTBUIDLER_MANAGE_REPORTS_PAGE', true);
define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', true); // We are setting up report here, do not accept source params.

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib/assign/lib.php');

$module = 'reportbuilder';
$itemid = required_param('itemid', PARAM_INT);
$suffix = required_param('suffix', PARAM_ALPHA);

require_sesskey();
require_capability('totara/reportbuilder:managereports', context_system::instance());
if (!in_array($suffix, array('record', 'user'))) {
    throw new coding_exception('Wrong assignment type for reportbuilder component.');
}

// Pagination variables.
$secho = required_param('sEcho', PARAM_INT);
$idisplaystart = optional_param('iDisplayStart', 0, PARAM_INT);
$idisplaylength = optional_param('iDisplayLength', 10, PARAM_INT);
$idisplaylength = ($idisplaylength == -1) ? null : $idisplaylength;
$ssearch = optional_param('sSearch', null, PARAM_TEXT);

$restriction = new rb_global_restriction($itemid);

$suffixstr = '_' . $suffix;
$assignclassname = "totara_assign_{$module}{$suffixstr}";
$assignclass = new $assignclassname($module, $restriction, $suffix);
$users = $assignclass->get_current_users($ssearch, $idisplaystart, $idisplaylength);
$igrandtotal = $assignclass->get_current_users_count();
$idisplaytotal = $assignclass->get_current_users_count($ssearch);
$emptyassignments = get_string('emptyassignments', 'totara_core');

// Since we only have one page we can save to an array.
$userdata = array();
foreach ($users as $user) {
    $userdata[$user->id] = $user;
}
$users->close();

// Get group info for the users on this page.
$groupinfo = $assignclass->get_group_assignedvia_details(array_keys($userdata));

$aadata = array();
foreach ($userdata as $userid => $user) {
    $url = new moodle_url('/user/view.php', array('id' => $user->id));
    $link = html_writer::link($url, fullname($user));

    $assignvia = array();
    if (!empty($groupinfo[$userid])) {
        foreach ($groupinfo[$userid] as $groupid => $groupstring) {
            $assignvia[] = $groupstring;
        }
    }
    if (empty($assignvia)) {
        $assignviastring = $emptyassignments;
    } else {
        $assignviastring = implode(', ', $assignvia);
    }

    $aadata[] = array($link, $assignviastring);
}

$output = array(
        "sEcho" => $secho,
        "iTotalRecords" => $igrandtotal,
        "iTotalDisplayRecords" => $idisplaytotal,
        "aaData" => $aadata
);
echo json_encode( $output );
