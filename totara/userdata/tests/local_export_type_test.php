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

use totara_userdata\local\export_type;
use totara_userdata\userdata\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the export type class.
 */
class totara_userdata_local_export_type_testcase extends advanced_testcase {
    public function test_prepare_for_add() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $data = export_type::prepare_for_add(0);
        $this->assertSame('0', $data->id);
        $this->assertSame('', $data->fullname);
        $this->assertSame('', $data->idnumber);
        $this->assertSame('', $data->description);
        $this->assertSame('0', $data->includefiledir);
        $this->assertSame(array(), $data->availablefor);

        $type = $generator->create_export_type(array('allowself' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi',
            'items' => 'core_user-additionalnames,core_user-otherfields'));
        $data = export_type::prepare_for_add($type->id);
        $this->assertSame('0', $data->id);
        $this->assertSame('Copy of XX name', $data->fullname);
        $this->assertSame('', $data->idnumber);
        $this->assertSame($type->description, $data->description);
        $this->assertSame(array('allowself'), $data->availablefor);
        $this->assertSame(array('core_user-additionalnames', 'core_user-otherfields'), $data->grp_core_user);
    }

    public function test_prepare_for_update() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1, 'includefiledir' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi',
            'items' => 'core_user-additionalnames,core_user-otherfields'));

        $data = export_type::prepare_for_update($type->id);
        $this->assertSame($type->id, $data->id);
        $this->assertSame($type->fullname, $data->fullname);
        $this->assertSame($type->idnumber, $data->idnumber);
        $this->assertSame($type->description, $data->description);
        $this->assertSame($type->includefiledir, $data->includefiledir);
        $this->assertSame(array('allowself'), $data->availablefor);
        $this->assertSame(array('core_user-additionalnames', 'core_user-otherfields'), $data->grp_core_user);
    }

    public function test_is_deletable() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_export_type(array('allowself' => 1));
        $user = $this->getDataGenerator()->create_user();
        $syscontext = context_system::instance();

        $this->assertTrue(export_type::is_deletable($type->id));

        manager::create_export($user->id, $syscontext->id, $type->id, 'self');
        $this->assertFalse(export_type::is_deletable($type->id));
    }

    public function test_delete() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');
        $type = $generator->create_export_type(array('allowself' => 1));

        $this->assertTrue($DB->record_exists('totara_userdata_export_type', array('id' => $type->id)));
        export_type::delete($type->id);
        $this->assertFalse($DB->record_exists('totara_userdata_export_type', array('id' => $type->id)));
    }

    public function test_export_edit_add() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $othertype = $generator->create_export_type(array('allowself' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi', 'timecreated' => '1010', 'usercreated' => $user->id,
            'items' => 'core_user-additionalnames,core_user-otherfields'));

        $data = export_type::prepare_for_add($othertype->id);
        $data->fullname = 'YY';
        $data->idnumber = 'xxxx';
        $data->description = 'haha';
        $data->includefiledir = '1';
        $data->availablefor = array('allowself', 'allowdeleted');
        $data->grp_core_user = array('core_user-additionalnames', 'core_user-username');
        $data->repurge = '0';

        $this->setUser($otheruser);
        $this->setCurrentTimeStart();
        $newtype = export_type::edit($data);
        $this->assertSame($data->fullname, $newtype->fullname);
        $this->assertSame($data->idnumber, $newtype->idnumber);
        $this->assertSame($data->includefiledir, $newtype->includefiledir);
        $this->assertSame($data->description, $newtype->description);
        $this->assertSame('1', $newtype->allowself);
        $this->assertSame($otheruser->id, $newtype->usercreated);
        $this->assertTimeCurrent($newtype->timecreated);
        $this->assertTimeCurrent($newtype->timechanged);
        $additionanames = $DB->get_record('totara_userdata_export_type_item', array('exporttypeid' => $newtype->id, 'component' => 'core_user', 'name' => 'additionalnames', 'exportdata' => 1), '*', MUST_EXIST);
        $this->assertTimeCurrent($additionanames->timechanged);
        $username = $DB->get_record('totara_userdata_export_type_item', array('exporttypeid' => $newtype->id, 'component' => 'core_user', 'name' => 'username', 'exportdata' => 1), '*', MUST_EXIST);
        $this->assertTimeCurrent($username->timechanged);
        $otherfields = $DB->get_record('totara_userdata_export_type_item', array('exporttypeid' => $newtype->id, 'component' => 'core_user', 'name' => 'otherfields', 'exportdata' => 0), '*', MUST_EXIST);
        $this->assertTimeCurrent($otherfields->timechanged);
    }

    public function test_export_edit_update() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $type = $generator->create_export_type(array('allowself' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi', 'timecreated' => '1010', 'usercreated' => $user->id,
            'items' => 'core_user-additionalnames,core_user-otherfields'));
        $this->assertSame('1010', $type->timecreated);
        $this->assertSame('1010', $type->timechanged);

        $data = export_type::prepare_for_update($type->id);
        $data->fullname = 'YY';
        $data->idnumber = 'xxxx';
        $data->description = 'haha';
        $data->includefiledir = '1';
        $data->availablefor = array('allowself');
        $data->grp_core_user = array('core_user-additionalnames', 'core_user-username');

        $this->setUser($otheruser);
        $this->setCurrentTimeStart();
        $updatedtype = export_type::edit($data);
        $this->assertSame($data->fullname, $updatedtype->fullname);
        $this->assertSame($data->idnumber, $updatedtype->idnumber);
        $this->assertSame($data->description, $updatedtype->description);
        $this->assertSame($data->includefiledir, $updatedtype->includefiledir);
        $this->assertSame('1', $updatedtype->allowself);
        $this->assertSame($type->usercreated, $updatedtype->usercreated);
        $this->assertSame($type->timecreated, $updatedtype->timecreated);
        $this->assertTimeCurrent($updatedtype->timechanged);
        $additionanames = $DB->get_record('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'component' => 'core_user', 'name' => 'additionalnames', 'exportdata' => 1), '*', MUST_EXIST);
        $this->assertSame($type->timechanged, $additionanames->timechanged);
        $username = $DB->get_record('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'component' => 'core_user', 'name' => 'username', 'exportdata' => 1), '*', MUST_EXIST);
        $this->assertTimeCurrent($username->timechanged);
        $otherfields = $DB->get_record('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'component' => 'core_user', 'name' => 'otherfields', 'exportdata' => 0), '*', MUST_EXIST);
        $this->assertTimeCurrent($otherfields->timechanged);

        // Simulate item removal during upgrade.
        $oldfield = clone($username);
        unset($oldfield->id);
        $oldfield->name = 'xxxyyy';
        $DB->insert_record('totara_userdata_export_type_item', $oldfield);
        $this->assertTrue($DB->record_exists('totara_userdata_export_type_item', array('component' => $oldfield->component, 'name' => $oldfield->name)));
        $data = export_type::prepare_for_update($type->id);
        export_type::edit($data);
        $this->assertFalse($DB->record_exists('totara_userdata_export_type_item', array('component' => $oldfield->component, 'name' => $oldfield->name)));
    }

    public function test_get_new_items() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type1 = $generator->create_export_type(array('items' => 'core_user-additionalnames,core_user-otherfields'));
        $type2 = $generator->create_export_type(array('items' => ''));

        $this->assertSame(array(), export_type::get_new_items($type1->id));
        $this->assertSame(array(), export_type::get_new_items($type2->id));

        $DB->delete_records('totara_userdata_export_type_item', array('name' => 'username', 'component' => 'core_user'));
        $this->assertSame(array('core_user-username' => 'core_user\\userdata\\username'), export_type::get_new_items($type1->id));
        $this->assertSame(array('core_user-username' => 'core_user\\userdata\\username'), export_type::get_new_items($type2->id));
    }

    public function test_trigger_self_export() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');
        $type = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-additionalnames'));
        $user = $this->getDataGenerator()->create_user(array('middlename' => 'midddle'));
        $syscontext = context_system::instance();

        $this->setUser($user);
        $taskid = export_type::trigger_self_export($type->id);
        $taskrecord = $DB->get_record('task_adhoc', array('id' => $taskid), '*', MUST_EXIST);
        $task = \core\task\manager::adhoc_task_from_record($taskrecord);
        $export = $DB->get_record('totara_userdata_export', array('id' => $task->get_custom_data()), '*', MUST_EXIST);
        $this->assertSame('self', $export->origin);
        $this->assertSame($user->id, $export->userid);
        $this->assertSame((string)$syscontext->id, $export->contextid);
        $this->assertNull($export->result);
        $this->assertNull($export->timestarted);
        $this->assertNull($export->timefinished);
    }
}
