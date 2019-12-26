<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$datetimestart = required_param('datetimestart', PARAM_SEQUENCE); // All time start.
$datetimefinish = required_param('datetimefinish', PARAM_SEQUENCE); // All time finish.
$s = required_param('s', PARAM_INT); // Facetoface session ID.
$datetimestart = explode(',', $datetimestart);
$datetimefinish = explode(',', $datetimefinish);
$cntdates = count($datetimestart);

require_sesskey();

// Confirm dates are present.
if ($cntdates === 0 || empty($datetimestart) || empty($datetimefinish)) {
    print_error('error:nodatesfound', 'facetoface');
}

// Confirm all dates have their timestart and timefinish.
if (count($datetimefinish) != $cntdates) {
    print_error('error:mismatchdatesdetected', 'facetoface');
}

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

// Check essential permissions.
require_course_login($course, true, $cm);
// Check if the user can see this information.
require_capability('mod/facetoface:addattendees', $context);

// Extra where.
$extrawhere = '';
$extraparams = array();
if (!empty($s)) {
    $extrawhere = ' AND s.id != ?';
    $extraparams[] = $s;
}

$attendees = facetoface_get_attendees($s);
for ($i = 0; $i < $cntdates; $i++) {
    $date = new stdClass();
    $date->timestart = $datetimestart[$i];
    $date->timefinish = $datetimefinish[$i];
    $dates[] = $date;
}
$conflictresults = facetoface_get_booking_conflicts($dates, $attendees, $extrawhere, $extraparams);
$table = new html_table();
$table->head = array(get_string('bulkaddsourceidnumber', 'facetoface'), get_string('name'), get_string('result', 'facetoface'));
$table->data = array();

foreach ($conflictresults as $key => $result) {
    $idnumber = new html_table_cell(s($result['idnumber']));
    $name = new html_table_cell($result['name']);
    $message = new html_table_cell($result['result']);
    $row = new html_table_row(array($idnumber, $name, $message));
    $table->data[] = $row;
}

echo $OUTPUT->render($table);
