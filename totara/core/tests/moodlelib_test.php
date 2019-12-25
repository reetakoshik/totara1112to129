<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests of our upstream hacks.
 */
class totara_core_moodlelib_testcase extends advanced_testcase {
    public function test_preprocess_param_url() {
        global $CFG;

        // Make sure the special characters are encoded properly.
        $url = "http://www.example.com/?whatever='\" \t\n&bbb={1,2}&amp;c=<br>";
        $result = preprocess_param_url($url);
        $this->assertSame('http://www.example.com/?whatever=%27%22%20%09%0A&bbb=%7B1,2%7D&amp;c=%3Cbr%3E', $result);
        $this->assertSame($url, urldecode($result));

        $this->assertSame('', preprocess_param_url(''));
        $this->assertSame('ssh://username@hostname:/path', preprocess_param_url('ssh://username@hostname:/path'));
        $this->assertSame('http://username@hostname:/path', preprocess_param_url('http://username@hostname:/path'));

        // Invalid data passes through without changes.
        $this->assertSame('://', preprocess_param_url('://'));
        $this->assertSame('aa/bb/:xx', preprocess_param_url('aa/bb/:xx'));

        // Leading double slash is fixed.
        $this->assertSame('https://example.com/', preprocess_param_url('//example.com/'));
        $CFG->wwwroot = 'http:/nowhere.example.com';
        $this->assertSame('http://example.com/', preprocess_param_url('//example.com/'));
    }

    /**
     * Test encoding of dangerous and incompatible characters in URLs.
     */
    public function test_clean_param_url() {
        // Make sure the special characters are encoded properly.
        $url = "http://www.example.com/?whatever='\" \t\n&bbb={1,2}&amp;c=<br>";
        $result = clean_param($url, PARAM_URL);
        $this->assertSame('http://www.example.com/?whatever=%27%22%20%09%0A&bbb=%7B1,2%7D&amp;c=%3Cbr%3E', $result);
        $this->assertSame($url, urldecode($result));

        // Only these 3 protocols are supported.
        $this->assertSame('http://www.example.com/course/view.php?id=1', clean_param('http://www.example.com/course/view.php?id=1', PARAM_URL));
        $this->assertSame('https://www.example.com/course/view.php?id=:1', clean_param('https://www.example.com/course/view.php?id=:1', PARAM_URL));
        $this->assertSame('ftp://www.example.com/index.html', clean_param('ftp://www.example.com/index.html', PARAM_URL));
        $this->assertSame('', clean_param('gpher://www.example.com/index.html', PARAM_URL));

        // Protocol case is not important.
        $this->assertSame('HttP://www.example.com/course/view.php?id=1', clean_param('HttP://www.example.com/course/view.php?id=1', PARAM_URL));

        $this->assertSame('https://www.example.com/course/view.php?id=1', clean_param('//www.example.com/course/view.php?id=1', PARAM_URL));
        $this->assertSame('', clean_param('://www.example.com/course/view.php?id=1', PARAM_URL));
        $this->assertSame('www.example.com/course/view.php?id=1', clean_param('www.example.com/course/view.php?id=1', PARAM_URL));

        // Ports are allowed.
        $this->assertSame('http://www.example.com:8080/course/view.php?id=1', clean_param('http://www.example.com:8080/course/view.php?id=1', PARAM_URL));
        $this->assertSame('https://www.example.com:443/course/view.php?id=1', clean_param('https://www.example.com:443/course/view.php?id=1', PARAM_URL));

        // Incomplete URLs should pass.
        $this->assertSame('/course/view.php?id=1', clean_param('/course/view.php?id=1', PARAM_URL));
        $this->assertSame('course/view.php?id=1', clean_param('course/view.php?id=1', PARAM_URL));

        // Various arguments should be ok, some of them may be URL encoded
        $this->assertSame('http://www.example.com/course/view.php?id=13#test', clean_param('http://www.example.com/course/view.php?id=13#test', PARAM_URL));
        $this->assertSame('http://www.example.com/?whatever%5B%5D=abc', clean_param('http://www.example.com/?whatever[]=abc', PARAM_URL));
        $this->assertSame('http://www.example.com/?whatever%5B0%5D=abc&%5B1%5D=def', clean_param('http://www.example.com/?whatever[0]=abc&[1]=def', PARAM_URL));
        $this->assertSame('/?whatever%5B%5D=abc', clean_param('/?whatever[]=abc', PARAM_URL));
        $this->assertSame('/course/view.php?id=%3A1', clean_param('/course/view.php?id=:1', PARAM_URL));
        $this->assertSame('course/view.php?id=%3A1', clean_param('course/view.php?id=:1', PARAM_URL));

        // mailto: never worked and never will
        $this->assertSame('', clean_param('mailto:someone@example.com', PARAM_URL));

        // Non-ascii characters never worked.
        $this->assertSame('', clean_param('http://www.example.com/course/view.php?id=škoďák', PARAM_URL));
        $this->assertSame('', clean_param('http://www.example.com/course/škoďák.php', PARAM_URL));
        $this->assertSame('', clean_param('http://www.example.com/course/view.php#škoďák', PARAM_URL));

        // Broken URLs.
        $this->assertSame('', clean_param('://www.example.com/course/view.php?id=1', PARAM_URL));
        $this->assertSame('', clean_param(' http://www.example.com/course/view.php?id=1', PARAM_URL));
        $this->assertSame('', clean_param('http://', PARAM_URL));
        $this->assertSame('', clean_param(' ', PARAM_URL));
        $this->assertSame('', clean_param('whatever[]=abc', PARAM_URL));
        $this->assertSame('', clean_param('[]', PARAM_URL));
        $this->assertSame('', clean_param('{}', PARAM_URL));
    }

