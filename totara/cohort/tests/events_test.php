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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

/**
 * Test cohort events.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_cohort_events_testcase
 *
 */
class totara_cohort_events_testcase extends advanced_testcase {

    private $cohort_generator = null;
    private $program_generator = null;
    private $cohort = null;

    protected function tearDown() {
        $this->cohort_generator = null;
        $this->program_generator = null;
        $this->cohort = null;
        parent::tearDown();
    }

    /**
     * SetUp.
     */
    public function setUp() {
        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $this->program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
    }

    /**
     * Test draft events(save and discard).
     */
    public function test_draft_events() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        // Modify cohort and approve changes.
        $ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);

        $sink->clear();
        cohort_rules_approve_changes($this->cohort);

        $events = $sink->get_events();
        $this->assertCount(2, $events);
        $event = $events[0];
        $this->assertInstanceOf('totara_cohort\event\draftcollection_saved', $event);
        $this->assertEventContextNotUsed($event);
        $eventdata = $event->get_data();
        $this->assertEquals('cohort', $eventdata['objecttable']);
        $this->assertEquals($this->cohort->id, $eventdata['objectid']);
        $this->assertEquals('u', $eventdata['crud']);
        $this->assertSame(null, $eventdata['other']);
        $event = $events[1];
        $this->assertInstanceOf('totara_cohort\event\members_updated', $event);

        // Modify cohort again and cancel changes.
        $cohort = $DB->get_record('cohort', array('id' => $this->cohort->id));
        $this->cohort_generator->create_cohort_rule_params($ruleset, 'user', 'idnumber', array('equal' => 0), array('02'));
        $sink->clear();
        cohort_rules_cancel_changes($cohort);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = $events[0];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\draftcollection_discarded', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('cohort', $eventdata['objecttable']);
        $this->assertEquals($cohort->id, $eventdata['objectid']);
        $this->assertEquals('u', $eventdata['crud']);
        $this->assertSame(null, $eventdata['other']);
    }

    /**
     * Test enrolled course item events(add and delete).
     */
    public function test_enrolled_course_item_events() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        // Create course and associate it with the cohort.
        $course = $this->getDataGenerator()->create_course(array('fullname' => 'course'));
        $cohortid = $this->cohort->id;
        $value = COHORT_ASSN_VALUE_ENROLLED;
        $sink->clear();

        // Enrol course item.
        $assncourseid = totara_cohort_add_association($cohortid, $course->id, COHORT_ASSN_ITEMTYPE_COURSE, $value);

        // Assertions.
        $events = $sink->get_events();
        $this->assertCount(2, $events);
        $this->assertInstanceOf('core\event\enrol_instance_created', $events[0]);
        $event = $events[1];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\enrolled_course_item_added', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('enrol', $eventdata['objecttable']);
        $this->assertEquals($assncourseid, $eventdata['objectid']);
        $this->assertEquals('c', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);

        // Delete course item.
        $sink->clear();
        totara_cohort_delete_association($cohortid, $assncourseid, COHORT_ASSN_ITEMTYPE_COURSE, $value);
        $events = $sink->get_events();
        $this->assertCount(2, $events);
        $this->assertInstanceOf('core\event\enrol_instance_deleted', $events[0]);
        $event = $events[1];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\enrolled_course_item_deleted', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('enrol', $eventdata['objecttable']);
        $this->assertEquals($assncourseid, $eventdata['objectid']);
        $this->assertEquals('d', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);
        $sink->close();
    }

    /**
     * Test enrolled program item events(add and delete).
     */
    public function test_enrolled_program_item_events() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        // Create program and associate it with the cohort.
        $program = $this->program_generator->create_program(array('fullname' => 'program'));
        $cohortid = $this->cohort->id;
        $value = COHORT_ASSN_VALUE_ENROLLED;
        $sink->clear();

        // Enrol program item.
        $assnprogid = totara_cohort_add_association($cohortid, $program->id, COHORT_ASSN_ITEMTYPE_PROGRAM, $value);

        // Assertions.
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = $events[0];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\enrolled_program_item_added', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('prog_assignment', $eventdata['objecttable']);
        $this->assertEquals($assnprogid, $eventdata['objectid']);
        $this->assertEquals('c', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);

        // Delete program item.
        $sink->clear();
        totara_cohort_delete_association($cohortid, $assnprogid, COHORT_ASSN_ITEMTYPE_PROGRAM, $value);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = $events[0];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\enrolled_program_item_deleted', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertSame('prog_assignment', $eventdata['objecttable']);
        $this->assertSame($assnprogid, $eventdata['objectid']);
        $this->assertSame('d', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);
        $sink->close();
    }

    /**
     * Test enrolled certification item events(add and delete).
     */
    public function test_enrolled_certification_item_events() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        // Create certification and associate it with the cohort.
        $certification = $this->program_generator->create_program(array('fullname' => 'certification'));
        $cohortid = $this->cohort->id;
        $value = COHORT_ASSN_VALUE_ENROLLED;
        $sink->clear();

        // Enrol certification item.
        $assncertifid = totara_cohort_add_association($cohortid, $certification->id, COHORT_ASSN_ITEMTYPE_CERTIF, $value);

        // Assertions.
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = $events[0];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\enrolled_program_item_added', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('prog_assignment', $eventdata['objecttable']);
        $this->assertEquals($assncertifid, $eventdata['objectid']);
        $this->assertEquals('c', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);

        // Delete certification item.
        $sink->clear();
        totara_cohort_delete_association($cohortid, $assncertifid, COHORT_ASSN_ITEMTYPE_CERTIF, $value);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = $events[0];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\enrolled_program_item_deleted', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('prog_assignment', $eventdata['objecttable']);
        $this->assertEquals($assncertifid, $eventdata['objectid']);
        $this->assertEquals('d', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);
        $sink->close();
    }

    /**
     * Test cohort update operator event.
     */
    public function test_cohort_update_operator_event() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        $cohortid = $this->cohort->id;
        $sink->clear();
        totara_cohort_update_operator($cohortid, $cohortid, COHORT_OPERATOR_TYPE_COHORT, COHORT_RULES_OP_OR);

        $events = $sink->get_events();
        $event = $events[0]->get_data();
        $other = array('cohortid' => $cohortid, 'value' => COHORT_RULES_OP_OR);
        $this->assertEquals('cohort_rule_collections', $event['objecttable']);
        $this->assertEquals($this->cohort->draftcollectionid, $event['objectid']);
        $this->assertEquals('u', $event['crud']);
        $this->assertSame($other, $event['other']);
        $sink->close();
    }

    /**
     * Test rule events (create, update and delete).
     */
    public function test_rule_events() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        $rulesetid = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $sink->clear();

        // Trigger creation event.
        $ruleid = cohort_rule_create_rule($rulesetid, 'user', 'idnumber');
        $ruleobj = $DB->get_record('cohort_rules', array('id' => $ruleid));
        $other = array('cohortid'  => $this->cohort->id);

        $events = $sink->get_events();
        $event = $events[0]->get_data();
        $this->assertEquals('cohort_rules', $event['objecttable']);
        $this->assertEquals($ruleid, $event['objectid']);
        $this->assertEquals('c', $event['crud']);
        $this->assertSame($other, $event['other']);
        $sink->close();

        // Trigger update event (manually as it is not executed inside a function).
        $event = \totara_cohort\event\rule_updated::create_from_instance($ruleobj, $this->cohort);
        $event->trigger();

        $this->assertEquals('cohort_rules', $event->objecttable);
        $this->assertEquals($ruleid, $event->objectid);
        $this->assertEquals('u', $event->crud);
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);

        // Trigger delete event (manually as it is not executed inside a function).
        $event = \totara_cohort\event\rule_deleted::create_from_instance($ruleobj, $this->cohort);
        $event->trigger();

        $this->assertEquals('cohort_rules', $event->objecttable);
        $this->assertEquals($ruleid, $event->objectid);
        $this->assertEquals('d', $event->crud);
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    /**
     * Test rule param delete event.
     */
    public function test_rule_param_delete_event() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        // Create a couple of programs for the rule.
        $program1 = $this->program_generator->create_program(array('fullname' => 'program1'));
        $program2 = $this->program_generator->create_program(array('fullname' => 'program2'));

        $rulesetid = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
        $type = 'learning';
        $name = 'programcompletionlist';
        $params = array('operator' => COHORT_RULE_COMPLETION_OP_NONE);
        $listofids = array($program1->id, $program2->id);
        $this->cohort_generator->create_cohort_rule_params($rulesetid, $type, $name, $params, $listofids, 'listofids');

        $ruleparams = $DB->get_records_sql(
            'SELECT crp.*
             FROM {cohort_rule_params} crp
             LEFT JOIN {cohort_rules} cr ON crp.ruleid = cr.id
             WHERE cr.rulesetid = ? AND crp.name = ?', array($rulesetid, 'listofids'));

        $this->assertEquals(2, count($ruleparams));
        $ruleparam = reset($ruleparams);
        $sink->clear();

        cohort_delete_param($ruleparam);
        $events = $sink->get_events();
        $event = $events[0]->get_data();
        $other = array('cohortid' => $this->cohort->id, 'ruleid' => $ruleparam->ruleid);
        $this->assertEquals('cohort_rule_params', $event['objecttable']);
        $this->assertEquals($ruleparam->id, $event['objectid']);
        $this->assertEquals('d', $event['crud']);
        $this->assertSame($other, $event['other']);
        $sink->close();
    }

    /**
     * Test ruleset events (create and operator update)
     */
    public function test_ruleset_events() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        // Trigger creation event.
        $rulesetid = cohort_rule_create_ruleset($this->cohort->draftcollectionid);

        $this->cohort_generator->create_cohort_rule_params($rulesetid, 'user', 'idnumber', array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('001'));
        $this->cohort_generator->create_cohort_rule_params($rulesetid, 'user', 'username', array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('001'));

        $events = $sink->get_events();
        $event = $events[0]->get_data();
        $this->assertEquals('cohort_rulesets', $event['objecttable']);
        $this->assertEquals($rulesetid, $event['objectid']);
        $this->assertEquals('c', $event['crud']);
        $this->assertSame(array('cohortid' => $this->cohort->id), $event['other']);

        // Trigger ruleset operator updated event.
        $sink->clear();
        totara_cohort_update_operator($this->cohort->id, $rulesetid, COHORT_OPERATOR_TYPE_RULESET, COHORT_RULES_OP_OR);

        $events = $sink->get_events();
        $event = $events[0]->get_data();
        $other = array('cohortid' => $this->cohort->id, 'value' => COHORT_RULES_OP_OR);
        $this->assertEquals('cohort_rulesets', $event['objecttable']);
        $this->assertEquals($rulesetid, $event['objectid']);
        $this->assertEquals('u', $event['crud']);
        $this->assertSame($other, $event['other']);

        // Trigger delete ruleset event.
        $ruleset = $DB->get_record('cohort_rulesets', array('id' => $rulesetid));
        $event = \totara_cohort\event\ruleset_deleted::create_from_instance($ruleset, $this->cohort);
        $event->add_record_snapshot('cohort_rulesets', $ruleset);
        $event->trigger();

        $this->assertEquals('cohort_rulesets', $event->objecttable);
        $this->assertEquals($rulesetid, $event->objectid);
        $this->assertEquals('d', $event->crud);
        $this->assertEquals($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(array('cohortid' => $this->cohort->id), $event->other);
        $sink->close();
    }

    /**
     * Test visible learning items added.
     */
    public function test_visible_learning_events() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        $cohortid = $this->cohort->id;
        $program = $this->program_generator->create_program(array('fullname' => 'program'));

        // Create association.
        $assid = totara_cohort_add_association($cohortid, $program->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_VISIBLE);

        $events = $sink->get_events();
        $this->assertCount(2, $events);

        foreach ($events as $event) {
            if (get_class($event) == 'totara_cohort\event\visible_learning_item_added') {
                break;
            }
        }

        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\visible_learning_item_added', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('cohort_visibility', $eventdata['objecttable']);
        $this->assertEquals($assid, $eventdata['objectid']);
        $this->assertEquals('c', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);
        $sink->clear();

        // Delete association.
        totara_cohort_delete_association($cohortid, $assid, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_VISIBLE);

        $events = $sink->get_events();
        $event = $events[0];
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\visible_learning_item_deleted', $event);
        $this->assertEventContextNotUsed($event);
        $this->assertEquals('cohort_visibility', $eventdata['objecttable']);
        $this->assertEquals($assid, $eventdata['objectid']);
        $this->assertEquals('d', $eventdata['crud']);
        $this->assertSame(array('cohortid' => $cohortid), $eventdata['other']);
        $sink->close();
    }
}
