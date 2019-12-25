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
 * @author Sam Hemelryk
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/cohort/lib.php');

/**
 * Test assign roles to cohorts.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_cohort_assign_roles_testcase
 *
 */
class totara_cohort_lib_testcase extends advanced_testcase {

    protected $course;
    protected $program;
    protected $cohort_dynamic;
    protected $cohort_set;

    protected function tearDown() {
        $this->course = null;
        $this->program = null;
        $this->cohort_dynamic = null;
        $this->cohort_set = null;
        parent::tearDown();
    }

    public function setUp() {
        global $DB;

        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        /** @var totara_cohort_generator $cohortgen */
        $cohortgen = $generator->get_plugin_generator('totara_cohort');
        /** @var totara_program_generator $programgen */
        $programgen = $generator->get_plugin_generator('totara_program');

        $this->course = $generator->create_course([
            'fullname' => 'Test course fullname',
            'shortname' => 'Test course shortname'
        ]);
        $this->program = $programgen->create_program([
            'fullname' => 'Test program fullname',
            'shortname' => 'Test program shortname'
        ]);
        $this->cohort_dynamic = $generator->create_cohort(['cohorttype' => cohort::TYPE_DYNAMIC]);
        $this->cohort_set = $generator->create_cohort(['cohorttype' => cohort::TYPE_STATIC]);

        $lastnames = ['Anderson', 'Balboa', 'Carlson'];
        $cities = ['Nelson', 'Wellington', 'Brighton', 'San Francisco', 'Sayulita'];
        $countries = ['NZ', 'NZ', 'UK', 'US', 'MX'];
        $setusers = [];
        for ($i = 0; $i < 30; $i++) {
            $user = $generator->create_user([
                'username' => 'user' . $i,
                'firstname' => 'U' . $i,
                'lastname' => $lastnames[$i % 3],
                'email' => 'u' . $i . '.' . strtolower($countries[$i % 5]) . '@example.com',
                'city' => $cities[$i % 5],
                'country' => $countries[$i % 5],
            ]);

            if ($i % 2 === 0) {
                $setusers[] = $user->id;
            }
        }
        // Add users to the static cohort, every second user is a member.
        $cohortgen->cohort_assign_users($this->cohort_set->id, $setusers);

        // Add a rule to the dynamic cohort and update it.
        $ruleset = cohort_rule_create_ruleset($this->cohort_dynamic->draftcollectionid);
        $cohortgen->create_cohort_rule_params($ruleset, 'user', 'country', array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('NZ', 'MX'));
        cohort_rules_approve_changes($this->cohort_dynamic);

        // Now check that they contain the number of users we expect.
        $this->assertSame(15, $DB->count_records('cohort_members', array('cohortid' => $this->cohort_set->id)));
        $this->assertSame(18, $DB->count_records('cohort_members', array('cohortid' => $this->cohort_dynamic->id)));
    }

    /**
     * Tests the totara_cohort_install function.
     *
     * Currently this function does nothing but returns true.
     * We'll test for that, if you ever find this failing make sure you update the tests!
     */
    public function test_totara_cohort_install() {
        $this->assertTrue(totara_cohort_install());
    }

    /**
     * Test updating course/program audience based visibility.
     */
    public function test_totara_cohort_update_audience_visibility() {
        global $CFG, $DB;

        $this->assertEmpty(get_config(null, 'audiencevisibility'));
        $this->assertEmpty($CFG->audiencevisibility);

        // Audience based visibility is off by default, test that we get back false.
        $this->assertFalse(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_COURSE, $this->course->id, COHORT_VISIBLE_NOUSERS)
        );

        // Turn on audience based visibility.
        set_config('audiencevisibility', 1);
        $CFG->audiencevisibility = true;
        $this->assertNotEmpty(get_config(null, 'audiencevisibility'));
        $this->assertNotEmpty($CFG->audiencevisibility);

