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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage appraisal
 *
 * Unit tests for appraisal class of totara/appraisal/lib.php
 */
global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

class totara_appraisal_appraisal_testcase extends appraisal_testcase {

    public function test_set_status() {
        $this->resetAfterTest();
        $appraisal = new appraisal();

        $this->expectException('appraisal_exception');
        $appraisal->set_status($appraisal::STATUS_CLOSED);
    }

    public function test_set_status2() {
        $this->resetAfterTest();
        $appraisal = new appraisal();

        $appraisal->set_status($appraisal::STATUS_ACTIVE);
        $this->assertNull($appraisal->timefinished);
    }

    public function test_set_status3() {
        $this->resetAfterTest();
        $appraisal = new appraisal();

        $this->expectException('appraisal_exception');
        $appraisal->set_status($appraisal::STATUS_COMPLETED);
    }

    public function test_appraisal_create() {
        $this->resetAfterTest();
        $appraisal = new appraisal();
        $data = new stdClass();
        $data->name = 'Appraisal 1';
        $data->description = 'description';
        $appraisal->set($data);
        $appraisal->save();
        $id = $appraisal->id;
        unset($appraisal);

        $check = new appraisal($id);
        $this->assertEquals($check->id, $id);
        $this->assertEquals($check->name, 'Appraisal 1');
        $this->assertEquals($check->description, 'description');
    }

    public function test_appraisal_edit() {
        $this->resetAfterTest();
        $def = array('name' => 'Appraisal', 'description' => 'Description');
        $appraisal = appraisal::build($def);

        $this->assertEquals($appraisal->name, 'Appraisal');
        $this->assertEquals($appraisal->description, 'Description');

        $data = new stdClass();
        $data->name = 'New Appraisal';
        $data->description = 'New Description';
        $appraisal->set($data)->save();
        $check = new appraisal($appraisal->id);
        unset($appraisal);
        $this->assertEquals($check->name, $data->name);
        $this->assertEquals($check->description, $data->description);
    }

    public function test_appraisal_delete() {
        $this->resetAfterTest();
        $wasappraisals = appraisal::fetch_all();
        $def1 = array('name' => 'Appraisal1');
        $def2 = array('name' => 'Appraisal2');
        $appraisal1 = appraisal::build($def1);
        $appraisal2 = appraisal::build($def1);
        $appraisal1->delete();
        $nowappraisals = appraisal::fetch_all();

        $this->assertEquals(count($wasappraisals)+1, count($nowappraisals));
        $this->assertTrue(isset($nowappraisals[$appraisal2->id]));
    }

    public function test_appraisal_duplicate() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $def = array('name' => 'Appraisal', 'description' => 'Description');
        $appraisal1 = appraisal::build($def);
        $cloned = appraisal::duplicate_appraisal($appraisal1->id);
        $appraisal2 = new appraisal($cloned->id);