    public function test_clean_param_url_preexisting() {
        global $CFG;
        include_once($CFG->dirroot . '/lib/validateurlsyntax.php');

        $oldclean = function ($url) {
            if (validateUrlSyntax($url, 's?H?S?F?E?u-P-a?I?p?f?q?r?')) {
                return $url;
            }
            return '';
        };

        $url = "http://www.example.com/?whatever='\" \t\n&bbb={1,2}&amp;c=<br>";
        $this->assertSame('', $oldclean($url)); // Fixed in new cleaning

        // Only these 3 protocols are supported.
        $this->assertSame('http://www.example.com/course/view.php?id=1', $oldclean('http://www.example.com/course/view.php?id=1'));
        $this->assertSame('https://www.example.com/course/view.php?id=:1', $oldclean('https://www.example.com/course/view.php?id=:1'));
        $this->assertSame('ftp://www.example.com/index.html', $oldclean('ftp://www.example.com/index.html'));
        $this->assertSame('', $oldclean('gpher://www.example.com/index.html'));

        // Protocol case is not important.
        $this->assertSame('HttP://www.example.com/course/view.php?id=1', $oldclean('HttP://www.example.com/course/view.php?id=1'));

        $this->assertSame('', $oldclean('//www.example.com/course/view.php?id=1')); // Fixed in new cleaning
        $this->assertSame('', $oldclean('://www.example.com/course/view.php?id=1'));
        $this->assertSame('www.example.com/course/view.php?id=1', $oldclean('www.example.com/course/view.php?id=1'));

        // Incomplete URLs should pass.
        $this->assertSame('/course/view.php?id=1', $oldclean('/course/view.php?id=1'));
        $this->assertSame('course/view.php?id=1', $oldclean('course/view.php?id=1'));

        // Ports are allowed.
        $this->assertSame('http://www.example.com:8080/course/view.php?id=1', $oldclean('http://www.example.com:8080/course/view.php?id=1'));
        $this->assertSame('https://www.example.com:443/course/view.php?id=1', $oldclean('https://www.example.com:443/course/view.php?id=1'));

        // Various arguments should be ok, some of them may be URL encoded
        $this->assertSame('http://www.example.com/course/view.php?id=13#test', $oldclean('http://www.example.com/course/view.php?id=13#test'));
        $this->assertSame('', $oldclean('http://www.example.com/?whatever[]=abc')); // Fixed in new cleaning
        $this->assertSame('', $oldclean('http://www.example.com/?whatever[0]=abc&[1]=def')); // Fixed in new cleaning
        $this->assertSame('', $oldclean('/?whatever[]=abc')); // Fixed in new cleaning
        $this->assertSame('/course/view.php?id=:1', $oldclean('/course/view.php?id=:1')); // Changed in new cleaning
        $this->assertSame('course/view.php?id=:1', $oldclean('course/view.php?id=:1')); // Changed in new cleaning

        // mailto: never worked and never will
        $this->assertSame('', $oldclean('mailto:someone@example.com'));

        // Non-ascii characters never worked.
        $this->assertSame('', $oldclean('http://www.example.com/course/view.php?id=škoďák'));
        $this->assertSame('', $oldclean('http://www.example.com/course/škoďák.php'));
        $this->assertSame('', $oldclean('http://www.example.com/course/view.php#škoďák'));

        // Broken URLs.
        $this->assertSame('', $oldclean('://www.example.com/course/view.php?id=1'));
        $this->assertSame('', $oldclean(' http://www.example.com/course/view.php?id=1'));
        $this->assertSame('http://', $oldclean('http://')); // Fixed in new cleaning
        $this->assertSame('', $oldclean(' '));
        $this->assertSame('', $oldclean('whatever[]=abc'));
        $this->assertSame('', $oldclean('[]'));
        $this->assertSame('', $oldclean('{}'));
    }

    /**
     * Test fix for Safari lang detection.
     *
     */
    public function test_setup_lang_from_browser() {
        global $SESSION, $USER, $CFG;
        $this->resetAfterTest();

        $this->assertNotEmpty($CFG->autolang);
        $USER->lang = '';

        $SESSION->lang = '';
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        setup_lang_from_browser();
        $this->assertSame('', $SESSION->lang);

        $SESSION->lang = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        setup_lang_from_browser();
        $this->assertSame('en', $SESSION->lang);

        $SESSION->lang = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-us';
        setup_lang_from_browser();
        $this->assertSame('en', $SESSION->lang);

        $SESSION->lang = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-GB,en-US;q=0.8,en;q=0.6';
        setup_lang_from_browser();
        $this->assertSame('en', $SESSION->lang);

        $SESSION->lang = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'xx-zz';
        setup_lang_from_browser();
        $this->assertSame('', $SESSION->lang);

        $CFG->autolang = 0;
        $SESSION->lang = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        setup_lang_from_browser();
        $this->assertSame('', $SESSION->lang);

        $CFG->autolang = 1;
        $USER->lang = 'cs';
        $SESSION->lang = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        setup_lang_from_browser();
        $this->assertSame('', $SESSION->lang);

        $CFG->autolang = 1;
        $USER->lang = '';
        $SESSION->lang = 'cs';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        setup_lang_from_browser();
        $this->assertSame('cs', $SESSION->lang);
    }

