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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package enrol_paypal
 */

namespace enrol_paypal\userdata;

use advanced_testcase;
use context_course;
use context_coursecat;
use context_helper;
use context_system;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of PayPal transaction information
 *
 * @group totara_userdata
 */
class enrol_paypal_userdata_transaction_information_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
        $actualcontextlevels = transaction_information::get_compatible_context_levels();
        sort($actualcontextlevels);
        $this->assertEquals($expectedcontextlevels, $actualcontextlevels);
    }

    /**
     * Testing abilities, is_purgeable|countable|exportable()
     */
    public function test_abilities() {
        $this->assertTrue(transaction_information::is_countable());
        $this->assertTrue(transaction_information::is_exportable());
        $this->assertTrue(transaction_information::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(transaction_information::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(transaction_information::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Create fixtures for our tests.
     */
    private function create_fixtures() {
        $this->resetAfterTest(true);

        $fixtures = new class() {
            /** @var target_user */
            public $user, $controluser;
            /** @var \stdClass */
            public $category1, $category2;
            /** @var \stdClass */
            public $course1, $course2, $course3;
            /** @var \stdClass */
            public $transaction1, $transaction2, $transaction3;
        };

        $fixtures->category1 = $this->getDataGenerator()->create_category();
        $fixtures->category2 = $this->getDataGenerator()->create_category();
        $fixtures->course1 = $this->getDataGenerator()->create_course(['category' => $fixtures->category1->id]);
        $fixtures->course2 = $this->getDataGenerator()->create_course(['category' => $fixtures->category2->id]);
        $fixtures->course3 = $this->getDataGenerator()->create_course(['category' => $fixtures->category2->id]);

        $fixtures->user = new target_user($this->getDataGenerator()->create_user(['username' => 'user1']));
        $fixtures->controluser = new target_user($this->getDataGenerator()->create_user(['username' => 'controluser']));

        $fixtures->transaction1 = $this->create_transaction($fixtures->user, $fixtures->course1->id);
        $fixtures->transaction2 = $this->create_transaction($fixtures->user, $fixtures->course2->id);
        $fixtures->transaction3 = $this->create_transaction($fixtures->user, $fixtures->course3->id);

        $this->create_transaction($fixtures->controluser, $fixtures->course1->id);
        $this->create_transaction($fixtures->controluser, $fixtures->course2->id);
        $this->create_transaction($fixtures->controluser, $fixtures->course3->id);

        return $fixtures;
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = transaction_information::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('enrol_paypal', ['userid' => $fixtures->user->id]));

        // Control user must not be affected.
        $this->assertEquals(3, $DB->count_records('enrol_paypal', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context_suspended_user() {
        global $DB;

        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->suspend_user($fixtures->user->id));

        // Purge active user.
        $result = transaction_information::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('enrol_paypal', ['userid' => $fixtures->user->id]));

        // Control user must not be affected.
        $this->assertEquals(3, $DB->count_records('enrol_paypal', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context_deleted_user() {
        global $DB;

        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->delete_user($fixtures->user->id));

        // Purge active user.
        $result = transaction_information::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('enrol_paypal', ['userid' => $fixtures->user->id]));

        // Control user must not be affected.
        $this->assertEquals(3, $DB->count_records('enrol_paypal', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_coursecat_context1() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = transaction_information::execute_purge($fixtures->user, context_coursecat::instance($fixtures->category1->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction1->id]));
        $this->assertNotEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction2->id]));
        $this->assertNotEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction3->id]));

        // Control user must not be affected.
        $this->assertEquals(3, $DB->count_records('enrol_paypal', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_coursecat_context2() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = transaction_information::execute_purge($fixtures->user, context_coursecat::instance($fixtures->category2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction1->id]));
        $this->assertEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction2->id]));
        $this->assertEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction3->id]));

        // Control user must not be affected.
        $this->assertEquals(3, $DB->count_records('enrol_paypal', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_course_context1() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = transaction_information::execute_purge($fixtures->user, context_course::instance($fixtures->course2->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction1->id]));
        $this->assertEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction2->id]));
        $this->assertNotEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction3->id]));

        // Control user must not be affected.
        $this->assertEquals(3, $DB->count_records('enrol_paypal', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_course_context2() {
        global $DB;

        $fixtures = $this->create_fixtures();

        // Purge active user.
        $result = transaction_information::execute_purge($fixtures->user, context_course::instance($fixtures->course3->id));
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertNotEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction1->id]));
        $this->assertNotEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction2->id]));
        $this->assertEmpty($DB->get_records('enrol_paypal', ['id' => $fixtures->transaction3->id]));

        // Control user must not be affected.
        $this->assertEquals(3, $DB->count_records('enrol_paypal', ['userid' => $fixtures->controluser->id]));
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $fixtures = $this->create_fixtures();

        // Do the count.
        $result = transaction_information::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(3, $result);

        $result = transaction_information::execute_count($fixtures->user, context_coursecat::instance($fixtures->category1->id));
        $this->assertEquals(1, $result);

        $result = transaction_information::execute_count($fixtures->user, context_coursecat::instance($fixtures->category2->id));
        $this->assertEquals(2, $result);

        $result = transaction_information::execute_count($fixtures->user, context_course::instance($fixtures->course1->id));
        $this->assertEquals(1, $result);

        $result = transaction_information::execute_count($fixtures->user, context_course::instance($fixtures->course2->id));
        $this->assertEquals(1, $result);

        $result = transaction_information::execute_count($fixtures->user, context_course::instance($fixtures->course3->id));
        $this->assertEquals(1, $result);

        // Purge data.
        transaction_information::execute_purge($fixtures->user, context_system::instance());

        $result = transaction_information::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $fixtures = $this->create_fixtures();

        // Export data.
        $result = transaction_information::execute_export($fixtures->user, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data);

        $ids = array_column($result->data, 'id');
        $this->assertContains($fixtures->transaction1->id, $ids);
        $this->assertContains($fixtures->transaction2->id, $ids);
        $this->assertContains($fixtures->transaction3->id, $ids);

        foreach ($result->data as $transaction) {
            $this->assertCount(15, array_keys($transaction));
            $this->assertArrayHasKey('id', $transaction);
            $this->assertArrayHasKey('courseid', $transaction);
            $this->assertArrayHasKey('instanceid', $transaction);
            $this->assertArrayHasKey('item_name', $transaction);
            $this->assertArrayHasKey('option_name1', $transaction);
            $this->assertArrayHasKey('option_selection1_x', $transaction);
            $this->assertArrayHasKey('option_name2', $transaction);
            $this->assertArrayHasKey('option_selection2_x', $transaction);
            $this->assertArrayHasKey('payment_status', $transaction);
            $this->assertArrayHasKey('payment_type', $transaction);
            $this->assertArrayHasKey('pending_reason', $transaction);
            $this->assertArrayHasKey('reason_code', $transaction);
            $this->assertArrayHasKey('tax', $transaction);
            $this->assertArrayHasKey('timeupdated', $transaction);
            $this->assertArrayHasKey('txn_id', $transaction);
        }

        $result = transaction_information::execute_export($fixtures->user, context_coursecat::instance($fixtures->category1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $ids = array_column($result->data, 'id');
        $this->assertContains($fixtures->transaction1->id, $ids);

        $result = transaction_information::execute_export($fixtures->user, context_coursecat::instance($fixtures->category2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $ids = array_column($result->data, 'id');
        $this->assertContains($fixtures->transaction2->id, $ids);
        $this->assertContains($fixtures->transaction3->id, $ids);

        $result = transaction_information::execute_export($fixtures->user, context_course::instance($fixtures->course1->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $ids = array_column($result->data, 'id');
        $this->assertContains($fixtures->transaction1->id, $ids);

        $result = transaction_information::execute_export($fixtures->user, context_course::instance($fixtures->course2->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $ids = array_column($result->data, 'id');
        $this->assertContains($fixtures->transaction2->id, $ids);

        $result = transaction_information::execute_export($fixtures->user, context_course::instance($fixtures->course3->id));
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $ids = array_column($result->data, 'id');
        $this->assertContains($fixtures->transaction3->id, $ids);
    }


    /**
     * @param target_user $user
     * @param int $courseid
     * @return \stdClass
     */
    private function create_transaction(target_user $user, int $courseid): \stdClass {
        global $DB;

        $transactiondata = [
            'receiver_email' => 'test@example.com',
            'courseid' => $courseid,
            'userid' => $user->id,
            'memo' => random_string(15),
            'timeupdated' => time()
        ];

        $transactionid = $DB->insert_record('enrol_paypal', (object)$transactiondata);

        return $DB->get_record('enrol_paypal', ['id' => $transactionid]);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function suspend_user(int $userid): \stdClass {
        global $DB;
        // Note that we don't properly delete the user, in fact we just simulate it.
        $DB->set_field('user', 'suspended', '1', ['id' => $userid]);
        return $DB->get_record('user', ['id' => $userid]);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function delete_user(int $userid): \stdClass {
        global $DB;
        // Note that we don't properly delete the user, in fact we just simulate it.
        $DB->set_field('user', 'deleted', '1', ['id' => $userid]);
        context_helper::delete_instance(CONTEXT_USER, $userid);
        return $DB->get_record('user', ['id' => $userid]);
    }

}