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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_message
 */

defined('MOODLE_INTERNAL') || die();

class totara_message_update_messages_testcase extends advanced_testcase {
    public function test_name_present() {
        $task = new \totara_message\task\update_messages_task();
        $task->get_name();
    }

    public function test_execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/message/lib.php');

        $this->resetAfterTest();
        $this->preventResetByRollback();

        $this->assertSame(0, $DB->count_records('message'));
        $this->assertSame(0, $DB->count_records('message_metadata'));
        $this->assertSame(0, $DB->count_records('message_read'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $event = new stdClass;
        $event->userfrom = $user1;
        $event->userto = $user2;
        $event->contexturl = $CFG->wwwroot . '/';
        $event->icon = 'program-approve';
        $event->subject = 'Some alert';
        $event->fullmessage = 'Full alert message';
        $event->fullmessagehtml = '<div style="color:red">Full alert message</div>';
        tm_alert_send(clone($event));
        tm_alert_send(clone($event));
        tm_alert_send(clone($event));

        $event = new stdClass;
        $event->userfrom = $user2;
        $event->userto = $user1;
        $event->contexturl = $CFG->wwwroot . '/';
        $event->icon = 'program-approve';
        $event->subject = 'Some task';
        $event->fullmessage = 'Full task message';
        $event->fullmessagehtml = '<div style="color:red">Full task message</div>';
        tm_task_send(clone($event));
        tm_task_send(clone($event));
        tm_task_send(clone($event));

        $messages = $DB->get_records('message', array(), 'id ASC', '*');
        $this->assertCount(6, $messages);
        $messages = array_values($messages);

        tm_message_dismiss($messages[2]->id);
        tm_message_dismiss($messages[5]->id);

        $this->assertSame(6, $DB->count_records('message_metadata'));
        $this->assertSame(4, $DB->count_records('message'));
        $this->assertSame(2, $DB->count_records('message_read'));

        $task = new \totara_message\task\update_messages_task();
        $task->execute();

        $this->assertSame(6, $DB->count_records('message_metadata'));
        $this->assertSame(4, $DB->count_records('message'));
        $this->assertSame(2, $DB->count_records('message_read'));

        $messages[0]->timecreated = $messages[0]->timecreated - (24*60*60*\totara_message\task\update_messages_task::TOTARA_MSG_CRON_DISMISS_ALERTS) - 3600;
        $DB->update_record('message', $messages[0]);

        $messages[3]->timecreated = $messages[3]->timecreated - (24*60*60*\totara_message\task\update_messages_task::TOTARA_MSG_CRON_DISMISS_TASKS) - 3600;
        $DB->update_record('message', $messages[3]);

        $messages = $DB->get_records('message', array(), 'id ASC', '*');
        $messages = array_values($messages);

        $task = new \totara_message\task\update_messages_task();
        $task->execute();

        $this->assertSame(6, $DB->count_records('message_metadata'));
        $this->assertSame(2, $DB->count_records('message'));
        $this->assertSame(4, $DB->count_records('message_read'));

        $newmessages = $DB->get_records('message', array(), 'id ASC', '*');
        $newmessages = array_values($newmessages);

        $this->assertEquals($messages[1], $newmessages[0]);
        $this->assertEquals($messages[3], $newmessages[1]);
    }
}