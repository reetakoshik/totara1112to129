<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

use totara_appraisal\userdata\participation;
use totara_job\job_assignment;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

/**
 * @group totara_userdata
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_appraisal_userdata_participation_testcase totara/appraisal/tests/userdata_participation_test.php
 */
class totara_appraisal_userdata_participation_testcase extends appraisal_testcase {

    /**
     * Set up the test data.
     */
    private function setup_data() {
        $data = new class() {
            /** @var stdClass */
            public $user1, $user2, $manager1, $manager2;

            /** @var array */
            public $appraisals = [];

            /** @var target_user */
            public $targetuser;

            /** @var job_assignment */
            public $userja1, $userja2, $managerja1, $managerja2;
        };

        $this->resetAfterTest();

        // Set up users.
        $data->manager1 = $this->getDataGenerator()->create_user(); // This is the target user.
        $data->manager2 = $this->getDataGenerator()->create_user();
        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        $data->managerja1 = job_assignment::create_default($data->manager1->id);
        $data->managerja2 = job_assignment::create_default($data->manager2->id);
        $data->userja1 = job_assignment::create_default($data->user1->id, ['managerjaid' => $data->managerja1->id]);
        $data->userja2 = job_assignment::create_default($data->user2->id, ['managerjaid' => $data->managerja2->id]);

        // Set up the target user.
        $data->targetuser = new target_user($data->manager1);

        // Give them relevant data.

        $def = array('name' => 'Appraisal 1', 'stages' => array(
            array('name' => 'Stage 1', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page 1', 'questions' => array(
                    array('name' => 'Question text 1', 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER, \appraisal::ROLE_MANAGER => \appraisal::ACCESS_CANANSWER),
                    ),
                    array('name' => 'Question text 2', 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER, \appraisal::ROLE_MANAGER => \appraisal::ACCESS_CANANSWER),
                    ),
                )),
            )),
        ));

        /* @var $appraisal1 \appraisal */
        list($appraisal1) = $this->prepare_appraisal_with_users($def, array($data->user1, $data->user2));
        $appraisal1->validate();
        $appraisal1->activate();
        /* @var $appraisal2 \appraisal */
        list($appraisal2) = $this->prepare_appraisal_with_users($def, array($data->user1, $data->user2));
        $appraisal2->validate();
        $appraisal2->activate();
        /* @var $appraisal3 \appraisal */
        list($appraisal3) = $this->prepare_appraisal_with_users($def, array($data->manager1));
        $appraisal3->validate();
        $appraisal3->activate();

        // Trigger job assignment allocation.
        $appraisal_user_assignment = appraisal_user_assignment::get_user($appraisal1->id, $data->user1->id);
        $appraisal_user_assignment->with_job_assignment($data->userja1->id);
        $appraisal_user_assignment = appraisal_user_assignment::get_user($appraisal1->id, $data->user2->id);
        $appraisal_user_assignment->with_job_assignment($data->userja2->id);
        $appraisal_user_assignment = appraisal_user_assignment::get_user($appraisal2->id, $data->user1->id);
        $appraisal_user_assignment->with_job_assignment($data->userja1->id);
        $appraisal_user_assignment = appraisal_user_assignment::get_user($appraisal2->id, $data->user2->id);
        $appraisal_user_assignment->with_job_assignment($data->userja2->id);
        $appraisal_user_assignment = appraisal_user_assignment::get_user($appraisal3->id, $data->manager1->id);
        $appraisal_user_assignment->with_job_assignment($data->managerja1->id);

        return $data;
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(array(CONTEXT_SYSTEM), participation::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(participation::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(participation::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(participation::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(participation::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(participation::is_countable());
    }

    /**
     * Test if data is correctly purged.
     */
    public function test_purge() {
        global $DB;

        $data = $this->setup_data();

        $expectedroles = $DB->get_records('appraisal_role_assignment', [], 'id');
        $this->assertEquals(20, count($expectedroles));

        // Purge target user.
        foreach ($expectedroles as $key => $expectedrole) {
            if ($expectedrole->userid == $data->targetuser->id && $expectedrole->appraisalrole != \appraisal::ROLE_LEARNER) {
                $expectedrole->userid = 0;
            }
        }
        $result = participation::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $actualroles = $DB->get_records('appraisal_role_assignment', [], 'id');
        $this->assertEquals($expectedroles, $actualroles);
    }

    /**
     * Test if data is correctly counted.
     */
    public function test_count() {
        $data = $this->setup_data();

        // Do the count.
        $result = participation::execute_count($data->targetuser, context_system::instance());
        $this->assertEquals(2, $result);

        participation::execute_purge(new target_user($data->targetuser), context_system::instance());

        // Recount.
        $result = participation::execute_count($data->targetuser, context_system::instance());
        $this->assertEquals(0, $result);
    }

    /**
     * test if data is correctly counted
     */
    public function test_export() {
        global $DB;

        $data = $this->setup_data();

        $expected = new export();

        $select = "userid = :userid AND appraisalrole <> :rolelearner";
        $params = ['userid' => $data->manager1->id, 'rolelearner' => \appraisal::ROLE_LEARNER];
        $expected->data = $DB->get_records_select('appraisal_role_assignment', $select, $params);

        $actual = participation::execute_export($data->targetuser, context_system::instance());

        $this->assertEquals($expected, $actual);
    }
}