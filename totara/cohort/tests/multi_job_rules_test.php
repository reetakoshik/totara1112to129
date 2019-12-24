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
require_once($CFG->libdir . '/testing/generator/lib.php');

/**
 * Test multiple job assignment rules.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_cohort_position_rules_testcase
 *
 */
class totara_cohort_multi_jobs_rules_testcase extends advanced_testcase {

    private $controluser = null;
    private $man1 = null;
    private $man2 = null;
    private $man3 = null;
    private $pos1 = null;
    private $pos2 = null;
    private $pos3 = null;
    private $org1 = null;
    private $org2 = null;
    private $org3 = null;
    private $posfw = null;
    private $ptype1 = null;
    private $ptype2 = null;
    private $orgfw = null;
    private $otype1 = null;
    private $otype2 = null;
    private $cohort = null;
    private $ruleset = 0;
    /** @var totara_cohort_generator $cohort_generator */
    private $cohort_generator = null;
    /** @var totara_hierarchy_generator $hierarchy_generator */
    private $hierarchy_generator = null;
    const TEST_POSITION_COUNT_MEMBERS = 24;

    protected function tearDown() {
        $this->controluser = null;
        $this->man1 = null;
        $this->man2 = null;
        $this->man3 = null;
        $this->pos1 = null;
        $this->pos2 = null;
        $this->pos3 = null;
        $this->org1 = null;
        $this->org2 = null;
        $this->org3 = null;
        $this->posfw = null;
        $this->ptype1 = null;
        $this->ptype2 = null;
        $this->orgfw = null;
        $this->otype1 = null;
        $this->otype2 = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->cohort_generator = null;
        $this->hierarchy_generator = null;
        parent::tearDown();
    }

