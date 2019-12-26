<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author  David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');

use \mod_facetoface\seminar_event;
use \mod_facetoface\signup;
use \mod_facetoface\signup_helper;

// The debug page is only for site administrators at the moment.
if (!is_siteadmin()) {
    throw new moodle_exception("Debug Access Permission Denied");
}

$userid = required_param('uid', PARAM_INT);
$eventid = required_param('event', PARAM_INT);

// Check that there is a user in the database matching the userid.
// Note: There is no need to check if user is deleted etc, the state debug will do that.
if (!$user = $DB->get_record('user', ['id' => $userid])) {
    throw new Exception("Invalid userid");
}

$seminarevent = new seminar_event($eventid);
$signup = signup::create($userid, $seminarevent, 0);

$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$PAGE->set_cm($cm);
$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/attendees/debug.php', ['uid' => $userid, 'event' => $eventid]);
$PAGE->set_heading('Seminar Debugging');

echo $OUTPUT->header();

$signup->debug_state_transitions();

echo $OUTPUT->footer();
