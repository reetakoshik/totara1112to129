<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */

defined('MOODLE_INTERNAL') || die();

use core_user\email_bounce_counter;

/**
 * Class core_user_email_bounce_counter_testcase
 * @see \core_user\email_bounce_counter
 */
class core_user_email_bounce_counter_testcase extends advanced_testcase {
    /**
     * Test suite of creating the history of user's email bounce/send when it is being updated.
     * @return void
     */
    public function test_create_history_preference(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $emailbouncecounter = new email_bounce_counter($user);
        $emailbouncecounter->reset_counts();
        $preferences = ["email_bounce_count", "email_send_count"];
        foreach ($preferences as $prefname) {
            $backupvalue = $emailbouncecounter->get_backup_count_value($prefname);
            $this->assertEquals(0, $backupvalue);
        }
    }

    /**
     * The test suite of restoring the history preference for `email_bounce_count` and `email_send_count`. The steps
     * are quite simple. First, the test creates the preference, then using class email_bounce_counter to create
     * the history record of that preference. Afterward, update the current record, and then restore it. The test is
     * about comparation between the snapshot of record at a point after update and snapshot at a point after restoe.
     *
     * @return void
     * @throws Exception
     */
    public function test_restore_history_preference(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $preferences = ['email_bounce_count', 'email_send_count'];
        $snapshots = [];
        foreach ($preferences as $preferencename) {
            $value = rand(1, 10);
            $current = (object) [
                'name'      => $preferencename,
                'value'     => $value,
                'userid'    => $user->id
            ];

            $id = $DB->insert_record("user_preferences", $current, true);
            $current->id = $id;
            $snapshots[$preferencename] = $value;
        }

        $emailcounter = new email_bounce_counter($user);
        $emailcounter->reset_counts();
        foreach ($preferences as $prefname) {
            $snapshotvalue = $snapshots[$prefname];
            $value = get_user_preferences($prefname, 0, $user->id);
            $this->assertGreaterThan($value, $snapshotvalue);
        }

        $emailcounter->restore();
        foreach ($preferences as $prefname) {
            $snapshotvalue = $snapshots[$prefname];
            $value = get_user_preferences($prefname, 0, $user->id);
            $this->assertEquals($value, $snapshotvalue);
        }
    }
}