    public function test_delete_user() {
        global $DB, $CFG;

        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user(array('idnumber' => 'abc'));
        $user2 = $this->getDataGenerator()->create_user(array('idnumber' => 'xyz'));
        $user3 = $this->getDataGenerator()->create_user(array('idnumber' => 'opq'));

        $this->assertTrue($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $user1->id)));
        $this->assertTrue($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $user2->id)));
        $this->assertTrue($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $user3->id)));

        // Delete user the proper way.
        $this->assertSame('fullproper', $CFG->authdeleteusers);
        $result = delete_user($user3);

        $this->assertTrue($result);
        $deluser = $DB->get_record('user', array('id' => $user3->id), '*', MUST_EXIST);
        $this->assertEquals(1, $deluser->deleted);
        $this->assertEquals(0, $deluser->picture);
        $this->assertSame('', $deluser->idnumber);
        $this->assertSame('', $deluser->email);
        $this->assertRegExp('/^deleted_[a-z0-9]+$/', $deluser->username);
        $this->assertSame(AUTH_PASSWORD_NOT_CACHED, $deluser->password);
        $this->assertFalse($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $user3->id)));

        // Delete user the Moodle way.
        $CFG->authdeleteusers = 'full';
        $result = delete_user($user1);

        // Test user is deleted in DB.
        $this->assertTrue($result);
        $deluser = $DB->get_record('user', array('id' => $user1->id), '*', MUST_EXIST);
        $this->assertEquals(1, $deluser->deleted);
        $this->assertEquals(0, $deluser->picture);
        $this->assertSame('', $deluser->idnumber);
        $this->assertSame(md5($user1->username), $deluser->email);
        $this->assertRegExp('/^' . preg_quote($user1->email, '/') . '\.\d*$/', $deluser->username);
        $this->assertSame($user1->password, $deluser->password);
        $this->assertFalse($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $user1->id)));

        // Delete user the old Totara way.
        $CFG->authdeleteusers = 'partial';
        $result = delete_user($user2);

        // Test user is deleted in DB.
        $this->assertTrue($result);
        $deluser = $DB->get_record('user', array('id' => $user2->id), '*', MUST_EXIST);
        $this->assertEquals(1, $deluser->deleted);
        $this->assertEquals(0, $deluser->picture);
        $this->assertEquals($user2->picture, $deluser->picture);
        $this->assertSame($user2->idnumber, $deluser->idnumber);
        $this->assertSame($user2->username, $deluser->username);
        $this->assertSame($user2->email, $deluser->email);
        $this->assertSame($user2->password, $deluser->password);
        $this->assertFalse($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $user2->id)));
    }

    public function test_undelete_user_context() {
        global $DB, $CFG;
        $this->resetAfterTest();

        $CFG->authdeleteusers = 'partial';

        $user1 = $this->getDataGenerator()->create_user(array());
        $user2 = $this->getDataGenerator()->create_user(array());
        $user3 = $this->getDataGenerator()->create_user(array());

        $context1 = context_user::instance($user1->id);
        $context2 = context_user::instance($user2->id);
        $context3 = context_user::instance($user3->id);

        delete_user($user2);
        $deleteduser2 = $DB->get_record('user', array('id' => $user2->id));
        delete_user($user3);
        $deleteduser3 = $DB->get_record('user', array('id' => $user3->id));

        $user4 = $this->getDataGenerator()->create_user(array());
        $context4 = context_user::instance($user4->id);

        $this->assertGreaterThan($context3->id, $context4->id);
        undelete_user($deleteduser2);
        undelete_user($deleteduser3);

        $context2b = context_user::instance($user2->id);
        $context3b = context_user::instance($user3->id);
        $this->assertSame($context2->id, $context2b->id);
        $this->assertSame($context3->id, $context3b->id);
    }

    /**
     * Totara specific tests for sending of emails.
     */
    public function test_email_to_user() {
        global $DB, $CFG;
        $this->resetAfterTest();
        $sink = $this->redirectEmails();
        $CFG->noemailever = 0;

        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();

        // Everything fine.

        $result = email_to_user($user, $admin, 'subject', 'message');
        $this->assertTrue($result);
        $this->assertCount(1, $sink->get_messages());
        $sink->clear();

        // Missing stuff.

        $u = new stdClass();
        $u->id = $user->id;
        $u->email = $user->email;
        $u->username = $user->username;

        $result = email_to_user($u, $admin, 'subject', 'message');
        $this->assertTrue($result);
        $this->assertCount(1, $sink->get_messages());
        $this->assertSame($user->id, $u->id);
        $this->assertSame($user->deleted, $u->deleted);
        $this->assertSame($user->suspended, $u->suspended);
        $this->assertSame($user->auth, $u->auth);
        $this->assertSame($user->mailformat, $u->mailformat);
        $sink->clear();

        // Suspended user with all details.

        $DB->set_field('user', 'suspended', '1', array('id' => $user->id));
        $user = $DB->get_record('user', array('id' => $user->id));

        $result = email_to_user($user, $admin, 'subject', 'message');
        $this->assertTrue($result);
        $this->assertCount(0, $sink->get_messages());
        $sink->clear();

        // Suspended user with missing info.

        $u = new stdClass();
        $u->id = $user->id;
        $u->email = $user->email;
        $user = $DB->get_record('user', array('id' => $user->id));

        $result = email_to_user($u, $admin, 'subject', 'message');
        $this->assertTrue($result);
        $this->assertCount(0, $sink->get_messages());
        $this->assertSame($user->id, $u->id);
        $this->assertSame($user->deleted, $u->deleted);
        $this->assertSame($user->suspended, $u->suspended);
        $this->assertSame($user->auth, $u->auth);
        $this->assertSame($user->mailformat, $u->mailformat);
        $sink->clear();

        // No messing with external Totara users.

        $u = \totara_core\totara_user::get_external_user('ext@example.com');
        $prevu = clone($u);

        $result = email_to_user($u, $admin, 'subject', 'message');
        $this->assertTrue($result);
        $this->assertCount(1, $sink->get_messages());
        $this->assertEquals($prevu, $u);
        $sink->clear();
    }

    /**
     * Test from in emails
     */
    public function test_email_to_user_from() {
        $this->resetAfterTest();

        $noreplyaddress = 'mynoreply@example.com';
        $subject = 'My subject';
        $noreplytext = get_string('noreplyname');

        $userto = $this->getDataGenerator()->create_user();
        $userfrom = $this->getDataGenerator()->create_user(array('maildisplay' => 1));
        $userfrom2 = $this->getDataGenerator()->create_user(array('maildisplay' => 0));


        set_config('noreplyaddress', $noreplyaddress);
        set_config('emailfromvia', '0');

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom), $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($userfrom->email, $email->replyto);
        $this->assertSame(fullname($userfrom), $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom2, $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom2), $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($noreplyaddress, $email->replyto);
        $this->assertSame($noreplytext, $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', false);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom), $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($noreplyaddress, $email->replyto);
        $this->assertSame($noreplytext, $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', true, 'other@example.com', 'Other');
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom), $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame('other@example.com', $email->replyto);
        $this->assertSame('Other', $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, 'Fantomas', $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame('Fantomas', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($noreplyaddress, $email->replyto);
        $this->assertSame($noreplytext, $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, 'Fantomas', $subject, 'My text message', 'My html message', '', '', true, 'other@example.com', 'Other');
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame('Fantomas', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame('other@example.com', $email->replyto);
        $this->assertSame('Other', $email->replytoname);

        set_config('noreplyaddress', '');
        $sink = $this->redirectEmails();
        $this->assertDebuggingNotCalled();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingCalled('email_to_user: Missing $CFG->noreplyaddress');
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame('noreply@www.example.com', $email->from);
        $this->assertSame(fullname($userfrom), $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($userfrom->email, $email->replyto);
        $this->assertSame(fullname($userfrom), $email->replytoname);
    }

    /**
     * Test from in emails with emailfromvia enabled.
     */
    public function test_email_to_user_from_with_via() {
        $this->resetAfterTest();

        $noreplyaddress = 'mynoreply@example.com';
        $subject = 'My subject';
        $noreplytext = get_string('noreplyname');

        $userto = $this->getDataGenerator()->create_user();
        $userfrom = $this->getDataGenerator()->create_user(array('maildisplay' => 1));
        $userfrom2 = $this->getDataGenerator()->create_user(array('maildisplay' => 0));

        set_config('noreplyaddress', $noreplyaddress);
        set_config('emailfromvia', '1');

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom) . ' (via phpunit)', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($userfrom->email, $email->replyto);
        $this->assertSame(fullname($userfrom), $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom2, $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom2) . ' (via phpunit)', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($noreplyaddress, $email->replyto);
        $this->assertSame($noreplytext, $email->replytoname);


        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', false);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom) . ' (via phpunit)', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($noreplyaddress, $email->replyto);
        $this->assertSame($noreplytext, $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', true, 'other@example.com', 'Other');
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame(fullname($userfrom) . ' (via phpunit)', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame('other@example.com', $email->replyto);
        $this->assertSame('Other', $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, 'Fantomas', $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame('Fantomas' . ' (via phpunit)', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($noreplyaddress, $email->replyto);
        $this->assertSame($noreplytext, $email->replytoname);

        $sink = $this->redirectEmails();
        $result = email_to_user($userto, 'Fantomas', $subject, 'My text message', 'My html message', '', '', true, 'other@example.com', 'Other');
        $this->assertTrue($result);
        $this->assertDebuggingNotCalled();
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame($noreplyaddress, $email->from);
        $this->assertSame('Fantomas' . ' (via phpunit)', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame('other@example.com', $email->replyto);
        $this->assertSame('Other', $email->replytoname);

        set_config('noreplyaddress', '');
        $sink = $this->redirectEmails();
        $this->assertDebuggingNotCalled();
        $result = email_to_user($userto, $userfrom, $subject, 'My text message', 'My html message', '', '', true);
        $this->assertTrue($result);
        $this->assertDebuggingCalled('email_to_user: Missing $CFG->noreplyaddress');
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = $emails[0];
        $this->assertSame($subject, $email->subject);
        $this->assertSame('noreply@www.example.com', $email->from);
        $this->assertSame(fullname($userfrom) . ' (via phpunit)', $email->fromname);
        $this->assertSame($userto->email, $email->to);
        $this->assertSame(fullname($userto), $email->toname);
        $this->assertSame($userfrom->email, $email->replyto);
        $this->assertSame(fullname($userfrom), $email->replytoname);
    }

    public function test_fullname() {
        global $CFG;
        $this->resetAfterTest();

        $this->assertSame('language', $CFG->fullnamedisplay);
        $this->assertSame('language', $CFG->alternativefullnameformat);

        $user = $this->getDataGenerator()->create_user(array(
            'firstname' => 'Krestni', 'lastname' => 'Prijmeni',
            'firstnamephonetic' => 'Křestní', 'lastnamephonetic' => 'Příjmení',
            'middlename' => 'Prostredni', 'alternatename' => 'prezdivka'));

        $CFG->fullnamedisplay = '';
        $CFG->alternativefullnameformat = 'language';
        $this->assertSame('Krestni Prijmeni', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        $CFG->fullnamedisplay = 'language';
        $CFG->alternativefullnameformat = 'language';
        $this->assertSame('Krestni Prijmeni', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        $CFG->fullnamedisplay = 'firstname lastname firstnamephonetic lastnamephonetic middlename alternatename';
        $CFG->alternativefullnameformat = 'language';
        $this->assertSame('Krestni Prijmeni Křestní Příjmení Prostredni prezdivka', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        $CFG->fullnamedisplay = 'firstname';
        $CFG->alternativefullnameformat = 'language';
        $this->assertSame('Krestni', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        // Alternative empty.
        $CFG->fullnamedisplay = '';
        $CFG->alternativefullnameformat = '';
        $this->assertSame('Krestni Prijmeni', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        $CFG->fullnamedisplay = 'language';
        $CFG->alternativefullnameformat = '';
        $this->assertSame('Krestni Prijmeni', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        $CFG->fullnamedisplay = 'firstname lastname firstnamephonetic lastnamephonetic middlename alternatename';
        $CFG->alternativefullnameformat = '';
        $this->assertSame('Krestni Prijmeni Křestní Příjmení Prostredni prezdivka', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        $CFG->fullnamedisplay = 'firstname';
        $CFG->alternativefullnameformat = '';
        $this->assertSame('Krestni', fullname($user));
        $this->assertSame('Krestni Prijmeni', fullname($user, true));

        // Alternative set.
        $CFG->fullnamedisplay = '';
        $CFG->alternativefullnameformat = 'alternatename';
        $this->assertSame('Krestni Prijmeni', fullname($user));
        $this->assertSame('prezdivka', fullname($user, true));

        $CFG->fullnamedisplay = 'language';
        $CFG->alternativefullnameformat = 'alternatename';
        $this->assertSame('Krestni Prijmeni', fullname($user));
        $this->assertSame('prezdivka', fullname($user, true));

        $CFG->fullnamedisplay = 'firstname lastname firstnamephonetic lastnamephonetic middlename alternatename';
        $CFG->alternativefullnameformat = 'alternatename';
        $this->assertSame('Krestni Prijmeni Křestní Příjmení Prostredni prezdivka', fullname($user));
        $this->assertSame('prezdivka', fullname($user, true));

        $CFG->fullnamedisplay = 'firstname';
        $CFG->alternativefullnameformat = 'alternatename';
        $this->assertSame('Krestni', fullname($user));
        $this->assertSame('prezdivka', fullname($user, true));
    }

    /**
     * Test all strftime() parameters.
     */
    public function test_date_format_string() {
        // Note: by default phpunit is using AU locale and Perth timezone.
        $time = make_timestamp(1975, 4, 6, 3, 5, 6);
        $this->assertsame(165956706, $time);
        $this->assertSame('Australia/Perth', date_default_timezone_get());

        $this->assertSame('Sun', date_format_string($time, '%a'));
        $this->assertSame('Sunday', date_format_string($time, '%A'));
        $this->assertSame('06', date_format_string($time, '%d'));
        $this->assertSame(' 6', date_format_string($time, '%e'));
        $this->assertSame('096', date_format_string($time, '%j'));
        $this->assertSame('7', date_format_string($time, '%u'));
        $this->assertSame('14', date_format_string($time, '%U'));
        $this->assertSame('14', date_format_string($time, '%V'));
        $this->assertSame('13', date_format_string($time, '%W'));
        $this->assertSame('Apr', date_format_string($time, '%b'));
        $this->assertSame('April', date_format_string($time, '%B'));
        $this->assertSame('Apr', date_format_string($time, '%h'));
        $this->assertSame('04', date_format_string($time, '%m'));
        $this->assertSame('19', date_format_string($time, '%C'));
        $this->assertSame('75', date_format_string($time, '%g'));
        $this->assertSame('1975', date_format_string($time, '%G'));
        $this->assertSame('75', date_format_string($time, '%y'));
        $this->assertSame('1975', date_format_string($time, '%Y'));
        $this->assertSame('03', date_format_string($time, '%H'));
        $this->assertSame(' 3', date_format_string($time, '%k'));
        $this->assertSame('03', date_format_string($time, '%I'));
        $this->assertSame(' 3', date_format_string($time, '%l'));
        $this->assertSame('05', date_format_string($time, '%M'));
        $this->assertSame('AM', date_format_string($time, '%p'));
        $this->assertSame('am', date_format_string($time, '%P'));
        $this->assertSame('03:05:06 AM', date_format_string($time, '%r'));
        $this->assertSame('03:05', date_format_string($time, '%R'));
        $this->assertSame('06', date_format_string($time, '%S'));
        $this->assertSame('03:05:06', date_format_string($time, '%T'));
        $this->assertSame('04/06/75', date_format_string($time, '%D'));
        $this->assertSame('1975-04-06', date_format_string($time, '%F'));
        $this->assertSame("$time", date_format_string($time, '%s')); // Real Unix timestamp in UTC timezone, strftime returns weird stuff.
        $this->assertSame("\n", date_format_string($time, '%n'));
        $this->assertSame("\t", date_format_string($time, '%t'));
        $this->assertSame('%', date_format_string($time, '%%'));
        $this->assertSame('+0800', date_format_string($time, '%z'));
        $this->assertSame('+0000', date_format_string($time, '%z', 'UTC'));
        $this->assertSame('-0400', date_format_string($time, '%z', 'America/New_York'));
        $this->assertSame('AWST', date_format_string($time, '%Z'));

        // These have variable result - depend on OS and locale.
        $this->assertNotEmpty(date_format_string($time, '%c')); // Something like 'Sun  6 Apr 03:05:06 1975'.
        $this->assertNotEmpty(date_format_string($time, '%x')); // Something like '06/04/1975'.
        $this->assertNotEmpty(date_format_string($time, '%X')); // Something like '03:05:06'.

        // Some extra tests for the magic replacement regex.
        $this->assertSame('%AM %p', date_format_string($time, '%%%p %%p'));
        $this->assertSame('% 6 %e', date_format_string($time, '%%%e %%e'));

        // Now the weird ISO leap weeks stuff - see https://en.wikipedia.org/wiki/ISO_week_date
        $time = make_timestamp(2005, 1, 2, 3, 4, 5);
        $this->assertSame('53', date_format_string($time, '%V'));
        $this->assertSame('04', date_format_string($time, '%g'));
        $this->assertSame('2004', date_format_string($time, '%G'));
        $this->assertSame('7', date_format_string($time, '%u'));
        $time = make_timestamp(2008, 12, 30, 3, 4, 5);
        $this->assertSame('01', date_format_string($time, '%V'));
        $this->assertSame('09', date_format_string($time, '%g'));
        $this->assertSame('2009', date_format_string($time, '%G'));
        $this->assertSame('2', date_format_string($time, '%u'));
    }

    /**
     * Test remove_dir with an empty temp directory.
     */
    public function test_remove_dir_with_empty_temp_dir() {
        global $CFG;

        $pattern = $CFG->tempdir.'/*';
        $structure_before = glob($pattern);

        $dir = make_temp_directory('remove_dir_test');

        $structure_during = glob($pattern);
        $this->assertNotSame($structure_before, $structure_during);
        $structure_difference = array_diff($structure_during, $structure_before);
        $this->assertCount(1, $structure_difference);
        $this->assertSame(reset($structure_difference), $dir);

        $this->assertSame($CFG->tempdir.'/remove_dir_test', $dir);
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_writable($dir));
        $this->assertTrue(remove_dir($dir));
        $this->assertFileNotExists($dir);
        $this->assertFalse(is_dir($dir));

        $structure_after = glob($pattern);
        // Check that the structure before matches exactly the structure after.
        $this->assertSame($structure_before, $structure_after);
    }

    /**
     * Test remove_dir with an empty cache directory.
     */
    public function test_remove_dir_with_empty_cache_dir() {
        global $CFG;

        $pattern = $CFG->cachedir.'/*';
        $structure_before = glob($pattern);

        $dir = make_cache_directory('remove_dir_test');

        $structure_during = glob($pattern);
        $this->assertNotSame($structure_before, $structure_during);
        $structure_difference = array_diff($structure_during, $structure_before);
        $this->assertCount(1, $structure_difference);
        $this->assertSame(reset($structure_difference), $dir);

        $this->assertSame($CFG->cachedir.'/remove_dir_test', $dir);
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_writable($dir));
        $this->assertTrue(remove_dir($dir));
        $this->assertFileNotExists($dir);
        $this->assertFalse(is_dir($dir));

        $structure_after = glob($pattern);
        // Check that the structure before matches exactly the structure after.
        $this->assertSame($structure_before, $structure_after);
    }

    /**
     * Test remove_dir with a temp directory that contains files.
     */
    public function test_remove_dir_with_temp_dir() {
        global $CFG;

        $pattern = $CFG->tempdir.'/*';
        $structure_before = glob($pattern);

        $dir = make_temp_directory('remove_dir_test');

        $structure_during = glob($pattern);
        $this->assertNotSame($structure_before, $structure_during);
        $structure_difference = array_diff($structure_during, $structure_before);
        $this->assertCount(1, $structure_difference);
        $this->assertSame(reset($structure_difference), $dir);

        $this->assertSame($CFG->tempdir.'/remove_dir_test', $dir);
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_writable($dir));

        $file1 = $dir.'/test.txt';
        $file2 = $dir.'/test.2.txt';
        $this->assertNotEmpty(file_put_contents($file1, 'Hello I am test.txt'));
        $this->assertNotEmpty(file_put_contents($file2, 'Hello I am test.2.txt'));

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        $this->assertTrue(remove_dir($dir));
        $this->assertFileNotExists($dir);
        $this->assertFalse(is_dir($dir));
        $this->assertFileNotExists($file1);
        $this->assertFileNotExists($file2);

        $structure_after = glob($pattern);
        // Check that the structure before matches exactly the structure after.
        $this->assertSame($structure_before, $structure_after);
    }

    /**
     * Test remove_dir with a cache directory that contains files.
     */
    public function test_remove_dir_with_cache_dir() {
        global $CFG;

        $pattern = $CFG->cachedir.'/*';
        $structure_before = glob($pattern);

        $dir = make_cache_directory('remove_dir_test');

        $structure_during = glob($pattern);
        $this->assertNotSame($structure_before, $structure_during);
        $structure_difference = array_diff($structure_during, $structure_before);
        $this->assertCount(1, $structure_difference);
        $this->assertSame(reset($structure_difference), $dir);

        $this->assertSame($CFG->cachedir.'/remove_dir_test', $dir);
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_writable($dir));

        $file1 = $dir.'/test.txt';
        $file2 = $dir.'/test.2.txt';
        $this->assertNotEmpty(file_put_contents($file1, 'Hello I am test.txt'));
        $this->assertNotEmpty(file_put_contents($file2, 'Hello I am test.2.txt'));

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        $this->assertTrue(remove_dir($dir));
        $this->assertFileNotExists($dir);
        $this->assertFalse(is_dir($dir));
        $this->assertFileNotExists($file1);
        $this->assertFileNotExists($file2);

        $structure_after = glob($pattern);
        // Check that the structure before matches exactly the structure after.
        $this->assertSame($structure_before, $structure_after);
    }

    /**
     * Test remove_dir with a temp directory containing files and directories.
     */
    public function test_remove_dir_with_deep_structure_temp_dir() {
        global $CFG;

        $pattern = $CFG->tempdir.'/*';
        $structure_before = glob($pattern);

        $dir1 = make_temp_directory('remove_dir_test');

        $structure_during = glob($pattern);
        $this->assertNotSame($structure_before, $structure_during);
        $structure_difference = array_diff($structure_during, $structure_before);
        $this->assertCount(1, $structure_difference);
        $this->assertSame(reset($structure_difference), $dir1);

        $this->assertSame($CFG->tempdir.'/remove_dir_test', $dir1);
        $this->assertTrue(is_dir($dir1));
        $this->assertTrue(is_writable($dir1));

        $dir2 = $dir1.'/test';
        $this->assertSame($dir2, make_writable_directory($dir2));

        $file1 = $dir1.'/test.txt';
        $file2 = $dir2.'/test.2.txt';
        $this->assertNotEmpty(file_put_contents($file1, 'Hello I am test.txt'));
        $this->assertNotEmpty(file_put_contents($file2, 'Hello I am test.2.txt'));

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        $this->assertTrue(remove_dir($dir1));
        $this->assertFileNotExists($dir1);
        $this->assertFileNotExists($dir2);
        $this->assertFalse(is_dir($dir1));
        $this->assertFalse(is_dir($dir2));
        $this->assertFileNotExists($file1);
        $this->assertFileNotExists($file2);

        $structure_after = glob($pattern);
        // Check that the structure before matches exactly the structure after.
        $this->assertSame($structure_before, $structure_after);
    }

    /**
     * Test remove_dir with a cache directory containing files and directories.
     */
    public function test_remove_dir_with_deep_structure_cache_dir() {
        global $CFG;

        $pattern = $CFG->cachedir.'/*';
        $structure_before = glob($pattern);

        $dir1 = make_cache_directory('remove_dir_test');

        $structure_during = glob($pattern);
        $this->assertNotSame($structure_before, $structure_during);
        $structure_difference = array_diff($structure_during, $structure_before);
        $this->assertCount(1, $structure_difference);
        $this->assertSame(reset($structure_difference), $dir1);

        $this->assertSame($CFG->cachedir.'/remove_dir_test', $dir1);
        $this->assertTrue(is_dir($dir1));
        $this->assertTrue(is_writable($dir1));

        $dir2 = $dir1.'/test';
        $this->assertSame($dir2, make_writable_directory($dir2));

        $file1 = $dir1.'/test.txt';
        $file2 = $dir2.'/test.2.txt';
        $this->assertNotEmpty(file_put_contents($file1, 'Hello I am test.txt'));
        $this->assertNotEmpty(file_put_contents($file2, 'Hello I am test.2.txt'));

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        $this->assertTrue(remove_dir($dir1));
        $this->assertFileNotExists($dir1);
        $this->assertFileNotExists($dir2);
        $this->assertFalse(is_dir($dir1));
        $this->assertFalse(is_dir($dir2));
        $this->assertFileNotExists($file1);
        $this->assertFileNotExists($file2);

        $structure_after = glob($pattern);
        // Check that the structure before matches exactly the structure after.
        $this->assertSame($structure_before, $structure_after);
    }

    /**
     * Test remove_dir with a temp directory containing files and directories with contentonly set to true.
     */
    public function test_remove_dir_with_content_only() {
        global $CFG;

        $dir1 = make_temp_directory('remove_dir_test');

        $this->assertSame($CFG->tempdir.'/remove_dir_test', $dir1);
        $this->assertTrue(is_dir($dir1));
        $this->assertTrue(is_writable($dir1));

        $dir2 = $dir1.'/test';
        $this->assertSame($dir2, make_writable_directory($dir2));

        $file1 = $dir1.'/test.txt';
        $file2 = $dir2.'/test.2.txt';
        $this->assertNotEmpty(file_put_contents($file1, 'Hello I am test.txt'));
        $this->assertNotEmpty(file_put_contents($file2, 'Hello I am test.2.txt'));

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        $this->assertTrue(remove_dir($dir1, true));
        $this->assertFileExists($dir1);
        $this->assertFileNotExists($dir2);
        $this->assertTrue(is_dir($dir1));
        $this->assertFalse(is_dir($dir2));
        $this->assertFileNotExists($file1);
        $this->assertFileNotExists($file2);
    }

    /**
     * Test calling remove_dir with an invalid directory.
     */
    public function test_remove_dir_with_an_invalid_directory() {
        global $CFG;
        $this->assertFileNotExists($CFG->tempdir.'/invalid_dir_test');
        $this->assertTrue(remove_dir($CFG->tempdir.'/invalid_dir_test'));
        $this->assertTrue(remove_dir($CFG->tempdir.'/invalid_dir_test', true));
    }

    public function test_user_not_fully_set_up_basic() {
        global $DB, $USER;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->setUser(null);
        $this->assertFalse(user_not_fully_set_up($user));
        $this->assertFalse(user_not_fully_set_up($user, false));
        $user->firstname = '';
        $this->assertTrue(user_not_fully_set_up($user));
        $this->assertTrue(user_not_fully_set_up($user, false));
        $user->firstname = ' ';
        $this->assertFalse(user_not_fully_set_up($user));
        $user->lastname = '';
        $this->assertTrue(user_not_fully_set_up($user));
        $user->lastname = ' ';
        $this->assertFalse(user_not_fully_set_up($user));
        $user->email = '';
        $this->assertTrue(user_not_fully_set_up($user));
        $user = $DB->get_record('user', ['id' => $user->id]);
        $this->setUser($user);
        $this->assertFalse(user_not_fully_set_up($user));
        $this->assertFalse(user_not_fully_set_up($user, false));
        $user->firstname = '';
        $this->assertTrue(user_not_fully_set_up($user));
        $this->assertTrue(user_not_fully_set_up($user, false));
        $user->firstname = ' ';
        $this->assertFalse(user_not_fully_set_up($user));
        $user->lastname = '';
        $this->assertTrue(user_not_fully_set_up($user));
        $user->lastname = ' ';
        $this->assertFalse(user_not_fully_set_up($user));
        $user->email = '';
        $this->assertTrue(user_not_fully_set_up($user));

        $this->setUser(null);
        $this->assertFalse(user_not_fully_set_up($USER));

        $guest = guest_user();
        $guest->firstname = '';
        $guest->lastname = '';
        $guest->email = '';
        $this->assertFalse(user_not_fully_set_up($guest));
        $this->setUser($guest);
        $this->assertFalse(user_not_fully_set_up($guest));

        $admin = get_admin();
        $this->setUser($user);
        $this->assertFalse(user_not_fully_set_up($admin));
        $this->assertFalse(user_not_fully_set_up($admin, false));
        $admin->firstname = '';
        $this->assertTrue(user_not_fully_set_up($admin));
        $this->assertTrue(user_not_fully_set_up($admin, false));
        $admin->firstname = ' ';
        $this->assertFalse(user_not_fully_set_up($admin));
        $admin->lastname = '';
        $this->assertTrue(user_not_fully_set_up($admin));
        $admin->lastname = ' ';
        $this->assertFalse(user_not_fully_set_up($admin));
        $admin->email = '';
        $this->assertTrue(user_not_fully_set_up($admin));
        $admin = $DB->get_record('user', ['id' => $admin->id]);
        $this->setUser($admin);
        $this->assertFalse(user_not_fully_set_up($admin));
        $admin->firstname = '';
        $this->assertTrue(user_not_fully_set_up($admin));
        $admin->firstname = ' ';
        $this->assertFalse(user_not_fully_set_up($admin));
        $admin->lastname = '';
        $this->assertTrue(user_not_fully_set_up($admin));
        $admin->lastname = ' ';
        $this->assertFalse(user_not_fully_set_up($admin));
        $admin->email = '';
        $this->assertTrue(user_not_fully_set_up($admin));
    }

    public function test_user_not_fully_set_up_custom_fields() {
        global $CFG, $DB, $USER;

        // Resolve dependencies for the test
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $this->resetAfterTest();

        // Add a required, visible, unlocked custom field.
        $DB->insert_record('user_info_field', ['shortname' => 'house', 'name' => 'House', 'required' => 1,
            'visible' => 1, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text']);

        $this->setUser(null);
        $this->assertFalse(user_not_fully_set_up($USER));
        $this->assertFalse(user_not_fully_set_up($USER, false));

        $guest = guest_user();
        $this->assertFalse(user_not_fully_set_up($guest));
        $this->assertFalse(user_not_fully_set_up($guest, false));
        $this->setUser($guest);
        $this->assertFalse(user_not_fully_set_up($guest));
        $this->assertFalse(user_not_fully_set_up($guest, false));

        $admin = get_admin();
        $this->assertFalse(user_not_fully_set_up($admin));
        $this->assertFalse(user_not_fully_set_up($admin, false));
        $this->setUser($admin);
        $this->assertFalse(user_not_fully_set_up($admin));
        $this->assertFalse(user_not_fully_set_up($admin, false));

        $wsuser = $this->getDataGenerator()->create_user(['auth' => 'webservice']);
        $this->setGuestUser();
        $this->assertFalse(user_not_fully_set_up($wsuser));
        $this->assertFalse(user_not_fully_set_up($wsuser, false));
        $this->setUser($wsuser);
        $this->assertFalse(user_not_fully_set_up($wsuser));
        $this->assertFalse(user_not_fully_set_up($wsuser, false));

        $user = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $this->setGuestUser();
        $this->assertTrue(user_not_fully_set_up($user));
        $this->assertFalse(user_not_fully_set_up($user, false));
        $this->setUser($user);
        $this->assertTrue(user_not_fully_set_up($user));
        $this->assertFalse(user_not_fully_set_up($user, false));

        profile_save_data((object)['id' => $user->id, 'profile_field_house' => 'Gray']);

        $this->setGuestUser();
        $this->assertFalse(user_not_fully_set_up($user));
        $this->assertFalse(user_not_fully_set_up($user, false));
        $this->setUser($user);
        $this->assertObjectNotHasAttribute('fullysetupaccount', $USER);
        $this->assertFalse(user_not_fully_set_up($user));
        $this->assertSame(1, $USER->fullysetupaccount);
        unset($USER->fullysetupaccount);
        $this->assertFalse(user_not_fully_set_up($user, false));
        $this->assertObjectNotHasAttribute('fullysetupaccount', $USER);

        $secadmin = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        set_config('siteadmins', $CFG->siteadmins . ',' . $secadmin->id);
        $this->setGuestUser();
        $this->assertTrue(user_not_fully_set_up($secadmin));
        $this->assertFalse(user_not_fully_set_up($secadmin, false));
        $this->setUser($secadmin);
        $this->assertTrue(user_not_fully_set_up($secadmin));
        $this->assertFalse(user_not_fully_set_up($secadmin, false));
        $this->assertObjectNotHasAttribute('fullysetupaccount', $USER);
    }
}
