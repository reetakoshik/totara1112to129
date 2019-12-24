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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/totara/cohort/db/upgradelib.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

/**
 * Test position rules.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit ./totara/cohort/tests/upgradelib_test.php
 *
 */
class totara_cohort_upgradelib_testcase extends advanced_testcase {

    private $generator;
    private $cohort;
    private $ruleset;
    private $program;
    private $course;

    protected function tearDown() {
        $this->generator = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->program = null;
        $this->course = null;
        parent::tearDown();
    }

    public function setUp() {

        $this->generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        // Creating dynamic cohort.
        $cohortdata = array('name' => 'Test Cohort', 'cohorttype' => cohort::TYPE_DYNAMIC);
        $this->cohort = $this->generator->create_cohort($cohortdata);
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);

        $coursedata = array('fullname' => 'Test Course');
        $this->getDataGenerator()->create_course($coursedata);

        $progdata = array('fullname' => 'Test Program');
        $this->getDataGenerator()->get_plugin_generator('totara_program')->create_program($progdata);
    }

    /**
     * Test the migration of some rules.
     */
    public function test_cohort_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule.
        $this->generator->create_cohort_rule_params($this->ruleset, 'learning', 'programcompletionduration',
                array('equal' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 1), array($this->program, false));

        // Create a control rule.
        $this->generator->create_cohort_rule_params($this->ruleset, 'learning', 'coursecompletionduration',
                array('equal' => COHORT_RULE_COMPLETION_OP_AFTER_FUTURE_DURATION, 'date' => 1), array($this->course, false));

        // Approve the rule changes and check them.
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(4, $DB->count_records('cohort_rules', array())); // Twice as many due to draft rules.

        // Migrate one of the rules to a new name.
        totara_cohort_migrate_rules('learning', 'programcompletionduration', 'learning', 'programcompletiondurationassigned');

        $this->assertEquals(4, $DB->count_records('cohort_rules', array()));
        $this->assertEquals(2, $DB->count_records('cohort_rules', array('name' => 'programcompletiondurationassigned')));
        $this->assertEquals(4, $DB->count_records('cohort_rules', array('ruletype' => 'learning')));

        // Now migrate the rule type.
        totara_cohort_migrate_rules('learning', 'programcompletiondurationassigned', 'teaching', 'programcompletiondurationassigned');

        $this->assertEquals(4, $DB->count_records('cohort_rules', array()));
        $this->assertEquals(2, $DB->count_records('cohort_rules', array('ruletype' => 'learning')));
        $this->assertEquals(2, $DB->count_records('cohort_rules', array('ruletype' => 'teaching')));
    }
}
