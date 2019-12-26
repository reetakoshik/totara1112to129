<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralms.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/classes/observer.php');

/**
 * Class totara_program_observer_testcase
 *
 * Tests functions found within the totara_program_observer class.
 */
class totara_program_observer_testcase extends reportcache_advanced_testcase {

    /** @var totara_reportbuilder_cache_generator */
    private $data_generator;

    /** @var stdClass */
    private $course1, $course2, $course3, $course4, $course5;

    /** @var program */
    private $program1, $program2;

    protected function tearDown() {
        $this->data_generator = null;
        $this->course1 = $this->course2 = $this->course3 = $this->course4 = $this->course5 = null;
        $this->program1 = $this->program2 = null;

        parent::tearDown();
    }

    public function setUp() {
        $this->resetAfterTest(true);
        parent::setUp();
        global $DB;

        $this->data_generator = $this->getDataGenerator();

        $this->course1 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course2 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course3 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course4 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course5 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->program1 = $this->data_generator->create_program();
        $this->program2 = $this->data_generator->create_program();

        // Reload courses. Otherwise when we compare the courses with the returned courses,
        // we get subtle differences in some values such as cacherev and sortorder.
        // Todo: Investigate whether we can improve the generator to fix this.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
        $this->course4 = $DB->get_record('course', array('id' => $this->course4->id));
        $this->course5 = $DB->get_record('course', array('id' => $this->course5->id));
    }

    public function reload_course($course) {
        global $DB;
        return $DB->get_record('course', array('id' => $course->id));
    }

    /**
     * Test that the results of the course_deleted static function in the totara_program_observer
     * deletes the correct records and only the correct records.
     *
     * This creates several course sets across 2 programs and then triggers the course_deleted event
     * for one course.
     */
    public function test_course_deleted() {
        $this->resetAfterTest(true);
        global $DB;

        // Set up program1.

        $progcontent1 = new prog_content($this->program1->id);
        $progcontent1->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent1->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent1->add_set(CONTENTTYPE_COMPETENCY);

        /** @var course_set[] $coursesets */
        $coursesets = $progcontent1->get_course_sets();

        // For program 1, it will have 3 course sets:
        // Course set 1: Multi-course set with course1 only.
        // Course set 2: Multi-course set with course1 and course2.
        // Course set 3: Competency course set where the competency has course 3 linked.

        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent1->add_course(1, $coursedata);

        $progcontent1->add_course(2, $coursedata);
        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent1->add_course(2, $coursedata);

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->data_generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for course 3 will be assigned to this competency.
        $course3evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course3);

        // Add a competency to the competency courseset.
        $compdata = new stdClass();
        $compdata->{$coursesets[2]->get_set_prefix() . 'competencyid'} = $competency->id;
        $progcontent1->add_competency(3, $compdata);

        $progcontent1->save_content();

        // Set up program2.

        $progcontent2 = new prog_content($this->program2->id);
        $progcontent2->add_set(CONTENTTYPE_RECURRING);

        /** @var course_set[] $coursesets */
        $coursesets = $progcontent2->get_course_sets();

        // Program2 contains a single recurring course set with course1.

        $coursesets[0]->course = $this->course1;
        $progcontent2->save_content();

