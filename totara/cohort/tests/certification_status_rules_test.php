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
class totara_cohort_certification_status_rules_testcase extends totara_cohort_certification_testcase {

    public $program_generator          = null;
    public $cohort_generator           = null;
    public $user_groups                = [];
    public $adminuser                  = null;
    public $courses                    = [];
    public $programs                   = [];
    public $certifications             = [];
    public $cohort                     = null;
    public $ruleset                    = 0;

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
     * Create an array of user groups in $this->user_groups
     *
     * The following groups are created
     *
     * - Currently certified | Assigned
     * - Currently expired   | Assigned
     * - Never certified	 | Assigned
     *
     * - Currently certified | Unassigned
     * - Currently expired   | Unassigned
     * - Never certified	 | Unassigned
     *
     */
    public function create_users_and_assignments() {

        $this->user_groups = [
            'certified_assigned_cert1'            => [],
            'expired_assigned_cert1'              => [],
            'never_certified_assigned_cert1'      => [],
            'certified_unassigned_cert1'          => [],
            'expired_unassigned_cert1'            => [],
            'never_certified_unassigned_cert1'    => []
        ];

        $cert = 1;

        //
        // Currently certified | Assigned
        //
        $group = "certified_assigned_cert1";
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id);

        //
        // Currently expired | Assigned
        //
        $group = "expired_assigned_cert1";
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id);
        $this->expire_user_certitifications($this->user_groups[$group]);

        //
        // Never certified | Assigned
        //
        $group = "never_certified_assigned_cert1";
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);

        //
        // Currently certified | Unassigned
        //
        $group = "certified_unassigned_cert1";
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id);
        $this->unassign_users($this->user_groups[$group], [$this->certifications[$cert]]);

        //
        // Currently expired | Unassigned
        //
        $group = "expired_unassigned_cert1";
        $this->add_users_to_group($group);
        $this->assign_users($this->user_groups[$group], [$this->certifications[$cert]]);
        $this->certify_users($this->user_groups[$group], $this->courses[$cert]->id);
        $this->expire_user_certitifications($this->user_groups[$group]);
        $this->unassign_users($this->user_groups[$group], [$this->certifications[$cert]]);

        //
        // Never certified | Unassigned
        //
        $group = "never_certified_unassigned_cert1";
        $this->add_users_to_group($group);
        $this->user_groups[$group][$this->adminuser->id] = $this->adminuser;
        $this->assertEquals(self::TEST_GROUP_USER_COUNT + 1, count($this->user_groups[$group]));
    }

    /**
     * Data provider.
     */
    public function data_certification_status() {

        // Rules setup and test result data for,
        //
        // Currently certified                                      | Assigned              | Cert1
        // Currently certified, Currently expired                   | Assigned              | Cert1
        // Currently certified, Never certified                     | Assigned              | Cert1
        // Currently certified, Currently expired, Never certified  | Assigned              | Cert1
        // Currently expired                                        | Assigned              | Cert1
        // Currently expired, Never certified                       | Assigned              | Cert1
        // Never certified                                          | Assigned              | Cert1
        //
        // Currently certified                                      | Unassigned            | Cert1
        // Currently certified, Currently expired                   | Unassigned            | Cert1
        // Currently certified, Never certified                     | Unassigned            | Cert1
        // Currently certified, Currently expired, Never certified  | Unassigned            | Cert1
        // Currently expired                                        | Unassigned            | Cert1
        // Currently expired, Never certified                       | Unassigned            | Cert1
        // Never certified                                          | Unassigned            | Cert1
        //
        // Currently certified                                      | Assigned, Unassigned  | Cert1
        // Currently certified, Currently expired                   | Assigned, Unassigned  | Cert1
        // Currently certified, Never certified                     | Assigned, Unassigned  | Cert1
        // Currently certified, Currently expired, Never certified  | Assigned, Unassigned  | Cert1
        // Currently expired                                        | Assigned, Unassigned  | Cert1
        // Currently expired, Never certified                       | Assigned, Unassigned  | Cert1
        // Never certified                                          | Assigned, Unassigned  | Cert1
        //
        //
        // Currently certified                                      | Assigned              | Cert1&2
        // Currently certified, Currently expired                   | Assigned              | Cert1&2
        // Currently certified, Never certified                     | Assigned              | Cert1&2
        // Currently certified, Currently expired, Never certified  | Assigned              | Cert1&2
        // Currently expired                                        | Assigned              | Cert1&2
        // Currently expired, Never certified                       | Assigned              | Cert1&2
        // Never certified                                          | Assigned              | Cert1&2
        //
        // Currently certified                                      | Unassigned            | Cert1&2
        // Currently certified, Currently expired                   | Unassigned            | Cert1&2
        // Currently certified, Never certified                     | Unassigned            | Cert1&2
        // Currently certified, Currently expired, Never certified  | Unassigned            | Cert1&2
        // Currently expired                                        | Unassigned            | Cert1&2
        // Currently expired, Never certified                       | Unassigned            | Cert1&2
        // Never certified                                          | Unassigned            | Cert1&2
        //
        // Currently certified                                      | Assigned, Unassigned  | Cert1&2
        // Currently certified, Currently expired                   | Assigned, Unassigned  | Cert1&2
        // Currently certified, Never certified                     | Assigned, Unassigned  | Cert1&2
        // Currently certified, Currently expired, Never certified  | Assigned, Unassigned  | Cert1&2
        // Currently expired                                        | Assigned, Unassigned  | Cert1&2
        // Currently expired, Never certified                       | Assigned, Unassigned  | Cert1&2
        // Never certified                                          | Assigned, Unassigned  | Cert1&2
        //
        //
        // Currently certified                                      | Assigned              | Cert3
        // Currently certified, Currently expired                   | Assigned              | Cert3
        // Currently certified, Never certified                     | Assigned              | Cert3
        // Currently certified, Currently expired, Never certified  | Assigned              | Cert3
        // Currently expired                                        | Assigned              | Cert3
        // Currently expired, Never certified                       | Assigned              | Cert3
        // Never certified                                          | Assigned              | Cert3
        //
        // Currently certified                                      | Unassigned            | Cert3
        // Currently certified, Currently expired                   | Unassigned            | Cert3
        // Currently certified, Never certified                     | Unassigned            | Cert3
        // Currently certified, Currently expired, Never certified  | Unassigned            | Cert3
        // Currently expired                                        | Unassigned            | Cert3
        // Currently expired, Never certified                       | Unassigned            | Cert3
        // Never certified                                          | Unassigned            | Cert3
        //
        // Currently certified                                      | Assigned, Unassigned  | Cert3
        // Currently certified, Currently expired                   | Assigned, Unassigned  | Cert3
        // Currently certified, Never certified                     | Assigned, Unassigned  | Cert3
        // Currently certified, Currently expired, Never certified  | Assigned, Unassigned  | Cert3
        // Currently expired                                        | Assigned, Unassigned  | Cert3
        // Currently expired, Never certified                       | Assigned, Unassigned  | Cert3
        // Never certified                                          | Assigned, Unassigned  | Cert3

        // Create our data array to test the above.
        $data = [
            // Data structure.
            // * Test name
            // * Certificates
            // * Params
            //   * Status
            //   * Assignment status
            // * User group(s) that should be included as members

            //
            // Certification 1.
            //

            // Currently certified | Assigned | cert1
            ['Currently certified | Assigned | cert1', [1], ['status' => '10', 'assignmentstatus' => '10'], ['certified_assigned_cert1']],

            // Currently certified, Currently expired | Assigned | cert1
            ['Currently certified, Currently expired | Assigned | cert1', [1], ['status' => '10,20', 'assignmentstatus' => '10'], ['certified_assigned_cert1', 'expired_assigned_cert1']],

            // Currently certified, Never certified | Assigned | cert1
            ['Currently certified, Never certified | Assigned | cert1', [1], ['status' => '10,30', 'assignmentstatus' => '10'], ['certified_assigned_cert1', 'never_certified_assigned_cert1']],

            // Currently certified, Currently expired, Never certified  | Assigned | cert1
            ['Currently certified, Currently expired, Never certified | Assigned | cert1', [1], ['status' => '10,20,30', 'assignmentstatus' => '10'], ['certified_assigned_cert1', 'expired_assigned_cert1', 'never_certified_assigned_cert1']],

            // Currently expired | Assigned | cert1
            ['Currently expired | Assigned | cert1', [1], ['status' => '20', 'assignmentstatus' => '10'], ['expired_assigned_cert1']],

            // Currently expired, Never certified | Assigned | cert1
            ['Currently expired, Never certified | Assigned | cert1', [1], ['status' => '20,30', 'assignmentstatus' => '10'], ['expired_assigned_cert1', 'never_certified_assigned_cert1']],

            // Never certified | Assigned | cert1
            ['Never certified | Assigned | cert1', [1], ['status' => '30', 'assignmentstatus' => '10'], ['never_certified_assigned_cert1']],

            // Currently certified | Unassigned | cert1
            ['Currently certified | Unassigned | cert1', [1], ['status' => '10', 'assignmentstatus' => '20'], ['certified_unassigned_cert1']],

            // Currently certified, Currently expired | Unassigned | cert1
            ['Currently certified, Currently expired | Unassigned | cert1', [1], ['status' => '10,20', 'assignmentstatus' => '20'], ['certified_unassigned_cert1', 'expired_unassigned_cert1']],

            // Currently certified, Never certified | Unassigned | cert1
            ['Currently certified, Never certified | Unassigned | cert1', [1], ['status' => '10,30', 'assignmentstatus' => '20'], ['certified_unassigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently certified, Currently expired, Never certified | Unassigned | cert1
            ['Currently certified, Currently expired, Never certified | Unassigned | cert1', [1], ['status' => '10,20,30', 'assignmentstatus' => '20'], ['certified_unassigned_cert1', 'expired_unassigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently expired | Unassigned | cert1
            ['Currently expired | Unassigned | cert1', [1], ['status' => '20', 'assignmentstatus' => '20'], ['expired_unassigned_cert1']],

            // Currently expired, Never certified | Unassigned | cert1
            ['Currently expired, Never certified | Unassigned | cert1', [1], ['status' => '20,30', 'assignmentstatus' => '20'], ['expired_unassigned_cert1', 'never_certified_unassigned_cert1']],

            // Never certified | Unassigned | cert1
            ['Never certified | Unassigned | cert1', [1], ['status' => '30', 'assignmentstatus' => '20'], ['never_certified_unassigned_cert1']],

            // Currently certified  | Assigned, Unassigned | cert1
            ['Currently certified | Assigned, Unassigned | cert1', [1], ['status' => '10', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1']],

            // Currently certified, Currently expired | Assigned, Unassigned | cert1
            ['Currently certified, Currently expired | Assigned, Unassigned | cert1', [1], ['status' => '10,20', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'expired_assigned_cert1', 'expired_unassigned_cert1']],

            // Currently certified, Never certified | Assigned, Unassigned | cert1
            ['Currently certified, Never certified | Assigned, Unassigned | cert1', [1], ['status' => '10,30', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently certified, Currently expired, Never certified | Assigned, Unassigned | cert1
            ['Currently certified, Currently expired, Never certified | Assigned, Unassigned | cert1', [1], ['status' => '10,20,30', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'expired_assigned_cert1', 'expired_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently expired | Assigned, Unassigned | cert1
            ['Currently expired | Assigned, Unassigned | cert1', [1], ['status' => '20', 'assignmentstatus' => '10,20'], ['expired_assigned_cert1', 'expired_unassigned_cert1']],

            // Currently expired, Never certified | Assigned, Unassigned | cert1
            ['Currently expired, Never certified | Assigned, Unassigned | cert1', [1], ['status' => '20,30', 'assignmentstatus' => '10,20'], ['never_certified_assigned_cert1', 'never_certified_unassigned_cert1', 'expired_assigned_cert1', 'expired_unassigned_cert1']],

            // Never certified | Assigned, Unassigned | cert1
            ['Never certified | Assigned, Unassigned | cert1', [1], ['status' => '30', 'assignmentstatus' => '10,20'], ['never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            //
            // Certification 1 & 3.
            //

            // Currently certified | Assigned | cert1&2
            ['Currently certified | Assigned | cert1&2', [1,2], ['status' => '10', 'assignmentstatus' => '10'], []],

            // Currently certified, Currently expired | Assigned | cert1&2
            ['Currently certified, Currently expired | Assigned | cert1&2', [1,2], ['status' => '10,20', 'assignmentstatus' => '10'], []],

            // Currently certified, Never certified | Assigned | cert1&2
            ['Currently certified, Never certified | Assigned | cert1&2', [1,2], ['status' => '10,30', 'assignmentstatus' => '10'], []],

            // Currently certified, Currently expired, Never certified  | Assigned | cert1&2
            ['Currently certified, Currently expired, Never certified | Assigned | cert1&2', [1,2], ['status' => '10,20,30', 'assignmentstatus' => '10'], []],

            // Currently expired | Assigned | cert1&2
            ['Currently expired | Assigned | cert1&2', [1,2], ['status' => '20', 'assignmentstatus' => '10'], []],

            // Currently expired, Never certified | Assigned | cert1&2
            ['Currently expired, Never certified | Assigned | cert1&2', [1,2], ['status' => '20,30', 'assignmentstatus' => '10'], []],

            // Never certified | Assigned | cert1&2
            ['Never certified | Assigned | cert1&2', [1,2], ['status' => '30', 'assignmentstatus' => '10'], []],

            // Currently certified | Unassigned | cert1&2
            ['Currently certified | Unassigned | cert1&2', [1,2], ['status' => '10', 'assignmentstatus' => '20'], []],

            // Currently certified, Currently expired | Unassigned | cert1&2
            ['Currently certified, Currently expired | Unassigned | cert1&2', [1,2], ['status' => '10,20', 'assignmentstatus' => '20'], []],

            // Currently certified, Never certified | Unassigned | cert1&2
            ['Currently certified, Never certified | Unassigned | cert1&2', [1,2], ['status' => '10,30', 'assignmentstatus' => '20'], ['never_certified_unassigned_cert1']],

            // Currently certified, Currently expired, Never certified | Unassigned | cert1&2
            ['Currently certified, Currently expired, Never certified | Unassigned | cert1&2', [1,2], ['status' => '10,20,30', 'assignmentstatus' => '20'], ['never_certified_unassigned_cert1', 'certified_unassigned_cert1', 'expired_unassigned_cert1']],

            // Currently expired | Unassigned | cert1&2
            ['Currently expired | Unassigned | cert1&2', [1,2], ['status' => '20', 'assignmentstatus' => '20'], []],

            // Currently expired, Never certified | Unassigned | cert1&2
            ['Currently expired, Never certified | Unassigned | cert1&2', [1,2], ['status' => '20,30', 'assignmentstatus' => '20'], ['never_certified_unassigned_cert1']],

            // Never certified | Unassigned | cert1&2
            ['Never certified | Unassigned | cert1&2', [1,2], ['status' => '30', 'assignmentstatus' => '20'], ['never_certified_unassigned_cert1']],

            // Currently certified  | Assigned, Unassigned | cert1&2
            ['Currently certified | Assigned, Unassigned | cert1&2', [1,2], ['status' => '10', 'assignmentstatus' => '10,20'], []],

            // Currently certified, Currently expired | Assigned, Unassigned | cert1&2
            ['Currently certified, Currently expired | Assigned, Unassigned', [1,2], ['status' => '10,20', 'assignmentstatus' => '10,20'], []],

            // Currently certified, Never certified | Assigned, Unassigned
            ['Currently certified, Never certified | Assigned, Unassigned | cert1&2', [1,2], ['status' => '10,30', 'assignmentstatus' => '10,20'], ['never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently certified, Currently expired, Never certified | Assigned, Unassigned | cert1&2
            ['Currently certified, Currently expired, Never certified | Assigned, Unassigned | cert1&2', [1,2], ['status' => '10,20,30', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently expired | Assigned, Unassigned | cert1&2
            ['Currently expired | Assigned, Unassigned | cert1&2', [1,2], ['status' => '20', 'assignmentstatus' => '10,20'], []],

            // Currently expired, Never certified | Assigned, Unassigned | cert1&2
            ['Currently expired, Never certified | Assigned, Unassigned | cert1&2', [1,2], ['status' => '20,30', 'assignmentstatus' => '10,20'], ['never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Never certified | Assigned, Unassigned | cert1&2
            ['Never certified | Assigned, Unassigned | cert1&2', [1,2], ['status' => '30', 'assignmentstatus' => '10,20'], ['never_certified_unassigned_cert1', 'never_certified_assigned_cert1']],

            //
            // Certification 3.
            //

            // Currently certified | Assigned | cert3
            ['Currently certified | Assigned | cert3', [3], ['status' => '10', 'assignmentstatus' => '10'], []],

            // Currently certified, Currently expired | Assigned | cert3
            ['Currently certified, Currently expired | Assigned | cert3', [3], ['status' => '10,20', 'assignmentstatus' => '10'], []],

            // Currently certified, Never certified | Assigned | cert3
            ['Currently certified, Never certified | Assigned | cert3', [3], ['status' => '10,30', 'assignmentstatus' => '10'], []],

            // Currently certified, Currently expired, Never certified  | Assigned | cert3
            ['Currently certified, Currently expired, Never certified | Assigned | cert3', [3], ['status' => '10,20,30', 'assignmentstatus' => '10'], []],

            // Currently expired | Assigned | cert3
            ['Currently expired | Assigned | cert3', [3], ['status' => '20', 'assignmentstatus' => '10'], []],

            // Currently expired, Never certified | Assigned | cert3
            ['Currently expired, Never certified | Assigned | cert3', [3], ['status' => '20,30', 'assignmentstatus' => '10'], []],

            // Never certified | Assigned | cert3
            ['Never certified | Assigned | cert3', [3], ['status' => '30', 'assignmentstatus' => '10'], []],

            // Currently certified | Unassigned | cert3
            ['Currently certified | Unassigned | cert3', [3], ['status' => '10', 'assignmentstatus' => '20'], []],

            // Currently certified, Currently expired | Unassigned | cert3
            ['Currently certified, Currently expired | Unassigned | cert3', [3], ['status' => '10,20', 'assignmentstatus' => '20'], []],

            // Currently certified, Never certified | Unassigned | cert3
            ['Currently certified, Never certified | Unassigned | cert3', [3], ['status' => '10,30', 'assignmentstatus' => '20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently certified, Currently expired, Never certified | Unassigned | cert3
            ['Currently certified, Currently expired, Never certified | Unassigned | cert3', [3], ['status' => '10,20,30', 'assignmentstatus' => '20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently expired | Unassigned | cert3
            ['Currently expired | Unassigned | cert3', [3], ['status' => '20', 'assignmentstatus' => '20'], []],

            // Currently expired, Never certified | Unassigned | cert3
            ['Currently expired, Never certified | Unassigned | cert3', [3], ['status' => '20,30', 'assignmentstatus' => '20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Never certified | Unassigned | cert3
            ['Never certified | Unassigned | cert3', [3], ['status' => '30', 'assignmentstatus' => '20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently certified  | Assigned, Unassigned | cert3
            ['Currently certified | Assigned, Unassigned | cert3', [3], ['status' => '10', 'assignmentstatus' => '10,20'], []],

            // Currently certified, Currently expired | Assigned, Unassigned | cert3
            ['Currently certified, Currently expired | Assigned, Unassigned | cert3', [3], ['status' => '10,20', 'assignmentstatus' => '10,20'], []],

            // Currently certified, Never certified | Assigned, Unassigned | cert3
            ['Currently certified, Never certified | Assigned, Unassigned | cert3', [3], ['status' => '10,30', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently certified, Currently expired, Never certified | Assigned, Unassigned | cert3
            ['Currently certified, Currently expired, Never certified | Assigned, Unassigned | cert3', [3], ['status' => '10,20,30', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Currently expired | Assigned, Unassigned | cert3
            ['Currently expired | Assigned, Unassigned | cert3', [3], ['status' => '20', 'assignmentstatus' => '10,20'], []],

            // Currently expired, Never certified | Assigned, Unassigned | cert3
            ['Currently expired, Never certified | Assigned, Unassigned | cert3', [3], ['status' => '20,30', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

            // Never certified | Assigned, Unassigned | cert3
            ['Never certified | Assigned, Unassigned | cert3', [3], ['status' => '30', 'assignmentstatus' => '10,20'], ['certified_assigned_cert1', 'certified_unassigned_cert1', 'certified_assigned_cert1', 'certified_unassigned_cert1', 'never_certified_assigned_cert1', 'never_certified_unassigned_cert1']],

        ];

        return $data;
    }

    /**
     * @dataProvider data_certification_status
     */
    public function test_certification_status_rule($name, $certifications, $params, $usergroups) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

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
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'learning', 'certificationstatus', $params, $listofids, 'listofids');
        cohort_rules_approve_changes($this->cohort);

        // Check we have the correct members.
        $members = $DB->get_records('cohort_members', ['cohortid' => $this->cohort->id], '', 'userid');
        $this->assertEquals(count($users), count($members), 'Failed for ' . $name);
        foreach ($users as $user) {
            $this->assertTrue(array_key_exists($user->id, $members), 'Failed for ' . $name);
        }
    }

}
