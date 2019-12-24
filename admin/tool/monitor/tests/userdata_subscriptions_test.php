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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_monitor
 */


use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose tool_monitor_userdata_subscriptions_testcase admin/tool/monitor/tests/userdata_subscriptions_test.php
 *
 * @group totara_userdata
 */
class tool_monitor_userdata_subscriptions_testcase extends \advanced_testcase {

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals([CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE], \tool_monitor\userdata\subscriptions::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(\tool_monitor\userdata\subscriptions::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(\tool_monitor\userdata\subscriptions::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(\tool_monitor\userdata\subscriptions::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(\tool_monitor\userdata\subscriptions::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(\tool_monitor\userdata\subscriptions::is_countable());
    }

    /**
     * Set up data that'll be purged, exported or counted.
     */
    private function setup_data() {
        $data = new class() {
            /** @var \stdClass */
            public $user1, $user2, $user3;

            /** @var \stdClass */
            public $course1;

            /** @var \stdClass */
            public $rule1, $rule2, $rule3;

            /** @var array */
            public $rules;
        };

        $this->resetAfterTest(true);

        // Create users.
        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();
        $data->user3 = $this->getDataGenerator()->create_user();

        // Create a course.
        $data->course1 = $this->getDataGenerator()->create_course(['shortname' => 'A test course']);

        $data->rules = [];

        // Create few rules.
        $monitorgenerator = $this->getDataGenerator()->get_plugin_generator('tool_monitor');
        $record = [
            'userid' => $data->user3->id,
            'courseid' => 0,
            'name' => 'Test Site Position Hierarchy event',
            'description' => 'Description for Test Site Position Hierarchy event',
            'frequency' => 3,
            'minutes' => 5,
            'plugin' => 'hierarchy_position',
            'eventname' => '\hierarchy_position\event\framework_created'
        ];
        $data->rule1 = $monitorgenerator->create_rule($record);
        $data->rules[$data->rule1->id] = $data->rule1;

        $record = [
            'userid' => $data->user3->id,
            'courseid' =>  $data->course1->id,
            'name' => 'Test Course specific event',
            'description' => 'Description for Test Course specific event',
            'frequency' => 10,
            'minutes' => 2,
            'plugin' => 'mod_choice',
            'eventname' => '\mod_choice\event\answer_deleted'
        ];
        $data->rule2 = $monitorgenerator->create_rule($record);
        $data->rules[$data->rule2->id] = $data->rule2;

        // Not doing much for specific data for rule 3 - just a control event
        $data->rule3 = $monitorgenerator->create_rule();
        $data->rules[$data->rule3->id] = $data->rule3;

        // Subscribe user 1 to rule 1, course 0.
        $record = new stdClass;
        $record->userid = $data->user1->id;
        $record->ruleid = $data->rule1->id;
        $record->courseid = 0;
        $monitorgenerator->create_subscription($record);

        // Subscribe user 2 to rule 1, course 0.
        $record->userid = $data->user2->id;
        $record->ruleid = $data->rule1->id;
        $record->courseid = 0;
        $monitorgenerator->create_subscription($record);

        // Subscribe user 2 to rule 2, course 1.
        $record->userid = $data->user2->id;
        $record->ruleid = $data->rule2->id;
        $record->courseid = $data->course1->id;
        $monitorgenerator->create_subscription($record);

        // Subscribe user 1 to rule 3, course 0.
        $record->userid = $data->user1->id;
        $record->ruleid = $data->rule3->id;
        $record->courseid = 0;
        $monitorgenerator->create_subscription($record);

        return $data;
    }

    /**
     * Test the purge function. Make sure that the control data is not affected.
     */
    public function test_purge() {
        global $DB;

        $data = $this->setup_data();

        // Verify the current data
        $rows = $DB->get_records('tool_monitor_subscriptions');
        $this->assertEquals(4, count($rows));
        $user1rows = array_filter($rows, function ($row) use ($data) {
            return $row->userid == $data->user1->id;
        });
        $user2rows = array_filter($rows, function ($row) use ($data) {
            return $row->userid == $data->user2->id;
        });

        // Execute the purge for user1.
        $status = \tool_monitor\userdata\subscriptions::execute_purge(new target_user($data->user1), context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check the results.
        $rows = $DB->get_records('tool_monitor_subscriptions');
        $this->assertEquals(2, count($rows));
        $this->assertEquals($user2rows, $rows);

        // Execute the purge for user without subscriptions
        $status = \tool_monitor\userdata\subscriptions::execute_purge(new target_user($data->user3), context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);

        // Check the results.
        $rows = $DB->get_records('tool_monitor_subscriptions');
        $this->assertEquals(2, count($rows));
        $this->assertEquals($user2rows, $rows);
    }

    /**
     * Test the export function. Make sure that the control data is not exported.
     */
    public function test_export() {
        global $DB;

        $data = $this->setup_data();
        $context = context_system::instance();

        // Execute the export.
        $result = \tool_monitor\userdata\subscriptions::execute_export(new target_user($data->user2), $context);

        // Check the results.
        $this->assertCount(2, $result->data);
        $this->assertCount(0, $result->files);

        $expectedids = [$data->rule1->id, $data->rule2->id];
        foreach ($result->data as $subscription) {
            $this->assertArrayHasKey('id', $subscription);
            $this->assertArrayHasKey('courseid', $subscription);
            $this->assertArrayHasKey('ruleid', $subscription);
            $this->assertArrayHasKey('cmid', $subscription);
            $this->assertArrayHasKey('rulename', $subscription);
            $this->assertArrayHasKey('plugin', $subscription);
            $this->assertArrayHasKey('eventname', $subscription);
            $this->assertArrayHasKey('threshold', $subscription);

            $idx = array_search($subscription['ruleid'], $expectedids);
            $this->assertNotFalse($idx);
            unset($expectedids[$idx]);

            $rule = $data->rules[$subscription['ruleid']];

            $this->assertSame($rule->get_name($context), $subscription['rulename']);
            $this->assertSame($rule->get_plugin_name(), $subscription['plugin']);
            $this->assertSame($rule->get_event_name(), $subscription['eventname']);
            $this->assertSame($rule->get_filters_description(), $subscription['threshold']);
        }
    }

    /**
     * Test the count function.
     */
    public function test_count() {
        $data = $this->setup_data();

        $this->assertEquals(2, \tool_monitor\userdata\subscriptions::execute_count(new target_user($data->user1), context_system::instance()));
        $this->assertEquals(2, \tool_monitor\userdata\subscriptions::execute_count(new target_user($data->user2), context_system::instance()));
        $this->assertEquals(0, \tool_monitor\userdata\subscriptions::execute_count(new target_user($data->user3), context_system::instance()));
    }
}