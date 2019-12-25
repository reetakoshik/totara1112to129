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
 * @subpackage totara_feedback360
 */

/**
 * User Paginator server-side processing
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/feedback360/lib.php');
require_once($CFG->dirroot.'/totara/feedback360/lib/assign/lib.php');

require_login();
require_sesskey();
require_capability('totara/feedback360:viewassignedusers', context_system::instance());

// Check if 360 Feedbacks are enabled.
feedback360::check_feature_enabled();

$module = required_param('module', PARAM_COMPONENT);
if ($module !== 'feedback360') {
    throw new invalid_parameter_exception("Invalid module name, must be feedback360");
}

$itemid = required_param('itemid', PARAM_INT);

// Pagination variables.
$secho = required_param('sEcho', PARAM_INT);
$idisplaystart = optional_param('iDisplayStart', 0, PARAM_INT);
$idisplaylength = optional_param('iDisplayLength', 10, PARAM_INT);
$idisplaylength = ($idisplaylength == -1) ? null : $idisplaylength;
$ssearch = optional_param('sSearch', null, PARAM_TEXT);
$feedback360 = new $module($itemid);
$assignclassname = "totara_assign_{$module}";
$assignclass = new $assignclassname($module, $feedback360);
$users = $assignclass->get_current_users($ssearch, $idisplaystart, $idisplaylength);
$igrandtotal = $assignclass->get_current_users_count();
$idisplaytotal = $assignclass->get_current_users_count($ssearch);

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
    if (isset($groupinfo[$userid])) {
        foreach ($groupinfo[$userid] as $groupid => $groupstring) {
            $assignvia[] = $groupstring;
        }
    }
    $assignviastring = implode(', ', $assignvia);

    $aadata[] = array($link, $assignviastring);
}

$output = array(
        "sEcho" => $secho,
        "iTotalRecords" => $igrandtotal,
        "iTotalDisplayRecords" => $idisplaytotal,
        "aaData" => $aadata
);
echo json_encode($output);
