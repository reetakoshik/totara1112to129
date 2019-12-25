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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

namespace auth_approved;

/**
 * Utility class for communication methods in this plugin.
 *
 * Please note that we need to use email_to_user() directly before the account is confirmed,
 * all communication to approvers needs to go through message_send().
 *
 */
final class comms {
    /**
     * Send email asking user to confirm their email address.
     *
     * @param \stdClass $request record from auth_approved_request
     * @return bool success
     */
    public static function email_request_confirmation(\stdClass $request) {
        global $CFG;

        $site = get_site();
        $sm = get_string_manager();
        $supportuser = \core_user::get_support_user();

        $data = new \stdClass();
        $data->firstname = $request->firstname;
        $data->lastname = $request->lastname;
        $data->email = $request->email;
        $data->username = $request->username;
        $data->sitename = format_string($site->fullname);
        $data->support = $supportuser->email;
        $data->link = $CFG->wwwroot .'/auth/approved/confirm.php?token='. $request->confirmtoken;

        $userto = \totara_core\totara_user::get_external_user($request->email);
        $subject = $sm->get_string('emailconfirmationsubject', 'auth_approved', $data, $request->lang);
        $message = $sm->get_string('emailconfirmationbody', 'auth_approved', $data, $request->lang);
        $messagehtml = markdown_to_html($message);

        return email_to_user($userto, $supportuser, $subject, $message, $messagehtml);
    }

    /**
     * Notify approvers there is a new request without email confirmation.
     *
     * @param \stdClass $request record from auth_approved_request
     * @return bool success
     */
    public static function notify_new_request(\stdClass $request) {
        global $CFG;
        $data = (object)[
            'fullname' => s(fullname($request)),
            'username' => $request->username,
            'email' => $request->email,
            'link' => $CFG->wwwroot .'/auth/approved/index.php'
        ];

        $sm = get_string_manager();
        $message = $sm->get_string('notificationnewrequest', 'auth_approved', $data);
        $notification = [
            'courseid' => SITEID,
            'component' => 'auth_approved',
            'name' => 'unconfirmed_request',
            'userfrom' => \core_user::get_noreply_user(),
            'subject' => get_string('notificationnewrequestsubject', 'auth_approved'),
            'fullmessage' => $message,
            'fullmessageformat' => FORMAT_MARKDOWN,
            'fullmessagehtml' => markdown_to_html($message),
            'smallmessage' => $message,
            'notification' => 1,
            'contexturl' => new \moodle_url('/auth/approved/index.php'),
            'contexturlname' => get_string('pluginname', 'auth_approved'),
        ];

        $users = \get_users_by_capability(\context_system::instance(), 'auth/approved:approve');
        foreach ($users as $user) {
            $notification['userto'] = $user;
            \message_send((object)$notification);
        }

        return true;
    }

    /**
     * Email user and tell them what happens after confirmation of email.
     *
     * @param \stdClass $request record from auth_approved_request
     * @return bool success
     */
    public static function email_approval_info(\stdClass $request) {
        $site = get_site();
        $sm = get_string_manager();
        $supportuser = \core_user::get_support_user();

        $data = new \stdClass();
        $data->firstname = $request->firstname;
        $data->lastname = $request->lastname;
        $data->email = $request->email;
        $data->username = $request->username;
        $data->sitename = format_string($site->fullname);
        $data->support = $supportuser->email;

        $userto = \totara_core\totara_user::get_external_user($request->email);
        $subject = $sm->get_string('emailconfirmedsubject', 'auth_approved', $data, $request->lang);
        $message = $sm->get_string('emailconfirmedbody', 'auth_approved', $data, $request->lang);
        $messagehtml = markdown_to_html($message);

        return email_to_user($userto, $supportuser, $subject, $message, $messagehtml);
    }

    /**
     * Notify approvers that a new account was auto-approved.
     *
     * @param \stdClass $request record from auth_approved_request
     * @return bool success
     */
    public static function notify_auto_approved_request(\stdClass $request) {
        global $CFG;
        $data = (object)[
            'fullname' => s(fullname($request)),
            'username' => $request->username,
            'email' => $request->email,
            'link' => $CFG->wwwroot .'/auth/approved/index.php'
        ];

        $sm = get_string_manager();
        $message = $sm->get_string('notificationautoapprovedrequest', 'auth_approved', $data);
        $notification = [
            'courseid' => SITEID,
            'component' => 'auth_approved',
            'name' => 'autoapproved_request',
            'userfrom' => \core_user::get_noreply_user(),
            'subject' => get_string('notificationautoapprovedrequestsubject', 'auth_approved'),
            'fullmessage' => $message,
            'fullmessageformat' => FORMAT_MARKDOWN,
            'fullmessagehtml' => markdown_to_html($message),
            'smallmessage' => $message,
            'notification' => 1,
            'contexturl' => new \moodle_url('/auth/approved/index.php'),
            'contexturlname' => get_string('pluginname', 'auth_approved'),
        ];

        $users = \get_users_by_capability(\context_system::instance(), 'auth/approved:approve');
        foreach ($users as $user) {
            $notification['userto'] = $user;
            \message_send((object)$notification);
        }

        return true;

    }

