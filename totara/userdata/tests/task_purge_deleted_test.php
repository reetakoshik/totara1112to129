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
use totara_userdata\local\util;

/**
 * Tests the deleted purge task class.
 */
class totara_userdata_task_purge_deleted_testcase extends advanced_testcase {
    public function test_adhoc() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowdeleted' => 1, 'items' => 'core_user-additionalnames'));
        $user = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $extra = util::get_user_extras($user->id);
        $extra->deletedpurgetypeid = $type->id;
        $DB->update_record('totara_userdata_user', $extra);

        $purges = $DB->get_records('totara_userdata_purge', array('userid' => $user->id));
        $this->assertCount(0, $purges);

        $this->setUser(null);

        $task = new totara_userdata\task\purge_deleted();

        $sink = $this->redirectMessages();
        ob_start();
        $this->setCurrentTimeStart();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $messages = $sink->get_messages();

        $this->assertCount(0, $messages);
        $this->assertContains('Purge finished - Success', $output);

        $purges = $DB->get_records('totara_userdata_purge', array('userid' => $user->id));
        $this->assertCount(1, $purges);
        $purge = reset($purges);
        $this->assertSame($type->id, $purge->purgetypeid);
        $this->assertTimeCurrent($purge->timestarted);
        $this->assertTimeCurrent($purge->timefinished);
        $this->assertEquals(-1, $purge->result);

        $extra = util::get_user_extras($user->id);
        $this->assertTimeCurrent($extra->timedeletedpurged);
    }
}