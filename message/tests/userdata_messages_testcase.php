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
 * @package core_message
 */

namespace core_message\userdata;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for userdata_*_messages tests
 */
abstract class userdata_messages_testcase extends advanced_testcase {

    /**
     * Assert that there is the expected messages
     *
     * @param $expectedid
     */
    protected function assert_message_exists(int $expectedid): void {
        $this->do_assert_message_exists($expectedid, 'message');
    }

    /**
     * Assert that there is the expected read messages
     *
     * @param $expectedid
     */
    protected function assert_message_read_exists(int $expectedid): void {
        $this->do_assert_message_exists($expectedid, 'message_read');
    }

    /**
     * Assert that there is the expected messages
     *
     * @param int $expectedid
     * @param string $messagetable
     */
    private function do_assert_message_exists(int $expectedid, string $messagetable): void {
        global $DB;

        $messages = $DB->get_records($messagetable, ['id' => $expectedid]);
        $this->assertNotEmpty(
            $messages,
            sprintf('Failed asserting that message with id %d exists.', $expectedid)
        );

        $messageidcolumn = $messagetable == 'message' ? 'messageid' : 'messagereadid';

        $metadata = $DB->get_records('message_metadata', [$messageidcolumn => $expectedid]);
        $this->assertNotEmpty(
            $metadata,
            sprintf('Failed asserting that expected record of metadata for message %d exists.', $expectedid)
        );

        if ($messagetable == 'message') {
            $popup = $DB->get_records('message_popup', ['messageid' => $expectedid]);
            $this->assertNotEmpty(
                $popup,
                sprintf('Failed asserting that expected record of popup for message %d exists.', $expectedid)
            );

            $working = $DB->get_records('message_working', ['unreadmessageid' => $expectedid]);
            $this->assertNotEmpty(
                $working,
                sprintf('Failed asserting that expected record of working for message %d exists.', $expectedid)
            );

        }
    }

    /**
     * Assert that there is the expected messages
     *
     * @param $expectedid
     */
    protected function assert_message_not_exists(int $expectedid): void {
        $this->do_assert_message_not_exists($expectedid, 'message');
    }

    /**
     * Assert that there is the expected read messages
     *
     * @param $expectedid
     */
    protected function assert_message_read_not_exists(int $expectedid): void {
        $this->do_assert_message_not_exists($expectedid, 'message_read');
    }

    /**
     * Assert that there is the expected messages
     *
     * @param int $expectedid
     * @param string $messagetable
     */
    private function do_assert_message_not_exists(int $expectedid, string $messagetable): void {
        global $DB;

        $messages = $DB->get_records($messagetable, ['id' => $expectedid]);
        $this->assertEmpty(
            $messages,
            sprintf('Failed asserting that message with id %d not exists.', $expectedid)
        );

        $messageidcolumn = $messagetable == 'message' ? 'messageid' : 'messagereadid';

        $metadata = $DB->get_records('message_metadata', [$messageidcolumn => $expectedid]);
        $this->assertEmpty(
            $metadata,
            sprintf('Failed asserting that expected record of metadata for message %d not exists.', $expectedid)
        );

        if ($messagetable == 'message') {
            $popup = $DB->get_records('message_popup', ['messageid' => $expectedid]);
            $this->assertEmpty(
                $popup,
                sprintf('Failed asserting that expected record of popup for message %d not exists.', $expectedid)
            );

            $working = $DB->get_records('message_working', ['unreadmessageid' => $expectedid]);
            $this->assertEmpty(
                $working,
                sprintf('Failed asserting that expected record of working for message %d not exists.', $expectedid)
            );

        }
    }


}