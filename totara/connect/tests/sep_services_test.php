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
 * @package totara_connect
 */

use \totara_connect\sep_services;
use \totara_connect\util;
use \totara_core\jsend;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests sep services class.
 */
class totara_connect_sep_services_testcase extends advanced_testcase {
    public function test_get_api_version() {
        $this->resetAfterTest();

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');
        $client = $generator->create_client();

        $result = sep_services::get_api_version($client, array('clienttype' => 'totaralms'));
        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['data']['minapiversion']);
        $this->assertSame(2, $result['data']['maxapiversion']);

        $result = sep_services::get_api_version($client, array('clienttype' => 'totarasocial'));
        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['data']['minapiversion']);
        $this->assertSame(2, $result['data']['maxapiversion']);

        $result = sep_services::get_api_version($client, array('clienttype' => 'abc'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);

        $result = sep_services::get_api_version($client, array());
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);
    }

    public function test_update_api_version() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');
        $client1 = $generator->create_client(array('clienttype' => ''));
        $DB->set_field('totara_connect_clients', 'timecreated', '11', array('id' => $client1->id));
        $DB->set_field('totara_connect_clients', 'timemodified', '22', array('id' => $client1->id));
        $client1 = $DB->get_record('totara_connect_clients', array('id' => $client1->id));

        $client2 = $generator->create_client(array('clienttype' => ''));

        $client3 = $generator->create_client(array('clienttype' => ''));
        $DB->set_field('totara_connect_clients', 'timecreated', '666', array('id' => $client3->id));
        $DB->set_field('totara_connect_clients', 'timemodified', '666', array('id' => $client3->id));
        $client3 = $DB->get_record('totara_connect_clients', array('id' => $client3->id));

        $this->setCurrentTimeStart();
        $result = sep_services::update_api_version($client1, array('apiversion' => '1', 'clienttype' => 'totaralms'));
        $this->assertSame('success', $result['status']);
        $c = $DB->get_record('totara_connect_clients', array('id' => $client1->id));
        $this->assertSame('1', $c->apiversion);
        $this->assertSame('totaralms', $c->clienttype);
        $this->assertTimeCurrent($c->timemodified);

        $this->setCurrentTimeStart();
        $result = sep_services::update_api_version($client1, array('apiversion' => '2', 'clienttype' => 'totaralms'));
        $this->assertSame('success', $result['status']);
        $c = $DB->get_record('totara_connect_clients', array('id' => $client1->id));
        $this->assertSame('2', $c->apiversion);
        $this->assertSame('totaralms', $c->clienttype);
        $this->assertTimeCurrent($c->timemodified);

        $this->setCurrentTimeStart();
        $result = sep_services::update_api_version($client2, array('apiversion' => '1', 'clienttype' => 'totarasocial'));
        $this->assertSame('success', $result['status']);
        $c = $DB->get_record('totara_connect_clients', array('id' => $client2->id));
        $this->assertSame('1', $c->apiversion);
        $this->assertSame('totarasocial', $c->clienttype);
        $this->assertTimeCurrent($c->timemodified);

        // Now try all possible errors.

        $result = sep_services::update_api_version($client3, array('clienttype' => 'totaralms'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing api version number', $result['data']['apiversion']);
        $this->assertSame($client3->timemodified, $DB->get_field('totara_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('totara_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('totara_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => 'a', 'clienttype' => 'totaralms'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing api version number', $result['data']['apiversion']);
        $this->assertSame($client3->timemodified, $DB->get_field('totara_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('totara_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('totara_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => '100', 'clienttype' => 'totaralms'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('unsupported api version number', $result['data']['apiversion']);
        $this->assertSame($client3->timemodified, $DB->get_field('totara_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('totara_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('totara_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => '1', 'clienttype' => 'moodle'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);
        $this->assertSame($client3->timemodified, $DB->get_field('totara_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('totara_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('totara_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => '1'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);
        $this->assertSame($client3->timemodified, $DB->get_field('totara_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('totara_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('totara_connect_clients', 'apiversion', array('id' => $client3->id)));
    }

    public function test_get_users() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        delete_user($user4);

        $cohort = $this->getDataGenerator()->create_cohort();

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');
        $client1 = $generator->create_client(array('apiversion' => 1));

        $client2 = $generator->create_client(array('cohortid' => $cohort->id, 'apiversion' => 1));
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Try client with all site users.

        $result = sep_services::get_users($client1, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(5, $result['data']['users']); // Normal 3 users + one deleted user + admin.

        $i = 0;
        foreach ($result['data']['users'] as $k => $u) {
            $this->assertSame($i++, $k);
            if ($u->id == $user4->id) {
                $this->assertSame('1', $u->deleted);
            } else {
                $this->assertSame('0', $u->deleted);
            }
            $this->assertNull($u->password);
            $this->assertObjectNotHasAttribute('secret', $u);
            $this->assertObjectNotHasAttribute('picture', $u);
            $this->assertObjectNotHasAttribute('pictures', $u);
            $this->assertObjectNotHasAttribute('jobs', $u);
            if ($u->deleted) {
                $this->assertNull($u->description);
                $this->assertNull($u->descriptionformat);
            } else {
                $this->assertObjectHasAttribute('description', $u);
                $this->assertObjectHasAttribute('descriptionformat', $u);
            }
        }

        // Try client with cohort restrictions and password sync.

        set_config('syncpasswords', '1', 'totara_connect');
        $result = sep_services::get_users($client2, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']['users']); // Only 2 cohort members.
        $i = 0;
        foreach ($result['data']['users'] as $k => $u) {
            $this->assertSame($i++, $k);
            $this->assertTrue(cohort_is_member($cohort->id, $u->id));
            $this->assertNotNull($u->password);
            $this->assertObjectNotHasAttribute('secret', $u);
            $this->assertObjectNotHasAttribute('picture', $u);
            $this->assertObjectNotHasAttribute('pictures', $u);
            $this->assertObjectNotHasAttribute('jobs', $u);
            if ($u->deleted) {
                $this->assertNull($u->description);
                $this->assertNull($u->descriptionformat);
            } else {
                $this->assertObjectHasAttribute('description', $u);
                $this->assertObjectHasAttribute('descriptionformat', $u);
            }
        }

        // Try clients with api version 2 (jobs).

        $client3 = $generator->create_client(array('apiversion' => 2));
        $client4 = $generator->create_client(array('apiversion' => 2, 'syncjobs' => 1, 'syncprofilefields' => 1));

        $result = sep_services::get_users($client3, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(5, $result['data']['users']); // Normal 3 users + one deleted user + admin.
        foreach ($result['data']['users'] as $k => $u) {
            if ($u->deleted) {
                $this->assertObjectNotHasAttribute('jobs', $u);
                $this->assertObjectNotHasAttribute('profile_fields', $u);
            } else {
                $this->assertNull($u->jobs);
                $this->assertNull($u->profile_fields);
            }
        }

        $result = sep_services::get_users($client4, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(5, $result['data']['users']); // Normal 3 users + one deleted user + admin.
        foreach ($result['data']['users'] as $k => $u) {
            if ($u->deleted) {
                $this->assertObjectNotHasAttribute('jobs', $u);
                $this->assertObjectNotHasAttribute('profile_fields', $u);
            } else {
                $this->assertIsArray($u->jobs);
                $this->assertIsArray($u->profile_fields);
            }
        }
    }

    public function test_get_users_jobs() {
        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var totara_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('totara_connect');

        $pos_framework1 = $hierarchygenerator->create_pos_frame(array());
        $pos_framework2 = $hierarchygenerator->create_pos_frame(array());
        $pos_framework3 = $hierarchygenerator->create_pos_frame(array());
        $pos1 = $hierarchygenerator->create_pos(array('frameworkid' => $pos_framework1->id));
        $pos2 = $hierarchygenerator->create_pos(array('frameworkid' => $pos_framework2->id));
        $pos3 = $hierarchygenerator->create_pos(array('frameworkid' => $pos_framework3->id));

        $org_framework1 = $hierarchygenerator->create_org_frame(array());
        $org_framework2 = $hierarchygenerator->create_org_frame(array());
        $org_framework3 = $hierarchygenerator->create_org_frame(array());
        $org1 = $hierarchygenerator->create_org(array('frameworkid' => $org_framework1->id));
        $org2 = $hierarchygenerator->create_org(array('frameworkid' => $org_framework2->id));
        $org3 = $hierarchygenerator->create_org(array('frameworkid' => $org_framework3->id));

        $client = $connectgenerator->create_client(array('apiversion' => 2, 'syncjobs' => 1));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $data1a = array(
            'userid' => $user1->id,
            'fullname' => 'full name 1',
            'shortname' => 'short 1',
            'idnumber' => 'idn1',
            'description' => 'desc 1',
            'startdate' => time() - 26*60*60*10,
            'enddate' => time() - 26*60*60*10,
            'positionid' => $pos1->id,
            'organisationid' => $org1->id,
        );
        $ja1a = \totara_job\job_assignment::create($data1a);
        $data1b = array(
            'userid' => $user1->id,
            'idnumber' => 'idn2',
        );
        $ja1b = \totara_job\job_assignment::create($data1b);
        $data2 = array(
            'userid' => $user2->id,
            'fullname' => 'full name 1',
            'shortname' => 'short 1',
            'idnumber' => 'idn1',
            'description' => 'desc 1',
            'startdate' => time() - 26*60*60*10,
            'enddate' => time() - 26*60*60*10,
            'positionid' => $pos2->id,
            'organisationid' => $org2->id,
        );
        $ja2 = \totara_job\job_assignment::create($data2);

        $result = sep_services::get_users($client, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(5, $result['data']['users']); // Normal 4 users + admin.

        $this->assertSame(array(), $result['data']['users'][0]->jobs);
        $this->assertCount(2, $result['data']['users'][1]->jobs);
        $this->assertCount(1, $result['data']['users'][2]->jobs);
        $this->assertSame(array(), $result['data']['users'][3]->jobs);
        $this->assertSame(array(), $result['data']['users'][4]->jobs);
        $expected = array(
            'id' => $ja1a->id,
            'fullname' => $ja1a->fullname,
            'shortname' => $ja1a->shortname,
            'idnumber' => $ja1a->idnumber,
            'description' => $ja1a->description,
            'startdate' => $ja1a->startdate,
            'enddate' => $ja1a->enddate,
            'timecreated' => $ja1a->timecreated,
            'timemodified' => $ja1a->timemodified,
            'usermodified' => $ja1a->usermodified,
            'positionid' => $ja1a->positionid,
            'positionassignmentdate' => $ja1a->positionassignmentdate,
            'organisationid' => $ja1a->organisationid,
            'sortorder' => $ja1a->sortorder,
            'totarasync' => '0',
            'synctimemodified' => '0',
        );
        $this->assertSame($expected, (array)$result['data']['users'][1]->jobs[0]);
        $expected = array(
            'id' => $ja1b->id,
            'fullname' => null,
            'shortname' => $ja1b->shortname,
            'idnumber' => $ja1b->idnumber,
            'description' => null,
            'startdate' => $ja1b->startdate,
            'enddate' => $ja1b->enddate,
            'timecreated' => $ja1b->timecreated,
            'timemodified' => $ja1b->timemodified,
            'usermodified' => $ja1b->usermodified,
            'positionid' => $ja1b->positionid,
            'positionassignmentdate' => $ja1b->positionassignmentdate,
            'organisationid' => $ja1b->organisationid,
            'sortorder' => $ja1b->sortorder,
            'totarasync' => '0',
            'synctimemodified' => '0',
        );
        $this->assertSame($expected, (array)$result['data']['users'][1]->jobs[1]);
        $expected = array(
            'id' => $ja2->id,
            'fullname' => $ja2->fullname,
            'shortname' => $ja2->shortname,
            'idnumber' => $ja2->idnumber,
            'description' => $ja2->description,
            'startdate' => $ja2->startdate,
            'enddate' => $ja2->enddate,
            'timecreated' => $ja2->timecreated,
            'timemodified' => $ja2->timemodified,
            'usermodified' => $ja2->usermodified,
            'positionid' => $ja2->positionid,
            'positionassignmentdate' => $ja2->positionassignmentdate,
            'organisationid' => $ja2->organisationid,
            'sortorder' => $ja2->sortorder,
            'totarasync' => '0',
            'synctimemodified' => '0',
        );
        $this->assertSame($expected, (array)$result['data']['users'][2]->jobs[0]);
    }

    public function test_get_users_profile_fields() {
        $this->resetAfterTest();

        // TODO
    }

    public function test_get_user_collections() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        delete_user($user4);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $cohort2 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user3->id);

        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);

        // Sortorders are busted and the generator uses APIs incorrectly.
        $course1 = $DB->get_record('course', array('id' => $course1->id));
        $course2 = $DB->get_record('course', array('id' => $course2->id));

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');

        $client1 = $generator->create_client();
        util::add_client_cohort($client1, $cohort1->id);
        util::add_client_cohort($client1, $cohort2->id);
        util::add_client_course($client1, $course1->id);
        util::add_client_course($client1, $course2->id);

        $client2 = $generator->create_client(array('cohortid' => $cohort->id));
        util::add_client_cohort($client2, $cohort1->id);
        util::add_client_cohort($client2, $cohort2->id);
        util::add_client_course($client2, $course1->id);
        util::add_client_course($client2, $course2->id);

        $client3 = $generator->create_client();

        // Test unrestricted client with cohorts and courses.

        $result = sep_services::get_user_collections($client1, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']['cohort']);
        $this->assertCount(2, $result['data']['course']);

        $c1 = $result['data']['cohort'][0];
        $this->assertSame(array(array('id' => $user1->id), array('id' => $user2->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($cohort1, $c1);

        $c2 = $result['data']['cohort'][1];
        $this->assertSame(array(array('id' => $user1->id), array('id' => $user3->id)), $c2->members);
        unset($c2->members);
        $this->assertEquals($cohort2, $c2);

        $c1 = $result['data']['course'][0];
        $this->assertSame(array(array('id' => $user1->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($course1, $c1);

        $c2 = $result['data']['course'][1];
        $this->assertSame(array(array('id' => $user3->id)), $c2->members);
        unset($c2->members);
        $this->assertEquals($course2, $c2);

        // Test restricted client.

        $result = sep_services::get_user_collections($client2, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']['cohort']);
        $this->assertCount(2, $result['data']['course']);

        $c1 = $result['data']['cohort'][0];
        $this->assertSame(array(array('id' => $user1->id), array('id' => $user2->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($cohort1, $c1);

        $c2 = $result['data']['cohort'][1];
        $this->assertSame(array(array('id' => $user1->id)), $c2->members);
        unset($c2->members);
        $this->assertEquals($cohort2, $c2);

        $c1 = $result['data']['course'][0];
        $this->assertSame(array(array('id' => $user1->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($course1, $c1);

        $c2 = $result['data']['course'][1];
        $this->assertSame(array(), $c2->members);
        unset($c2->members);
        $this->assertEquals($course2, $c2);

        // No cohort or course.
        $result = sep_services::get_user_collections($client3, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(0, $result['data']['cohort']);
        $this->assertCount(0, $result['data']['course']);
    }

    public function test_get_positions() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var totara_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('totara_connect');

        $pos_type1id = $hierarchygenerator->create_pos_type();
        $pos_framework1 = $hierarchygenerator->create_pos_frame(array());
        $pos_framework2 = $hierarchygenerator->create_pos_frame(array());
        $pos_framework3 = $hierarchygenerator->create_pos_frame(array());

        $pos1 = $hierarchygenerator->create_pos(array('frameworkid' => $pos_framework1->id, 'typeid' => $pos_type1id));
        $pos1->custom_fields = array();
        $pos1->typeidnumber = $DB->get_field('pos_type', 'idnumber', array('id' => $pos_type1id));
        $pos2 = $hierarchygenerator->create_pos(array('frameworkid' => $pos_framework2->id));
        $pos2->typeidnumber = null;
        $pos2->custom_fields = array();
        $pos3 = $hierarchygenerator->create_pos(array('frameworkid' => $pos_framework3->id));
        $pos3->typeidnumber = null;
        $pos3->custom_fields = array();

        $client1 = $connectgenerator->create_client(array('apiversion' => 1, 'syncjobs' => 1, 'positionframeworks' => [$pos_framework2->id]));
        $client2 = $connectgenerator->create_client(array('apiversion' => 2, 'syncjobs' => 1, 'positionframeworks' => [$pos_framework1->id, $pos_framework3->id]));

        $result = sep_services::get_positions($client1, array());
        $this->assertSame('error', $result['status']);
        $this->assertSame('get_positions not available in api version 1', $result['message']);

        $result = sep_services::get_positions($client2, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']);

        // Normalise the order of results first.
        core_collator::asort_objects_by_property($result['data']['frameworks'], 'id', core_collator::SORT_NUMERIC);
        $result['data']['frameworks'] = array_values($result['data']['frameworks']);
        core_collator::asort_objects_by_property($result['data']['positions'], 'id', core_collator::SORT_NUMERIC);
        $result['data']['positions'] = array_values($result['data']['positions']);

        $this->assertEquals(array($pos_framework1, $pos_framework3), $result['data']['frameworks']);
        $this->assertEquals(array($pos1, $pos3), $result['data']['positions']);
    }

    public function test_get_organisations() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var totara_connect_generator $connectgenerator */
        $connectgenerator = $this->getDataGenerator()->get_plugin_generator('totara_connect');

        $org_type1id = $hierarchygenerator->create_org_type();
        $org_framework1 = $hierarchygenerator->create_org_frame(array());
        $org_framework2 = $hierarchygenerator->create_org_frame(array());
        $org_framework3 = $hierarchygenerator->create_org_frame(array());

        $org1 = $hierarchygenerator->create_org(array('frameworkid' => $org_framework1->id, 'typeid' => $org_type1id));
        $org1->custom_fields = array();
        $org1->typeidnumber = $DB->get_field('org_type', 'idnumber', array('id' => $org_type1id));
        $org2 = $hierarchygenerator->create_org(array('frameworkid' => $org_framework2->id));
        $org2->typeidnumber = null;
        $org2->custom_fields = array();
        $org3 = $hierarchygenerator->create_org(array('frameworkid' => $org_framework3->id));
        $org3->typeidnumber = null;
        $org3->custom_fields = array();

        $client1 = $connectgenerator->create_client(array('apiversion' => 1, 'syncjobs' => 1, 'organisationframeworks' => [$org_framework2->id]));
        $client2 = $connectgenerator->create_client(array('apiversion' => 2, 'syncjobs' => 1, 'organisationframeworks' => [$org_framework1->id, $org_framework3->id]));

        $result = sep_services::get_organisations($client1, array());
        $this->assertSame('error', $result['status']);
        $this->assertSame('get_organisations not available in api version 1', $result['message']);

        $result = sep_services::get_organisations($client2, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']);

        // Normalise the order of results first.
        core_collator::asort_objects_by_property($result['data']['frameworks'], 'id', core_collator::SORT_NUMERIC);
        $result['data']['frameworks'] = array_values($result['data']['frameworks']);
        core_collator::asort_objects_by_property($result['data']['organisations'], 'id', core_collator::SORT_NUMERIC);
        $result['data']['organisations'] = array_values($result['data']['organisations']);

        $this->assertEquals(array($org_framework1, $org_framework3), $result['data']['frameworks']);
        $this->assertEquals(array($org1, $org3), $result['data']['organisations']);
    }

    public function test_get_sso_user() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');
        $client = $generator->create_client();

        // Some extra user session first.
        $this->setUser($user2);
        $session = util::create_sso_session($client);
        $session->sid = sha1('abc'); // Fake real session.
        $DB->set_field('totara_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        // The file handler is used by default, so let's fake the data somehow.
        $this->setUser($user1);
        $sid = md5('hokus');
        mkdir("$CFG->dataroot/sessions/", $CFG->directorypermissions, true);
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('totara_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);

        // Try to find out if everything works.
        $this->assertTrue(\core\session\manager::session_exists($sid));
        $this->assertSame('0', $DB->get_field('totara_connect_sso_sessions', 'active', array('id' => $session->id)));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $this->assertCount(54, (array)$result['data']);
        $this->assertSame('1', $DB->get_field('totara_connect_sso_sessions', 'active', array('id' => $session->id)));

        $user = (object)$result['data'];
        $this->assertSame($user1->id, $user->id);
        $this->assertNull($user->password);
        $this->assertObjectNotHasAttribute('secret', $user);
        $this->assertSame('0', $user->picture);
        $this->assertSame(array(), $user->pictures);
        $this->assertSame(FORMAT_HTML, $user->descriptionformat);

        // Now with password.

        set_config('syncpasswords', '1', 'totara_connect');

        $this->assertTrue(\core\session\manager::session_exists($sid));
        $DB->set_field('totara_connect_sso_sessions', 'active', 0, array('id' => $session->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $this->assertCount(54, (array)$result['data']);
        $this->assertSame('1', $DB->get_field('totara_connect_sso_sessions', 'active', array('id' => $session->id)));

        $user = (object)$result['data'];
        $this->assertSame($user1->id, $user->id);
        $this->assertNotNull($user->password);
        $this->assertObjectNotHasAttribute('secret', $user);
        $this->assertSame('0', $user->picture);
        $this->assertSame(array(), $user->pictures);

        // Test for all errors.

        $result = sep_services::get_sso_user($client, array());
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing sso token', $result['data']['ssotoken']);

        $result = sep_services::get_sso_user($client, array('ssotoken' => sha1('unknown')));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('invalid sso token', $result['data']['ssotoken']);

        // Reused ssotoken.

        $DB->set_field('totara_connect_sso_sessions', 'active', 0, array('id' => $session->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('reused ssotoken', $result['message']);

        // Session timed out.

        unlink("$CFG->dataroot/sessions/sess_$sid");
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $this->assertTrue($DB->record_exists('totara_connect_sso_sessions', array('id' => $session->id)));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('session expired', $result['message']);
        $this->assertFalse($DB->record_exists('totara_connect_sso_sessions', array('id' => $session->id)));

        // Session messed up.

        $this->setUser($user1);
        $sid = md5('pokus');
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('totara_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);
        $this->assertTrue(\core\session\manager::session_exists($sid));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);

        $DB->set_field('totara_connect_sso_sessions', 'userid', $user2->id, array('id' => $session->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('invalid user session', $result['message']);
        $this->assertFalse($DB->record_exists('totara_connect_sso_sessions', array('id' => $session->id)));

        // User incorrectly deleted.

        $this->setUser($user1);
        $sid = md5('roks');
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('totara_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);
        $this->assertTrue(\core\session\manager::session_exists($sid));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);

        $DB->set_field('user', 'deleted', 1, array('id' => $user1->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('invalid user session', $result['message']);
        $this->assertFalse($DB->record_exists('totara_connect_sso_sessions', array('id' => $session->id)));
    }

    public function test_force_sso_logout() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');
        $client = $generator->create_client();

        // Some extra user session first.
        $this->setUser($user2);
        $session = util::create_sso_session($client);
        $session->sid = sha1('abc'); // Fake real session.
        $DB->set_field('totara_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        // The file handler is used by default, so let's fake the data somehow.
        $this->setUser($user1);
        $sid = md5('hokus');
        mkdir("$CFG->dataroot/sessions/", $CFG->directorypermissions, true);
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('totara_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);
        $this->assertTrue(\core\session\manager::session_exists($sid));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $this->assertFalse($DB->record_exists('totara_connect_sso_sessions', array('id' => $session->id)));

        // Repeated execution not a problem.

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => sha1('xxzzxxz')));
        $this->assertSame('success', $result['status']);

        // Test errors.

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array());
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing sso token', $result['data']['ssotoken']);

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => 'xxxx'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('invalid sso token format', $result['data']['ssotoken']);
    }

    public function test_delete_client() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        delete_user($user4);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $cohort2 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user3->id);

        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');

        $client1 = $generator->create_client();
        util::add_client_cohort($client1, $cohort1->id);
        util::add_client_cohort($client1, $cohort2->id);
        util::add_client_course($client1, $course1->id);
        util::add_client_course($client1, $course2->id);

        $client2 = $generator->create_client(array('cohortid' => $cohort->id));
        util::add_client_cohort($client2, $cohort1->id);
        util::add_client_cohort($client2, $cohort2->id);
        util::add_client_course($client2, $course1->id);
        util::add_client_course($client2, $course2->id);

        $client3 = $generator->create_client();

        $this->setUser($user1);
        util::create_sso_session($client1);
        util::create_sso_session($client2);

        $this->assertCount(3, $DB->get_records('totara_connect_clients'));
        $this->assertCount(0, $DB->get_records('totara_connect_clients', array('status' => util::CLIENT_STATUS_DELETED)));
        $this->assertCount(2, $DB->get_records('totara_connect_sso_sessions'));
        $this->assertCount(4, $DB->get_records('totara_connect_client_cohorts'));
        $this->assertCount(4, $DB->get_records('totara_connect_client_courses'));
        $this->assertCount(1, $DB->get_records('totara_connect_sso_sessions', array('clientid' => $client1->id)));
        $this->assertCount(2, $DB->get_records('totara_connect_client_cohorts', array('clientid' => $client1->id)));
        $this->assertCount(2, $DB->get_records('totara_connect_client_courses', array('clientid' => $client1->id)));

        $this->setCurrentTimeStart();
        $result = sep_services::delete_client($client1, array());
        $this->assertSame('success', $result['status']);
        $client = $DB->get_record('totara_connect_clients', array('id' => $client1->id));
        $this->assertEquals(util::CLIENT_STATUS_DELETED, $client->status);
        $this->assertTimeCurrent($client->timemodified);

        $this->assertCount(3, $DB->get_records('totara_connect_clients'));
        $this->assertCount(1, $DB->get_records('totara_connect_clients', array('status' => util::CLIENT_STATUS_DELETED)));
        $this->assertCount(1, $DB->get_records('totara_connect_sso_sessions'));
        $this->assertCount(2, $DB->get_records('totara_connect_client_cohorts'));
        $this->assertCount(2, $DB->get_records('totara_connect_client_courses'));
        $this->assertCount(0, $DB->get_records('totara_connect_sso_sessions', array('clientid' => $client1->id)));
        $this->assertCount(0, $DB->get_records('totara_connect_client_cohorts', array('clientid' => $client1->id)));
        $this->assertCount(0, $DB->get_records('totara_connect_client_courses', array('clientid' => $client1->id)));

        // Run repeatedly.
        $result = sep_services::delete_client($client1, array());
        $this->assertSame('success', $result['status']);
    }
}
