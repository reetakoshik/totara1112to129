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
 * @author Ryan Adams <ryana@learningpool.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

use \auth_approved\util;

class auth_approved_util_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_get_report_url() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        $this->resetAfterTest();

        $this->setAdminUser();

        // Default should go to the pending requests.
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create_embedded('auth_approved_pending_requests', $config);
        $this->assertEquals($report->report_url(), util::get_report_url(0));

        // When returning back to pending requests.
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create_embedded('auth_approved_pending_requests', $config);
        $this->assertEquals($report->report_url(), util::get_report_url($report->_id));

        // Any other embedded report.
        $report = reportbuilder::create_embedded('cohort_admin');
        $this->assertEquals($report->report_url(), util::get_report_url($report->_id));

        // Custom report.
        $rid = $this->create_report('user', 'User report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->assertEquals($report->report_url(), util::get_report_url($report->_id));

        // Messed up access control.
        $report = reportbuilder::create_embedded('cohort_admin');
        $this->setUser(null);
        $this->assertEquals(util::get_report_url(0), util::get_report_url($report->_id));
    }

    public function test_normalise_domain_list() {
        $this->assertSame('example.com', util::normalise_domain_list('test@example.com'));
        $this->assertSame('example.com', util::normalise_domain_list('test@exAMPle.com'));
        $this->assertSame('example.com example.com ex.com', util::normalise_domain_list('test@exAMPle.com; test@example.com, ex.com'));
        $this->assertSame('example.com', util::normalise_domain_list(' examPLE.com;, '));
        $this->assertSame('totaralms.com totaralearning.com totara.com', util::normalise_domain_list(" TotaraLMS.com \n \t *@totaralearning.com\n @xxx@totara.com"));
    }

    public function test_email_matches_domain_list() {
        $this->assertTrue(util::email_matches_domain_list('me@totaralms.com', 'totaralms.com demo.totaralms.com .totaralearning.com'));
        $this->assertTrue(util::email_matches_domain_list('me@totaralms.COM', 'totaralms.com demo.totaralms.com .totaralearning.com'));
        $this->assertTrue(util::email_matches_domain_list('ME@lms.totaralearning.com', 'totaralms.com ,  demo.totaralms.com; .totaralearning.COM'));
        $this->assertFalse(util::email_matches_domain_list('me@prod.totaralms.com', 'totaralms.com demo.totaralms.com .totaralearning.com'));
        $this->assertFalse(util::email_matches_domain_list('they@example.com', 'totaralms.com demo.totaralms.com .totaralearning.com'));
        $this->assertFalse(util::email_matches_domain_list('notenemail', 'totaralms.com demo.totaralms.com .totaralearning.com'));
        $this->assertFalse(util::email_matches_domain_list('me@totaralms.com', '.totaralms.com'));
        $this->assertFalse(util::email_matches_domain_list('me@totaralms.com', ''));
    }

    public function test_render_request_details_view() {
        // Yip, we need the database here.
        $this->resetAfterTest();

        // Needed as we are using has_capability.
        $this->setAdminUser();

        // We don't have a real request id here, we expect an error string apparently!
        $this->assertSame(get_string('error'), \auth_approved\util::render_request_details_view(0));

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');

        // Test the report with a pending signup.
        $requestid = $generator->create_signup([
            'username' => 'test1_username',
            'password' => 'monkey',
            'email' => 'test1@example.com',
            'first name' => 'test1 firstname',
            'surname' => 'test1 surname',
            'status' => 'pending',
        ]);
        $report = \auth_approved\util::render_request_details_view($requestid);
        $this->assertContains('<dt>User First Name</dt><dd>test1 firstname</dd>', $report);
        $this->assertContains('<dt>User Last Name</dt><dd>test1 surname</dd>', $report);
        $this->assertContains('<dt>Username</dt><dd>test1_username</dd>', $report);
        $this->assertContains('<dt>User\'s Email</dt><dd>test1@example.com</dd>', $report);
        $this->assertContains('<dt>Email confirmed</dt><dd>No</dd>', $report);
        $this->assertNotContains('monkey', $report);

        // Now test with an approved signup.
        $requestid = $generator->create_signup([
            'username' => 'test2_username',
            'password' => 'monkey',
            'email' => 'test2@example.com',
            'first name' => 'test2 firstname',
            'surname' => 'test2 surname',
            'status' => 'approved',
        ]);
        $report = \auth_approved\util::render_request_details_view($requestid);
        $this->assertContains('<dt>User First Name</dt><dd>test2 firstname</dd>', $report);
        $this->assertContains('<dt>User Last Name</dt><dd>test2 surname</dd>', $report);
        $this->assertContains('<dt>Username</dt><dd>test2_username</dd>', $report);
        $this->assertContains('<dt>User\'s Email</dt><dd>test2@example.com</dd>', $report);
        $this->assertContains('<dt>Email confirmed</dt><dd>No</dd>', $report);
        $this->assertNotContains('monkey', $report);

        // Now test with a rejected signup.
        $requestid = $generator->create_signup([
            'username' => 'test2_username',
            'password' => 'monkey',
            'email' => 'test2@example.com',
            'first name' => 'test2 firstname',
            'surname' => 'test2 surname',
            'status' => 'rejected',
        ]);
        $report = \auth_approved\util::render_request_details_view($requestid);
        $this->assertContains('<dt>User First Name</dt><dd>test2 firstname</dd>', $report);
        $this->assertContains('<dt>User Last Name</dt><dd>test2 surname</dd>', $report);
        $this->assertContains('<dt>Username</dt><dd>test2_username</dd>', $report);
        $this->assertContains('<dt>User\'s Email</dt><dd>test2@example.com</dd>', $report);
        $this->assertContains('<dt>Email confirmed</dt><dd>No</dd>', $report);
        $this->assertNotContains('monkey', $report);
    }
}
