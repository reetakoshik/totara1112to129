<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara_dashboard
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot .'/totara/dashboard/dashboard_forms.php');

require_login();
try {
    require_sesskey();
} catch (moodle_exception $e) {
    $error = array('error' => $e->getMessage());
    die(json_encode($error));
}

$itemids = required_param('itemid', PARAM_SEQUENCE);
$itemids = explode(',', $itemids);

// Check user capabilities.
$contextsystem = context_system::instance();
if ((!has_capability('moodle/cohort:view', $contextsystem)) && (!has_capability('moodle/cohort:manage', $contextsystem))) {
    print_error('error:capabilitycohortview', 'totara_cohort');
}

$PAGE->set_context($contextsystem);
$PAGE->set_url('/totara/dashboard/dialog/cohort_item.php');

$cohort = new totara_cohort_dashboard_cohorts();

$items = array();
$rows = array();
$users = 0;
foreach ($itemids as $itemid) {
    $item = $cohort->get_item(intval($itemid));
    $users += $cohort->user_affected_count($item);

    $items[] = $item;
    $row = $cohort->build_row($item);

    $rowhtml = html_writer::start_tag('tr');
    $colcount = 0;
    foreach ($row as $cell) {
        $rowhtml .= html_writer::tag('td', $cell, array('class' => 'cell'.$colcount));
        $colcount++;
    }
    $rowhtml .= html_writer::end_tag('tr');

    $rows[] = $rowhtml;
}

$data = array('rows' => $rows);

echo json_encode($data);
