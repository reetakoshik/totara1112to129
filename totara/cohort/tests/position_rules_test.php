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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

/**
 * Test position rules.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_cohort_position_rules_testcase
 *
 */
class totara_cohort_position_rules_testcase extends advanced_testcase {

    private $pos1 = null;
    private $pos2 = null;
    private $pos3 = null;
    private $pos4 = null;
    private $pos5 = null;
    private $posfw = null;
    private $cohort = null;
    private $ruleset = 0;
    private $userspos1 = array();
    private $userspos2 = array();
    private $userspos3 = array();
    private $userspos4 = array();
    /** @var totara_cohort_generator $cohort_generator */
    private $cohort_generator = null;
    /** @var totara_hierarchy_generator $hierarchy_generator */
    private $hierarchy_generator = null;
    private $dateformat = '';
    const TEST_POSITION_COUNT_MEMBERS = 23;

    protected function tearDown() {
        $this->pos1 = null;
        $this->pos2 = null;
        $this->pos3 = null;
        $this->pos4 = null;
        $this->pos5 = null;
        $this->posfw = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->userspos1 = null;
        $this->userspos2 = null;
        $this->userspos3 = null;
        $this->userspos4 = null;
        $this->cohort_generator = null;
        $this->hierarchy_generator = null;
        $this->dateformat = null;
        parent::tearDown();
    }

