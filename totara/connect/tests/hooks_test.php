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
 * @package totara_connect
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests hooks use by totara connect.
 */
class totara_connect_hooks_testcase extends advanced_testcase {
    public function test_profile_edit_returnto() {
        global $CFG;
        require_once("$CFG->dirroot/user/editlib.php");
        $this->resetAfterTest();

        /** @var totara_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_connect');

        $user = get_admin();
        $site = get_site();
        $client = $generator->create_client();


        $CFG->enableconnectserver = '0';

        $result = useredit_get_return_url($user, 'profile', $site);
        $expected = new moodle_url('/user/profile.php', array('id' => $user->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user, 'tc_' . $client->clientidnumber, $site);
        $expected = new moodle_url('/user/preferences.php', array('userid' => $user->id));
        $this->assertSame((string)$expected, (string)$result);


        $CFG->enableconnectserver = '1';

        $result = useredit_get_return_url($user, 'profile', $site);
        $expected = new moodle_url('/user/profile.php', array('id' => $user->id));
        $this->assertSame((string)$expected, (string)$result);

        $result = useredit_get_return_url($user, 'tc_' . $client->clientidnumber, $site);
        $expected = new moodle_url($client->clienturl . '/auth/connect/user_edit_finish.php', array('serveruserid' => $user->id, 'clientidnumber' => $client->clientidnumber));
        $this->assertSame((string)$expected, (string)$result);
    }
}
