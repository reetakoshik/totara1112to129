<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test hierarchy type creation.
 */
class totara_hierarchy_create_type_testcase extends advanced_testcase {

    protected $hierarchy_generator = null;

    protected function tearDown() {
        $this->hierarchy_generator = null;
        parent::tearDown();
    }

    protected function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Set totara_hierarchy_generator.
        $this->hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
    }

    /**
     * Tests the organisation type.
     */
    public function test_organisation_type() {
        global $DB;

        // Create organisation type.
        $typeid = $this->hierarchy_generator->create_org_type();
        $this->assertTrue($DB->record_exists('org_type', array('id' => $typeid)));
    }

    /**
     * Tests the position type.
     */
    public function test_position_type() {
        global $DB;

        // Create position type.
        $typeid = $this->hierarchy_generator->create_pos_type();
        $this->assertTrue($DB->record_exists('pos_type', array('id' => $typeid)));
    }

    /**
     * Tests the goal type.
     */
    public function test_goal_type() {
        global $DB;

        // Create goal type.
        $typeid = $this->hierarchy_generator->create_goal_type();
        $this->assertTrue($DB->record_exists('goal_type', array('id' => $typeid)));
    }

    /**
     * Tests the competency type.
     */
    public function test_competency_type() {
        global $DB;

        // Create competency type.
        $typeid = $this->hierarchy_generator->create_comp_type();
        $this->assertTrue($DB->record_exists('comp_type', array('id' => $typeid)));
    }
}
