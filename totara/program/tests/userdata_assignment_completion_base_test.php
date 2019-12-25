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
 * @package totara_program
 */

use totara_program\task\send_messages_task;
use totara_program\userdata\base_assignment_completion;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Tests for assignment and completion userdata items
 */
abstract class totara_program_base_userdata_assignment_completion_base_test extends advanced_testcase {

    /**
     * Returns the item class, needs to be based on \totara_program\userdata\base_assignment_completion
     *
     * @return string
     */
    abstract protected function get_item_class(): string;

    /**
     * Test abilities to export, count and purge
     */
    public function test_abilities() {
        /** @var base_assignment_completion $class */
        $class = $this->get_item_class();

        $this->assertTrue($class::is_exportable());
        $this->assertTrue($class::is_countable());
        $this->assertTrue($class::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue($class::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue($class::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test which context levels this item is compatible with
     */
    public function test_compatible_context_levels() {
        /** @var base_assignment_completion $class */
        $class = $this->get_item_class();

        $expectedlevels = [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_PROGRAM];
        $actuallevels = $class::get_compatible_context_levels();
        sort($actuallevels);
        $this->assertEquals($expectedlevels, $actuallevels);
    }

    /**
     * Create fixtures to be used in the test cases.
     */
    abstract protected function create_fixtures();

    /**
     * Sends assignment messages and fill the messagelog table
     */
    protected function send_messages() {
        global $DB;

        ob_start();
        $programs = [];
        // Manipulate timeassigned to make it possible to send messages.
        $DB->set_field('prog_user_assignment', 'timeassigned', time() - 10);
        // Trigger one protected method, only for performance reason.
        $task = new send_messages_task();
        try {
            $method = new ReflectionMethod(send_messages_task::class, 'program_cron_enrolment_messages');
            $method->setAccessible(true);
            $method->invokeArgs($task, [&$programs]);
        } catch (ReflectionException $exception) {
            $this->fail('Couldn\'t find method to trigger sending messages in task');
        }
        ob_end_clean();
    }

    /**
     * @param int $programid
     * @return stdClass|null
     */
    protected function get_certification(int $programid) {
        global $DB;
        $select = "id IN (SELECT certifid FROM {prog} WHERE id = :id)";
        $certification = $DB->get_record_select('certif', $select, ['id' => $programid]);
        if ($certification) {
            return $certification;
        }
        return null;
    }

    /**
     * @param target_user $user
     * @param int $programid
     */
    protected function create_extension(target_user $user, int $programid) {
        global $DB;

        $extension = [
            'programid' => $programid,
            'userid' => $user->id,
            'extensiondate' => time() + 3600,
            'extensionreason' => 'Testing the extension',
            'status' => 0
        ];

        $DB->insert_record('prog_extension', (object)$extension);
    }

    /**
     * @param int $certid
     * @param int $userid
     */
    protected function create_certif_history(int $certid, int $userid) {
        copy_certif_completion_to_hist($certid, $userid);
    }

    /**
     * @param int $programid
     * @param int $userid
     */
    protected function create_prog_history(int $programid, int $userid) {
        global $DB;
        $record = $DB->get_record('prog_completion', ['programid' => $programid, 'userid' => $userid]);
        totara_prog_completion_to_history($record);
    }

    /**
     * @param target_user $user
     * @param int $programid
     * @param bool $iscertification
     */
    protected function assert_entries_exist(target_user $user, int $programid, bool $iscertification = false) {
        $this->assertGreaterThan(0, $this->count_prog_entries('prog_user_assignment', $user, $programid));
        $this->assertGreaterThan(0, $this->count_prog_entries('prog_completion', $user, $programid));
        $this->assertGreaterThan(0, $this->count_prog_entries('prog_completion_log', $user, $programid));
        if ($iscertification) {
            $this->assertGreaterThan(0, $this->count_certif_entries('certif_completion_history', $user, $programid));
        } else {
            $this->assertGreaterThan(0, $this->count_prog_entries('prog_completion_history', $user, $programid));
        }
        $this->assertGreaterThan(0, $this->count_prog_entries('prog_messagelog', $user, $programid));
        $this->assertGreaterThan(0, $this->count_prog_entries('prog_extension', $user, $programid));
    }

    /**
     * @param target_user $user
     * @param int $programid
     */
    protected function assert_entries_not_exist(target_user $user, int $programid) {
        $this->assertEquals(0, $this->count_prog_entries('prog_user_assignment', $user, $programid));
        $this->assertEquals(0, $this->count_prog_entries('prog_completion', $user, $programid));
        $this->assertEquals(0, $this->count_certif_entries('certif_completion_history', $user, $programid));
        $this->assertEquals(0, $this->count_prog_entries('prog_completion_history', $user, $programid));
        $this->assertEquals(0, $this->count_prog_entries('prog_completion_log', $user, $programid));
        $this->assertEquals(0, $this->count_prog_entries('prog_messagelog', $user, $programid));
        $this->assertEquals(0, $this->count_prog_entries('prog_extension', $user, $programid));
    }

    /**
     * @param string $table
     * @param target_user $user
     * @param int $programid
     * @return int
     */
    protected function count_prog_entries(string $table, target_user $user, int $programid): int {
        global $DB;

        $select = "userid = :userid";
        if ($table == 'prog_messagelog') {
            $select .= " AND messageid IN (SELECT id FROM {prog_message} WHERE programid = :programid)";
        } else {
            $select .= " AND programid = :programid";
        }
        return $DB->count_records_select($table, $select, ['userid' => $user->id, 'programid' => $programid]);
    }

    /**
     * @param string $table
     * @param target_user $user
     * @param int $programid
     * @return int
     */
    private function count_certif_entries(string $table, target_user $user, int $programid): int {
        global $DB;
        $certification = $this->get_certification($programid);
        if ($certification) {
            return $DB->count_records($table, ['userid' => $user->id, 'certifid' => $certification->id]);
        }
        return 0;
    }

}