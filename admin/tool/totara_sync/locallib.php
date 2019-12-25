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

require_once($CFG->dirroot . '/totara/core/lib/scheduler.php');

/**
 * Take scheduled task config and construct an array to set the form data.
 *
 * @param \core\task\scheduled_task $task The scheduled task
 *
 * @return mixed Complex schedule and data for $form->set_data eg. array(true, array('frequency' => 1, 'schedule'=> 4))
 */
function get_schedule_form_data (\core\task\scheduled_task $task) {
    // Detect if this is to complex to display.
    $count = 0;

    if ($task->get_minute() != '*' && $task->get_minute() != '0') {
        $count++;
    }
    if ($task->get_hour() != '*' && $task->get_minute() != '0') {
        $count++;
    }
    if ($task->get_day() != '*') {
        $count++;
    }
    if ($task->get_month() != '*') {
        $count++;
    }
    if ($task->get_day_of_week() != '*') {
        $count++;
    }

    if ($count > 1) {
        // Complex scheduling.
        return array(true, array());
    }

    // Frequency 1.
    // Daily at x hour.
    if ($task->get_minute() == '0' &&
        $task->get_hour() != '*' &&
        strpos($task->get_hour(), '*/') === false &&
        $task->get_day() == '*' &&
        $task->get_month() == '*' &&
        $task->get_day_of_week() == '*') {

        $scheduleconfig = array(
            'frequency' => 1,
            'schedule' => $task->get_hour()
        );

        return array(false, $scheduleconfig);
    }

    // Frequency 2.
    // Weekly on x day of week.
    if ($task->get_minute() == '0' &&
        $task->get_hour() == '0' &&
        $task->get_day() == '*' &&
        $task->get_month() == '*' &&
        $task->get_day_of_week() != '*') {

        $scheduleconfig = array(
            'frequency' => 2,
            'schedule' => $task->get_day_of_week()
        );

        return array(false, $scheduleconfig);
    }

    // Frequency 3.
    // Monthly on x day of month.
    if ($task->get_minute() == '0' &&
        $task->get_hour() == '0' &&
        $task->get_day() != '*' &&
        $task->get_month() == '*' &&
        $task->get_day_of_week() == '*') {

        $scheduleconfig = array(
            'frequency' => 3,
            'schedule' => $task->get_day()
        );

        return array(false, $scheduleconfig);
    }

    // Frequency 4.
    // Every x hours.
    $validhours = array(1,2,3,4,6,8,12);
    $hour = str_replace("*/", "", $task->get_hour());
    $hour = $hour === "*" ? 1 : $hour;

    if (in_array($hour, $validhours) &&
        $task->get_minute() == '0' &&
        $task->get_day() == '*' &&
        $task->get_month() == '*' &&
        $task->get_day_of_week() == '*') {

        $scheduleconfig = array(
            'frequency' => 4,
            'schedule' => $hour
        );

        return array(false, $scheduleconfig);
    }

    // Frequency 5.
    // Every x minutes.
    $validminutes = array(1,2,3,4,5,10,15,20,30);
    $minute = str_replace("*/", "", $task->get_minute());
    $minute = $minute === "*" ? 1 : $minute;

    if ((substr($task->get_minute(), 0, 2) == '*/' || $task->get_minute() == '*') &&
        in_array($minute, $validminutes) &&
        $task->get_hour() == '*' &&
        $task->get_day() == '*' &&
        $task->get_month() == '*' &&
        $task->get_day_of_week() == '*') {

        $scheduleconfig = array(
            'frequency' => 5,
            'schedule' => $minute
        );

        return array(false, $scheduleconfig);
    }

    // A valid schedule for output could not be found so return as complex scheduling.
    return array(true, array());
}


/**
 * Save the totara_sync scheduled task given form data.
 *
 * @param object $data Object containing the frequency and schedule.
 *
 */
function save_scheduled_task_from_form ($data) {
    // Create instance of the task so we can change config.
    $task = \core\task\manager::get_scheduled_task('\totara_core\task\tool_totara_sync_task');

    if (isset($data->frequency) && isset($data->schedule)) {
        switch ($data->frequency) {
            case scheduler::DAILY:
                $hour = $data->schedule;
                $task->set_hour($hour);
                // Set other schedule variables to ensure this only runs once in the hour.
                $task->set_day('*');
                $task->set_minute('0');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
            case scheduler::WEEKLY:
                $dayofweek = $data->schedule;
                $task->set_day_of_week($dayofweek);
                // Set other schedule variables to ensure this only runs once in the week.
                $task->set_hour('0');
                $task->set_minute('0');
                $task->set_day('*');
                $task->set_month('*');
                break;
            case scheduler::MONTHLY:
                $day = $data->schedule;
                $task->set_day($day);
                // Set other schedule variables to ensure this only runs once in the week.
                $task->set_hour('0');
                $task->set_minute('0');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
            case scheduler::HOURLY:
                $hour = $data->schedule == 1 ? '*' : '*/' . $data->schedule;
                $task->set_hour($hour);
                // Set other schedule variables to ensure this only runs once in the selected hours.
                $task->set_day('*');
                $task->set_minute('0');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
            case scheduler::MINUTELY:
                $minute = $data->schedule == 1 ? '*' : '*/' . $data->schedule;
                $task->set_minute($minute);
                // Set all other schedule variables to '*'.
                $task->set_hour('*');
                $task->set_day('*');
                $task->set_day_of_week('*');
                $task->set_month('*');
                break;
        }
    }

    // Set scheduled task to enabled/disabled.
    $crondisabled = $data->cronenable == 1 ? false : true;
    $task->set_disabled($crondisabled);

    // The task is customised.
    $task->set_customised(true);

    // Write settings to database.
    \core\task\manager::configure_scheduled_task($task);
}
