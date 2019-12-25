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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->admin . '/tool/totara_sync/locallib.php');

/**
 * @group tool_totara_sync
 */
class test_sync_schedule extends advanced_testcase {

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    protected function tearDown() {
        parent::tearDown();
    }

    public function test_schedule_from_form_basic_data() {
        // Possible values for Frequency are:
        // 1 - Daily
        // 2 - Weekly
        // 3 - Monthly
        // 4 - Every X Hours
        // 5 - Every X Minutes

        // Every Minute, using '*'
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $testdata1 = array();
        $testdata1['frequency'] = 5; // Every x minutes.
        $testdata1['schedule'] = 1;

        $this->assertFalse($complexschedule);
        $this->assertEquals($testdata1, $scheduleconfig);

        // Every Minute, using '*/1'.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*/1'); # Note this is written differently than the above that just used '*'.
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $testdata1 = array();
        $testdata1['frequency'] = 5; // Every x minutes.
        $testdata1['schedule'] = 1;

        $this->assertFalse($complexschedule);
        $this->assertEquals($testdata1, $scheduleconfig);

        // Every 5 Minutes.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*/5');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $testdata1 = array();
        $testdata1['frequency'] = 5; // Every x minutes.
        $testdata1['schedule'] = 5;

        $this->assertFalse($complexschedule);
        $this->assertEquals($testdata1, $scheduleconfig);


        // Every 12 Minutes (this is too complex because the schedule form doesn't allow 12).
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*/12');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // Every 3 hours.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('0');
        $task->set_hour('*/3');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $testdata1 = array();
        $testdata1['frequency'] = 4; // Every x hours.
        $testdata1['schedule'] = 3;

        $this->assertFalse($complexschedule);
        $this->assertEquals($testdata1, $scheduleconfig);


        // Every 5 hours (this is too complext because the schedule form doesn't allow 5)
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('*/5');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // At 5:00am Everyday.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('0');
        $task->set_hour('5');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $testdata1 = array();
        $testdata1['frequency'] = 1; // Daily.
        $testdata1['schedule'] = 5;

        $this->assertFalse($complexschedule);
        $this->assertEquals($testdata1, $scheduleconfig);


        // Weekly on Wednesday.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('0');
        $task->set_hour('0');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('3');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $testdata1 = array();
        $testdata1['frequency'] = 2; // Weekly.
        $testdata1['schedule'] = 3; // Wednesday.

        $this->assertFalse($complexschedule);
        $this->assertEquals($testdata1, $scheduleconfig);


        // Monthly on the 7th.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('0');
        $task->set_hour('0');
        $task->set_month('*');
        $task->set_day('7');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $testdata1 = array();
        $testdata1['frequency'] = 3; // Monthly.
        $testdata1['schedule'] = 7; // 7th.

        $this->assertFalse($complexschedule);
        $this->assertEquals($testdata1, $scheduleconfig);

    }

    public function test_schedule_from_form_complex_data() {
        // Every 5 Minutes for every 5 hours.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*/5');
        $task->set_hour('*/5');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // Every 15 Minutes past the hour every hour.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('15');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // Every Wednesday at 2am.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('2');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('3');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // Everyday a 1am and 2am.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('1,2');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // Everyday hour at 15 past and quater to.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('15,45');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // The 15th and 25th of each month.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('15,25');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // The Tuesday and Friday each week.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('2,5');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // The every minute between 1am and 12pm.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('1-12');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // The every minute between Tuesday til Thursday.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('*');
        $task->set_day_of_week('2-4');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);


        // The every minute between the 12 and 17th of the month.
        $task = new \totara_core\task\tool_totara_sync_task();
        $task->set_minute('*');
        $task->set_hour('*');
        $task->set_month('*');
        $task->set_day('12-17');
        $task->set_day_of_week('*');
        $task->set_disabled(false);

        list($complexschedule, $scheduleconfig) = get_schedule_form_data($task);

        $this->assertTrue($complexschedule);
        $this->assertEmpty($scheduleconfig);
    }

    public function test_save_schedule_task_from_form() {
        // Possible values for Frequency are:
        // 1 - Daily
        // 2 - Weekly
        // 3 - Monthly
        // 4 - Every X Hours
        // 5 - Every X Minutes

        // Data object:
        // $data->frequency
        // $data->schedule
        // $data->cronenable

        // 4am Daily.
        $data = new stdClass();
        $data->frequency = 1;
        $data->schedule = 4;
        $data->cronenable = 1;

        save_scheduled_task_from_form($data);

        $task = \core\task\manager::get_scheduled_task('\totara_core\task\tool_totara_sync_task');

        $this->assertEquals('0', $task->get_minute());
        $this->assertEquals('4', $task->get_hour());
        $this->assertEquals('*', $task->get_month());
        $this->assertEquals('*', $task->get_day());
        $this->assertEquals('*', $task->get_day_of_week());


        // Weekly on Friday.
        $data = new stdClass();
        $data->frequency = 2;
        $data->schedule = 5;
        $data->cronenable = 1;

        save_scheduled_task_from_form($data);

        $task = \core\task\manager::get_scheduled_task('\totara_core\task\tool_totara_sync_task');

        $this->assertEquals('0', $task->get_minute());
        $this->assertEquals('0', $task->get_hour());
        $this->assertEquals('*', $task->get_month());
        $this->assertEquals('*', $task->get_day());
        $this->assertEquals('5', $task->get_day_of_week());


        // Monthy on the 21st.
        $data = new stdClass();
        $data->frequency = 3;
        $data->schedule = 21;
        $data->cronenable = 1;

        save_scheduled_task_from_form($data);

        $task = \core\task\manager::get_scheduled_task('\totara_core\task\tool_totara_sync_task');

        $this->assertEquals('0', $task->get_minute());
        $this->assertEquals('0', $task->get_hour());
        $this->assertEquals('*', $task->get_month());
        $this->assertEquals('21', $task->get_day());
        $this->assertEquals('*', $task->get_day_of_week());


        // Every 6 hours.
        $data = new stdClass();
        $data->frequency = 4;
        $data->schedule = 6;
        $data->cronenable = 1;

        save_scheduled_task_from_form($data);

        $task = \core\task\manager::get_scheduled_task('\totara_core\task\tool_totara_sync_task');

        $this->assertEquals('0', $task->get_minute());
        $this->assertEquals('*/6', $task->get_hour());
        $this->assertEquals('*', $task->get_month());
        $this->assertEquals('*', $task->get_day());
        $this->assertEquals('*', $task->get_day_of_week());


        // Every 10 minutes.
        $data = new stdClass();
        $data->frequency = 5;
        $data->schedule = 10;
        $data->cronenable = 1;

        save_scheduled_task_from_form($data);

        $task = \core\task\manager::get_scheduled_task('\totara_core\task\tool_totara_sync_task');

        $this->assertEquals('*/10', $task->get_minute());
        $this->assertEquals('*', $task->get_hour());
        $this->assertEquals('*', $task->get_month());
        $this->assertEquals('*', $task->get_day());
        $this->assertEquals('*', $task->get_day_of_week());

        // Every minute.
        $data = new stdClass();
        $data->frequency = 5;
        $data->schedule = 1;
        $data->cronenable = 1;

        save_scheduled_task_from_form($data);

        $task = \core\task\manager::get_scheduled_task('\totara_core\task\tool_totara_sync_task');

        $this->assertEquals('*', $task->get_minute());
        $this->assertEquals('*', $task->get_hour());
        $this->assertEquals('*', $task->get_month());
        $this->assertEquals('*', $task->get_day());
        $this->assertEquals('*', $task->get_day_of_week());
    }
}
