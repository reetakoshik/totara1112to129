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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

class totara_hierarchy_positionassign_testcase extends advanced_testcase {

    protected $user1, $user2, $user3, $user4, $user5;

    protected $pos_framework_data = array(
        'id' => 1, 'fullname' => 'Postion Framework 1', 'shortname' => 'PFW1', 'idnumber' => 'ID1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $pos_data = array(
        array('id' => 1, 'fullname' => 'Data Analyst', 'shortname' => 'Analyst', 'idnumber' => 'DA1', 'frameworkid' => 1,
              'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
              'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2),
        array('id' => 2, 'fullname' => 'Software Developer', 'shortname' => 'Developer', 'idnumber' => 'SD1', 'frameworkid' => 1,
              'path' => '/2', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
              'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2)
    );

    protected function tearDown() {
        $this->user1 = $this->user2 = $this->user3 = $this->user4 = $this->user5 = null;
        $this->pos_framework_data = null;
        $this->pos_data = null;

        parent::tearDown();
    }

    protected function setUp() {
        global $DB;
        parent::setUp();

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();
        $this->user4 = $this->getDataGenerator()->create_user();
        $this->user5 = $this->getDataGenerator()->create_user();

        $DB->insert_record('pos_framework', $this->pos_framework_data);
        $DB->insert_record('pos', $this->pos_data[0]);
        $DB->insert_record('pos', $this->pos_data[1]);

        // First job assignments:
        // user5
        // user1
        //  |
        //  |->user2
        //  |->user3
        $data = array(
            'userid' => $this->user5->id,
            'fullname' => 'User 5 assignment 1',
            'idnumber' => 'U5A1',
            'positionid' => 1,
        );
        \totara_job\job_assignment::create($data);

        $data = array(
            'userid' => $this->user1->id,
            'fullname' => 'User 1 assignment 1',
            'idnumber' => 'U1A1',
            'positionid' => 1,
        );
        $user1firstja = \totara_job\job_assignment::create($data);

        $data = array(
            'userid' => $this->user2->id,
            'fullname' => 'User 2 assignment 1',
            'idnumber' => 'U2A1',
            'positionid' => 1,
            'managerjaid' => $user1firstja->id,
        );
        \totara_job\job_assignment::create($data);

        $data = array(
            'userid' => $this->user3->id,
            'fullname' => 'User 3 assignment 1',
            'idnumber' => 'U3A1',
            'positionid' => 1,
            'managerjaid' => $user1firstja->id,
        );
        \totara_job\job_assignment::create($data);

        // Second job assignment:
        // user5
        // user2
        //  |
        //  |-user1
        //  |-user3
        $data = array(
            'userid' => $this->user5->id,
            'fullname' => 'User 5 assignment 2',
            'idnumber' => 'U5A2',
            'positionid' => 1,
        );
        \totara_job\job_assignment::create($data);

        $data = array(
            'userid' => $this->user2->id,
            'fullname' => 'User 2 assignment 2',
            'idnumber' => 'U2A2',
            'positionid' => 1,
        );
        $user2secondja = \totara_job\job_assignment::create($data);

        $data = array(
            'userid' => $this->user1->id,
            'fullname' => 'User 1 assignment 2',
            'idnumber' => 'U1A2',
            'positionid' => 1,
            'managerjaid' => $user2secondja->id,
        );
        \totara_job\job_assignment::create($data);

        $data = array(
            'userid' => $this->user3->id,
            'fullname' => 'User 3 assignment 2',
            'idnumber' => 'U3A2',
            'positionid' => 1,
            'managerjaid' => $user2secondja->id,
        );
        \totara_job\job_assignment::create($data);
    }

    public function test_assign_top_level_user() {
        global $DB;
        $this->resetAfterTest();

        $user1ja = \totara_job\job_assignment::get_with_idnumber($this->user1->id, 'U1A1');
        $user2ja = \totara_job\job_assignment::get_with_idnumber($this->user2->id, 'U2A1');
        $user3ja = \totara_job\job_assignment::get_with_idnumber($this->user3->id, 'U3A1');
        $user5ja = \totara_job\job_assignment::get_with_idnumber($this->user5->id, 'U5A1');

        // Assign to top level user.
        // Set user5 to be user2's manager. User5 has no manager. Check that user2 has been given correct path.
        $user2ja->update(array('managerjaid' => $user5ja->id));

        if (!$field = $DB->get_field('job_assignment', 'managerjapath',
            array('id' => $user2ja->id))) {

            $this->fail();
        }
        // Check correct path.
        $path = "/{$user5ja->id}/{$user2ja->id}";
        $this->assertEquals($path, $field);

        // Give user5 a manager and check that user2 was correctly updated.
        $user5ja->update(array('managerjaid' => $user3ja->id));

        if (!$field = $DB->get_field('job_assignment', 'managerjapath',
            array('id' => $user2ja->id))) {

            $this->fail();
        }
        // Check correct path.
        $path = "/{$user1ja->id}/{$user3ja->id}/{$user5ja->id}/{$user2ja->id}";
        $this->assertEquals($path, $field);
    }

    public function test_assign_lower_level_user() {
        global $DB;
        $this->resetAfterTest();

        $user1ja = \totara_job\job_assignment::get_with_idnumber($this->user1->id, 'U1A1');
        $user2ja = \totara_job\job_assignment::get_with_idnumber($this->user2->id, 'U2A1');
        $user3ja = \totara_job\job_assignment::get_with_idnumber($this->user3->id, 'U3A1');
        $user5ja = \totara_job\job_assignment::get_with_idnumber($this->user5->id, 'U5A1');

        // Assign to a lower level user.
        // Assign B->A where A is assigned to X.
        $user5ja->update(array('managerjaid' => $user2ja->id));

        if (!$field = $DB->get_field('job_assignment', 'managerjapath',
            array('id' => $user5ja->id))) {

            $this->fail();
        }
        $path = "/{$user1ja->id}/{$user2ja->id}/{$user5ja->id}";
        $this->assertEquals($path, $field);

        // Reassign A to a new parent and check B is updated.
        $user2ja->update(array('managerjaid' => $user3ja->id));

        if (!$field = $DB->get_field('job_assignment', 'managerjapath', array('id' => $user5ja->id))) {
            $this->fail();
        }
        $path = "/{$user1ja->id}/{$user3ja->id}/{$user2ja->id}/{$user5ja->id}";
        $this->assertEquals($path, $field);
    }

}
