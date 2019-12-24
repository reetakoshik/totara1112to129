<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @package auth_approved
 */

class auth_approved_bulk_testcase extends advanced_testcase {

    public function test_get_all_actions() {
        $expected = [
            'approve' => 'auth_approved\bulk\approve',
            'manager' => 'auth_approved\bulk\manager',
            'message' => 'auth_approved\bulk\message',
            'organisation' => 'auth_approved\bulk\organisation',
            'position' => 'auth_approved\bulk\position',
            'reject' => 'auth_approved\bulk\reject',
        ];
        $this->assertSame($expected, \auth_approved\bulk::get_all_actions());
    }

    public function test_get_actions_menu() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $expected = [
            'approve' => get_string('bulkactionapprove', 'auth_approved'),
            'manager' => get_string('bulkactionmanager', 'auth_approved'),
            'message' => get_string('bulkactionmessage', 'auth_approved'),
            'organisation' => get_string('bulkactionorganisation', 'auth_approved'),
            'position' => get_string('bulkactionposition', 'auth_approved'),
            'reject' => get_string('bulkactionreject', 'auth_approved'),
        ];
        ksort($expected);
        $actual = \auth_approved\bulk::get_actions_menu();
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

}