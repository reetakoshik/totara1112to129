<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package auth_approved
 */

class auth_approved_request_testcase extends advanced_testcase {

    private function create_approver() {
        $approver = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        role_change_permission($roleid, context_system::instance(), 'auth/approved:approve', CAP_ALLOW);
        $this->getDataGenerator()->role_assign($roleid, $approver->id);
        return $approver;
    }

    private function create_request($key = 1) {
        global $DB;
        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test' . $key;
        $data->firstname = 'test' . $key;
        $data->lastname = 'test' . $key;
        $data->password = 'monkey';
        $data->email = 'test_'.$key.'@example.com';
        $data->city = 'test'.$key;
        $data->country = 'NZ';
        $data->lang = 'en';
        $requestid = \auth_approved\request::add_request($data);
        return $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
    }

    public function test_get_statuses() {
        $statuses = \auth_approved\request::get_statuses();
        $this->assertInternalType('array', $statuses);
        $this->assertArrayHasKey(\auth_approved\request::STATUS_PENDING, $statuses);
        $this->assertArrayHasKey(\auth_approved\request::STATUS_APPROVED, $statuses);
        $this->assertArrayHasKey(\auth_approved\request::STATUS_REJECTED, $statuses);
    }

    public function test_encode_signup_form_data() {

        // Test with full details.
        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'username';
        $data->firstname = 'firstname';
        $data->lastname = 'lastname';
        $data->lastnamephonetic = 'lastnamephonetic';
        $data->firstnamephonetic = 'firstnamephonetic';
        $data->middlename = 'middlename';
        $data->alternatename = 'alternatename';
        $data->email = 'email';
        $data->city = 'city';
        $data->country = 'country';
        $data->lang = 'lang';
        $data->positionid = 0;
        $data->positionfreetext = 'Manager';
        $data->organisationid = 0;
        $data->organisationfreetext = 'Totara Learning';
        $data->managerjaid = 0;
        $data->managerfreetext = 'managerfreetext';
        $data->profile_field_fake = 'Fake!';
        $record = \auth_approved\request::encode_signup_form_data($data);

        $expected = new \stdClass;
        $expected->id = $data->requestid;
        $expected->username = 'username';
        $expected->firstname = 'firstname';
        $expected->lastname = 'lastname';
        $expected->lastnamephonetic = 'lastnamephonetic';
        $expected->firstnamephonetic = 'firstnamephonetic';
        $expected->middlename = 'middlename';
        $expected->alternatename = 'alternatename';
        $expected->email = 'email';
        $expected->city = 'city';
        $expected->country = 'country';
        $expected->lang = 'lang';
        $expected->positionid = 0;
        $expected->positionfreetext = 'Manager';
        $expected->organisationid = 0;
        $expected->organisationfreetext = 'Totara Learning';
        $expected->managerjaid = 0;
        $expected->managerfreetext = 'managerfreetext';
        $expected->profilefields = json_encode(['profile_field_fake' => $data->profile_field_fake]);
        $this->assertSame((array)$expected, (array)$record);

        // Test with partial details and no freetext.
        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'username';
        $data->firstname = 'firstname';
        $data->lastname = 'lastname';
        $data->email = 'email';
        $data->city = 'city';
        $data->country = 'country';
        $data->lang = 'lang';
        $record = \auth_approved\request::encode_signup_form_data($data);

        $expected = new \stdClass;
        $expected->id = $data->requestid;
        $expected->username = 'username';
        $expected->firstname = 'firstname';
        $expected->lastname = 'lastname';
        $expected->lastnamephonetic = null;
        $expected->firstnamephonetic = null;
        $expected->middlename = null;
        $expected->alternatename = null;
        $expected->email = 'email';
        $expected->city = 'city';
        $expected->country = 'country';
        $expected->lang = 'lang';
        $expected->positionid = 0;
        $expected->organisationid = 0;
        $expected->managerjaid = 0;
        $expected->profilefields = json_encode([]);
        $this->assertSame((array)$expected, (array)$record);

        // Test freetext empty to null conversions.
        // Test with full details.
        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'username';
        $data->firstname = 'firstname';
        $data->lastname = 'lastname';
        $data->email = 'email';
        $data->city = 'city';
        $data->country = 'country';
        $data->lang = 'lang';
        $data->positionfreetext = '';
        $data->organisationfreetext = '';
        $data->managerfreetext = '';
        $record = \auth_approved\request::encode_signup_form_data($data);

        $expected = new \stdClass;
        $expected->id = $data->requestid;
        $expected->username = 'username';
        $expected->firstname = 'firstname';
        $expected->lastname = 'lastname';
        $expected->lastnamephonetic = null;
        $expected->firstnamephonetic = null;
        $expected->middlename = null;
        $expected->alternatename = null;
        $expected->email = 'email';
        $expected->city = 'city';
        $expected->country = 'country';
        $expected->lang = 'lang';
        $expected->positionid = 0;
        $expected->positionfreetext = null;
        $expected->organisationid = 0;
        $expected->organisationfreetext = null;
        $expected->managerjaid = 0;
        $expected->managerfreetext = null;
        $expected->profilefields = json_encode([]);

        $this->assertSame((array)$expected, (array)$record);

    }

