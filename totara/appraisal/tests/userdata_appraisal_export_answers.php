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

use \totara_appraisal\userdata\appraisal;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

abstract class totara_appraisal_userdata_appraisal_export_answers_testcase extends appraisal_testcase {

    abstract protected function classtotest();

    abstract protected function includehiddenanswers();

    /**
     * Set up the test data.
     */
    private function setup_data() {
        global $DB;

        $data = new class() {
            /** @var stdClass */
            public $user1, $user2, $manager;

            /** @var \totara_job\job_assignment */
            public $user1ja;

            /** @var \appraisal */
            public $appraisal;

            /** @var target_user */
            public $targetuser;
        };

        $this->resetAfterTest();

        // Set up users.
        $data->manager = $this->getDataGenerator()->create_user();
        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($data->manager->id);
        $data->user1ja = \totara_job\job_assignment::create_default($data->user1->id, array('managerjaid' => $managerja->id));

        // Give them relevant data.
        $def1 = array('name' => 'Appraisal 1', 'stages' => array(
            array('name' => 'Stage 1', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page 1', 'questions' => array(
                    array('name' => 'Question text 1 A', 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER, \appraisal::ROLE_MANAGER => \appraisal::ACCESS_CANANSWER),
                    ),
                    array('name' => 'Question text 1 B', 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER | \appraisal::ACCESS_CANVIEWOTHER, \appraisal::ROLE_MANAGER => \appraisal::ACCESS_CANANSWER),
                    ),
                )),
            )),
        ));

        $def2 = array('name' => 'Appraisal 2', 'stages' => array(
            array('name' => 'Stage 2', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page 2', 'questions' => array(
                    array('name' => 'Question text 2', 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER)),
                )),
            )),
            array('name' => 'Stage 3', 'timedue' => time() + 86401, 'pages' => array(
                array('name' => 'Page 3', 'sortorder' => 0, 'questions' => array(
                    array('name' => 'Question text 3', 'sortorder' => 0, 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER)),
                    array('name' => 'Question text 4', 'sortorder' => 1, 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER)),
                )),
                array('name' => 'Page 4', 'sortorder' => 1, 'questions' => array(
                    array('name' => 'Question text 5', 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER)),
                )),
            )),
            array('name' => 'Stage 4', 'timedue' => time() + 86402, 'pages' => array(
                array('name' => 'Page 5', 'questions' => array(
                    array('name' => 'Question text 6', 'type' => 'text', 'roles' =>
                        array(\appraisal::ROLE_LEARNER => \appraisal::ACCESS_CANANSWER)),
                )),
            )),
        ));

        /** @var \appraisal $appraisal1 */
        list($appraisal1) = $this->prepare_appraisal_with_users($def1, array($data->user1, $data->user2));
        $appraisal1->validate();
        $appraisal1->activate();

        // Add answers for each role (appraisal1 only).
        $appraisal_user_assignment = appraisal_user_assignment::get_user($appraisal1->id, $data->user1->id);
        $appraisal_user_assignment->with_job_assignment($data->user1ja->id);
        $user1roleid1 = $DB->get_field('appraisal_role_assignment', 'id',
            ['appraisalrole' => \appraisal::ROLE_LEARNER, 'userid' => $data->user1->id], MUST_EXIST);
        $user2roleid1 = $DB->get_field('appraisal_role_assignment', 'id',
            ['appraisalrole' => \appraisal::ROLE_LEARNER, 'userid' => $data->user2->id], MUST_EXIST);
        $managerroleid1 = $DB->get_field('appraisal_role_assignment', 'id',
            ['appraisalrole' => \appraisal::ROLE_MANAGER, 'userid' => $data->manager->id], MUST_EXIST);

        /** @var \appraisal $appraisal2 */
        list($appraisal2) = $this->prepare_appraisal_with_users($def2, array($data->user1, $data->user2));
        $appraisal2->validate();
        $appraisal2->activate();

        $sql = "SELECT quest.id
                  FROM {appraisal_quest_field} quest
                  JOIN {appraisal_stage_page} page ON quest.appraisalstagepageid = page.id
                  JOIN {appraisal_stage} stage ON page.appraisalstageid = stage.id
                 WHERE stage.appraisalid = :appraisalid AND quest.sortorder = 0";
        $questionaid = $DB->get_field_sql($sql, ['appraisalid' => $appraisal1->id]);

        $sql = "SELECT quest.id
                  FROM {appraisal_quest_field} quest
                  JOIN {appraisal_stage_page} page ON quest.appraisalstagepageid = page.id
                  JOIN {appraisal_stage} stage ON page.appraisalstageid = stage.id
                 WHERE stage.appraisalid = :appraisalid AND quest.sortorder = 1";
        $questionbid = $DB->get_field_sql($sql, ['appraisalid' => $appraisal1->id]);

        $record = new stdClass();
        $record->appraisalroleassignmentid = $user1roleid1;
        $record->{'data_' . $questionaid} = 'Learner answer A';
        $record->{'data_' . $questionbid} = 'Learner answer B';
        $DB->insert_record('appraisal_quest_data_' . $appraisal1->id, $record);

        $record = new stdClass();
        $record->appraisalroleassignmentid = $managerroleid1;
        $record->{'data_' . $questionaid} = 'Manager answer A';
        $record->{'data_' . $questionbid} = 'Manager answer B';
        $DB->insert_record('appraisal_quest_data_' . $appraisal1->id, $record);

        // Control data.
        $record = new stdClass();
        $record->appraisalroleassignmentid = $user2roleid1;
        $record->{'data_' . $questionaid} = 'Learner answer A';
        $record->{'data_' . $questionbid} = 'Learner answer B';
        $DB->insert_record('appraisal_quest_data_' . $appraisal1->id, $record);

        // Set up the target user.
        $data->targetuser = new target_user($data->user1);

        return $data;
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        /** @var \totara_appraisal\userdata\appraisal_export $testclass */
        $testclass = $this->classtotest();
        $this->assertEquals(array(CONTEXT_SYSTEM), $testclass::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        /** @var \totara_appraisal\userdata\appraisal_export $testclass */
        $testclass = $this->classtotest();
        $this->assertFalse($testclass::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertFalse($testclass::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertFalse($testclass::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        /** @var \totara_appraisal\userdata\appraisal_export $testclass */
        $testclass = $this->classtotest();
        $this->assertTrue($testclass::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        /** @var \totara_appraisal\userdata\appraisal_export $testclass */
        $testclass = $this->classtotest();
        $this->assertTrue($testclass::is_countable());
    }

    public function test_export() {
        $data = $this->setup_data();

        if ($this->includehiddenanswers()) {
            $firstquestionanswers =
                [
                'role 1' => (object)['data' => 'Learner answer A'],
                // The manager's answer is not visible to the learner, but is included anyway.
                'role 2' => (object)['data' => 'Manager answer A'],
            ];
        } else {
            $firstquestionanswers =
                [
                    'role 1' => (object)['data' => 'Learner answer A'],
                    // The manager's answer is not visible to the learner.
                ];
        }

        $expected = new \totara_userdata\userdata\export();
        $expected->data = [
            (object)[
                'appraisalname' => 'Appraisal 1',
                'status' => '1',
                'timecompleted' => null,
                'jobassignmentidnumber' => $data->user1ja->id,
                'stages' => [
                    (object)[
                        'stagename' => 'Stage 1',
                        'pages' => [
                            (object)[
                                'pagename' => 'Page 1',
                                'questions' => [
                                    (object)[
                                        'questionname' => 'Question text 1 A',
                                        'roleanswers' => $firstquestionanswers,
                                    ],
                                    (object)[
                                        'questionname' => 'Question text 1 B',
                                        'roleanswers' => [
                                            'role 1' => (object)['data' => 'Learner answer B'],
                                            'role 2' => (object)['data' => 'Manager answer B'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            (object)[
                'appraisalname' => 'Appraisal 2',
                'status' => '1',
                'timecompleted' => null,
                'jobassignmentidnumber' => null,
                'stages' => [
                    (object)[
                        'stagename' => 'Stage 2',
                        'pages' => [
                            (object)[
                                'pagename' => 'Page 2',
                                'questions' => [
                                    (object)[
                                        'questionname' => 'Question text 2',
                                        'roleanswers' => [
                                            'role 1' => '-',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    (object)[
                        'stagename' => 'Stage 3',
                        'pages' => [
                            (object)[
                                'pagename' => 'Page 3',
                                'questions' => [
                                    (object)[
                                        'questionname' => 'Question text 3',
                                        'roleanswers' => [
                                            'role 1' => '-',
                                        ],
                                    ],
                                    (object)[
                                        'questionname' => 'Question text 4',
                                        'roleanswers' => [
                                            'role 1' => '-',
                                        ],
                                    ],
                                ],
                            ],
                            (object)[
                                'pagename' => 'Page 4',
                                'questions' => [
                                    (object)[
                                        'questionname' => 'Question text 5',
                                        'roleanswers' => [
                                            'role 1' => '-',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    (object)[
                        'stagename' => 'Stage 4',
                        'pages' => [
                            (object)[
                                'pagename' => 'Page 5',
                                'questions' => [
                                    (object)[
                                        'questionname' => 'Question text 6',
                                        'roleanswers' => [
                                            'role 1' => '-',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var \totara_appraisal\userdata\appraisal_export $testclass */
        $testclass = $this->classtotest();
        $result = $testclass::execute_export($data->targetuser, context_system::instance());

        $this->assertEquals($expected, $result);
    }

    /**
     * Test the count function.
     */
    public function test_count() {
        /** @var \totara_appraisal\userdata\appraisal_export $testclass */
        $testclass = $this->classtotest();

        $data = $this->setup_data();

        $this->assertEquals(2, $testclass::execute_count($data->targetuser, context_system::instance()));

        // Execute the purge.
        $status = appraisal::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assertEquals(0, $testclass::execute_count($data->targetuser, context_system::instance()));
    }
}