<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package totara_job
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/job/dialog/assign_manager.php');

class totara_job_dialog_assign_manager_testcase extends advanced_testcase {

    /** @var  testing_data_generator */
    private $data_generator;

    private $max_users = 10;
    private $users = array();
    private $userids = array();
    private $userfullnames = array();

    protected function tearDown() {
        $this->data_generator = null;
        $this->max_users = null;
        $this->users = null;
        $this->userids = null;
        $this->userfullnames = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setup();
        $this->resetAfterTest();

        $this->data_generator = $this->getDataGenerator();
        for($i = 0; $i < $this->max_users; $i++) {
            $user = $this->data_generator->create_user();
            $this->users[$i] = $user;
            $this->userids[$i] = $user->id;
            $this->userfullnames[$i] = fullname($user);
        }
    }

    private function execute_restricted_method($object, $methodname, $arguments = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $arguments);
    }

    private function get_restricted_property($object, $propertyname) {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyname);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public function test_load_managers() {
        $currentuser = $this->users[0];

        $dialog = new totara_job_dialog_assign_manager($currentuser->id);
        $this->execute_restricted_method($dialog, 'load_managers');
        $dialogmanagers = $this->get_restricted_property($dialog, 'managers');

        // The count should equal max users, because although the current user won't be in the list,
        // the admin user should have been added.
        $this->assertEquals($this->max_users, count($dialogmanagers));

        $admin = get_admin();
        $guest = guest_user();

        $expecteduserids = $this->userids;
        $expecteduserids[] = $admin->id;
        foreach($dialogmanagers as $dialogmanager) {
            // Remove the 'mgr' prefix from the id.
            $dialogmanagerid = (int)substr($dialogmanager->id, 3);
            $this->assertNotEquals($guest->id, $dialogmanagerid);
            $this->assertNotEquals($currentuser->id, $dialogmanagerid);
            $this->assertContains($dialogmanagerid, $expecteduserids);
        }
    }

    public function test_load_job_assignments() {
        // $manager is who we'll test with for returning the correct data.
        $currentuser = $this->users[0];
        $manager = $this->users[1];
        $notmanager = $this->users[2];

        // Set admin user to pass permission checks for being allowed to create a user.
        $this->setAdminUser();

        $jobdata1 = array(
            'userid' => $manager->id,
            'idnumber' => 1
        );
        $newjobassignment1 = \totara_job\job_assignment::create($jobdata1);
        $jobdata2 = array(
            'userid' => $manager->id,
            'idnumber' => 2,
            'fullname' => 'Job2 Fullname'
        );
        $newjobassignment2 = \totara_job\job_assignment::create($jobdata2);
        $jobdata3 = array(
            'userid' => $notmanager->id,
            'idnumber' => 3,
            'fullname' => 'Not managers job'
        );
        $newjobassignment3 = \totara_job\job_assignment::create($jobdata3);
        $jobdata4 = array(
            'userid' => $currentuser->id,
            'idnumber' => 4,
            'fullname' => 'Current users job'
        );
        $newjobassignment4 = \totara_job\job_assignment::create($jobdata4);

        $prefixedmgrid = 'mgr' . $manager->id;
        $dialog = new totara_job_dialog_assign_manager($currentuser->id, $prefixedmgrid);
        $this->execute_restricted_method($dialog, 'load_job_assignments');
        $jobassignments = $this->get_restricted_property($dialog, 'jobassignments');

        $expectednames = array(
            'Unnamed job assignment (ID: 1)',
            'Job2 Fullname',
            'Create empty job assignment'
        );
        // Should be 3 as there are 2 job assignments for $manager + the option to create a new one.
        $this->assertEquals(3, count($jobassignments));
        foreach($jobassignments as $jobassignment) {
            $this->assertContains($jobassignment->name, $expectednames);
            $this->assertNotEquals('Other users job', $jobassignment->name);
        }
    }

    public function test_search_managers() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/core/searchlib.php');

        $currentuser = $this->users[0];

        // Create our own users here so that we can search for them.
        $manager1 = $this->data_generator->create_user(['lastname' => 'Smith-Jennison', 'firstname' => 'Richard']);
        $manager2 = $this->data_generator->create_user(['lastname' => 'Robertson', 'firstname' => 'Jennifer']);
        $manager3 = $this->data_generator->create_user(['lastname' => 'Jenkins', 'firstname' => 'Bob']);

        // Set admin user to pass permission checks for being allowed to create a user.
        $this->setAdminUser();

        // Prepare job assignments for managers:
        // - manager1 - 2 job assignments
        // - manager2 - 1 job assignment
        // - manager3 - no job assignment
        $jobdata1 = array('userid' => $manager1->id, 'idnumber' => 1, 'fullname' => 'Manager 1 job 1');
        $ja11 = \totara_job\job_assignment::create($jobdata1);
        $jobdata2 = array('userid' => $manager1->id, 'idnumber' => 2, 'fullname' => 'Manager 1 job 2');
        $ja12 = \totara_job\job_assignment::create($jobdata2);
        $jobdata3 = array('userid' => $manager2->id, 'idnumber' => 3, 'fullname' => 'Manager 2 job');
        $ja21 = \totara_job\job_assignment::create($jobdata3);

        $dialog = new totara_job_dialog_assign_manager($currentuser->id);
        list($sql, $params) = $this->execute_restricted_method($dialog, 'get_managers_joinsql_and_params', array(true));

        $fields = get_all_user_name_fields(false, 'u', null, null, true);
        $keywords = totara_search_parse_keywords('jen');
        list($searchsql, $searchparams) = totara_search_get_keyword_where_clause($keywords, $fields, SQL_PARAMS_NAMED, 'u');

        $search_info = new stdClass();
        $search_info->id = 'COALESCE((' . $DB->sql_concat_join('\'-\'', array('u.id', 'managerja.id')) . '), '
            . $DB->sql_concat('u.id', '\'-\'') . ')';
        $search_info->fullnamefields = 'managerja.fullname, managerja.idnumber, u.id AS userid, managerja.id AS jaid, ' . implode(',', $fields);
        $search_info->sql = $sql . ' AND ' . $searchsql;
        $search_info->params = array_merge($params, $searchparams);
        $search_info->order = ' ORDER BY ' . implode(',', $fields);
        $search_info->datakeys = array('userid', 'jaid', 'displaystring');
        $select = "SELECT {$search_info->id} AS id, {$search_info->fullnamefields} ";

        // Get the search results.
        $results = $DB->get_records_sql($select . $search_info->sql . $search_info->order, $search_info->params);

        set_config('totara_job_allowmultiplejobs', 1);
        $items = $this->execute_restricted_method($dialog, 'get_search_items_array', array($results));
        // MySQL doesn't append '-' when manager job ID doesn't exist, so strip any dashes from the keys for testing.
        $keys = array_map(function($item) { return rtrim($item, '-'); }, array_keys($items));
        // Count: manager1 2+NEW; manager2 1+NEW; manager3 NEW;
        $this->assertCount(6, $items);
        $this->assertContains($manager1->id . '-' . $ja11->id, $keys);
        $this->assertContains($manager1->id . '-' . $ja12->id, $keys);
        $this->assertContains($manager1->id . '-NEW', $keys);
        $this->assertContains($manager3->id, $keys); // Managers without jobs don't get NEW appended to the key.

        set_config('totara_job_allowmultiplejobs', 0);
        $dialog = new totara_job_dialog_assign_manager($currentuser->id); // New dialog object to update config setting.
        $items = $this->execute_restricted_method($dialog, 'get_search_items_array', array($results));
        // MySQL doesn't append '-' when manager job ID doesn't exist, so strip any dashes from the keys for testing.
        $keys = array_map(function($item) { return rtrim($item, '-'); }, array_keys($items));
        // Count: manager1 2; manager2 1; manager3 NEW;
        $this->assertCount(4, $items);
        $this->assertContains($manager1->id . '-' . $ja11->id, $keys);
        $this->assertContains($manager1->id . '-' . $ja12->id, $keys);
        $this->assertNotContains($manager1->id . '-NEW', $keys); // Can't create any more job assignments.
        $this->assertContains($manager3->id, $keys); // Managers without jobs don't get NEW appended to the key.
    }

    public function test_get_managers_from_db() {
        $currentuser = $this->users[0];
        $manager = $this->users[1];

        $dialog = new totara_job_dialog_assign_manager($currentuser->id);

        // Test without specifying a manager.
        $allmanagers = $this->execute_restricted_method($dialog, 'get_managers_from_db');
        $this->assertEquals($this->max_users, count($allmanagers));
        $admin = get_admin();
        $guest = guest_user();

        $expecteduserids = $this->userids;
        $expecteduserids[] = $admin->id;
        foreach($allmanagers as $manager) {
            $this->assertNotEquals($guest->id, $manager->id);
            $this->assertNotEquals($currentuser->id, $manager->id);
            $this->assertContains($manager->id, $expecteduserids);
            // Check sensitive fields are not being returned.
            $this->assertFalse(isset($manager->password));
        }

        // Now execute with a manager id.
        $returnedmanager = $this->execute_restricted_method($dialog, 'get_managers_from_db', array($manager->id));
        $this->assertEquals($manager->id, $returnedmanager->id);
        $this->assertEquals($manager->firstname, $returnedmanager->firstname);
        $this->assertEquals($manager->lastname, $returnedmanager->lastname);
        $this->assertFalse(isset($manager->password));
    }
}
