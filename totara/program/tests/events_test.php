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
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Test events in programs.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_program_events_testcase
 *
 */
class totara_program_events_testcase extends advanced_testcase {

    /** @var totara_program_generator */
    private $program_generator = null;
    /** @var program */
    private $program = null;
    private $user = null;

    protected function tearDown() {
        $this->program_generator = null;
        $this->program = null;
        $this->user = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
        $this->program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->program = $this->program_generator->create_program(array('fullname' => 'program1'));
        $this->user = $this->getDataGenerator()->create_user(array('fullname' => 'user1'));
    }

    public function test_program_assigned() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $event = \totara_program\event\program_assigned::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
            )
        );
        $event->trigger();

        $this->assertSame('prog_assignment', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('c', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(null, $event->other);
    }

    public function test_program_assignmentsupdated() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $assignments = $this->program->get_assignments();
        $eventdata = array();
        foreach ($assignments as $assignment) {
            $eventdata[] = (array) $assignment;
        }

        $other = array('assignments' => $eventdata);
        $event = \totara_program\event\program_assignmentsupdated::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
                'other' => $other,
            )
        );
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_completed() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $other = array('certifid' => isset($this->program->certifid) ? $this->program->certifid : 0);
        $event = \totara_program\event\program_completed::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
                'other' => $other,
            )
        );
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_completionstateedited() {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $other = array(
            'oldstate' => STATUS_PROGRAM_INCOMPLETE,
            'newstate' => STATUS_PROGRAM_COMPLETE,
            'changedby' => $USER->id
        );
        $event = \totara_program\event\program_completionstateedited::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
                'other' => $other,
            )
        );
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_contentupdated() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $programcontent = $this->program->get_content();
        $coursesetids = array();
        $coursesets = $programcontent->get_course_sets();
        foreach ($coursesets as $courseset) {
            $coursesetids[] = $courseset->id;
        }

        $other = array('coursesets' => $coursesetids);
        $dataevent = array('id' => $this->program->id, 'other' => $other);
        $event = \totara_program\event\program_contentupdated::create_from_data($dataevent);
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_courseset_completed() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add coursesets for program. I cannot use the add_courseset_to_program as it has become too slow to work with.
        $course = $this->getDataGenerator()->create_course(array('fullname' => 'course1'));
        $prog_courseset = array('id' => 1, 'programid' => $this->program->id, 'completiontype' => 1,
            'contenttype' => 1, 'certifpath' => 1);
        $prog_courseset_course = array('id' => 1, 'coursesetid' => 1, 'courseid' => $course->id);

        $this->loadDataSet($this->createArrayDataset(
            array(
                'prog_courseset' => array($prog_courseset),
                'prog_courseset_course' => array($prog_courseset_course)
            )
        ));

        $other = array('coursesetid' => $prog_courseset_course['coursesetid'], 'certifid' => 0);
        $event = \totara_program\event\program_courseset_completed::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
                'other' => $other
            )
        );
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_created() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $other = array('certifid' => 0);
        $event = \totara_program\event\program_created::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
                'other' => $other
            )
        );
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('c', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_deleted() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $other = array('certifid' => empty($this->program->certifid) ? 0 : $this->program->certifid);
        $event = \totara_program\event\program_deleted::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
                'other' => $other
            )
        );
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('d', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_unassigned() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $event = \totara_program\event\program_unassigned::create(
            array(
                'objectid' => $this->program->id,
                'context' => context_program::instance($this->program->id),
                'userid' => $this->user->id,
            )
        );
        $event->trigger();

        $this->assertSame('prog_assignment', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('d', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(null, $event->other);
    }

    public function test_program_updated() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $other = array('certifid' => empty($this->program->certifid) ? 0 : $this->program->certifid);
        $dataevent = array('id' => $this->program->id, 'other' => $other);
        $event = \totara_program\event\program_updated::create_from_data($dataevent);
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_program_viewed() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $other = array('section' => 'assignments');
        $dataevent = array('id' => $this->program->id, 'other' => $other);
        $event = \totara_program\event\program_viewed::create_from_data($dataevent);
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('r', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);

    }

    public function test_bulk_learner_assignments() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a cohort and add it as assignment.
        $cohort = $this->getDataGenerator()->create_cohort();
        $this->program_generator->assign_to_program($this->program->id, ASSIGNTYPE_COHORT, $cohort->id);

        // Get assignment.
        $assign = $DB->get_record('prog_assignment', array('programid' => $this->program->id));

        // Trigger event.
        $other = array('programid' => $this->program->id, 'assignmentid' => $assign->id);
        $event = \totara_program\event\bulk_learner_assignments_started::create_from_data(array('other' => $other));
        $event->trigger();
        $users = array($this->user->id => array('timedue' => 14245262, 'exceptions' => 0));
        $this->program->assign_learners_bulk($users, $assign);
        \totara_program\event\bulk_learner_assignments_ended::create()->trigger();

        $this->assertSame(null, $event->objecttable);
        $this->assertSame(null, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);

        // Final test is with an empty array of users to ensure that nothing has changed.
        $initialcount = $DB->count_records('prog_user_assignment');
        $this->assertNull($this->program->assign_learners_bulk([], $assign));
        $this->assertSame($initialcount, $DB->count_records('prog_user_assignment'));
    }

    public function test_bulk_future_assignments() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $cohort = $this->getDataGenerator()->create_cohort();
        $this->program_generator->assign_to_program($this->program->id, ASSIGNTYPE_COHORT, $cohort->id);

        // Get assignment.
        $assign = $DB->get_record('prog_assignment', array('programid' => $this->program->id));

        $other = array('programid' => $this->program->id, 'assignmentid' => $assign->id);
        $event = \totara_program\event\bulk_future_assignments_started::create_from_data(array('other' => $other));
        $event->trigger();
        $this->program->create_future_assignments_bulk($this->program->id, array($this->user->id), $assign->id);
        \totara_program\event\bulk_future_assignments_ended::create()->trigger();

        $this->assertSame(null, $event->objecttable);
        $this->assertSame(null, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame($other, $event->other);
    }

    public function test_update_messages() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $event = \totara_program\event\update_messages::create_from_instance($this->program);
        $event->trigger();

        $this->assertSame('prog', $event->objecttable);
        $this->assertSame($this->program->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
    }
}
