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

require_once($CFG->dirroot . '/lib/phpunit/classes/advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/program_content.class.php');

/**
 * Class totara_program_program_content_testcase
 *
 * Tests methods found within the prog_content class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_program_program_content_testcase totara/program/tests/program_content_test.php
 */
class totara_program_program_content_testcase extends advanced_testcase {

    /** @var testing_data_generator */
    private $generator;

    /** @var totara_program_generator*/
    private $program_generator;

    /** @var stdClass */
    private $course1, $course2, $course3;

    /** @var program */
    private $program1, $program2;

    protected function tearDown() {
        $this->generator = null;
        $this->program_generator = null;
        $this->course1 = null;
        $this->program1 = null;
        parent::tearDown();
    }

    public function setUp() {
        $this->resetAfterTest(true);
        parent::setUp();
        global $DB;

        $this->generator = $this->getDataGenerator();
        $this->program_generator = $this->generator->get_plugin_generator('totara_program');

        $this->course1 = $this->generator->create_course();
        $this->course2 = $this->generator->create_course();
        $this->course3 = $this->generator->create_course();
        $this->program1 = $this->program_generator->create_program();
        $this->program2 = $this->program_generator->create_program();

        // Reload courses. Otherwise when we compare the courses with the returned courses,
        // we get subtle differences in some values such as cacherev and sortorder.
        // Todo: Investigate whether we can improve the generator to fix this.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
    }

    /**
     * Tests the get_courseset_by_id method.
     */
    public function test_get_courseset_by_id() {
        $this->resetAfterTest(true);
        global $DB;

        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_COMPETENCY);

        /** @var course_set[] $coursesets */
        $coursesets = $progcontent->get_course_sets();

