<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/totara/cohort/rules/lib.php");

/**
 * Class record_checker_for_broken_rules_testcase
 */
class record_checker_for_broken_rules_testcase extends advanced_testcase {
    /**
     * Creating the number of course base on $n
     * @param int $n
     * @return stdClass[]
     */
    private function create_courses($n=2) {
        $generator = $this->getDataGenerator();
        $data = [];

        for ($i=0; $i < $n; $i++) {
            $data[] = $generator->create_course();
        }

        return $data;
    }

    /**
     * Creating the number of programs base on $n
     * @param int $n
     * @return stdClass[]
     */
    private function create_programs($n) {
        /** @var totara_program_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator("totara_program");
        $data = [];

        for ($i = 0; $i < $n; $i++) {
            $data[] = $generator->create_program();
        }

        return $data;
    }

    /**
     * Creating a cohort wit rules
     * @param stdClass[] $programs
     * @param stdClass[] $courses
     * @param stdClass[] $positions
     * @param stdClass[] $organisations
     * @return stdClass
     */
    private function create_cohort_and_rules(array $programs=[], array $courses=[], array $positions=[], array $organisations=[]) {
        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $cohort = $generator->create_cohort(['cohorttype' => cohort::TYPE_DYNAMIC]);

        $rulesetid = cohort_rule_create_ruleset($cohort->activecollectionid);

        if (!empty($programs)) {
            // Creating a rule for the list of programs here
            $programids = [];
            foreach ($programs as $program) {
                $programids[] = $program->id;
            }

            $generator->create_cohort_rule_params(
                $rulesetid, 'learning', 'programcompletionlist', ['equal' => COHORT_RULES_OP_AND], $programids, 'listofids'
            );
        }

        if (!empty($courses)) {
            // Creating a rule for the list of courses here
            $courseids = [];
            foreach ($courses as $course) {
                $courseids[] = $course->id;
            }

            $generator->create_cohort_rule_params(
                $rulesetid, 'learning', 'coursecompletionlist', ['equal' => COHORT_RULES_OP_AND], $courseids, 'listofids'
            );
        }

        if (!empty($positions)) {
            // Creating a rule for the list of positions here
            $positionids = [];
            foreach ($positions as $position) {
                $positionids[] = $position->id;
            }

            $generator->create_cohort_rule_params(
                $rulesetid, 'alljobassign', 'positions', ['equal' => COHORT_RULES_OP_IN_EQUAL], $positionids
            );
        }

        if (!empty($organisations)) {
            // Creating a rule for the list of organisations here
            $organisationids = [];
            foreach ($organisations as $organisation) {
                $organisationids[] = $organisation->id;
            }

            $generator->create_cohort_rule_params(
                $rulesetid, 'alljobassign', 'organisations', ['equal' => COHORT_RULES_OP_IN_EQUAL], $organisationids
            );
        }

        return $cohort;
    }

    /**
     * @param int $max
     * @return stdClass[]
     */
    private function create_organisations($max=2) {
        /** @var totara_hierarchy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);

        $data = [];
        for ($i = 0; $i < $max; $i++) {
            $data[] = $generator->create_org(['frameworkid' => $framework->id]);
        }

        return $data;
    }

    /**
     * @param int $max
     * @return stdClass[]
     */
    private function create_positions($max=2) {
        /** @var totara_hierarchy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);

        $data = [];
        for ($i = 0; $i < $max; $i++) {
            $data[] = $generator->create_pos(['frameworkid' => $framework->id]);
        }

        return $data;
    }

    /**
     * This is the common assertion method that is being used among the test suites
     *
     * @return void
     */
    private function perform_test() {
        $sink = phpunit_util::start_message_redirection();

        ob_start();
        totara_cohort_check_and_update_dynamic_cohort_members(null, new null_progress_trace());
        ob_end_clean();

        // Since there is a rule that the detection detect to be broken, therefore, one email should be sending out
        // to the admin user to notify it. Hence, there must be one email sent out after run the whole code block.
        // As there was one cohort with one broken rule, and so we are expecting 1 email here
        $this->assertCount(1, $sink->get_messages());
    }

    /**
     * This is the test suite where we are testing the functionality of broken rule where that rule
     * is containing the one of the program deleted. Since, there
     *
     * @return void
     */
    public function test_cohort_broken_rule_checking_when_one_of_program_deleted() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $programs = $this->create_programs(2);
        $cohort = $this->create_cohort_and_rules($programs);

        // Deleting one of the program here, so that the test is able to run the check within method
        // totara_cohort_check_and_update_dynamic_cohort_members.
        $DB->delete_records('prog', ['id' => current($programs)->id]);
        $this->perform_test();
    }

    /**
     * Same as the test above, this is the test suite for checking the broken rule, when one of the course was deleted
     *
     * @return void
     */
    public function test_cohort_broken_rule_checking_when_one_of_course_deleted() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $courses = $this->create_courses(5);
        $cohort = $this->create_cohort_and_rules([], $courses);

        // Deleting the course here, so that the broken rule checker can detect the issue within rule
        $DB->delete_records('course', ['id' => current($courses)->id]);
        $this->perform_test();
    }

    /**
     * Same as 2 of tests above, however, this test suite will have both one of courses and one of programs deleted.
     *
     * @return void
     */
    public function test_cohort_broken_rule_with_deleted_course_and_deleted_program() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $programs = $this->create_programs(2);
        $courses = $this->create_courses(2);

        $cohort = $this->create_cohort_and_rules($programs, $courses);

        // Deleting the course and program's record here
        $DB->delete_records('course', ['id' => current($courses)->id]);
        $DB->delete_records('prog', ['id' => current($programs)->id]);
        $this->perform_test();
    }

    /**
     * Checking whether the task runner is sending an email out to say that the rule is broken since there is a rule
     * that linked with one of deleted positions
     *
     * @return void
     */
    public function test_cohort_broken_rule_checking_when_one_of_positions_deleted() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $positions = $this->create_positions(2);
        $this->create_cohort_and_rules([], [], $positions);
        $DB->delete_records('pos', ['id' => current($positions)->id]);

        $this->perform_test();
    }

    /**
     * Checking whether the task runner is sending an email out to say that the rule is broken when there is a rule
     * that linked with one of deleted organisations
     *
     * @return void
     */
    public function test_cohort_broken_rule_checking_when_one_of_organisations_deleted() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $organisations = $this->create_organisations(2);
        $this->create_cohort_and_rules([], [], [], $organisations);
        $DB->delete_records('org', ['id' => current($organisations)->id]);

        $this->perform_test();
    }
}