        // Multi course set which contains course1.
        $prog1courseset1 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 1));
        // Multi course set which contains course1 and course2.
        $prog1courseset2 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 2));
        // Competency course set which contains competency 1 which links to course3.
        $prog1courseset3 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 3));
        // Recurring course which contains course1.
        $prog2courseset1 = $DB->get_record('prog_courseset', array('programid' => $this->program2->id, 'sortorder' => 1));

        // We create the course_deleted event, deleting course1.
        $context = context_system::instance();
        $event = \core\event\course_deleted::create(array(
            'objectid' => $this->course1->id,
            'contextid' => $context->id,
            'other' => array(
                'fullname' => $this->course1->fullname
            )));
        $event->trigger();

        // The prog_courseset records that were only directly linked to course1 should have been deleted.
        // The others should still be there.
        $this->assertFalse($DB->record_exists('prog_courseset', array('id' => $prog1courseset1->id)));
        $this->assertTrue($DB->record_exists('prog_courseset', array('id' => $prog1courseset2->id)));
        $this->assertTrue($DB->record_exists('prog_courseset', array('id' => $prog1courseset3->id)));
        $this->assertFalse($DB->record_exists('prog_courseset', array('id' => $prog2courseset1->id)));

        // There should be no records left for course1 in prog_courseset_course.
        // But other records should still be there.
        $this->assertFalse($DB->record_exists('prog_courseset_course', array('courseid' => $this->course1->id)));
        $this->assertTrue($DB->record_exists('prog_courseset_course', array('courseid' => $this->course2->id)));

        // Call the component courses of the remaining course sets to ensure that still works following
        // the deletion of other data. And make sure they are in the order we expect.
        // We'll reload prog_content beforehand.
        unset($progcontent1);
        $progcontent1 = new prog_content($this->program1->id);
        $coursesets = $progcontent1->get_course_sets();

        $this->assertEquals($prog1courseset2->id, $coursesets[0]->id);
        $this->assertEquals(array($this->course2), $coursesets[0]->get_courses());
        $this->assertEquals(1, $coursesets[0]->sortorder);

        $this->assertEquals($prog1courseset3->id, $coursesets[1]->id);
        $this->assertEquals(array($this->course3->id => $this->course3), $coursesets[1]->get_courses());
        $this->assertEquals(2, $coursesets[1]->sortorder);
    }

    /**
     * Tests the job_assignment_updated observer method.
     *
     * Ensures that when user's manager is updated, that any programs with assignments
     * based on that or the user's previous manager are flagged for update by the deferred assignments task.
     */
    public function test_job_assignment_updated_prog_assignment_manager() {
        global $DB;

        $user1 = $this->data_generator->create_user();
        $user1_ja = \totara_job\job_assignment::get_first($user1->id);
        $user2 = $this->data_generator->create_user();
        $user2_ja = \totara_job\job_assignment::get_first($user2->id);

        $manager1 = $this->data_generator->create_user();
        $manager1_ja = \totara_job\job_assignment::get_first($manager1->id);
        $manager1a = $this->data_generator->create_user();
        $manager1a_ja = \totara_job\job_assignment::get_first($manager1a->id);
        $manager2 = $this->data_generator->create_user();
        $manager2_ja = \totara_job\job_assignment::get_first($manager2->id);
        $manager2a = $this->data_generator->create_user();
        $manager2a_ja = \totara_job\job_assignment::get_first($manager2a->id);

        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_MANAGERJA, $manager1_ja->id);

        // Check the deferred flag isn't set yet.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        $manager1a_ja->update(array('managerjaid' => $manager1_ja->id));

        // Check that just program1 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(1, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Reset the deferred flag before the next check.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program1->id));

        $user1_ja->update(array('managerjaid' => $manager1a_ja->id));

        // No deferred flags should be set here. We didn't set the assignment to include children.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Now we'll test this where include children is set.
        $this->data_generator->assign_to_program($this->program2->id, ASSIGNTYPE_MANAGERJA, $manager2_ja->id, array('includechildren' => 1));

        $manager2a_ja->update(array('managerjaid' => $manager2_ja->id));

        // We're not interested in direct manager assignments here. Reset the deferred flag before the next check.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program2->id));

        // Check that no deferred flags are set at this stage.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        $user2_ja->update(array('managerjaid' => $manager2a_ja->id));

        // Check that just program2 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(1, $program2_record->assignmentsdeferred);

        // Reset the deferred field.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program2->id));

        // Remove manager1a's manager.
        $manager1a_ja->update(array('managerjaid' => null));

        // Check that just program1 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(1, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Reset the deferred field.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program1->id));

        // Remove manager1a's manager.
        $user2_ja->update(array('managerjaid' => null));

        // Check that just program2 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(1, $program2_record->assignmentsdeferred);
    }

    /**
     * Tests the job_assignment_updated observer method.
     *
     * Ensures that when user's position is updated, that any programs with assignments
     * based on that or the user's previous position are flagged for update by the deferred assignments task.
     */
    public function test_job_assignment_updated_prog_assignment_position() {
        global $DB;

        $user1 = $this->data_generator->create_user();
        $user1_ja = \totara_job\job_assignment::get_first($user1->id);
        $user2 = $this->data_generator->create_user();
        $user2_ja = \totara_job\job_assignment::get_first($user2->id);
        $user3 = $this->data_generator->create_user();
        $user3_ja = \totara_job\job_assignment::get_first($user3->id);

        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->data_generator->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchy_generator->create_pos_frame(array());
        $position1 = $hierarchy_generator->create_pos(array('frameworkid' => $posframework->id));
        $position1a = $hierarchy_generator->create_pos(array('frameworkid' => $posframework->id, 'parentid' => $position1->id));
        $position2 = $hierarchy_generator->create_pos(array('frameworkid' => $posframework->id));
        $position2a = $hierarchy_generator->create_pos(array('frameworkid' => $posframework->id, 'parentid' => $position2->id));

        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_POSITION, $position1->id);

        // Check the deferred flag isn't set yet.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        $user1_ja->update(array('positionid' => $position1->id));

        // Check that just program1 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(1, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Reset the deferred flag before the next check.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program1->id));

        $user2_ja->update(array('positionid' => $position1a->id));

        // No deferred flags should be set here. We didn't set the assignment to include children.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Now we'll test this where include children is set.
        $this->data_generator->assign_to_program($this->program2->id, ASSIGNTYPE_POSITION, $position2->id, array('includechildren' => 1));

        $user3_ja->update(array('positionid' => $position2a->id));

        // Check that just program2 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(1, $program2_record->assignmentsdeferred);

        // Reset the deferred field.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program2->id));

        // Remove the direct position1 assignment.
        $user1_ja->update(array('positionid' => null));

        // Check that just program1 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(1, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Reset the deferred field.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program1->id));

        // Remove the child position2a assignment.
        $user3_ja->update(array('positionid' => null));

        // Check that just program2 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(1, $program2_record->assignmentsdeferred);
    }

    /**
     * Tests the job_assignment_updated observer method.
     *
     * Ensures that when user's organisation is updated, that any programs with assignments
     * based on that or the user's previous organisation are flagged for update by the deferred assignments task.
     */
    public function test_job_assignment_updated_prog_assignment_organisation() {
        global $DB;

        $user1 = $this->data_generator->create_user();
        $user1_ja = \totara_job\job_assignment::get_first($user1->id);
        $user2 = $this->data_generator->create_user();
        $user2_ja = \totara_job\job_assignment::get_first($user2->id);
        $user3 = $this->data_generator->create_user();
        $user3_ja = \totara_job\job_assignment::get_first($user3->id);

        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->data_generator->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_org_frame(array());
        $organisation1 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id));
        $organisation1a = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'parentid' => $organisation1->id));
        $organisation2 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id));
        $organisation2a = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'parentid' => $organisation2->id));

        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_ORGANISATION, $organisation1->id);

        // Check the deferred flag isn't set yet.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        $user1_ja->update(array('organisationid' => $organisation1->id));

        // Check that just program1 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(1, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Reset the deferred flag before the next check.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program1->id));

        $user2_ja->update(array('organisationid' => $organisation1a->id));

        // No deferred flags should be set here. We didn't set the assignment to include children.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Now we'll test this where include children is set.
        $this->data_generator->assign_to_program($this->program2->id, ASSIGNTYPE_ORGANISATION, $organisation2->id, array('includechildren' => 1));

        $user3_ja->update(array('organisationid' => $organisation2a->id));

        // Check that just program2 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(1, $program2_record->assignmentsdeferred);

        // Reset the deferred field.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program2->id));

        // Remove the direct organisation1 assignment.
        $user1_ja->update(array('organisationid' => null));

        // Check that just program1 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(1, $program1_record->assignmentsdeferred);
        $this->assertEquals(0, $program2_record->assignmentsdeferred);

        // Reset the deferred field.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->program1->id));

        // Remove the child organisation2a assignment.
        $user3_ja->update(array('organisationid' => null));

        // Check that just program2 has the flag set.
        $program1_record = $DB->get_record('prog', array('id' => $this->program1->id));
        $program2_record = $DB->get_record('prog', array('id' => $this->program2->id));
        $this->assertEquals(0, $program1_record->assignmentsdeferred);
        $this->assertEquals(1, $program2_record->assignmentsdeferred);
    }

    /*
     * Check that starting a course marks the courseset and programs as started.
     */
    public function test_course_started() {
        global $DB;

        $this->resetAfterTest(true);
        set_config('enablecompletion', 1);

        $now = time();

        $user1 = $this->data_generator->create_user(); // Test user.
        $user2 = $this->data_generator->create_user(); // Control user.

        // Create some programs.
        $this->data_generator->add_courseset_program(
            $this->program1->id,
            array($this->course1->id, $this->course2->id)
        );
        $this->data_generator->add_courseset_program(
            $this->program1->id,
            array($this->course3->id, $this->course4->id)
        );
        $p1content = $this->program1->get_content();
        $p1content->fix_set_sortorder();
        $p1content->save_content();

        $cs1id = $DB->get_field('prog_courseset', 'id', array('programid' => $this->program1->id, 'sortorder' => 1));
        $cs2id = $DB->get_field('prog_courseset', 'id', array('programid' => $this->program1->id, 'sortorder' => 2));

        $this->data_generator->add_courseset_program(
            $this->program2->id,
            array($this->course5->id)
        );
        $p2content = $this->program2->get_content();
        $p2content->fix_set_sortorder();
        $p2content->save_content();

        $cs3id = $DB->get_field('prog_courseset', 'id', array('programid' => $this->program2->id, 'sortorder' => 1));

        // Assign some users.
        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $this->program1->update_learner_assignments(true);

        $this->data_generator->assign_to_program($this->program2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->program2->update_learner_assignments(true);

        // Check the time created is set but the time started is empty.
        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'programid' => $this->program1->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertEquals(0, $record->timestarted);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs1id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertEquals(0, $record->timestarted);

        // User 1 starts the first course.
        $setcourses = $DB->get_records('prog_courseset_course', array('coursesetid' => $cs1id));
        $setcrs1 = $DB->get_record('course', array('id' => array_pop($setcourses)->courseid));
        $this->data_generator->enrol_user($user1->id, $setcrs1->id);
        $completion = new completion_completion(array('userid' => $user1->id, 'course' => $setcrs1->id));
        $completion->mark_inprogress($now + 10000);

        // Check the timestarted for program 1 and courseset 1.
        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'programid' => $this->program1->id,  'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted); // Note: this will be set to now() if its in the future.

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs1id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted); // Note: this will be set to now() if its in the future.

        // User 1 completes the courses for courseset 1.
        $completion->mark_complete($now + 10000);
        $setcrs2 = $DB->get_record('course', array('id' => array_pop($setcourses)->courseid));
        $this->data_generator->enrol_user($user1->id, $setcrs2->id);
        $completion = new completion_completion(array('userid' => $user1->id, 'course' => $setcrs2->id));
        $completion->mark_inprogress($now + 20000);
        $completion->mark_complete($now + 20000);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs2id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertEquals(0, $record->timestarted);

        // User 1 starts courseset 2.
        $setcourses = $DB->get_records('prog_courseset_course', array('coursesetid' => $cs2id));
        $setcrs3 = $DB->get_record('course', array('id' => array_pop($setcourses)->courseid));
        $this->data_generator->enrol_user($user1->id, $setcrs3->id);
        $completion = new completion_completion(array('userid' => $user1->id, 'course' => $setcrs3->id));
        $completion->mark_inprogress($now + 30000);

        // Check the existing timestarted doesn't change but the courseset 2 timestarted is set to $now.
        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'programid' => $this->program1->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs1id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs2id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted);

        // Complete the program and check the data.
        $completion->mark_complete($now + 30000);
        $setcrs4 = $DB->get_record('course', array('id' => array_pop($setcourses)->courseid));
        $this->data_generator->enrol_user($user1->id, $setcrs4->id);
        $completion = new completion_completion(array('userid' => $user1->id, 'course' => $setcrs4->id));
        $completion->mark_inprogress($now + 40000);
        $completion->mark_complete($now + 40000);

        prog_update_completion($user1->id);

        // Make sure the controls are unaffected by everything.
        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'programid' => $this->program1->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted);
        $this->assertEquals($now + 40000, $record->timecompleted);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs1id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted);
        $this->assertEquals($now + 20000, $record->timecompleted);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs2id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertGreaterThanOrEqual($now, $record->timestarted);
        $this->assertEquals($now + 40000, $record->timecompleted);

        $record = $DB->get_record('prog_completion', array('userid' => $user2->id, 'programid' => $this->program1->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertEquals(0, $record->timestarted);
        $this->assertEquals(0, $record->timecompleted);

        $record = $DB->get_record('prog_completion', array('userid' => $user2->id, 'coursesetid' => $cs1id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertEquals(0, $record->timestarted);
        $this->assertEquals(0, $record->timecompleted);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'programid' => $this->program2->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertEquals(0, $record->timestarted);
        $this->assertEquals(0, $record->timecompleted);

        $record = $DB->get_record('prog_completion', array('userid' => $user1->id, 'coursesetid' => $cs3id));
        $this->assertGreaterThanOrEqual($now, $record->timecreated);
        $this->assertEquals(0, $record->timestarted);
        $this->assertEquals(0, $record->timecompleted);
    }

    /**
     * This test only covers what happens to the program and certification completion records when a user is deleted.
     */
    public function test_user_deleted_completion_records() {
        global $DB;

        $generator = $this->data_generator;

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $generator->create_certification();
        $cert2 = $generator->create_certification();

        // Create some programs.
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Add the users to the certs.
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Add the users to the programs.
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Mark one of the programs complete for both users.
        $progcompletion = prog_load_completion($prog1->id, $user1->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $this->assertEquals(array(), prog_get_completion_errors($progcompletion));
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($prog1->id, $user2->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $this->assertEquals(array(), prog_get_completion_errors($progcompletion));
        $this->assertTrue(prog_write_completion($progcompletion));

        // Make one of the certifications certified for both users.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user1->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user2->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');
        list($expectedcertcompletionhistory, $progcompletion) = certif_load_completion($cert1->id, $user1->id);

        // Trigger the user deleted observer by deleting user1.
        delete_user($user1);

        // Manually make the same change to the expected data.
        foreach ($expectedcertcompletions as $key => $certcompletion) {
            // Both cert completions for user1 are gone.
            if ($certcompletion->userid == $user1->id) {
                unset($expectedcertcompletions[$key]);
            }
        }
        foreach ($expectedprogcompletions as $key => $progcompletion) {
            // The incomplete prog completions for user1 are gone.
            $deletedprogs = array($cert2->id, $prog2->id);
            if ($progcompletion->userid == $user1->id && in_array($progcompletion->programid, $deletedprogs)) {
                unset($expectedprogcompletions[$key]);
            }
        }
        unset($expectedcertcompletionhistory->id);
        unset($expectedcertcompletionhistory->timemodified);
        $expectedcertcompletionhistory->unassigned = 1;

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // Make sure that the history record has been created.
        $certcomplhistories = $DB->get_records('certif_completion_history');
        $this->assertCount(1, $certcomplhistories);
        $certcompletionhistory = reset($certcomplhistories);
        unset($certcompletionhistory->id);
        unset($certcompletionhistory->timemodified);
        $this->assertEquals($expectedcertcompletionhistory, $certcompletionhistory);
    }
}