    /**
     * Users positions per job assignment.
     *
     * + --------+--------------------------------------------------------------------------+
     * |         |    Job Assignment 1    |    Job Assignment 2    |    Job Assignment 3    |
     * |         | Pos1  |  Pos2  |  Pos3 | Pos1  |  Pos2  |  Pos3 | Pos1  |  Pos2  |  Pos3 |
     * + --------+--------------------------------------------------------------------------+
     * | User 1  |       |        |       |       |        |       |       |        |       |
     * | User 2  |       |        |   3   |       |   2    |       |   1   |        |       |
     * | User 3  |       |   2    |       |   1   |        |       |       |        |       |
     * | User 4  |   1   |        |       |       |   2    |       |   1   |        |       |
     * | User 5  |       |        |       |       |        |       |       |        |       |
     * | User 6  |       |   2    |       |   1   |        |       |   1   |        |       |
     * | User 7  |       |        |       |       |        |       |       |        |       |
     * | User 8  |   1   |        |       |       |   2    |       |   1   |        |       |
     * | User 9  |       |   2    |       |   1   |        |       |       |        |       |
     * | User 10 |       |        |   3   |       |   2    |       |   1   |        |       |
     * | User 11 |       |        |       |       |        |       |       |        |       |
     * | User 12 |   1   |        |       |   1   |        |       |   1   |        |       |
     * | User 13 |       |        |       |       |        |       |       |        |       |
     * | User 14 |       |        |   3   |       |   2    |       |   1   |        |       |
     * | User 15 |       |   2    |       |   1   |        |       |       |        |       |
     * | User 16 |   1   |        |       |       |   2    |       |   1   |        |       |
     * | User 17 |       |        |       |       |        |       |       |        |       |
     * | User 18 |       |   2    |       |   1   |        |       |   1   |        |       |
     * | User 19 |       |        |       |       |        |       |       |        |       |
     * | User 20 |   1   |        |       |       |   2    |       |   1   |        |       |
     * | User 21 |       |   2    |       |   1   |        |       |       |        |       |
     * | User 22 |       |        |   3   |       |   2    |       |   1   |        |       |
     * | User 23 |       |        |       |       |        |       |       |        |       |
     * | User 24 |   1   |        |       |   1   |        |       |   1   |        |       |
     * + --------+--------------------------------------------------------------------------+
     */
    public function setUp() {
        global $DB;

        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $now = time();
        $this->users = array();

        // Set totara_cohort generator.
        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Set totara_hierarchy generator.
        $this->hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create position framework.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_POSITION;
        $name .= ' ' . totara_generator_util::get_next_record_number('pos_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        $this->posfw = $this->hierarchy_generator->create_framework('position', $data);

        // Create position types.
        $this->ptype1 = $this->hierarchy_generator->create_pos_type(array('idnumber' => 'ptype1', 'fullname' => 'ptype1'));
        $this->ptype2 = $this->hierarchy_generator->create_pos_type(array('idnumber' => 'ptype2', 'fullname' => 'ptype2'));

        // Create organisation framework.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_ORGANISATION;
        $name .= ' ' . totara_generator_util::get_next_record_number('org_framework', 'fullname', $name);
        $data = array('fullname' => $name);
        $this->orgfw = $this->hierarchy_generator->create_framework('organisation', $data);

        // Create position types.
        $this->otype1 = $this->hierarchy_generator->create_org_type(array('idnumber' => 'otype1', 'fullname' => 'otype1'));
        $this->otype2 = $this->hierarchy_generator->create_org_type(array('idnumber' => 'otype2', 'fullname' => 'otype2'));

        // Create positions and organisation hierarchies.
        $this->assertEquals(0, $DB->count_records('pos'));
        $this->pos1 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position',
            array('idnumber' => 'pos1', 'fullname' => 'posname1', 'typeid' => $this->ptype1));
        $this->pos2 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position',
            array('idnumber' => 'pos2', 'fullname' => 'posname2', 'typeid' => $this->ptype2));
        $this->pos3 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position',
            array('idnumber' => 'pos3', 'fullname' => 'posname3', 'typeid' => 0));
        $this->assertEquals(3, $DB->count_records('pos'));

        // Create organisations and organisation hierarchies.
        $this->assertEquals(0, $DB->count_records('org'));
        $this->org1 = $this->hierarchy_generator->create_hierarchy($this->orgfw->id, 'organisation',
            array('idnumber' => 'org1', 'fullname' => 'orgname1', 'typeid' => $this->otype1));
        $this->org2 = $this->hierarchy_generator->create_hierarchy($this->orgfw->id, 'organisation',
            array('idnumber' => 'org2', 'fullname' => 'orgname2', 'typeid' => $this->otype2));
        $this->org3 = $this->hierarchy_generator->create_hierarchy($this->orgfw->id, 'organisation',
            array('idnumber' => 'org3', 'fullname' => 'orgname3', 'typeid' => 0));
        $this->assertEquals(3, $DB->count_records('org'));

        // Create some managers.
        $this->man1 = $this->getDataGenerator()->create_user();
        $man1ja = \totara_job\job_assignment::create_default($this->man1->id);

        $this->man2 = $this->getDataGenerator()->create_user();
        $man2ja = \totara_job\job_assignment::create_default($this->man2->id);
        $man2ja2 = \totara_job\job_assignment::create_default($this->man2->id);

        $this->man3 = $this->getDataGenerator()->create_user();
        $man3ja = \totara_job\job_assignment::create_default($this->man3->id);

        // Create some test users and assign them primary job assignments.
        $users = array();
        for ($i = 1; $i <= self::TEST_POSITION_COUNT_MEMBERS; $i++) {
            $this->{'user'.$i} = $this->getDataGenerator()->create_user();
            if ($i % 4 === 0) {
                // Users 24,20,16,12,8,4 total(6).
                $man = $man1ja->id;
                $orgid = $this->org1->id;
                $posid = $this->pos1->id;
                $jobassignstartdate = $now + (20 * DAYSECS);
                $jobassignenddate = $now + (70 * DAYSECS);
                $jobname = 'jobassignment-1.1';
                $jobidnumber = 'job-1.1';
            } else if ($i % 3 === 0) {
                // Users 21,18,15,9,6,3 total(6).
                $man = $man2ja->id;
                $orgid = $this->org2->id;
                $posid = $this->pos2->id;
                $jobassignstartdate = $now - (15 * DAYSECS);
                $jobassignenddate = $now + (15 * DAYSECS);
                $jobname = 'jobassignment-1.2';
                $jobidnumber = 'job-1.2';
            } else if ($i % 2 === 0) {
                // Users 22,14,10,2 total(4).
                $man = $man3ja->id;
                $orgid = $this->org3->id;
                $posid = $this->pos3->id;
                $jobassignstartdate = $now - (70 * DAYSECS);
                $jobassignenddate = $now - (20 * DAYSECS);
                $jobname = 'jobassignment-1.3';
                $jobidnumber = 'job-1.3';
            } else {
                // Users 23,19,17,13,11,7,5,1 total(8).
                $man = null;
                $orgid = null;
                $org = null;
                $posid = null;
                $pos = null;
                $jobassignstartdate = null;
                $jobassignenddate = null;
                $jobname = 'emptyjob-1';
                $jobidnumber = 'emptyjob-1';
            }

            \totara_job\job_assignment::create(array(
                'userid' => $this->{'user'.$i}->id,
                'fullname' => $jobname,
                'managerjaid' => $man,
                'positionid' => $posid,
                'organisationid' => $orgid,
                'startdate' => $jobassignstartdate,
                'enddate' => $jobassignenddate,
                'idnumber' => $jobidnumber
            ));
        }

        // Now create some secondary positions.
        for ($i = 1; $i <= self::TEST_POSITION_COUNT_MEMBERS; $i++) {
            if ($i % 3 === 0) {
                // Users 24,21,18,15,12,9,6,3 total(8).
                $man = $man1ja->id;
                $orgid = $this->org1->id;
                $posid = $this->pos1->id;
                $jobassignstartdate = $now + (20 * DAYSECS);
                $jobassignenddate = $now + (70 * DAYSECS);
                $jobname = 'jobassignment-2.1';
                $jobidnumber = 'job-2.1';
            } else if ($i % 2 === 0) {
                // Users 22,20,16,14,10,8,4,2 total(8).
                $man = $man2ja2->id;
                $orgid = $this->org2->id;
                $posid = $this->pos2->id;
                $jobassignstartdate = $now - (15 * DAYSECS);
                $jobassignenddate = $now + (15 * DAYSECS);
                $jobname = 'jobassignment-2.2';
                $jobidnumber = 'job-2.2';
            } else {
                // Users 23,19,17,13,11,7,5,1 total(8).
                $man = null;
                $orgid = null;
                $posid = null;
                $jobassignstartdate = null;
                $jobassignenddate = null;
                $jobname = 'emptyjob-2';
                $jobidnumber = 'emptyjob-2';
            }

            \totara_job\job_assignment::create(array(
                'userid' => $this->{'user'.$i}->id,
                'fullname' => $jobname,
                'managerjaid' => $man,
                'positionid' => $posid,
                'organisationid' => $orgid,
                'startdate' => $jobassignstartdate,
                'enddate' => $jobassignenddate,
                'idnumber' => $jobidnumber
            ));
        }

        // Now create some tertiary positions.
        for ($i = 1; $i <= self::TEST_POSITION_COUNT_MEMBERS; $i++) {
            if ($i % 2 === 0) {
                // Users 24,22,20,18,16,14,12,10,8,6,4,2 total(12).
                $man = $man1ja->id;
                $orgid = $this->org1->id;
                $posid = $this->pos1->id;
                $jobassignstartdate = $now + (20 * DAYSECS);
                $jobassignenddate = $now + (70 * DAYSECS);
                $jobname = 'jobassignment-3.1';
                $jobidnumber = 'job-3.1';
            } else {
                // Users 23,21,19,17,15,13,11,9,7,5,3,1 total(12).
                $man = null;
                $orgid = null;
                $posid = null;
                $jobassignstartdate = null;
                $jobassignenddate = null;
                $jobname = 'emptyjob-3';
                $jobidnumber = 'emptyjob-3';
            }

            \totara_job\job_assignment::create(array(
                'userid' => $this->{'user'.$i}->id,
                'fullname' => $jobname,
                'managerjaid' => $man,
                'positionid' => $posid,
                'organisationid' => $orgid,
                'startdate' => $jobassignstartdate,
                'enddate' => $jobassignenddate,
                'idnumber' => $jobidnumber
            ));
        }

        // Create one last user to verify non-existant job assignments doesn't break anything.
        $this->controluser = $this->getDataGenerator()->create_user();

        // Check the users were created. It should match $this->countmembers + 2 users(admin + guest) + 3 Managers + 1 control.
        $this->assertEquals(self::TEST_POSITION_COUNT_MEMBERS + 6, $DB->count_records('user'));

        // Check positions were assigned correctly.
        $this->assertEquals(6, $DB->count_records('job_assignment', array('positionid' => $this->pos1->id, 'sortorder' => 1)));
        $this->assertEquals(6, $DB->count_records('job_assignment', array('positionid' => $this->pos2->id, 'sortorder' => 1)));
        $this->assertEquals(4, $DB->count_records('job_assignment', array('positionid' => $this->pos3->id, 'sortorder' => 1)));

        $this->assertEquals(8, $DB->count_records('job_assignment', array('positionid' => $this->pos1->id, 'sortorder' => 2)));
        $this->assertEquals(8, $DB->count_records('job_assignment', array('positionid' => $this->pos2->id, 'sortorder' => 2)));

        $this->assertEquals(12, $DB->count_records('job_assignment', array('positionid' => $this->pos1->id, 'sortorder' => 3)));

        // Creating dynamic cohort.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->cohort->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Create ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);

        // Sneak the positionassignmentdate into the future/past.
        $DB->execute('UPDATE {job_assignment} SET positionassignmentdate = startdate WHERE positionid > 0');
    }

    /**
     * Test the all job titles rule.
     */
    public function test_all_job_titles_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'jobtitles',
            array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('jobassignment-'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'jobtitles',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('jobassignment-1.3'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'jobtitles',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('jobassignment-2.2'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'jobtitles',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('jobassignment-3.1'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));
    }

    /**
     * Test the all job start dates rule.
     */
    public function test_all_job_start_dates_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $now = time();
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION, 'date' => 16), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION, 'date' => 16), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));

        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE, 'date' => $now), array());
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_AFTER_FIXED_DATE, 'date' => $now + (16 * DAYSECS)), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(14, count($members));
    }

    /**
     * Test the all job end dates rule.
     */
    public function test_all_job_end_dates_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $now = time();
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION, 'date' => 16), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION, 'date' => 16), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));

        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE, 'date' => $now + (16 * DAYSECS)), array());
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'startdates',
            array('operator' => COHORT_RULE_DATE_OP_AFTER_FIXED_DATE, 'date' => $now), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(14, count($members));
    }

    /**
     * Test the all positions rule.
     */
    public function test_all_positions_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'positions',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->pos1->id));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'positions',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->pos2->id));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'positions',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->pos3->id));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'positions',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->pos1->id));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'positions',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->pos2->id));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));

        $this->assertEquals(14, count($members));

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'positions',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->pos1->id));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));
    }

    /**
     * Test the all position names rule.
     */
    public function test_all_position_names_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posnames',
            array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('posname'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posnames',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('posname1'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posnames',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('posname2'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posnames',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('posname3'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));
    }

    /**
     * Test the all position idnumbers rule.
     */
    public function test_all_position_idnumbers_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posidnumbers',
            array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('pos'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posidnumbers',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('pos1'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posidnumbers',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('pos2'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posidnumbers',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('pos3'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));
    }

    /**
     * Test the all position assignment dates rule.
     */
    public function test_all_position_assignment_dates_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $now = time();
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posassigndates',
            array('operator' => COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION, 'date' => 16), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posassigndates',
            array('operator' => COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION, 'date' => 16), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));

        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posassigndates',
            array('operator' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE, 'date' => $now), array());
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posassigndates',
            array('operator' => COHORT_RULE_DATE_OP_AFTER_FIXED_DATE, 'date' => $now + (16 * DAYSECS)), array());
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(14, count($members));
    }

    /**
     * Test the all organisations rule.
     */
    public function test_all_organisations_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'organisations',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->org1->id));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'organisations',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->org2->id));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'organisations',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->org3->id));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'organisations',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->org1->id));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'organisations',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->org2->id));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));

        $this->assertEquals(14, count($members));

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'organisations',
            array('includechildren' => 0, 'equal' => COHORT_RULES_OP_IN_ISEQUALTO), array($this->org1->id));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));
    }

    /**
     * Test the all organisation names rule.
     */
    public function test_all_organisation_names_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgnames',
            array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('orgname'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgnames',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('orgname1'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgnames',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('orgname2'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgnames',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('orgname3'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));
    }

    /**
     * Test the all organisation idnumbers rule.
     */
    public function test_all_organisation_idnumbers_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgidnumbers',
            array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('org'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgidnumbers',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('org1'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgidnumbers',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('org2'));
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'orgidnumbers',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('org3'));
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));
    }

    /**
     * Test the all managers rule.
     */
    public function test_all_managers_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users who have all three positions assigned across all of their job assignments.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'managers',
            array('isdirectreport' => 1), array($this->man1->id), 'managerid');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'managers',
            array('isdirectreport' => 0), array($this->man2->id), 'managerid');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'managers',
            array('isdirectreport' => 1), array($this->man3->id), 'managerid');
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(4, count($members));

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'managers',
            array('isdirectreport' => 1), array($this->man1->id), 'managerid');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'managers',
            array('isdirectreport' => 1), array($this->man2->id), 'managerid');
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));

        $this->assertEquals(14, count($members));

        // Add a rule that matches users who have the first two positions assigned across all of their job assignments.
        $this->cohort_generator->cohort_clean_ruleset($this->ruleset);
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'managers',
            array('isdirectreport' => 1), array($this->man1->id), 'managerid');
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(16, count($members));
    }
}