        // Test changing course visibility to NOUSERS.
        $this->assertSame((string)COHORT_VISIBLE_ALL, $DB->get_field('course', 'audiencevisible', array('id' => $this->course->id)));
        $this->assertTrue(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_COURSE, $this->course->id, COHORT_VISIBLE_NOUSERS)
        );
        $this->assertSame((string)COHORT_VISIBLE_NOUSERS, $DB->get_field('course', 'audiencevisible', array('id' => $this->course->id)));

        // Test changing program visibility to NOUSERS.
        $this->assertSame((string)COHORT_VISIBLE_ALL, $DB->get_field('prog', 'audiencevisible', array('id' => $this->program->id)));
        $this->assertTrue(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_PROGRAM, $this->program->id, COHORT_VISIBLE_NOUSERS)
        );
        $this->assertSame((string)COHORT_VISIBLE_NOUSERS, $DB->get_field('prog', 'audiencevisible', array('id' => $this->program->id)));

        // Test with an out of range visibility value.
        $this->assertFalse(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_PROGRAM, $this->program->id, -1)
        );
        $this->assertFalse(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_PROGRAM, $this->program->id, 100)
        );
        $this->assertFalse(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_PROGRAM, $this->program->id, null)
        );
        $this->assertFalse(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_PROGRAM, $this->program->id, '')
        );

        // Test the poor matching on false.
        // Luckily this falls back to enrolled which is OK behaviour.
        $this->assertSame((string)COHORT_VISIBLE_NOUSERS, $DB->get_field('prog', 'audiencevisible', array('id' => $this->program->id)));
        $this->assertTrue(
            totara_cohort_update_audience_visibility(COHORT_ASSN_ITEMTYPE_PROGRAM, $this->program->id, false)
        );
        $this->assertSame((string)COHORT_VISIBLE_ENROLLED, $DB->get_field('prog', 'audiencevisible', array('id' => $this->program->id)));
    }

    /**
     * Tests totara_cohort_get_associations
     * Tests totara_cohort_add_association
     * Tests totara_cohort_delete_association
     */
    public function test_totara_cohort_associations_on_courses() {
        global $DB;

        // Test that we have absolutely no cohort associations by default.
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_ENROLLED));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_PERMITTED));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_dynamic->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_dynamic->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_ENROLLED));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_dynamic->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_PERMITTED));

        // Now add the course as a visible association to the set cohort.
        $sink = $this->redirectEvents();
        $return = totara_cohort_add_association($this->cohort_set->id, $this->course->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
        // Check that the return value matches an cohort_visibility record.
        $this->assertIsInt($return);
        $this->assertTrue($return > 0);
        $record = $DB->get_record('cohort_visibility', array('id' => $return), '*', MUST_EXIST);
        $this->assertEquals($this->cohort_set->id, $record->cohortid);
        $this->assertEquals($this->course->id, $record->instanceid);
        $this->assertEquals(COHORT_ASSN_ITEMTYPE_COURSE, $record->instancetype);
        // Check that we got the one expected event.
        $this->assertSame(1, $sink->count());
        $events = $sink->get_events();
        /** @var totara_cohort\event\visible_learning_item_added $event */
        $event = reset($events);
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\visible_learning_item_added', $event);
        $this->assertSame('added', $eventdata['action']);
        $this->assertSame($this->cohort_set->id, $eventdata['other']['cohortid']);
        $this->assertSame($return, $eventdata['objectid']);
        $this->assertSame('cohort_visibility', $eventdata['objecttable']);
        // Check that if we get back the expected associations now.
        $expected = totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
        $this->assertCount(1, $expected);
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_ENROLLED));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_PERMITTED));
        $record = reset($expected);
        $this->assertEquals($this->course->id, $record->instanceid);
        $this->assertEquals('course', $record->type); // Hardoded - ick!
        $this->assertSame($this->course->fullname, $record->fullname); // WTF? ick!
        $sink->clear();

        // Check if we try to add the course a second time we just get true.
        $this->assertTrue(
            totara_cohort_add_association($this->cohort_set->id, $this->course->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE)
        );
        // Check that there are no new events.
        $this->assertSame(0, $sink->count());

        // Now add the course as enrolled learning.
        $return = totara_cohort_add_association($this->cohort_set->id, $this->course->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_ENROLLED);
        // Check that we got back an enrolment id.
        $this->assertIsInt($return);
        $this->assertTrue($return > 0);
        $enrolinstance = $DB->get_record('enrol', array('id' => $return), '*', MUST_EXIST);
        $this->assertSame('cohort', $enrolinstance->enrol);
        $this->assertSame((string)ENROL_INSTANCE_ENABLED, $enrolinstance->status);
        $this->assertSame((string)$this->course->id, $enrolinstance->courseid);

        $expectedeventsaftersync = [
            'totara_core\event\bulk_enrolments_started',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'core\event\user_enrolment_created',
            'totara_core\event\bulk_enrolments_ended',
            'totara_core\event\bulk_role_assignments_started',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'core\event\role_assigned',
            'totara_core\event\bulk_role_assignments_ended',
        ];
        $expected = [
            'core\event\enrol_instance_created',
            'totara_cohort\event\enrolled_course_item_added',
        ];
        $this->assertSame(count($expected), $sink->count());
        $events = $sink->get_events();
        foreach ($events as $event) {
            $this->assertInstanceOf(array_shift($expected), $event);
        }

        // user enrolments is now part of an adhoc task.
        $sink->clear();
        phpunit_util::run_all_adhoc_tasks();
        $this->assertSame(count($expectedeventsaftersync), $sink->count());
        $eventsaftersync = $sink->get_events();
        foreach ($eventsaftersync as $event) {
            $this->assertInstanceOf(array_shift($expectedeventsaftersync), $event);
        }

        $event = array_shift($events);
        $eventdata = $event->get_data();
        $this->assertInstanceOf('core\event\enrol_instance_created', $event);
        $this->assertSame('created' , $eventdata['action']);
        $this->assertSame('enrol_instance' , $eventdata['target']);
        $this->assertEquals($return, $eventdata['objectid']);
        $this->assertSame('enrol' , $eventdata['objecttable']);
        $this->assertEquals($this->course->id, $eventdata['contextinstanceid']);
        $this->assertEquals('cohort', $eventdata['other']['enrol']);
        $event = array_pop($events);
        $eventdata = $event->get_data();
        $this->assertInstanceOf('totara_cohort\event\enrolled_course_item_added', $event);
        $this->assertSame('added' , $eventdata['action']);
        $this->assertSame('enrolled_course_item' , $eventdata['target']);
        $this->assertEquals($return, $eventdata['objectid']);
        $this->assertSame('enrol' , $eventdata['objecttable']);
        $this->assertEquals($this->cohort_set->id, $eventdata['other']['cohortid']);
        // Check that if we get back the expected associations now.
        $expectedvisible = totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
        $expectedenrolled = totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_ENROLLED);
        $expectedpermitted = totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_PERMITTED);
        $this->assertCount(1, $expectedvisible);
        $this->assertCount(1, $expectedenrolled);
        $this->assertCount(1, $expectedpermitted);
        $record = reset($expectedvisible);
        $this->assertEquals($this->course->id, $record->instanceid);
        $this->assertEquals('course', $record->type); // Hardoded - ick!
        $this->assertSame($this->course->fullname, $record->fullname); // WTF? ick!
        $record = reset($expectedenrolled);
        $this->assertEquals($this->course->id, $record->instanceid);
        $this->assertEquals('course', $record->type); // Hardoded - ick!
        $this->assertSame($this->course->fullname, $record->fullname); // WTF? ick!
        $record = reset($expectedpermitted);
        $this->assertEquals($this->course->id, $record->instanceid);
        $this->assertEquals('course', $record->type); // Hardoded - ick!
        $this->assertSame($this->course->fullname, $record->fullname); // WTF? ick!
    }

    /**
     * Tests totara_cohort_get_associations
     * Tests totara_cohort_add_association
     * Tests totara_cohort_delete_association
     */
    public function test_totara_cohort_associations_on_programs() {
        global $DB;

        // Test that we have absolutely no cohort associations by default.
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_VISIBLE));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_ENROLLED));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_set->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_PERMITTED));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_dynamic->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_VISIBLE));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_dynamic->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_ENROLLED));
        $this->assertCount(0, totara_cohort_get_associations($this->cohort_dynamic->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_PERMITTED));
    }

}
