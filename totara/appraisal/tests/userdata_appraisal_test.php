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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

use \totara_appraisal\userdata\appraisal;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

/**
 * @group totara_userdata
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_appraisal_userdata_appraisal_testcase totara/appraisal/tests/userdata_appraisal_test.php
 */
class totara_appraisal_userdata_appraisal_testcase extends appraisal_testcase {

    /**
     * Set up the test data.
     */
    private function setup_data() {
        global $DB;

        $data = new class() {
            /** @var stdClass */
            public $user1, $user2;

            /** @var array */
            public $appraisals = [];

            /** @var target_user */
            public $targetuser;

            /** @var file_storage */
            public $fs;
        };

        $this->resetAfterTest();

        // Set up users.
        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        // Set up the target user.
        $data->targetuser = new target_user($data->user1);

        $data->fs = get_file_storage();

        // Give them relevant data.
        /* @var $appraisal \appraisal */
        list($appraisal) = $this->prepare_appraisal_with_users(array(), array($data->user1, $data->user2));
        $appraisal->validate();
        $appraisal->activate();
        $data->appraisals[] = $appraisal;
        list($appraisal) = $this->prepare_appraisal_with_users(array(), array($data->user1, $data->user2));
        $appraisal->validate();
        $appraisal->activate();
        $data->appraisals[] = $appraisal;

        $file = make_request_directory() . '/appraisal_snapshot.pdf';
        file_put_contents($file, "This is some appraisal content");

        foreach ($data->appraisals as $appraisal) {
            $userassignment = $DB->get_record('appraisal_user_assignment', array('appraisalid' => $appraisal->id, 'userid' => $data->targetuser->id));
            $roleassignments = $DB->get_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $userassignment->id));
            foreach ($roleassignments as $roleassignment) {
                $appraisal->save_snapshot($file, $roleassignment->id);
            }
        }

        return $data;
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(array(CONTEXT_SYSTEM), appraisal::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(appraisal::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(appraisal::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(appraisal::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertFalse(appraisal::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(appraisal::is_countable());
    }

    /**
     * Test the purge function. Make sure that the control data is not affected.
     */
    public function test_purge() {
        global $DB;

        $data = $this->setup_data();

        // Get the expected data.
        $deleteduserassignmentids = [];
        $expecteduserassignments = $DB->get_records('appraisal_user_assignment', array(), 'id');
        $this->assertCount(4, $expecteduserassignments);
        foreach ($expecteduserassignments as $key => $assignment) {
            if ($assignment->userid == $data->targetuser->id) {
                $deleteduserassignmentids[] = $assignment->id;
                unset ($expecteduserassignments[$key]);
            }
        }

        // Save some data for later.
        list($insql, $inparams) = $DB->get_in_or_equal($deleteduserassignmentids);
        $sql = "SELECT ara.*, aua.appraisalid
                  FROM {appraisal_role_assignment} ara
                  JOIN {appraisal_user_assignment} aua ON ara.appraisaluserassignmentid = aua.id
                 WHERE ara.appraisaluserassignmentid " . $insql;
        $roleassignments = $DB->get_records_sql($sql, $inparams);

        // Execute the purge.
        $status = appraisal::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check the results. We assume that related data has been removed - that should be tested by delete_learner_assignments tests.
        // Even if the related data wasn't deleted, there shouldn't be anything left to identify the user that it belongs to.
        $this->assertEquals($expecteduserassignments, $DB->get_records('appraisal_user_assignment', array(), 'id'));

        // Check that files have been removed, because we don't even trust the other tests to be working/implemented.
        $systemcontext = context_system::instance();

        foreach ($roleassignments as $roleassignment) {
            $files = $data->fs->get_area_files($systemcontext->id, 'totara_appraisal', 'snapshot_' . $roleassignment->appraisalid, $roleassignment->id, '', false);
            $this->assertCount(0, $files);
        }
    }

    /**
     * Test the count function.
     */
    public function test_count() {
        $data = $this->setup_data();

        $this->assertEquals(2, appraisal::execute_count($data->targetuser, context_system::instance()));

        // Execute the purge.
        $status = appraisal::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        $this->assertEquals(0, appraisal::execute_count($data->targetuser, context_system::instance()));
    }
}