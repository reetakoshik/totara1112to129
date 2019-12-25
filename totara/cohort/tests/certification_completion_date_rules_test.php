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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/cohort/tests/certification_testcase.php');

/**
 * Test certificate status completion rules.
 *
 */
class totara_cohort_certification_date_rules_testcase extends totara_cohort_certification_testcase {

    public $program_generator          = null;
    public $cohort_generator           = null;
    public $user_groups                = [];
    public $adminuser                  = null;
    public $programs                   = [];
    public $courses                    = [];
    public $certifications             = [];
    public $cohort                     = null;
    public $ruleset                    = null;

    protected function tearDown() {
        $this->program_generator        = null;
        $this->cohort_generator         = null;
        $this->user_groups              = null;
        $this->adminuser                = null;
        $this->courses                  = null;
        $this->programs                 = null;
        $this->certifications           = null;
        $this->cohort                   = null;
        $this->ruleset                  = null;

        parent::tearDown();
    }

    public function setUp() {
        global $DB;

        parent::setup();
        set_config('enablecompletion', 1);
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->adminuser = $DB->get_record('user', ['username' => 'admin']);

        // Set totara_cohort generator.
        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create programs, mostly so that we don't end up with coincidental success due to matching ids.
        $this->program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        for ($i = 1; $i <= self::TEST_PROGRAMS_COUNT; $i++) {
            $this->programs[$i] = $this->program_generator->create_program();
        }

        // Turn off programs. This is to test that it doesn't interfere with certification status in anyway.
        set_config('enableprograms', TOTARA_DISABLEFEATURE);

        // Create certifications.
        for ($i = 1; $i <= self::TEST_CERTIFICATIONS_COUNT; $i++) {
            $this->certifications[$i] = $this->getDataGenerator()->create_certification();

            // Also create a course, each certification to have one course associated to it.
            $this->courses[$i] = $this->getDataGenerator()->create_course();

            // Add the course to the certification.
            $this->getDataGenerator()->add_courseset_program($this->certifications[$i]->id, [$this->courses[$i]->id], CERTIFPATH_CERT);
            $this->getDataGenerator()->add_courseset_program($this->certifications[$i]->id, [$this->courses[$i]->id], CERTIFPATH_RECERT);
        }

        // Create cohort.
        $this->cohort = $this->cohort_generator->create_cohort(['cohorttype' => cohort::TYPE_DYNAMIC]);
        $this->assertTrue($DB->record_exists('cohort', ['id' => $this->cohort->id]));
        $this->assertEquals(0, $DB->count_records('cohort_members', ['cohortid' => $this->cohort->id]));

        // Create ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);

        // Create the test user groups stored in $this->$user_groups
        $this->create_users_and_assignments();
    }

    /**
     * Get 'now' time to ensure we are using the same 'base' time for all tests.
     *
     * Using a static method here as class propeties are not availbale in the Data provider.
     *
     * @return int timestamp
     */
    public static function timenow() {
        static $timenow = null;
        if (!$timenow) {
            $timenow = time();
        }
        return $timenow;
    }

    /**
     * Create an array of user groups in $this->user_groups
     *
     * The following groups are created
     * - not-completed-cert1
     * - completed-cert1-now
     * - completed-cert1-5days-ago
     * - completed-cert1-35days-ago
     * - completed-cert1-2years-ago
     *
     * - not-completed-cert2
     * - completed-cert2-now
     * - completed-cert2-5days-ago
     * - completed-cert2-35days-ago
     * - completed-cert2-2years-ago
     *
     */
    public function create_users_and_assignments() {

        $this->user_groups = [
            'not-completed-cert1'           => [],
            'completed-cert1-now'           => [],
            'completed-cert1-3days-ago'     => [],
            'completed-cert1-35days-ago'    => [],
            'completed-cert1-2years-ago'    => [],

            'not-completed-cert2,cert3'           => [],
            'completed-cert2,cert3-now'           => [],
            'completed-cert2,cert3-3days-ago'     => [],
            'completed-cert2,cert3-35days-ago'    => [],
            'completed-cert2,cert3-2years-ago'    => [],
        ];

        // Group not-completed-cert1
        $group = "not-completed-cert1";
        $cert = 1;
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);

