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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */
namespace mod_facetoface;

use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\waitlisted;
use \stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/notification/lib.php');


/**
 * Class notice_sender is just a wrapper that sends typical signup related notices and notifications to users.
 */
class notice_sender {
    /**
     * Send manager request notices
     *
     * @param signup $signup
     * @return string
     */
    public static function request_manager(signup $signup) {
        $managers = facetoface_get_session_managers($signup->get_userid(), $signup->get_sessionid(), $signup->get_jobassignmentid());

        $hasemail = false;
        foreach ($managers as $manager) {
            if (!empty($manager->email)) {
                $hasemail = true;
                break;
            }
        }

        if ($hasemail) {
            $params = [
                'type'          => MDL_F2F_NOTIFICATION_AUTO,
                'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER
            ];
            return static::send($signup, $params);
        }
        return 'error:nomanagersemailset';
    }

    /**
     * Send booking request notice to user and all users with the specified sessionrole
     *
     * @param signup $signup
     * @return string
     */
    public static function request_role(signup $signup) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE
        ];

        return static::send($signup, $params);
    }

    /**
     * Send booking request notice to user, manager, all session admins.
     *
     * @param signup $signup
     * @return string
     */
    public static function request_admin(signup $signup) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN
        ];

        return static::send($signup, $params);
    }

    /**
     * Send a booking confirmation email to the user and manager
     *
     * @param signup $signup Signup
     * @param int $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
     * @param stdClass $fromuser User object describing who the email is from.
     * @returns string Error message (or empty string if successful)
     */
    public static function confirm_booking(signup $signup, int $notificationtype, stdClass $fromuser = null) : string {
        global $DB;

        $params = [
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        ];

        $fromuser = $signup->get_fromuser();
        if (empty($fromuser) && !empty($signup->get_managerid())) {
            $fromuser = $DB->get_record('user', ['id' => $signup->get_managerid()]);
        }

        return static::send($signup, $params, $notificationtype, MDL_F2F_INVITE, $fromuser);
    }

    /**
     * Send a waitlist confirmation email to the user and manager
     *
     * @param signup $signup Signup
     * @param int $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
     * @param stdClass $fromuser User object describing who the email is from.
     * @returns string Error message (or empty string if successful)
     */
    public static function confirm_waitlist(signup $signup, int $notificationtype, stdClass $fromuser = null) : string {
        global $DB;

        $params = [
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION
        ];

        $fromuser = $signup->get_fromuser();
        if (empty($fromuser) && !empty($signup->get_managerid())) {
            $fromuser = $DB->get_record('user', ['id' => $signup->get_managerid()]);
        }

        return static::send($signup, $params, $notificationtype, MDL_F2F_INVITE, $fromuser);
    }


    /**
     * Send a confirmation email to the user and manager regarding the
     * cancellation
     *
     * @param signup $signup Signup
     * @returns string Error message (or empty string if successful)
     */
    public static function decline(signup $signup) {
        global $CFG;

        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_DECLINE_CONFIRMATION
        ];

        $includeical = empty($CFG->facetoface_disableicalcancel);
        return static::send($signup, $params, $includeical ? MDL_F2F_BOTH : MDL_F2F_TEXT, MDL_F2F_CANCEL);
    }

    /**
     * Send a email to the not signed up attendees (e.g. roles)
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @param array $olddates array of previous dates
     * @returns string Error message (or empty string if successful)
     */
    public static function event_datetime_changed(int $recipientid, seminar_event $seminarevent, array $olddates) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE
        ];

        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_INVITE, null, $olddates);
    }

    /**
     * Send a email to the user and manager regarding the
     * session date/time change
     *
     * @param signup $signup
     * @param array $olddates
     * @return string Error message or empty string if success
     */
    public static function signup_datetime_changed(signup $signup, array $olddates) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE
        ];

        $invite = ($signup->get_state() instanceof waitlisted) ? MDL_F2F_TEXT : MDL_F2F_BOTH;
        return static::send($signup, $params, $invite, MDL_F2F_INVITE, null, $olddates);
    }

    /**
     * Send a message to a user who has just had their waitlisted signup cancelled due to the event starting
     * and the automatic waitlist cleaner cancelling all waitlisted records.
     *
     * @param \signup        $signup
     * @return string
     */
    public static function signup_waitlist_autoclean(signup $signup) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_WAITLIST_AUTOCLEAN
        ];

        return static::send($signup, $params);
    }

    /**
     * Send a confirmation email to the trainer
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @returns string Error message (or empty string if successful)
     */
    public static function trainer_confirmation(int $recipientid, seminar_event $seminarevent) {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_TRAINER_CONFIRMATION
        ];

        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_INVITE);
    }

    /**
     * Send a cancellation email to the trainer
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @returns string Error message (or empty string if successful)
     */
    public static function event_trainer_cancellation(int $recipientid, seminar_event $seminarevent) {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION
        ];
        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_CANCEL);
    }

    /**
     * Send a unassignment email to the trainer
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @returns string Error message (or empty string if successful)
     */
    public static function event_trainer_unassigned(int $recipientid, seminar_event $seminarevent) {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT
        ];

        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_CANCEL);
    }

    /**
     * Send a confirmation email to the user and manager regarding the
     * signup cancellation
     *
     * @param signup $signup Signup
     * @param bool $attachical Should cancellation ical be attached
     * @returns string Error message (or empty string if successful)
     */
    public static function signup_cancellation(signup $signup, $attachical = true) {
        global $CFG;

        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ];

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        return static::send($signup, $params, $icalattachmenttype, MDL_F2F_CANCEL);
    }

    /**
     * Send a confirmation email to the recepient regarding seminar event cancellation
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @param bool $attachical Should cancellation ical be attached
     * @returns string Error message (or empty string if successful)
     */
    public static function event_cancellation(int $recipientid, seminar_event $seminarevent, bool $attachical = true) {
        global $CFG;

        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_SESSION_CANCELLATION
        ];

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        return static::send_event($recipientid, $seminarevent, $params, $icalattachmenttype, MDL_F2F_CANCEL);
    }

    /**
     * Send message to signed up attendee
     * @param signup $signup
     * @param array $params
     * @param int $icalattachmenttype
     * @param int $icalattachmentmethod
     * @param stdClass $fromuser
     * @param array $olddates
     * @return string
     */
    protected static function send(signup $signup, array $params, int $icalattachmenttype = MDL_F2F_TEXT, int $icalattachmentmethod = MDL_F2F_INVITE, stdClass $fromuser = null, array $olddates = []) : string {
        global $DB;
        $recipientid = $signup->get_userid();
        $seminarevent = $signup->get_seminar_event();
        $params['facetofaceid']  = $signup->get_seminar_event()->get_facetoface();

        $session = facetoface_get_session($seminarevent->get_id());
        $skipnotifyuser = false;
        $skipnotifymanager = false;
        if ($signup->get_skipusernotification()) {
            $session->notifyuser = false;
            $skipnotifyuser = true;
        }

        $facetoface = $DB->get_record('facetoface', ['id' => $seminarevent->get_facetoface()]);
        if ($signup->get_skipmanagernotification()) {
            $facetoface->ccmanager = 0;
            $skipnotifymanager = true;
            $session->notifymanager = false;
        }

        // When the notify user and manager options are disabled, then it is not sending any notification to anyone at all
        if ($skipnotifymanager && $skipnotifyuser && !$seminarevent->is_started()) {
            // Returning empty string here, to make it compatible from what the function facetoface_send_notice returns
            return '';
        }

        return facetoface_send_notice($facetoface, $session, $recipientid, $params,$icalattachmenttype, $icalattachmentmethod, $fromuser, $olddates);
    }

    /**
     * Send message to not signed up event attendee (e.g. role)
     * @param int $recipientid
     * @param seminar_event $seminarevent
     * @param array $params
     * @param int $icalattachmenttype
     * @param int $icalattachmentmethod
     * @param stdClass $fromuser
     * @param array $olddates
     * @return string
     */
    protected static function send_event(int $recipientid, seminar_event $seminarevent, array $params, int $icalattachmenttype = MDL_F2F_TEXT, int $icalattachmentmethod = MDL_F2F_INVITE, stdClass $fromuser = null, array $olddates = []) : string {
        global $DB;
        $params['facetofaceid']  = $seminarevent->get_facetoface();

        $session = facetoface_get_session($seminarevent->get_id());
        $facetoface = $DB->get_record('facetoface', ['id' => $seminarevent->get_facetoface()]);

        return facetoface_send_notice($facetoface, $session, $recipientid, $params,$icalattachmenttype, $icalattachmentmethod, $fromuser, $olddates);
    }
}
