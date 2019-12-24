<?php
/*
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralms.com>
 * @package totara_dashboard
 */

use totara_dashboard\userdata\totara_dashboard as totara_dashboard_item;
use totara_userdata\userdata\target_user;

/**
 * tests the {@see totara_dashboard\userdata\totara_dashboard} class
 */
class totara_dashboard_item_testcase extends advanced_testcase {

    /**
     * Setup the data for the tests.
     */
    private function get_data() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');
        $this->resetAfterTest();
        $data = new class() {
            /** @var target_user */
            public $user1, $user2;
            /** @var array */
            public $testdashboards;
            /** @var context_system */
            public $systemcontext;
        };
        $data->systemcontext = context_system::instance();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $data->user1 = new target_user($user1);
        $data->user2 = new target_user($user2);

        $dashboard = new totara_dashboard();
        $dashboarddata = ['name' => 'test1', 'locked' => 1, 'published' => COHORT_VISIBLE_ALL];
        $dashboard->set_from_form((object)$dashboarddata)->save();
        $id1 = $dashboard->get_id();
        $data->testdashboards[] = new totara_dashboard($id1);

        $dashboard = new totara_dashboard();
        $dashboarddata['name'] = 'test2';
        $dashboard->set_from_form((object)$dashboarddata)->save();
        $id2 = $dashboard->get_id();
        $data->testdashboards[] = new totara_dashboard($id2);
        return $data;
    }

    /**
     * Tests that after a purge the count method returns 0.
     */
    public function test_dashboard_item_purge_makes_count_zero() {
        global $DB;
        $data = $this->get_data();

        $this->assertEquals(0, totara_dashboard_item::execute_count($data->user1, $data->systemcontext));
        foreach ($data->testdashboards as $index => $dashboard) {
            $dashboard->user_copy($data->user1->id);
            $this->assertEquals($index + 1, totara_dashboard_item::execute_count($data->user1, $data->systemcontext));
        }
        $result = totara_dashboard_item::execute_purge($data->user1, $data->systemcontext);
        $this->assertEquals(totara_dashboard_item::RESULT_STATUS_SUCCESS, $result);

        $currentcount = totara_dashboard_item::execute_count($data->user1, $data->systemcontext);
        $this->assertEquals(0, $currentcount);

        $dbcount = $DB->count_records('totara_dashboard_user', ['userid' => $data->user1->id]);
        $this->assertEquals(0, $dbcount);
    }

    /**
     * Tests that after a purge the count method returns 0.
     */
    public function test_dashboard_item_purge_doesnt_effect_other_dashboards() {
        global $DB;
        $data = $this->get_data();

        $dashboardsbefore = totara_dashboard::get_user_dashboards($data->user1->id);

        foreach ($data->testdashboards as $index => $dashboard) {
            $dashboard->user_copy($data->user1->id);
            $dashboard->user_copy($data->user2->id);
        }
        $user2dashboardcount = totara_dashboard_item::execute_count($data->user2, $data->systemcontext);

        $result = totara_dashboard_item::execute_purge($data->user1, $data->systemcontext);
        $this->assertEquals(totara_dashboard_item::RESULT_STATUS_SUCCESS, $result);

        $dbcount = $DB->count_records('totara_dashboard_user', ['userid' => $data->user1->id]);
        $this->assertEquals(0, $dbcount);

        $user2count = totara_dashboard_item::execute_count($data->user2, $data->systemcontext);
        $this->assertEquals($user2dashboardcount, $user2count);

        // Make sure the purge doesnt effect system dashboards.
        $user1dasboard = totara_dashboard::get_user_dashboards($data->user1->id);
        $this->assertEquals($dashboardsbefore, $user1dasboard);
    }

    /**
     * Tests that the export method returns the expected data
     */
    public function test_dashboard_item_export() {
        $data = $this->get_data();

        foreach ($data->testdashboards as $index => $dashboard) {
            $dashboard->user_copy($data->user1->id);
        }

        $export = totara_dashboard_item::execute_export($data->user1, $data->systemcontext);

        $dashboard1 = new stdClass();
        $dashboard1->id = $data->testdashboards[0]->get_id();
        $dashboard1->name = $data->testdashboards[0]->name;
        $dashboard2 = new stdClass();
        $dashboard2->id = $data->testdashboards[1]->get_id();
        $dashboard2->name = $data->testdashboards[1]->name;
        $exportdata = [];
        $exportdata[$dashboard1->id] = $dashboard1;
        $exportdata[$dashboard2->id] = $dashboard2;

        $this->assertEquals($exportdata, $export->data);

        $otheruser = $this->getDataGenerator()->create_user();
        $otherusertarget = new target_user($otheruser, context_user::instance($otheruser->id)->id);
        $export = totara_dashboard_item::execute_export($otherusertarget, $data->systemcontext);
        $this->assertEquals([], $export->data);
    }

    /**
     * Tests that purge removes the data even when the user is deleted.
     */
    public function test_dashboard_item_purge_works_on_deleted_user() {
        global $DB;
        $data = $this->get_data();

        foreach ($data->testdashboards as $index => $dashboard) {
            $dashboard->user_copy($data->user1->id);
        }

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $user->id]);
        $reloadeduser = new target_user($user);

        $this->assertNotEquals(
            0,
            totara_dashboard_item::execute_count($reloadeduser, $data->systemcontext)
        );
        $this->assertEquals(
            totara_dashboard_item::RESULT_STATUS_SUCCESS,
            totara_dashboard_item::execute_purge($reloadeduser, $data->systemcontext)
        );
        $this->assertEquals(
            0,
            totara_dashboard_item::execute_count($reloadeduser, $data->systemcontext)
        );
    }

    /**
     * Tests the count is the same before and after deleting a user.
     */
    public function test_dashboard_item_count_works_on_deleted_user() {
        global $DB;
        $data = $this->get_data();

        foreach ($data->testdashboards as $index => $dashboard) {
            $dashboard->user_copy($data->user1->id);
        }

        $countbefore = totara_dashboard_item::execute_count($data->user1, $data->systemcontext);

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $user->id]);
        $reloadeduser = new target_user($user);

        $currentcount = totara_dashboard_item::execute_count($reloadeduser, $data->systemcontext);
        $this->assertEquals($countbefore, $currentcount);
    }

    private function get_audience_data() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');
        $this->resetAfterTest();
        $data = new class() {
            /** @var stdClass */
            public $user;
            /** @var target_user */
            public $usertarget;
            /** @var totara_dashboard */
            public $dashboard;
            /** @var stdClass */
            public $cohort;
            /** @var context_system */
            public $systemcontext;
        };
        $data->systemcontext = context_system::instance();
        $data->user = $this->getDataGenerator()->create_user();
        $data->usertarget = new target_user($data->user);

        /** @var totara_cohort_generator $cohort_generator */
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        /** @var totara_dashboard_generator $dashboard_generator */
        $dashboard_generator = $this->getDataGenerator()->get_plugin_generator('totara_dashboard');

        $data->cohort = $this->getDataGenerator()->create_cohort();

        $cohort_generator->cohort_assign_users($data->cohort->id, [$data->user->id]);

        $data->dashboard = $dashboard_generator->create_dashboard([
            'name' => 'test1',
            'locked' => 0,
            'published' => COHORT_VISIBLE_AUDIENCE,
            'cohorts' => [$data->cohort->id]
        ]);
        $data->dashboard->user_copy($data->user->id);
        return $data;
    }

    public function test_with_audience_visibility() {
        $data = $this->get_audience_data();

        $countbefore = totara_dashboard_item::execute_count($data->usertarget, $data->systemcontext);
        $this->assertNotEquals(0, $countbefore);

        cohort_remove_member($data->cohort->id, $data->user->id);

        $currentcount = totara_dashboard_item::execute_count($data->usertarget, $data->systemcontext);
        $this->assertEquals($countbefore, $currentcount);

        $export = totara_dashboard_item::execute_export($data->usertarget, $data->systemcontext);
        $this->assertEquals($countbefore, count($export->data));

        $result = totara_dashboard_item::execute_purge($data->usertarget, $data->systemcontext);
        $this->assertEquals(totara_dashboard_item::RESULT_STATUS_SUCCESS, $result);

        $currentcount = totara_dashboard_item::execute_count($data->usertarget, $data->systemcontext);
        $this->assertEquals(0, $currentcount);

        $export = totara_dashboard_item::execute_export($data->usertarget, $data->systemcontext);
        $this->assertEquals(0, count($export->data));
    }

    public function test_with_dashboards_disabled() {
        global $CFG;
        $data = $this->get_data();

        foreach ($data->testdashboards as $index => $dashboard) {
            $dashboard->user_copy($data->user1->id);
        }
        $countbefore = totara_dashboard_item::execute_count($data->user1, $data->systemcontext);

        // Disable dashboards.
        $CFG->enabletotaradashboard = TOTARA_DISABLEFEATURE;

        $currentcount = totara_dashboard_item::execute_count($data->user1, $data->systemcontext);
        $this->assertEquals($countbefore, $currentcount);

        $export = totara_dashboard_item::execute_export($data->user1, $data->systemcontext);
        $this->assertEquals($countbefore, count($export->data));

        $result = totara_dashboard_item::execute_purge($data->user1, $data->systemcontext);
        $this->assertEquals(totara_dashboard_item::RESULT_STATUS_SUCCESS, $result);

        $currentcount = totara_dashboard_item::execute_count($data->user1, $data->systemcontext);
        $this->assertEquals(0, $currentcount);

        $export = totara_dashboard_item::execute_export($data->user1, $data->systemcontext);
        $this->assertEquals(0, count($export->data));
    }
}