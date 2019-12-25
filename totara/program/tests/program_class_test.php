<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Class totara_program_program_class_testcase
 *
 * Tests the methods in the program class in program.class.php
 */
class totara_program_program_class_testcase extends reportcache_advanced_testcase {

    /** @var totara_reportbuilder_cache_generator $data_generator */
    private $data_generator;

    /** @var totara_program_generator $program_generator */
    private $program_generator;

    /** @var totara_hierarchy_generator $hierarchy_generator */
    private $hierarchy_generator;

    /** @var totara_cohort_generator $cohort_generator */
    private $cohort_generator;

    /** @var totara_plan_generator $plan_generator */
    private $plan_generator;

    /** @var phpunit_message_sink $messagesink */
    private $messagesink;

    private $orgframe, $posframe;
    private $users = array(), $organisations = array(), $positions = array(), $audiences = array(), $managers = array(), $managerjas= array();

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->messagesink = $this->redirectMessages();

        // Number of each assignment type to a program.
        $maxusers = 40;
        $maxorgs = 3;
        $maxpos = 3;
        $maxauds = 3;
        $maxmanagers = 3;

        $this->data_generator = $this->getDataGenerator();
        $this->program_generator = $this->data_generator->get_plugin_generator('totara_program');
        $this->hierarchy_generator = $this->data_generator->get_plugin_generator('totara_hierarchy');
        $this->cohort_generator = $this->data_generator->get_plugin_generator('totara_cohort');
        $this->plan_generator = $this->data_generator->get_plugin_generator('totara_plan');

        for($numuser = 0; $numuser < $maxusers; $numuser++) {
            // Important to remember that create_user also creates a job assignment for each user and assigns
            // a manager. When no manager is specified, admin is the manager. So this will be overwritten
            // in this test for users with a managerja type program assignment, the rest will still have
            // admin as manager.
            $this->users[$numuser] = $this->data_generator->create_user();
        }

        $this->orgframe = $this->hierarchy_generator->create_org_frame(array());
        for ($numorg = 0; $numorg < $maxorgs; $numorg++) {
            $this->organisations[$numorg] = $this->hierarchy_generator->create_org(array('frameworkid' => $this->orgframe->id));
        }

        $this->posframe = $this->hierarchy_generator->create_pos_frame(array());
        for ($numpos = 0; $numpos < $maxpos; $numpos++) {
            $this->positions[$numpos] = $this->hierarchy_generator->create_pos(array('frameworkid' => $this->posframe->id));
        }

        for ($numaud = 0; $numaud < $maxauds; $numaud++) {
            $this->audiences[$numaud] = $this->data_generator->create_cohort();
        }

