<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_my
 */

use core_my\userdata\profile_customisations;
use totara_userdata\userdata\target_user;

/**
 * Test the {@see \core_my\userdata\profile_customisations} class
 */
class core_my_userdata_profile_customisations_test extends advanced_testcase {

    /**
     * Sets up the data for the tests.
     */
    private function get_data() {
        global $CFG;
        require_once($CFG->dirroot . '/my/lib.php');
        $this->resetAfterTest();
        $data = new class() {
            /** @var target_user */
            public $user1, $user2;
            /** @var context_system */
            public $systemcontext;
        };
        $data->systemcontext = context_system::instance();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data->user1 = new target_user($user1);
        $data->user2 = new target_user($user2);

        my_copy_page($user1->id, MY_PAGE_PUBLIC, 'user-profile');
        return $data;
    }

    /**
     * Checks that the Dashboards get removed and checks that the.
     * Also checks that the other users dashboards are not deleted.
     */
    public function test_purge_resets_profile_dashboard() {
        global $DB;
        $data = $this->get_data();

        my_copy_page($data->user2->id, MY_PAGE_PUBLIC, 'user-profile');

        $user2dashboardsbefore = profile_customisations::execute_count($data->user2, $data->systemcontext);

        profile_customisations::execute_purge($data->user1, $data->systemcontext);

        $this->assertEquals(
            0,
            $DB->count_records('my_pages', ['userid' => $data->user1->id])
        );
        $this->assertEquals(
            $user2dashboardsbefore,
            profile_customisations::execute_count($data->user2, $data->systemcontext)
        );
    }

    /**
     * Makes sure that the count is 0 after purging.
     */
    public function test_count_zero_after_purge() {
        $data = $this->get_data();

        $this->assertEquals(
            1,
            profile_customisations::execute_count($data->user1, $data->systemcontext)
        );

        profile_customisations::execute_purge($data->user1, $data->systemcontext);

        $this->assertEquals(
            0,
            profile_customisations::execute_count($data->user1, $data->systemcontext)
        );
    }

    /**
     * Makes sure that purging still works when the user
     * has being deleted by setting the deleted flag and deleting context.
     */
    public function test_purge_works_on_deleted_user() {
        global $DB;
        $data = $this->get_data();

        my_copy_page($data->user2->id, MY_PAGE_PUBLIC, 'user-profile');

        $this->getDataGenerator()->create_block('totara_featured_links', [
            'parentcontextid' => $data->user1->contextid,
            'pagetypepattern' => 'user-profile'
        ]);

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        $user->deleted = 1;
        context_helper::delete_instance(CONTEXT_USER, $user->id);
        $DB->update_record('user', $user);
        $deleteduser = new target_user($user);

        $this->assertEquals(
            profile_customisations::RESULT_STATUS_SUCCESS,
            profile_customisations::execute_purge($deleteduser, $data->systemcontext)
        );
        $this->assertEquals(
            0,
            profile_customisations::execute_count($deleteduser, $data->systemcontext)
        );
        $this->assertEquals(
            0,
            $DB->count_records('block_instances', ['parentcontextid' => $deleteduser->contextid])
        );
        $this->assertEquals(
            1,
            profile_customisations::execute_count($data->user2, $data->systemcontext)
        );
    }

    /**
     * Makes sure that purging still works when the user
     * has being deleted via the {@see delete_user()} function.
     */
    public function test_purge_works_on_fully_deleted_user() {
        global $DB;
        $data = $this->get_data();

        my_copy_page($data->user2->id, MY_PAGE_PUBLIC, 'user-profile');

        $this->getDataGenerator()->create_block('totara_featured_links', [
            'parentcontextid' => $data->user1->contextid,
            'pagetypepattern' => 'user-profile'
        ]);

        $user = $DB->get_record('user', ['id' => $data->user2->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $user->id]);
        $deleteduser = new target_user($user);

        $this->assertEquals(
            profile_customisations::RESULT_STATUS_SUCCESS,
            profile_customisations::execute_purge($deleteduser, $data->systemcontext)
        );
        $this->assertEquals(
            0,
            profile_customisations::execute_count($deleteduser, $data->systemcontext)
        );
        $this->assertEquals(
            0,
            $DB->count_records('block_instances', ['parentcontextid' => $deleteduser->contextid])
        );
        $this->assertEquals(
            1,
            profile_customisations::execute_count($data->user1, $data->systemcontext)
        );
    }

    /**
     * Tests that the purge and count don't fail when the user does not have any data.
     */
    public function test_purge_works_on_user_with_no_data() {
        global $DB;
        $data = $this->get_data();

        $this->assertEquals(
            0,
            profile_customisations::execute_count($data->user2, $data->systemcontext)
        );

        $this->assertEquals(
            profile_customisations::RESULT_STATUS_SUCCESS,
            profile_customisations::execute_purge($data->user2, $data->systemcontext)
        );

        $user = $DB->get_record('user', ['id' => $data->user2->id]);
        $user->deleted = 1;
        context_helper::delete_instance(CONTEXT_USER, $user->id);
        $DB->update_record('user', $user);
        $deleteduser = new target_user($user);

        $this->assertEquals(
            0,
            profile_customisations::execute_count($deleteduser, $data->systemcontext)
        );

        $this->assertEquals(
            profile_customisations::RESULT_STATUS_SUCCESS,
            profile_customisations::execute_purge($deleteduser, $data->systemcontext)
        );
    }

    /**
     * Makes sure that count still works when a user has being deleted.
     */
    public function test_count_works_on_deleted_user() {
        global $DB;
        $data = $this->get_data();

        $countbefore = profile_customisations::execute_count($data->user1, $data->systemcontext);

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        $user->deleted = 1;
        context_helper::delete_instance(CONTEXT_USER, $user->id);
        $DB->update_record('user', $user);
        $deleteduser = new target_user($user);

        $this->assertEquals(
            $countbefore,
            profile_customisations::execute_count($deleteduser, $data->systemcontext)
        );

        $nocontextuser = new target_user($user);

        $this->assertEquals(
            $countbefore,
            profile_customisations::execute_count($nocontextuser, $data->systemcontext)
        );
    }

    /**
     * Tests that count returns 1 when the user has customised their profile page and 0 when they havent.
     */
    public function test_count_returns_expected_amount() {
        $data = $this->get_data();

        $this->assertEquals(
            1,
            profile_customisations::execute_count($data->user1, $data->systemcontext)
        );
        $this->assertEquals(
            0,
            profile_customisations::execute_count($data->user2, $data->systemcontext)
        );
        my_copy_page($data->user2->id, MY_PAGE_PUBLIC, 'user-profile');
        $this->assertEquals(
            1,
            profile_customisations::execute_count($data->user2, $data->systemcontext)
        );
    }
}
