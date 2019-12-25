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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 * @category test
 */

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the target_user class.
 */
class totara_userdata_target_user_testcase extends advanced_testcase {
    public function test_instance() {
        global $DB;
        $this->resetAfterTest();

        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(array('suspended' => 1));
        $deleteduser = $this->getDataGenerator()->create_user();
        $activeusercontext = context_user::instance($activeuser->id);
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $deletedusercontext = context_user::instance($deleteduser->id);
        delete_user($deleteduser);
        $deleteduser = $DB->get_record('user', array('id' => $deleteduser->id));

        $target = new target_user($activeuser);
        $this->assertSame($activeusercontext->id, $target->contextid);
        $this->assertSame(target_user::STATUS_ACTIVE, $target->status);
        foreach ((array)$activeuser as $k => $v) {
            $this->assertSame($v, $target->{$k});
        }
        try {
            $target->xx = 'xx';
            $this->fail('coding_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
        try {
            unset($target->status);
            $this->fail('coding_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        $target = new target_user($suspendeduser);
        $this->assertSame($suspendedusercontext->id, $target->contextid);
        $this->assertSame(target_user::STATUS_SUSPENDED, $target->status);

        $target = new target_user($deleteduser);
        $this->assertSame($deletedusercontext->id, $target->contextid);
        $this->assertSame(target_user::STATUS_DELETED, $target->status);

        $DB->delete_records('totara_userdata_user', array('userid' => $deleteduser->id));
        $target = new target_user($deleteduser);
        $this->assertNull($target->contextid);
        $this->assertSame(target_user::STATUS_DELETED, $target->status);

        // Test soon-to-be deprecated unconfirmed users too.
        $unconfirmeduser = $this->getDataGenerator()->create_user(array('confirmed' => 0));
        $unconfirmedsuspendeduser = $this->getDataGenerator()->create_user(array('confirmed' => 0, 'suspended' => 1));
        $unconfirmedusercontext = context_user::instance($unconfirmeduser->id);
        $unconfirmedsuspendedusercontext = context_user::instance($unconfirmedsuspendeduser->id);
        $target = new target_user($unconfirmeduser);
        $this->assertSame($unconfirmedusercontext->id, $target->contextid);
        $this->assertSame(target_user::STATUS_ACTIVE, $target->status);
        $target = new target_user($unconfirmedsuspendeduser);
        $this->assertSame($unconfirmedsuspendedusercontext->id, $target->contextid);
        $this->assertSame(target_user::STATUS_SUSPENDED, $target->status);
    }

    public function test_get_user_statuses() {
        $statuses = target_user::get_user_statuses();
        $this->assertCount(3, $statuses);
        $this->assertArrayHasKey(target_user::STATUS_ACTIVE, $statuses);
        $this->assertArrayHasKey(target_user::STATUS_DELETED, $statuses);
        $this->assertArrayHasKey(target_user::STATUS_SUSPENDED, $statuses);
    }
}
