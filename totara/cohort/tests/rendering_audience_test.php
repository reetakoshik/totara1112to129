<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
require_once("{$CFG->dirroot}/totara/cohort/lib.php");


/**
 * Class totara_cohort_rendering_audience_testcase
 */
class totara_cohort_rendering_audience_testcase extends advanced_testcase {
    /**
     * @param int $max
     * @return stdClass[]
     */
    private function create_courses($max=2) {
        $generator = $this->getDataGenerator();

        $data = [];
        for ($i = 0; $i < $max; $i++) {
            $data[] = $this->getDataGenerator()->create_course();
        }

        return $data;
    }

    /**
     * @param int $max
     * @return stdClass[]
     */
    private function create_programs($max=2) {
        /** @var totara_program_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $data = [];
        for ($i = 0; $i < $max; $i++) {
            $data[] = $generator->create_program();
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
        $positions = [];
        for ($i = 0; $i < $max; $i++) {
            $positions[] = $generator->create_pos(['frameworkid' => $framework->id]);
        }
        return $positions;
    }

    /**
     * Creating the cohort and rules
     * @param int[]     $listofids
     * @param string    $ruletype
     * @param string    $rulename
     * @param array     $ruleparams
     * @param string    $paramname
     * @return stdClass
     */
    private function create_cohort_and_rules(array $listofids, $ruletype, $rulename, array $ruleparams, $paramname) {
        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $cohort = $generator->create_cohort(['cohortype' => Cohort::TYPE_DYNAMIC]);
        $rulesetid = cohort_rule_create_ruleset($cohort->activecollectionid);

        $generator->create_cohort_rule_params($rulesetid, $ruletype, $rulename, $ruleparams, $listofids, $paramname);

        return $cohort;
    }

    /**
     * @param stdClass $cohort
     * @return stdClass[]
     */
    private function get_rulesets(stdClass $cohort) {
        global $DB;
        $rulesets = $DB->get_records('cohort_rulesets', ['rulecollectionid' => $cohort->activecollectionid], 'sortorder');
        foreach ($rulesets as $index => $ruleset) {
            $rules = $DB->get_records('cohort_rules', array('rulesetid' => $ruleset->id), 'sortorder');
            $rulesets[$index]->rules = $rules;
        }

        return $rulesets;
    }

    /**
     * A test suite to check whether the audience rule rendering the deleted output or not. As within this test, the
     * program linked into the audience rule get deleted, therefore the rule that has the link with the program should
     * also warning that the rule was deleted.
     *
     * @return void
     */
    public function test_rendering_audience_rule_with_deleted_programs() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $programs = $this->create_programs();
        $listofids = [];
        foreach ($programs as $program) {
            $listofids[] = $program->id;
            unset($program);
        }

        $cohort = $this->create_cohort_and_rules(
            $listofids,
            'learning',
            'programcompletionlist',
            ['operator' => COHORT_RULE_COMPLETION_OP_ALL],
            'listofids'
        );

        $rulesets = $this->get_rulesets($cohort);
        $this->assertCount(1, $rulesets);
        $ruleset = current($rulesets);

        $deletedprogram = array_shift($programs);
        // Deleting the program record here, so that the audience rule is having an invalid ruleset
        $DB->delete_records('prog', ['id' => $deletedprogram->id]);

        $ruledata = cohort_ruleset_form_template_object($ruleset);
        $ruleoutput = $ruledata->rules[0];
        if (!$ruleoutput) {
            $this->fail("Unable to get the rule decription output");
        }

        $this->assertContains("Deleted (ID: {$deletedprogram->id})", $ruleoutput->ruledescription);
    }

    /**
     * Same scenario as above, but this test suite is for courses
     * @return void
     */
    public function test_rendering_audience_rule_with_deleted_courses() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $courses = $this->create_courses();
        $listofids = [];
        foreach ($courses as $course) {
            $listofids[] = $course->id;
            unset($course);
        }

        $cohort = $this->create_cohort_and_rules(
            $listofids,
            'learning',
            'coursecompletionlist',
            ['operator' => COHORT_RULE_COMPLETION_OP_ALL],
            'listofids'
        );

        $rulesets = $this->get_rulesets($cohort);
        $this->assertCount(1, $rulesets);
        $ruleset = current($rulesets);

        $deletedcourse = array_shift($courses);
        $DB->delete_records('course', ['id' => $deletedcourse->id]);

        $ruledata = cohort_ruleset_form_template_object($ruleset);
        $ruleoutput = $ruledata->rules[0];
        if (!$ruleoutput) {
            $this->fail("Unable to retrieve the ruleoutput");
        }

        $this->assertContains("Deleted (ID: {$deletedcourse->id})", $ruleoutput->ruledescription);
    }

    /**
     * Same scenario as with the test suite of deleted course and program. This test suite is for deleted positions
     * @return void
     */
    public function test_rendering_audience_rule_with_deleted_positions() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $positions = $this->create_positions(2);
        $listofvalues = [];
        foreach ($positions as $position) {
            $listofvalues[] = $position->id;
            unset($position);
        }

        $cohort = $this->create_cohort_and_rules(
            $listofvalues,
            'alljobassign',
            'positions',
            ['equal' => COHORT_RULES_OP_IN_EQUAL, 'includechildren' => 1],
            'listofvalues'
        );

        $rulesets = $this->get_rulesets($cohort);
        $this->assertCount(1, $rulesets);
        $ruleset = current($rulesets);

        $deletedposition = array_shift($positions);
        $DB->delete_records('pos', ['id' => $deletedposition->id]);

        $ruledata = cohort_ruleset_form_template_object($ruleset);
        $ruleoutput = $ruledata->rules[0];
        if (!$ruleoutput) {
            $this->fail("Unable to retrieve the ruleoutput");
        }

        $this->assertContains("Deleted (ID: {$deletedposition->id}", $ruleoutput->ruledescription);
    }
}
