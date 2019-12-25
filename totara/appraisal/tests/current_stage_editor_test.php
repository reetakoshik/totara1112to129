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

    public function test_set_stage_for_role_assignment() {
        global $DB;

        $this->resetAfterTest();

        set_config('totara_job_allowmultiplejobs', false);

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

        $learner1 = $this->getDataGenerator()->create_user();
        $learner2 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerja = \totara_job\job_assignment::create_default($manager->id);
        \totara_job\job_assignment::create_default($learner1->id, ['managerjaid' => $managerja->id]);
        \totara_job\job_assignment::create_default($learner2->id, ['managerjaid' => $managerja->id]);

        /** @var appraisal $appraisal1 */
        list($appraisal1) = $this->prepare_appraisal_with_users($def, array($learner1, $learner2));
        $appraisal1->validate();
        $appraisal1->activate();
        /** @var appraisal $appraisal2 */
        list($appraisal2) = $this->prepare_appraisal_with_users($def, array($learner1, $learner2));
        $appraisal2->validate();
        $appraisal2->activate();

        $map1 = $this->map($appraisal1);
        $map2 = $this->map($appraisal2);

        $stagestounlock = array($map1['stages']['Stage2'], $map1['stages']['Stage3']);

        $roles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER);

        $msg = new appraisal_message();
        $msg->event_stage($map1['stages']['Stage1'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();

        $msg = new appraisal_message();
        $msg->event_stage($map1['stages']['Stage2'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();
        $eventidappraisal1stage2 = $msg->id;

        $msg = new appraisal_message();
        $msg->event_stage($map2['stages']['Stage1'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();

        $msg = new appraisal_message();
        $msg->event_stage($map2['stages']['Stage2'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msg->set_delta(1, appraisal_message::PERIOD_DAY);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();

        $learner1userassignment1 = appraisal_user_assignment::get_user($appraisal1->get()->id, $learner1->id);
        $managerroleassignmentlearner1appraisal1 = appraisal_role_assignment::get_role(
            $appraisal1->get()->id,
            $learner1->id,
            $manager->id,
            appraisal::ROLE_MANAGER
        );
        $managerroleassignmentlearner1appraisal2 = appraisal_role_assignment::get_role(
            $appraisal2->get()->id,
            $learner1->id,
            $manager->id,
            appraisal::ROLE_MANAGER
        );
        $managerroleassignmentlearner2appraisal1 = appraisal_role_assignment::get_role(
            $appraisal1->get()->id,
            $learner2->id,
            $manager->id,
            appraisal::ROLE_MANAGER
        );
        $managerroleassignmentlearner2appraisal2 = appraisal_role_assignment::get_role(
            $appraisal2->get()->id,
            $learner2->id,
            $manager->id,
            appraisal::ROLE_MANAGER
        );
        $learner1roleassignment1 = appraisal_role_assignment::get_role(
            $appraisal1->get()->id,
            $learner1->id,
            $learner1->id,
            appraisal::ROLE_LEARNER
        );
        $learner1roleassignment2 = appraisal_role_assignment::get_role(
            $appraisal2->get()->id,
            $learner1->id,
            $learner1->id,
            appraisal::ROLE_LEARNER
        );
        $learner2roleassignment1 = appraisal_role_assignment::get_role(
            $appraisal1->get()->id,
            $learner2->id,
            $learner2->id,
            appraisal::ROLE_LEARNER
        );
        $learner2roleassignment2 = appraisal_role_assignment::get_role(
            $appraisal2->get()->id,
            $learner2->id,
            $learner2->id,
            appraisal::ROLE_LEARNER
        );

        // Complete all the stages for all roles in all user appraisals.
        /** @var appraisal_stage $stage */
        $appraisal1stages = appraisal_stage::get_stages($appraisal1->get()->id);
        foreach ($appraisal1stages as $stage) {
            $stage->complete_for_role($managerroleassignmentlearner1appraisal1);
            $stage->complete_for_role($managerroleassignmentlearner2appraisal1);
            $stage->complete_for_role($learner1roleassignment1);
            $stage->complete_for_role($learner2roleassignment1);
        }
        $appraisal2stages = appraisal_stage::get_stages($appraisal2->get()->id);
        foreach ($appraisal2stages as $stage) {
            $stage->complete_for_role($managerroleassignmentlearner1appraisal2);
            $stage->complete_for_role($managerroleassignmentlearner2appraisal2);
            $stage->complete_for_role($learner1roleassignment2);
            $stage->complete_for_role($learner2roleassignment2);
        }

        /////////////////////////////////////////////////////////////////////////////
        // Only learner1's user assignment was modified.

        // Mark all records so that we know the control records are not touched.
        $DB->set_field('appraisal_user_assignment', 'activestageid', 123);
        $DB->set_field('appraisal_user_assignment', 'timecompleted', 234);
        $DB->set_field('appraisal_user_assignment', 'status', 345);
        $expecteduserassignments = $DB->get_records('appraisal_user_assignment', [], 'id');
        $this->assertCount(4, $expecteduserassignments); // One per appraisal per learner.

        // Modify the records to make them look like what we expect they will be.
        $matches_found = 0;
        foreach ($expecteduserassignments as $expectedrecord) {
            if ($expectedrecord->userid == $learner1->id && $expectedrecord->appraisalid == $appraisal1->get()->id) {
                $this->assertEquals(123, $expectedrecord->activestageid);
                $expectedrecord->activestageid = $map1['stages']['Stage2'];
                $this->assertEquals(234, $expectedrecord->timecompleted);
                $expectedrecord->timecompleted = null;
                $this->assertEquals(345, $expectedrecord->status);
                $expectedrecord->status = appraisal::STATUS_ACTIVE;
                $matches_found++;
            }
        }
        $this->assertEquals(1, $matches_found);

        /////////////////////////////////////////////////////////////////////////////
        // Delete learner1's appraisal_stage_data.
        $expectedstagedata = $DB->get_records('appraisal_stage_data', [], 'id');
        $this->assertCount(24, $expectedstagedata); // One per appraisal per learner per role * 3 stages.

        // Modify the records to make them look like what we expect they will be.
        foreach ($expectedstagedata as $key => $expectedrecord) {
            if ($expectedrecord->appraisalroleassignmentid == $managerroleassignmentlearner1appraisal1->id &&
                in_array($expectedrecord->appraisalstageid, $stagestounlock)) {
                unset($expectedstagedata[$key]);
            }
        }
        $this->assertCount(22, $expectedstagedata); // Two deleted.

        /////////////////////////////////////////////////////////////////////////////
        // Delete events (messages that are due to be sent).
        $expecteduserevents = $DB->get_records('appraisal_user_event', [], 'id');
        $this->assertCount(8, $expecteduserevents); // One per appraisal per learner * 2 stages (stage 3 has no message).

        // Modify the records to make them look like what we expect they will be.
        foreach ($expecteduserevents as $key => $expectedrecord) {
            if ($expectedrecord->userid == $learner1->id &&
                $expectedrecord->eventid == $eventidappraisal1stage2) {
                unset($expecteduserevents[$key]);
            }
        }
        $this->assertCount(7, $expecteduserevents); // One deleted.

        /////////////////////////////////////////////////////////////////////////////
        // Update the appraisal_role_assignment activepageid.
        $DB->set_field('appraisal_role_assignment', 'activepageid', 123);
        $expectedroleassignments = $DB->get_records('appraisal_role_assignment', [], 'id');
        $this->assertCount(16, $expectedroleassignments); // One per appraisal per learner * all 4 roles.

        // Modify the records to make them look like what we expect they will be.
        $matches_found = 0;
        foreach ($expectedroleassignments as $expectedrecord) {
            if ($expectedrecord->appraisaluserassignmentid == $learner1userassignment1->id &&
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
            $appraisal1->get()->id,
            $learner1->id,
            $managerroleassignmentlearner1appraisal1->id,
            $map1['stages']['Stage2']
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
            $appraisal1->get()->id,
            $learner2->id,
            $managerroleassignmentlearner1appraisal1->id,
            -1
        );
    }
}