        $this->assertEquals($appraisal1->name, $appraisal2->name);
        $this->assertEquals($appraisal1->description, $appraisal2->description);
        $this->assertGreaterThan($appraisal1->id, $appraisal2->id);
        $this->assertEmpty($appraisal2->timestarted);
        $this->assertEmpty($appraisal2->timefinished);
        $this->assertEquals($appraisal1->status, appraisal::STATUS_DRAFT);
    }

    public function test_appraisal_activate() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        list($appraisal1) = $this->prepare_appraisal_with_users();
        list($errors, $warnings) = $appraisal1->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        $appraisal1->activate();
        $this->update_job_assignments($appraisal1);

        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal1->status);
        $dbman = $DB->get_manager();
        $this->assertTrue($dbman->table_exists('appraisal_quest_data_'.$appraisal1->id));
        $assign2 = new totara_assign_appraisal('appraisal', $appraisal1);
        $this->assertTrue($assign2->assignments_are_stored());
        // The function get_current_users() returns a recordset so need to loop through to count.
        $users = $assign2->get_current_users();
        $count = 0;
        foreach ($users as $user) {
            $count++;
        }
        $this->assertEquals(2, $count);

    }

    public function test_appraisal_validate_wrong_status() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($appraisal) = $this->prepare_appraisal_with_users();
        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        list($errors, $warnings) = $appraisal->validate();
        $this->assertCount(1, $errors);
        $this->assertEquals(array('status'), array_keys($errors));

    }

    public function test_appraisal_validate_no_roles() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => array(appraisal::ROLE_LEARNER => 1))
                ))
            ))
        ));
        list($appraisal) = $this->prepare_appraisal_with_users($def);
        list($errors, $warnings) = $appraisal->validate();
        $this->assertArrayHasKey('roles', $errors);
    }

    public function test_appraisal_answers() {
        $this->resetAfterTest();
        $this->setAdminUser();
        list($appraisal, $users) = $this->prepare_appraisal_with_users();
        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $users[0]->id, $users[0]->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');
        $map = $this->map($appraisal);

        $saved = $appraisal->get_answers($map['pages']['Page'], $roleassignment);

        $questions = appraisal_question::fetch_appraisal($appraisal->id, null, null, array(), false);
        $question = new appraisal_question(current($questions)->id, $roleassignment);
        $field = $question->get_element()->get_prefix_form();

        $this->assertEquals('test', $saved->$field);
    }

    public function test_appraisal_complete_user() {
        $this->resetAfterTest();
        $this->setAdminUser();
        list($appraisal, $users) = $this->prepare_appraisal_with_users();
        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $this->assertEquals(2, $appraisal->count_incomplete_userassignments());

        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $users[0]->id, $users[0]->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');

        $updateduserassignment = appraisal_user_assignment::get_user($appraisal->id, $users[0]->id);
        $updateduser2assignment = appraisal_user_assignment::get_user($appraisal->id, $users[1]->id);

        $this->assertEquals(1, $appraisal->count_incomplete_userassignments());
        $this->assertTrue($appraisal->is_locked($updateduserassignment));
        $this->assertFalse($appraisal->is_locked($updateduser2assignment));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
    }

    public function test_appraisal_complete() {
        $this->resetAfterTest();
        $this->setAdminUser();
        list($appraisal, $users) = $this->prepare_appraisal_with_users();
        $appraisal->validate();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $this->assertEquals(2, $appraisal->count_incomplete_userassignments());

        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $users[0]->id, $users[0]->id,
                appraisal::ROLE_LEARNER);
        $roleassignment2 = appraisal_role_assignment::get_role($appraisal->id, $users[1]->id, $users[1]->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment, '', 'completestage');
        $this->answer_question($appraisal, $roleassignment2, '', 'completestage');

        $updateduserassignment = appraisal_user_assignment::get_user($appraisal->id, $users[0]->id);
        $updateduser2assignment = appraisal_user_assignment::get_user($appraisal->id, $users[1]->id);

        $this->assertEquals(0, $appraisal->count_incomplete_userassignments());
        $this->assertTrue($appraisal->is_locked($updateduserassignment));
        $this->assertTrue($appraisal->is_locked($updateduser2assignment));

        // Appraisals no longer auto complete due to dynamic assignments, check it is still open.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
    }

    public function test_appraisal_role_involved() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => 7,
                        appraisal::ROLE_MANAGER => 1,
                        appraisal::ROLE_APPRAISER => 2
                    ))
                ))
            ))
        ));
        list($appraisal) = $this->prepare_appraisal_with_users($def);

        $all = $appraisal->get_roles_involved();
        $this->assertContains(appraisal::ROLE_LEARNER, $all);
        $this->assertContains(appraisal::ROLE_MANAGER, $all);
        $this->assertContains(appraisal::ROLE_APPRAISER, $all);
        $this->assertNotContains(appraisal::ROLE_TEAM_LEAD, $all);

        $canviewothers = $appraisal->get_roles_involved(1);
        $this->assertContains(appraisal::ROLE_LEARNER, $canviewothers);
        $this->assertContains(appraisal::ROLE_MANAGER, $canviewothers);
        $this->assertNotContains(appraisal::ROLE_APPRAISER, $canviewothers);
        $this->assertNotContains(appraisal::ROLE_TEAM_LEAD, $canviewothers);

        $cananswer = $appraisal->get_roles_involved(2);
        $this->assertContains(appraisal::ROLE_LEARNER, $cananswer);
        $this->assertNotContains(appraisal::ROLE_MANAGER, $cananswer);
        $this->assertContains(appraisal::ROLE_APPRAISER, $cananswer);
        $this->assertNotContains(appraisal::ROLE_TEAM_LEAD, $cananswer);
    }

    public function test_appraisal_get_user_appraisal() {
        $this->resetAfterTest();
        $this->setAdminUser();
        list($appraisal1, $users1) = $this->prepare_appraisal_with_users();
        list($appraisal2) = $this->prepare_appraisal_with_users(array(), $users1);
        list($appraisal3, $users3) = $this->prepare_appraisal_with_users();
        foreach (array($appraisal1, $appraisal2, $appraisal3) as $appr) {
            $appr->validate();
            $appr->activate();
            $this->update_job_assignments($appr);
        }
        $appraisal2->close();
        $user4 = $this->getDataGenerator()->create_user();

        $this->setUser($users1[0]);
        $users1allappr = appraisal::get_user_appraisals($users1[0]->id, appraisal::ROLE_LEARNER);
        $users1actappr = appraisal::get_user_appraisals($users1[0]->id, appraisal::ROLE_LEARNER, appraisal::STATUS_CLOSED);
        $this->setUser($users3[0]);
        $users3actappr = appraisal::get_user_appraisals($users3[0]->id, appraisal::ROLE_LEARNER, appraisal::STATUS_CLOSED);
        $users3drftappr = appraisal::get_user_appraisals($users3[0]->id, appraisal::ROLE_LEARNER, appraisal::STATUS_ACTIVE);
        $users3managappr = appraisal::get_user_appraisals($users3[0]->id, appraisal::ROLE_MANAGER);
        $user4appr = appraisal::get_user_appraisals($user4->id, appraisal::ROLE_LEARNER);

        $this->assertCount(2, $users1allappr);
        $this->assertContains($appraisal1->id, array(current($users1allappr)->id, next($users1allappr)->id));
        $this->assertCount(1, $users1actappr);
        $this->assertEquals($appraisal2->id, current($users1actappr)->id);
        $this->assertEmpty($users3actappr);
        $this->assertCount(1, $users3drftappr);
        $this->assertEquals($appraisal3->id, current($users3drftappr)->id);
        $this->assertEmpty($users3managappr);
        $this->assertEmpty($user4appr);
    }

    public function test_appraisal_get_user_appraisal_sort_order() {
        global $DB, $CFG;

        // Set up users.
        $manager = $this->getDataGenerator()->create_user();
        $this->setUser($manager);

        $user1 = $this->getDataGenerator()->create_user((object)[
            'lastname' => 'Aardvark',
            'firstname' => 'Yannis',
        ]);
        $user2 = $this->getDataGenerator()->create_user((object)[
            'lastname' => 'Borg',
            'firstname' => 'Walt',
        ]);
        $user3 = $this->getDataGenerator()->create_user((object)[
            'lastname' => 'Chandler',
            'firstname' => 'Walt',
        ]);
        $user4 = $this->getDataGenerator()->create_user((object)[
            'lastname' => 'Doyley',
            'firstname' => 'Zacharias',
        ]);

        // Set up job assignments.
        $all_users = [$user1, $user2, $user3, $user4];
        $manager_ja = \totara_job\job_assignment::create_default($manager->id);
        $user_ja = [];
        foreach ($all_users as $user) {
            $user_ja[$user->id] = \totara_job\job_assignment::create_default($user->id, ['managerjaid' => $manager_ja->id]);
        }

        $def = [
            'name' => 'Appraisal 1', 'stages' => [
                [
                    'name' => 'Stage 1', 'timedue' => time() + 86400, 'pages' => [
                        [
                            'name' => 'Page 1', 'questions' => [
                                ['name' => 'Question text 1', 'type' => 'text', 'roles' =>
                                    [
                                        \appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER,
                                        \appraisal::ROLE_MANAGER => \appraisal::ACCESS_CANANSWER
                                    ],
                                ],
                            ]
                        ],
                    ]
                ],
            ]
        ];
        list($appraisal1) = $this->prepare_appraisal_with_users($def, $all_users);
        $def['name'] = 'Appraisal 2';
        list($appraisal2) = $this->prepare_appraisal_with_users($def, $all_users);
        $def['name'] = 'Appraisal 3';
        list($appraisal3) = $this->prepare_appraisal_with_users($def, $all_users);

        $all_appraisals = [$appraisal1, $appraisal2, $appraisal3];

        $now = time();

        foreach ($all_appraisals as $index => $appr) {
            $appr->validate();
            $appr->activate();
            $DB->update_record('appraisal', ['id' => $appr->id, 'timestarted' => $now + $index]);

            // Trigger job assignment allocation.
            foreach ($all_users as $user) {
                $appraisal_user_assignment = appraisal_user_assignment::get_user($appr->id, $user->id);
                $appraisal_user_assignment->with_job_assignment($user_ja[$user->id]->id);
            }
        }

        // Sorting should be by timestarted descending, then by user name fields configured for display.
        $oldconfig = $CFG->fullnamedisplay;
        $CFG->fullnamedisplay = 'firstname lastname';
        $appraisals = array_values(appraisal::get_user_appraisals($manager->id, appraisal::ROLE_MANAGER));
        $this->assertCount(12, $appraisals);
        $this->assertEquals('Appraisal 3', $appraisals[0]->name);
        $this->assertEquals($user2->id, $appraisals[0]->userid);
        $this->assertEquals('Appraisal 3', $appraisals[1]->name);
        $this->assertEquals($user3->id, $appraisals[1]->userid);
        $this->assertEquals('Appraisal 3', $appraisals[2]->name);
        $this->assertEquals($user1->id, $appraisals[2]->userid);
        $this->assertEquals('Appraisal 3', $appraisals[3]->name);
        $this->assertEquals($user4->id, $appraisals[3]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[4]->name);
        $this->assertEquals($user2->id, $appraisals[4]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[5]->name);
        $this->assertEquals($user3->id, $appraisals[5]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[6]->name);
        $this->assertEquals($user1->id, $appraisals[6]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[7]->name);
        $this->assertEquals($user4->id, $appraisals[7]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[8]->name);
        $this->assertEquals($user2->id, $appraisals[8]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[9]->name);
        $this->assertEquals($user3->id, $appraisals[9]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[10]->name);
        $this->assertEquals($user1->id, $appraisals[10]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[11]->name);
        $this->assertEquals($user4->id, $appraisals[11]->userid);

        // Change configuration for user name fields and make sure sorting changes accordingly.
        $CFG->fullnamedisplay = 'lastname firstname';
        $appraisals = array_values(appraisal::get_user_appraisals($manager->id, appraisal::ROLE_MANAGER));
        $this->assertCount(12, $appraisals);
        $this->assertEquals('Appraisal 3', $appraisals[0]->name);
        $this->assertEquals($user1->id, $appraisals[0]->userid);
        $this->assertEquals('Appraisal 3', $appraisals[1]->name);
        $this->assertEquals($user2->id, $appraisals[1]->userid);
        $this->assertEquals('Appraisal 3', $appraisals[2]->name);
        $this->assertEquals($user3->id, $appraisals[2]->userid);
        $this->assertEquals('Appraisal 3', $appraisals[3]->name);
        $this->assertEquals($user4->id, $appraisals[3]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[4]->name);
        $this->assertEquals($user1->id, $appraisals[4]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[5]->name);
        $this->assertEquals($user2->id, $appraisals[5]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[6]->name);
        $this->assertEquals($user3->id, $appraisals[6]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[7]->name);
        $this->assertEquals($user4->id, $appraisals[7]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[8]->name);
        $this->assertEquals($user1->id, $appraisals[8]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[9]->name);
        $this->assertEquals($user2->id, $appraisals[9]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[10]->name);
        $this->assertEquals($user3->id, $appraisals[10]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[11]->name);
        $this->assertEquals($user4->id, $appraisals[11]->userid);

        // As manager, get appraisals for one user.
        $appraisals = array_values(appraisal::get_user_appraisals($user1->id, appraisal::ROLE_MANAGER));
        $this->assertCount(3, $appraisals);
        $this->assertEquals('Appraisal 3', $appraisals[0]->name);
        $this->assertEquals($user1->id, $appraisals[0]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[1]->name);
        $this->assertEquals($user1->id, $appraisals[1]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[2]->name);
        $this->assertEquals($user1->id, $appraisals[2]->userid);

        // Should be empty because current user (manager) doesn't have learner role anywhere.
        $appraisals = array_values(appraisal::get_user_appraisals($manager->id, appraisal::ROLE_LEARNER));
        $this->assertCount(0, $appraisals);
        $appraisals = array_values(appraisal::get_user_appraisals($user1->id, appraisal::ROLE_LEARNER));
        $this->assertCount(0, $appraisals);

        // As user, get own appraisals.
        $this->setUser($user1);
        $appraisals = array_values(appraisal::get_user_appraisals($user1->id, appraisal::ROLE_LEARNER));
        $this->assertCount(3, $appraisals);
        $this->assertEquals('Appraisal 3', $appraisals[0]->name);
        $this->assertEquals($user1->id, $appraisals[0]->userid);
        $this->assertEquals('Appraisal 2', $appraisals[1]->name);
        $this->assertEquals($user1->id, $appraisals[1]->userid);
        $this->assertEquals('Appraisal 1', $appraisals[2]->name);
        $this->assertEquals($user1->id, $appraisals[2]->userid);

        // As user, call with role manager. Shouldn't return anything.
        $appraisals = array_values(appraisal::get_user_appraisals($user1->id, appraisal::ROLE_MANAGER));
        $this->assertCount(0, $appraisals);

        $CFG->fullnamedisplay = $oldconfig;
    }

    public function test_active_appraisal_add_group() {
        global $DB;

        // Set up active appraisal.
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var appraisal $appraisal */
        list($appraisal) = $this->prepare_appraisal_with_users();
        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        $this->assertEquals(appraisal::STATUS_DRAFT, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(0, $count);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(2, $count);

        // Set up group.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Add group.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        // There should still only be 2 user assignments.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(2, $count);

        // Force user assignments update.
        $appraisal->check_assignment_changes();

        // Check users have now gone up to 4.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(4, $count);

        // Check there were no job assignments auto-linked for the added users, because with default configuration this shouldn't happen.
        $this->assertCount(1, $DB->get_records('appraisal_user_assignment', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user1->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));
        $this->assertCount(1, $DB->get_records('appraisal_user_assignment', ['userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user2->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));
    }

    /**
     * Test auto-linking of job assignments works as expected when activating appraisal.
     */
    public function test_auto_link_job_assignment_on_activate() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Create one job assignment for user1.
        $user1ja = \totara_job\job_assignment::create_default($user1->id);

        // Create two job assignments for user2.
        \totara_job\job_assignment::create_default($user2->id);
        \totara_job\job_assignment::create_default($user2->id);

        // Switch allowmultiplejobs off.
        set_config('totara_job_allowmultiplejobs', 0);

        // Set up appraisal for all 3 users and activate.
        /** @var appraisal $appraisal */
        list($appraisal) = $this->prepare_appraisal_with_users([], [$user1, $user2, $user3]);
        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);
        $this->assertEquals(appraisal::STATUS_DRAFT, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', ['appraisalid' => $appraisal->id]);
        $this->assertEquals(0, $count);
        $appraisal->activate();

        // For user1 the existing job assignment should have been linked.
        $jobassignments = $DB->get_records('job_assignment', ['userid' => $user1->id]);
        $this->assertCount(1, $jobassignments);
        $jobassignment = reset($jobassignments);
        $this->assertEquals($user1ja->id, $jobassignment->id);
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user1->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => $user1ja->id,
        ]));

        // For user2 nothing should have been linked because he had more than 1 job assignment.
        $this->assertCount(1, $DB->get_records('appraisal_user_assignment', ['userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user2->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));

        // For user3 an empty job assignment should have been created and linked.
        $jobassignments = $DB->get_records('job_assignment', ['userid' => $user3->id]);
        $this->assertCount(1, $jobassignments);
        $user3ja = reset($jobassignments);
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user3->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => $user3ja->id,
        ]));
    }

    /**
     * Test auto-linking of job assignments works as expected when users are added to an active appraisal.
     */
    public function test_auto_link_job_assignment_on_dynamic_assignment() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Create one job assignment for user1.
        $user1ja = \totara_job\job_assignment::create_default($user1->id);

        // Create two job assignments for user2.
        \totara_job\job_assignment::create_default($user2->id);
        \totara_job\job_assignment::create_default($user2->id);

        // Switch allowmultiplejobs off.
        set_config('totara_job_allowmultiplejobs', 0);

        // Set up appraisal and activate.
        /** @var appraisal $appraisal */
        list($appraisal) = $this->prepare_appraisal_with_users();
        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);
        $this->assertEquals(appraisal::STATUS_DRAFT, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', ['appraisalid' => $appraisal->id]);
        $this->assertEquals(0, $count);
        $appraisal->activate();

        // Create audience and add to appraisal.
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);
        cohort_add_member($cohort->id, $user3->id);
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        // Our 3 users should not be assigned to the appraisal yet.
        $this->assertFalse($DB->record_exists('appraisal_user_assignment', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('appraisal_user_assignment', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('appraisal_user_assignment', ['userid' => $user3->id]));

        // Force user assignments update.
        $appraisal->check_assignment_changes();

        // For user1 the existing job assignment should have been linked.
        $jobassignments = $DB->get_records('job_assignment', ['userid' => $user1->id]);
        $this->assertCount(1, $jobassignments);
        $jobassignment = reset($jobassignments);
        $this->assertEquals($user1ja->id, $jobassignment->id);
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user1->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => $user1ja->id,
        ]));

        // For user2 nothing should have been linked because he had more than 1 job assignment.
        $this->assertCount(1, $DB->get_records('appraisal_user_assignment', ['userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user2->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));

        // For user3 an empty job assignment should have been created and linked.
        $jobassignments = $DB->get_records('job_assignment', ['userid' => $user3->id]);
        $this->assertCount(1, $jobassignments);
        $user3ja = reset($jobassignments);
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user3->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => $user3ja->id,
        ]));
    }

    public function test_store_job_assignments() {
        global $DB;

        // Make sure allowmultiplejobs is ON, so no auto-linking of job assignments should happen to begin with.
        set_config('totara_job_allowmultiplejobs', 1);

        $this->resetAfterTest();
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Set up appraisal.
        /** @var appraisal $appraisal */
        list($appraisal) = $this->prepare_appraisal_with_users();
        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);
        $this->assertEquals(appraisal::STATUS_DRAFT, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', ['appraisalid' => $appraisal->id]);
        $this->assertEquals(0, $count);

        // Create audience and add to appraisal - only for user1 & user2.
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();

        // Only user1 & user2 should be assigned to the appraisal.
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user1->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user2->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));
        $this->assertFalse($DB->record_exists('appraisal_user_assignment', ['userid' => $user3->id]));

        // No job assignments should exist for our users.
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user3->id]));

        // Nothing should happen when calling store_job_assignments() because setting totara_job_allowmultiplejobs is ON.
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $assign->store_job_assignments([$user1->id, $user2->id, $user3->id]);

        // No changes to data expected.
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user1->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user2->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));
        $this->assertFalse($DB->record_exists('appraisal_user_assignment', ['userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user3->id]));

        // Switch allowmultiplejobs to OFF, so auto-linking of job assignments should happen now.
        set_config('totara_job_allowmultiplejobs', 0);

        // User3 is not assigned to the appraisal and should be ignored without error.
        $assign->store_job_assignments([$user1->id, $user3->id]);

        // User1 should have an appraisal assignment with job assignment linked.
        $jobassignments = $DB->get_records('job_assignment', ['userid' => $user1->id]);
        $this->assertCount(1, $jobassignments);
        $user1ja = reset($jobassignments);
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user1->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => $user1ja->id,
        ]));

        // User2's & User3's data is unchanged.
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user2->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => null,
        ]));
        $this->assertFalse($DB->record_exists('appraisal_user_assignment', ['userid' => $user3->id]));

        // User2 should not have a job assignment added (was left out in parameter array) and neither user3 (is not assigned to the appraisal).
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user3->id]));

        // Call without parameter should also take care of user2 that we left out so far.
        $assign->store_job_assignments();
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user1->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => $user1ja->id,
        ]));
        $jobassignments = $DB->get_records('job_assignment', ['userid' => $user2->id]);
        $this->assertCount(1, $jobassignments);
        $user2ja = reset($jobassignments);
        $this->assertTrue($DB->record_exists('appraisal_user_assignment', [
            'userid' => $user2->id,
            'appraisalid' => $appraisal->id,
            'jobassignmentid' => $user2ja->id,
        ]));
        $this->assertFalse($DB->record_exists('appraisal_user_assignment', ['userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('job_assignment', ['userid' => $user3->id]));
    }

    public function test_active_appraisal_remove_group () {
        global $DB;

        // Set up active appraisal.
        $this->resetAfterTest();
        $this->setAdminUser();

        list($appraisal) = $this->prepare_appraisal_with_users();
        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        // Set up group.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Add group.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $this->assertEquals(appraisal::STATUS_DRAFT, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(0, $count);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(4, $count);

        // Remove one of the groups.
        $assignedgroups = $assign->get_current_assigned_groups();
        foreach ($assignedgroups as $assignedgroup) {
            if ($assignedgroup->sourceid == $cohort->id) {
                $assign->delete_assigned_group('cohort', $assignedgroup->assignedgroupid);
            }
        }

        $assign->delete_assigned_group('cohort', $cohort->id);

        // Check appraisal is active, and total user assignments is 4.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(4, $count);
        // All of them are still active.
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'status' => appraisal::STATUS_ACTIVE));
        $this->assertEquals(4, $count);

        // Force user assignments update.
        $appraisal->check_assignment_changes();

        // Check appraisal is still active, and total user assignments are still 4.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(4, $count);
        // Only 2 user assignments should be active.
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'status' => appraisal::STATUS_ACTIVE));
        $this->assertEquals(2, $count);
        // There should be 2 closed user assignments, the 2 from the removed group.
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'status' => appraisal::STATUS_CLOSED));
        $this->assertEquals(2, $count);
    }

    /**
     * Test activating an appraisal when an assigned user is missing required roles.
     *
     * User position assignment structure
     * $user1 ------| Manager   | 0
     *              | Teamlead  | 0
     *              | Appraiser | 0
     */
    public function test_activation_with_missing_roles() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));

        $user1 = $this->getDataGenerator()->create_user();

        list($appraisal) = $this->prepare_appraisal_with_users($def, array($user1));
        list($errors, $warnings) = $appraisal->validate();

        // There should be no errors or warnings before activation; these come
        // after a job assignment has been linked to the appraisal. Only then
        // will roles be assigned and missing ones flagged.
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);

        $this->assertEquals(appraisal::STATUS_DRAFT, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(0, $count);

        $appraisal->activate();

        // Check for missing job assignment warning.
        $warnings = $appraisal->validate_roles();
        $this->assertEquals(1, count($warnings));
        $this->assertContains('has not selected a job assignment yet', reset($warnings));

        $this->update_job_assignments($appraisal);

        $this->assertEquals(appraisal::STATUS_ACTIVE, $appraisal->status);
        $count = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(1, $count);

        // Now we check for missing role warnings!
        $warnings = $appraisal->validate_roles();
        $this->assertEquals(1, count($warnings));
        $this->assertContains('is missing their', reset($warnings));
    }

    /**
     * Test removing the position assignments of an assigned user
     * while they are assigned to an active appraisal.
     *
     * User position assignment structure
     * $manager ----| Manager   | $teamlead1
     *
     * $user1 ------| Manager   | $manager     -> 0
     *              | Teamlead  | $teamlead    -> 0
     *              | Appraiser | $appraiser   -> 0
     *
     * $user2 ------| Manager   | $manager
     *              | Teamlead  | $teamlead
     *              | Appraiser | $appraiser
     */
    public function test_active_appraisal_role_removal() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up appraisal.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);

        // Set up group.
        $teamlead = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teamleadja = \totara_job\job_assignment::create_default($teamlead->id);
        $managerja = \totara_job\job_assignment::create_default(
            $manager->id,
            [
                'managerjaid' => $teamleadja->id
            ]
        );

        $jobassignmentmanagers = [
            'managerjaid' => $managerja->id,
            'appraiserid' => $appraiser->id
        ];

        $user1ja = \totara_job\job_assignment::create_default($user1->id, $jobassignmentmanagers);
        $user2ja = \totara_job\job_assignment::create_default($user2->id, $jobassignmentmanagers);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Assign group and activate.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        // That should have created 2 user assignments.
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(2, count($userassignments));

        // And 4 role assignments per userassignment.
        foreach ($userassignments as $aua) {
            $countrole = $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $aua->id));
            $this->assertEquals(4, $countrole);
        }

        // Now Change user1s job assignment.
        $removedroles = [
            appraisal::ROLE_MANAGER => 'managerjaid',
            appraisal::ROLE_TEAM_LEAD => '',
            appraisal::ROLE_APPRAISER => 'appraiserid'
        ];

        $jobroleupdates = [];
        foreach ($removedroles as $role => $jobrolefield) {
            if (!empty($jobrolefield)) {
                $jobroleupdates[$jobrolefield] = null;
            }
        }

        // To forestall unnecessary computation, the appraisal looks up the last
        // modified time for a linked job assignment. If there was no change in
        // the last modified time => the job assignment has no change => no role
        // computation needs to be done. But timestamp precision is in seconds
        // and occasionally, PHPUnit runs so fast that the timestamp is updated
        // in the same second it was created. Thus, there is a sleep() here to
        // stop the test from failing further down.
        $this->waitForSecond();
        $user1ja->update($jobroleupdates);

        // User1 should now be missing roles except learner and team lead. Note
        // the actual roles assigned to the appraisal has not changed yet; this
        // is just a "prediction" of what will be missing when the cron task
        // executes.
        $missing = $assign->missing_role_assignments()->roles;
        $this->assertTrue(array_key_exists($user1->id, $missing));
        $this->assertEquals(count($removedroles), count($missing[$user1->id]));

        // Simulate a cron run.
        $appraisal->check_assignment_changes();

        // Now the appraisal assignments will really be changed for User1 and
        // the changed roles will reflect that.
        $changed = $assign->changed_role_assignments();
        $expectedchangecount = count($roles) - count($removedroles) > 0 ? 1 : 0;
        $this->assertEquals($expectedchangecount, count($changed));
        $this->assertTrue(array_key_exists($user1->id, $changed));
        $this->assertEquals(count($removedroles), count($changed[$user1->id]));

        // The role assignments for the removed roles will be not be physically
        // gone from the appraisal assignments; instead they will have a 0 user
        // id.
        $newRoles = [];
        foreach (array_keys($roles) as $role) {
            $user = null;

            switch ($role) {
                case appraisal::ROLE_LEARNER:
                    $user = $user1;
                    break;

                case appraisal::ROLE_MANAGER:
                    $user = $manager;
                    break;

                case appraisal::ROLE_TEAM_LEAD:
                    $user = $teamlead;
                    break;

                case appraisal::ROLE_APPRAISER:
                default:
                    $user = $appraiser;
            }

            $newRoles[$role] = array_key_exists($role, $removedroles)
                               ? 0
                               : $user->id;
        }

        $currentassignments = $appraisal->get_all_assignments($user1->id);
        $this->assertEquals(count($roles), count($currentassignments));
        foreach ($currentassignments as $assigned) {
            $role = $assigned->appraisalrole;
            $this->assertTrue(key_exists($role, $newRoles));
            $this->assertEquals($newRoles[$role], $assigned->userid);
        }

        // There should still be 2 user assignments.
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(2, count($userassignments));

        // And user2 should still have his current assignments.
        $currentassignments = $appraisal->get_all_assignments($user2->id);
        $this->assertEquals(4, count($currentassignments));
    }

    /**
     * Test changing the position assignments of an assigned user
     * while they are assigned to an active appraisal.
     *
     * User position assignment structure
     * $manager1 ---| Manager   | $teamlead1
     *
     * $manager2 ---| Manager   | $teamlead2
     *
     * $user1 ------| Manager   | $manager1     -> $manager2
     *              | Teamlead  | $teamlead1    -> $teamlead2
     *              | Appraiser | $appraiser1   -> $appraiser2
     *
     * $user2 ------| Manager   | $manager1
     *              | Teamlead  | $teamlead1
     *              | Appraiser | $appraiser1
     */
    public function test_active_appraisal_role_reassignment() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up appraisal.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);

        // Set up group.
        $teamlead1 = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teamlead2 = $this->getDataGenerator()->create_user();
        $manager2 = $this->getDataGenerator()->create_user();
        $appraiser2 = $this->getDataGenerator()->create_user();

        $teamlead1ja = \totara_job\job_assignment::create_default($teamlead1->id);
        $teamlead2ja = \totara_job\job_assignment::create_default($teamlead2->id);

        $manager1ja = \totara_job\job_assignment::create_default(
            $manager1->id,
            [
                'managerjaid' => $teamlead1ja->id
            ]
        );
        $manager2ja = \totara_job\job_assignment::create_default(
            $manager2->id,
            [
                'managerjaid' => $teamlead2ja->id
            ]
        );

        $user1ja = \totara_job\job_assignment::create_default(
            $user1->id,
            [
                'managerjaid' => $manager1ja->id,
                'appraiserid' => $appraiser->id
            ]
        );

        $user2ja = \totara_job\job_assignment::create_default(
            $user2->id,
            [
                'managerjaid' => $manager2ja->id,
                'appraiserid' => $appraiser->id

            ]
        );

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Assign group and activate.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        // That should have created 2 user assignments.
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(2, count($userassignments));

        // And 4 role assignments per userassignment.
        foreach ($userassignments as $aua) {
            $countrole = $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $aua->id));
            $this->assertEquals(4, $countrole);
        }

        // Now Change user1s job assignment.
        //
        // To forestall unnecessary computation, the appraisal looks up the last
        // modified time for a linked job assignment. If there was no change in
        // the last modified time => the job assignment has no change => no role
        // computation needs to be done. But timestamp precision is in seconds
        // and occasionally, PHPUnit runs so fast that the timestamp is updated
        // in the same second it was created. Thus, there is a sleep() here to
        // stop the test from failing further down.
        $this->waitForSecond();
        $user1ja->update(array('managerjaid' => $manager2ja->id, 'appraiserid' => $appraiser2->id));

        // There should be no missing roles.
        $missing = $assign->missing_role_assignments()->roles;
        $this->assertEquals(0, count($missing));

        // Simulate a cron run.
        $appraisal->check_assignment_changes();

        // Now the appraisal assignments will really be changed for User1 and
        // the changed roles will reflect that.
        $changed = $assign->changed_role_assignments();
        $this->assertEquals(1, count($changed));
        $this->assertTrue(array_key_exists($user1->id, $changed));
        $this->assertEquals(3, count($changed[$user1->id]));

        $currentassignments = $appraisal->get_all_assignments($user1->id);
        $this->assertEquals(4, count($currentassignments));
        foreach ($currentassignments as $assigned) {
            $role = $assigned->appraisalrole;

            $user = null;
            switch ($role) {
                case appraisal::ROLE_LEARNER:
                    $user = $user1;
                    break;

                case appraisal::ROLE_MANAGER:
                    $user = $manager2;
                    break;

                case appraisal::ROLE_TEAM_LEAD:
                    $user = $teamlead2;
                    break;

                case appraisal::ROLE_APPRAISER:
                default:
                    $user = $appraiser2;
            }

            $this->assertEquals($user->id, $assigned->userid);
        }

        // There should still be 2 user assignments
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(2, count($userassignments));

        // And user2 should still have his current assignments.
        $currentassignments = $appraisal->get_all_assignments($user2->id);
        $this->assertEquals(4, count($currentassignments));
    }

    /**
     * Test deleting an assigned user while they are assigned to an active appraisal
     *
     * User position assignment structure
     * $manager ----| Manager   | $teamlead
     *
     * $user1   ----| Manager   | $manager      -> null
     *              | Teamlead  | $teamlead     -> null
     *              | Appraiser | $appraiser    -> null
     *
     * $user2   ----| Manager   | $user1        -> 0
     *              | Teamlead  | $manager      -> 0
     *              | Appraiser | $appraiser
     */
    public function test_active_appraisal_user_deletion() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up appraisal.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);
        $answertable = 'appraisal_quest_data_'.$appraisal->id;

        // Create users.
        $teamlead = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teamleadja = \totara_job\job_assignment::create_default($teamlead->id);
        $managerja = \totara_job\job_assignment::create_default(
            $manager->id,
            [
                'managerjaid' => $teamleadja->id
            ]
        );

        $user1ja = \totara_job\job_assignment::create_default(
            $user1->id,
            [
                'managerjaid' => $managerja->id,
                'appraiserid' => $appraiser->id
            ]
        );
        $user2ja = \totara_job\job_assignment::create_default(
            $user2->id,
            [
                'managerjaid' => $user1ja->id,
                'appraiserid' => $appraiser->id
            ]
        );

        // Create group and assign users.
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Assign group and activate.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $ua1 = $DB->get_record('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'userid' => $user1->id));
        $ua2 = $DB->get_record('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'userid' => $user2->id));

        // This should have created 2 user assignments and 8 role assignments.
        $this->assertEquals(2, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(8, $DB->count_records('appraisal_role_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_quest_data_'.$appraisal->id));

        // Now create some answer data for user1.
        $user1roles = array();
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $user1->id,
            appraisal::ROLE_LEARNER);
        $user1roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $manager->id,
                appraisal::ROLE_MANAGER);
        $user1roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $teamlead->id,
                appraisal::ROLE_TEAM_LEAD);
        $user1roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $appraiser->id,
                appraisal::ROLE_APPRAISER);
        $user1roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');

        // Now create some answer data for user2.
        $user2roles = array();
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user2->id, $user2->id,
                appraisal::ROLE_LEARNER);
        $user2roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user2->id, $user1->id,
                appraisal::ROLE_MANAGER);
        $user2roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user2->id, $manager->id,
                appraisal::ROLE_TEAM_LEAD);
        $user2roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user2->id, $appraiser->id,
                appraisal::ROLE_APPRAISER);
        $user2roles[] = $roleassignment->id;
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');

        list($u1insql, $u1param) = $DB->get_in_or_equal($user1roles);
        $u1sql = "SELECT COUNT(*) FROM {{$answertable}} where appraisalroleassignmentid " . $u1insql;
        list($u2insql, $u2param) = $DB->get_in_or_equal($user2roles);
        $u2sql = "SELECT COUNT(*) FROM {{$answertable}} where appraisalroleassignmentid " . $u2insql;

        // There should now be 8 answer records, 4 per user_assignment.
        $this->assertEquals(8, $DB->count_records($answertable));
        $this->assertEquals(4, $DB->count_records_sql($u1sql, $u1param));
        $this->assertEquals(4, $DB->count_records_sql($u2sql, $u2param));

        // First half of the delete, remove user_assignment records.
        appraisal::delete_learner_assignments($user1->id);

        // This should have deleted user1's user assignment and associated role assignments.
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_user_assignment', array('userid' => $user1->id)));
        $this->assertEquals(4, $DB->count_records('appraisal_role_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $ua1->id)));
        $this->assertEquals(4, $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $ua2->id)));
        $this->assertEquals(4, $DB->count_records($answertable));
        $this->assertEquals(0, $DB->count_records_sql($u1sql, $u1param));
        $this->assertEquals(4, $DB->count_records_sql($u2sql, $u2param));

        // Second half of the delete, unassign role_assignment records.
        appraisal::unassign_user_roles($user1->id);

        // This should have left the role_assignments and associated data alone but set the userid to 0.
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment', array('userid' => $user2->id)));
        $this->assertEquals(4, $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $ua2->id)));
        $this->assertEquals(1, $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $ua2->id, 'userid' => 0)));
        $this->assertEquals(4, $DB->count_records($answertable));
        $this->assertEquals(0, $DB->count_records_sql($u1sql, $u1param));
        $this->assertEquals(4, $DB->count_records_sql($u2sql, $u2param));
    }

    public function test_single_stage_all_role_removal_after_learner_completion() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up appraisal.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);

        // Set up group.
        $manager = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);

        $jobassignmentmanagers = [
            'managerjaid' => $managerja->id
        ];

        $user1ja = \totara_job\job_assignment::create_default($user1->id, $jobassignmentmanagers);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);

        // Assign group and activate.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        // That should have created a user assignment.
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(1, count($userassignments));

        // And 4 role assignments per userassignment.
        foreach ($userassignments as $aua) {
            $countrole = $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $aua->id));
            $this->assertEquals(4, $countrole);
        }

        // Now answer the question
        $ra1 = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $user1->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $ra1, '', 'completestage');
        $map = $this->map($appraisal);

        // user1 completed the stage, but other roles still need to complete
        $this->assertEquals(1, $DB->count_records('appraisal_stage_data',
            array('appraisalroleassignmentid' => $ra1->id, 'appraisalstageid' => $map['stages']['Stage'])));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $DB->get_field('appraisal_user_assignment', 'status',
            array('appraisalid' => $appraisal->id, 'userid' => $user1->id)));

        // Now Change user1s job assignment.
        $removedroles = [
            appraisal::ROLE_MANAGER => 'managerjaid'
        ];

        $jobroleupdates = [];
        foreach ($removedroles as $role => $jobrolefield) {
            if (!empty($jobrolefield)) {
                $jobroleupdates[$jobrolefield] = null;
            }
        }

        // To forestall unnecessary computation, the appraisal looks up the last
        // modified time for a linked job assignment. If there was no change in
        // the last modified time => the job assignment has no change => no role
        // computation needs to be done. But timestamp precision is in seconds
        // and occasionally, PHPUnit runs so fast that the timestamp is updated
        // in the same second it was created. Thus, there is a sleep() here to
        // stop the test from failing further down.
        $this->waitForSecond();
        $user1ja->update($jobroleupdates);

        // Simulate a cron run.
        $appraisal->check_assignment_changes();

        // user_assignment should now be shown as completed as no other roles currently exist that can complete the stage
        $this->assertEquals(appraisal::STATUS_COMPLETED, $DB->get_field('appraisal_user_assignment', 'status',
            array('appraisalid' => $appraisal->id, 'userid' => $user1->id)));
        // Check the appraisal should still be active.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $DB->get_field('appraisal', 'status', array('id' => $appraisal->id)));
    }

    /**
     * Test removing some position assignments of an assigned user
     * after it completed the stage but before the user's position assignments
     * completed it
     *
     * User position assignment structure
     * $user1   ----| Manager   | $manager      -> 0
     *              | Teamlead  | $teamlead     -> 0
     *              | Appraiser | $appraiser
     */
    public function test_single_stage_some_role_removal_after_learner_completion() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up appraisal.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);

        // Set up group.
        $teamlead1 = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();

        $teamlead1ja = \totara_job\job_assignment::create_default($teamlead1->id);

        $manager1ja = \totara_job\job_assignment::create_default(
            $manager1->id,
            [
                'managerjaid' => $teamlead1ja->id
            ]
        );

        $user1ja = \totara_job\job_assignment::create_default(
            $user1->id,
            [
                'managerjaid' => $manager1ja->id,
                'appraiserid' => $appraiser->id
            ]
        );

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);

        // Assign group and activate.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        // That should have created a user assignment
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(1, count($userassignments));

        // And 4 role assignments per userassignment.
        foreach ($userassignments as $aua) {
            $countrole = $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $aua->id));
            $this->assertEquals(4, $countrole);
        }

        // Now answer the question
        $ra1 = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $user1->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $ra1, '', 'completestage');
        $map = $this->map($appraisal);

        // user1 completed the stage, but other roles still need to complete
        $this->assertEquals(1, $DB->count_records('appraisal_stage_data',
            array('appraisalroleassignmentid' => $ra1->id, 'appraisalstageid' => $map['stages']['Stage'])));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $DB->get_field('appraisal_user_assignment', 'status',
            array('appraisalid' => $appraisal->id, 'userid' => $user1->id)));

        // Now Change user1s job assignment.
        $removedroles = [
            appraisal::ROLE_MANAGER => 'managerjaid',
            appraisal::ROLE_TEAM_LEAD => ''
        ];

        $jobroleupdates = [];
        foreach ($removedroles as $role => $jobrolefield) {
            if (!empty($jobrolefield)) {
                $jobroleupdates[$jobrolefield] = null;
            }
        }

        // To forestall unnecessary computation, the appraisal looks up the last
        // modified time for a linked job assignment. If there was no change in
        // the last modified time => the job assignment has no change => no role
        // computation needs to be done. But timestamp precision is in seconds
        // and occasionally, PHPUnit runs so fast that the timestamp is updated
        // in the same second it was created. Thus, there is a sleep() here to
        // stop the test from failing further down.
        $this->waitForSecond();
        $user1ja->update($jobroleupdates);

        // Simulate a cron run.
        $appraisal->check_assignment_changes();

        // user_assignment should still not be completed as appraiser still needs to complete the stage
        $ua1 = $DB->get_record('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'userid' => $user1->id));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $ua1->status);
        $this->assertEquals($map['stages']['Stage'], $ua1->activestageid);
    }

    /**
     * Test removing the position assignments of an assigned user
     * after it completed first stage but before the user's position assignments
     * completed it
     *
     * User position assignment structure
     * $user1 ------| Manager   | $manager     -> 0
     */
    public function test_multi_stage_all_role_removal_after_learner_completion() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up appraisal.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage1', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page1', 'questions' => array(
                    array('name' => 'Text1', 'type' => 'text', 'roles' => $roles)
                ))
            )),
            array('name' => 'Stage2', 'timedue' => time() + 2 * 86400, 'pages' => array(
                array('name' => 'Page2', 'questions' => array(
                    array('name' => 'Text2', 'type' => 'text', 'roles' => $roles)
                ))
            )),
            array('name' => 'Stage3', 'timedue' => time() + 3 * 86400, 'pages' => array(
                array('name' => 'Page3', 'questions' => array(
                    array('name' => 'Text3', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);

        // Set up group.
        $manager = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);

        $jobassignmentmanagers = [
            'managerjaid' => $managerja->id
        ];

        $user1ja = \totara_job\job_assignment::create_default($user1->id, $jobassignmentmanagers);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);

        // Assign group and activate.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        // That should have created a user assignment.
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(1, count($userassignments));

        // And 4 role assignments per userassignment.
        foreach ($userassignments as $aua) {
            $countrole = $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $aua->id));
            $this->assertEquals(4, $countrole);
        }

        // Now answer the first question in the first stage
        $ra1 = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $user1->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $ra1, '', 'completestage');
        $map = $this->map($appraisal);

        // user1 completed the first stage, but other roles still need to complete
        $this->assertEquals(1, $DB->count_records('appraisal_stage_data',
            array('appraisalroleassignmentid' => $ra1->id, 'appraisalstageid' => $map['stages']['Stage1'])));
        $this->assertEquals(0, $DB->count_records('appraisal_stage_data',
            array('appraisalroleassignmentid' => $ra1->id, 'appraisalstageid' => $map['stages']['Stage2'])));
        $ua1 = $DB->get_record('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'userid' => $user1->id));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $ua1->status);
        $this->assertEquals($map['stages']['Stage1'], $ua1->activestageid);

        // Now Change user1s job assignment.
        $removedroles = [
            appraisal::ROLE_MANAGER => 'managerjaid'
        ];

        $jobroleupdates = [];
        foreach ($removedroles as $role => $jobrolefield) {
            if (!empty($jobrolefield)) {
                $jobroleupdates[$jobrolefield] = null;
            }
        }

        // To forestall unnecessary computation, the appraisal looks up the last
        // modified time for a linked job assignment. If there was no change in
        // the last modified time => the job assignment has no change => no role
        // computation needs to be done. But timestamp precision is in seconds
        // and occasionally, PHPUnit runs so fast that the timestamp is updated
        // in the same second it was created. Thus, there is a sleep() here to
        // stop the test from failing further down.
        $this->waitForSecond();
        $user1ja->update($jobroleupdates);

        // Simulate a cron run.
        $appraisal->check_assignment_changes();

        // user_assignment should still be active, but activepage should have been moved to the next page
        $ua1 = $DB->get_record('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'userid' => $user1->id));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $ua1->status);
        $this->assertEquals($map['stages']['Stage2'], $ua1->activestageid);
    }

    /**
     * Test removing some position assignments of an assigned user
     * after it completed the first stage but before the user's position assignments
     * completed it
     *
     * User position assignment structure
     * $user1   ----| Manager   | $manager      -> 0
     *              | Teamlead  | $teamlead     -> 0
     *              | Appraiser | $appraiser
     */
    public function test_multi_stage_some_role_removal_after_learner_completion() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Set up appraisal.
        $roles = array();
        $roles[appraisal::ROLE_LEARNER] = 6;
        $roles[appraisal::ROLE_MANAGER] = 6;
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $roles[appraisal::ROLE_APPRAISER] = 6;

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage1', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page1', 'questions' => array(
                    array('name' => 'Text1', 'type' => 'text', 'roles' => $roles)
                ))
            )),
            array('name' => 'Stage2', 'timedue' => time() + 2 * 86400, 'pages' => array(
                array('name' => 'Page2', 'questions' => array(
                    array('name' => 'Text2', 'type' => 'text', 'roles' => $roles)
                ))
            )),
            array('name' => 'Stage3', 'timedue' => time() + 3 * 86400, 'pages' => array(
                array('name' => 'Page3', 'questions' => array(
                    array('name' => 'Text3', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);

        // Set up group.
        $teamlead1 = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();

        $teamlead1ja = \totara_job\job_assignment::create_default($teamlead1->id);

        $manager1ja = \totara_job\job_assignment::create_default(
            $manager1->id,
            [
                'managerjaid' => $teamlead1ja->id
            ]
        );

        $user1ja = \totara_job\job_assignment::create_default(
            $user1->id,
            [
                'managerjaid' => $manager1ja->id,
                'appraiserid' => $appraiser->id
            ]
        );

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);

        // Assign group and activate.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($cohort->id));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector($urlparams);

        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        // That should have created a user assignment
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));
        $this->assertEquals(1, count($userassignments));

        // And 4 role assignments per userassignment.
        foreach ($userassignments as $aua) {
            $countrole = $DB->count_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $aua->id));
            $this->assertEquals(4, $countrole);
        }

        // Now answer the question
        $ra1 = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $user1->id,
                appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $ra1, '', 'completestage');
        $map = $this->map($appraisal);

        // user1 completed the stage, but other roles still need to complete
        $this->assertEquals(1, $DB->count_records('appraisal_stage_data',
            array('appraisalroleassignmentid' => $ra1->id, 'appraisalstageid' => $map['stages']['Stage1'])));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $DB->get_field('appraisal_user_assignment', 'status',
            array('appraisalid' => $appraisal->id, 'userid' => $user1->id)));

        // Now Change user1s job assignment.
        $removedroles = [
            appraisal::ROLE_MANAGER => 'managerjaid',
            appraisal::ROLE_TEAM_LEAD => ''
        ];

        $jobroleupdates = [];
        foreach ($removedroles as $role => $jobrolefield) {
            if (!empty($jobrolefield)) {
                $jobroleupdates[$jobrolefield] = null;
            }
        }

        // To forestall unnecessary computation, the appraisal looks up the last
        // modified time for a linked job assignment. If there was no change in
        // the last modified time => the job assignment has no change => no role
        // computation needs to be done. But timestamp precision is in seconds
        // and occasionally, PHPUnit runs so fast that the timestamp is updated
        // in the same second it was created. Thus, there is a sleep() here to
        // stop the test from failing further down.
        $this->waitForSecond();
        $user1ja->update($jobroleupdates);

        // Simulate a cron run.
        $appraisal->check_assignment_changes();

        // user_assignment should still not be completed as appraiser still needs to complete the stage
        // activestage should still be on Stage1
        $ua1 = $DB->get_record('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'userid' => $user1->id));
        $this->assertEquals(appraisal::STATUS_ACTIVE, $ua1->status);
        $this->assertEquals($map['stages']['Stage1'], $ua1->activestageid);
    }

    public function test_cleanup_task() {
        global $DB;
        $this->resetAfterTest();

        // Create appraisal and activate it.
        list($appraisal, $users) = $this->prepare_appraisal_with_users();
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        // Get user assignments.
        $userassignments = $DB->get_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id));

        $sql = "SELECT ara.id
                  FROM {appraisal_role_assignment} ara
            INNER JOIN {appraisal_user_assignment} aua
                    ON aua.id = ara.appraisaluserassignmentid
                 WHERE aua.appraisalid = ? ";

        // Let's mark users as deleted.
        $deletedusers = array();
        foreach ($userassignments as $userassignment) {
            if ($userassignment->userid % 2) {
                $DB->set_field('user', 'deleted', 1, array('id' => $userassignment->userid));
                $deletedusers[$userassignment->userid] = $userassignment->id;
            }
        }

        // Check we have the users assigned to the appraisal and the users has records in the appraisal_role_assignment.
        // Note appraisees now always 4 records for the role assignment; some may be for user id 0 - which indicates the
        // assignment was deleted or doesn't exist.
        $this->assertEquals(count($users), count($userassignments));
        $this->assertEquals(count($users) * 4, count($DB->get_records_sql($sql, array($appraisal->id))));

        // Call the clean up task.
        $task = new \totara_appraisal\task\cleanup_task();
        $task->execute();

        // Check the clean up task has done its work and deleted users have been removed from the assignments table.
        $currentusers = count($users) - count($deletedusers);
        $this->assertEquals($currentusers, $DB->count_records('appraisal_user_assignment', array('appraisalid' => $appraisal->id)));
        $this->assertEquals($currentusers * 4, count($DB->get_records_sql($sql, array($appraisal->id))));

        foreach ($deletedusers as $userid => $value) {
            $params = array('userid' => $userid, 'appraisalid' => $appraisal->id);
            $paramsrole = array('appraisaluserassignmentid' => $value);
            $this->assertFalse($DB->record_exists('appraisal_user_assignment', $params));
            $this->assertFalse($DB->record_exists('appraisal_role_assignment', $paramsrole));
        }
    }

    public function test_get_mandatory_completion() {
        // Create appraisal and activate it.
        $user1 = $this->getDataGenerator()->create_user();

        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage1', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => appraisal::ACCESS_MUSTANSWER,
                        appraisal::ROLE_MANAGER => appraisal::ACCESS_MUSTANSWER
                    ))
                ))
            )),
            array('name' => 'Stage2', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => array(
                        appraisal::ROLE_LEARNER => appraisal::ACCESS_CANANSWER,
                        appraisal::ROLE_MANAGER => appraisal::ACCESS_CANANSWER
                    ))
                ))
            ))
        ));

        /** @var appraisal $appraisal */
        list($appraisal, $users) = $this->prepare_appraisal_with_users($def, array($user1));
        $appraisal->activate();
        $this->update_job_assignments($appraisal);

        $map = $this->map($appraisal);

        $stage = new appraisal_stage($map['stages']['Stage1']);

        // Enable dynamic appraisals, disable auto-progress. Mandatory roles should be all roles involved
        // in the stage.
        set_config('dynamicappraisals', true);
        set_config('dynamicappraisalsautoprogress', false);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER), array_keys($mandatory_roles));

        // Enable dynamic appraisals, enable auto-progress. Unfilled roles are not mandatory.
        set_config('dynamicappraisals', true);
        set_config('dynamicappraisalsautoprogress', true);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER), array_keys($mandatory_roles));

        // Disable dynamic appraisals, disbale auto-progress. Unfilled roles are not mandatory.
        set_config('dynamicappraisals', false);
        set_config('dynamicappraisalsautoprogress', false);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER), array_keys($mandatory_roles));

        // Disable dynamic appraisals, enable auto-progress. Unfilled roles are not mandatory.
        set_config('dynamicappraisals', false);
        set_config('dynamicappraisalsautoprogress', true);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER), array_keys($mandatory_roles));

        // Repeat with CANANSWER - no difference.
        $stage = new appraisal_stage($map['stages']['Stage2']);

        // Enable dynamic appraisals, disable auto-progress. Mandatory roles should be all roles involved
        // in the stage.
        set_config('dynamicappraisals', true);
        set_config('dynamicappraisalsautoprogress', false);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER), array_keys($mandatory_roles));

        // Enable dynamic appraisals, enable auto-progress. Unfilled roles are not mandatory.
        set_config('dynamicappraisals', true);
        set_config('dynamicappraisalsautoprogress', true);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER), array_keys($mandatory_roles));

        // Disable dynamic appraisals, disbale auto-progress. Unfilled roles are not mandatory.
        set_config('dynamicappraisals', false);
        set_config('dynamicappraisalsautoprogress', false);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER), array_keys($mandatory_roles));

        // Disable dynamic appraisals, enable auto-progress. Unfilled roles are not mandatory.
        set_config('dynamicappraisals', false);
        set_config('dynamicappraisalsautoprogress', true);

        $mandatory_roles = $stage->get_mandatory_completion($user1->id);
        $this->assertEquals(array(appraisal::ROLE_LEARNER), array_keys($mandatory_roles));
    }
}
