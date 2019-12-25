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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests \core\task\delete_unconfirmed_users_task.
 *
 * @package   core
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
class delete_unconfirmed_users_task_test extends advanced_testcase {

    /**
     * Test the basic operation of the \core\task\delete_unconfirmed_users_task task.
     */
    public function test_basic_operation() {
        global $DB;

        $this->resetAfterTest();

        $oldtime = time() - 14 * DAYSECS;

        $baseuser = [
            'confirmed' => 0,
            'timecreated' => $oldtime,
            'firstaccess' => $oldtime,
            'lastaccess' => $oldtime
        ];
        $generator = $this->getDataGenerator();
        $bill = $generator->create_user(['firstname' => 'Bill', 'lastname' => 'Smith'] + $baseuser);
        $jen = $generator->create_user(['firstname' => 'Jen', 'lastname' => 'Doe'] + $baseuser);

        // Check that we have the two users we expect, and no more.
        $params = ['confirmed' => 0, 'lastaccess' => $oldtime, 'deleted' => 0];
        $this->assertTrue($DB->record_exists('user', ['firstname' => 'Bill'] + $params));
        $this->assertTrue($DB->record_exists('user', ['firstname' => 'Jen'] + $params));
        $this->assertSame(2, $DB->count_records('user', $params));
        $usercount = $DB->count_records('user');

        // Run the task.
        $sink = $this->redirectEvents();
        $task = new \core\task\delete_unconfirmed_users_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $sink->close();
        $events = $sink->get_events();

        // Confirm the expected events.
        $this->assertCount(2, $events);
        foreach ($events as $event) {
            $data = $event->get_data();
            $this->assertSame('\core\event\user_deleted', $data['eventname']);
            $this->assertSame('user', $data['target']);
            $this->assertSame('deleted', $data['action']);
        }

        // Confirm the output was exactly as we expect.
        $output = explode("\n", trim($output, "\n"));
        sort($output);
        $output = join("\n", $output);
        $expected =  " Deleted unconfirmed user for Bill Smith ({$bill->id})\n";
        $expected .= " Deleted unconfirmed user for Jen Doe ({$jen->id})";
        $this->assertSame($expected, $output);

        // Confirm that the two users have now been marked as deleted, and that we have no unconfirmed
        // users left who have not been deleted.
        $this->assertSame(0, $DB->count_records('user', $params));
        $params['deleted'] = '1';
        $this->assertTrue($DB->record_exists('user', ['firstname' => 'Bill'] + $params));
        $this->assertTrue($DB->record_exists('user', ['firstname' => 'Jen'] + $params));
        // Ensure that absolutely no users have been added or removed.
        $this->assertSame($usercount, $DB->count_records('user'));
    }
}