        // We'll add courses while we know what the sortorder is.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent->add_course(2, $coursedata);

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for course 3 will be assigned to this competency.
        $course3evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course3);

        // Add a competency to the competency courseset.
        $compdata = new stdClass();
        $compdata->{$coursesets[2]->get_set_prefix() . 'competencyid'} = $competency->id;
        $progcontent->add_competency(3, $compdata);

        // Now try to get coursesets by id before saving using an arbitrary id number. This should return false.
        $this->assertEquals(false, $progcontent->get_courseset_by_id(1));

        $progcontent->save_content();

        // Get the ids. At this stage we know what the sortorder should be and can test based on the below records.
        // Multi course set which contains course1.
        $courseset1 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 1));
        // Multi course set which contains course2.
        $courseset2 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 2));
        // Competency course set which contains competency 1 which links to course3.
        $courseset3 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 3));

        // Try to get a courseset by id.
        $returnedcourseset1 = $progcontent->get_courseset_by_id($courseset1->id);
        // Check it contains the same values as well as data.
        $this->assertEquals(1, $returnedcourseset1->sortorder);
        $this->assertEquals(array($this->course1), $returnedcourseset1->get_courses());

        // Try to get a courseset by another id.
        $returnedcourseset3 = $progcontent->get_courseset_by_id($courseset3->id);
        // Check it contains the same values as well as data.
        $this->assertEquals(3, $returnedcourseset3->sortorder);
        $this->assertEquals(array($this->course3->id => $this->course3), $returnedcourseset3->get_courses());

        // Remove a courseset. We'll use the method which takes the sortorder in this case.
        $progcontent->delete_set(2);
        $progcontent->save_content();
        $returnedcourseset3 = $progcontent->get_courseset_by_id($courseset3->id);
        // The sortorder for the competency courseset should have changed.
        $this->assertEquals(2, $returnedcourseset3->sortorder);
        $this->assertEquals(array($this->course3->id => $this->course3), $returnedcourseset3->get_courses());

        // Now let's swap the order of the coursesets by moving the second one up.
        $progcontent->move_set_up(2);
        $progcontent->save_content();

        // Try to get a courseset by id.
        $returnedcourseset1 = $progcontent->get_courseset_by_id($courseset1->id);
        // Check it contains the new correct sortorder but still the same data such as courses.
        $this->assertEquals(2, $returnedcourseset1->sortorder);
        $this->assertEquals(array($this->course1), $returnedcourseset1->get_courses());

        // Try to get a courseset by another id.
        $returnedcourseset3 = $progcontent->get_courseset_by_id($courseset3->id);
        // Check it contains the new correct sortorder but still the same data such as courses.
        $this->assertEquals(1, $returnedcourseset3->sortorder);
        $this->assertEquals(array($this->course3->id => $this->course3), $returnedcourseset3->get_courses());
    }

    /**
     * Tests the delete_courseset_by_id method.
     */
    public function test_delete_courseset_by_id() {
        $this->resetAfterTest(true);
        global $DB;

        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_COMPETENCY);

        /** @var course_set[] $coursesets */
        $coursesets = $progcontent->get_course_sets();

        // We'll add courses while we know what the sortorder is.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent->add_course(2, $coursedata);

        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for course 3 will be assigned to this competency.
        $course3evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course3);

        // Add a competency to the competency courseset.
        $compdata = new stdClass();
        $compdata->{$coursesets[2]->get_set_prefix() . 'competencyid'} = $competency->id;
        $progcontent->add_competency(3, $compdata);

        // Now try to delete coursesets by id before saving using an arbitrary id number. This should return false.
        $this->assertEquals(false, $progcontent->delete_courseset_by_id(1));

        $progcontent->save_content();

        // Get the ids. At this stage we know what the sortorder should be and can test based on the below records.
        // Multi course set which contains course1.
        $courseset1 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 1));
        // Multi course set which contains course2.
        $courseset2 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 2));
        // Competency course set which contains competency 1 which links to course3.
        $courseset3 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 3));

        $progcontent->delete_courseset_by_id($courseset1->id);

        // Check the deleted course set no longer gets returned.
        $this->assertFalse($progcontent->get_courseset_by_id($courseset1->id));

        // Check sortorders are updated.
        $returnedcourseset2 = $progcontent->get_courseset_by_id($courseset2->id);
        $this->assertEquals(1, $returnedcourseset2->sortorder);
        $returnedcourseset3 = $progcontent->get_courseset_by_id($courseset3->id);
        $this->assertEquals(2, $returnedcourseset3->sortorder);

        $progcontent->save_content();

        // Check correct prog_courseset record was deleted and none others.
        $this->assertFalse($DB->record_exists('prog_courseset', array('id' => $courseset1->id)));
        $this->assertTrue($DB->record_exists('prog_courseset', array('id' => $courseset2->id)));
        $this->assertTrue($DB->record_exists('prog_courseset', array('id' => $courseset3->id)));

        // Check that the correct prog_courseset_course record was deleted and none others.
        $this->assertFalse($DB->record_exists('prog_courseset_course', array('coursesetid' => $courseset1->id)));
        $this->assertTrue($DB->record_exists('prog_courseset_course', array('coursesetid' => $courseset2->id)));

        // Now delete again, this time with the competency course set.
        $progcontent->delete_courseset_by_id($courseset3->id);

        $this->assertFalse($progcontent->get_courseset_by_id($courseset3->id));

        // Check sortorders are correct.
        $returnedcourseset2 = $progcontent->get_courseset_by_id($courseset2->id);
        $this->assertEquals(1, $returnedcourseset2->sortorder);

        $progcontent->save_content();

        // Check correct prog_courseset record was deleted and none others.
        $this->assertTrue($DB->record_exists('prog_courseset', array('id' => $courseset2->id)));
        $this->assertFalse($DB->record_exists('prog_courseset', array('id' => $courseset3->id)));

        // No prog_courseset_course records should have been removed.
        $this->assertTrue($DB->record_exists('prog_courseset_course', array('coursesetid' => $courseset2->id)));
    }

    /**
     * Tests the contains_course method.
     */
    public function test_contains_course() {
        global $DB;

        // Set up program 1 first.
        $progcontent = new prog_content($this->program1->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent->add_set(CONTENTTYPE_COMPETENCY);

        /* @var course_set[] $coursesets */
        $coursesets = $progcontent->get_course_sets();

        // Add courses to course sets.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent->add_course(1, $coursedata);

        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent->add_course(2, $coursedata);

        /* @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for course 3 will be assigned to this competency.
        $course3evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course3);

        // Add a competency to the competency courseset.
        $compdata = new stdClass();
        $compdata->{$coursesets[2]->get_set_prefix() . 'competencyid'} = $competency->id;
        $progcontent->add_competency(3, $compdata);

        $progcontent->save_content();

        // Then set up program 2.
        $progcontent = new prog_content($this->program2->id);
        $progcontent->add_set(CONTENTTYPE_MULTICOURSE);

        /* @var course_set[] $coursesets */
        $coursesets = $progcontent->get_course_sets();

        // Add courses to course sets.
        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent->add_course(1, $coursedata);

        $progcontent->save_content();

        // Test the function.
        $program1 = new program($this->program1->id);
        $this->assertTrue($program1->content->contains_course($this->course1->id));
        $this->assertTrue($program1->content->contains_course($this->course2->id));
        $this->assertTrue($program1->content->contains_course($this->course3->id));
        $program2 = new program($this->program2->id);
        $this->assertFalse($program2->content->contains_course($this->course1->id));
        $this->assertTrue($program2->content->contains_course($this->course2->id));
        $this->assertFalse($program2->content->contains_course($this->course3->id));
    }
}

