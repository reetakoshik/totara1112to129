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

use \totara_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests util class.
 */
class totara_connect_generator_testcase extends advanced_testcase {
    public function test_create_client() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $pos_framework1 = $hierarchygenerator->create_pos_frame(array());
        $pos_framework2 = $hierarchygenerator->create_pos_frame(array());
        $org_framework1 = $hierarchygenerator->create_org_frame(array());
        $org_framework2 = $hierarchygenerator->create_org_frame(array());

        $this->setCurrentTimeStart();
        $client = $generator->create_client();
        $this->assertEquals(util::CLIENT_STATUS_OK, $client->status);
        $this->assertSame(40, strlen($client->clientidnumber));
        $this->assertSame(40, strlen($client->clientsecret));
        $this->assertStringStartsWith('Some client ', $client->clientname);
        $this->assertSame('https://www.example.com/totara', $client->clienturl);
        $this->assertSame('totaralms', $client->clienttype);
        $this->assertSame('', $client->clientcomment);
        $this->assertSame(null, $client->cohortid);
        $this->assertSame(40, strlen($client->serversecret));
        $this->assertSame('0', $client->addnewcohorts);
        $this->assertSame('0', $client->addnewcourses);
        $this->assertSame('0', $client->syncjobs);
        $this->assertSame('1', $client->apiversion);
        $this->assertTimeCurrent($client->timecreated);
        $this->assertSame($client->timecreated, $client->timemodified);

        $cohort = $this->getDataGenerator()->create_cohort();

        $record = array(
            'clientname' => 'My name',
            'clienturl' => 'http://example.net',
            'clienttype' => 'totarasocial',
            'cohortid' => (string)$cohort->id,
            'addnewcohorts' => '1',
            'addnewcourses' => '1',
        );
        $client2 = $generator->create_client($record);
        foreach ($record as $k => $v) {
            $this->assertSame($v, $client2->$k);
        }

        $record = array(
            'clientname' => 'My name',
            'clienturl' => 'http://example.net',
            'clienttype' => '',
            'cohortid' => (string)$cohort->id,
            'syncjobs' => '1',
            'addnewcohorts' => '1',
            'addnewcourses' => '1',
            'apiversion' => '2',
        );
        $client3 = $generator->create_client($record);
        foreach ($record as $k => $v) {
            $this->assertSame($v, $client3->$k);
        }

        $record = array(
            'clientname' => 'My name',
            'clienturl' => 'http://example.net',
            'syncjobs' => '1',
            'positionframeworks' => array($pos_framework1->id),
            'organisationframeworks' => array($org_framework2->id),
            'apiversion' => '2',
        );
        $client4 = $generator->create_client($record);
        $this->assertCount(1, $DB->get_records('totara_connect_client_pos_frameworks'));
        $this->assertCount(1, $DB->get_records('totara_connect_client_org_frameworks'));
    }
}
