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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();

class totara_plan_lib_testcase extends advanced_testcase {
    /** @var totara_plan_generator $plangenerator */
    protected $plangenerator;

    protected function tearDown() {
        $this->plangenerator = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $CFG;
        parent::setUp();

        require_once($CFG->dirroot.'/totara/plan/lib.php');
        require_once($CFG->dirroot.'/totara/hierarchy/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $this->plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
    }

    /**
     * Test creating a learning plan and adding a course.
     */
    public function test_add_course_to_learning_plan() {
        $this->resetAfterTest(true);

        // Create a learning plan.
        $plan = $this->plangenerator->create_learning_plan();

        $course = $this->getDataGenerator()->create_course();

        // Add the course to the learning plan.
        $this->add_component_to_learning_plan($plan->id, 'course', $course->id);
    }

    /**
     * Test creating a learning plan and adding a competency.
     */
    public function test_add_competency_to_learning_plan() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a learning plan.
        $plan = $this->plangenerator->create_learning_plan();

        // Get a hierarchy generator.
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchy_gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create a new competency framework and check it exists.
        $comp_fw = $hierarchy_gen->create_framework('competency');
        $exists = $DB->record_exists('comp_framework', array('id' => $comp_fw->id));
        // Assert the existence of the framework.
        $this->assertTrue($exists);

        // Create a new competency and check it exists.
        $comp = $hierarchy_gen->create_hierarchy($comp_fw->id, 'competency', array('fullname' => 'Test Competency'));
        $exists = $DB->record_exists('comp', array('id' => $comp->id));
        // Assert the existence of the competency.
        $this->assertTrue($exists);

        $this->add_component_to_learning_plan($plan->id, 'competency', $comp->id);
    }

    /**
     * Test creating a learning plan and adding a program.
     */
    public function test_add_program_to_learning_plan() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a learning plan.
        $plan = $this->plangenerator->create_learning_plan();

        /** @var totara_plan_generator $plangenerator */
        $program_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        // Create a new program and check it exists
        $program = $program_gen->create_program();
        $exists = $DB->record_exists('prog', array('id' => $program->id));
        $this->assertTrue($exists);
        // Add program to learning plan.
        $this->add_component_to_learning_plan($plan->id, 'program', $program->id);
    }

    /**
     * Add a component to a competency framework.
     * @param int $planid
     * @param string $component
     * @param int $componentid
     */
    protected function add_component_to_learning_plan($planid, $component, $componentid) {
        global $DB;
        // Add the competency to the learning plan.
        $plan = new development_plan($planid);
        $componentobj = $plan->get_component($component);
        $componentobj->update_assigned_items(array($componentid));
        // Check the course has been assigned to the learning plan.
        $exists = $DB->record_exists('dp_plan_' . $component . '_assign', array('planid' => $planid, $component . 'id' => $componentid));
        // Assert the existence of the record.
        $this->assertTrue($exists);
    }

    /**
     * Change the state of all cert and prog completion records to certified, before the window opens.
     *
     * Borrowed from totara/certificaton/tests/certification_completion_test.php.
     */
    private function shift_completions_to_certified($timecompleted) {
        global $DB;

        // Manually change their state.
        $sql = "UPDATE {prog_completion}
                   SET status = :progstatus, timecompleted = :timecompleted, timedue = :timedue
                 WHERE coursesetid = 0";
        $params = array('progstatus' => STATUS_PROGRAM_COMPLETE, 'timecompleted' => $timecompleted,
            'timedue' => $timecompleted + 2000);
        $DB->execute($sql, $params);
        $sql = "UPDATE {certif_completion}
                   SET status = :certstatus, renewalstatus = :renewalstatus, certifpath = :certifpath,
                       timecompleted = :timecompleted, timewindowopens = :timewindowopens, timeexpires = :timeexpires";
        $params = array('certstatus' => CERTIFSTATUS_COMPLETED, 'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
            'certifpath' => CERTIFPATH_RECERT, 'timecompleted' => $timecompleted, 'timewindowopens' => $timecompleted + 1000,
            'timeexpires' => $timecompleted + 2000);
        $DB->execute($sql, $params);
    }

    /**
     * Tests the function dp_get_rol_tabs_visible.
     *
     * The particular scenario being tested here is that the programs is not returned as visible
     * when the user is only enrolled in certs.
     *
     * That issue can happen because we might test for program completion records when deciding whether
     * the programs tab should be visible. But certs use program completion records as well.
     */
    public function test_dp_get_rol_tabs_visible_cert_not_program() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        /** @var totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');

        // This program won't be assigned. Just helps to detect things like joins being done incorrectly.
        $program =  $program_generator->create_program();

        // We'll have the user complete this certification.
        $certprogramid = $program_generator->create_certification();
        $certprogram = new program($certprogramid);

        $program_generator->assign_to_program($certprogram->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        $this->shift_completions_to_certified(time());
        $visible_tabs = dp_get_rol_tabs_visible($user->id);
        $this->assertContains('certifications', $visible_tabs);
        $this->assertNotContains('programs', $visible_tabs);
    }

    /**
     * Tests the function dp_get_rol_tabs_visible.
     *
     * The particular scenario is the inverse of the test above (test_dp_get_rol_tabs_visible_cert_not_program).
     * The issue tested above is the main concern. We're just making sure the opposite doesn't happen while
     * making the above test pass.
     */
    public function test_dp_get_rol_tabs_visible_program_not_cert() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        /** @var totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');

        // We'll have the user complete this program.
        $program =  $program_generator->create_program();

        // Adding a certification. This won't be assigned though.
        $certprogramid = $program_generator->create_certification();
        $certprogram = new program($certprogramid);

        $program_generator->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        $program->update_program_complete($user->id, array('status' => STATUS_PROGRAM_COMPLETE));

        $visible_tabs = dp_get_rol_tabs_visible($user->id);
        $this->assertContains('programs', $visible_tabs);
        $this->assertNotContains('certifications', $visible_tabs);
    }
}
