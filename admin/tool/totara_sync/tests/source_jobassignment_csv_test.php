<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_jobassignment_csv.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_source_jobassignment_csv_testcase extends advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        $this->preventResetByRollback();

        set_config('source_jobassignment', 'totara_sync_source_jobassignment_csv', 'totara_sync');
    }

    private function set_fixture_as_import_file($fixturefile) {
        global $CFG;

        $tempdir = make_temp_directory('hrimporttests');
        mkdir($tempdir . '/csv');
        mkdir($tempdir . '/csv/ready');
        copy(
            $CFG->dirroot . '/admin/tool/totara_sync/tests/fixtures/jobassignment/' . $fixturefile,
            $tempdir . '/csv/ready/jobassignment.csv'
        );
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $tempdir, 'totara_sync');
    }

    public function test_empty_strings_erase() {
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'fullname' =>'Developer', 'totarasync' => 1]);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev2', 'fullname' =>'Developer', 'totarasync' => 1]);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev3', 'fullname' =>'Developer', 'totarasync' => 1]);

        // The file has a fullname of "" for dev1 and 0 for dev2. Nothing for dev3.
        $this->set_fixture_as_import_file('blankfullname.csv');

        $source = new totara_sync_source_jobassignment_csv();
        $source->set_config('import_fullname', '1');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('csvsaveemptyfields', true);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(3, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Unnamed job assignment (ID: dev1)', $jobassignments[1]->fullname);
        $this->assertEquals('dev2', $jobassignments[2]->idnumber);
        $this->assertEquals('0', $jobassignments[2]->fullname);
        $this->assertEquals('dev3', $jobassignments[3]->idnumber);
        $this->assertEquals('Developer', $jobassignments[3]->fullname);
    }

    public function test_empty_strings_ignored() {
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'fullname' =>'Developer', 'totarasync' => 1]);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev2', 'fullname' =>'Developer', 'totarasync' => 1]);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev3', 'fullname' =>'Developer', 'totarasync' => 1]);

        // The file has a fullname of "" for dev1 and 0 for dev2. Nothing for dev3.
        $this->set_fixture_as_import_file('blankfullname.csv');

        $source = new totara_sync_source_jobassignment_csv();
        $source->set_config('import_fullname', '1');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('csvsaveemptyfields', false);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(3, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Developer', $jobassignments[1]->fullname);
        $this->assertEquals('dev2', $jobassignments[2]->idnumber);
        $this->assertEquals('0', $jobassignments[2]->fullname);
        $this->assertEquals('dev3', $jobassignments[3]->idnumber);
        $this->assertEquals('Developer', $jobassignments[3]->fullname);
    }

    public function test_startdate_invalid() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $this->set_fixture_as_import_file('startdateinvalid.csv');

        $source = new totara_sync_source_jobassignment_csv();
        $source->set_config('import_startdate', '1');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($tenjune, $jobassignments[1]->startdate);
    }

    public function test_startdate_timestamp() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $this->set_fixture_as_import_file('startdatetimestamp.csv');

        $source = new totara_sync_source_jobassignment_csv();
        $source->set_config('import_startdate', '1');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(10000, $jobassignments[1]->startdate);
    }

    public function test_startdate_formatted() {
        $tenjune = totara_date_parse_from_format('d/m/Y', '10/06/2017');
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'startdate' => $tenjune, 'totarasync' => 1]);

        $this->set_fixture_as_import_file('startdateformatted.csv');

        $source = new totara_sync_source_jobassignment_csv();
        $source->set_config('import_startdate', '1');

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($user->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $fifteenjune = totara_date_parse_from_format('d/m/Y', '15/06/2017');
        $this->assertEquals($fifteenjune, $jobassignments[1]->startdate);
    }
}
