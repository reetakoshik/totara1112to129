<?php
/**
 *
 * @author  Piers Harding  piers@catalyst.net.nz
 * @version 0.0.1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local
 * @subpackage message
 *
 */

defined('MOODLE_INTERNAL') || die();

// We need some constants.
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

use \mod_facetoface\signup\state\{booked, requestedadmin, waitlisted, declined};

/**
* Extend the base plugin class
* This class contains the action for facetoface onaccept/onreject message processing
*/
class totara_message_workflow_facetoface extends totara_message_workflow_plugin_base {

    /**
     * Action called on accept for face to face action
     *
     * @param array $eventdata
     * @param object $msg
     */
    function onaccept($eventdata, $msg) {
        global $DB;

        // Load course
        $userid = $eventdata['userid'];
        $session = $eventdata['session'];
        $seminarevent = new \mod_facetoface\seminar_event($session->id);
        $facetoface = $eventdata['facetoface'];
        if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
            print_error('error:coursemisconfigured', 'facetoface');
            return false;
        }
        if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
            return false;
        }
        $signup = \mod_facetoface\signup::create($userid, $seminarevent);
        if ($signup->can_switch(booked::class, waitlisted::class, requestedadmin::class)) {
            $signup->switch_state(booked::class, waitlisted::class, requestedadmin::class);
            totara_set_notification(get_string('attendancerequestsupdated', 'mod_facetoface'), null, ['class' => 'notifysuccess']);
        } else {
            $errors = $signup->get_failures(booked::class, waitlisted::class, requestedadmin::class);
            totara_set_notification(current($errors), null, ['class' => 'notifyproblem']);
            return false;
        }

        // issue notification that registration has been accepted
        return $this->acceptreject_notification($userid, $facetoface, $session, 'status_approved');
    }


    /**
     * Action called on reject of a face to face action
     *
     * @param array $eventdata
     * @param object $msg
     */
    function onreject($eventdata, $msg) {
        global $DB;
        // can manipulate the language by setting $SESSION->lang temporarily
        // Load course
        $userid = $eventdata['userid'];
        $session = $eventdata['session'];
        $facetoface = $eventdata['facetoface'];
        if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
            print_error('error:coursemisconfigured', 'facetoface');
            return false;
        }
        if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
            return false;
        }
        $seminarevent = new \mod_facetoface\seminar_event($session->id);
        $signup = \mod_facetoface\signup::create($userid, $seminarevent);
        if (!$signup->can_switch(declined::class)) {
            // Return false here for the compability with the old behaviour, no point to sending email if the target,
            // user is not able to switch state.
            $errors = $signup->get_failures(declined::class);
            totara_set_notification(current($errors), null, ['class' => 'notifyproblem']);
            return false;
        }
        $signup->switch_state(declined::class);
        totara_set_notification(get_string('attendancerequestsupdated', 'mod_facetoface'), null, ['class' => 'notifysuccess']);

        // issue notification that registration has been declined
        return $this->acceptreject_notification($userid, $facetoface, $session, 'status_declined');
    }

    /**
     * Send the accept or reject notification to the user
     *
     * @param int $userid
     * @param object $facetoface
     * @param object $session
     * @param string $langkey
     */
    private function acceptreject_notification($userid, $facetoface, $session, $langkey) {
        global $CFG, $DB, $USER;

        $stringmanager = get_string_manager();
        $newevent = new stdClass();
        $newevent->userfrom    = NULL;
        $user = $DB->get_record('user', array('id' => $userid));
        $newevent->userto      = $user;
        $url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
        $a = new stdClass();
        $a->name = $facetoface->name;
        $a->status = $stringmanager->get_string($langkey, 'facetoface', null, $user->lang);
        $a->user   = fullname($USER);
        $a->linkname = html_writer::link($url, $facetoface->name);
        $newevent->fullmessage = $stringmanager->get_string("requestattendsession_message", 'facetoface', $a, $user->lang);
        $newevent->subject     = $stringmanager->get_string("requestattendsession_subject", 'facetoface', $a, $user->lang);
        $newevent->urgency     = TOTARA_MSG_URGENCY_NORMAL;
        $newevent->icon        = 'facetoface-regular';
        $newevent->msgtype     = TOTARA_MSG_TYPE_FACE2FACE;
        return tm_alert_send($newevent);
    }
}
