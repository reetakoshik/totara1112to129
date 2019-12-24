<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test registration related code.
 */
class totara_core_register_testcase extends advanced_testcase {
    public static function setUpBeforeClass() {
        global $CFG;
        parent::setUpBeforeClass();
        require_once("$CFG->dirroot/$CFG->admin/registerlib.php");
    }

    public function test_is_valid_registration_code_format() {
        $this->assertFalse(is_valid_registration_code_format(1));
        $this->assertFalse(is_valid_registration_code_format('1111111111111111'));
        $this->assertTrue(is_valid_registration_code_format('aaaaaaaaaaaaaa12'));
    }

    public function test_is_registration_required() {
        global $CFG;
        $this->resetAfterTest();

        unset_config('registrationenabled');
        unset_config('sitetype');
        unset_config('registrationcode');

        $this->assertFalse(is_registration_required());
        $this->setAdminUser();
        $this->assertTrue(is_registration_required());

        $CFG->registrationenabled = 0;
        $this->assertFalse(is_registration_required());

        $CFG->registrationenabled = 1;
        $this->assertTrue(is_registration_required());

        $CFG->sitetype = 'demo';
        $this->assertFalse(is_registration_required());

        $CFG->sitetype = 'qa';
        $this->assertFalse(is_registration_required());

        $CFG->sitetype = 'development';
        $this->assertFalse(is_registration_required());

        $CFG->sitetype = 'production';
        $this->assertTrue(is_registration_required());

        $CFG->registrationcode = 'xx';
        $this->assertTrue(is_registration_required());

        $CFG->registrationcode = 'aaaaaaaaaaaaaa12';
        $this->assertFalse(is_registration_required());

        $CFG->registrationcodewwwhash = sha1($CFG->wwwroot);
        $this->assertFalse(is_registration_required());

        $CFG->registrationcodewwwhash = sha1($CFG->wwwroot).'x';
        $this->assertTrue(is_registration_required());

        unset($CFG->registrationcodewwwhash);
        $this->assertFalse(is_registration_required());
    }

    public function test_get_registration_data() {
        global $CFG;
        $this->resetAfterTest();

        unset_config('registrationenabled');
        unset_config('sitetype');
        unset_config('registrationcode');

        $data = get_registration_data();
        $this->assertArrayNotHasKey('edition', $data); // Removed in registration, we can use version number.

        // Optional fields/
        $this->assertArrayNotHasKey('flavour', $data);
        $this->assertArrayNotHasKey('sitetype', $data);
        $this->assertArrayNotHasKey('registrationcode', $data);

        set_config('currentflavour', 'enterprise', 'totara_flavour');
        set_config('sitetype', 'production');
        set_config('registrationcode', 'aaaaaaaaaaaa12');

        $data = get_registration_data();
        $this->assertSame($CFG->wwwroot, $data['wwwroot']);
        $this->assertSame($CFG->sitetype, $data['sitetype']);
        $this->assertSame($CFG->registrationcode, $data['registrationcode']);
        $this->assertSame('enterprise', $data['flavour']);
        $this->assertArrayHasKey('addons', $data);
    }

    public function test_send_registration_data_email() {
        $this->resetAfterTest();

        $sink = $this->redirectEmails();
        $data = get_registration_data();
        send_registration_data_email($data);
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertSame('registrations@totaralearning.com', $messages[0]->to);
        $this->assertSame('registrations@totaralearning.com', $messages[0]->toname);
        $this->assertContains('Content-Disposition: attachment; filename=site_registration.ttr', $messages[0]->body);
    }

    public function test_send_registration_data_task() {
        global $CFG;
        $this->resetAfterTest();

        set_config('registrationenabled', 1);
        $this->setCurrentTimeStart();
        $task = new \totara_core\task\send_registration_data_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame("Performing registration update:\nRegistration update done\n", $output);
        $this->assertTimeCurrent($CFG->registered);

        $task = new \totara_core\task\send_registration_data_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame("Registration was already updated less than 3 days ago\n", $output);

        set_config('registered', time() - 60*60*24*3 + 100);
        $task = new \totara_core\task\send_registration_data_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame("Registration was already updated less than 3 days ago\n", $output);

        set_config('registered', time() - 60*60*24*3 - 100);
        $this->setCurrentTimeStart();
        $task = new \totara_core\task\send_registration_data_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame("Performing registration update:\nRegistration update done\n", $output);
        $this->assertTimeCurrent($CFG->registered);

        set_config('registrationenabled', 0);
        set_config('registered', time() - 60*60*24*3 - 100);
        $task = new \totara_core\task\send_registration_data_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame("Registration updates are disabled\n", $output);

        set_config('registrationenabled', 1);
        set_config('registered', time() - 60*60*24*3 - 100);
        $this->setCurrentTimeStart();
        $task = new \totara_core\task\send_registration_data_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame("Performing registration update:\nRegistration update done\n", $output);
        $this->assertTimeCurrent($CFG->registered);
    }
}
