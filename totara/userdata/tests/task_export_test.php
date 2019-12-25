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

/**
 * Tests the export task class.
 */
class totara_userdata_task_export_testcase extends advanced_testcase {
    public function test_adhoc() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-additionalnames'));
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $taskid = \totara_userdata\local\export_type::trigger_self_export($type->id);
        $taskrecord = $DB->get_record('task_adhoc', array('id' => $taskid), '*', MUST_EXIST);
        $task = \core\task\manager::adhoc_task_from_record($taskrecord);
        $oldexport = $DB->get_record('totara_userdata_export', array('id' => $task->get_custom_data()), '*', MUST_EXIST);
        $this->assertNull($oldexport->timestarted);
        $this->assertNull($oldexport->timefinished);
        $this->assertNull($oldexport->result);

        $sink = $this->redirectMessages();
        ob_start();
        $this->setCurrentTimeStart();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $export = $DB->get_record('totara_userdata_export', array('id' => $oldexport->id), '*', MUST_EXIST);
        $messages = $sink->get_messages();

        $this->assertContains('Export - Success', $output);
        $this->assertTimeCurrent($export->timestarted);
        $this->assertTimeCurrent($export->timefinished);
        $this->assertEquals(-1, $export->result);
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertSame('User data export completed', $message->subject);
        $this->assertSame('noreply@www.example.com', $message->fromemail);
        $this->assertSame($user->id, $message->useridto);
    }
}