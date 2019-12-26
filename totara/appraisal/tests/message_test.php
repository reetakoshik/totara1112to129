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
 * Unit tests for appraisal_message class of totara/appraisal/lib.php
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit --verbose appraisal_message_test totara/appraisal/tests/message_test.php
 */
global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

class appraisal_message_test extends appraisal_testcase {
    public function test_create() {
        $this->resetAfterTest();
        list($appraisal) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);

        $msg = new appraisal_message();
        $msg->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msg->set_delta(-3, appraisal_message::PERIOD_DAY);
        $roles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ONLY_COMPLETE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();
        $msgid = $msg->id;
        unset($msg);

        $msgtest = new appraisal_message($msgid);
        $this->assertEquals($appraisal->id, $msgtest->appraisalid);
        $this->assertEquals($map['stages']['Stage'], $msgtest->stageid);
        $this->assertEquals(appraisal_message::EVENT_STAGE_DUE, $msgtest->type);
        $this->assertEquals(-3, $msgtest->delta);
        $this->assertEquals(1, $msgtest->deltaperiod);
        $this->assertEquals($roles, $msgtest->roles);
        $this->assertEquals(true, $msgtest->stageiscompleted);
        foreach ($roles as $role) {
            $content = $msgtest->get_message($role);
            $this->assertEquals('Title '.$role, $content->name);
            $this->assertEquals('Body '.$role, $content->content);
        }
    }

    public function test_edit() {
        $this->resetAfterTest();
        // Create appraisal with messages.
        list($appraisal) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);

        $msg = new appraisal_message();
        $msg->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msg->set_delta(-3, appraisal_message::PERIOD_DAY);
        $roles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER);
        $msg->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        $msg->save();
        $msgid = $msg->id;
        unset($msg);

        // Edit this message.
        $msgedit = new appraisal_message($msgid);
        $msgedit->event_appraisal($appraisal->id);
        $msgedit->set_delta(0);
        $roles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_APPRAISER);
        $msgedit->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        $msgedit->set_message(0, 'Title 0', 'Body 0');
        $msgedit->save();

        // Check changes.
        $msgtest = new appraisal_message($msgid);
        $this->assertEquals($appraisal->id, $msgtest->appraisalid);
        $this->assertEquals(0, $msgtest->stageid);
        $this->assertEquals(appraisal_message::EVENT_APPRAISAL_ACTIVATION, $msgtest->type);
        $this->assertEquals(0, $msgtest->delta);
        $this->assertEquals(0, $msgtest->deltaperiod);
        $msgtestroles = $msgtest->roles;
        sort($roles);
        sort($msgtestroles);
        $this->assertEquals($roles, $msgtestroles);
        $this->assertEquals(0, $msgtest->stageiscompleted);
        foreach ($roles as $role) {
            $content = $msgtest->get_message($role);
            $this->assertEquals('Title 0', $content->name);
            $this->assertEquals('Body 0', $content->content);
        }
    }

    public function test_delete() {
        $this->resetAfterTest();
        list($appraisal) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);
        list($appraisal2) = $this->prepare_appraisal_with_users();
        $roles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER);

        // Create three messages: 2x appraisal, and Stage.
        $msg1 = new appraisal_message();
        $msg1->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msg1->set_delta(-3, appraisal_message::PERIOD_DAY);
        $msg1->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        $msg1->set_message(0, 'Title 0', 'Body 0');
        $msg1->save();
        $msg1id = $msg1->id;
        unset($msg1);

        $msg2 = new appraisal_message();
        $msg2->event_appraisal($appraisal->id);
        $msg2->set_roles($roles);
        $msg2->set_message(0, 'Title 0', 'Body 0');
        $msg2->save();
        $msg2id = $msg2->id;
        unset($msg2);

        $msg3 = new appraisal_message();
        $msg3->event_appraisal($appraisal->id);
        $msg3->set_roles($roles);
        $msg3->set_message(0, 'Title 0', 'Body 0');
        $msg3->save();
        $msg3id = $msg3->id;
        unset($msg3);

        $msg4 = new appraisal_message();
        $msg4->event_appraisal($appraisal2->id);
        $msg4->set_roles($roles);
        $msg4->set_message(0, 'Title 0', 'Body 0');
        $msg4->save();
        $msg4id = $msg4->id;
        unset($msg4);

        // Delete one related to appraisal.
        appraisal_message::delete($msg2id);
        $list1 = appraisal_message::get_list($appraisal->id);
        $this->assertCount(2, $list1);
        $this->assertArrayHasKey($msg1id, $list1);
        $this->assertArrayHasKey($msg3id, $list1);

        // Delete stage1.
        appraisal_message::delete_stage($map['stages']['Stage']);
        $list2 = appraisal_message::get_list($appraisal->id);
        $this->assertCount(1, $list2);
        $this->assertArrayHasKey($msg3id, $list1);

        // Delete all appraisal.
        appraisal_message::delete_appraisal($appraisal->id);
        $list3 = appraisal_message::get_list($appraisal->id);
        $this->assertEmpty($list3);
        $list4 = appraisal_message::get_list($appraisal2->id);
        $this->assertCount(1, $list4);
        $this->assertArrayHasKey($msg4id, $list4);
    }

    public function test_is_time() {
        $this->resetAfterTest();
        list($appraisal) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);
        $roles = array(appraisal::ROLE_LEARNER);

        $msgpast = new appraisal_message();
        $msgpast->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msgpast->set_delta(-1, appraisal_message::PERIOD_DAY);
        $msgpast->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        $msgpast->set_message(0, 'Title 0', 'Body 0');
        $msgpast->save();
        $msgpastid = $msgpast->id;
        unset($msgpast);

        $msgfuture = new appraisal_message();
        $msgfuture->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msgfuture->set_delta(2, appraisal_message::PERIOD_WEEK);
        $msgfuture->set_roles($roles, appraisal_message::MESSAGE_SEND_ANY_STATE);
        $msgfuture->set_message(0, 'Title 0', 'Body 0');
        $msgfuture->save();
        $msgfutureid = $msgfuture->id;
        unset($msgfuture);

        $appraisal->validate();
        $appraisal->activate();
        $stage = new appraisal_stage($map['stages']['Stage']);
        $stagedue = $stage->timedue;

        $msgpastact = new appraisal_message($msgpastid);
        $msgfutureact = new appraisal_message($msgfutureid);

        // Check past time (happened).
        $pstistime = $stagedue - 86400;

        $this->assertTrue($msgpastact->is_time($pstistime));

        // Check past time (not happened).
        $pstnotistime = $stagedue - 86400 - 1;
        $this->assertFalse($msgpastact->is_time($pstnotistime));

        // Check future time (happened).
        $ftristime = $stagedue + 86400 * 14;
        $this->assertTrue($msgfutureact->is_time($ftristime));

        // Check future time (not happened).
        $ftrnotistime = $stagedue + 86400 * 14 - 1;
        $this->assertFalse($msgfutureact->is_time($ftrnotistime));
    }

    public function test_duplicate_appraisal() {
        $this->resetAfterTest();
        list($appraisal) = $this->prepare_appraisal_with_users();
        list($appraisal2) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);
        $map2 = $this->map($appraisal2);
        $roles = array(appraisal::ROLE_LEARNER);

        $msgappr = new appraisal_message();
        $msgappr->event_appraisal($appraisal->id);
        $msgappr->set_delta(-1, appraisal_message::PERIOD_DAY);
        $msgappr->set_roles($roles);
        $msgappr->set_message(0, 'Title 0', 'Body 0');
        $msgappr->save();
        $msgapprid = $msgappr->id;
        unset($msgappr);

        $appraisal->validate();
        $appraisal->activate();

        // Check appraisal activation.
        appraisal_message::duplicate_appraisal($appraisal->id, $appraisal2->id);
        $list = appraisal_message::get_list($appraisal2->id);
        $this->assertCount(1, $list);
        $msgtest = new appraisal_message(current($list)->id);
        $this->assertEquals(0, $msgtest->timescheduled);
        $this->assertGreaterThan($msgapprid, $msgtest->id);
        $this->assertEquals($appraisal2->id, $msgtest->appraisalid);
        $this->assertEquals(appraisal_message::EVENT_APPRAISAL_ACTIVATION, $msgtest->type);
        $this->assertEquals(-1, $msgtest->delta);
        $this->assertEquals(1, $msgtest->deltaperiod);
        $this->assertEquals($roles, $msgtest->roles);
        $this->assertEquals(0, $msgtest->stageiscompleted);
        foreach ($roles as $role) {
            $content = $msgtest->get_message($role);
            $this->assertEquals('Title 0', $content->name);
            $this->assertEquals('Body 0', $content->content);
        }
    }

    public function test_duplicate_stage() {
        $this->resetAfterTest();
        list($appraisal) = $this->prepare_appraisal_with_users();
        list($appraisal2) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);
        $map2 = $this->map($appraisal2);
        $roles = array(appraisal::ROLE_LEARNER);

        $msgstage = new appraisal_message();
        $msgstage->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msgstage->set_delta(-1, appraisal_message::PERIOD_DAY);
        $msgstage->set_roles($roles, appraisal_message::MESSAGE_SEND_ONLY_COMPLETE);
        $msgstage->set_message(0, 'Title 0', 'Body 0');
        $msgstage->save();
        $msgstageid = $msgstage->id;
        unset($msgstage);

        $appraisal->validate();
        $appraisal->activate();

        // Check stage duplicate.
        appraisal_message::duplicate_stage($map['stages']['Stage'], $map2['stages']['Stage']);

        $list = appraisal_message::get_list($appraisal2->id);
        $this->assertCount(1, $list);
        $msgtest = new appraisal_message(current($list)->id);
        $this->assertEquals(0, $msgtest->timescheduled);
        $this->assertGreaterThan($msgstageid, $msgtest->id);
        $this->assertEquals($appraisal2->id, $msgtest->appraisalid);
        $this->assertEquals($map2['stages']['Stage'], $msgtest->stageid);
        $this->assertEquals(appraisal_message::EVENT_STAGE_DUE, $msgtest->type);
        $this->assertEquals(-1, $msgtest->delta);
        $this->assertEquals(1, $msgtest->deltaperiod);
        $this->assertEquals($roles, $msgtest->roles);
        $this->assertEquals(1, $msgtest->stageiscompleted);
        foreach ($roles as $role) {
            $content = $msgtest->get_message($role);
            $this->assertEquals('Title 0', $content->name);
            $this->assertEquals('Body 0', $content->content);
        }
    }

    public function test_set_message() {
        $this->resetAfterTest();
        list($appraisal) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);
        $roles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER, appraisal::ROLE_APPRAISER, appraisal::ROLE_TEAM_LEAD);

        // Separate messages for roles.
        $msg = new appraisal_message();
        $msg->set_roles($roles);
        foreach ($roles as $role) {
            $msg->set_message($role, 'Title '.$role, 'Body '.$role);
        }
        foreach ($roles as $role) {
            $content = $msg->get_message($role);
            $this->assertEquals('Title '.$role, $content->name);
            $this->assertEquals('Body '.$role, $content->content);
        }

        // Common message for roles.
        $msg2 = new appraisal_message();
        $msg2->set_roles($roles);
        $msg->set_message(0, 'Title', 'Body');
        foreach ($roles as $role) {
            $content = $msg->get_message($role);
            $this->assertEquals('Title', $content->name);
            $this->assertEquals('Body', $content->content);
        }
    }

    public function test_get_schedule_from() {
        $this->resetAfterTest();
        list($appraisal) = $this->prepare_appraisal_with_users();
        $map = $this->map($appraisal);

        $msgappr = new appraisal_message();
        $msgappr->event_appraisal($appraisal->id);

        $msgstage = new appraisal_message();
        $msgstage->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $stage = new appraisal_stage($map['stages']['Stage']);
        $stagedue = $stage->timedue;

        // Check appraisal event +3 days.
        $msgappr->set_delta(3, appraisal_message::PERIOD_DAY);
        $this->assertEquals(1000259200, $msgappr->get_schedule_from(1000000000));

        // Check appraisal event immediate.
        $msgappr->set_delta(0);
        $this->assertEquals(1000000000, $msgappr->get_schedule_from(1000000000));

        // Check stagedue event -1 months.
        $msgstage->set_delta(-1, appraisal_message::PERIOD_MONTH);
        $this->assertEquals($stagedue - 2592000, $msgstage->get_schedule_from(1000000000));

        // Check stagedue event immediate.
        $msgstage->set_delta(0);
        $this->assertEquals($stagedue, $msgstage->get_schedule_from(1000000000));

        // Check stagedue event -1 week.
        $msgstage->set_delta(-1, appraisal_message::PERIOD_WEEK);
        $this->assertEquals($stagedue - 604800, $msgstage->get_schedule_from(1000000000));
    }

    public function test_process_event() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->preventResetByRollback();

        $oldlog = ini_get('error_log');
        ini_set('error_log', "$CFG->dataroot/testlog.log"); // Prevent standard logging.
        unset_config('noemailever');

        // Testing with 3 users to make sure that one user's stage completion doesn't interfere with another's.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Record the time before we prepare the appraisal.
        $minactivationtime = time();

        /** @var appraisal $appraisal */
        list($appraisal) = $this->prepare_appraisal_with_users(array(), array($user1, $user2, $user3));

        $map = $this->map($appraisal);
        $roles = array(appraisal::ROLE_LEARNER);
        $stage = new appraisal_stage($map['stages']['Stage']);
        $stagedue = $stage->timedue;

        // Appraisal activation, immediate.
        $msgappractivateimmediate = new appraisal_message();
        $msgappractivateimmediate->event_appraisal($appraisal->id);
        $msgappractivateimmediate->set_delta(0);
        $msgappractivateimmediate->set_roles($roles);
        $msgappractivateimmediate->set_message(0, 'Appraisal activation immediate', 'Body 1');
        $msgappractivateimmediate->save();
        $msgappractivateimmediateid = $msgappractivateimmediate->id;
        unset($msgappractivateimmediate);

        // Appraisal activation, after.
        $msgappractivateafter = new appraisal_message();
        $msgappractivateafter->event_appraisal($appraisal->id);
        $msgappractivateafter->set_delta(1, appraisal_message::PERIOD_DAY);
        $msgappractivateafter->set_roles($roles);
        $msgappractivateafter->set_message(0, 'Appraisal activation after', 'Body 2');
        $msgappractivateafter->save();
        $msgappractivateafterid = $msgappractivateafter->id;
        unset($msgappractivateafter);

        // Stage due before.
        $msgstageduebefore = new appraisal_message();
        $msgstageduebefore->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msgstageduebefore->set_delta(-1, appraisal_message::PERIOD_DAY);
        $msgstageduebefore->set_roles($roles);
        $msgstageduebefore->set_message(0, 'Stage due before', 'Body 3');
        $msgstageduebefore->save();
        $msgstageduebeforeid = $msgstageduebefore->id;
        unset($msgstageduebefore);

        // Stage due immediate.
        $msgstagedueimmediate = new appraisal_message();
        $msgstagedueimmediate->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msgstagedueimmediate->set_delta(0);
        $msgstagedueimmediate->set_roles($roles);
        $msgstagedueimmediate->set_message(0, 'Stage due immediate', 'Body 4');
        $msgstagedueimmediate->save();
        $msgstagedueimmediateid = $msgstagedueimmediate->id;
        unset($msgstagedueimmediate);

        // Stage due after complete.
        $msgstagedueafter = new appraisal_message();
        $msgstagedueafter->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msgstagedueafter->set_delta(1, appraisal_message::PERIOD_DAY);
        $msgstagedueafter->set_roles($roles, appraisal_message::MESSAGE_SEND_ONLY_COMPLETE);
        $msgstagedueafter->set_message(0, 'Stage due after complete', 'Body 5');
        $msgstagedueafter->save();
        $msgstagedueafterid = $msgstagedueafter->id;
        unset($msgstagedueafter);

        // Stage due after incomplete.
        $msgstagedueafter = new appraisal_message();
        $msgstagedueafter->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_DUE);
        $msgstagedueafter->set_delta(1, appraisal_message::PERIOD_DAY);
        $msgstagedueafter->set_roles($roles, appraisal_message::MESSAGE_SEND_ONLY_INCOMPLETE);
        $msgstagedueafter->set_message(0, 'Stage due after incomplete', 'Body 6');
        $msgstagedueafter->save();
        $msgstagedueafterid = $msgstagedueafter->id;
        unset($msgstagedueafter);

        // Stage completion immediate.
        $msgstagecompimmediate = new appraisal_message();
        $msgstagecompimmediate->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msgstagecompimmediate->set_delta(0);
        $msgstagecompimmediate->set_roles($roles);
        $msgstagecompimmediate->set_message(0, 'Stage completion immediate', 'Body 7');
        $msgstagecompimmediate->save();
        $msgstagecompimmediateid = $msgstagecompimmediate->id;
        unset($msgstagecompimmediate);

        // Stage completion after.
        $msgstagecompafter = new appraisal_message();
        $msgstagecompafter->event_stage($map['stages']['Stage'], appraisal_message::EVENT_STAGE_COMPLETE);
        $msgstagecompafter->set_delta(1, appraisal_message::PERIOD_DAY);
        $msgstagecompafter->set_roles($roles);
        $msgstagecompafter->set_message(0, 'Stage completion after', 'Body 8');
        $msgstagecompafter->save();
        $msgstagecompafterid = $msgstagecompafter->id;
        unset($msgstagecompafter);

        $validationstatus = $appraisal->validate();
        $this->assertEmpty($validationstatus[0]);
        $this->assertEmpty($validationstatus[1]);

        // Testing Part 1 - Activate the appraisal. The following should occur:
        // Three appraisal activation immediate messages are sent.
        // The appraisal activation immediate event is marked triggered.
        // The appraisal activation after and stage due (all threee) events should be scheduled.

        // Make sure we are redirecting emails.
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        // Activate the appraisal.
        $appraisal->activate();

        // Record the time after activation has completed. The scheduled times should be relative to a time in between.
        $maxactivationtime = time();

        // Check the emails haven't been sent immediately.
        $emails = $sink->get_messages();
        $this->assertCount(0, $emails);

        // Run scheduled messages code, make sure the activation messages have been sent.
        \appraisal::send_scheduled();
        $emails = $sink->get_messages();
        $this->assertCount(6, $emails);
        $expectedactivation = array('username1@example.com', 'username2@example.com', 'username3@example.com');
        $expectedduedatemsg = array('username1@example.com', 'username2@example.com', 'username3@example.com');
        foreach ($emails as $email) {
            if ($email->subject == 'Appraisal activation immediate') {
                $location = array_search($email->to, $expectedactivation);
                $this->assertIsInt($location);
                unset($expectedactivation[$location]);
            } else if ($email->subject == 'Stage due before') {
                $location = array_search($email->to, $expectedduedatemsg);
                $this->assertIsInt($location);
                unset($expectedduedatemsg[$location]);
            } else {
                $this->assertTrue(false, 'Unexpected Email Type');
            }
        }
        $this->assertEmpty($expectedactivation);
        $this->assertEmpty($expectedduedatemsg);

        // Check that the appraisal activation immediate event has been marked triggered.
        $msgappractivateimmediatetest = new appraisal_message($msgappractivateimmediateid);
        $this->assertEquals(1, $msgappractivateimmediatetest->wastriggered);
        $this->assertGreaterThanOrEqual($minactivationtime, $msgappractivateimmediatetest->timescheduled);
        $this->assertLessThanOrEqual($maxactivationtime, $msgappractivateimmediatetest->timescheduled);
        // Check that the appraisal activation after and stage due events have not been marked triggered and are scheduled.
        $msgappractivateaftertest = new appraisal_message($msgappractivateafterid);
        $this->assertEquals(0, $msgappractivateaftertest->wastriggered);
        $this->assertGreaterThanOrEqual($minactivationtime + DAYSECS, $msgappractivateaftertest->timescheduled);
        $this->assertLessThanOrEqual($maxactivationtime + DAYSECS, $msgappractivateaftertest->timescheduled);
        unset($msgappractivateaftertest);
        $msgstageduebeforetest = new appraisal_message($msgstageduebeforeid);
        $this->assertEquals(1, $msgstageduebeforetest->wastriggered);
        $this->assertGreaterThanOrEqual($minactivationtime, $msgstageduebeforetest->timescheduled);
        $this->assertLessThanOrEqual($maxactivationtime, $msgstageduebeforetest->timescheduled);
        unset($msgstageduebeforetest);
        $msgstagedueimmediatetest = new appraisal_message($msgstagedueimmediateid);
        $this->assertEquals(0, $msgstagedueimmediatetest->wastriggered);
        $this->assertGreaterThanOrEqual($minactivationtime + DAYSECS, $msgstagedueimmediatetest->timescheduled);
        $this->assertLessThanOrEqual($maxactivationtime + DAYSECS, $msgstagedueimmediatetest->timescheduled);
        unset($msgstagedueimmediatetest);
        $msgstagedueaftertest = new appraisal_message($msgstagedueafterid);
        $this->assertEquals(0, $msgstagedueaftertest->wastriggered);
        $this->assertGreaterThanOrEqual($minactivationtime + DAYSECS * 2, $msgstagedueaftertest->timescheduled);
        $this->assertLessThanOrEqual($maxactivationtime + DAYSECS * 2, $msgstagedueaftertest->timescheduled);
        unset($msgstagedueaftertest);

        $sink->close();

        // Check that the stage due before event has been marked triggered.
        $msgstageduebeforetest = new appraisal_message($msgstageduebeforeid);
        $this->assertEquals(1, $msgstageduebeforetest->wastriggered);

        // Testing Part 2 - Run cron after one day. The following should occur:
        // Three appraisal activation after messages are sent.
        // Three stage due immediate messages are sent.
        // The appraisal activation after and stage due immediate events are marked triggered.

        // Make sure we are redirecting emails.
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        // Run the cron, specifying $maxactivationtime plus one day (just enough for those scheduled for one day after).
        \appraisal::send_scheduled($maxactivationtime + DAYSECS);

        // Get the emails that were just sent.
        $emails = $sink->get_messages();
        $this->assertCount(6, $emails);
        $expectedemails = array(
            array('Appraisal activation after', 'username1@example.com'),
            array('Appraisal activation after', 'username2@example.com'),
            array('Appraisal activation after', 'username3@example.com'),
            array('Stage due immediate', 'username1@example.com'),
            array('Stage due immediate', 'username2@example.com'),
            array('Stage due immediate', 'username3@example.com'));
        foreach ($emails as $email) {
            $location = array_search(array($email->subject, $email->to), $expectedemails);
            $this->assertIsInt($location);
            unset($expectedemails[$location]);
        }
        $sink->close();

        // Check that the appraisal activation after and stage due before events have been marked triggered.
        $msgappractivateaftertest = new appraisal_message($msgappractivateafterid);
        $this->assertEquals(1, $msgappractivateaftertest->wastriggered);
        unset($msgappractivateaftertest);
        $msgstageduebeforetest = new appraisal_message($msgstageduebeforeid);
        $this->assertEquals(1, $msgstageduebeforetest->wastriggered);
        unset($msgstageduebeforetest);

        // Testing Part 3 - Complete the stage for one user. The following should occur:
        // One stage complete immediate message is sent.
        // One stage complete after message is scheduled in appraisal_user_event.
        // The stage complete immediate event should NOT be marked triggered.

        // Make sure we are redirecting emails.
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        // Record the time before we trigger.
        $mincompletetime = time();

        // Trigger the stage complete event.
        $roleassignment = appraisal_role_assignment::get_role($appraisal->id, $user1->id, $user1->id, appraisal::ROLE_LEARNER);
        $this->answer_question($appraisal, $roleassignment, 0, 'completestage');

        // Record the time after triggering. The scheduled time should be relative to a time in between.
        $maxcompletetime = time();

        // Get the emails that were just sent.
        $emails = $sink->get_messages();
        $this->assertCount(1, $emails);
        $expectedemails = array(
            array('Stage completion immediate', 'username1@example.com'));
        foreach ($emails as $email) {
            $location = array_search(array($email->subject, $email->to), $expectedemails);
            $this->assertIsInt($location);
            unset($expectedemails[$location]);
        }
        $sink->close();

        // Check that the stage completion immediate is NOT marked triggered.
        $msgstagecompimmediatetest = new appraisal_message($msgstagecompimmediateid);
        $this->assertEquals(0, $msgstagecompimmediatetest->wastriggered);
        unset($msgstagecompimmediatetest);

        // Check that the stage complete after event has been scheduled.
        $msgstagecompaftertest = new appraisal_message($msgstagecompafterid);
        $this->assertEquals(0, $msgstagecompaftertest->wastriggered);
        $this->assertEquals(0, $msgstagecompaftertest->timescheduled);
        $userevents = $DB->get_records('appraisal_user_event');
        $this->assertCount(1, $userevents); // This could fail if other tests add user events, but is unlikely.
        $userevent = reset($userevents);
        $this->assertEquals($user1->id, $userevent->userid);
        $this->assertEquals($msgstagecompafterid, $userevent->eventid);
        $this->assertGreaterThanOrEqual($mincompletetime + DAYSECS, $userevent->timescheduled);
        $this->assertLessThanOrEqual($maxcompletetime + DAYSECS, $userevent->timescheduled);
        unset($msgstagecompaftertest);

        // Testing Part 4 - Run cron after three days. The following should occur:
        // One stage completion after message should be sent and the appraisal_user_event record should be deleted.
        // One stage due after complete message should be sent.
        // Two stage due after incomplete messages should be sent.

        // Make sure we are redirecting emails.
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        // Run scheduled messages code, specifying $maxcompletetime plus three days (enough that all remaining scheduled should be sent).
        \appraisal::send_scheduled($maxactivationtime + DAYSECS * 3);

        // Get the emails that were just sent.
        $emails = $sink->get_messages();
        $this->assertCount(4, $emails);
        $expectedemails = array(
            array('Stage completion after', 'username1@example.com'),
            array('Stage due after complete', 'username1@example.com'),
            array('Stage due after incomplete', 'username2@example.com'),
            array('Stage due after incomplete', 'username3@example.com'));
        foreach ($emails as $email) {
            $location = array_search(array($email->subject, $email->to), $expectedemails);
            $this->assertIsInt($location);
            unset($expectedemails[$location]);
        }
        $sink->close();

        // Check that the stage complete after event schedule has been deleted.
        $userevents = $DB->get_records('appraisal_user_event');
        $this->assertCount(0, $userevents); // This could fail if other tests add user events, but is unlikely.

        // Make sure we are redirecting emails.
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        $closedata = new stdClass();
        $closedata->sendalert = true;
        $closedata->id = $appraisal->id;
        $closedata->alerttitle = 'Appraisal Closure Message Subject';
        $closedata->alertbody_editor = array('text' => 'Appraisal Closure Message Body');
        $appraisal->close($closedata);

        // Check this doesn't happen immediately.
        $emails = $sink->get_messages();
        $this->assertCount(0, $emails);

        // Run the cron, the closure messages should now be sent.
        \appraisal::send_scheduled(time());

        $exptrcpt = array('username2@example.com', 'username3@example.com');
        $emails = $sink->get_messages();
        foreach ($emails as $email) {
            $this->assertTrue(in_array($email->to, $exptrcpt)); // Closure messages don't go to completed users.
            $this->assertEquals('Appraisal Closure Message Subject', $email->subject);
        }

        ini_set('error_log', $oldlog);
    }

    public function test_activated_appraisal_new_appraisee_notification() {
        $this->resetAfterTest();
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        $learner1 = $this->getDataGenerator()->create_user(array('username' => 'learner1'));
        [$appraisal] = $this->prepare_appraisal_with_users([], [$learner1]);

        $roles = [appraisal::ROLE_LEARNER];
        $notification = new appraisal_message();
        $notification->event_appraisal($appraisal->id); // By default this is activation.
        $notification->set_delta(0);
        $notification->set_roles($roles);
        $notification->set_message(0, 'Appraisal activation', 'Body 1');
        $notification->save();

        $validationstatus = $appraisal->validate();
        $this->assertEmpty($validationstatus[0]);
        $this->assertEmpty($validationstatus[1]);

        $appraisal->activate();
        \appraisal::send_scheduled();

        $emails = $sink->get_messages();
        $this->assertCount(1, $emails);
        $this->assertSame('Appraisal activation', $emails[0]->subject, "wrong subject");
        $this->assertSame($learner1->email, $emails[0]->to, "wrong email");
        $sink->clear();

        // Add new appraisees after activation.
        $learner2 = $this->getDataGenerator()->create_user(['username' => 'learner2']);
        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $cohort = $cohortgenerator->create_cohort(['name' => 'newappraisees']);
        $cohortgenerator->cohort_assign_users($cohort->id, [$learner2->id]);
        $this->getDataGenerator()->get_plugin_generator('totara_appraisal')->create_group_assignment($appraisal, 'cohort', $cohort->id);

        // Simulate a cron run.
        $appraisal->check_assignment_changes();

        $emails = $sink->get_messages();
        $this->assertCount(1, $emails);
        $this->assertSame('Appraisal activation', $emails[0]->subject, "wrong subject");
        $this->assertSame($learner2->email, $emails[0]->to, "wrong email");
        $sink->clear();

        $sink->close();
    }

    public function test_appraisal_close() {
        $this->resetAfterTest();
        $sink = $this->redirectEmails();
        $this->setAdminUser();
        /** @var appraisal $appraisal */
        list($appraisal, $users) = $this->prepare_appraisal_with_users();
        $this->assertCount(2, $users);
        $appraisal->validate();
        $appraisal->activate();

        $formdata = new stdClass();
        $formdata->sendalert = true;
        $formdata->id = $appraisal->id;
        $formdata->alerttitle = 'Test alert title';
        $formdata->alertbody_editor['text'] = 'Test alert body text';

        $appraisal->close($formdata);
        appraisal::send_scheduled();

        $emails = $sink->get_messages();
        $this->assertCount(2, $emails);
        $this->assertSame('Test alert title', $emails[0]->subject);
        $this->assertSame('Test alert title', $emails[1]->subject);

        // Check that both emails have the same content.
        // Remove multipart boundary because that's the only expected difference between the two messages.
        preg_match('|\R--(.{0,69})\R|', $emails[0]->body, $matches);
        $boundary = $matches[1];
        $body_0 = str_replace($boundary, '', $emails[0]->body);

        preg_match('|\R--(.{0,69})\R|', $emails[1]->body, $matches);
        $boundary = $matches[1];
        $body_1 = str_replace($boundary, '', $emails[1]->body);

        $this->assertContains('Test alert body text', $body_0);

        // A bug (TL-20258) used to append a contexturl string for each appraisal closure message.
        // Make sure the bug is not re-introduced by checking both bodies are the same and don't contain this text.
        $this->assertSame($body_0, $body_1);
        $this->assertNotContains('More details can be found at', $body_1);

        $sink->clear();
        $sink->close();
    }
}
