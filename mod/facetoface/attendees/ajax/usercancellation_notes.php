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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package totara
 * @subpackage facetoface
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$userid    = required_param('userid', PARAM_INT); // Facetoface signup user ID.
$sessionid = required_param('s', PARAM_INT); // Facetoface session ID.

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($sessionid);

// Check essential permissions.
require_login($course, true, $cm);

if (!has_capability('mod/facetoface:manageattendeesnote', $context)) {
    print_error('nopermissions', 'error', '', 'Showing cancellation note');
}

/* @var mod_facetoface_renderer|core_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_facetoface');
$renderer->setcontext($context);

// Get custom field values of the cancellation.
$cancellationnote = facetoface_get_attendee($sessionid, $userid);
$cancellationnote->userid = $cancellationnote->id;
$cancellationnote->id = $cancellationnote->submissionid;
$cancellationnote->sessionid = $sessionid;
$customfields = customfield_get_data($cancellationnote, 'facetoface_cancellation', 'facetofacecancellation');

// Prepare output.
$usernamefields = get_all_user_name_fields(true);
$user = $DB->get_record('user', array('id' => $userid), "{$usernamefields}");
$output = get_string('usercancellationnoteheading', 'facetoface', fullname($user));
$output .= html_writer::empty_tag('hr');
if (!empty($customfields)) {
    foreach ($customfields as $cftitle => $cfvalue) {
        $output .= html_writer::tag('strong', str_replace(' ', '&nbsp;', $cftitle) . ': ') . html_writer::span($cfvalue);
        $output .= html_writer::empty_tag('br');
    }
} else {
    $output .= get_string('none');
}
$output .= html_writer::empty_tag('hr');
$output .= $renderer->single_button(
    new moodle_url('/mod/facetoface/attendees/edit_usercancellation_notes.php', array('userid' => $userid, 's' => $sessionid, 'sesskey' => sesskey())),
    get_string('edit'),
    'post'
);

header('Content-type: text/html; charset=utf-8');
echo $output;
