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
 * @author Murali Nair <murali.nair@totaralms.com>
 * @package totara
 * @subpackage feedback360
 */
global $CFG;
require_once($CFG->dirroot.'/totara/feedback360/tests/feedback360_testcase.php');


/**
 * Unit test to check if review emails sent out by the feedback360 module have
 * the correct footer text about changing email preferences.
 *
 * Refer to TL-5226
 */
class feedback360_email_preference_test extends feedback360_testcase {
    /**
     * Intercept emails and stores them locally for later verification.
     */
    private $emailsink = NULL;


    /**
     * String in email text that indicates that changing email preferences text
     * is present. Although the actual email message is internationalized, this
     * part of the email text does not change since it deals with the fixed part
     * of the URL to change the email preferences.
     */
    private $fingerprint = '/message/edit.php';


    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp() {
        parent::setUp();

        $this->preventResetByRollback();
        $this->resetAfterTest();

        $this->emailsink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());
    }


    /**
     * PhpUnit fixture method that runs after the test method executes.
     */
    protected function tearDown() {
        $this->emailsink->close();
        $this->emailsink = null;
        $this->fingerprint = null;
        parent::tearDown();
    }


    /**
     * Convenience function to create users in the repository.
     *
     * @param emailformat either FORMAT_MOODLE or FORMAT_HTML to indicate target
     *        user's email format preference.
     * @param noofusers no of users to create.
     *
     * @return the list of created users.
     */
    private function create_users( $emailformat, $noofusers ) {
        $record = array('mailformat' => $emailformat);

        $users = array();
        for ($i = 0; $i < $noofusers; $i++) {
            $users[] = $this->getDataGenerator()->create_user($record);
        }

        return $users;
    }


    /**
     * Convenience function to create an instance of a Feedback 360 feedback and
     * assigned it for a generated user.
     *
     * @return the assignment.
     */
    private function create_feedback_assignment() {
        global $DB;

        $users = $this->create_users(FORMAT_MOODLE, 1);
        list($feedback, $na) = $this->prepare_feedback_with_users($users);

        $feedback->activate();
        $assignment = $DB->get_record(
            'feedback360_user_assignment',
            array(
                'feedback360id' => $feedback->id,
                'userid' => current($users)->id
            )
        );

        return $assignment;
    }


    /**
     * Returns the email in the sink.
     *
     * @return a list of email bodies.
     */
    private function get_emails() {
        $emails = array();
        foreach ($this->emailsink->get_messages() as $email) {
           $emails[] = trim($email->body);
        }

        return $emails;
    }


    /**
     * Validates the emails sent to external users have no preference changing
     * URL.
     */
    public function test_external_user_email() {
        $assignment = $this->create_feedback_assignment();
        $reviewers = array(
            'nobody@erehwon.gro',
            'someone@erehwon.gro'
        );

        feedback360_responder::update_external_assignments(
            $reviewers, array(), $assignment->id, time()
        );

        $emails = $this->get_emails();
        $this->assertCount(
            count($reviewers), $emails, 'Wrong no of review emails sent out.'
        );

        foreach ($emails as $email) {
            $this->assertFalse(
                strpos($email, $this->fingerprint, 0),
                'External user email has mail preferences text.'
            );
        }
    }


    /**
     * Validates emails sent to system users have preference changing text.
     *
     * @param mailformat either FORMAT_MOODLE (plain) or FORMAT_HTML.
     */
    private function validate_system_user_email( $mailformat ) {
        $reviewers = array();
        foreach ($this->create_users($mailformat, 5) as $user) {
            $reviewers[] = $user->id;
        }

        $assignment = $this->create_feedback_assignment();
        feedback360_responder::update_system_assignments(
            $reviewers, array(), $assignment->id, time()
        );

        $emails = $this->get_emails();
        $this->assertCount(
            count($reviewers), $emails, 'Wrong no of review emails sent out.'
        );

        foreach ($emails as $email) {
            $typeof = $mailformat === FORMAT_MOODLE ? "plain" : "HTML";
            $this->assertTrue(
                strpos($email, $this->fingerprint, 0) >= 0,
                "System user's $typeof email has NO mail preferences text."
            );
        }
    }


    /**
     * Tests that HTML emails sent to system users contain email preferences
     * changing text.
     */
    public function test_system_user_email_html() {
        $this->validate_system_user_email(FORMAT_HTML);
    }


    /**
     * Tests that plain emails sent to system users contain email preferences
     * changing text.
     */
    public function test_system_user_email_plain() {
        $this->validate_system_user_email(FORMAT_PLAIN);
    }
}