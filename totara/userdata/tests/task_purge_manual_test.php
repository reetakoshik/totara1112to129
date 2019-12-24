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

defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\target_user;

/**
 * Tests the manual purge task class.
 */
class totara_userdata_task_purge_manual_testcase extends advanced_testcase {
    public function test_adhoc() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $contextsystem = context_system::instance();
        $type = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1, 'items' => 'core_user-additionalnames'));
        $user = $this->getDataGenerator()->create_user();
        $activeuser = $this->getDataGenerator()->create_user();
        $this->setUser($activeuser);
        $taskid = \totara_userdata\local\purge_type::trigger_manual_purge($type->id, $user->id, $contextsystem->id);
        $taskrecord = $DB->get_record('task_adhoc', array('id' => $taskid), '*', MUST_EXIST);
        $task = \core\task\manager::adhoc_task_from_record($taskrecord);
        $oldpurge = $DB->get_record('totara_userdata_purge', array('id' => $task->get_custom_data()), '*', MUST_EXIST);
        $this->assertNull($oldpurge->timestarted);
        $this->assertNull($oldpurge->timefinished);
        $this->assertNull($oldpurge->result);

        $sink = $this->redirectMessages();
        ob_start();
        $this->setCurrentTimeStart();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $oldpurge->id), '*', MUST_EXIST);
        $messages = $sink->get_messages();

        $this->assertContains('Purge - Success', $output);
        $this->assertTimeCurrent($purge->timestarted);
        $this->assertTimeCurrent($purge->timefinished);
        $this->assertEquals(-1, $purge->result);
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertSame('Manual purge of user data completed', $message->subject);
        $this->assertSame('noreply@www.example.com', $message->fromemail);
        $this->assertSame($activeuser->id, $message->useridto);
    }
}