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
 * @subpackage course
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot .'/cohort/lib.php');

require_login();
try {
    require_sesskey();
} catch (moodle_exception $e) {
    $error = array('error' => $e->getMessage());
    die(json_encode($error));
}

$instancetype = required_param('instancetype', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$module = optional_param('module', '', PARAM_TEXT);
$itemids = required_param('itemid', PARAM_SEQUENCE);
$itemids = explode(',', $itemids);

$contextsystem = context_system::instance();
// Check user capabilities.
if ($instancetype === COHORT_ASSN_ITEMTYPE_COURSE) {
    $context = context_course::instance($instanceid);
} else if ($instancetype === COHORT_ASSN_ITEMTYPE_CATEGORY) {
    $context = context_coursecat::instance($instanceid);
} else if ($instancetype === COHORT_ASSN_ITEMTYPE_PROGRAM ||
           $instancetype === COHORT_ASSN_ITEMTYPE_CERTIF) {
    $context = context_program::instance($instanceid);
} else {
    $context = $contextsystem;
}

if ((!has_capability('moodle/cohort:view', $context)) && (!has_capability('moodle/cohort:manage', $contextsystem))) {
    print_error('error:capabilitycohortview', 'totara_cohort');
}

$PAGE->set_context($context);
$PAGE->set_url('/totara/cohort/dialog/cohort_item.php');

if ($module === "course") {
    $ccohort = new totara_cohort_course_cohorts();
} else {
    $ccohort = new totara_cohort_goal_cohorts();
}

$items = array();
$rows = array();
$users = 0;
foreach ($itemids as $itemid) {
    $item = $ccohort->get_item(intval($itemid));
    $users += $ccohort->user_affected_count($item);

    $items[] = $item;
    $row = $ccohort->build_row($item);

    $rowhtml = html_writer::start_tag('tr');
    $colcount = 0;
    foreach ($row as $cell) {
        $rowhtml .= html_writer::tag('td', $cell, array('class' => 'cell'.$colcount));
        $colcount++;
    }
    $rowhtml .= html_writer::end_tag('tr');

    $rows[] = $rowhtml;
}

// Build the html to display in the confirmation dialog
$num = count($items);
$itemnames = '';
if ($num == 1) {
    $itemnames .= '"'.$items[0]->fullname.'"';
} else {
    for ($i = 0; $i < $num; $i++) {
        // If not last item
        if ($i == 0) {
            $itemnames .= ' "'.$items[$i]->fullname.'"';
        } else if ($i != $num-1) {
            $itemnames .= ', "'.$items[$i]->fullname.'"';
        } else {
            $itemnames .= ' and "'.$items[$i]->fullname.'"';
        }
    }
}
$a = new stdClass();
$a->itemnames = $itemnames;
$a->affectedusers = $users;
$html = get_string('youhaveadded', 'totara_cohort', $a);

$data = array(
'html'      => $html,
'rows'      => $rows
);

echo json_encode($data);