    /**
     * Users per position:
     *
     * pos1 ----> user3, user6, user9, user12, user15, user18, user21.
     *
     * pos2 ----> user2, user4, user8, user10, user14, user16, user20, user22.
     *
     * pos3 ----> user1, user5, user7, user11, user13, user17, user19, user23.
     */
    public function setUp() {
        global $DB;

        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->users = array();
        $this->dateformat = 'd/m/Y';

        // Set totara_cohort generator.
        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Set totara_hierarchy generator.
        $this->hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create positions and position fw.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_POSITION;
        $name .= ' ' . totara_generator_util::get_next_record_number('pos_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        $this->posfw = $this->hierarchy_generator->create_framework('position', $data);

        // Create positions and organisation hierarchies.
        $this->assertEquals(0, $DB->count_records('pos'));
        $this->pos1 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position', array('idnumber' => 'pos1', 'fullname' => 'posname1'));
        $this->pos2 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position', array('idnumber' => 'pos2', 'fullname' => 'posname2'));
        $this->pos3 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position', array('idnumber' => 'pos3', 'fullname' => 'posname3'));
        $this->assertEquals(3, $DB->count_records('pos'));

        // Create some test users and assign them to a position.
        $this->assertEquals(2, $DB->count_records('user'));
        $now = time();
        for ($i = 1; $i <= self::TEST_POSITION_COUNT_MEMBERS; $i++) {
            $this->{'user'.$i} = $this->getDataGenerator()->create_user();
            if ($i%3 === 0) {
                $posid = $this->pos1->id; // 7 users.
                $pos = 'pos1';
                $jobassignstartdate = date($this->dateformat, $now);
                $jobassignenddate = date($this->dateformat, $now + (20 * DAYSECS));
            } else if ($i%2 === 0){
                $posid = $this->pos2->id; // 8 users.
                $pos = 'pos2';
                $jobassignstartdate = date($this->dateformat, $now - DAYSECS);
                $jobassignenddate = date($this->dateformat, $now + (50 * DAYSECS));
            } else {
                $posid = $this->pos3->id; // 8 users.
                $pos = 'pos3';
                $jobassignstartdate = date($this->dateformat, $now + (2 * DAYSECS));
                $jobassignenddate = date($this->dateformat, $now + (70 * DAYSECS));
            }
            $jobassignstartdate = totara_date_parse_from_format($this->dateformat, $jobassignstartdate);
            $jobassignenddate = totara_date_parse_from_format($this->dateformat, $jobassignenddate);
            $data = array('positionid' => $posid, 'startdate' => $jobassignstartdate, 'enddate' => $jobassignenddate);
            \totara_job\job_assignment::create_default($this->{'user'.$i}->id, $data);
            array_push($this->{'users'.$pos}, $this->{'user'.$i}->id);
        }

        $this->userspos1 = array_flip($this->userspos1);
        $this->userspos2 = array_flip($this->userspos2);
        $this->userspos3 = array_flip($this->userspos3);

        // Check the users were created. It should match $this->countmembers + 2 users(admin + guest).
        $this->assertEquals(self::TEST_POSITION_COUNT_MEMBERS + 2, $DB->count_records('user'));

        // Check positions were assigned correctly.
        $this->assertEquals(7, $DB->count_records('job_assignment', array('positionid' => $this->pos1->id)));
        $this->assertEquals(8, $DB->count_records('job_assignment', array('positionid' => $this->pos2->id)));
        $this->assertEquals(8, $DB->count_records('job_assignment', array('positionid' => $this->pos3->id)));

        // Creating dynamic cohort.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->cohort->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Create ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
    }

    /**
     * Test position name rule.
     */
    public function test_position_name_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users for the position posname1. It should match 7 users.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posnames', array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('posname1'));
        cohort_rules_approve_changes($this->cohort);
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(7, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos1));
    }

    /**
     * Test position idnumber rule.
     */
    public function test_position_idnumber_rule() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule that matches users for the position pos1. It should match 7 users.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'posidnumbers', array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('pos1'));
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(7, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for position date rules.
     */
    public function data_position_date_rules() {
        $params1 =  array('operator' => COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION, 'date' => 50);
        $params2 =  array('operator' => COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION, 'date' => 60);
        $params3 =  array('operator' => COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE, 'date' => 0);
        $params4 =  array('operator' => COHORT_RULE_DATE_OP_AFTER_FIXED_DATE, 'date' => 1);
        $params5 =  array('operator' => COHORT_RULE_DATE_OP_AFTER_FIXED_DATE, 'date' => 0);
        $data = array(
            array('enddates', $params1, 15, array('pos1', 'pos2')),
            array('enddates', $params2, 8, array('pos3')),
            array('startdates', $params3, 15, array('pos1', 'pos2')),
            array('startdates', $params4, 8, array('pos3')),
            array('posassigndates', $params5, 23, array('pos1', 'pos2', 'pos3')),
        );
        return $data;
    }

    /**
     * @dataProvider data_position_date_rules
     */
    public function test_position_date_rules($rulename, $params, $usercount, $sourcemembersmatched) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Process list of users that should match the data.
        $usersinposition = array();
        foreach ($sourcemembersmatched as $pos) {
            $usersinposition = $usersinposition + $this->{'users'.$pos};
        }

        if ($params['operator'] === COHORT_RULE_DATE_OP_AFTER_FIXED_DATE ||
            $params['operator'] === COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE) {
            $now = time();
            $time = $now + ($params['date'] * DAYSECS);
            $params['date'] = totara_date_parse_from_format($this->dateformat, date($this->dateformat, $time));
        }

        // Create a position rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', $rulename, $params, array());
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 15 (users in positions that ends within a period of 50 days).
        // 2. data2: 8  (users which position ends after 60 days from now).
        // 3. data3: 15 (users in positions that starts before today).
        // 4. data4: 8  (users which position starts tomorrow or after).
        // 5. data5: 23  (user was assigned their primary position before now). Note: tiemassigned is filled with time()
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals($usercount, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $usersinposition));
    }

    /**
     * Test position type rule (Positions > Manage types).
     * This rule matches users based on the type of position they have assigned.
     */
    public function test_position_type_rule() {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create type of position.
        $newtype = new stdClass();
        $newtype->shortname = 'type1';
        $newtype->fullname = 'type1';
        $newtype->idnumber = 'typeID1';
        $newtype->description = '';
        $newtype->timecreated = time();
        $newtype->usermodified = $USER->id;
        $newtype->timemodified = time();
        $postype1 = $DB->insert_record('pos_type', $newtype);

        // Check the record was created correctly.
        $this->assertInternalType('int', $postype1);

        // Assign the type position to pos1.
        $this->assertTrue($DB->set_field('pos', 'typeid', $postype1, array('id' => $this->pos1->id)));

        // Create a rule that matches users in the previous created type.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'postypes', array('equal' => COHORT_RULES_OP_IN_EQUAL), array($postype1));
        cohort_rules_approve_changes($this->cohort);

        // It should match 7 users (pos1).
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(7, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos1));
    }

    /**
     * Test jobassign fullname rule (Job assignment > Job assignment full name).
     * This rule matches users based on the job assignment fullname assigned to them.
     * @author Aldo Paradiso (aparadiso@multamedio.de)
     */
    public function test_jobassign_fullname_rule() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // First create four users.
        $my_users = array();
        for($i = 0; $i <= 3; $i ++) {
            $my_users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create and assign job assignment data.
        \totara_job\job_assignment::create_default($my_users[0]->id, array('fullname' => 'Campaign Manager Online Marketing'));
        \totara_job\job_assignment::create_default($my_users[1]->id, array('fullname' => 'Account Manager'));
        \totara_job\job_assignment::create_default($my_users[2]->id, array('fullname' => 'Inside Sales Representative'));
        \totara_job\job_assignment::create_default($my_users[3]->id, array('fullname' => 'Inside Sales Representative'));

        // Add a rule that matches users for the job assignment 'Inside Sales Representative'. It should match 2 users.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'jobtitles',
                array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('Inside Sales Representative'));

        // Where is the doc of the next method? I assume, it will check if the rule is correctly defined and executable.
        cohort_rules_approve_changes($this->cohort);

        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array ($this->cohort->id));
        $this->assertEquals(2, count($members));
    }


    /**
     * Data provider for the reports to rule.
     */
    public function data_reportsto() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_EQUAL),  array(1), 2),
            array(array('equal' => COHORT_RULES_OP_IN_EQUAL),  array(0), 23),
        );
        return $data;
    }
    /**
     * Evaluates if the user is a manager.
     * Has direct reports = is_manager.
     *
     * manager1
     *    |-------> user1, user3, user5.
     *
     * manager2
     *    |-------> user2, user4.
     *
     * @dataProvider data_reportsto
     */
    public function test_direct_reports_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create some manager accounts.
        $manager1 = $this->getDataGenerator()->create_user(array('username' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('username' => 'manager2'));

        // Assign managers to users.
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);
        \totara_job\job_assignment::get_first($this->user1->id)->update(array('managerjaid' => $manager1ja->id));
        \totara_job\job_assignment::get_first($this->user2->id)->update(array('managerjaid' => $manager2ja->id));
        \totara_job\job_assignment::get_first($this->user3->id)->update(array('managerjaid' => $manager1ja->id));
        \totara_job\job_assignment::get_first($this->user4->id)->update(array('managerjaid' => $manager2ja->id));
        \totara_job\job_assignment::get_first($this->user5->id)->update(array('managerjaid' => $manager1ja->id));

        // Exclude admin user from this cohort.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'username', array('equal' => COHORT_RULES_OP_IN_NOTEQUALTO), array('admin'));

        // Create a rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'hasdirectreports', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 2 (users that are assigned as managers (Has direct reports)).
        // 2. data2: 23 (users that no are managers (Do not have direct reports)).
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for managers hierarchy.
     */
    public function data_manager_hierarchy() {
        $data = array(
            array(array('isdirectreport' => 0),  array('manager1', 'manager2'), 7),
            array(array('isdirectreport' => 0),  array('manager2'), 4),
            array(array('isdirectreport' => 1),  array('manager3'), 1),
            array(array('isdirectreport' => 1),  array('manager1'), 3),
        );
        return $data;
    }

    /**
     * Test what users reports to a list of managers.
     *
     * Hierarchy of managers:
     *
     * manager1
     *    |-------> user1
     *    |-------> user3
     *    |-------> manager2
     *                 | --------> user2
     *                 | --------> user4
     *                 | --------> manager3
     *                                | --------> user5
     *
     * @dataProvider data_manager_hierarchy
     */
    public function test_manager_rule($params, $managerids, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create some manager accounts.
        $manager1 = $this->getDataGenerator()->create_user(array('username' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('username' => 'manager2'));
        $manager3 = $this->getDataGenerator()->create_user(array('username' => 'manager3'));

        // Hierarchy of managers.
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id, array('managerjaid' => $manager1ja->id));
        $manager3ja = \totara_job\job_assignment::create_default($manager3->id, array('managerjaid' => $manager2ja->id));

        // Assign managers to users.
        \totara_job\job_assignment::get_first($this->user1->id)->update(array('managerjaid' => $manager1ja->id));
        \totara_job\job_assignment::get_first($this->user2->id)->update(array('managerjaid' => $manager2ja->id));
        \totara_job\job_assignment::get_first($this->user3->id)->update(array('managerjaid' => $manager1ja->id));
        \totara_job\job_assignment::get_first($this->user4->id)->update(array('managerjaid' => $manager2ja->id));
        \totara_job\job_assignment::get_first($this->user5->id)->update(array('managerjaid' => $manager3ja->id));

        $users = [
            $manager1->username => $manager1,
            $manager2->username => $manager2,
            $manager3->username => $manager3,
        ];
        $directreports = [
            $manager1->username => [$manager2->id, $this->user1->id, $this->user3->id],
            $manager2->username => [$manager3->id, $this->user2->id, $this->user4->id],
            $manager3->username => [$this->user5->id],
        ];
        $indirectreports = [
            $manager1->username => [$manager2->id, $this->user1->id, $this->user3->id, $manager3->id, $this->user2->id, $this->user4->id, $this->user5->id],
            $manager2->username => [$manager3->id, $this->user2->id, $this->user4->id, $this->user5->id],
            $manager3->username => [$this->user5->id],
        ];

        // Processing managers.
        $listofmanagers = array();
        $membersofmanagers = array();
        foreach ($managerids as $manager) {
            $listofmanagers[] = $users[$manager]->id;
            if ($params['isdirectreport']) {
                $membersofmanagers = array_merge($membersofmanagers, $directreports[$manager]);
            } else {
                $membersofmanagers = array_merge($membersofmanagers, $indirectreports[$manager]);
            }
        }
        $membersofmanagers = array_unique($membersofmanagers);

        // Create a rule to test "Reports to" option.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'managers', $params, $listofmanagers, 'managerid');
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 7 users (5 users that were assigned to managers and manager2 and manager3).
        // 2. data2: 4 users (user2, user4 manager3 and user5 because his manager is manager3).
        // 3. data3: 1 user (user5).
        // 4. data4: 3 users (user1, user3 and manager2).
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals($usercount, count($members));
        $this->assertEquals($members, $membersofmanagers, '', 0.0, 10, true);
    }

    /**
     * Data provider for position rule.
     */
    public function data_position_hierarchy() {
        $data = array(
            array(array('equal' => 1, 'includechildren' => 1),  array('pos1'), 10, array('pos1', 'pos4')),
            array(array('equal' => 1, 'includechildren' => 0),  array('pos1'), 7, array('pos1')),
            array(array('equal' => 0, 'includechildren' => 1),  array('pos1', 'pos2'), 8, array('pos3')),
        );
        return $data;
    }

    /**
     * Test rule that matches users assigned to a list of positions.
     *
     * Hierarchy of positions with their users assigned:
     *
     * pos1
     *   |----> user3, user6, user9, user12, user15, user18, user21.
     *   |----> pos4
     *            |----> newuser1, newuser4, newuser5.
     *
     * pos2
     *   |----> user2, user4, user8, user10, user14, user16, user20, user22.
     *   |----> pos5
     *            |----> newuser2, newuser3.
     *
     *  @dataProvider data_position_hierarchy
     */
    public function test_position_rule($params, $positions, $usercount, $sourcemembersmatched) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Process positions.
        $listofvalues = array();
        foreach ($positions as $pos) {
            $listofvalues[] = $this->{$pos}->id;
        }

        // Create some positions to make a hierarchy.
        $data = array('idnumber' => 'pos4', 'parentid' => $this->pos1->id, 'fullname' => 'pos4');
        $this->pos4 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position', $data);
        $data = array('idnumber' => 'pos5', 'parentid' => $this->pos2->id, 'fullname' => 'pos5');
        $this->pos5 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position', $data);

        // Create some users and assign them to the new positions.
        $newuser1 = $this->getDataGenerator()->create_user(array('username' => 'newuser1'));
        $newuser2 = $this->getDataGenerator()->create_user(array('username' => 'newuser2'));
        $newuser3 = $this->getDataGenerator()->create_user(array('username' => 'newuser3'));
        $newuser4 = $this->getDataGenerator()->create_user(array('username' => 'newuser4'));
        $newuser5 = $this->getDataGenerator()->create_user(array('username' => 'newuser5'));

        // Assign positions.
        \totara_job\job_assignment::create_default($newuser1->id, array('positionid' => $this->pos4->id));
        \totara_job\job_assignment::create_default($newuser2->id, array('positionid' => $this->pos5->id));
        \totara_job\job_assignment::create_default($newuser3->id, array('positionid' => $this->pos5->id));
        \totara_job\job_assignment::create_default($newuser4->id, array('positionid' => $this->pos4->id));
        \totara_job\job_assignment::create_default($newuser5->id, array('positionid' => $this->pos4->id));
        $this->userspos4 = array_flip(array($newuser1->id, $newuser4->id, $newuser5->id));

        $this->assertEquals(3, $DB->count_records('job_assignment', array('positionid' => $this->pos4->id)));
        $this->assertEquals(2, $DB->count_records('job_assignment', array('positionid' => $this->pos5->id)));

        // Process list of users that should match the data.
        $membersincohort = array();
        foreach ($sourcemembersmatched as $pos) {
            $membersincohort = $membersincohort + $this->{'users'.$pos};
        }

        // Exclude admin user from this cohort.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'username', array('equal' => COHORT_RULES_OP_IN_NOTEQUALTO), array('admin'));

        // Create a rule for positions.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'positions', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);

        // It should match:
        // 1. data1: 10 users: 7 from pos1 and 3 from pos4 which is a children of pos1.
        // 2. data2: 7 users: users from pos1 without include its children.
        // 3. data3: 8 users: users from pos3.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals($usercount, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $membersincohort));
    }
}
