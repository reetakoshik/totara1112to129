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

use totara_userdata\userdata\item;
use totara_userdata\userdata\manager;
use totara_userdata\local\export;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the export class.
 */
class totara_userdata_local_export_testcase extends advanced_testcase {
    public function test_get_origins() {
        $origins = export::get_origins();
        $this->assertCount(2, $origins);
        $this->assertArrayHasKey('self', $origins);
        $this->assertArrayHasKey('other', $origins);
    }

    public function test_get_exportable_item_classes() {
        $classes = export::get_exportable_item_classes();
        foreach ($classes as $class) {
            /** @var item $class this is not a real instance */
            $this->assertTrue($class::is_exportable());
        }
    }

    public function test_get_exportable_items_grouped_list() {
        $maincomponents = export::get_exportable_items_grouped_list();
        foreach ($maincomponents as $maincomponent => $classes) {
            foreach ($classes as $class) {
                /** @var item $class this is not a real instance */
                $this->assertTrue($class::is_exportable());
            }
        }
    }

    public function test_export_items() {
        global $DB;
        $this->resetAfterTest();

        $fs = get_file_storage();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-names,core_user-username'));
        $user = $this->getDataGenerator()->create_user();
        $syscontext = context_system::instance();
        $this->setUser($user);
        $exportid = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $export->timestarted = (string)time();
        $DB->update_record('totara_userdata_export', $export);

        $this->setCurrentTimeStart();
        $result = export::export_items($export);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $newexport = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertEquals($export, $newexport);
        $exportfile = $fs->get_file(SYSCONTEXTID, 'totara_userdata', 'export', $exportid, '/', 'export.tgz');
        $this->assertInstanceOf('stored_file', $exportfile);
        $this->assertGreaterThan(100, $exportfile->get_filesize());
        $items = $DB->get_records('totara_userdata_export_item', array('exportid' => $export->id), 'component ASC,name ASC');
        $items = array_combine(array_column($items, 'name'), $items);
        $this->assertCount(2, $items);
        $this->assertSame('core_user', $items['names']->component);
        $this->assertTimeCurrent($items['names']->timestarted);
        $this->assertGreaterThanOrEqual($items['names']->timestarted, $items['names']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['names']->result);
        $this->assertSame('core_user', $items['username']->component);
        $this->assertTimeCurrent($items['username']->timestarted);
        $this->assertGreaterThanOrEqual($items['username']->timestarted, $items['username']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['username']->result);
    }

    public function test_get_result_file_record() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-names,core_user-username'));
        $user = $this->getDataGenerator()->create_user();
        $syscontext = context_system::instance();
        $this->setUser($user);
        $exportid = manager::create_export($user->id, $syscontext->id, $type->id, 'self');

        $filerecord = export::get_result_file_record($exportid);
        $this->assertFalse($filerecord);

        $result = manager::execute_export($exportid);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $filerecord = export::get_result_file_record($exportid);
        $this->assertInstanceOf('stdClass', $filerecord);
        $this->assertSame('export.tgz', $filerecord->filename);
        $this->assertSame((string)$exportid, $filerecord->itemid);

        // Make the file nearly expired.
        $DB->execute("UPDATE {totara_userdata_export} SET timefinished = timefinished + 100 - :maxlifetime WHERE id = :id", array('id' => $exportid, 'maxlifetime' => export::MAX_FILE_AVAILABILITY_TIME));
        $filerecord = export::get_result_file_record($exportid);
        $this->assertInstanceOf('stdClass', $filerecord);
        $this->assertSame('export.tgz', $filerecord->filename);