    /**
     * Notify approvers there is a new confirmed request for new account.
     *
     * @param \stdClass $request record from auth_approved_request
     * @return bool success
     */
    public static function notify_confirmed_request(\stdClass $request) {
        global $CFG;
        $data = (object)[
            'fullname' => s(fullname($request)),
            'username' => $request->username,
            'email' => $request->email,
            'link' => $CFG->wwwroot .'/auth/approved/index.php'
        ];

        $sm = get_string_manager();
        $message = $sm->get_string('notificationconfirmrequest', 'auth_approved', $data);
        $notification = [
            'courseid' => SITEID,
            'component' => 'auth_approved',
            'name' => 'confirmed_request',
            'userfrom' => \core_user::get_noreply_user(),
            'subject' => get_string('notificationconfirmrequestsubject', 'auth_approved'),
            'fullmessage' => $message,
            'fullmessageformat' => FORMAT_MARKDOWN,
            'fullmessagehtml' => markdown_to_html($message),
            'smallmessage' => $message,
            'notification' => 1,
            'contexturl' => new \moodle_url('/auth/approved/index.php'),
            'contexturlname' => get_string('pluginname', 'auth_approved'),
        ];

        $users = \get_users_by_capability(\context_system::instance(), 'auth/approved:approve');
        foreach ($users as $user) {
            $notification['userto'] = $user;
            \message_send((object)$notification);
        }

        return true;
    }

    /**
     * Email user that their account was approved and that they can now login.
     *
     * NOTE: we should probably use the same email_to_user() method here to
     *       keep consistency with previous communication. They were not able
     *       to configure messaging yet.
     *
     * @param \stdClass $request record from auth_approved_request
     * @param \stdClass $user record from user table, newly approved account
     * @param string $custommessage custom message for user
     * @return bool success
     */
    public static function email_request_approved(\stdClass $request, \stdClass $user, $custommessage) {
        global $CFG;

        $site = get_site();
        $sm = get_string_manager();
        $supportuser = \core_user::get_support_user();

        $data = new \stdClass();
        $data->firstname = $user->firstname;
        $data->lastname = $user->lastname;
        $data->fullname = fullname($user);
        $data->email = $user->email;
        $data->username = $user->username;
        $data->sitename = format_string($site->fullname);
        $data->support = $supportuser->email;
        $data->link = $CFG->wwwroot .'/login/index.php';
        $data->custommessage = $custommessage;

        $subject = $sm->get_string('requestapprovedsubject', 'auth_approved', $data, $user->lang);
        $message = $sm->get_string('requestapprovedbody', 'auth_approved', $data, $user->lang);
        $messagehtml = markdown_to_html($message);

        return email_to_user($user, $supportuser, $subject, $message, $messagehtml);
    }

    /**
     * Email user that their account was rejected.
     *
     * @param \stdClass $request record from auth_approved_request
     * @param string $custommessage custom message for user
     * @return bool success
     */
    public static function email_request_rejected(\stdClass $request, $custommessage) {
        $site = get_site();
        $sm = get_string_manager();

        $supportuser = \core_user::get_support_user();
        $data = new \stdClass();
        $data->firstname = $request->firstname;
        $data->lastname = $request->lastname;
        $data->email = $request->email;
        $data->username = $request->username;
        $data->sitename = format_string($site->fullname);
        $data->support = $supportuser->email;
        $data->custommessage = $custommessage;

        $userto = \totara_core\totara_user::get_external_user($request->email);
        $subject = $sm->get_string('requestrejectedsubject', 'auth_approved', $data, $request->lang);
        $message = $sm->get_string('requestrejectedbody', 'auth_approved', $data, $request->lang);
        $messagehtml = markdown_to_html($message);

        return email_to_user($userto, $supportuser, $subject, $message, $messagehtml);
    }

    /**
     * Send a custom email to the user.
     *
     * @param string $email user email address.
     * @param string $subject email subject.
     * @param string $body email body.
     * @return bool success
     */
    public static function email_custom_message($email, $subject, $body) {
        $userto = \totara_core\totara_user::get_external_user($email);
        $supportuser = \core_user::get_support_user();
        $bodyhtml = markdown_to_html($body);

        return email_to_user($userto, $supportuser, $subject, $body, $bodyhtml);
    }
}
