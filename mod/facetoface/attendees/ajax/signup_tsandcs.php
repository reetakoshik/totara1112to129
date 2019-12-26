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
 *
 * @package modules
 * @subpackage facetoface
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$s = required_param('s', PARAM_INT); // Facetoface session ID.

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

$PAGE->set_context($context);

if ($facetoface->approvaltype != \mod_facetoface\seminar::APPROVAL_SELF) {
    // This should not happen unless there is a concurrent change of settings.
    print_error('error');
}

/** @var enrol_totara_facetoface_plugin $enrol */
$enrol = enrol_get_plugin('totara_facetoface');
if (in_array($s, array_keys($enrol->get_enrolable_sessions($course->id)))) {
    // F2f direct enrolment is enabled for this session.
    require_login();
} else {
    // F2f direct enrolment is not enabled here, the user must have the ability to sign up for sessions
    // in this f2f as normal.
    require_login($course, false, $cm);
    require_capability('mod/facetoface:view', $context);
}

$mform = new \mod_facetoface\form\signup_tsandcs(null, array('tsandcs' => $facetoface->approvalterms, 's' => $s));

// This should be json_encoded, but for now we need to use html content
// type to not break $.get().
header('Content-type: text/html; charset=utf-8');
$mform->display();