        // Group completed-cert1-now
        $group = "completed-cert1-now";
        $cert = 1;
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow());

        // Group completed-cert1-3days-ago
        $group = "completed-cert1-3days-ago";
        $cert = 1;
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (3 * DAYSECS));

        // Group completed-cert1-35days-ago
        $group = "completed-cert1-35days-ago";
        $cert = 1;
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (35 * DAYSECS));

        // Group completed-cert1-2years-ago
        $group = "completed-cert1-2years-ago";
        $cert = 1;
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (2 * YEARSECS));

        // Group not-completed-cert2,cert3
        $group = "Group not-completed-cert2,cert3";
        $this->add_users_to_group($group);
        $cert = 2;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $cert = 3;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);

        // Group completed-cert2,cert3-now
        $group = "completed-cert2,cert3-now";
        $this->add_users_to_group($group);
        $cert = 2;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow());
        $cert = 3;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow());

        // Group completed-cert2,cert3-3days-ago
        $group = "completed-cert2,cert3-3days-ago";
        $this->add_users_to_group($group);
        $cert = 2;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (3 * DAYSECS));
        $cert = 3;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (3 * DAYSECS));

        // Group completed-cert2,cert3-35days-ago
        $group = "completed-cert2,cert3-35days-ago";
        $this->add_users_to_group($group);
        $cert = 2;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (35 * DAYSECS));
        $cert = 3;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (35 * DAYSECS));

        // Group completed-cert2,cert3-2years-ago
        $group = "completed-cert2,cert3-2years-ago";
        $this->add_users_to_group($group);
        $cert = 2;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (2 * YEARSECS));
        $cert = 3;
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id, self::timenow() - (2 * YEARSECS));
    }

    /**
     * Data provider.
     */
    public function data_certification_status_date() {

        // Tests for the following.
        //
        // fixed date.
        //
        // fixed-date-before   | date now              | cert1
        // fixed-date-before   | date now -5 days      | cert1
        // fixed-date-before   | date now -50 days     | cert1
        // fixed-date-before   | date now -1 year      | cert1
        // fixed-date-before   | date now -3 year      | cert1
        //
        // fixed-date-after    | date now +1 day       | cert1
        // fixed-date-after    | date now              | cert1
        // fixed-date-after    | date now -5 days      | cert1
        // fixed-date-after    | date nowv-50 days     | cert1
        // fixed-date-after    | date now -1 year      | cert1
        // fixed-date-after    | date now -3 year      | cert1
        //
        // fixed-date-before   | date now              | cert2, cert3
        // fixed-date-before   | date now -5 days      | cert2, cert3
        // fixed-date-before   | date now -50 days     | cert2, cert3
        // fixed-date-before   | date now -1 year      | cert2, cert3
        // fixed-date-before   | date now -3 year      | cert2, cert3
        //
        // fixed-date-after    | date now +1 day       | cert2, cert3
        // fixed-date-after    | date now              | cert2, cert3
        // fixed-date-after    | date now -5 days      | cert2, cert3
        // fixed-date-after    | date nowv-50 days     | cert2, cert3
        // fixed-date-after    | date now -1 year      | cert2, cert3
        // fixed-date-after    | date now -3 year      | cert2, cert3
        //
        //
        // relative date.
        //
        // relative-date-within-previous | 3 days      | cert1
        // relative-date-within-previous | 10 days     | cert1
        // relative-date-within-previous | 50 days     | cert1
        // relative-date-within-previous | 3-years     | cert1
        //
        // relative-date-before-previous | 3 days      | cert1
        // relative-date-before-previous | 10 days     | cert1
        // relative-date-before-previous | 50 days     | cert1
        // relative-date-before-previous | 3 years     | cert1
        //
        // relative-date-within-previous | 3 days      | cert2, cert3
        // relative-date-within-previous | 10 days     | cert2, cert3
        // relative-date-within-previous | 50 days     | cert2, cert3
        // relative-date-within-previous | 3-years     | cert2, cert3
        //
        // relative-date-before-previous | 3 days      | cert2, cert3
        // relative-date-before-previous | 10 days     | cert2, cert3
        // relative-date-before-previous | 50 days     | cert2, cert3
        // relative-date-before-previous | 3 years     | cert2, cert3

        $data = [
            //
            // Fixed dates.
            //

            // fixed-date-before | date now | cert1
            ['fixed-date-before | date now | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow()], ['completed-cert1-now', 'completed-cert1-35days-ago', 'completed-cert1-3days-ago', 'completed-cert1-2years-ago']],

            // fixed-date-before | date now -5 days | cert1
            ['fixed-date-before | date now -5 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (5 * DAYSECS)], ['completed-cert1-35days-ago', 'completed-cert1-2years-ago']],

            // fixed-date-before| date now -50 days | cert1
            ['fixed-date-before| date now -50 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (50 * DAYSECS)], ['completed-cert1-2years-ago']],

            // fixed-date-before | date now -1 year | cert1
            ['fixed-date-before | date now -1 year | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (1 * YEARSECS)], ['completed-cert1-2years-ago']],

            // fixed-date-before | date now -3 year | cert1
            ['fixed-date-before | date now -3 year | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (3 * YEARSECS)], []],

            // fixed-date-after | date now +1 day | cert1
            ['fixed-date-after | date now +1 day | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() + (1 * DAYSECS)], []],

            // fixed-date-after | date now | cert1
            ['fixed-date-after | date now | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow()], ['completed-cert1-now']],

            // fixed-date-after | date now -5 days | cert1
            ['fixed-date-after | date now -5 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (5 * DAYSECS)], ['completed-cert1-now', 'completed-cert1-3days-ago']],

            // fixed-date-after| date now -50 days | cert1
            ['fixed-date-after| date now -50 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (50 * DAYSECS)], ['completed-cert1-now', 'completed-cert1-3days-ago', 'completed-cert1-35days-ago']],

            // fixed-date-after | date now -1 year | cert1
            ['fixed-date-after | date now -1 year | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (1 * YEARSECS)], ['completed-cert1-now', 'completed-cert1-3days-ago', 'completed-cert1-35days-ago']],

            // fixed-date-after | date now -3 year | cert1
            ['fixed-date-after | date now -3 year | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (3 * YEARSECS)], ['completed-cert1-now', 'completed-cert1-35days-ago', 'completed-cert1-3days-ago', 'completed-cert1-2years-ago']],

            // fixed-date-before | date now | cert2, cert3
            ['fixed-date-before | date now | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow()], ['completed-cert2,cert3-now', 'completed-cert2,cert3-3days-ago', 'completed-cert2,cert3-35days-ago', 'completed-cert2,cert3-2years-ago']],

            // fixed-date-before | date now -5 days | cert2, cert3
            ['fixed-date-before | date now -5 days | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (5 * DAYSECS)], ['completed-cert2,cert3-35days-ago', 'completed-cert2,cert3-2years-ago']],

            // fixed-date-before| date now -50 days | cert2, cert3
            ['fixed-date-before| date now -50 days | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (50 * DAYSECS)], ['completed-cert2,cert3-2years-ago']],

            // fixed-date-before | date now -1 year | cert2, cert3
            ['fixed-date-before | date now -1 year | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (1 * YEARSECS)], ['completed-cert2,cert3-2years-ago']],

            // fixed-date-before | date now -3 year | cert2, cert3
            ['fixed-date-before | date now -3 year | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN, 'date' => self::timenow() - (3 * YEARSECS)], []],

            // fixed-date-after | date now +1 day | cert2, cert3
            ['fixed-date-after | date now +1 day | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() + (1 * DAYSECS)], []],

            // fixed-date-after | date now | cert2, cert3
            ['fixed-date-after | date now | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow()], ['completed-cert2,cert3-now']],

            // fixed-date-after | date now -5 days | cert2, cert3
            ['fixed-date-after | date now -5 days | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (5 * DAYSECS)], ['completed-cert2,cert3-now', 'completed-cert2,cert3-3days-ago']],

            // fixed-date-after| date now -50 days | cert2, cert3
            ['fixed-date-after| date now -50 days | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (50 * DAYSECS)], ['completed-cert2,cert3-now', 'completed-cert2,cert3-3days-ago', 'completed-cert2,cert3-35days-ago']],

            // fixed-date-after | date now -1 year | cert2, cert3
            ['fixed-date-after | date now -1 year | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (1 * YEARSECS)], ['completed-cert2,cert3-now', 'completed-cert2,cert3-3days-ago', 'completed-cert2,cert3-35days-ago']],

            // fixed-date-after | date now -3 year | cert2, cert3
            ['fixed-date-after | date now -3 year | cert2, cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN, 'date' => self::timenow() - (3 * YEARSECS)], ['completed-cert2,cert3-now', 'completed-cert2,cert3-35days-ago', 'completed-cert2,cert3-3days-ago', 'completed-cert2,cert3-2years-ago']],


            //
            // Relative dates.
            //

            // relative-date-within-previous | 3 days | cert1
            ['relative-date-within-previous | 3 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 3], ['completed-cert1-now']],

            // relative-date-within-previous | 10 days | cert1
            ['relative-date-within-previous | 10 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 10], ['completed-cert1-now', 'completed-cert1-3days-ago']],

            // relative-date-within-previous | 50 days | cert1
            ['relative-date-within-previous | 50 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 50], ['completed-cert1-now', 'completed-cert1-3days-ago', 'completed-cert1-35days-ago']],

            // relative-date-within-previous | 3-years | cert1
            ['relative-date-within-previous | 3-years | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 1092], ['completed-cert1-now', 'completed-cert1-3days-ago', 'completed-cert1-35days-ago', 'completed-cert1-2years-ago']],

            // relative-date-before-previous | 3 days | cert1
            ['relative-date-before-after | 3 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 3], ['completed-cert1-3days-ago', 'completed-cert1-35days-ago', 'completed-cert1-2years-ago']],

            // relative-date-before-previous | 10 days | cert1
            ['relative-date-before-after | 10 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 10], ['completed-cert1-35days-ago', 'completed-cert1-2years-ago']],

            // relative-date-before-previous | 50 days | cert1
            ['relative-date-before-after | 50 days | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 50], ['completed-cert1-2years-ago']],

            // relative-date-before-previous | 3-years | cert1
            ['relative-date-before-after | 3-years | cert1', [1], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 1092], []],

            // relative-date-within-previous | 3 days | cert2,cert3
            ['relative-date-within-previous | 3 days | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 3], ['completed-cert2,cert3-now']],

            // relative-date-within-previous | 10 days | cert2,cert3
            ['relative-date-within-previous | 10 days | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 10], ['completed-cert2,cert3-now', 'completed-cert2,cert3-3days-ago']],

            // relative-date-within-previous | 50 days | cert2,cert3
            ['relative-date-within-previous | 50 days | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 50], ['completed-cert2,cert3-now', 'completed-cert2,cert3-3days-ago', 'completed-cert2,cert3-35days-ago']],

            // relative-date-within-previous | 3-years | cert2,cert3
            ['relative-date-within-previous | 3-years | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION, 'date' => 1092], ['completed-cert2,cert3-now', 'completed-cert2,cert3-3days-ago', 'completed-cert2,cert3-35days-ago', 'completed-cert2,cert3-2years-ago']],

            // relative-date-before-previous | 3 days | cert2,cert3
            ['relative-date-before-after | 3 days | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 3], ['completed-cert2,cert3-3days-ago', 'completed-cert2,cert3-35days-ago', 'completed-cert2,cert3-2years-ago']],

            // relative-date-before-previous | 10 days | cert2,cert3
            ['relative-date-before-after | 10 days | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 10], ['completed-cert2,cert3-35days-ago', 'completed-cert2,cert3-2years-ago']],

            // relative-date-before-previous | 50 days | cert2,cert3
            ['relative-date-before-after | 50 days | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 50], ['completed-cert2,cert3-2years-ago']],

            // relative-date-before-previous | 3-years | cert2,cert3
            ['relative-date-before-after | 3-years | cert2,cert3', [2,3], ['operator' => COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION, 'date' => 1092], []],

        ];

        return $data;
    }

    /**
     * @dataProvider data_certification_status_date
     */
    public function test_certification_date_rule($name, $certifications, $params, $usergroups) {
        global $DB;

        // Users that should be in this audience.
        $users = [];
        foreach ($usergroups as $usergroup) {
            $users = array_merge($users, $this->user_groups[$usergroup]);
        }

        // Process listofids.
        $listofids = [];
        foreach ($certifications as $certification) {
            $listofids[] = $this->certifications[$certification]->id;
        }

        // Create certification status rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'certificationcompletiondate', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // Check we have the correct members.
        $members = $DB->get_records('cohort_members', ['cohortid' => $this->cohort->id], '', 'userid');
        $this->assertEquals(count($users), count($members), 'Failed for ' . $name);
        foreach ($users as $user) {
            $this->assertTrue(array_key_exists($user->id, $members), 'Failed for ' . $name);
        }
    }

}
