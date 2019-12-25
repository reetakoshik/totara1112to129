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

class totara_plan_events_testcase extends advanced_testcase {
    public function test_plan_events() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $this->setAdminUser();

        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        $event = \totara_plan\event\plan_created::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('c', $event->crud);

        $event = \totara_plan\event\plan_updated::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\plan_viewed::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('r', $event->crud);

        $event = \totara_plan\event\plan_list_viewed::create_from_userid($user->id);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('r', $event->crud);

        $event = \totara_plan\event\plan_completed::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\plan_deleted::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('d', $event->crud);

        $event = \totara_plan\event\plan_reactivated::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);
    }

    public function test_component_events() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $competencyframework = $hierarchygenerator->create_framework('competency');
        $competency = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency');

        $this->setAdminUser();

        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $sink = $this->redirectMessages();
        $plangenerator->add_learning_plan_competency($planrecord->id, $competency->id);
        $sink->close();

        $plan = new development_plan($planrecord->id);

        $event = \totara_plan\event\component_created::create_from_component($plan, 'competency', $competency->id, $competency->fullname);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('c', $event->crud);

        $event = \totara_plan\event\component_updated::create_from_component($plan, 'competency', $competency->id, $competency->fullname);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\component_deleted::create_from_component($plan, 'competency', $competency->id, $competency->fullname);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('d', $event->crud);
    }

    public function test_approval_events() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $competencyframework = $hierarchygenerator->create_framework('competency');
        $competency = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency');

        $this->setAdminUser();

        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id, 'status' => DP_PLAN_STATUS_PENDING));
        $plangenerator->add_learning_plan_competency($planrecord->id, $competency->id);

        $plan = new development_plan($planrecord->id);

        $event = \totara_plan\event\approval_requested::create_from_component($plan, 'competency', $competency->id, $competency->fullname);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\approval_requested::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\approval_approved::create_from_component($plan, 'competency', $competency->id, $competency->fullname);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\approval_approved::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\approval_declined::create_from_component($plan, 'competency', $competency->id, $competency->fullname);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\approval_declined::create_from_plan($plan);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);
    }

    public function test_evidence_events() {
        $this->resetAfterTest();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $user = $this->getDataGenerator()->create_user();
        $evidencetype = $plangenerator->create_evidence_type();
        $evidence = $plangenerator->create_evidence(array('evidencetypeid' => $evidencetype->id, 'userid' => $user->id));

        $event = \totara_plan\event\evidence_type_created::create_from_type($evidencetype);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('c', $event->crud);

        $event = \totara_plan\event\evidence_type_updated::create_from_type($evidencetype);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\evidence_type_deleted::create_from_type($evidencetype);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNull($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('d', $event->crud);

        $event = \totara_plan\event\evidence_created::create_from_instance($evidence);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('c', $event->crud);

        $event = \totara_plan\event\evidence_updated::create_from_instance($evidence);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\evidence_deleted::create_from_instance($evidence);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('d', $event->crud);
    }

    public function test_objective_scale_events() {
        global $DB;
        $this->resetAfterTest();

        $scale = $DB->get_record('dp_objective_scale', array('id' => 1), '*', MUST_EXIST);

        $event = \totara_plan\event\objective_scale_created::create_from_scale($scale);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('c', $event->crud);

        $event = \totara_plan\event\objective_scale_updated::create_from_scale($scale);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $DB->delete_records('dp_objective_scale', array('id' => 1));
        $event = \totara_plan\event\objective_scale_deleted::create_from_scale($scale);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNull($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('d', $event->crud);
    }

    public function test_priority_scale_events() {
        global $DB;
        $this->resetAfterTest();

        $scale = $DB->get_record('dp_priority_scale', array('id' => 1), '*', MUST_EXIST);

        $event = \totara_plan\event\priority_scale_created::create_from_scale($scale);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('c', $event->crud);

        $event = \totara_plan\event\priority_scale_updated::create_from_scale($scale);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $DB->delete_records('dp_priority_scale', array('id' => 1));
        $event = \totara_plan\event\priority_scale_deleted::create_from_scale($scale);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNull($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('d', $event->crud);
    }

    public function test_priority_template_events() {
        global $DB;
        $this->resetAfterTest();

        $template = $DB->get_record('dp_template', array('id' => 1), '*', MUST_EXIST);

        $event = \totara_plan\event\template_updated::create_from_template($template);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);

        $event = \totara_plan\event\template_updated::create_from_template($template, 'course');
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertEquals('u', $event->crud);
    }
}
