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

use totara_userdata\local\export;
use totara_userdata\userdata\manager;
use totara_userdata\userdata\item;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests export downloaded event.
 */
class totara_userdata_event_export_downloaded_testcase extends advanced_testcase {
    public function test_event() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-names,core_user-username'));
        $syscontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $exportid = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $result = manager::execute_export($exportid);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $filerecord = export::get_result_file_record($exportid);
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid));
        $fs = get_file_storage();
        $file = $fs->get_file_instance($filerecord);

        $event = \totara_userdata\event\export_downloaded::create_from_download($export, $file);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('totara_userdata\event\export_downloaded', $event);
        $this->assertEquals(SYSCONTEXTID, $event->get_context()->id);
        $this->assertSame($export->id, $event->objectid);
        $this->assertSame($user->id, $event->relateduserid);
        $this->assertSame(array('fileid' => $file->get_id(), 'contenthash' => $file->get_contenthash()), $event->other);
        $this->assertEventContextNotUsed($event);
        $url = new \moodle_url('/totara/userdata/exports.php', array('userid' => $user->id));
        $this->assertEquals($url, $event->get_url());

    }
}