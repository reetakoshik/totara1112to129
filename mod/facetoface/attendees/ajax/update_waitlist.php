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
 * @author Andrew Davidson <andrew.davidson@synergy-learning.com>
 * @package mod_facetoface
 */
/**
 * This class is an ajax back-end for updating attendance
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$data = required_param('datasubmission', PARAM_SEQUENCE);

$data = explode(',', $data);

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($sessionid);
// Check essential permissions.
require_course_login($course, true, $cm);
require_capability('mod/facetoface:takeattendance', $context);
require_sesskey();

$result = array('result' => 'failure', 'content' => '');
switch($action) {
    case 'confirmattendees':
        $errors = facetoface_confirm_attendees($sessionid, $data);
        if (empty($errors)) {
            $result['result'] = 'success';
        } else {
            $result['result'] = 'failure';
            $errormsgs = [];
            foreach ($errors as $userid => $error) {
                $user = $DB->get_record('user', ['id' => $userid]);
                $errormsgs[] = get_string('error:cannotchangestateuser', 'mod_facetoface',
                    (object)['user'=> fullname($user), 'error' => $error]);
            }
            $result['content'] = html_writer::alist($errormsgs);
        }
        break;
    case 'cancelattendees':
        facetoface_cancel_attendees($sessionid, $data);
        $result['result'] = 'success';
        break;
    case 'playlottery':
        facetoface_waitlist_randomly_confirm_users($sessionid, $data);
        $result['result'] = 'success';
        break;
    case 'checkcapacity':
        $seminar_event = new \mod_facetoface\seminar_event($sessionid);
        $signupcount = facetoface_get_num_attendees($seminar_event->get_id());

        if (($signupcount + count($data)) > $seminar_event->get_capacity()) {
            $result['result'] = 'overcapacity';
        } else {
            $result['result'] = 'undercapacity';
        }
        echo json_encode($result);
        die();
        break;
}
$attendees = facetoface_get_attendees($sessionid, $status = array(\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\user_cancelled::get_code()));
$result['attendees'] = array_keys($attendees);
echo json_encode($result);