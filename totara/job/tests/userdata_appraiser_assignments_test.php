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
 * @package totara_job
 */

use totara_job\job_assignment;
use totara_job\userdata\appraiser_assignments;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_job_userdata_appraiser_assignments_testcase totara/job/tests/userdata_appraiser_assignments_test.php
 *
 * @group totara_userdata
 */
class totara_job_userdata_appraiser_assignments_testcase extends \advanced_testcase {

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(array(CONTEXT_SYSTEM), appraiser_assignments::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(appraiser_assignments::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(appraiser_assignments::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(appraiser_assignments::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(appraiser_assignments::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(appraiser_assignments::is_countable());
    }

    /**
     * Set up data that'll be purged.
     */
    private function setup_data() {
        $data = new class() {
            /** @var \stdClass */
            public $user1, $user2, $user3, $user4, $user5;

            /** @var target_user */
            public $targetuser;
        };

        $this->resetAfterTest(true);

        // Set up users.
        $data->user1 = $this->getDataGenerator()->create_user(); // One appraised by user2, one by user3.
        $data->user2 = $this->getDataGenerator()->create_user(); // Appraised by user2.
        $data->user3 = $this->getDataGenerator()->create_user(); // Target user! One appraised by user4, one by user5, one with no appraiser.
        $data->user4 = $this->getDataGenerator()->create_user(); // Appraised by user5.
        $data->user5 = $this->getDataGenerator()->create_user(); // Appraiser.

        // Set up some management hierarchies.
        job_assignment::create_default($data->user5->id);
        job_assignment::create_default($data->user4->id, array('appraiserid' => $data->user5->id));
        job_assignment::create_default($data->user3->id, array('appraiserid' => $data->user4->id));
        job_assignment::create_default($data->user3->id, array('appraiserid' => $data->user5->id));
        job_assignment::create_default($data->user3->id);
        job_assignment::create_default($data->user2->id, array('appraiserid' => $data->user3->id));
        job_assignment::create_default($data->user1->id, array('appraiserid' => $data->user2->id));
        job_assignment::create_default($data->user1->id, array('appraiserid' => $data->user3->id));

        // Set up the target user.
        $data->targetuser = new target_user($data->user3);

        return $data;
    }

    /**
     * Test the purge function. Make sure that the control data is not affected.
     */
    public function test_purge() {
        global $DB;

        $data = $this->setup_data();

        // Get the expected data, by modifying the actual data. We only need the list and affected field, not the details.
        $expectedjas = $DB->get_records('job_assignment', array(), 'id', 'id, userid, appraiserid');
        foreach ($expectedjas as $key => $expectedja) {
            if ($expectedja->appraiserid == $data->targetuser->id) {
                $expectedja->appraiserid = null;
            }
        }

        // Execute the purge.
        $status = appraiser_assignments::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check the results.
        $this->assertEquals($expectedjas, $DB->get_records('job_assignment', array(), 'id', 'id, userid, appraiserid'));
    }

    /**
     * Test the export function. Make sure that the control data is not exported.
     */
    public function test_export() {
        global $DB;

        $data = $this->setup_data();

        // Get the expected data.
        $expecteddata = array();
        $records = $DB->get_records('job_assignment', array('appraiserid' => $data->targetuser->id), 'id', 'id, userid');
        foreach ($records as $record) {
            $expecteddata[] = $record->userid;
        }

        // Execute the export.
        $result = appraiser_assignments::execute_export($data->targetuser, context_system::instance());

        // Check the results.
        $this->assertCount(0, $result->files);
        $this->assertCount(2, $result->data);

        $this->assertEquals($expecteddata, $result->data);
    }

    /**
     * Test the count function.
     */
    public function test_count() {
        $data = $this->setup_data();

        $this->assertEquals(2, appraiser_assignments::execute_count($data->targetuser, context_system::instance()));
        appraiser_assignments::execute_purge($data->targetuser, context_system::instance());
        $this->assertEquals(0, appraiser_assignments::execute_count($data->targetuser, context_system::instance()));
    }
}