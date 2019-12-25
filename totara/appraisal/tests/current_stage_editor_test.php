<?php
/*
 * This file is part of Totara LMS
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
global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

use \totara_appraisal\current_stage_editor;

class totara_appraisal_current_stage_editor_testcase extends appraisal_testcase {

    public function test_get_stages_for_users() {
        global $DB;

        $this->resetAfterTest();

        $learner = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        /** @var appraisal $appraisal */
        list($appraisal) = $this->prepare_appraisal_with_users(array(), array($learner));
        $appraisal->validate();
        $appraisal->activate();

        // No users (check it doesn't just fail).
        $this->assertEmpty(current_stage_editor::get_stages_for_users($appraisal->get()->id, array()));

        // Invalid appraisalid (check it doesn't just fail).
        $this->assertEmpty(current_stage_editor::get_stages_for_users(-1, array($learner->id)));

        // No results when the user isn't assigned.
        $this->assertEmpty(current_stage_editor::get_stages_for_users($appraisal->get()->id, array($manager->id)));

        // Actual record!
        $expected = new stdClass();
        $expected->userid = $learner->id;
        $expected->name = 'Stage';
        $expected->timecompleted = null; // Not yet complete.
        $expected->status = appraisal::STATUS_ACTIVE;
        $expected->jobassignmentid = null; // None chosed yet.
        $actual = current_stage_editor::get_stages_for_users($appraisal->get()->id, array($learner->id));
        $actual = reset($actual);
        $this->assertEquals($expected, $actual);

        // Tweak the data just to make sure we're getting the right fields and it's not coincidence.
        $DB->set_field('appraisal_user_assignment', 'timecompleted', 123);
        $DB->set_field('appraisal_user_assignment', 'status', 234);
        $DB->set_field('appraisal_user_assignment', 'jobassignmentid', 345);

        $expected = new stdClass();
        $expected->userid = $learner->id;
        $expected->name = 'Stage';
        $expected->timecompleted = 123;
        $expected->status = 234;
        $expected->jobassignmentid = 345;
        $actual = current_stage_editor::get_stages_for_users($appraisal->get()->id, array($learner->id));
        $actual = reset($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Set up some data which will be used by the set_stage_for_role_assignment tests.
     *
     * @return stdClass
     */
    public function setup_data_for_set_stage_for_role_assignment(): stdClass {
        $this->resetAfterTest();

        set_config('totara_job_allowmultiplejobs', false);

        $data = new stdClass();

        $def = array(
            'name' => 'Appraisal',
            'stages' => array(
                array(
                    'name' => 'Stage1',
                    'timedue' => time() + 86400,
                    'locks' => array(appraisal::ROLE_LEARNER => 1),
                    'pages' => array(
                        array(
                            'name' => 'Page1',
                            'questions' => array(
                                array(
                                    'name' => 'Text1',
                                    'type' => 'text',
                                    'roles' => array(appraisal::ROLE_LEARNER => 7, appraisal::ROLE_MANAGER => 7)
                                ),
                            )
                        ),
                    )
                ),
                array(
                    'name' => 'Stage2',
                    'timedue' => time() + 2 * 86400,
                    'pages' => array(
                        array(
                            'name' => 'Page2',
                            'questions' => array(
                                array(
                                    'name' => 'Text2',
                                    'type' => 'text',
                                    'roles' => array(appraisal::ROLE_LEARNER => 7, appraisal::ROLE_MANAGER => 7)
                                )
                            )
                        )
                    )
                ),
                array(
                    'name' => 'Stage3',
                    'timedue' => time() + 3 * 86400,
                    'pages' => array(
                        array(
                            'name' => 'Page3',
                            'questions' => array(
                                array(
                                    'name' => 'Text3',
                                    'type' => 'text',
                                    'roles' => array(appraisal::ROLE_LEARNER => 7, appraisal::ROLE_MANAGER => 7)
                                )
                            )
                        )
                    )
                )
            )
        );

        $data->learner1 = $this->getDataGenerator()->create_user();
        $data->learner2 = $this->getDataGenerator()->create_user();
        $data->manager = $this->getDataGenerator()->create_user();
        $managerja = \totara_job\job_assignment::create_default($data->manager->id);
        \totara_job\job_assignment::create_default($data->learner1->id, ['managerjaid' => $managerja->id]);
        \totara_job\job_assignment::create_default($data->learner2->id, ['managerjaid' => $managerja->id]);

        /** @var appraisal $appraisal1 */
        list($appraisal1) = $this->prepare_appraisal_with_users($def, array($data->learner1, $data->learner2));
        $appraisal1->validate();
        $appraisal1->activate();
        $data->appraisal1 = $appraisal1;

        /** @var appraisal $appraisal2 */
        list($appraisal2) = $this->prepare_appraisal_with_users($def, array($data->learner1, $data->learner2));
        $appraisal2->validate();
        $appraisal2->activate();
        $data->appraisal2 = $appraisal2;

        $data->map1 = $this->map($appraisal1);
        $data->map2 = $this->map($appraisal2);

        $data->stagestounlock = array($data->map1['stages']['Stage2'], $data->map1['stages']['Stage3']);

        $roles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER);

        $msg = new appraisal_message();
        $msg->event_stage($data->map1['stages']['Stage1'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();

        $msg = new appraisal_message();
        $msg->event_stage($data->map1['stages']['Stage2'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();
        $data->eventidappraisal1stage2 = $msg->id;

        $msg = new appraisal_message();
        $msg->event_stage($data->map2['stages']['Stage1'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();

        $msg = new appraisal_message();
        $msg->event_stage($data->map2['stages']['Stage2'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();

        $data->learner1userassignment1 = appraisal_user_assignment::get_user($appraisal1->get()->id, $data->learner1->id);
        /** @var appraisal_role_assignment $data->managerroleassignmentlearner1appraisal1 */
        $data->managerroleassignmentlearner1appraisal1 = appraisal_role_assignment::get_role(
            $data->appraisal1->get()->id,
            $data->learner1->id,
            $data->manager->id,
            appraisal::ROLE_MANAGER
        );
        /** @var appraisal_role_assignment $data->managerroleassignmentlearner1appraisal2 */
        $data->managerroleassignmentlearner1appraisal2 = appraisal_role_assignment::get_role(
            $data->appraisal2->get()->id,
            $data->learner1->id,
            $data->manager->id,
            appraisal::ROLE_MANAGER
        );
        /** @var appraisal_role_assignment $data->managerroleassignmentlearner2appraisal1 */
        $data->managerroleassignmentlearner2appraisal1 = appraisal_role_assignment::get_role(
            $data->appraisal1->get()->id,
            $data->learner2->id,
            $data->manager->id,
            appraisal::ROLE_MANAGER
        );
        /** @var appraisal_role_assignment $data->managerroleassignmentlearner2appraisal2 */
        $data->managerroleassignmentlearner2appraisal2 = appraisal_role_assignment::get_role(
            $data->appraisal2->get()->id,
            $data->learner2->id,
            $data->manager->id,
            appraisal::ROLE_MANAGER
        );
        /** @var appraisal_role_assignment $learner1roleassignment1 */
        $data->learner1roleassignment1 = appraisal_role_assignment::get_role(
            $data->appraisal1->get()->id,
            $data->learner1->id,
            $data->learner1->id,
            appraisal::ROLE_LEARNER
        );
        /** @var appraisal_role_assignment $learner1roleassignment2 */
        $data->learner1roleassignment2 = appraisal_role_assignment::get_role(
            $data->appraisal2->get()->id,
            $data->learner1->id,
            $data->learner1->id,
            appraisal::ROLE_LEARNER
        );
        /** @var appraisal_role_assignment $learner2roleassignment1 */
        $data->learner2roleassignment1 = appraisal_role_assignment::get_role(
            $data->appraisal1->get()->id,
            $data->learner2->id,
            $data->learner2->id,
            appraisal::ROLE_LEARNER
        );
        /** @var appraisal_role_assignment $learner2roleassignment2 */
        $data->learner2roleassignment2 = appraisal_role_assignment::get_role(
            $data->appraisal2->get()->id,
            $data->learner2->id,
            $data->learner2->id,
            appraisal::ROLE_LEARNER
        );

        // Complete all the stages for all roles in all user appraisals.
        /** @var appraisal_stage $stage */
        $appraisal1stages = appraisal_stage::get_stages($appraisal1->get()->id);
        foreach ($appraisal1stages as $stage) {
            $stage->complete_for_role($data->managerroleassignmentlearner1appraisal1);
            $stage->complete_for_role($data->managerroleassignmentlearner2appraisal1);
            $stage->complete_for_role($data->learner1roleassignment1);
            $stage->complete_for_role($data->learner2roleassignment1);
        }
        $appraisal2stages = appraisal_stage::get_stages($appraisal2->get()->id);
        foreach ($appraisal2stages as $stage) {
            $stage->complete_for_role($data->managerroleassignmentlearner1appraisal2);
            $stage->complete_for_role($data->managerroleassignmentlearner2appraisal2);
            $stage->complete_for_role($data->learner1roleassignment2);
            $stage->complete_for_role($data->learner2roleassignment2);
        }

        return $data;
    }

    public function test_set_stage_for_role_assignment_unlock_single_role() {
        global $DB;

        $data = $this->setup_data_for_set_stage_for_role_assignment();

        /////////////////////////////////////////////////////////////////////////////
        // Only learner1's user assignment will be modified.

        // Mark all records so that we know the control records are not touched.
        $DB->set_field('appraisal_user_assignment', 'activestageid', 123);
        $DB->set_field('appraisal_user_assignment', 'timecompleted', 234);
        $DB->set_field('appraisal_user_assignment', 'status', 345);
        $expecteduserassignments = $DB->get_records('appraisal_user_assignment', [], 'id');
        $this->assertCount(4, $expecteduserassignments); // One per appraisal per learner.

        // Modify the records to make them look like what we expect they will be.
        $matches_found = 0;
        foreach ($expecteduserassignments as $expectedrecord) {
            if ($expectedrecord->userid == $data->learner1->id && $expectedrecord->appraisalid == $data->appraisal1->get()->id) {
                $this->assertEquals(123, $expectedrecord->activestageid);
                $expectedrecord->activestageid = $data->map1['stages']['Stage2'];
                $this->assertEquals(234, $expectedrecord->timecompleted);
                $expectedrecord->timecompleted = null;
                $this->assertEquals(345, $expectedrecord->status);
                $expectedrecord->status = appraisal::STATUS_ACTIVE;
                $matches_found++;
            }
        }
        $this->assertEquals(1, $matches_found);

        /////////////////////////////////////////////////////////////////////////////
        // Only the manager's appraisal_stage_data will be deleted.
        $expectedstagedata = $DB->get_records('appraisal_stage_data', [], 'id');
        $this->assertCount(24, $expectedstagedata); // One per appraisal per learner per role * 3 stages.

        // Modify the records to make them look like what we expect they will be.
        foreach ($expectedstagedata as $key => $expectedrecord) {
            if ($expectedrecord->appraisalroleassignmentid == $data->managerroleassignmentlearner1appraisal1->id &&
                in_array($expectedrecord->appraisalstageid, $data->stagestounlock)) {
                unset($expectedstagedata[$key]);
            }
        }
        $this->assertCount(22, $expectedstagedata); // Two deleted.

        /////////////////////////////////////////////////////////////////////////////
        // The event will be deleted (messages that are due to be sent).
        $expecteduserevents = $DB->get_records('appraisal_user_event', [], 'id');
        $this->assertCount(8, $expecteduserevents); // One per appraisal per learner * 2 stages (stage 3 has no message).

        // Modify the records to make them look like what we expect they will be.
        foreach ($expecteduserevents as $key => $expectedrecord) {
            if ($expectedrecord->userid == $data->learner1->id &&
                $expectedrecord->eventid == $data->eventidappraisal1stage2) {
                unset($expecteduserevents[$key]);
            }
        }
        $this->assertCount(7, $expecteduserevents); // One deleted.

        /////////////////////////////////////////////////////////////////////////////
        // The appraisal_role_assignment activepageid will be updated.
        $DB->set_field('appraisal_role_assignment', 'activepageid', 123);
        $expectedroleassignments = $DB->get_records('appraisal_role_assignment', [], 'id');
        $this->assertCount(16, $expectedroleassignments); // One per appraisal per learner * all 4 roles.

        // Modify the records to make them look like what we expect they will be.
        $matches_found = 0;
        foreach ($expectedroleassignments as $expectedrecord) {
            if ($expectedrecord->appraisaluserassignmentid == $data->learner1userassignment1->id &&
                $expectedrecord->appraisalrole == appraisal::ROLE_MANAGER) {
                $this->assertEquals(123, $expectedrecord->activepageid);
                $expectedrecord->activepageid = null;
                $matches_found++;
            }
        }
        $this->assertEquals(1, $matches_found);

        /////////////////////////////////////////////////////////////////////////////
        // Run the function - reset manager of learner1 to stage2 in appraisal1.
        current_stage_editor::set_stage_for_role_assignment(
            $data->appraisal1->get()->id,
            $data->learner1->id,
            $data->managerroleassignmentlearner1appraisal1->id,
            $data->map1['stages']['Stage2']
        );

        /////////////////////////////////////////////////////////////////////////////
        // Check the results.
        $actualuserassignments = $DB->get_records('appraisal_user_assignment', [], 'id');
        $this->assertEquals($expecteduserassignments, $actualuserassignments);

        $actualstagedata = $DB->get_records('appraisal_stage_data', [], 'id');
        $this->assertEquals($expectedstagedata, $actualstagedata);

        $actualuserevents = $DB->get_records('appraisal_user_event', [], 'id');
        $this->assertEquals($expecteduserevents, $actualuserevents);

        $actualroleassignments = $DB->get_records('appraisal_role_assignment', [], 'id');
        $this->assertEquals($expectedroleassignments, $actualroleassignments);

        /////////////////////////////////////////////////////////////////////////////
        // Check that an exception is thrown when no stage can be found to unlock (for whatever reason).
        $this->expectExceptionMessage("Cannot find any stage to unlock");
        current_stage_editor::set_stage_for_role_assignment(
            $data->appraisal1->get()->id,
            $data->learner2->id,
            $data->managerroleassignmentlearner1appraisal1->id,
            -1
        );
    }

    public function test_set_stage_for_role_assignment_unlock_all_roles() {
        global $DB;

        $data = $this->setup_data_for_set_stage_for_role_assignment();

        /////////////////////////////////////////////////////////////////////////////
        // Only learner1's user assignment will be modified.

        // Mark all records so that we know the control records are not touched.
        $DB->set_field('appraisal_user_assignment', 'activestageid', 123);
        $DB->set_field('appraisal_user_assignment', 'timecompleted', 234);
        $DB->set_field('appraisal_user_assignment', 'status', 345);
        $expecteduserassignments = $DB->get_records('appraisal_user_assignment', [], 'id');
        $this->assertCount(4, $expecteduserassignments); // One per appraisal per learner.

        // Modify the records to make them look like what we expect they will be.
        $matches_found = 0;
        foreach ($expecteduserassignments as $expectedrecord) {
            if ($expectedrecord->userid == $data->learner1->id && $expectedrecord->appraisalid == $data->appraisal1->get()->id) {
                $this->assertEquals(123, $expectedrecord->activestageid);
                $expectedrecord->activestageid = $data->map1['stages']['Stage2'];
                $this->assertEquals(234, $expectedrecord->timecompleted);
                $expectedrecord->timecompleted = null;
                $this->assertEquals(345, $expectedrecord->status);
                $expectedrecord->status = appraisal::STATUS_ACTIVE;
                $matches_found++;
            }
        }
        $this->assertEquals(1, $matches_found);

        /////////////////////////////////////////////////////////////////////////////
        // All roles' appraisal_stage_data will be deleted.
        $expectedstagedata = $DB->get_records('appraisal_stage_data', [], 'id');
        $this->assertCount(24, $expectedstagedata); // One per appraisal per learner per role * 3 stages.

        // Modify the records to make them look like what we expect they will be.
        foreach ($expectedstagedata as $key => $expectedrecord) {
            if (($expectedrecord->appraisalroleassignmentid == $data->learner1roleassignment1->id ||
                 $expectedrecord->appraisalroleassignmentid == $data->managerroleassignmentlearner1appraisal1->id) &&
                in_array($expectedrecord->appraisalstageid, $data->stagestounlock)) {
                unset($expectedstagedata[$key]);
            }
        }
        $this->assertCount(20, $expectedstagedata); // Four deleted.

        /////////////////////////////////////////////////////////////////////////////
        // The event will be deleted (messages that are due to be sent).
        $expecteduserevents = $DB->get_records('appraisal_user_event', [], 'id');
        $this->assertCount(8, $expecteduserevents); // One per appraisal per learner * 2 stages (stage 3 has no message).

        // Modify the records to make them look like what we expect they will be.
        foreach ($expecteduserevents as $key => $expectedrecord) {
            if ($expectedrecord->userid == $data->learner1->id &&
                $expectedrecord->eventid == $data->eventidappraisal1stage2) {
                unset($expecteduserevents[$key]);
            }
        }
        $this->assertCount(7, $expecteduserevents); // One deleted.

        /////////////////////////////////////////////////////////////////////////////
        // The appraisal_role_assignment activepageid will be updated.
        $DB->set_field('appraisal_role_assignment', 'activepageid', 123);
        $expectedroleassignments = $DB->get_records('appraisal_role_assignment', [], 'id');
        $this->assertCount(16, $expectedroleassignments); // One per appraisal per learner * all 4 roles.

        // Modify the records to make them look like what we expect they will be.
        $matches_found = 0;
        foreach ($expectedroleassignments as $expectedrecord) {
            if ($expectedrecord->appraisaluserassignmentid == $data->learner1userassignment1->id) {
                $this->assertEquals(123, $expectedrecord->activepageid);
                $expectedrecord->activepageid = null;
                $matches_found++;
            }
        }
        $this->assertEquals(4, $matches_found);

        /////////////////////////////////////////////////////////////////////////////
        // Run the function - reset all roles of learner1 to stage2 in appraisal1.
        current_stage_editor::set_stage_for_role_assignment(
            $data->appraisal1->get()->id,
            $data->learner1->id,
            -1,
            $data->map1['stages']['Stage2']
        );

        /////////////////////////////////////////////////////////////////////////////
        // Check the results.
        $actualuserassignments = $DB->get_records('appraisal_user_assignment', [], 'id');
        $this->assertEquals($expecteduserassignments, $actualuserassignments);

        $actualstagedata = $DB->get_records('appraisal_stage_data', [], 'id');
        $this->assertEquals($expectedstagedata, $actualstagedata);

        $actualuserevents = $DB->get_records('appraisal_user_event', [], 'id');
        $this->assertEquals($expecteduserevents, $actualuserevents);

        $actualroleassignments = $DB->get_records('appraisal_role_assignment', [], 'id');
        $this->assertEquals($expectedroleassignments, $actualroleassignments);

        /////////////////////////////////////////////////////////////////////////////
        // Check that an exception is thrown when no stage can be found to unlock (for whatever reason).
        $this->expectExceptionMessage("Cannot find any stage to unlock");
        current_stage_editor::set_stage_for_role_assignment(
            $data->appraisal1->get()->id,
            $data->learner2->id,
            $data->learner1roleassignment1->id,
            -1
        );
    }
}