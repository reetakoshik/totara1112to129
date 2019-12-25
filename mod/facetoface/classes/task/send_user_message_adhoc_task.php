<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 Totara Learning Solutions Ltd
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */
namespace mod_facetoface\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');

use \stdClass;
use \context_course;
use \mod_facetoface\messaging;

class send_user_message_adhoc_task extends \core\task\adhoc_task {
    /**
     * Send out messages formatted for given user
     */
    public function execute() {
        $data = unserialize($this->get_custom_data());

        $trace = new \text_progress_trace();
        if (!defined('PHPUNIT_TEST')) {
            $trace->output('Send seminar adhoc notification to user id: ' . $data->event->userto->id);
        }

        $recipient = $data->event->userto;
        cron_setup_user($recipient);

        $message = $this->prepare_message();
        if (!empty($data->icaldata)) {
            $this->regenerate_ical($message, $data);
        }
        message_send($message);

        if ($data->addhistory) {
            $this->add_history();
        }

        $this->cleanup($message);

        $trace->finished();
    }

    /**
     * Prepare data for send_message to recipient
     * Performs filtering and substitution and replaces fields to match recipient user's preferences
     *
     * @return stdClass Object for send_message
     */
    protected function prepare_message(): stdClass {
        $data = unserialize($this->get_custom_data());
        $signupuser = $data->signupuser;
        $sessionid = $data->sessionid;
        $facetoface = $data->facetoface;
        $event = $data->event;
        $sessions = $data->sessions;
        $session = $sessions[$data->sessionid];
        $title = $data->title;
        $body = $data->body;
        $managerprefix = $data->managerprefix;
        $addmanagerprefix = empty($data->event->addmanagerprefix) ? false : true;

        $options = array('context' => context_course::instance($facetoface->course));
        $coursename = format_string($facetoface->coursename, true, $options);

        $recipientformatoptions = null;
        $activityname = format_string($facetoface->name, true, $recipientformatoptions);

        $subject = facetoface_message_substitutions(
            format_string($title, true, $recipientformatoptions),
            $coursename,
            $activityname,
            $signupuser,
            $session,
            $sessionid,
            $facetoface->approvalrole
        );
        $body = facetoface_message_substitutions(
            format_text($body, FORMAT_HTML, $recipientformatoptions),
            $coursename,
            $activityname,
            $signupuser,
            $session,
            $sessionid,
            $facetoface->approvalrole
        );

        $plaintext = format_text_email($body, FORMAT_HTML);

        $messagedata = clone $event;

        $messagedata->subject     = $subject;
        $messagedata->fullmessage       = $plaintext;
        $messagedata->fullmessageformat = FORMAT_PLAIN;
        $messagedata->fullmessagehtml   = $body;
        $messagedata->smallmessage      = $plaintext;

        // Fix totara_task data.
        if (!empty($event->onaccept->data) && !is_array(!empty($event->onaccept->data))) {
            $event->onaccept->data = $event->onaccept->data;
        }
        if (!empty($event->onreject->data) && !is_array(!empty($event->onreject->data))) {
            $event->onreject->data = $event->onreject->data;
        }

        if ($addmanagerprefix) {

            $managerprefix = facetoface_message_substitutions(
                format_text($managerprefix, FORMAT_HTML, $recipientformatoptions),
                $coursename,
                $activityname,
                $signupuser,
                $session,
                $sessionid,
                $facetoface->approvalrole
            );

            $plaintext = format_text_email($managerprefix, FORMAT_HTML);
            $messagedata->fullmessage = $plaintext . $messagedata->fullmessage;
            $messagedata->fullmessagehtml = $managerprefix . $messagedata->fullmessagehtml;
            $messagedata->smallmessage = $plaintext . $messagedata->smallmessage;
        }

        return $messagedata;
    }

    /**
     * Regenerates ical attachment file
     * Rest of the fields (attachname, ical_uids, ical_method) are not lost, so not regenerated.
     * @param stdClass $message
     * @param stdClass $data
     */
    protected function regenerate_ical(stdClass $message, stdClass $data) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/facetoface/notification/lib.php');

        if ($data->conditiontype == MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION) {
            return;
        }

        $signupuser = $data->signupuser;
        $facetoface = $data->facetoface;
        $sessions = $data->sessions;
        $session = $sessions[$data->sessionid];
        $icaldata = $data->icaldata;

        $ical_attach = messaging::generate_ical($facetoface, $session, $icaldata['method'], $signupuser, $icaldata['dates'], $icaldata['olddates']);
        $message->attachment = $ical_attach->file;
    }

    protected function add_history() {
        global $DB;
        $data = unserialize($this->get_custom_data());
        $notificationid = $data->id;
        $signupuser = $data->signupuser;
        $sessionid = $data->sessionid;
        $icaluids = empty($data->event->ical_uids) ? [] : $data->event->ical_uids;
        $icalmethod = empty($data->event->ical_method) ? '' : $data->event->ical_method;
        $sessiondate = $data->sessiondate;
        $sessions = $data->sessions;

        if (!$DB->record_exists('facetoface_notification', ['id' => $notificationid])) {
            return;
        }
        if (!$DB->record_exists('facetoface_sessions', ['id' => $sessionid])) {
            return;
        }
        if (!empty($sessiondate)) {
            $uid = empty($icaluids) ? '' : array_shift($icaluids);
            $hist = new stdClass();
            $hist->notificationid = $notificationid;
            $hist->sessionid = $sessionid;
            $hist->userid = $signupuser->id;
            $hist->sessiondateid = $sessiondate->id;
            $hist->ical_uid = $uid;
            $hist->ical_method = $icalmethod;
            $hist->timecreated = time();
            $DB->insert_record('facetoface_notification_hist', $hist);
        } else {
            $dates = $sessions[$sessionid]->sessiondates;
            foreach ($dates as $sessiondate) {
                $uid = empty($icaluids) ? '' : array_shift($icaluids);
                $hist = new stdClass();
                $hist->notificationid = $notificationid;
                $hist->sessionid = $sessionid;
                $hist->userid = $signupuser->id;
                $hist->sessiondateid = $sessiondate->id;
                $hist->ical_uid = $uid;
                $hist->ical_method = $icalmethod;
                $hist->timecreated = time();
                $DB->insert_record('facetoface_notification_hist', $hist);
            }
        }

        // Mark notification as sent for user.
        $sent = new stdClass();
        $sent->sessionid = $sessionid;
        $sent->notificationid = $notificationid;
        $sent->userid = $signupuser->id;
        $DB->insert_record('facetoface_notification_sent', $sent);

    }
    /**
     * Remove generated ical file
     * @param mixed \stdClass|\stored_file $message
     */
    protected function cleanup($message) {
        if (!empty($message->attachment) && $message->attachment instanceof \stored_file) {
            $message->attachment->delete();
        }
    }
}