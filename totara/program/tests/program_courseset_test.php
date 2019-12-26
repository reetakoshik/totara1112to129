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
require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

/**
 * Class totara_program_program_courseset_testcase
 *
 * Tests functions found within classes in the /totara/program/program_content.class.php file.
 * This includes the abstract function, course_set and child functions: multi_course_set, competency_course_set
 * and recurring_course_set.
 */
class totara_program_program_courseset_testcase extends advanced_testcase {

    /** @var testing_data_generator */
    private $generator;

    /** @var totara_program_generator*/
    private $program_generator;

    /** @var stdClass */
    private $course1, $course2, $course3;

    /** @var program */
    private $program1;

    protected function tearDown() {
        $this->generator = null;
        $this->program_generator = null;
        $this->course1 = $this->course2 = $this->course3 = null;
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

        // Reload courses. Otherwise when we compare the courses with the returned courses,
        // we get subtle differences in some values such as cacherev and sortorder.
        // Todo: Investigate whether we can improve the generator to fix this.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
    }

    /**
     * Tests the multi_course_set::get_courses method.
     */
    public function test_multi_course_set_get_courses() {
        $this->resetAfterTest(true);
        global $DB;

        // Create the multi-course set. We define a unique id as otherwise it defines it's own random one.
        $uniqueid = 'multiset';
        $multicourseset1 = new multi_course_set($this->program1->id, null, $uniqueid);

        // Let's use the methods within the class to add the courses. The data is formatted in a similar
        // way to what will come from a form.
        $coursedata = new stdClass();
        $coursedata->{$uniqueid . 'courseid'} = $this->course1->id;
        $multicourseset1->add_course($coursedata);
        $coursedata->{$uniqueid . 'courseid'} = $this->course2->id;
        $multicourseset1->add_course($coursedata);

        // Add a second multi-course set. We will ensure we still get the correct courses from each.
        $multicourseset2 = new multi_course_set($this->program1->id, null, $uniqueid);
        $coursedata->{$uniqueid . 'courseid'} = $this->course3->id;
        $multicourseset2->add_course($coursedata);

        // The courses should already be returned prior to saving.
        // We can set $checkForObjectIdentity to false in the assertion.
        $this->assertContains($this->course1, $multicourseset1->get_courses(), '', false, false);
        $this->assertContains($this->course2, $multicourseset1->get_courses(), '', false, false);
        $this->assertCount(2, $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        $multicourseset1->save_set();
        $multicourseset2->save_set();

        // After saving, the same courses should still be there.
        $this->assertContains($this->course1, $multicourseset1->get_courses(), '', false, false);
        $this->assertContains($this->course2, $multicourseset1->get_courses(), '', false, false);
        $this->assertCount(2, $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Check the same courses are there after freshly instantiating each courseset.
        $coursesetid1 = $multicourseset1->id;
        $coursesetid2 = $multicourseset2->id;
        unset($multicourseset, $multicourseset2);
        $coursesetrecord1 = $DB->get_record('prog_courseset', array('id' => $coursesetid1));
        $coursesetrecord2 = $DB->get_record('prog_courseset', array('id' => $coursesetid2));
        $multicourseset1 = new multi_course_set($this->program1->id, $coursesetrecord1, $uniqueid);
        $multicourseset2 = new multi_course_set($this->program1->id, $coursesetrecord2, $uniqueid);
        $this->assertContains($this->course1, $multicourseset1->get_courses(), '', false, false);
        $this->assertContains($this->course2, $multicourseset1->get_courses(), '', false, false);
        $this->assertCount(2, $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());
    }

    /**
     * Tests the multi_course_set::delete_course method, specifically
     * checking for correct data before saving the course set.
     */
    public function test_multi_course_set_delete_course_presave() {
        $this->resetAfterTest(true);

        // Create the multi-course set. We define a unique id as otherwise it defines it's own random one.
        $uniqueid = 'multiset';
        $multicourseset1 = new multi_course_set($this->program1->id, null, $uniqueid);

        // Let's use the methods within the class to add the courses. The data is formatted in a similar
        // way to what will come from a form.
        $coursedata = new stdClass();
        $coursedata->{$uniqueid . 'courseid'} = $this->course1->id;
        $multicourseset1->add_course($coursedata);
        $coursedata->{$uniqueid . 'courseid'} = $this->course2->id;
        $multicourseset1->add_course($coursedata);

        // Add a second multi-course set. We will ensure we still get the correct courses from each.
        $multicourseset2 = new multi_course_set($this->program1->id, null, $uniqueid);
        $coursedata->{$uniqueid . 'courseid'} = $this->course3->id;
        $multicourseset2->add_course($coursedata);

        // The courses should already be returned prior to saving.
        $this->assertContains($this->course1, $multicourseset1->get_courses(), '', false, false);
        $this->assertContains($this->course2, $multicourseset1->get_courses(), '', false, false);
        $this->assertCount(2, $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Delete course 2 from multicourseset1.
        $multicourseset1->delete_course($this->course2->id);
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Try to delete course 1 from multicourseset2. This shouldn't have any effect.
        $multicourseset2->delete_course($this->course1->id);
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Try to delete a non-existent course id from multicourseset2. This shouldn't have any effect.
        $multicourseset2->delete_course(4003);
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Delete the only course from multicourseset2. This should leave it empty.
        $multicourseset2->delete_course($this->course3->id);
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array(), $multicourseset2->get_courses());

        // Now save the course sets and make sure the same courses are in each.
        $multicourseset1->save_set();
        $multicourseset2->save_set();
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array(), $multicourseset2->get_courses());
    }

    /**
     * Tests the multi_course_set::delete_course method, specifically
     * checking for correct data after saving the course set.
     */
    public function test_multi_course_set_delete_course_postsave() {
        $this->resetAfterTest(true);

        // Create the multi-course set. We define a unique id as otherwise it defines it's own random one.
        $uniqueid = 'multiset';
        $multicourseset1 = new multi_course_set($this->program1->id, null, $uniqueid);

        // Let's use the methods within the class to add the courses. The data is formatted in a similar
        // way to what will come from a form.
        $coursedata = new stdClass();
        $coursedata->{$uniqueid . 'courseid'} = $this->course1->id;
        $multicourseset1->add_course($coursedata);
        $coursedata->{$uniqueid . 'courseid'} = $this->course2->id;
        $multicourseset1->add_course($coursedata);

        // Add a second multi-course set. We will ensure we still get the correct courses from each.
        $multicourseset2 = new multi_course_set($this->program1->id, null, $uniqueid);
        $coursedata->{$uniqueid . 'courseid'} = $this->course3->id;
        $multicourseset2->add_course($coursedata);

        $multicourseset1->save_set();
        $multicourseset2->save_set();

        // Ensure the initial courses are returned after saving.
        $this->assertContains($this->course1, $multicourseset1->get_courses(), '', false, false);
        $this->assertContains($this->course2, $multicourseset1->get_courses(), '', false, false);
        $this->assertCount(2, $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());


        // Delete course 2 from multicourseset1.
        $multicourseset1->delete_course($this->course2->id);
        // Test before save.
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());
        $multicourseset1->save_set();
        $multicourseset2->save_set();
        // Test after save.
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Try to delete course 1 from multicourseset2. This shouldn't have any effect.
        $multicourseset2->delete_course($this->course1->id);
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());
        $multicourseset1->save_set();
        $multicourseset2->save_set();
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Try to delete a non-existent course id from multicourseset2. This shouldn't have any effect.
        $multicourseset2->delete_course(4003);
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());
        $multicourseset1->save_set();
        $multicourseset2->save_set();
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array($this->course3), $multicourseset2->get_courses());

        // Delete the only course from multicourseset2. This should leave it empty.
        $multicourseset2->delete_course($this->course3->id);
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array(), $multicourseset2->get_courses());
        $multicourseset1->save_set();
        $multicourseset2->save_set();
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        $this->assertEquals(array(), $multicourseset2->get_courses());
    }

    /**
     * Tests the recurring_course_set::get_courses method.
     */
    public function test_recurring_courseset_get_courses() {
        $this->resetAfterTest(true);

        $recurringcourseset = new recurring_course_set($this->program1->id);
        $recurringcourseset->course = $this->course2;

        $this->assertEquals(array($this->course2), $recurringcourseset->get_courses());
        $recurringcourseset->save_set();
        $this->assertEquals(array($this->course2), $recurringcourseset->get_courses());
    }

    /**
     * Tests the recurring_course_set::delete_course method.
     */
    public function test_recurring_courseset_delete_course() {
        $this->resetAfterTest(true);

        $recurringcourseset = new recurring_course_set($this->program1->id);
        $recurringcourseset->course = $this->course2;

        // Try to delete course1. Should have no effect.
        $recurringcourseset->delete_course($this->course1->id);
        $this->assertEquals(array($this->course2), $recurringcourseset->get_courses());
        $recurringcourseset->save_set();
        $this->assertEquals(array($this->course2), $recurringcourseset->get_courses());

        // Try to delete non-existent course id. Should have no effect.
        $recurringcourseset->delete_course(4003);
        $this->assertEquals(array($this->course2), $recurringcourseset->get_courses());
        $recurringcourseset->save_set();
        $this->assertEquals(array($this->course2), $recurringcourseset->get_courses());

        // Delete course2 this time.
        $recurringcourseset->delete_course($this->course2->id);
        $this->assertEquals(array(), $recurringcourseset->get_courses());
        $recurringcourseset->save_set();
        $this->assertEquals(array(), $recurringcourseset->get_courses());

        // Try to delete a course when it's no longer in the course set. Should have no effect.
        $recurringcourseset->delete_course($this->course2->id);
        $this->assertEquals(array(), $recurringcourseset->get_courses());
        $recurringcourseset->save_set();
        $this->assertEquals(array(), $recurringcourseset->get_courses());
    }

    /**
     * Tests the competency_course_set::get_courses method.
     */
    public function test_competency_courseset_get_courses() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll create a multi-course set as well as a competency course set in this test to ensure
        // no unwanted interactions occur.

        //We define a unique id as otherwise it defines it's own random one.
        $uniqueidmulti = 'multiset';
        $multicourseset1 = new multi_course_set($this->program1->id, null, $uniqueidmulti);

        // Let's use the methods within the class to add the courses. The data is formatted in a similar
        // way to what will come from a form.
        $coursedata = new stdClass();
        $coursedata->{$uniqueidmulti . 'courseid'} = $this->course1->id;
        $multicourseset1->add_course($coursedata);
        $multicourseset1->save_set();

        $uniqueidcomp = 'compset';
        $competencycourseset = new competency_course_set($this->program1->id, null, $uniqueidcomp);

        // Create the competency.
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for courses 2 and 3 will be assigned to this competency.
        $course2evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course2);
        $course3evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course3);

        // Add the competency to the courseset.
        $compdata = new stdClass();
        $compdata->{$uniqueidcomp . 'competencyid'} = $competency->id;
        $competencycourseset->add_competency($compdata);

        // Ensure the correct courses are retuned prior to save.
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        // The competency course set will return it's courses with course ids for keys.
        $compsetexpected = array($this->course2->id => $this->course2, $this->course3->id => $this->course3);
        $compsetactual = $competencycourseset->get_courses();
        ksort($compsetexpected);
        ksort($compsetactual);
        $this->assertEquals($compsetexpected, $compsetactual);

        // Ensure the same courses are returned after save.
        $multicourseset1->save_set();
        $competencycourseset->save_set();

        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        // The competency course set will return it's courses with course ids for keys.
        $compsetactual = $competencycourseset->get_courses();
        ksort($compsetactual);
        $this->assertEquals($compsetexpected, $compsetactual);

        // Instantiate new courseset and ensure we still get correct courses returned.
        $competencycoursesetid = $competencycourseset->id;
        unset($competencycourseset);
        $compcoursesetrecord = $DB->get_record('prog_courseset', array('id' => $competencycoursesetid));
        $competencycourseset = new competency_course_set($this->program1->id, $compcoursesetrecord, $uniqueidcomp);
        $compsetactual = $competencycourseset->get_courses();
        ksort($compsetactual);
        $this->assertEquals($compsetexpected, $compsetactual);

        // Unlink a course from the competency.
        $hierarchygenerator->remove_linked_course_from_competency($competency, $course3evidenceid);
        $this->assertEquals(array($this->course2->id => $this->course2), $competencycourseset->get_courses());
    }

    /**
     * Tests the competency_course_set::delete_course method.
     */
    public function test_competency_courseset_delete_course() {
        $this->resetAfterTest(true);

        // We'll create a multi-course set as well as a competency course set in this test to ensure
        // no unwanted interactions occur.

        //We define a unique id as otherwise it defines it's own random one.
        $uniqueidmulti = 'multiset';
        $multicourseset1 = new multi_course_set($this->program1->id, null, $uniqueidmulti);

        // Let's use the methods within the class to add the courses. The data is formatted in a similar
        // way to what will come from a form.
        $coursedata = new stdClass();
        $coursedata->{$uniqueidmulti . 'courseid'} = $this->course1->id;
        $multicourseset1->add_course($coursedata);
        $multicourseset1->save_set();

        $uniqueidcomp = 'compset';
        $competencycourseset = new competency_course_set($this->program1->id, null, $uniqueidcomp);

        // Create the competency.
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for courses 2 and 3 will be assigned to this competency.
        $course2evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course2);
        $course3evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course3);

        // Add the competency to the courseset.
        $compdata = new stdClass();
        $compdata->{$uniqueidcomp . 'competencyid'} = $competency->id;
        $competencycourseset->add_competency($compdata);

        // Ensure the correct courses are retuned prior to save.
        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        // The competency course set will return it's courses with course ids for keys.
        $compsetexpected = array($this->course2->id => $this->course2, $this->course3->id => $this->course3);
        $compsetactual = $competencycourseset->get_courses();
        ksort($compsetexpected);
        ksort($compsetactual);
        $this->assertEquals($compsetexpected, $compsetactual);

        // Try a delete prior to save. It should return false before and after.
        // But the script should also carry on without errors/exceptions.
        $this->assertEquals(false, $competencycourseset->delete_course($this->course2->id));

        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        // The competency course set will return it's courses with course ids for keys.
        $compsetactual = $competencycourseset->get_courses();
        ksort($compsetactual);
        $this->assertEquals($compsetexpected, $compsetactual);

        $multicourseset1->save_set();
        $competencycourseset->save_set();

        // Try a delete after save. It should return false before and after.
        // But the script should also carry on without errors/exceptions.
        $this->assertEquals(false, $competencycourseset->delete_course($this->course2->id));

        $this->assertEquals(array($this->course1), $multicourseset1->get_courses());
        // The competency course set will return it's courses with course ids for keys.
        $compsetactual = $competencycourseset->get_courses();
        ksort($compsetactual);
        $this->assertEquals($compsetexpected, $compsetactual);

        // Now delete the course from the multicourse set and ensure it does not have any effect on the competency course set.
        $multicourseset1->delete_course($this->course1->id);
        // Also try deleting one of the courses that's in the competency course set.
        $multicourseset1->delete_course($this->course2->id);

        $multicourseset1->save_set();

        $this->assertEquals(array(), $multicourseset1->get_courses());
        // The competency course set will return it's courses with course ids for keys.
        $compsetactual = $competencycourseset->get_courses();
        ksort($compsetactual);
        $this->assertEquals($compsetexpected, $compsetactual);
    }

    public function test_fix_set_sortorder() {

        // Create three coursesets, each with one course.
        $multicourseset1 = new multi_course_set($this->program1->id, null, 'multiset1');
        $multicourseset1->add_course((object)['multiset1_course1' => $this->course1->id]);
        $multicourseset1->save_set();

        $multicourseset2 = new multi_course_set($this->program1->id, null, 'multiset2');
        $multicourseset2->add_course((object)['multiset2_course2' => $this->course2->id]);
        $multicourseset2->save_set();

        $multicourseset3 = new multi_course_set($this->program1->id, null, 'multiset3');
        $multicourseset3->add_course((object)['multiset3_course3' => $this->course3->id]);
        $multicourseset3->save_set();

        $program = new program($this->program1->id);
        $content = $program->get_content();
        $this->assertInstanceOf('prog_content', $content);
        $coursesets = $content->get_course_sets();
        $this->assertIsArray($coursesets);
        $this->assertCount(3, $coursesets);
        $content->fix_set_sortorder();

        $property_isfirstset = new ReflectionProperty('course_set', 'isfirstset');
        $property_isfirstset->setAccessible(true);
        $property_islastset = new ReflectionProperty('course_set', 'islastset');
        $property_islastset->setAccessible(true);

        $count = 1;
        foreach ($coursesets as $courseset) {
            $this->assertEquals($count, $courseset->sortorder);
            if ($count === 1) {
                $this->assertTrue($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            } else if ($count === 3) {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertTrue($property_islastset->getValue($courseset));
            } else {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            }
            $count++;
        }

        // Change the order to set 3,1,2.
        $temp = array_pop($coursesets);
        array_unshift($coursesets, $temp);
        // Verify its mixed up.
        $count = 1;
        foreach ($coursesets as $courseset) {
            if ($count === 1) {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertTrue($property_islastset->getValue($courseset));
            } else if ($count === 3) {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            } else {
                $this->assertTrue($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            }
            $count++;
        }

        // Check the fix_sortorder function works on the outside.
        $content->fix_set_sortorder($coursesets);

        $count = 1;
        foreach ($coursesets as $courseset) {
            $this->assertEquals($count, $courseset->sortorder);
            if ($count === 1) {
                $this->assertTrue($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            } else if ($count === 3) {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertTrue($property_islastset->getValue($courseset));
            } else {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            }
            $count++;
        }

        // Change the order to set 3,1,2 again.
        $temp = array_pop($coursesets);
        array_unshift($coursesets, $temp);
        // Put it inside. Cheat!
        $property = new ReflectionProperty($content, 'coursesets');
        $property->setAccessible(true);
        $property->setValue($content, $coursesets);

        // Verify its mixed up inside as well.
        $count = 1;
        foreach ($content->get_course_sets() as $courseset) {
            if ($count === 1) {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertTrue($property_islastset->getValue($courseset));
            } else if ($count === 3) {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            } else {
                $this->assertTrue($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            }
            $count++;
        }

        // Check the fix_sortorder function works on the inside.
        $content->fix_set_sortorder();

        $count = 1;
        foreach ($content->get_course_sets() as $courseset) {
            $this->assertEquals($count, $courseset->sortorder);
            if ($count === 1) {
                $this->assertTrue($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            } else if ($count === 3) {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertTrue($property_islastset->getValue($courseset));
            } else {
                $this->assertNull($property_isfirstset->getValue($courseset));
                $this->assertNull($property_islastset->getValue($courseset));
            }
            $count++;
        }
    }
}