        // Make the file expired.
        $DB->execute("UPDATE {totara_userdata_export} SET timefinished = timefinished - 200 WHERE id = :id", array('id' => $exportid));
        $filerecord = export::get_result_file_record($exportid);
        $this->assertFalse($filerecord);
    }

    public function test_internal_cleanup() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-names,core_user-username'));
        $user = $this->getDataGenerator()->create_user();
        $syscontext = context_system::instance();
        $this->setUser($user);

        $exportidfinished = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $result = manager::execute_export($exportidfinished);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertNotFalse(export::get_result_file_record($exportidfinished));
        $exportfinished = $DB->get_record('totara_userdata_export', array('id' => $exportidfinished));

        $exportidpending = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $DB->set_field('totara_userdata_export', 'timestarted', time() - export::MAX_TOTAL_EXECUTION_TIME + 100, array('id' => $exportidpending));
        $exportpending = $DB->get_record('totara_userdata_export', array('id' => $exportidpending));

        $exportidtimedout = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $DB->set_field('totara_userdata_export', 'timestarted', time() - export::MAX_TOTAL_EXECUTION_TIME - 100, array('id' => $exportidtimedout));
        $exporttimedout = $DB->get_record('totara_userdata_export', array('id' => $exportidtimedout));
        $this->assertNull($exporttimedout->result);
        $this->assertNull($exporttimedout->timefinished);

        $exportidstalefile = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $result = manager::execute_export($exportidstalefile);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertNotFalse(export::get_result_file_record($exportidstalefile));
        $DB->set_field('totara_userdata_export', 'timestarted', time() - export::MAX_FILE_AVAILABILITY_TIME - 1000, array('id' => $exportidstalefile));
        $DB->set_field('totara_userdata_export', 'timefinished', time() - export::MAX_FILE_AVAILABILITY_TIME - 100, array('id' => $exportidstalefile));
        $exportstalefile = $DB->get_record('totara_userdata_export', array('id' => $exportidstalefile));

        $this->setCurrentTimeStart();
        export::internal_cleanup();

        $newexportfinished = $DB->get_record('totara_userdata_export', array('id' => $exportidfinished));
        $this->assertEquals($exportfinished, $newexportfinished);
        $this->assertNotFalse(export::get_result_file_record($exportidfinished));

        $newexportpending = $DB->get_record('totara_userdata_export', array('id' => $exportidpending));
        $this->assertEquals($exportpending, $newexportpending);
        $this->assertFalse(export::get_result_file_record($exportidpending));

        $newexporttimedout = $DB->get_record('totara_userdata_export', array('id' => $exportidtimedout));
        $this->assertSame((string)item::RESULT_STATUS_TIMEDOUT, $newexporttimedout->result);
        $exporttimedout->result = $newexporttimedout->result;
        $this->assertEquals($exporttimedout, $newexporttimedout);

        $newexportstalefile = $DB->get_record('totara_userdata_export', array('id' => $exportidstalefile));
        $this->assertEquals($exportstalefile, $newexportstalefile);
        $this->assertFalse(export::get_result_file_record($exportidstalefile));
    }

    public function test_get_my_last_export() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-names,core_user-username'));
        $syscontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $lastexport = export::get_my_last_export();
        $this->assertFalse($lastexport);

        $exportid1 = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $lastexport = export::get_my_last_export();
        $this->assertEquals($exportid1, $lastexport->id);

        $exportid2 = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $lastexport = export::get_my_last_export();
        $this->assertEquals($exportid2, $lastexport->id);

        $exportid3 = manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $lastexport = export::get_my_last_export();
        $this->assertEquals($exportid3, $lastexport->id);
    }

    public function test_is_export_file_available() {
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
        $this->assertNotFalse(export::get_result_file_record($exportid));
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid));

        // Set up everything to get true result now.
        $role = $DB->get_record('role', array('shortname' => 'user'), '*', MUST_EXIST);
        assign_capability('totara/userdata:exportself', CAP_ALLOW, $role->id, $syscontext->id);
        $this->setUser($user);
        set_config('selfexportenable', 1, 'totara_userdata');
        $this->assertTrue(export::is_export_file_available($export));

        // Now lets break try other user.
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setUser($otheruser);
        $this->assertFalse(export::is_export_file_available($export));
        $this->setUser($user);
        $this->assertTrue(export::is_export_file_available($export));

        set_config('selfexportenable', 0, 'totara_userdata');
        $this->assertFalse(export::is_export_file_available($export));
        set_config('selfexportenable', 1, 'totara_userdata');
        $this->assertTrue(export::is_export_file_available($export));

        $DB->set_field('totara_userdata_export_type', 'allowself', 0, array('id' => $type->id));
        $this->assertFalse(export::is_export_file_available($export));
        $DB->set_field('totara_userdata_export_type', 'allowself', 1, array('id' => $type->id));
        $this->assertTrue(export::is_export_file_available($export));

        $DB->set_field('totara_userdata_export', 'timefinished', time() - export::MAX_FILE_AVAILABILITY_TIME - 100, array('id' => $export->id));
        $this->assertFalse(export::is_export_file_available($export));
    }
}
