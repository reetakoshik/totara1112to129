<?php
/*
 * This file is part of Totara LMS
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
 * @author  Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_workflow
 */

/**
 * Tests for workflow base class.
 *
 * @author  Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_workflow
 */

defined('MOODLE_INTERNAL') || die();

class totara_workflow_workflow_testcase extends advanced_testcase {

    public function test_workflow_basics() {
        $workflow = \core\workflow\core_course\coursecreate\standard::instance();
        $this->assertInstanceOf('\core\workflow\core_course\coursecreate\standard', $workflow);

        // Workflow params should be empty.
        $params = $workflow->get_params();
        $this->assertEquals([], $params);

        // Workflow should return correct url.
        $url = $workflow->get_url();
        $expectedurl = new \moodle_url('/course/edit.php');
        $this->assertEquals($expectedurl, $url);

        // Workflow params should be correctly stored and returned.
        $workflow->set_params(['foo' => 'bar']);
        $params = $workflow->get_params();
        $this->assertEquals(['foo' => 'bar'], $params);

        // Workflow should return correct url with params.
        $url = $workflow->get_url();
        $expectedurl = new \moodle_url('/course/edit.php', ['foo' => 'bar']);
        $this->assertEquals($expectedurl, $url);

    }

    public function test_can_access() {
        global $DB;
        $this->resetAfterTest(true);
        $datagenerator = $this->getDataGenerator();
        $student = $datagenerator->create_user();
        $coursecreator = $datagenerator->create_user();
        $creatorrole = $DB->get_record('role', array('shortname' => 'coursecreator'));
        role_assign($creatorrole->id, $coursecreator->id, context_system::instance());

        // Student should not be able to access this workflow, because they can't create courses.
        $workflow = \core\workflow\core_course\coursecreate\standard::instance();

        $this->setUser($student->id);
        $this->assertFalse($workflow->is_available());

        // Course creator should be able to access this workflow, because they can create courses.
        $this->setUser($coursecreator->id);
        $this->assertTrue($workflow->is_available());
    }

    /**
     * Provides data used by next test.
     */
    public function example_classnames_data_provider() {
        return [
            // Test a valid case with simple placeholders.
            [
                'component\\workflow\\managercomponent\\manager\\workflow',
                true,
                'managercomponent',
                'manager',
                'component',
                'workflow',
            ],
            // Test a realistic case.
            [
                'mod_facetoface\\workflow\\core_course\\coursecreate\\seminar',
                true,
                'core_course',
                'coursecreate',
                'mod_facetoface',
                'seminar',
            ],
            // Very badly formed classname
            [
                'badclass',
                false,
                null,
                null,
                null,
                null,
            ],
            // Workflow manager class
            [
                'component\\workflow_manager\\coursecreate\\seminar',
                false,
                null,
                null,
                null,
                null,
            ],
            // Valid structure but invalid special chars in strings.
            [
                'bad_special_chars!\\workflow\\core_course\\course&create\\semi(nar',
                false,
                null,
                null,
                null,
                null,
            ],
            // Valid component and manager but missing workflow
            [
                'valid_component\\workflow\\managercomponent\\validmanager',
                false,
                null,
                null,
                null,
                null,
            ],
            // Valid class but with additional characters on the end.
            [
                'valid_component\\workflow\\managercomponent\\validmanager\\validworkflow\\extrastuff',
                false,
                null,
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider example_classnames_data_provider
     */
    public function test_split_classname(string $classname, bool $valid, ?string $expectedmanagercomponent, ?string $expectedmanager, ?string $expectedworkflowcomponent, ?string $expectedworkflow) {

        // When classname is not valid a coding_exception is thrown.
        if (!$valid) {
            $this->expectException("coding_exception");
        }

        // Reflection needed to test protected method.
        $rc = new \ReflectionClass('\totara_workflow\workflow\base');
        $rcm = $rc->getMethod('split_classname');
        $rcm->setAccessible(true);
        list($managercomponent, $manager, $workflowcomponent, $workflow) = $rcm->invokeArgs(null, [$classname]);

        if ($valid) {
            $this->assertEquals($expectedmanagercomponent, $managercomponent);
            $this->assertEquals($expectedmanager, $manager);
            $this->assertEquals($expectedworkflowcomponent, $workflowcomponent);
            $this->assertEquals($expectedworkflow, $workflow);
        }
    }

    public function test_export_for_template() {
        global $OUTPUT;
        $workflow = \core\workflow\core_course\coursecreate\standard::instance();

        $contextdata = $workflow->export_for_template($OUTPUT);
        $this->assertEquals('core_course', $contextdata['managercomponent']);
        $this->assertEquals('coursecreate', $contextdata['manager']);
        $this->assertEquals('core', $contextdata['workflowcomponent']);
        $this->assertEquals('standard', $contextdata['workflow']);
    }

    public function test_enabled() {
        $this->resetAfterTest(true);
        $workflow = \core\workflow\core_course\coursecreate\standard::instance();
        $workflow->enable();
        $this->assertTrue($workflow->is_enabled());
        $workflow->disable();
        $this->assertFalse($workflow->is_enabled());
    }
}
