<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_hierarchy
 */

use \totara_hierarchy\task\update_competencies_task as task;
use totara_job\job_assignment;

/**
 * Tests the {@see update_competencies_task} class
 */
class totara_hierarchy_update_competencies_task_testcase extends advanced_testcase {

    public static function setUpBeforeClass() {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/evidence/lib.php');
    }

    public function test_get_name() {
        $task = new task;
        $this->assertSame(get_string('updatecompetenciestask', 'totara_hierarchy'), $task->get_name());
    }

    public function test_execution_basic() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $generator_hierarchy */
        $generator_hierarchy = $generator->get_plugin_generator('totara_hierarchy');

        $course = $this->create_self_completable_course([]);
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id);
        $generator->enrol_user($user2->id, $course->id);
        $generator->enrol_user($user3->id, $course->id);

        // Create an organisation
        $org_framework = $generator_hierarchy->create_framework('organisation', array('fullname' => 'All Organisations'));
        $organisation_data = ['fullname' => 'Test Organisation 1'];
        $organisation = $generator_hierarchy->create_hierarchy($org_framework->id, 'organisation', $organisation_data);

        $job_assignment_data = [
            'fullname' => 'ja1',
            'organisationid' => $organisation->id
        ];
        job_assignment::create_default($user1->id, $job_assignment_data);

        $framework = $generator_hierarchy->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1']);
        $comp1 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c1', 'parentid' => 0]);
        $comp2 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c2', 'parentid' => $comp1->id]);
        $comp3 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c3', 'parentid' => $comp2->id]);
        $generator_hierarchy->assign_linked_course_to_competency($comp2, $course);
        $proficiencyid = $DB->get_field('comp_scale_values', 'id', ['proficient' => '1'], IGNORE_MULTIPLE);
        $notproficiencyid = $DB->get_field('comp_scale_values', 'id', ['proficient' => '0'], IGNORE_MULTIPLE);

        $comp1id = (int)$comp1->id;
        $comp2id = (int)$comp2->id;
        $comp3id = (int)$comp3->id;

        $this->assertEquals([], competency::get_user_completed_competencies($user1->id));
        $this->assertEquals([], competency::get_user_completed_competencies($user2->id));
        $this->assertEquals([], competency::get_user_completed_competencies($user3->id));

        $reaggregatetime = time() - 3600;
        hierarchy_add_competency_evidence($comp3id, $user1->id, $proficiencyid, null, new stdClass(), $reaggregatetime, false);
        hierarchy_add_competency_evidence($comp3id, $user2->id, $notproficiencyid, null, new stdClass(), $reaggregatetime, false);
        hierarchy_add_competency_evidence($comp2id, $user3->id, $proficiencyid, null, new stdClass(), $reaggregatetime, false);
        $this->assertSame(6, $DB->count_records_select('comp_record', 'reaggregate <> 0'));

        $this->assert_user_hold_competencies($user1, [$comp3id]);
        $this->assert_user_hold_competencies($user2, []);
        $this->assert_user_hold_competencies($user3, [$comp2id]);

        $output = $this->run_task();

        $this->assertSame(0, $DB->count_records('comp_record', ['reaggregate' => $reaggregatetime]));

        $this->assert_user_hold_competencies($user1, [$comp3id]);
        $this->assert_user_hold_competencies($user2, []);
        $this->assert_user_hold_competencies($user3, [$comp1id, $comp2id]);

        $this->waitForSecond();

        // Run a second time to ensure that all aggregations are done.
        // Due to the design of the reaggregation it can happen that reaggregation time for some items is
        // set to a newer timestamp (depends on the speed of the test). In this case the items will be picked up a second time.
        $output .= $this->run_task();

        $this->assertSame(6, $DB->count_records('comp_record', ['reaggregate' => 0]));

        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp2id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user2->id.' for competency '.$comp2id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user3->id.' for competency '.$comp2id, $output);
        $this->assertContains("Aggregating competency items evidence for user ".$user3->id." for competency ".$comp1id."\nUpdate proficiency to 1", $output);

        $this->assert_user_hold_competencies($user1, [$comp3id]);
        $this->assert_user_hold_competencies($user2, []);
        $this->assert_user_hold_competencies($user3, [$comp1id, $comp2id]);

        $this->make_user_mark_course_complete($user1, $course);

        $this->assert_user_hold_competencies($user1, [$comp3id]);
        $this->assert_user_hold_competencies($user2, []);
        $this->assert_user_hold_competencies($user3, [$comp1id, $comp2id]);

        $this->waitForSecond();

        $output = $this->run_task();
        // Run it a second time, see explanation above
        $this->waitForSecond();
        $output .= $this->run_task();

        $this->assertSame(7, $DB->count_records('comp_record', ['reaggregate' => 0]));

        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp1id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp2id, $output);

        $this->assert_user_hold_competencies($user1, [$comp1id, $comp2id, $comp3id]);
        $this->assert_user_hold_competencies($user2, []);
        $this->assert_user_hold_competencies($user3, [$comp1id, $comp2id]);
    }

    public function test_execution_deep_path() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $generator_hierarchy */
        $generator_hierarchy = $generator->get_plugin_generator('totara_hierarchy');

        $course = $generator->create_course();
        $user1 = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id);

        $framework = $generator_hierarchy->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1']);
        $comp1 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c1', 'parentid' => 0]);
        $comp2 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c2', 'parentid' => $comp1->id]);
        $comp3 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c3', 'parentid' => $comp2->id]);
        $comp4 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c4', 'parentid' => $comp3->id]);
        $comp5 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c5', 'parentid' => $comp4->id]);
        $comp6 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c6', 'parentid' => $comp5->id]);
        $comp7 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c7', 'parentid' => $comp6->id]);
        $comp8 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c8', 'parentid' => $comp7->id]);
        $comp9 = $generator_hierarchy->create_comp(['frameworkid' => $framework->id, 'idnumber' => 'c9', 'parentid' => $comp8->id]);
        $generator_hierarchy->assign_linked_course_to_competency($comp9, $course);
        $proficiencyid = $DB->get_field('comp_scale_values', 'id', ['proficient' => '1'], IGNORE_MULTIPLE);

        $comp1id = (int)$comp1->id;
        $comp2id = (int)$comp2->id;
        $comp3id = (int)$comp3->id;
        $comp4id = (int)$comp4->id;
        $comp5id = (int)$comp5->id;
        $comp6id = (int)$comp6->id;
        $comp7id = (int)$comp7->id;
        $comp8id = (int)$comp8->id;
        $comp9id = (int)$comp9->id;

        $reaggregatetime = time() - 3600;
        hierarchy_add_competency_evidence($comp9id, $user1->id, $proficiencyid, null, new stdClass(), $reaggregatetime, false);
        $this->assertSame(2, $DB->count_records_select('comp_record', 'reaggregate <> 0'));

        $this->assert_user_hold_competencies($user1, [$comp9id]);

        // Run it a multiple times, maximum five times
        // Just to make sure all records are processed.
        $i = 0;
        $output = '';
        do {
            $this->waitForSecond();
            $output .= $this->run_task();
            $i++;
        } while ($i < 5 && $DB->count_records('comp_record', ['reaggregate' => 0]) < 9);

        // All items should have been reaggregated
        $this->assertSame(0, $DB->count_records('comp_record', ['reaggregate' => $reaggregatetime]));
        $this->assertSame(9, $DB->count_records('comp_record', ['reaggregate' => 0]));

        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp9id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp8id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp7id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp6id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp5id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp4id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp3id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp2id, $output);
        $this->assertContains('Aggregating competency items evidence for user '.$user1->id.' for competency '.$comp1id, $output);

        $expected = [
            $comp9id,
            $comp8id,
            $comp7id,
            $comp6id,
            $comp5id,
            $comp4id,
            $comp3id,
            $comp2id,
            $comp1id,
        ];
        $this->assert_user_hold_competencies($user1, $expected);

        $this->waitForSecond();

        $output = $this->run_task();

        $this->assertNotContains('Aggregating competency items evidence for user', $output);

        $expected = [
            $comp9id,
            $comp8id,
            $comp7id,
            $comp6id,
            $comp5id,
            $comp4id,
            $comp3id,
            $comp2id,
            $comp1id,
        ];
        $this->assert_user_hold_competencies($user1, $expected);
    }

    /**
     * Creates a course that can be self completed.
     *
     * @param array $data
     * @return stdClass
     */
    private function create_self_completable_course($data) {
        global $CFG;

        require_once($CFG->dirroot . '/lib/completionlib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');

        set_config('enablecompletion', COMPLETION_ENABLED);

        $coursedefaults = [
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ];
        $course = $this->getDataGenerator()->create_course($data + $coursedefaults, array('createsections' => true));

        $criteriadata = new stdClass();
        $criteriadata->id = $course->id;
        $criteriadata->criteria_activity = array();

        // Self completion.
        $criteriadata->criteria_self = COMPLETION_CRITERIA_TYPE_SELF;
        $criteriadata->criteria_self_value = COMPLETION_CRITERIA_TYPE_SELF;
        $criterion = new completion_criteria_self();
        $criterion->update_config($criteriadata);

        // Handle overall aggregation.
        $aggdata = array(
            'course' => $course->id,
            'criteriatype' => null
        );
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        return $course;
    }

    /**
     * Run the task and return the output
     *
     * @return string
     */
    private function run_task() {
        $task = new task;
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * Asserts the the user holds all of the given competencies
     * @param stdClass $user
     * @param array $expected An array of competency ids
     */
    private function assert_user_hold_competencies(stdClass $user, array $expected) {
        $actual = competency::get_user_completed_competencies($user->id);
        rsort($expected);
        rsort($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Have the given user mark themselves complete in the given course
     *
     * @param stdClass $user
     * @param stdClass $course
     */
    private function make_user_mark_course_complete($user, $course) {
        $this->setUser($user);
        core_completion_external::mark_course_self_completed($course->id);
        $ccompletion = new completion_completion(array('course' => $course->id, 'userid' => $user->id));
        $ccompletion->mark_complete();
    }

}