    public function test_add_request() {
        $this->resetAfterTest();
        $approver = $this->create_approver();

        $emailsink = $this->redirectEmails();
        $messagesink = $this->redirectMessages();
        $eventsink = $this->redirectEvents();

        $noreplyuser = \core_user::get_noreply_user();
        $supportuser = \core_user::get_support_user();

        $request = $this->create_request();

        // We expect the following:
        //  - 1 email to the user who just registered.
        //  - 1 notification to the approver.
        //  - 1 event to be fired.
        $this->assertSame(1, $emailsink->count());
        $this->assertSame(1, $messagesink->count());
        $this->assertSame(1, $eventsink->count());

        $emails = $emailsink->get_messages();
        $email = reset($emails);
        $this->assertSame('PHPUnit test site: Confirmation of account request', $email->subject);
        $this->assertSame($noreplyuser->email, $email->from);
        $this->assertSame($request->email, $email->to);
        $this->assertContains('Please go to this web address to confirm your request', $email->body);
        $this->assertContains('auth/approved/confirm.php?token', $email->body);
        $this->assertContains('If you need help, please contact support at this address: '.$supportuser->email, $email->body);
        $this->assertNotContains('monkey', $email->body);

        $messages = $messagesink->get_messages();
        $message = reset($messages);
        $this->assertEquals($approver->id, $message->useridto);
        $this->assertSame('Account request awaits email confirmation', $message->subject);
        $this->assertContains('Applicant "test1 test1" requested an account with username "test1"; they were asked to confirm their email address "test_1@example.com"', $message->fullmessage);
        $this->assertContains('Applicant "test1 test1" requested an account with username "test1"; they were asked to confirm their email address "test_1@example.com"', $message->smallmessage);
        $this->assertSame($noreplyuser->email, $message->fromemail);

        $events = $eventsink->get_events();
        $event = reset($events);
        $this->assertInstanceOf('\auth_approved\event\request_added', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!
        $this->assertContains('registered for system access', $event->get_description());
        $this->assertSame(get_string('eventrequestadded', 'auth_approved'), $event::get_name());
        $this->assertContains('/auth/approved/index.php', (string)$event->get_url());

        $emailsink->close();
        $messagesink->close();
        $eventsink->close();
    }

    public function test_update_request() {
        global $DB;
        $this->resetAfterTest();
        $this->redirectEmails();

        $request = $this->create_request();
        $data = clone($request);
        $request->requestid = $request->id;
        $request->firstname = 'tricky';
        $request->lastname = 'gorilla';

        \auth_approved\request::update_request($request);

        $record = $DB->get_record('auth_approved_request', ['id' => $data->id], '*', MUST_EXIST);
        $this->assertSame($request->firstname, $record->firstname);
        $this->assertSame($request->lastname, $record->lastname);

        \auth_approved\request::approve_request($request->id, 'Test', true);
        $record = $DB->get_record('auth_approved_request', ['id' => $data->id], '*', MUST_EXIST);
        $record->requestid = $record->id;
        $record->firstname = 'old';
        $this->expectException('coding_exception', 'Cannot update resolved request!');
        \auth_approved\request::update_request($record);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_send_message() {
        $this->resetAfterTest();

        $request = $this->create_request();

        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        $noreplyuser = \core_user::get_noreply_user();

        $result = \auth_approved\request::send_message($request->id, 'Some subject '.$request->username, 'This is the body '.$request->username);
        $this->assertTrue($result);

        $this->assertSame(1, $emailsink->count());
        $this->assertSame(0, $eventsink->count());
        $this->assertSame(0, $messagesink->count());

        $emails = $emailsink->get_messages();
        $email = reset($emails);
        $this->assertSame('Some subject '.$request->username, $email->subject);
        $this->assertSame($noreplyuser->email, $email->from);
        $this->assertSame($request->email, $email->to);
        $this->assertContains('This is the body '.$request->username, $email->body);
        $this->assertNotContains('monkey', $email->body);

        $emailsink->close();
        $messagesink->close();
        $eventsink->close();

    }

    public function test_confirm_request() {
        global $DB;
        $this->resetAfterTest();

        // We want an approver so that we can check his notification.
        $approver = $this->create_approver();

        $noreplyuser = \core_user::get_noreply_user();
        $supportuser = \core_user::get_support_user();

        // First up lets try with an invalid token. This shouldn't generate any messages, so no need for sinks.
        $result = \auth_approved\request::confirm_request('gorilla');
        $this->assertCount(3, $result);
        $this->assertFalse($result[0]);
        $this->assertSame(get_string('confirmtokeninvalid', 'auth_approved'), $result[1]);
        $this->assertNull($result[2]);

        // Next lets try with a token that doesn't exist. This shouldn't generate any messages, so no need for sinks.
        $result = \auth_approved\request::confirm_request(str_repeat('x', 32));
        $this->assertCount(3, $result);
        $this->assertFalse($result[0]);
        $this->assertSame(get_string('confirmtokeninvalid', 'auth_approved'), $result[1]);
        $this->assertNull($result[2]);

        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        $request = $this->create_request();
        $DB->set_field('auth_approved_request', 'positionfreetext', 'Manager', ['id' => $request->id]);
        $token = $DB->get_field('auth_approved_request', 'confirmtoken', ['id' => $request->id]);

        $emails = $emailsink->get_messages();
        $email = reset($emails);
        $this->assertContains('auth/approved/confirm.php?token', $email->body);
        $this->assertContains($token, $email->body);

        // Clear we want to validate the stuff on a successfull confirmation.
        $emailsink->clear();
        $eventsink->clear();
        $messagesink->clear();

        $result = \auth_approved\request::confirm_request($token);
        $this->assertInternalType('array', $result);
        $this->assertCount(3, $result);
        $this->assertTrue($result[0]);
        $this->assertSame('Thank you for confirming your account request, an email should have been sent to your address at test_1@example.com with information describing the account approval process.', $result[1]);
        $this->assertNull($result[2]);

        $this->assertSame(1, $emailsink->count());
        $this->assertSame(1, $eventsink->count());
        $this->assertSame(1, $messagesink->count());

        $emails = $emailsink->get_messages();
        $email = reset($emails);
        $this->assertSame('PHPUnit test site: Account request confirmed', $email->subject);
        $this->assertSame($noreplyuser->email, $email->from);
        $this->assertSame($request->email, $email->to);
        $this->assertContains('Thank you for confirming your account request at \'PHPUnit test site\'', $email->body);
        $this->assertContains('If you need help, please contact support at this address: '.$supportuser->email, $email->body);
        $this->assertNotContains('monkey', $email->body);

        $events = $eventsink->get_events();
        $event = reset($events);
        $this->assertInstanceOf('\auth_approved\event\request_confirmed', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame('confirmed', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        $messages = $messagesink->get_messages();
        $message = reset($messages);
        $this->assertEquals($approver->id, $message->useridto);
        $this->assertSame('New account request requires approval', $message->subject);
        $this->assertContains('Applicant "test1 test1", who requested an account with username "test1", has just confirmed their email address "test_1@example.com"', $message->fullmessage);
        $this->assertContains('Applicant "test1 test1", who requested an account with username "test1", has just confirmed their email address "test_1@example.com"', $message->smallmessage);
        $this->assertSame($noreplyuser->email, $message->fromemail);



        $emailsink->clear();
        $eventsink->clear();
        $messagesink->clear();

        // Attempt to confirm it again.
        $result = \auth_approved\request::confirm_request($token);
        $this->assertCount(3, $result);
        $this->assertFalse($result[0]);
        $this->assertSame('User account request was already confirmed', $result[1]);
        $this->assertNull($result[2]);

        // We better not have sent any email or notices.
        $this->assertSame(0, $emailsink->count());
        $this->assertSame(0, $eventsink->count());
        $this->assertSame(0, $messagesink->count());

        $emailsink->close();
        $eventsink->close();
        $messagesink->close();

    }

    public function test_confirm_request_already_approved() {
        global $DB;
        $this->resetAfterTest();
        $this->redirectEmails();
        $request = $this->create_request();
        $token = $DB->get_field('auth_approved_request', 'confirmtoken', ['id' => $request->id]);
        \auth_approved\request::approve_request($request->id, 'Test', true);
        $result = \auth_approved\request::confirm_request($token);
        $this->assertCount(3, $result);
        $this->assertFalse($result[0]);
        $this->assertSame('User account request was already approved', $result[1]);
        $this->assertNull($result[2]);
    }

    public function test_confirm_request_already_rejected() {
        global $DB;
        $this->resetAfterTest();
        $this->redirectEmails();
        $request = $this->create_request();
        $token = $DB->get_field('auth_approved_request', 'confirmtoken', ['id' => $request->id]);
        \auth_approved\request::reject_request($request->id, 'Test');
        $result = \auth_approved\request::confirm_request($token);
        $this->assertCount(3, $result);
        $this->assertFalse($result[0]);
        $this->assertSame(get_string('confirmtokenrejected', 'auth_approved'), $result[1]);
        $this->assertNull($result[2]);
    }

    public function test_confirm_request_invalid_status() {
        global $DB;
        $this->resetAfterTest();
        $this->redirectEmails();
        $request = $this->create_request();
        $token = $DB->get_field('auth_approved_request', 'confirmtoken', ['id' => $request->id]);
        $DB->set_field('auth_approved_request', 'status', 3, ['id' => $request->id]);
        $this->expectException('coding_exception');
        \auth_approved\request::confirm_request($token);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_confirm_request_requireapproval_off() {
        global $DB;

        $this->resetAfterTest();
        $approver = $this->create_approver();

        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        $noreplyuser = \core_user::get_noreply_user();
        $supportuser = \core_user::get_support_user();

        set_config('requireapproval', false, 'auth_approved');

        $request = $this->create_request();
        $token = $DB->get_field('auth_approved_request', 'confirmtoken', ['id' => $request->id]);

        // Clear we want to validate the stuff on a successfull confirmation.
        $emailsink->clear();
        $eventsink->clear();
        $messagesink->clear();

        $result = \auth_approved\request::confirm_request($token);
        $this->assertInternalType('array', $result);
        $this->assertCount(3, $result);
        $this->assertTrue($result[0]);
        $this->assertSame('Thank you for confirming your account request, you can now log in using your requested username: ' . $request->username, $result[1]);
        $this->assertInstanceOf('single_button', $result[2]);
        $this->assertSame(get_login_url(), $result[2]->url->out(false));
        $this->assertSame(3, $eventsink->count());
        $this->assertSame(1, $messagesink->count());
        $this->assertSame(1, $emailsink->count());

        $emails = $emailsink->get_messages();

        // The account has been created confirmation.
        $email = $emails[0];
        $this->assertSame('PHPUnit test site: Account request approved', $email->subject);
        $this->assertSame($noreplyuser->email, $email->from);
        $this->assertSame($request->email, $email->to);
        $this->assertContains('A new account has been created at \'PHPUnit test site\' as requested.', $email->body);
        $this->assertContains('If you need help, please contact support at this address: '.$supportuser->email, $email->body);
        $this->assertNotContains('monkey', $email->body);

        $events = $eventsink->get_events();

        // Request confirmed.
        $event = reset($events);
        $this->assertInstanceOf('\auth_approved\event\request_confirmed', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame('confirmed', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        // The user created event is expected first.
        $event = next($events);
        $this->assertInstanceOf('\core\event\user_created', $event);
        $this->assertSame('core', $event->component);
        $this->assertSame('created', $event->action);
        $this->assertSame(CONTEXT_USER, $event->contextlevel);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        // Now the request approved event.
        $event = next($events);
        $this->assertInstanceOf('\auth_approved\event\request_approved', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame('approved', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!
        $this->assertContains('approved for system access', $event->get_description());
        $this->assertSame(get_string('eventrequestapproved', 'auth_approved'), $event::get_name());
        $this->assertContains('/auth/approved/index.php', (string)$event->get_url());

        // Verify approver notification.
        $messages = $messagesink->get_messages();
        $message = reset($messages);
        $this->assertEquals($approver->id, $message->useridto);
        $this->assertSame('New account request was approved automatically', $message->subject);
        $this->assertSame($noreplyuser->email, $message->fromemail);

        $emailsink->close();
        $eventsink->close();
        $messagesink->close();
    }

    public function test_confirm_request_domainwhitelist_match() {
        global $DB;

        $this->resetAfterTest();

        $approver = $this->create_approver();

        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        $noreplyuser = \core_user::get_noreply_user();
        $supportuser = \core_user::get_support_user();

        set_config('domainwhitelist', 'example.com', 'auth_approved');

        $request = $this->create_request();
        $token = $DB->get_field('auth_approved_request', 'confirmtoken', ['id' => $request->id]);
        $DB->set_field('auth_approved_request', 'profilefields', json_encode(['profile_field_fake' => 'blah']), ['id' => $request->id]);

        // Clear we want to validate the stuff on a successfull confirmation.
        $emailsink->clear();
        $eventsink->clear();
        $messagesink->clear();

        $result = \auth_approved\request::confirm_request($token);
        $this->assertInternalType('array', $result);
        $this->assertCount(3, $result);
        $this->assertTrue($result[0]);
        $this->assertSame('Thank you for confirming your account request, you can now log in using your requested username: ' . $request->username, $result[1]);
        $this->assertInstanceOf('single_button', $result[2]);
        $this->assertSame(get_login_url(), $result[2]->url->out(false));

        $this->assertSame(1, $emailsink->count());
        $this->assertSame(3, $eventsink->count());
        $this->assertSame(1, $messagesink->count());

        $emails = $emailsink->get_messages();

        // The account has been created.
        $email = $emails[0];
        $this->assertSame('PHPUnit test site: Account request approved', $email->subject);
        $this->assertSame($noreplyuser->email, $email->from);
        $this->assertSame($request->email, $email->to);
        $this->assertContains('A new account has been created at \'PHPUnit test site\' as requested.', $email->body);
        $this->assertContains('If you need help, please contact support at this address: '.$supportuser->email, $email->body);
        $this->assertNotContains('monkey', $email->body);

        $events = $eventsink->get_events();

        // Request confirmed.
        $event = reset($events);
        $this->assertInstanceOf('\auth_approved\event\request_confirmed', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame('confirmed', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!
        $this->assertContains('confirmed email address', $event->get_description());
        $this->assertSame(get_string('eventrequestconfirmed', 'auth_approved'), $event::get_name());
        $this->assertContains('/auth/approved/index.php', (string)$event->get_url());

        // The user created event is expected first.
        $event = next($events);
        $this->assertInstanceOf('\core\event\user_created', $event);
        $this->assertSame('core', $event->component);
        $this->assertSame('created', $event->action);
        $this->assertSame(CONTEXT_USER, $event->contextlevel);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        // Now the request approved event.
        $event = next($events);
        $this->assertInstanceOf('\auth_approved\event\request_approved', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame('approved', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        // Verify approver notification.
        $messages = $messagesink->get_messages();
        $message = reset($messages);
        $this->assertEquals($approver->id, $message->useridto);
        $this->assertSame('New account request was approved automatically', $message->subject);
        $this->assertSame($noreplyuser->email, $message->fromemail);

        $emailsink->close();
        $eventsink->close();
        $messagesink->close();
    }

    public function test_reject_request() {
        $this->resetAfterTest();

        $request = $this->create_request();

        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        $noreplyuser = \core_user::get_noreply_user();
        $supportuser = \core_user::get_support_user();

        $result = \auth_approved\request::reject_request($request->id, 'A custom rejection message');
        $this->assertTrue($result);

        $this->assertSame(1, $emailsink->count());
        $this->assertSame(1, $eventsink->count());
        $this->assertSame(0, $messagesink->count());

        $emails = $emailsink->get_messages();
        $email = reset($emails);
        $this->assertSame('PHPUnit test site: Account request rejected', $email->subject);
        $this->assertSame($noreplyuser->email, $email->from);
        $this->assertSame($request->email, $email->to);
        $this->assertContains('A custom rejection message', $email->body);
        $this->assertContains('If you need help, please contact support at this address: '.$supportuser->email, $email->body);
        $this->assertNotContains('monkey', $email->body);

        $events = $eventsink->get_events();
        $event = reset($events);
        $this->assertInstanceOf('\auth_approved\event\request_rejected', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame('rejected', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!
        $this->assertContains('rejected for system access', $event->get_description());
        $this->assertSame(get_string('eventrequestrejected', 'auth_approved'), $event::get_name());
        $this->assertContains('/auth/approved/index.php', (string)$event->get_url());

        $emailsink->clear();
        $eventsink->clear();
        $messagesink->clear();

        // Attempt to reject it again and confirm that no more emails etc are sent.
        $result = \auth_approved\request::reject_request($request->id, 'A custom rejection message');
        $this->assertTrue($result);

        $this->assertSame(0, $emailsink->count());
        $this->assertSame(0, $eventsink->count());
        $this->assertSame(0, $messagesink->count());

        // Finally confirm you can't reject an already approved request.
        $request = $this->create_request(2);
        $newuserid = \auth_approved\request::approve_request($request->id, 'A custom approval message', false);
        $this->assertInternalType('int', $newuserid);
        $this->assertGreaterThan(0, $newuserid);

        $result = \auth_approved\request::reject_request($request->id, 'A custom rejection message');
        $this->assertFalse($result);

        $emailsink->close();
        $messagesink->close();
        $eventsink->close();
    }

    public function test_approve_request() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $org = $generator->create_org(['frameworkid' => $framework->id]);

        $request = $this->create_request();
        // Put the request into an organisations.
        $DB->set_field('auth_approved_request', 'organisationid', $org->id, ['id' => $request->id]);

        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();
        $messagesink = $this->redirectMessages();

        $noreplyuser = \core_user::get_noreply_user();
        $supportuser = \core_user::get_support_user();

        $newuserid = \auth_approved\request::approve_request($request->id, 'A custom approval message', true);
        $this->assertInternalType('int', $newuserid);
        $this->assertGreaterThan(0, $newuserid);

        $this->assertSame(1, $emailsink->count());
        $this->assertSame(3, $eventsink->count());
        $this->assertSame(0, $messagesink->count());

        $emails = $emailsink->get_messages();
        $email = reset($emails);
        $this->assertSame('PHPUnit test site: Account request approved', $email->subject);
        $this->assertSame($noreplyuser->email, $email->from);
        $this->assertSame($request->email, $email->to);
        $this->assertContains('A custom approval message', $email->body);
        $this->assertContains('If you need help, please contact support at this address: '.$supportuser->email, $email->body);
        $this->assertNotContains('monkey', $email->body);

        $events = $eventsink->get_events();
        // First job assignment updated.
        $event = reset($events);
        $this->assertInstanceOf('\totara_job\event\job_assignment_updated', $event);
        $this->assertSame('totara_job', $event->component);
        $this->assertSame('updated', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        // The user created event is expected next.
        $event = next($events);
        $this->assertInstanceOf('\core\event\user_created', $event);
        $this->assertSame('core', $event->component);
        $this->assertSame('created', $event->action);
        $this->assertSame(CONTEXT_USER, $event->contextlevel);
        $this->assertEquals($newuserid, $event->objectid);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        // Now the request approved event.
        $event = next($events);
        $this->assertInstanceOf('\auth_approved\event\request_approved', $event);
        $this->assertSame('auth_approved', $event->component);
        $this->assertSame('approved', $event->action);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertEquals($request->id, $event->objectid);
        $this->assertCount(2, $event->other);
        $this->assertSame($request->email, $event->other['email']);
        $this->assertSame($request->username, $event->other['username']);
        $this->assertNotContains('monkey', json_encode($event)); // Confirm the event does not contain the password!

        $emailsink->clear();
        $eventsink->clear();
        $messagesink->clear();

        $newuser = $DB->get_record('user', ['id' => $newuserid], '*', MUST_EXIST);
        $this->assertSame($request->username, $newuser->username);
        $this->assertSame($request->password, $newuser->password);

        $jobs = \totara_job\job_assignment::get_all($newuser->id);
        $this->assertCount(1, $jobs);
        $job = reset($jobs);
        $this->assertInstanceOf('\totara_job\job_assignment', $job);
        $this->assertEquals($org->id, $job->organisationid);

        // Attempt to approve it again and confirm that no more emails etc are sent.
        $result = \auth_approved\request::approve_request($request->id, 'A custom approval message', false);
        $this->assertTrue($result);

        $this->assertSame(0, $emailsink->count());
        $this->assertSame(0, $eventsink->count());
        $this->assertSame(0, $messagesink->count());

        // Finally confirm you can't approve an already rejected request.
        $request = $this->create_request(2);
        $result = \auth_approved\request::reject_request($request->id, 'A custom rejection message');
        $this->assertTrue($result);

        $result = \auth_approved\request::approve_request($request->id, 'A custom approval message', false);
        $this->assertFalse($result);

        $emailsink->close();
        $messagesink->close();
        $eventsink->close();
    }

    public function test_approve_request_invalid_email() {
        global $DB;
        $this->resetAfterTest();

        $request = $this->create_request();
        $DB->set_field('auth_approved_request', 'email', 'fail', ['id' => $request->id]);

        // This should not approve the user!
        $result = \auth_approved\request::approve_request($request->id, 'A custom approval message', true);
        $this->assertFalse($result);
    }

    public function test_validate_signup_form_data_email() {
        global $DB;
        $this->resetAfterTest();
        $this->getDataGenerator()->create_user(['email' => 'exists@example.com']);
        $request = $this->create_request();
        $DB->set_field('auth_approved_request', 'email', 'request@example.com', ['id' => $request->id]);


        // First with an unused email.
        $data = array(
            'email' => 'test@example.com',
            'username' => 'test',
            'password' => 'Special-8'
        );
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // Next with an existing email.
        $data['email'] = 'exists@example.com';
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('email', $errors);
        $this->assertContains('This email address is already registered.', $errors['email']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('email', $errors);
        $this->assertContains('This email address is already registered', $errors['email']);

        // With an email that has an existing request.
        $data['email'] = 'request@example.com';
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('email', $errors);
        $this->assertContains('Pending request with the same email address already exists', $errors['email']);

        // Next with an email address that is not allowed.
        set_config('denyemailaddresses', 'example.com');
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('email', $errors);
        $this->assertContains('Email addresses in these domains are not allowed', $errors['email']);
    }

    public function test_validate_signup_form_data_password() {
        // First with an acceptable password..
        $data = array(
            'email' => 'test@example.com',
            'username' => 'test',
            'password' => 'Special-8'
        );
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // Next with an empty password.
        $data['password'] = '';

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('password', $errors);
        $this->assertSame(get_string('required'), $errors['password']);

        // Next with a password that won't meet policy
        $data['password'] = 'a';
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Passwords must be at least 8 characters long', $errors['password']);
    }

    public function test_validate_signup_form_data_username() {
        global $DB;
        $this->resetAfterTest();
        $this->getDataGenerator()->create_user(['username' => 'frank']);
        $request = $this->create_request();
        $DB->set_field('auth_approved_request', 'username', 'request', ['id' => $request->id]);

        // First with an unused username.
        $data = array(
            'email' => 'test@example.com',
            'username' => 'test',
            'password' => 'Special-8'
        );
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // Next with an existing username.
        $data['username'] = 'frank';
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('username', $errors);
        $this->assertContains(get_string('usernameexists'), $errors['username']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('username', $errors);
        $this->assertContains(get_string('usernameexists'), $errors['username']);

        // With a username that has an existing request.
        $data['username'] = 'request';
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('username', $errors);
        $this->assertContains(get_string('requestusernameexists', 'auth_approved'), $errors['username']);

        // Next with an invalid username.
        $data['username'] = '-ALPha-';
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('username', $errors);
        $this->assertContains('Only lowercase letters allowed', $errors['username']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('username', $errors);
        $this->assertContains('Only lowercase letters allowed', $errors['username']);

        // And again.
        $data['username'] = '-*al**pha*-';
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('username', $errors);
        $this->assertContains('The username can only contain alphanumeric lowercase', $errors['username']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('username', $errors);
        $this->assertContains('The username can only contain alphanumeric lowercase', $errors['username']);
    }

    public function test_validate_signup_form_data_organisation() {
        $this->resetAfterTest();
        /** @var totara_hierarchy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $organisation = $generator->create_org(['frameworkid' => $framework->id]);

        set_config('alloworganisation', true, 'auth_approved');
        set_config('alloworganisationfreetext', true, 'auth_approved');

        $data = array(
            'email' => 'test@example.com',
            'username' => 'test',
            'password' => 'Special-8',
            'organisationid' => $organisation->id
        );

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // With an invalid organisation id.
        $data['organisationid'] = -10;

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('organisationid', $errors);
        $this->assertContains(get_string('errorunknownorganisationid', 'auth_approved', (object)$data), $errors['organisationid']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // With an empty organisation id when required.
        set_config('requireorganisation', true, 'auth_approved');
        $data['organisationid'] = null;
        $data['organisationfreetext'] = null;

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('organisationid', $errors);
        $this->assertContains(get_string('errormissingorg', 'auth_approved'), $errors['organisationid']);
        $this->assertArrayHasKey('organisationselector', $errors);
        $this->assertContains(get_string('errormissingorg', 'auth_approved'), $errors['organisationselector']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('organisationid', $errors);
        $this->assertContains(get_string('errormissingorg', 'auth_approved'), $errors['organisationid']);
        $this->assertArrayHasKey('organisationfreetext', $errors);
        $this->assertContains(get_string('errormissingorg', 'auth_approved'), $errors['organisationfreetext']);
    }

    public function test_validate_signup_form_data_position() {
        $this->resetAfterTest();
        /** @var totara_hierarchy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $position = $generator->create_pos(['frameworkid' => $framework->id]);

        set_config('allowposition', true, 'auth_approved');
        set_config('allowpositionfreetext', true, 'auth_approved');

        $data = array(
            'email' => 'test@example.com',
            'username' => 'test',
            'password' => 'Special-8',
            'positionid' => $position->id
        );

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // With an invalid position id.
        $data['positionid'] = -10;

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('positionid', $errors);
        $this->assertContains(get_string('errorunknownpositionid', 'auth_approved', (object)$data), $errors['positionid']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // With an empty position id when required.
        set_config('requireposition', true, 'auth_approved');
        $data['positionid'] = null;
        $data['positionfreetext'] = null;

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('positionid', $errors);
        $this->assertContains(get_string('errormissingpos', 'auth_approved'), $errors['positionid']);
        $this->assertArrayHasKey('positionselector', $errors);
        $this->assertContains(get_string('errormissingpos', 'auth_approved'), $errors['positionselector']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('positionid', $errors);
        $this->assertContains(get_string('errormissingpos', 'auth_approved'), $errors['positionid']);
        $this->assertArrayHasKey('positionfreetext', $errors);
        $this->assertContains(get_string('errormissingpos', 'auth_approved'), $errors['positionfreetext']);
    }

    public function test_validate_signup_form_data_manager() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['username' => 'manager']);
        $job = \totara_job\job_assignment::create_default($user->id);

        set_config('allowmanager', true, 'auth_approved');
        set_config('allowmanagerfreetext', true, 'auth_approved');

        $data = array(
            'email' => 'test@example.com',
            'username' => 'test',
            'password' => 'Special-8',
            'managerjaid' => $job->id
        );

        // First up with a valid job id.
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertEmpty($errors);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertEmpty($errors);

        // Without a valid job id.
        $data['managerjaid'] = -10;
        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('managerjaid', $errors);
        $this->assertContains(get_string('errorunknownmanagerjaid', 'auth_approved', (object)$data), $errors['managerjaid']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('managerjaid', $errors);
        $this->assertContains(get_string('errorunknownmanagerjaid', 'auth_approved', (object)$data), $errors['managerjaid']);

        // With an empty position id when required.
        set_config('requiremanager', true, 'auth_approved');
        $data['managerjaid'] = null;
        $data['managerfreetext'] = null;

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_APPROVAL);
        $this->assertArrayHasKey('managerjaid', $errors);
        $this->assertContains(get_string('errormissingmgr', 'auth_approved'), $errors['managerjaid']);
        $this->assertArrayHasKey('managerselector', $errors);
        $this->assertContains(get_string('errormissingmgr', 'auth_approved'), $errors['managerselector']);

        $errors = \auth_approved\request::validate_signup_form_data($data, \auth_approved\request::STAGE_SIGNUP);
        $this->assertArrayHasKey('managerjaid', $errors);
        $this->assertContains(get_string('errormissingmgr', 'auth_approved'), $errors['managerjaid']);
        $this->assertArrayHasKey('managerfreetext', $errors);
        $this->assertContains(get_string('errormissingmgr', 'auth_approved'), $errors['managerfreetext']);
    }

    private function hierarchy_data($type, $no) {
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchyfn = "create_$type";
        $hierarchyframeworkfn = $hierarchyfn . "_frame";

        $frameworkids = array_map(
            function ($i) use ($generator, $hierarchyframeworkfn) {
                return (int)$generator->$hierarchyframeworkfn([])->id;
            },

            range(0, $no)
        );

        $hierarchyids = array_map(
            function ($frameworkid) use ($generator, $hierarchyfn) {
                $data = ['frameworkid' => $frameworkid];
                return (int)$generator->$hierarchyfn($data)->id;
            },

            $frameworkids
        );

        return [$frameworkids, $hierarchyids];
    }

    public function test_valid_signup_positionid() {
        global $DB;
        $this->resetAfterTest();

        list($frameworks, $hierarchies) = $this->hierarchy_data('pos', 10);
        $delimiter = 3;
        $excludedframeworks = array_slice($frameworks, $delimiter);
        $excludedpositions = array_slice($hierarchies, $delimiter);
        $includedpositions = array_slice($hierarchies, 0, $delimiter);
        $includedframeworks = array_slice($frameworks, 0, $delimiter);
        $included = implode(',', $includedframeworks);

        set_config('allowposition', false, 'auth_approved');
        set_config('positionframeworks', $included, 'auth_approved');
        $validpositionid = $includedpositions[0];
        $this->assertFalse(\auth_approved\request::is_valid_signup_positionid($validpositionid), 'id not required');

        set_config('allowposition', true, 'auth_approved');
        set_config('positionframeworks', $included, 'auth_approved');
        $maxid = $DB->get_field_sql('SELECT MAX(id) from {pos}');
        $this->assertFalse(\auth_approved\request::is_valid_signup_positionid($maxid + 1), 'non existent id');
        $this->assertFalse(\auth_approved\request::is_valid_signup_positionid(0), 'empty id is true');

        set_config('allowposition', true, 'auth_approved');
        set_config('positionframeworks', '', 'auth_approved');
        $this->assertTrue(\auth_approved\request::is_valid_signup_positionid($excludedpositions[0]), 'all positions allowed');

        set_config('allowposition', true, 'auth_approved');
        set_config('positionframeworks', $included, 'auth_approved');
        foreach ($includedpositions as $id) {
            $this->assertTrue(\auth_approved\request::is_valid_signup_positionid($id), 'allowed positions');
        }
        foreach ($excludedpositions as $id) {
            $this->assertFalse(\auth_approved\request::is_valid_signup_positionid($id), 'disallowed positions');
        }
    }

    public function test_valid_signup_organisationid() {
        global $DB;
        $this->resetAfterTest();

        list($frameworks, $hierarchies) = $this->hierarchy_data('org', 10);
        $delimiter = 5;
        $excludedframeworks = array_slice($frameworks, $delimiter);
        $excludedorganisations = array_slice($hierarchies, $delimiter);
        $includedorganisations = array_slice($hierarchies, 0, $delimiter);
        $includedframeworks = array_slice($frameworks, 0, $delimiter);
        $included = implode(',', $includedframeworks);

        set_config('alloworganisation', false, 'auth_approved');
        set_config('organisationframeworks', $included, 'auth_approved');
        $validorganisationid = $includedorganisations[0];
        $this->assertFalse(\auth_approved\request::is_valid_signup_organisationid($validorganisationid), 'id not required');

        set_config('alloworganisation', true, 'auth_approved');
        set_config('organisationframeworks', $included, 'auth_approved');
        $maxid = $DB->get_field_sql('SELECT MAX(id) from {org}');
        $this->assertFalse(\auth_approved\request::is_valid_signup_organisationid($maxid + 1), 'non existent id');
        $this->assertFalse(\auth_approved\request::is_valid_signup_organisationid(0), 'empty id is true');

        set_config('alloworganisation', true, 'auth_approved');
        set_config('organisationframeworks', '', 'auth_approved');
        $this->assertTrue(\auth_approved\request::is_valid_signup_organisationid($excludedorganisations[0]), 'all organisations allowed');

        set_config('alloworganisation', true, 'auth_approved');
        set_config('organisationframeworks', $included, 'auth_approved');
        foreach ($includedorganisations as $id) {
            $this->assertTrue(\auth_approved\request::is_valid_signup_organisationid($id), 'allowed organisations');
        }
        foreach ($excludedorganisations as $id) {
            $this->assertFalse(\auth_approved\request::is_valid_signup_organisationid($id), 'disallowed organisations');
        }
    }

    public function test_valid_signup_managerjaid() {
        global $DB;
        $this->resetAfterTest();

        list($frameworks, $hierarchies) = $this->hierarchy_data('org', 10);
        $delimiter = 5;
        $excludedorgframeworks = array_slice($frameworks, $delimiter);
        $excludedorganisations = array_slice($hierarchies, $delimiter);
        $includedorganisations = array_slice($hierarchies, 0, $delimiter);
        $includedorgframeworks = array_slice($frameworks, 0, $delimiter);
        $includedorg = implode(',', $includedorgframeworks);

        list($frameworks, $hierarchies) = $this->hierarchy_data('pos', 6);
        $delimiter = 2;
        $excludedposframeworks = array_slice($frameworks, $delimiter);
        $excludedpositions = array_slice($hierarchies, $delimiter);
        $includedpositions = array_slice($hierarchies, 0, $delimiter);
        $includedorgframeworks = array_slice($frameworks, 0, $delimiter);
        $includedpos = implode(',', $includedorgframeworks);

        $includedjasbyposition = [];
        foreach ($excludedorganisations as $orgid) {
            foreach ($includedpositions as $posid) {
                $userid = $this->getDataGenerator()->create_user()->id;
                $ja = \totara_job\job_assignment::create([
                    'userid' => $userid,
                    'idnumber' => "$userid",
                    'positionid' => $posid,
                    'organisationid' => $orgid
                ]);
                $includedjasbyposition[] = $ja->id;
            }
        }

        $includedjasbyorganisation = [];
        foreach ($includedorganisations as $orgid) {
            foreach ($excludedpositions as $posid) {
                $userid = $this->getDataGenerator()->create_user()->id;
                $ja = \totara_job\job_assignment::create([
                    'userid' => $userid,
                    'idnumber' => "$userid",
                    'positionid' => $posid,
                    'organisationid' => $orgid
                ]);
                $includedjasbyorganisation[] = $ja->id;
            }
        }

        $includedjasbyboth = [];
        foreach ($includedorganisations as $orgid) {
            foreach ($includedpositions as $posid) {
                $userid = $this->getDataGenerator()->create_user()->id;
                $ja = \totara_job\job_assignment::create([
                    'userid' => $userid,
                    'idnumber' => "$userid",
                    'positionid' => $posid,
                    'organisationid' => $orgid
                ]);
                $includedjasbyboth[] = $ja->id;
            }
        }

        $excludedjas[] = \totara_job\job_assignment::create([
            'userid' => $this->getDataGenerator()->create_user()->id,
            'idnumber' => "aaa"
        ])->id;
        foreach ($excludedorganisations as $orgid) {
            foreach ($excludedpositions as $posid) {
                $userid = $this->getDataGenerator()->create_user()->id;
                $ja = \totara_job\job_assignment::create([
                    'userid' => $userid,
                    'idnumber' => "$userid",
                    'positionid' => $posid,
                    'organisationid' => $orgid
                ]);
                $excludedjas[] = $ja->id;
            }
        }

        set_config('allowmanager', false, 'auth_approved');
        set_config('managerorganisationframeworks', $includedorg, 'auth_approved');
        set_config('managerpositionframeworks', $includedpos, 'auth_approved');
        $this->assertFalse(\auth_approved\request::is_valid_signup_mgrjaid($includedjasbyboth[0]), 'ja not required');

        set_config('allowmanager', true, 'auth_approved');
        set_config('managerorganisationframeworks', $includedorg, 'auth_approved');
        set_config('managerpositionframeworks', $includedpos, 'auth_approved');
        $maxid = $DB->get_field_sql('SELECT MAX(id) from {job_assignment}');
        $this->assertFalse(\auth_approved\request::is_valid_signup_mgrjaid($maxid + 1), 'non existent jaid');
        $this->assertFalse(\auth_approved\request::is_valid_signup_mgrjaid(0), 'empty jaid is true');

        set_config('allowmanager', true, 'auth_approved');
        set_config('managerorganisationframeworks', '', 'auth_approved');
        set_config('managerpositionframeworks', '', 'auth_approved');
        $this->assertTrue(\auth_approved\request::is_valid_signup_mgrjaid($excludedjas[0]), 'all jas allowed');

        set_config('allowmanager', true, 'auth_approved');
        set_config('managerorganisationframeworks', $includedorg, 'auth_approved');
        set_config('managerpositionframeworks', $includedpos, 'auth_approved');
        foreach ($includedjasbyposition as $id) {
            $this->assertTrue(\auth_approved\request::is_valid_signup_mgrjaid($id), 'allowed ja by position');
        }
        foreach ($includedjasbyorganisation as $id) {
            $this->assertTrue(\auth_approved\request::is_valid_signup_mgrjaid($id), 'allowed ja by organisation');
        }
        foreach ($includedjasbyboth as $id) {
            $this->assertTrue(\auth_approved\request::is_valid_signup_mgrjaid($id), 'allowed ja by both');
        }
        foreach ($excludedjas as $id) {
            $this->assertFalse(\auth_approved\request::is_valid_signup_mgrjaid($id), 'disallowed jas');
        }
    }
}