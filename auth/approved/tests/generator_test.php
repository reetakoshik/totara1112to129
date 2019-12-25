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

class auth_approved_generator_testcase extends advanced_testcase {

    public function test_username_required() {
        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            // 'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'pending',
        ];
        $this->expectException('coding_exception', "Field 'username' must be provided when creating a request.");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_password_required() {
        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'pending',
        ];
        $this->expectException('coding_exception', "Field 'password' must be provided when creating a request.");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_email_required() {
        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'pending',
        ];
        $this->expectException('coding_exception', "Field 'email' must be provided when creating a request.");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_firstname_required() {
        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'surname' => 'User',
            'status' => 'pending',
        ];
        $this->expectException('coding_exception', "Field 'first name' must be provided when creating a request.");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_surname_required() {
        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'status' => 'pending',
        ];
        $this->expectException('coding_exception', "Field 'surname' must be provided when creating a request.");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_status_required() {
        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
        ];
        $this->expectException('coding_exception', "Field 'status' must be provided when creating a request.");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_create_signup_required_fields_pending() {
        global $DB;

        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'pending',
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertSame('Wellington', $request->city);
        $this->assertSame('NZ', $request->country);
        $this->assertEquals(\auth_approved\request::STATUS_PENDING, $request->status);
        $this->assertTrue(password_verify($data['password'], $request->password));

        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test2';
        $data->firstname = 'Test';
        $data->lastname = 'User';
        $data->password = 'test';
        $data->email = 'test_2@example.com';
        // These next properties are set internally by the generator if not provided.
        $data->city = 'Wellington';
        $data->country = 'NZ';
        $data->lang = 'en';
        $realrequestid = \auth_approved\request::add_request($data);
        $realrequest = $DB->get_record('auth_approved_request', ['id' => $realrequestid], '*', MUST_EXIST);

        $this->assertSame($realrequest->firstname, $request->firstname);
        $this->assertSame($realrequest->lastname, $request->lastname);
        $this->assertSame($realrequest->lastnamephonetic, $request->lastnamephonetic);
        $this->assertSame($realrequest->firstnamephonetic, $request->firstnamephonetic);
        $this->assertSame($realrequest->middlename, $request->middlename);
        $this->assertSame($realrequest->alternatename, $request->alternatename);
        $this->assertSame($realrequest->city, $request->city);
        $this->assertSame($realrequest->country, $request->country);
        $this->assertSame($realrequest->lang, $request->lang);
        $this->assertSame($realrequest->confirmed, $request->confirmed);
        $this->assertSame($realrequest->positionid, $request->positionid);
        $this->assertSame($realrequest->positionfreetext, $request->positionfreetext);
        $this->assertSame($realrequest->organisationid, $request->organisationid);
        $this->assertSame($realrequest->organisationfreetext, $request->organisationfreetext);
        $this->assertSame($realrequest->managerjaid, $request->managerjaid);
        $this->assertSame($realrequest->managerfreetext, $request->managerfreetext);
        $this->assertSame($realrequest->profilefields, $request->profilefields);
        $this->assertSame($realrequest->userid, $request->userid);
    }

