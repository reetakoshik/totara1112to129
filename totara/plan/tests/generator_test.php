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
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();

class totara_plan_generator_testcase extends advanced_testcase {
    public function test_create_learning_plan() {
        global $DB, $USER;

        $this->resetAfterTest();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $record = $plangenerator->create_learning_plan();
        $this->assertTrue($DB->record_exists('dp_plan', array('id' => $record->id)));
        $this->assertEquals($USER->id, $record->userid);
        $this->assertEquals(PLAN_CREATE_METHOD_MANUAL, $record->createdby);

        $record = $plangenerator->create_learning_plan(array('userid' => $user2->id, 'createdby' => PLAN_CREATE_METHOD_COHORT));
        $this->assertTrue($DB->record_exists('dp_plan', array('id' => $record->id)));
        $this->assertEquals($user2->id, $record->userid);
        $this->assertEquals(PLAN_CREATE_METHOD_COHORT, $record->createdby);
        $this->assertNotEmpty($record->name);
        $this->assertNotEmpty($record->description);
        $this->assertNotEmpty($record->startdate);
        $this->assertNotEmpty($record->enddate);
        $this->assertEquals(DP_PLAN_STATUS_UNAPPROVED, $record->status);
        $this->assertEquals(1, $record->templateid);

        $now = time();
        $this->setAdminUser();
        $record = $plangenerator->create_learning_plan(array(
            'userid' => $user2->id, 'createdby' => PLAN_CREATE_METHOD_MANUAL, 'name' => 'pokus', 'description' => 'lala',
            'startdate' => $now + 10, 'enddate' => $now + 100, 'templateid' => 666,
        ));
        $this->assertTrue($DB->record_exists('dp_plan', array('id' => $record->id)));
        $this->assertEquals($user2->id, $record->userid);
        $this->assertEquals(PLAN_CREATE_METHOD_MANUAL, $record->createdby);
        $this->assertSame('pokus', $record->name);
        $this->assertSame('lala', $record->description);
        $this->assertEquals($now + 10, $record->startdate);
        $this->assertEquals($now + 100, $record->enddate);
        $this->assertEquals(DP_PLAN_STATUS_UNAPPROVED, $record->status);
        $this->assertEquals(666, $record->templateid);
    }

    public function test_add_learning_plan_competency() {
        global $DB;

        $this->resetAfterTest();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $competencyframework = $hierarchygenerator->create_framework('competency');
        $competency = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency');

        $user = $this->getDataGenerator()->create_user();
        $plan = $plangenerator->create_learning_plan(array('userid' => $user->id));

        $this->setAdminUser(); // Arrgh, this low level API includes access control.
        $sink = $this->redirectMessages();
        $result = $plangenerator->add_learning_plan_competency($plan->id, $competency->id);
        $this->assertTrue($result);
        // The plan has not been approved yet so any additions will not generate an message.
        $this->assertCount(0, $sink->get_messages());
        $sink->close();

        $this->assertTrue($DB->record_exists('dp_plan_competency_assign', array('planid' => $plan->id, 'competencyid' => $competency->id)));
    }

    public function test_create_learning_plan_objective() {
        global $DB, $USER;

        $this->resetAfterTest();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $user = $this->getDataGenerator()->create_user();
        $plan = $plangenerator->create_learning_plan(array('userid' => $user->id));

        $this->setAdminUser();

        $sink = $this->redirectMessages();
        $result = $plangenerator->create_learning_plan_objective($plan->id, $USER->id, null);
        $this->assertCount(1, $sink->get_messages());
        $sink->close();

        $this->assertEquals($plan->id, $result->planid);
        $this->assertNotEmpty($result->fullname);
        $this->assertNull($result->shortname);
        $this->assertNotEmpty($result->description);
        $this->assertEquals(3, $result->priority);
        $this->assertEquals(3, $result->scalevalueid);
        $this->assertNull($result->duedate);
        $this->assertEquals(DP_PLAN_STATUS_APPROVED, $result->approved);
        $this->assertNull($result->reasonfordecision);
        $this->assertEquals(0, $result->manual);
    }

    public function test_create_evidence_type() {
        global $USER;

        $this->resetAfterTest();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $this->setCurrentTimeStart();
        $result = $plangenerator->create_evidence_type();

        $this->assertNotEmpty($result->name);
        $this->assertNotEmpty($result->description);
        $this->assertTimeCurrent($result->timemodified);
        $this->assertEquals($USER->id, $result->usermodified);
        $this->assertNotEmpty($result->sortorder);

        $result = $plangenerator->create_evidence_type(array(
            'name' => 'a', 'description' => 'b', 'timemodified' => 10, 'usermodified' => 99, 'sortorder' => 666,
        ));

        $this->assertSame('a', $result->name);
        $this->assertSame('b', $result->description);
        $this->assertEquals(10, $result->timemodified);
        $this->assertEquals(99, $result->usermodified);
        $this->assertEquals(666, $result->sortorder);
    }

    public function test_create_evidence() {
        global $USER;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $et = $plangenerator->create_evidence_type();

        $this->setCurrentTimeStart();
        $result = $plangenerator->create_evidence(array('evidencetypeid' => $et->id, 'userid' => $user->id));

        $this->assertSame($et->id, $result->evidencetypeid);
        $this->assertSame($user->id, $result->userid);
        $this->assertNotEmpty($result->name);
        $this->assertTimeCurrent($result->timecreated);
        $this->assertTimeCurrent($result->timemodified);
        $this->assertEquals($USER->id, $result->usermodified);
        $this->assertEmpty($result->readonly);

        $result = $plangenerator->create_evidence(array(
            'evidencetypeid' => $et->id, 'userid' => $user->id,
            'name' => 'a', 'timecreated' => 10,
            'timemodified' => 20, 'usermodified' => 99, 'readonly' => 1,
        ));

        $this->assertSame($et->id, $result->evidencetypeid);
        $this->assertSame($user->id, $result->userid);
        $this->assertSame('a', $result->name);
        $this->assertEquals(10, $result->timecreated);
        $this->assertEquals(20, $result->timemodified);
        $this->assertEquals(99, $result->usermodified);
        $this->assertEquals(1, $result->readonly);
    }
}