        for($numman = 0; $numman < $maxmanagers; $numman++) {
            // This is really assignment via hierarchies based on the manager's job assignment rather than
            // the manager themselves. For our testing, the managers and their job assignments map onto each
            // other according to the keys in $this->managers and $this->managerjas.
            $this->managers[$numman] = $this->data_generator->create_user();
            $this->managerjas[$numman] = \totara_job\job_assignment::create_default($this->managers[$numman]->id);
        }
    }

    protected function tearDown() {
        $this->messagesink->clear();
        $this->messagesink->close();
        $this->messagesink = null;
        $this->data_generator = null;
        $this->program_generator = null;
        $this->hierarchy_generator = null;
        $this->cohort_generator = null;
        $this->plan_generator = null;
        $this->orgframe = null;
        $this->users = null;
        parent::tearDown();
    }

    /**
     * Creates an array of arrays. Each component array represents a users data.
     * It includes its own index to help us track which user we are dealing with at any one time.
     *  - So array with index 2 will be the user in $this->users[2].
     * It then includes an array of methods by which that user will be assigned to a program.
     * It then includes an array of objects in the same order as the assignment types.
     *  - This means that if the 1st assignment method is ASSIGNTYPE_ORGANISATION,
     * then the first item of the other array should be the organisation to assign to.
     *
     */
    private function get_assignment_data() {
        $user_data_full =  array(
            array(0, array(ASSIGNTYPE_INDIVIDUAL), array($this->users[0])),
            array(1, array(ASSIGNTYPE_INDIVIDUAL), array($this->users[1])),
            array(2, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[0])),
            array(3, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[0])),
            array(4, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[1])),
            array(5, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[1])),
            array(6, array(ASSIGNTYPE_POSITION), array($this->positions[0])),
            array(7, array(ASSIGNTYPE_POSITION), array($this->positions[0])),
            array(8, array(ASSIGNTYPE_POSITION), array($this->positions[1])),
            array(9, array(ASSIGNTYPE_POSITION), array($this->positions[1])),
            array(10, array(ASSIGNTYPE_COHORT), array($this->audiences[0])),
            array(11, array(ASSIGNTYPE_COHORT), array($this->audiences[0])),
            array(12, array(ASSIGNTYPE_COHORT), array($this->audiences[1])),
            array(13, array(ASSIGNTYPE_COHORT), array($this->audiences[1])),
            array(14, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[0])),
            array(15, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[0])),
            array(16, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[1])),
            array(17, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[1])),
            array(18, array(ASSIGNTYPE_ORGANISATION, ASSIGNTYPE_POSITION), array($this->organisations[0], $this->positions[1])),
            array(19, array(ASSIGNTYPE_ORGANISATION, ASSIGNTYPE_POSITION, ASSIGNTYPE_COHORT),
                array($this->organisations[1], $this->positions[1], $this->audiences[0])),
            array(20, array(ASSIGNTYPE_COHORT, ASSIGNTYPE_MANAGERJA), array($this->audiences[1], $this->managerjas[1])),
            array(21, array(ASSIGNTYPE_COHORT, ASSIGNTYPE_INDIVIDUAL), array($this->audiences[1], $this->users[21])),
            array(22, array(), array()), // This user will not be assigned to anything.
            array(23, array(), array()), // This user will not be assigned to anything.
        );

        return $user_data_full;
    }

    /**
     * @param program $program
     * @param array $user_data_full - array in a format like that returned in get_assignment_data above.
     */
    private function assign_users_to_program($program, $user_data_full) {

        foreach($user_data_full as $user_data) {
            foreach($user_data[1] as $key => $assignment_method) {
                $userid = $this->users[$user_data[0]]->id;
                // Groupid could actually be the individual user id in the case of ASSIGNTYPE_INDIVIDUAL
                // but for all other cases, its the id of the audience, organisation etc.
                $groupid = $user_data[2][$key]->id;
                // All users will have a job assignment record that was created during create_user.
                $jobassignment = \totara_job\job_assignment::get_first($userid);
                switch($assignment_method) {
                    case ASSIGNTYPE_ORGANISATION:
                        $jobassignment->update(array('organisationid' => $groupid));
                        break;
                    case ASSIGNTYPE_POSITION:
                        $jobassignment->update(array('positionid' => $groupid));
                        break;
                    case ASSIGNTYPE_COHORT:
                        $this->cohort_generator->cohort_assign_users($groupid, array($userid));
                        break;
                    case ASSIGNTYPE_MANAGERJA:
                        $jobassignment->update(array('managerjaid' => $groupid));
                        break;
                }
                $this->data_generator->assign_to_program($program->id, $assignment_method, $user_data[2][$key]->id);
            }
        }
    }

    /**
     * These are parts of the strings returned when assignment is for their corresponding reason.
     *
     * The html hasn't been included here. This is really just to be used as part of a strpos to indicate
     * that the correct reasons were included in the string, not to validate the entire message.
     *
     * @return array
     */
    private function get_expected_assignment_reason_strings() {
        $expectedstrings = array(
            ASSIGNTYPE_COHORT => 'Member of audience',
            ASSIGNTYPE_INDIVIDUAL => 'Assigned as an individual',
            ASSIGNTYPE_MANAGERJA => 'Part of',
            ASSIGNTYPE_ORGANISATION => 'Member of organisation',
            ASSIGNTYPE_POSITION => 'Hold position of ',
        );

        return $expectedstrings;
    }

    /**
     * The dataProvider for a number of assignment and completion record reason tests.
     *
     * @return array
     */
    public function program_type() {
        $data = array(
            array('program'),
            array('certification')
        );

        return $data;
    }

    /**
     * Given the type, 'program' or 'certification', returns the program instances used
     * for assignment and completion record reason tests.
     *
     * @param $type - a string in the array returned by the program_type dataProvider.
     * @return program[]
     */
    private function get_program_objects($type) {
        if ($type === 'program') {
            /** @var program $program1 */
            $program1 = $this->program_generator->create_program();
            /** @var program $program2 */
            $program2 = $this->program_generator->create_program();
        } else {
            // Create a certification. We only need to deal with it's related prog records for this
            // test though.
            $program1id = $this->program_generator->create_certification();
            $program1 = new program($program1id);

            $program2id = $this->program_generator->create_certification();
            $program2 = new program($program2id);
        }

        return array($program1, $program2);
    }

    /**
     * Tests the program->display_required_assignment_reason method.
     *
     * The dataProvider ensures this is tested for both programs and certs.
     *
     * The user assignments as defined in get_assignment_data make sure and each assignment
     * method is tested along with combinations of them.
     *
     * @dataProvider program_type
     */
    public function test_display_required_assignment_reason($type) {
        $this->resetAfterTest(true);

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $assignmentdata = $this->get_assignment_data();
        $this->assign_users_to_program($program1, $assignmentdata);

        $this->setAdminUser();

        // Strings for start of assignment reason string, before show list of reasons.
        $learnerisassignedtocomplete = '<p>The learner is required to complete this program under the following criteria:</p>';
        $youarerequiredtocompleted = '<p>You are required to complete this program under the following criteria:</p>';
        $expectedreasonstrings = $this->get_expected_assignment_reason_strings();

        foreach($assignmentdata as $userassigned) {

            $userid = $this->users[$userassigned[0]]->id;

            // First of all, none should be assigned to program2.
            $program2reason = $program2->display_required_assignment_reason($userid, true);
            $this->assertEquals('', $program2reason);

            // Let's run the function we're testing.
            $returnedreason = $program1->display_required_assignment_reason($userid, true);

            if (empty($userassigned[1])) {
                // There are no assignments specified for this user. So no string should be returned.
                $this->assertEquals('', $returnedreason);
            } else {
                // We said true for the $viewinganothersprogram param, so should return 'The learner is required to complete...'.
                $this->assertNotFalse(strpos($returnedreason, $learnerisassignedtocomplete),
                    'The user with index ' . $userassigned[0] . ' did not return the expected string.');
                // Loop through the expected reason strings making sure those and only those we expect are present.
                foreach($expectedreasonstrings as $assignmentmethod => $expectedreasonstring) {
                    if (in_array($assignmentmethod, $userassigned[1])) {
                        $this->assertNotFalse(strpos($returnedreason, $expectedreasonstring),
                            'The user with index ' . $userassigned[0] . ' did not return an expected reason.');
                    } else {
                        $this->assertFalse(strpos($returnedreason, $expectedreasonstring),
                            'The user with index ' . $userassigned[0] . ' returned a reason it should not have.');
                    }
                }
            }
        }

        // Our loop tested a whole range of scenarios but it's clunky to test for more specific
        // strings, e.g. manager's names, in a loop.  We do a few spot checks below to test for actual manager, org and pos
        // names in the returned message.

        // User[11] should have a reason of being assigned to audience[0]
        $user11 = $this->users[11];
        $expected = $learnerisassignedtocomplete;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_required_assignment_reason($user11->id, true);
        $this->assertEquals($expected, $returnedreason);

        // Let's test this one with some different settings for $USER and the 2nd param of display_assignment_reason.

        // First of all,have user11 view their own record.
        $this->setUser($user11);
        $expected = $youarerequiredtocompleted;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_required_assignment_reason($user11->id, true);
        $this->assertEquals($expected, $returnedreason);

        // Now set $includefull to false. This means we should just get the <li> tags and their contents.
        $expected = '<li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li>';
        $returnedreason = $program1->display_required_assignment_reason($user11->id, false);
        $this->assertEquals($expected, $returnedreason);

        // User[16] should have the reasons of being part of manager[1]'s team.
        $this->setAdminUser();
        $user16 = $this->users[16];
        $expected = $learnerisassignedtocomplete;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $returnedreason = $program1->display_required_assignment_reason($user16->id, true);
        $this->assertEquals($expected, $returnedreason);

        // User[22] has not been assigned to the program. An empty string should be returned.
        $user22 = $this->users[22];
        $expected = '';
        $returnedreason = $program1->display_required_assignment_reason($user22->id, true);
        $this->assertEquals($expected, $returnedreason);
        // We don't expect any different when the other settings are changed.
        $returnedreason = $program1->display_required_assignment_reason($user22->id, true);
        $this->assertEquals($expected, $returnedreason);
        $returnedreason = $program1->display_required_assignment_reason($user22->id, false);
        $this->assertEquals($expected, $returnedreason);
    }

    /**
     * Tests the program->display_completion_record_reason method when used with standard assignment methods.
     *
     * @dataProvider program_type
     */
    public function test_display_completion_record_reason_required_assignments($type) {
        $this->resetAfterTest(true);

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $assignmentdata = $this->get_assignment_data();
        $this->assign_users_to_program($program1, $assignmentdata);

        // Strings for start of assignment reason string, before show list of reasons.
        $hasrecordbecause = '<p>This user is assigned for the following reasons:</p>';

        // We don't need to test everything that would be done internally by the display_required_assignment_reason method in full,
        // we just need to make sure that when there's data that would process, it is returned properly by display_completion_record_reason.

        // User[11] should have a reason of being assigned to audience[0]
        $user11 = $this->users[11];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user11);
        $this->assertEquals($expected, $returnedreason);

        // User[16] should have the reasons of being part of manager[1]'s team.
        $user16 = $this->users[16];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user16);
        $this->assertEquals($expected, $returnedreason);
    }

    /**
     * Tests the program->display_completion_record_reason method when a user is assigned via a learning plan.
     *
     * Note that we use the dataProvider to test this with certifications as well as programs.
     * Certifications are currently not supported by learning plans (at the time of writing this test),
     * but I've included certs for now since it is still passing.
     *
     * @dataProvider program_type
     */
    public function test_display_completion_record_reason_learningplan($type) {
        $this->resetAfterTest(true);

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $user = $this->users[25];
        $this->setUser($user);

        $enddate = time() + DAYSECS;
        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $user->id, 'enddate' => $enddate));
        $plan = new development_plan($planrecord->id);

        $plan->initialize_settings();
        /** @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');
        $assigneditem = $component_program->assign_new_item($program1->id);

        $expected = '<p>This program has been added to their learning plan. However, please note that this has not been approved.</p>';
        $returnedreason = $program1->display_completion_record_reason($user);
        $this->assertEquals($expected, $returnedreason);

        // Set the status to approved.
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        $expected = '<p>This user is assigned for the following reasons:</p><ul><li>Assigned via learning plan.</li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user);
        $this->assertEquals($expected, $returnedreason);
    }

    /**
     * Tests the program->display_completion_record_reason method. This a message is given acknowledging
     * when users are suspended or deleted.
     *
     * @dataProvider program_type
     */
    public function test_display_completion_record_reason_deleted_suspended($type) {
        $this->resetAfterTest(true);
        global $DB;

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $assignmentdata = $this->get_assignment_data();
        $this->assign_users_to_program($program1, $assignmentdata);

        // Strings for start of assignment reason string, before show list of reasons.
        $hasrecordbecause = '<p>This user is assigned for the following reasons:</p>';

        // Check their status before deletion and suspension.

        // User[11] should have a reason of being assigned to audience[0]
        $user11 = $this->users[11];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user11);
        $this->assertEquals($expected, $returnedreason);

        // User[16] should have the reasons of being part of manager[1]'s team.
        $user16 = $this->users[16];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user16);
        $this->assertEquals($expected, $returnedreason);

        delete_user($user11);
        $user11 = $DB->get_record('user', array('id' => $user11->id));

        $user16->suspended = 1;
        user_update_user($user16, false);
        \totara_core\event\user_suspended::create_from_user($user16)->trigger();
        $program1->update_learner_assignments();
        $user16 = $DB->get_record('user', array('id' => $user16->id));

        $expected = '<p>The user has been deleted and they are not currently assigned.</p>';
        $returnedreason = $program1->display_completion_record_reason($user11);
        $this->assertEquals($expected, $returnedreason);

        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $expected .= '<p>This user is suspended. Automated processes such as cron tasks are unlikely to update this user\'s records.</p>';
        $returnedreason = $program1->display_completion_record_reason($user16);
        $this->assertEquals($expected, $returnedreason);
    }

    // Create some completion records by direct insert where there will be no explanation for them.
    /**
     *
     */
    public function test_display_completion_record_reason_unknown() {
        $this->resetAfterTest(true);
        global $DB;

        // Create a program and a certification.

        /** @var program $program1 */
        $program1 = $this->program_generator->create_program();

        $certprogram1id = $this->program_generator->create_certification();
        /** @var program $certprogram1 */
        $certprogram1 = new program($certprogram1id);

        $user25 = $this->users[25];
        $user26 = $this->users[26];
        $user27 = $this->users[27];
        $user28 = $this->users[28];

        /*
         * $user25 has a prog completion record for the program.
         * $user26 has both a prog completion record and a cert completion for the cert.
         * $user27 has no prog completion but not cert completion for the cert.
         * $user28 has a prog completion but no cert completion for the cert.
         */

        $progcompletion25 = new stdClass();
        $progcompletion25->programid = $program1->id;
        $progcompletion25->userid = $user25->id;
        $progcompletion25->coursesetid = 0;
        $progcompletion25->status = 0;
        $progcompletion25->timestarted = time();
        $progcompletion25->timedue = COMPLETION_TIME_NOT_SET;
        $progcompletion25->timecompleted = 0;
        $DB->insert_record('prog_completion', $progcompletion25);

        $progcompletion26 = clone $progcompletion25;
        $progcompletion26->programid = $certprogram1->id;
        $progcompletion26->userid = $user26->id;
        $DB->insert_record('prog_completion', $progcompletion26);

        $certcompletion26 = new stdClass();
        $certcompletion26->certifid = $certprogram1->certifid;
        $certcompletion26->userid = $user26->id;
        $certcompletion26->certifpath = CERTIFPATH_CERT;
        $certcompletion26->status = CERTIFSTATUS_ASSIGNED;
        $certcompletion26->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion26->timewindowopens = 0;
        $certcompletion26->timeexpires = 0;
        $certcompletion26->timecompleted = 0;
        $certcompletion26->timemodified = time();
        $DB->insert_record('certif_completion', $certcompletion26);

        $certcompletion27 = clone $certcompletion26;
        $certcompletion27->userid = $user27->id;
        $DB->insert_record('certif_completion', $certcompletion27);

        $progcompletion28 = clone $progcompletion26;
        $progcompletion28->userid = $user28->id;
        $DB->insert_record('prog_completion', $progcompletion28);

        $expected = '<p>No current assignment details could be found.</p>';
        $returnedreason = $program1->display_completion_record_reason($user25);
        $this->assertEquals($expected, $returnedreason);

        // The user has both cert and prog completions, it just needs to say there's no found reason.
        $expected = '<p>No current assignment details could be found.</p>';
        $returnedreason = $certprogram1->display_completion_record_reason($user26);
        $this->assertEquals($expected, $returnedreason);

        // The user has only a cert but no prog completion record. The completion editor currently only shows a current record if
        // it has both.
        $expected = '<p>No current assignment details could be found.</p>';
        $returnedreason = $certprogram1->display_completion_record_reason($user27);
        $this->assertEquals($expected, $returnedreason);

        // The user has only a cert but no prog completion record. The completion editor currently only shows a current record if
        // it has both.
        $expected = '<p>No current assignment details could be found.</p>';
        $returnedreason = $certprogram1->display_completion_record_reason($user28);
        $this->assertEquals($expected, $returnedreason);
    }

    /**
     * Compares to programs, checking they are both programs, and that their public properties as the same.
     *
     * This function does not type hint, but does check internally, to give a unit test failure precedence.
     *
     * @param program $prog_a
     * @param program $prog_b
     */
    private function compare_programs($prog_a, $prog_b) {
        // Check they are both programs.
        $this->assertInstanceOf('program', $prog_a);
        $this->assertInstanceOf('program', $prog_b);

        // Get a list of public properties from the class. We fetch it off the class so that we don't
        // have to update this method when the properties come and go.
        // This will help us ensure any new properties are dealt with consistently.
        $class = new ReflectionClass('program');
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $this->assertEquals(
                $property->getValue($prog_a),
                $property->getValue($prog_b),
                'Differing property value for "'.$property->getName().'"" when comparing programs'
            );
        }
    }

    /**
     * Tests construction of a program object using just the program id.
     */
    public function test_construction_from_id() {
        global $DB;

        $this->resetAfterTest();

        $detail = [
            'fullname' => 'Testing program construction from ID',
            'shortname' => 'Test prog',
        ];
        $program_created = $this->program_generator->create_program($detail);
        $program_id = $DB->get_field('prog', 'id', $detail, MUST_EXIST);

        $this->assertEquals($program_id, $program_created->id);

        $program_loaded = new program($program_id);
        $this->compare_programs($program_created, $program_loaded);

        foreach ($detail as $property => $value) {
            $this->assertTrue(property_exists($program_created, $property));
            $this->assertTrue(property_exists($program_loaded, $property));
            $this->assertSame($value, $program_created->$property);
            $this->assertSame($value, $program_loaded->$property);
        }
    }

    /**
     * Tests construction of a program object using a row from the prog table.
     */
    public function test_construction_from_object() {
        global $DB;

        $this->resetAfterTest();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
        ];
        $program_created = $this->program_generator->create_program($detail);
        $program_record = $DB->get_record('prog', $detail, '*', MUST_EXIST);

        $this->assertEquals($program_record->id, $program_created->id);

        $program_loaded = new program($program_record);
        $this->compare_programs($program_created, $program_loaded);

        foreach ($detail as $property => $value) {
            $this->assertTrue(property_exists($program_created, $property));
            $this->assertTrue(property_exists($program_loaded, $property));
            $this->assertTrue(property_exists($program_record, $property));
            $this->assertSame($value, $program_created->{$property});
            $this->assertSame($value, $program_loaded->{$property});
            $this->assertSame($value, $program_record->{$property});
        }
    }

    /**
     * Tests construction of a program object is not possible using an incomplete program object.
     */
    public function test_construction_from_incomplete_object() {
        global $DB;

        $this->resetAfterTest();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
        ];
        $program_created = $this->program_generator->create_program($detail);
        $program_record = $DB->get_record('prog', $detail, 'id, category, sortorder, fullname, shortname, idnumber, summary, endnote', MUST_EXIST);

        $this->assertEquals($program_record->id, $program_created->id);

        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Program created with incomplete program record');
        new program($program_record);
    }

    /**
     * Tests construction of a program object is not possible using a record from the wrong table.
     */
    public function test_construction_from_incorrect_object() {
        global $DB;

        $this->resetAfterTest();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
        ];
        $program_created = $this->program_generator->create_program($detail);
        $program_record = $DB->get_record('prog', $detail, '*', MUST_EXIST);

        $course_created = $this->data_generator->create_course($detail);
        $course_record = $DB->get_record('course', $detail, '*', MUST_EXIST);

        $this->assertEquals($program_record->id, $program_created->id);
        $this->assertEquals($course_record->id, $course_created->id);

        $program_loaded = new program($program_record);
        $this->compare_programs($program_created, $program_loaded);

        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Program created with incomplete program record');

        new program($course_record);
    }

    /**
     * Tests the construction of a program from an invalid id.
     */
    public function test_construction_from_invalid_id() {

        // This can't possibly exist, as we've not created it.
        $this->expectException('ProgramException');
        $this->expectExceptionMessage('Program does not exist for ID : 7');
        new program(7);

    }

    /**
     * Tests construction of a program object when no student role has been identified.
     */
    public function test_construction_without_student_role_id() {
        global $CFG;

        $this->resetAfterTest();

        $detail = [
            'fullname' => 'Testing program construction from ID',
            'shortname' => 'Test prog',
        ];
        $program = $this->program_generator->create_program($detail);

        $CFG->learnerroleid = 0;
        try {
            new program($program->id);
            $this->fail("Exception expected");
        } catch (moodle_exception $e) {
            $this->assertSame('Could not find role with shortname learner', $e->getMessage());
        }

        $CFG->learnerroleid = null;
        try {
            new program($program->id);
            $this->fail("Exception expected");
        } catch (moodle_exception $e) {
            $this->assertSame('Could not find role with shortname learner', $e->getMessage());
        }

        $CFG->learnerroleid = false;
        try {
            new program($program->id);
            $this->fail("Exception expected");
        } catch (moodle_exception $e) {
            $this->assertSame('Could not find role with shortname learner', $e->getMessage());
        }
    }

    /**
     * Tests getting the context of the program.
     */
    public function test_get_context() {

        $this->resetAfterTest();

        $category = $this->data_generator->create_category();
        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
            'category' => $category->id
        ];
        $program = $this->program_generator->create_program($detail);
        $context = $program->get_context();

        $this->assertInstanceOf('context_program', $context);
        $this->assertEquals(CONTEXT_PROGRAM, $context->contextlevel);
        $this->assertEquals($program->id, $context->instanceid);
        $this->assertEquals(3, $context->depth);

        $systemcontext =context_system::instance();
        $categorycontext = context_coursecat::instance($category->id);
        $this->assertEquals('/'.$systemcontext->id.'/'.$categorycontext->id.'/'.$context->id, $context->path);
    }

    /**
     * Tests the program_get_context() function.
     */
    public function test_program_get_context() {
        $this->resetAfterTest();

        $category = $this->data_generator->create_category();
        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
            'category' => $category->id
        ];
        $program = $this->program_generator->create_program($detail);
        $context = program_get_context($program->id);

        $this->assertInstanceOf('context_program', $context);
        $this->assertEquals(CONTEXT_PROGRAM, $context->contextlevel);
        $this->assertEquals($program->id, $context->instanceid);
        $this->assertEquals(3, $context->depth);

        $systemcontext =context_system::instance();
        $categorycontext = context_coursecat::instance($category->id);
        $this->assertEquals('/'.$systemcontext->id.'/'.$categorycontext->id.'/'.$context->id, $context->path);
    }

    /**
     * Tests fetching the content of the course.
     *
     * Please note this does not test the generation of the program content, just the it returns the content we expect.
     */
    public function test_get_content() {

        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 10; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(10, $courses);

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        // Each course set has only a single course because internally when adding content like this
        // it calls rand(1, $numcourses)! how lame is that.
        // The only predictable number is 1. Because rand(1,1) is always 1.
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);

        $program = new program($program->id);
        $content = $program->get_content();
        $this->assertInstanceOf('prog_content', $content);
        $coursesets = $content->get_course_sets();
        $this->assertCount(3, $coursesets);
        foreach ($coursesets as $courseset) {
            $this->assertInstanceOf('multi_course_set', $courseset);
            $this->assertCount(1, $courseset->get_courses());
        }
    }

    /**
     * Tests a program cannot be created with available data.
     */
    public function test_creation_with_availabile_property_is_not_allowed() {
        // This is easy to test - just set availability to true, it is not allowed in any form at this point.
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Property \'available\' is automatically calculated based on the given from and until dates and should not be manually specified');
        program::create(['available' => true]);
    }

    /**
     * Tests the resetting of assignments from the program.
     */
    public function test_reset_assignments() {

        $this->resetAfterTest();

        $courses = [];
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
            $user = $this->data_generator->create_user(['email' => 'user'.$i.'@example.com', 'username' => 'user'.$i, 'idnumber' => 'u'.$i]);
            $users[$user->id] = $user;
        }
        $this->assertCount(10, $courses);
        $this->assertCount(10, $users);

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);

        // Initialise the assignments now, prior to adding any.
        $assignments_preassign = $program->get_assignments();
        $this->assertCount(0, $assignments_preassign->get_assignments());

        $this->assertCount(0, $program->get_program_learners());
        $this->program_generator->assign_program($program->id, array_keys($users));
        $this->assertCount(10, $program->get_program_learners());

        // Check we still have 0. Because its not been reset yet.
        $assignments_preassign = $program->get_assignments();
        $this->assertCount(0, $assignments_preassign->get_assignments());

        // Reset and count the result from the reset.
        $program->reset_assignments();
        $this->assertCount(10, $program->get_assignments()->get_assignments());

        $assignments_postassign = $program->get_assignments();

        // Check that the pre and post assign are both the same object - exactly the same.
        $this->assertSame($assignments_preassign, $assignments_postassign);
        $this->assertCount(10, $assignments_postassign->get_assignments());

    }

    /**
     * Test fetching the exceptions manager.
     *
     * Please note this does not actually test exceptions, just that we get back the expected object.
     */
    public function test_get_exceptionsmanager() {

        $this->resetAfterTest();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
        ];
        $program = $this->program_generator->create_program($detail);
        $manager = $program->get_exceptionsmanager();
        // Check it is what we expect it to be.
        $this->assertInstanceOf('prog_exceptions_manager', $manager);
        // Check there are no exceptions (no assignments so it has to be the case).
        $this->assertSame(0, $manager->count_exceptions());
    }

    /**
     * Test that assigned users can access and gain enrolment in courses.
     */
    public function test_assigned_learners_are_enrollable_in_courses() {
        global $DB;

        $this->resetAfterTest();

        $courses = [];
        $users = [];
        $reassignuserids = [];
        for ($i = 0; $i < 5; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
            $user = $this->data_generator->create_user(['email' => 'user'.$i.'@example.com', 'username' => 'user'.$i, 'idnumber' => 'u'.$i]);
            $users[$user->id] = $user;
            if ($i < 3) {
                $reassignuserids[] = $user->id;
            }
        }
        $this->assertCount(5, $courses);
        $this->assertCount(5, $users);

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->assertCount(0, $program->get_program_learners());
        $this->program_generator->assign_program($program->id, array_keys($users));
        $this->assertCount(5, $program->get_program_learners());

        // Now we need to assign the users to the program and ensure they are enrolled in the course.
        $program = new program($program->id);
        $coursesets = $program->get_content()->get_course_sets();
        /** @var multi_course_set $courseset */
        $courseset = reset($coursesets);
        $this->assertInstanceOf('multi_course_set', $courseset);
        $coursesetcourses = $courseset->get_courses();
        $course = reset($coursesetcourses);
        foreach ($users as $user) {
            // They should not be enrolled yet, not until they first access the course.
            $enrolledcourses = enrol_get_all_users_courses($user->id);
            $this->assertCount(0, $enrolledcourses);

            /// Now check if they can enter - this adds the enrolment record.
            $result = prog_can_enter_course($user, $course);
            $this->assertObjectHasAttribute('enroled', $result);
            $this->assertTrue($result->enroled);

            // They should now be enrolled.
            $enrolledcourses = enrol_get_all_users_courses($user->id);
            $this->assertCount(1, $enrolledcourses);
            $enrolledcourse = reset($enrolledcourses);
            $this->assertSame($course->fullname, $enrolledcourse->fullname);
        }

        // Make sure that assign_learners_bulk calls process_program_reassignments for all users.

        /* @var enrol_totara_program_plugin $programenrolmentplugin */
        $programenrolmentplugin = enrol_get_plugin('totara_program');
        $course1instance = $programenrolmentplugin->get_instance_for_course($course->id);
        $courseenrolid = $course1instance->id;

        // Check the data before we being.
        $this->assertCount(5, $program->get_program_learners());
        $this->assertEquals(5, $DB->count_records('user_enrolments', array('enrolid' => $courseenrolid)));
        $this->assertEquals(5, $DB->count_records('user_enrolments', array('enrolid' => $courseenrolid, 'status' => ENROL_USER_ACTIVE)));

        // Unassign users from the program and check that the enrolments are suspended.
        $this->program_generator->assign_program($program->id, array());
        $this->assertCount(0, $program->get_program_learners());
        $this->assertEquals(5, $DB->count_records('user_enrolments', array('enrolid' => $courseenrolid)));
        $this->assertEquals(5, $DB->count_records('user_enrolments', array('enrolid' => $courseenrolid, 'status' => ENROL_USER_SUSPENDED)));

        // Reassign SOME users from the program and check that the enrolments are no longer suspended and events were triggered.
        array_pop($users);
        array_pop($users);
        $eventsink = $this->redirectEvents();
        $this->program_generator->assign_program($program->id, $reassignuserids);
        $events = $eventsink->get_events();
        $this->assertCount(3, $program->get_program_learners());
        $this->assertEquals(5, $DB->count_records('user_enrolments', array('enrolid' => $courseenrolid)));
        $this->assertEquals(3, $DB->count_records('user_enrolments', array('enrolid' => $courseenrolid, 'status' => ENROL_USER_ACTIVE)));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array('enrolid' => $courseenrolid, 'status' => ENROL_USER_SUSPENDED)));

        // Check that the correct events were triggered.
        $reassigneventcount = 0;
        $actualreassignuserids = array();
        foreach ($events as $event) {
            $eventdata = $event->get_data();
            if ($eventdata['eventname'] == '\core\event\user_enrolment_updated') {
                $this->assertEquals('updated', $eventdata['action']);
                $this->assertEquals($course->id, $eventdata['courseid']);
                $this->assertEquals('totara_program', $eventdata['other']['enrol']);
                $actualreassignuserids[$eventdata['relateduserid']] = $eventdata['relateduserid'];
                $reassigneventcount++;

            }
        }
        $this->assertEquals(3, $reassigneventcount);
        sort($actualreassignuserids);
        sort($reassignuserids);
        $this->assertEquals($reassignuserids, $actualreassignuserids);
    }

    /**
     * Test unassigning users.
     */
    public function test_unassign_learners() {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $this->resetAfterTest();

        $courses = [];
        $users = [];
        $oddusers = [];
        for ($i = 0; $i < 6; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
            $user = $this->data_generator->create_user(['email' => 'user'.$i.'@example.com', 'username' => 'user'.$i, 'idnumber' => 'u'.$i]);
            $users[$user->id] = $user;
            if ($i % 2 !== 0) {
                $oddusers[$user->id] = $user;
            }
        }
        $this->assertCount(6, $courses);
        $this->assertCount(6, $users);
        $this->assertCount(3, $oddusers);

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        // Make sure there are no users assigned.
        $this->assertCount(0, $program->get_program_learners());
        $this->program_generator->assign_program($program->id, array_keys($users));
        // Now make sure we have 6.
        $this->assertCount(6, $program->get_program_learners());

        // Now we need to assign the users to the program and ensure they are enrolled in the course.
        $program = new program($program->id);
        $coursesets = $program->get_content()->get_course_sets();
        /** @var multi_course_set $courseset */
        $courseset = reset($coursesets);
        $this->assertInstanceOf('multi_course_set', $courseset);
        $coursesetcourses = $courseset->get_courses();
        $course = reset($coursesetcourses);
        foreach ($users as $user) {
            // They should not be enrolled yet, not until they first access the course.
            $enrolledcourses = enrol_get_all_users_courses($user->id);
            $this->assertCount(0, $enrolledcourses);

            /// Now check if they can enter - this adds the enrolment record.
            $result = prog_can_enter_course($user, $course);
            $this->assertObjectHasAttribute('enroled', $result);
            $this->assertTrue($result->enroled);

            // They should now be enrolled.
            $enrolledcourses = enrol_get_all_users_courses($user->id);
            $this->assertCount(1, $enrolledcourses);
            $enrolledcourse = reset($enrolledcourses);
            $this->assertSame($course->fullname, $enrolledcourse->fullname);
        }

        $page = new moodle_page();

        // Get an active enrolment manager to make this easier.
        $enrolmentmanager = new course_enrolment_manager($page, $course, null, 0, '', 0, ENROL_USER_ACTIVE);

        // Just to be safe.
        $program->reset_assignments();
        $this->assertCount(6, $program->get_assignments()->get_assignments());
        $this->assertSame(6, $enrolmentmanager->get_total_users());

        $program->unassign_learners(array_keys($oddusers));

        $assignedusers = $program->get_program_learners();
        $this->assertCount(3, $assignedusers);

        // We need to get this again as it is statically cached internally :(
        $enrolmentmanager = new course_enrolment_manager($page, $course, null, 0, '', 0, ENROL_USER_ACTIVE);
        $this->assertSame(3, $enrolmentmanager->get_total_users());

        foreach ($users as $user) {
            $enrolledcourses = enrol_get_all_users_courses($user->id, true);
            if (isset($oddusers[$user->id])) {
                // The user should have been unassigned.
                $this->assertCount(0, $enrolledcourses);
                $this->assertFalse(in_array($user->id, $assignedusers));
            } else {
                // The user is still enrolled.
                $this->assertCount(1, $enrolledcourses);
                $this->assertTrue(in_array($user->id, $assignedusers));
            }
        }
    }

    /**
     * Test that completion records are processed correctly when unassigning users.
     */
    public function test_unassign_learners_completion_records() {
        global $DB;

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $generator->create_certification();
        $cert2 = $generator->create_certification();

        // Create some programs.
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Add the users to the certs.
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Add the users to the programs.
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);

        // Mark program 1 as complete.
        $progcompletion = prog_load_completion($prog1->id, $user1->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $this->assertEquals(array(), prog_get_completion_errors($progcompletion));
        $this->assertTrue(prog_write_completion($progcompletion));

        // Mark cert 1 as complete.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user1->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');

        // 1) Remove user1 from prog1. Nothing changes because the program is complete.
        $prog1->unassign_learners(array($user1->id));

        // And check that the expected records match the actual records.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // 2) Remove user1 from prog2. The prog_completion should be deleted.
        $prog2->unassign_learners(array($user1->id));

        // Manually make the same change to the expected data.
        foreach ($expectedprogcompletions as $key => $progcompletion) {
            if ($progcompletion->programid == $prog2->id && $progcompletion->userid == $user1->id) {
                unset($expectedprogcompletions[$key]);
            }
        }

        // And check that the expected records match the actual records.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // 3) Remove user1 from cert1. Only the certif_completion record is deleted because the program is complete.
        $cert1->unassign_learners(array($user1->id));

        // Manually make the same change to the expected data.
        foreach ($expectedcertcompletions as $key => $certcompletion) {
            if ($certcompletion->certifid == $cert1->certifid && $certcompletion->userid == $user1->id) {
                unset($expectedcertcompletions[$key]);
            }
        }

        // And check that the expected records match the actual records.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // 4) Remove user1 from cert2. Both completion records are deleted.
        $cert2->unassign_learners(array($user1->id));

        // Manually make the same change to the expected data.
        foreach ($expectedcertcompletions as $key => $certcompletion) {
            if ($certcompletion->certifid == $cert2->certifid && $certcompletion->userid == $user1->id) {
                unset($expectedcertcompletions[$key]);
            }
        }
        foreach ($expectedprogcompletions as $key => $progcompletion) {
            if ($progcompletion->programid == $cert2->id && $progcompletion->userid == $user1->id) {
                unset($expectedprogcompletions[$key]);
            }
        }

        // And check that the expected records match the actual records.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);
    }

    /**
     * Test that assigned users can access and gain enrolment in courses.
     */
    public function test_user_is_assigned() {

        $this->resetAfterTest();

        $courses = [];
        $users = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
            $user = $this->data_generator->create_user(['email' => 'user'.$i.'@example.com', 'username' => 'user'.$i, 'idnumber' => 'u'.$i]);
            $users[$user->id] = $user;
        }
        $this->assertCount(2, $courses);
        $this->assertCount(2, $users);

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->assign_program($program->id, array_keys($users));

        $unassigneduser = $this->data_generator->create_user();

        foreach ($users as $user) {
            $this->assertTrue($program->user_is_assigned($user->id));
        }
        $this->assertFalse($program->user_is_assigned($unassigneduser->id));

        $this->assertFalse($program->user_is_assigned(0));
        $this->assertFalse($program->user_is_assigned(-1));
    }

    /**
     * Test user_is_assigned with users who have been assigned through a plan.
     */
    public function test_user_is_assigned_through_plan() {
        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(2, $courses);

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);

        $planuser = $this->data_generator->create_user();
        // Not assigned through a plan yet.
        $this->assertFalse($program->user_is_assigned($planuser->id));

        // We need a capable user for this next bit.
        $this->setAdminUser();

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->data_generator->get_plugin_generator('totara_plan');
        $plan = $plangenerator->create_learning_plan(['userid' => $planuser->id]);
        $plangenerator->add_learning_plan_program($plan->id, $program->id);
        $plan = new development_plan($plan->id);
        $plan->set_status(DP_APPROVAL_APPROVED);
        plan_activate_plan($plan);

        $program->update_learner_assignments(true);

        $this->assertTrue($program->assigned_through_plan($planuser->id));

        // Is now assigned through a plan.
        $this->assertTrue($program->user_is_assigned($planuser->id));
    }

    /**
     * Test get_all_programs_with_incomplete_users
     */
    public function test_get_all_programs_with_incomplete_users() {

        $this->resetAfterTest();

        $programs = [];
        $courses = [];
        $users = [];
        for ($i = 0; $i < 5; $i++) {

            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
            $user = $this->data_generator->create_user(['email' => 'user'.$i.'@example.com', 'username' => 'user'.$i, 'idnumber' => 'u'.$i]);
            $users[$user->id] = $user;
        }

        $detail = [
            'fullname' => 'Testing program 1',
            'shortname' => 'Test prog 1'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->assign_program($program->id, array_keys($users));
        $programs[] = $program;

        $detail = [
            'fullname' => 'Testing program 2',
            'shortname' => 'Test prog 2'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $programs[] = $program;

        $this->assertCount(2, $programs);
        $this->assertCount(5, $courses);
        $this->assertCount(5, $users);

        $fullnames = [
            'Testing program 1',
        ];
        $programs = program::get_all_programs_with_incomplete_users();
        $this->assertCount(1, $programs);
        foreach ($programs as $program) {
            $this->assertInstanceOf('program', $program);
            $key = array_search($program->fullname, $fullnames);
            $this->assertNotFalse($key);
            // Remove it so it can only be found once.
            unset($fullnames[$key]);
        }
    }

    /**
     * Test the assigned_to_users_required_learning method.
     */
    public function test_assigned_to_users_required_learning() {
        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(2, $courses);

        $user = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);

        // Check its not in their required learning.
        $this->assertFalse($program->assigned_to_users_required_learning($user->id));

        // Assign the user.
        $this->program_generator->assign_program($program->id, [$user->id]);

        // Check it is not in their required learning.
        $this->assertTrue($program->assigned_to_users_required_learning($user->id));
    }

    /**
     * Test the deprecated is_required_learning method.
     */
    public function test_deprecated_is_required_learning() {

        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(2, $courses);

        $user = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);

        $debugmessage = '$program->is_required_learning() is deprecated, use the lib function prog_required_for_user() instead';

        // Check the program is not required, the user is not assigned.
        $this->assertFalse($program->is_required_learning($user->id));
        $this->assertDebuggingCalled($debugmessage);

        // Assign the user and check the program is now required.
        $this->program_generator->assign_program($program->id, [$user->id]);
        $this->assertTrue($program->is_required_learning($user->id));
        $this->assertDebuggingCalled($debugmessage);

        // Mark the program complete for the user, and check it is no longer required.
        $program->update_program_complete($user->id, [
            'status' => STATUS_PROGRAM_COMPLETE,
            'timecompleted' => time()
        ]);
        $this->assertFalse($program->is_required_learning($user->id));
        $this->assertDebuggingCalled($debugmessage);
    }

    /**
     * Test the deprecated is_accessible method.
     */
    public function test_deprecated_is_accessible() {

        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(2, $courses);

        $user = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        
        $debugmessage = '$program->is_accessible() is deprecated, use the lib function prog_is_accessible() instead';

        // Check the program is accessible before the user is not assigned.
        $this->assertTrue($program->is_accessible($user));
        $this->assertDebuggingCalled($debugmessage);

        // Assign the user and check the program still accessible.
        $this->program_generator->assign_program($program->id, [$user->id]);
        $this->assertTrue($program->is_accessible($user));
        $this->assertDebuggingCalled($debugmessage);

        // Create a new program that is not visible.
        $now = time();
        $day = 86400;
        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
            'availablefrom' => $now - ($day * 7),
            'availableuntil' => $now - ($day * 5),
        ];
        $unavailableprogram = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($unavailableprogram->id, 1, 1);
        $this->program_generator->assign_program($program->id, [$user->id]);
        $this->assertEquals(0, $unavailableprogram->available);
        // Check the user cannot access it.
        $this->assertFalse($unavailableprogram->is_accessible($user));
        $this->assertDebuggingCalled($debugmessage);
        // Check the admin can still access it.
        $admin = get_admin();
        $this->assertTrue($unavailableprogram->is_accessible($admin));
        $this->assertDebuggingCalled($debugmessage);
    }

    /**
     * Test the user cannot enter a course through the program if the program is not available.
     */
    public function test_can_enter_course_with_unavailable_program() {
        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(2, $courses);

        $user = $this->data_generator->create_user();

        // Create a new program that is not visible.
        $now = time();
        $day = 86400;
        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog',
            'availablefrom' => $now - ($day * 7),
            'availableuntil' => $now - ($day * 5),
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->assign_program($program->id, [$user->id]);
        $this->assertEquals(0, $program->available);

        foreach ($courses as $course) {
            $this->assertFalse($program->can_enter_course($user->id, $course->id));
        }
    }

    /**
     * Test the get_progress method.
     */
    public function test_get_progress() {

        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 10; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(10, $courses);

        $user = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 10);

        // Check the program progress is 0, the user is not assigned.
        $this->assertEquals(0, $program->get_progress($user->id));

        // Assign the user and check the program progress is still 0.
        $this->program_generator->assign_program($program->id, [$user->id]);
        $this->assertEquals(0, $program->get_progress($user->id));

        // Mark the program complete for the user, and check it is now 100% complete.
        $program->update_program_complete($user->id, [
            'status' => STATUS_PROGRAM_COMPLETE,
            'timecompleted' => time()
        ]);
        $this->assertEquals(100, $program->get_progress($user->id));

        // Lets test completing the first course set.
        // Now we want to mark the incomplete user complete in courses in the first courseset.
        $coursesets = $program->get_content()->get_course_sets();
        /** @var multi_course_set $courseset */
        $courseset = reset($coursesets);
        $this->assertInstanceOf('multi_course_set', $courseset);
        $courses = $courseset->get_courses();
        if (count($courses) === 1) {
            // Stupid bloody random generator.
            // If you get here you are here because we tried to add more than one course to a courseset in the program
            // using the generator and it failed, it randomly selected to add 1. YAY!
            // Look for mt_rand within totara_program_generator::add_courseset_to_program.
            $this->markTestSkipped('Skipped due to bad luck - fix the program add_courseset_to_program method');
        }
        $course = reset($courses);
        // Mark the user as complete in this one course, this should put them into progress.
        $this->mark_user_complete_in_course($user, $course);
        $this->assertFalse($courseset->check_courseset_complete($user->id));
    }

    /**
     * Test the get_program_learners method.
     */
    public function test_get_program_learners() {

        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(2, $courses);

        $user_complete = $this->data_generator->create_user();
        $user_incomplete = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->assign_program($program->id, [$user_complete->id, $user_incomplete->id]);

        // Mark one user as complete.
        $program->update_program_complete($user_complete->id, [
            'status' => STATUS_PROGRAM_COMPLETE,
            'timecompleted' => time()
        ]);

        // Test fetching just the all users.
        $learners = $program->get_program_learners();
        $this->assertCount(2, $learners);

        // Test fetching just the incomplete users.
        $learners = $program->get_program_learners(STATUS_PROGRAM_INCOMPLETE);
        $this->assertCount(1, $learners);
        $learner = reset($learners);
        $this->assertEquals($user_incomplete->id, $learner);

        // Test fetching just the complete users.
        $learners = $program->get_program_learners(STATUS_PROGRAM_COMPLETE);
        $this->assertCount(1, $learners);
        $learner = reset($learners);
        $this->assertEquals($user_complete->id, $learner);
    }

    /**
     * Test the is_program_complete method and the is_program_incomplete method.
     */
    public function test_deprecated_is_program_methods() {

        $this->resetAfterTest();

        $courses = [];
        for ($i = 0; $i < 2; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(2, $courses);

        $user_complete = $this->data_generator->create_user();
        $user_incomplete = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->assign_program($program->id, [$user_complete->id, $user_incomplete->id]);

        // Mark one user as complete.
        $program->update_program_complete($user_complete->id, [
            'status' => STATUS_PROGRAM_COMPLETE,
            'timecompleted' => time()
        ]);

        // Mark the other user as started.
        $program->update_program_complete($user_incomplete->id, [
            'status' => STATUS_PROGRAM_INCOMPLETE,
            'timestarted' => time()
        ]);

        $completedebugging = '$program->is_program_complete() is deprecated, use the lib function prog_is_complete() instead';
        $incompletedebugging = '$program->is_program_inprogress() is deprecated, use the lib function prog_is_inprogress() instead';

        $this->assertTrue($program->is_program_complete($user_complete->id));
        $this->assertDebuggingCalled($completedebugging);
        $this->assertFalse($program->is_program_inprogress($user_complete->id));
        $this->assertDebuggingCalled($incompletedebugging);

        $this->assertFalse($program->is_program_complete($user_incomplete->id));
        $this->assertDebuggingCalled($completedebugging);
        $this->assertTrue($program->is_program_inprogress($user_incomplete->id));
        $this->assertDebuggingCalled($incompletedebugging);
    }

    /**
     * Test deletion of a program.
     */
    public function test_delete() {
        global $USER, $DB;

        $this->resetAfterTest();
        // We need the admin user for this test, as we need to work with learning plans.
        $this->setAdminUser();

        $courses = [];
        for ($i = 0; $i < 5; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(5, $courses);

        $user_complete = $this->data_generator->create_user();
        $user_incomplete = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $program = $this->program_generator->create_program($detail);
        $this->program_generator->add_courseset_to_program($program->id, 1, 1);
        $this->program_generator->add_courseset_to_program($program->id, 2, 1);
        $this->program_generator->assign_program($program->id, [$user_complete->id, $user_incomplete->id]);

        $program = new program($program->id);

        // Mark one user as complete.
        $program->update_program_complete($user_complete->id, [
            'status' => STATUS_PROGRAM_COMPLETE,
            'timecompleted' => time()
        ]);

        // Now we want to mark the incomplete user complete in courses in the first courseset.
        $coursesets = $program->get_content()->get_course_sets();
        $this->assertCount(2, $coursesets);
        /** @var multi_course_set $courseset */
        $courseset = reset($coursesets);
        $this->assertInstanceOf('multi_course_set', $courseset);
        $courses_one = $courseset->get_courses();
        $this->assertCount(1, $courses_one);
        foreach ($courses_one as $course) {
            $this->mark_user_complete_in_course($user_incomplete, $course);
            $this->mark_user_complete_in_course($user_complete, $course);
        }
        // Check they are complete, this will mark the user as complete for the first course set.
        $this->assertTrue($courseset->check_courseset_complete($user_incomplete->id));

        // Now do the same for the last courseset for the complete user.
        $courseset = next($coursesets);
        $this->assertInstanceOf('multi_course_set', $courseset);
        $courses_two = $courseset->get_courses();
        $this->assertCount(1, $courses_two);
        foreach ($courses_two as $course) {
            $this->mark_user_complete_in_course($user_complete, $course);
        }
        $this->assertNotFalse($courseset->check_courseset_complete($user_complete->id));

        // Now add the program to an incomplete users plan.
        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->data_generator->get_plugin_generator('totara_plan');
        $plan = $plangenerator->create_learning_plan(['userid' => $user_incomplete->id]);
        $plangenerator->add_learning_plan_program($plan->id, $program->id);
        $plan = new development_plan($plan->id);
        $plan->set_status(DP_APPROVAL_APPROVED);
        plan_activate_plan($plan);

        // Refresh the object just to make sure it is 100% up to date.
        $program = new program($program->id);
        $programcontext = $program->get_context();

        // At this point the complete user is 100% complete and the incomplete user is 50% complete.
        $this->assertTrue(prog_is_complete($program->id, $user_complete->id));
        $this->assertEquals(100, $program->get_progress($user_complete->id));
        $this->assertTrue(prog_is_inprogress($program->id, $user_incomplete->id));
        $this->assertEquals(50, $program->get_progress($user_incomplete->id));
        // Double check both users are in the state we expect.
        foreach ($courses_one as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course);
        }
        foreach ($courses_two as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course, false);
        }

        // OK now run the event.
        $eventsink = $this->redirectEvents();
        $this->assertTrue($program->delete());

        // First up verify the event.
        $events = $eventsink->get_events();
        $expectedevents = [
            'totara_core\event\bulk_role_assignments_started',
            'core\event\role_unassigned',
            'core\event\role_unassigned',
            'totara_core\event\bulk_role_assignments_ended',
            'totara_program\event\program_unassigned',
            'totara_program\event\program_unassigned',
            'totara_program\event\program_deleted',
        ];
        /** @var \totara_program\event\program_deleted $deletionevent */
        $deletionevent = null;
        foreach ($events as $event) {
            $class = get_class($event);
            $key = array_search($class, $expectedevents);
            $this->assertNotFalse($key);
            // Remove it from the array, to reduce it to 0 hopefully.
            unset($expectedevents[$key]);
            if ($class === 'totara_program\event\program_deleted') {
                $deletionevent = $event;
            }
        }
        // Check we got all of the expected events.
        $this->assertCount(0, $expectedevents, 'The following events were not expected when deleting a program: '.join(', ', $expectedevents));
        // Verify the deletion event now.
        $this->assertNotNull($deletionevent);
        $this->assertInstanceOf('\totara_program\event\program_deleted', $event);
        $this->assertSame($program->id, $event->objectid);
        $this->assertSame($USER->id, $event->userid);
        $this->assertSame($programcontext->id, $event->get_context()->id);

        // Now verify the database.
        $this->assertSame(0, $DB->count_records('prog', ['id' => $program->id]));
        $this->assertSame(0, $DB->count_records('dp_plan_program_assign', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_assignment', ['programid' => $program->id]));
        // $this->assertSame(0, $DB->count_records('prog_completion', ['programid' => $program->id]));
        // $this->assertSame(0, $DB->count_records('prog_completion_history', ['programid' => $program->id]));
        // $this->assertSame(0, $DB->count_records('prog_completion_log', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_courseset', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_exception', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_extension', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_future_user_assignment', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_info_data', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_message', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_recurrence', ['programid' => $program->id]));
        $this->assertSame(0, $DB->count_records('prog_user_assignment', ['programid' => $program->id]));

        // Finally verify the users are still complete in their courses.
        foreach ($courses_one as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course);
        }
        foreach ($courses_two as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course, false);
        }
    }

    /**
     * Test deletion of a certification.
     */
    public function test_delete_certification() {
        global $USER, $DB;

        $this->resetAfterTest();
        // We need the admin user for this test, as we need to work with learning plans.
        $this->setAdminUser();

        $courses = [];
        for ($i = 0; $i < 5; $i++) {
            $courses[] = $this->data_generator->create_course(['fullname' => 'Test course '.$i, 'shortname' => 'Test '.$i, 'idnumber' => 'TC'.$i]);
        }
        $this->assertCount(5, $courses);

        $user_complete = $this->data_generator->create_user();
        $user_incomplete = $this->data_generator->create_user();

        $detail = [
            'fullname' => 'Testing program fullname',
            'shortname' => 'Test prog'
        ];
        $certificationid = $this->program_generator->create_certification($detail);
        $certification = new program($certificationid);
        $this->program_generator->add_courseset_to_program($certification->id, 1, 1);
        $this->program_generator->add_courseset_to_program($certification->id, 2, 1);
        $this->program_generator->assign_program($certification->id, [$user_complete->id, $user_incomplete->id]);

        $certification = new program($certificationid);

        // Mark one user as complete.
        $certification->update_program_complete($user_complete->id, [
            'status' => STATUS_PROGRAM_COMPLETE,
            'timecompleted' => time()
        ]);

        // Now we want to mark the incomplete user complete in courses in the first courseset.
        $coursesets = $certification->get_content()->get_course_sets();
        $this->assertCount(2, $coursesets);
        /** @var multi_course_set $courseset */
        $courseset = reset($coursesets);
        $this->assertInstanceOf('multi_course_set', $courseset);
        $courses_one = $courseset->get_courses();
        $this->assertCount(1, $courses_one);
        foreach ($courses_one as $course) {
            $this->mark_user_complete_in_course($user_incomplete, $course);
            $this->mark_user_complete_in_course($user_complete, $course);
        }
        // Check they are complete, this will mark the user as complete for the first course set.
        $this->assertTrue($courseset->check_courseset_complete($user_incomplete->id));

        // Now do the same for the last courseset for the complete user.
        $courseset = next($coursesets);
        $this->assertInstanceOf('multi_course_set', $courseset);
        $courses_two = $courseset->get_courses();
        $this->assertCount(1, $courses_two);
        foreach ($courses_two as $course) {
            $this->mark_user_complete_in_course($user_complete, $course);
        }
        $this->assertNotFalse($courseset->check_courseset_complete($user_complete->id));

        // Now add the program to an incomplete users plan.
        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $this->data_generator->get_plugin_generator('totara_plan');
        $plan = $plangenerator->create_learning_plan(['userid' => $user_incomplete->id]);
        $plangenerator->add_learning_plan_program($plan->id, $certification->id);
        $plan = new development_plan($plan->id);
        $plan->set_status(DP_APPROVAL_APPROVED);
        plan_activate_plan($plan);

        // Refresh the object just to make sure it is 100% up to date.
        $certification = new program($certification->id);
        $certificationcontext = $certification->get_context();

        prog_update_completion($user_complete->id, $certification);
        prog_update_completion($user_incomplete->id, $certification);

        // At this point the complete user is 100% complete and the incomplete user is 50% complete.
        $this->assertTrue(prog_is_complete($certification->id, $user_complete->id));
        $this->assertEquals(100, $certification->get_progress($user_complete->id));
        $this->assertTrue(prog_is_inprogress($certification->id, $user_incomplete->id));
        $this->assertEquals(50, $certification->get_progress($user_incomplete->id));
        // Double check both users are in the state we expect.
        foreach ($courses_one as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course);
        }
        foreach ($courses_two as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course, false);
        }

        // OK now run the event.
        $eventsink = $this->redirectEvents();
        $this->assertTrue($certification->delete());

        // First up verify the event.
        $events = $eventsink->get_events();
        $expectedevents = [
            'totara_core\event\bulk_role_assignments_started',
            'core\event\role_unassigned',
            'core\event\role_unassigned',
            'totara_core\event\bulk_role_assignments_ended',
            'totara_program\event\program_unassigned',
            'totara_program\event\program_unassigned',
            'totara_program\event\program_deleted',
        ];
        /** @var \totara_program\event\program_deleted $deletionevent */
        $deletionevent = null;
        foreach ($events as $event) {
            $class = get_class($event);
            $key = array_search($class, $expectedevents);
            $this->assertNotFalse($key);
            // Remove it from the array, to reduce it to 0 hopefully.
            unset($expectedevents[$key]);
            if ($class === 'totara_program\event\program_deleted') {
                $deletionevent = $event;
            }
        }
        // Check we got all of the expected events.
        $this->assertCount(0, $expectedevents, 'The following events were not expected when deleting a program: '.join(', ', $expectedevents));
        // Verify the deletion event now.
        $this->assertNotNull($deletionevent);
        $this->assertInstanceOf('\totara_program\event\program_deleted', $deletionevent);
        $this->assertSame($certification->id, $deletionevent->objectid);
        $this->assertSame($USER->id, $deletionevent->userid);
        $this->assertSame($certificationcontext->id, $deletionevent->get_context()->id);

        // Now verify the database.
        $this->assertSame(0, $DB->count_records('prog', ['id' => $certification->id]));
        $this->assertSame(0, $DB->count_records('dp_plan_program_assign', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_assignment', ['programid' => $certification->id]));
        // $this->assertSame(0, $DB->count_records('prog_completion', ['programid' => $certification->id]));
        // $this->assertSame(0, $DB->count_records('prog_completion_history', ['programid' => $certification->id]));
        // $this->assertSame(0, $DB->count_records('prog_completion_log', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_courseset', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_exception', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_extension', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_future_user_assignment', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_info_data', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_message', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_recurrence', ['programid' => $certification->id]));
        $this->assertSame(0, $DB->count_records('prog_user_assignment', ['programid' => $certification->id]));

        // Finally verify the users are still complete in their courses.
        foreach ($courses_one as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course);
        }
        foreach ($courses_two as $course) {
            $this->verify_user_complete_in_course($user_complete, $course);
            $this->verify_user_complete_in_course($user_incomplete, $course, false);
        }
    }

    /**
     * Marks the user as complete in the course and assert it was done successfully.
     *
     * @param stdClass $user
     * @param stdClass $course
     */
    private function mark_user_complete_in_course($user, $course) {
        $params = array('userid' => $user->id, 'course' => $course->id);
        $completion = new completion_completion($params);
        $completion->mark_inprogress();
        $this->assertNotEmpty($completion->mark_complete());
        $this->assertTrue($completion->is_complete());
    }

    /**
     * Asserts the user is complete in the course.
     *
     * @param stdClass $user
     * @param stdClass $course
     * @param bool $expect_complete
     */
    private function verify_user_complete_in_course($user, $course, $expect_complete = true) {
        $params = array('userid' => $user->id, 'course' => $course->id);
        $completion = new completion_completion($params);
        if ($expect_complete) {
            $this->assertTrue($completion->is_complete());
        } else {
            $this->assertFalse($completion->is_complete());
        }
    }

    /**
     * Tests the static duration implode method.
     */
    public function test_duration_implode() {

        $hour = 60 * 60;
        $day = $hour * 24;
        $week = $day * 7;
        $month = $day * 30;
        $year = $day * 365;

        $this->assertSame(0, program_utilities::duration_implode(0, TIME_SELECTOR_HOURS));
        $this->assertSame(1 * $hour, program_utilities::duration_implode(1, TIME_SELECTOR_HOURS));
        $this->assertSame(17 * $hour, program_utilities::duration_implode(17, TIME_SELECTOR_HOURS));

        $this->assertSame(0, program_utilities::duration_implode(0, TIME_SELECTOR_DAYS));
        $this->assertSame(1 * $day, program_utilities::duration_implode(1, TIME_SELECTOR_DAYS));
        $this->assertSame(23 * $day, program_utilities::duration_implode(23, TIME_SELECTOR_DAYS));

        $this->assertSame(0, program_utilities::duration_implode(0, TIME_SELECTOR_WEEKS));
        $this->assertSame(1 * $week, program_utilities::duration_implode(1, TIME_SELECTOR_WEEKS));
        $this->assertSame(5 * $week, program_utilities::duration_implode(5, TIME_SELECTOR_WEEKS));

        $this->assertSame(0, program_utilities::duration_implode(0, TIME_SELECTOR_MONTHS));
        $this->assertSame(1 * $month, program_utilities::duration_implode(1, TIME_SELECTOR_MONTHS));
        $this->assertSame(51 * $month, program_utilities::duration_implode(51, TIME_SELECTOR_MONTHS));

        $this->assertSame(0, program_utilities::duration_implode(0, TIME_SELECTOR_YEARS));
        $this->assertSame(1 * $year, program_utilities::duration_implode(1, TIME_SELECTOR_YEARS));
        $this->assertSame(42 * $year, program_utilities::duration_implode(42, TIME_SELECTOR_YEARS));

        $this->assertSame(0, program_utilities::duration_implode(0, TIME_SELECTOR_NOMINIMUM));
        $this->assertSame(0, program_utilities::duration_implode(1, TIME_SELECTOR_NOMINIMUM));
        $this->assertSame(0, program_utilities::duration_implode(18, TIME_SELECTOR_NOMINIMUM));

        $this->assertSame(0, program_utilities::duration_implode(0, TIME_SELECTOR_INFINITY));
        $this->assertSame(0, program_utilities::duration_implode(1, TIME_SELECTOR_INFINITY));
        $this->assertSame(0, program_utilities::duration_implode(34, TIME_SELECTOR_INFINITY));
    }

    /**
     * Tests the static duration explode method.
     */
    public function test_duration_explode() {

        $hour = 60 * 60;
        $day = $hour * 24;
        $week = $day * 7;
        $month = $day * 30;
        $year = $day * 365;

        $result = program_utilities::duration_explode(0);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_INFINITY, $result->period);
        $this->assertEquals(0, $result->num);
        $this->assertEquals('no minimum time', $result->periodstr);

        $result = program_utilities::duration_explode(1);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(0, $result->period);
        $this->assertEquals(0, $result->num);
        $this->assertEquals('', $result->periodstr);

        $result = program_utilities::duration_explode($hour * 7);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_HOURS, $result->period);
        $this->assertEquals(7, $result->num);
        $this->assertEquals('hour(s)', $result->periodstr);

        $result = program_utilities::duration_explode($day * 3);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_DAYS, $result->period);
        $this->assertEquals(3, $result->num);
        $this->assertEquals('day(s)', $result->periodstr);

        $result = program_utilities::duration_explode($day * 7);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_WEEKS, $result->period);
        $this->assertEquals(1, $result->num);
        $this->assertEquals('week(s)', $result->periodstr);

        $result = program_utilities::duration_explode($day * 21);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_WEEKS, $result->period);
        $this->assertEquals(3, $result->num);
        $this->assertEquals('week(s)', $result->periodstr);

        $result = program_utilities::duration_explode($week * 3);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_WEEKS, $result->period);
        $this->assertEquals(3, $result->num);
        $this->assertEquals('week(s)', $result->periodstr);

        $result = program_utilities::duration_explode($week * 7);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_WEEKS, $result->period);
        $this->assertEquals(7, $result->num);
        $this->assertEquals('week(s)', $result->periodstr);

        $result = program_utilities::duration_explode($month * 7);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_MONTHS, $result->period);
        $this->assertEquals(7, $result->num);
        $this->assertEquals('month(s)', $result->periodstr);

        $result = program_utilities::duration_explode($month * 18);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_MONTHS, $result->period);
        $this->assertEquals(18, $result->num);
        $this->assertEquals('month(s)', $result->periodstr);

        $result = program_utilities::duration_explode($month * 24);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_MONTHS, $result->period);
        $this->assertEquals(24, $result->num);
        $this->assertEquals('month(s)', $result->periodstr);

        $result = program_utilities::duration_explode($year * 7);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals(TIME_SELECTOR_YEARS, $result->period);
        $this->assertEquals(7, $result->num);
        $this->assertEquals('year(s)', $result->periodstr);

    }

    /**
     * Test the get_standard_time_allowance_options static method.
     */
    public function test_get_standard_time_allowance_options() {

        $options_limited = program_utilities::get_standard_time_allowance_options();
        $options_all = program_utilities::get_standard_time_allowance_options(true);

        $this->assertInternalType('array', $options_limited);
        $this->assertInternalType('array', $options_all);

        $this->assertCount(4, $options_limited);
        $this->assertCount(5, $options_all);

        $expected = array(
            TIME_SELECTOR_DAYS => 'Day(s)',
            TIME_SELECTOR_WEEKS => 'Week(s)',
            TIME_SELECTOR_MONTHS => 'Month(s)',
            TIME_SELECTOR_YEARS => 'Year(s)',
        );
        $this->assertSame($expected, $options_limited);
        $expected[TIME_SELECTOR_NOMINIMUM] = 'No minimum time';
        $this->assertSame($expected, $options_all);

    }

    /**
     * Tests the print duration selector.
     *
     * This function doesn't test what is output, its just testing the code is executable and that it returns a string.
     */
    public function test_print_duration_selector() {

        $html = program_utilities::print_duration_selector('t_', 'name_test', TIME_SELECTOR_WEEKS, 'number_test', 7);
        $this->assertInternalType('string', $html);
        $this->assertSame(1, preg_match('/name=([\'"])t_name_test\1/', $html));
        $this->assertSame(1, preg_match('/name=([\'"])t_number_test\1/', $html));
        $this->assertSame(1, preg_match('/value=([\'"])7\1/', $html));

    }
}
