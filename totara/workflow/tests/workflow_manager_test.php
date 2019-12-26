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
 * Tests for workflow manager base class.
 *
 * @author  Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_workflow
 */

defined('MOODLE_INTERNAL') || die();

class totara_workflow_workflow_manager_testcase extends advanced_testcase {

    public function test_workflow_manager_basics() {
        $wm = new \core_course\workflow_manager\coursecreate();

        $this->assertInstanceOf('\core_course\workflow_manager\coursecreate', $wm);

        // Workflow manager params should be empty.
        $params = $wm->get_params();
        $this->assertEquals([], $params);

        $expectedurl = new \moodle_url('/totara/workflow/manager.php', [
            'component' => 'core_course',
            'manager' => 'coursecreate',
        ]);
        $url = $wm->get_url();
        $this->assertEquals($expectedurl, $url);

        // Workflow manager params should be correctly stored and returned.
        $wm->set_params(['foo' => 'bar']);
        $params = $wm->get_params();
        $this->assertEquals(['foo' => 'bar'], $params);

        // Params should be passed in URL.
        $expectedurl->params(['foo' => 'bar']);
        $url = $wm->get_url();
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

        $wm = new \core_course\workflow_manager\coursecreate();

        $this->setUser($student->id);

        $workflows = $wm->get_workflows();
        $this->assertEmpty($workflows);
        $this->assertFalse($wm->workflows_available());

        $workflows = $wm->get_workflows(true);
        $this->assertNotEmpty($workflows);

        $this->setUser($coursecreator->id);

        $workflows = $wm->get_workflows();
        $this->assertNotEmpty($workflows);
        $this->assertTrue($wm->workflows_available());

        $workflows = $wm->get_workflows(true);
        $this->assertNotEmpty($workflows);

    }

    /**
     * Provides data used by next test.
     */
    public function example_classnames_data_provider() {
        return [
            // Test a valid case with simple placeholders.
            [
                'component\\workflow_manager\\manager',
                true,
                'component',
                'manager',
            ],
            // Test a realistic case.
            [
                'core_course\\workflow_manager\\coursecreate',
                true,
                'core_course',
                'coursecreate',
            ],
            // Very badly formed classname
            [
                'badclass',
                false,
                null,
                null,
            ],
            // Workflow  class
            [
                'component\\workflow\\core_course\\coursecreate\\seminar',
                false,
                null,
                null,
            ],
            // Valid structure but invalid special chars in strings.
            [
                'bad_special_chars!\\managercomponent\\workflow_manager\\course&create',
                false,
                null,
                null,
            ],
            // Valid component and manager with extra workflow
            [
                'valid_component\\workflow_manager\\managercomponent\\validmanager\\workflow',
                false,
                null,
                null,
            ],
            // Valid component but missing manager
            [
                'valid_component\\workflow_manager',
                false,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider example_classnames_data_provider
     */
    public function test_split_classname(string $classname, bool $valid, ?string $expectedcomponent, ?string $expectedmanager) {
        // When classname is not valid a coding_exception is thrown.
        if (!$valid) {
            $this->expectException("coding_exception");
        }

        // Reflection needed to test protected method.
        $rc = new \ReflectionClass('\totara_workflow\workflow_manager\base');
        $rcm = $rc->getMethod('split_classname');
        $rcm->setAccessible(true);
        list($component, $manager) = $rcm->invokeArgs(null, [$classname]);

        if ($valid) {
            $this->assertEquals($expectedcomponent, $component);
            $this->assertEquals($expectedmanager, $manager);
        }
    }

    public function test_export_for_template() {
        global $OUTPUT, $CFG;
        $wm = new \core_course\workflow_manager\coursecreate();

        $contextdata = $wm->export_for_template($OUTPUT);

        $this->assertEquals($CFG->wwwroot, $contextdata['wwwroot']);
        $this->assertEquals(sesskey(), $contextdata['sesskey']);
        $this->assertNotEmpty($contextdata['workflows']);

    }

    public function test_get_workflow() {
        $wm = new \core_course\workflow_manager\coursecreate();

        // Manager should return valid workflow instances.
        $workflow = $wm->get_workflow('\core\workflow\core_course\coursecreate\standard');
        $this->assertInstanceOf('\core\workflow\core_course\coursecreate\standard', $workflow);

        // Manager should throw a coding_exception if requested to instantiate invalid class.
        $this->expectException("coding_exception");
        $workflow = $wm->get_workflow('\this\is\not\a\valid\workflow');

    }
    public function test_get_wm_classes() {
        $wmclasses = \totara_workflow\workflow_manager\base::get_all_workflow_manager_classes();

        // There should be workflow managers.
        $this->assertNotEmpty($wmclasses);

        // The coursecreate manager should be one of them.
        $this->assertContains('core_course\workflow_manager\coursecreate', $wmclasses);

    }

}