    public function test_create_signup_required_fields_approved() {
        global $DB;

        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Approved request.
        $data = [
            'username' => 'test3',
            'password' => 'test3',
            'email' => 'test_3@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertEquals(\auth_approved\request::STATUS_APPROVED, $request->status);
        $this->assertTrue(password_verify($data['password'], $request->password));

        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test4';
        $data->firstname = 'Test';
        $data->lastname = 'User';
        $data->password = 'test';
        $data->email = 'test_4@example.com';
        // These next properties are set internally by the generator if not provided.
        $data->city = 'Wellington';
        $data->country = 'NZ';
        $data->lang = 'en';
        $realrequestid = \auth_approved\request::add_request($data);
        \auth_approved\request::approve_request($realrequestid, 'Custom approval message', false);
        $realrequest = $DB->get_record('auth_approved_request', ['id' => $realrequestid], '*', MUST_EXIST);

        $this->assertSame($realrequest->firstname, $request->firstname);
        $this->assertSame($realrequest->lastname, $request->lastname);
        $this->assertSame($realrequest->lastnamephonetic, $request->lastnamephonetic);
        $this->assertSame($realrequest->firstnamephonetic, $request->firstnamephonetic);
        $this->assertSame($realrequest->middlename, $request->middlename);
        $this->assertSame($realrequest->alternatename, $request->alternatename);
        $this->assertSame($realrequest->city, $request->city);
        $this->assertSame($realrequest->country, $request->country);
        $this->assertSame($realrequest->lang, $request->lang);
        $this->assertSame($realrequest->confirmed, $request->confirmed);
        $this->assertSame($realrequest->positionid, $request->positionid);
        $this->assertSame($realrequest->positionfreetext, $request->positionfreetext);
        $this->assertSame($realrequest->organisationid, $request->organisationid);
        $this->assertSame($realrequest->organisationfreetext, $request->organisationfreetext);
        $this->assertSame($realrequest->managerjaid, $request->managerjaid);
        $this->assertSame($realrequest->managerfreetext, $request->managerfreetext);
        $this->assertSame($realrequest->profilefields, $request->profilefields);
        $this->assertNotEmpty($request->userid);
    }

    public function test_create_signup_required_fields_rejected() {
        global $DB;

        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Rejected request.
        $data = [
            'username' => 'test5',
            'password' => 'test5',
            'email' => 'test_5@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'rejected',
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertEquals(\auth_approved\request::STATUS_REJECTED, $request->status);
        $this->assertEquals(0, $request->confirmed);
        $this->assertTrue(password_verify($data['password'], $request->password));

        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test6';
        $data->firstname = 'Test';
        $data->lastname = 'User';
        $data->password = 'test';
        $data->email = 'test_6@example.com';
        // These next properties are set internally by the generator if not provided.
        $data->city = 'Wellington';
        $data->country = 'NZ';
        $data->lang = 'en';
        $realrequestid = \auth_approved\request::add_request($data);
        \auth_approved\request::reject_request($realrequestid, 'Custom approval message');
        $realrequest = $DB->get_record('auth_approved_request', ['id' => $realrequestid], '*', MUST_EXIST);

        $this->assertSame($realrequest->firstname, $request->firstname);
        $this->assertSame($realrequest->lastname, $request->lastname);
        $this->assertSame($realrequest->lastnamephonetic, $request->lastnamephonetic);
        $this->assertSame($realrequest->firstnamephonetic, $request->firstnamephonetic);
        $this->assertSame($realrequest->middlename, $request->middlename);
        $this->assertSame($realrequest->alternatename, $request->alternatename);
        $this->assertSame($realrequest->city, $request->city);
        $this->assertSame($realrequest->country, $request->country);
        $this->assertSame($realrequest->lang, $request->lang);
        $this->assertSame($realrequest->confirmed, $request->confirmed);
        $this->assertSame($realrequest->positionid, $request->positionid);
        $this->assertSame($realrequest->positionfreetext, $request->positionfreetext);
        $this->assertSame($realrequest->organisationid, $request->organisationid);
        $this->assertSame($realrequest->organisationfreetext, $request->organisationfreetext);
        $this->assertSame($realrequest->managerjaid, $request->managerjaid);
        $this->assertSame($realrequest->managerfreetext, $request->managerfreetext);
        $this->assertSame($realrequest->profilefields, $request->profilefields);
        $this->assertSame($realrequest->userid, $request->userid);
    }

    public function test_create_signup_all_fields_pending() {
        global $DB;

        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $hierarchies */
        $hierarchies = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchies->create_org_frame([]);
        $org = $hierarchies->create_org(['frameworkid' => $orgframework->id, 'idnumber' => '25']);
        $posframework = $hierarchies->create_pos_frame([]);
        $pos = $hierarchies->create_pos(['frameworkid' => $posframework->id, 'idnumber' => '26']);

        $manager = $this->getDataGenerator()->create_user(['username' => 'manager']);
        $job = \totara_job\job_assignment::create_default($manager->id, ['idnumber' => '27']);

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'pending',
            'signup time' => 1498514074,
            'token' => str_repeat('a', 32),
            'confirmed' => 1,
            'city' => 'Brighton',
            'country' => 'GB',
            'lang' => 'en',
            'mgr text' => 'Fred',
            'manager jaidnum' => $job->idnumber,
            'pos text' => 'Developer',
            'pos idnum' => $pos->idnumber,
            'org text' => 'Totara Learning',
            'org idnum' => $org->idnumber
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertSame($data['city'], $request->city);
        $this->assertSame($data['country'], $request->country);
        $this->assertSame($data['lang'], $request->lang);
        $this->assertEquals($data['signup time'], $request->timecreated);
        $this->assertEquals($data['signup time'], $request->timemodified);
        $this->assertSame($data['token'], $request->confirmtoken);
        $this->assertSame($data['mgr text'], $request->managerfreetext);
        $this->assertEquals($job->id, $request->managerjaid);
        $this->assertSame($data['pos text'], $request->positionfreetext);
        $this->assertEquals($pos->id, $request->positionid);
        $this->assertSame($data['org text'], $request->organisationfreetext);
        $this->assertEquals($org->id, $request->organisationid);
        $this->assertEquals(\auth_approved\request::STATUS_PENDING, $request->status);
        $this->assertTrue(password_verify($data['password'], $request->password));

        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test2';
        $data->firstname = 'Test';
        $data->lastname = 'User';
        $data->password = 'test';
        $data->email = 'test_2@example.com';
        $data->city = 'Brighton';
        $data->country = 'GB';
        $data->lang = 'en';
        $data->managerfreetext = 'Fred';
        $data->managerjaid = $job->id;
        $data->positionfreetext = 'Developer';
        $data->positionid = $pos->id;
        $data->organisationfreetext = 'Totara Learning';
        $data->organisationid = $org->id;
        $realrequestid = \auth_approved\request::add_request($data);
        $realrequest = $DB->get_record('auth_approved_request', ['id' => $realrequestid], '*', MUST_EXIST);

        $this->assertSame($realrequest->firstname, $request->firstname);
        $this->assertSame($realrequest->lastname, $request->lastname);
        $this->assertSame($realrequest->lastnamephonetic, $request->lastnamephonetic);
        $this->assertSame($realrequest->firstnamephonetic, $request->firstnamephonetic);
        $this->assertSame($realrequest->middlename, $request->middlename);
        $this->assertSame($realrequest->alternatename, $request->alternatename);
        $this->assertSame($realrequest->city, $request->city);
        $this->assertSame($realrequest->country, $request->country);
        $this->assertSame($realrequest->lang, $request->lang);
        $this->assertSame($realrequest->positionid, $request->positionid);
        $this->assertSame($realrequest->positionfreetext, $request->positionfreetext);
        $this->assertSame($realrequest->organisationid, $request->organisationid);
        $this->assertSame($realrequest->organisationfreetext, $request->organisationfreetext);
        $this->assertSame($realrequest->managerjaid, $request->managerjaid);
        $this->assertSame($realrequest->managerfreetext, $request->managerfreetext);
        $this->assertSame($realrequest->profilefields, $request->profilefields);
        $this->assertSame($realrequest->userid, $request->userid);
    }

    public function test_create_signup_all_fields_approved() {
        global $DB;

        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $hierarchies */
        $hierarchies = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchies->create_org_frame([]);
        $org = $hierarchies->create_org(['frameworkid' => $orgframework->id, 'idnumber' => '25']);
        $posframework = $hierarchies->create_pos_frame([]);
        $pos = $hierarchies->create_pos(['frameworkid' => $posframework->id, 'idnumber' => '26']);

        $manager = $this->getDataGenerator()->create_user(['username' => 'manager']);
        $job = \totara_job\job_assignment::create_default($manager->id, ['idnumber' => '27']);

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'signup time' => 1498514074,
            'token' => str_repeat('a', 32),
            'confirmed' => 1,
            'city' => 'Brighton',
            'country' => 'GB',
            'lang' => 'en',
            'mgr text' => 'Fred',
            'manager jaidnum' => $job->idnumber,
            'pos text' => 'Developer',
            'pos idnum' => $pos->idnumber,
            'org text' => 'Totara Learning',
            'org idnum' => $org->idnumber
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertSame($data['city'], $request->city);
        $this->assertSame($data['country'], $request->country);
        $this->assertSame($data['lang'], $request->lang);
        $this->assertEquals($data['signup time'], $request->timecreated);
        $this->assertEquals($data['signup time'], $request->timemodified);
        $this->assertSame($data['token'], $request->confirmtoken);
        $this->assertSame($data['mgr text'], $request->managerfreetext);
        $this->assertEquals($job->id, $request->managerjaid);
        $this->assertSame($data['pos text'], $request->positionfreetext);
        $this->assertEquals($pos->id, $request->positionid);
        $this->assertSame($data['org text'], $request->organisationfreetext);
        $this->assertEquals($org->id, $request->organisationid);
        $this->assertEquals(\auth_approved\request::STATUS_APPROVED, $request->status);
        $this->assertTrue(password_verify($data['password'], $request->password));

        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test2';
        $data->firstname = 'Test';
        $data->lastname = 'User';
        $data->password = 'test';
        $data->email = 'test_2@example.com';
        $data->city = 'Brighton';
        $data->country = 'GB';
        $data->lang = 'en';
        $data->managerfreetext = 'Fred';
        $data->managerjaid = $job->id;
        $data->positionfreetext = 'Developer';
        $data->positionid = $pos->id;
        $data->organisationfreetext = 'Totara Learning';
        $data->organisationid = $org->id;
        $realrequestid = \auth_approved\request::add_request($data);
        $realrequest = $DB->get_record('auth_approved_request', ['id' => $realrequestid], '*', MUST_EXIST);
        \auth_approved\request::approve_request($realrequestid, 'Custom message', false);

        $this->assertSame($realrequest->firstname, $request->firstname);
        $this->assertSame($realrequest->lastname, $request->lastname);
        $this->assertSame($realrequest->lastnamephonetic, $request->lastnamephonetic);
        $this->assertSame($realrequest->firstnamephonetic, $request->firstnamephonetic);
        $this->assertSame($realrequest->middlename, $request->middlename);
        $this->assertSame($realrequest->alternatename, $request->alternatename);
        $this->assertSame($realrequest->city, $request->city);
        $this->assertSame($realrequest->country, $request->country);
        $this->assertSame($realrequest->lang, $request->lang);
        $this->assertSame($realrequest->positionid, $request->positionid);
        $this->assertSame($realrequest->positionfreetext, $request->positionfreetext);
        $this->assertSame($realrequest->organisationid, $request->organisationid);
        $this->assertSame($realrequest->organisationfreetext, $request->organisationfreetext);
        $this->assertSame($realrequest->managerjaid, $request->managerjaid);
        $this->assertSame($realrequest->managerfreetext, $request->managerfreetext);
        $this->assertSame($realrequest->profilefields, $request->profilefields);
        $this->assertNotEmpty($request->userid);
    }

    public function test_create_signup_all_fields_rejected() {
        global $DB;

        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $hierarchies */
        $hierarchies = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchies->create_org_frame([]);
        $org = $hierarchies->create_org(['frameworkid' => $orgframework->id, 'idnumber' => '25']);
        $posframework = $hierarchies->create_pos_frame([]);
        $pos = $hierarchies->create_pos(['frameworkid' => $posframework->id, 'idnumber' => '26']);

        $manager = $this->getDataGenerator()->create_user(['username' => 'manager']);
        $job = \totara_job\job_assignment::create_default($manager->id, ['idnumber' => '27']);

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Pending request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'rejected',
            'signup time' => 1498514074,
            'token' => str_repeat('a', 32),
            'confirmed' => 1,
            'city' => 'Brighton',
            'country' => 'GB',
            'lang' => 'en',
            'mgr text' => 'Fred',
            'manager jaidnum' => $job->idnumber,
            'pos text' => 'Developer',
            'pos idnum' => $pos->idnumber,
            'org text' => 'Totara Learning',
            'org idnum' => $org->idnumber
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertSame($data['city'], $request->city);
        $this->assertSame($data['country'], $request->country);
        $this->assertSame($data['lang'], $request->lang);
        $this->assertEquals($data['signup time'], $request->timecreated);
        $this->assertEquals($data['signup time'], $request->timemodified);
        $this->assertSame($data['token'], $request->confirmtoken);
        $this->assertSame($data['mgr text'], $request->managerfreetext);
        $this->assertEquals($job->id, $request->managerjaid);
        $this->assertSame($data['pos text'], $request->positionfreetext);
        $this->assertEquals($pos->id, $request->positionid);
        $this->assertSame($data['org text'], $request->organisationfreetext);
        $this->assertEquals($org->id, $request->organisationid);
        $this->assertEquals(\auth_approved\request::STATUS_REJECTED, $request->status);
        $this->assertTrue(password_verify($data['password'], $request->password));

        $data = new \stdClass;
        $data->requestid = 0;
        $data->username = 'test2';
        $data->firstname = 'Test';
        $data->lastname = 'User';
        $data->password = 'test';
        $data->email = 'test_2@example.com';
        $data->city = 'Brighton';
        $data->country = 'GB';
        $data->lang = 'en';
        $data->managerfreetext = 'Fred';
        $data->managerjaid = $job->id;
        $data->positionfreetext = 'Developer';
        $data->positionid = $pos->id;
        $data->organisationfreetext = 'Totara Learning';
        $data->organisationid = $org->id;
        $realrequestid = \auth_approved\request::add_request($data);
        $realrequest = $DB->get_record('auth_approved_request', ['id' => $realrequestid], '*', MUST_EXIST);
        \auth_approved\request::reject_request($realrequestid, 'Custom message');

        $this->assertSame($realrequest->firstname, $request->firstname);
        $this->assertSame($realrequest->lastname, $request->lastname);
        $this->assertSame($realrequest->lastnamephonetic, $request->lastnamephonetic);
        $this->assertSame($realrequest->firstnamephonetic, $request->firstnamephonetic);
        $this->assertSame($realrequest->middlename, $request->middlename);
        $this->assertSame($realrequest->alternatename, $request->alternatename);
        $this->assertSame($realrequest->city, $request->city);
        $this->assertSame($realrequest->country, $request->country);
        $this->assertSame($realrequest->lang, $request->lang);
        $this->assertSame($realrequest->positionid, $request->positionid);
        $this->assertSame($realrequest->positionfreetext, $request->positionfreetext);
        $this->assertSame($realrequest->organisationid, $request->organisationid);
        $this->assertSame($realrequest->organisationfreetext, $request->organisationfreetext);
        $this->assertSame($realrequest->managerjaid, $request->managerjaid);
        $this->assertSame($realrequest->managerfreetext, $request->managerfreetext);
        $this->assertSame($realrequest->profilefields, $request->profilefields);
        $this->assertSame($realrequest->userid, $request->userid);
    }

    public function test_create_signup_with_approver() {
        global $DB;

        $this->resetAfterTest();

        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);
        $approver = $this->getDataGenerator()->create_user();

        // Approved request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'approveduserorid' => $approver
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertEquals(\auth_approved\request::STATUS_APPROVED, $request->status);
        $this->assertEquals($data['approveduserorid']->id, $request->userid);
        $this->assertTrue(password_verify($data['password'], $request->password));

        // Approved request.
        $data = [
            'username' => 'test2',
            'password' => 'test2',
            'email' => 'test_2@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'approveduserorid' => $approver->id
        ];
        $requestid = $generator->create_signup($data);
        $request = $DB->get_record('auth_approved_request', ['id' => $requestid], '*', MUST_EXIST);
        $this->assertSame($data['username'], $request->username);
        $this->assertSame($data['email'], $request->email);
        $this->assertSame($data['first name'], $request->firstname);
        $this->assertSame($data['surname'], $request->lastname);
        $this->assertEquals(\auth_approved\request::STATUS_APPROVED, $request->status);
        $this->assertEquals($data['approveduserorid'], $request->userid);
        $this->assertTrue(password_verify($data['password'], $request->password));
    }

    public function test_create_signup_invalid_country() {
        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Approved request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'country' => 'New Zealand'
        ];

        $this->expectException('coding_exception', "The given country ISO code is not valid: 'New Zealand'");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_create_signup_invalid_language() {
        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Approved request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'lang' => 'English'
        ];

        $this->expectException('coding_exception', "The given language is no a valid language: 'English'");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_create_signup_invalid_status() {
        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Approved request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'random',
        ];

        $this->expectException('coding_exception', "unknown status: 'random'");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_create_signup_invalid_organisationid() {
        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Approved request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'org idnum' => -10
        ];

        $this->expectException('coding_exception', "table 'org' does not have idnumber '-10'");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_create_signup_invalid_positionid() {
        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Approved request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'pos idnum' => -10
        ];

        $this->expectException('coding_exception', "table 'pos' does not have idnumber '-10'");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

    public function test_create_signup_invalid_managerid() {
        /** @var auth_approved_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_approved');
        $this->assertInstanceOf('auth_approved_generator', $generator);

        // Approved request.
        $data = [
            'username' => 'test1',
            'password' => 'test1',
            'email' => 'test_1@example.com',
            'first name' => 'Test',
            'surname' => 'User',
            'status' => 'approved',
            'manager jaidnum' => -10
        ];

        $this->expectException('coding_exception', "table 'job_assignment' does not have idnumber '-10'");
        $generator->create_signup($data);
        $this->assertEmpty($this->getExpectedException());
    }

}