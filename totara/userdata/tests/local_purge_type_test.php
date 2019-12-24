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

use totara_userdata\local\purge_type;
use totara_userdata\userdata\manager;
use totara_userdata\userdata\target_user;
use totara_userdata\local\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the purge type class.
 */
class totara_userdata_local_purge_type_testcase extends advanced_testcase {
    public function test_prepare_for_add() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $data = purge_type::prepare_for_add(target_user::STATUS_ACTIVE, 0);
        $this->assertSame('0', $data->id);
        $this->assertSame((string)target_user::STATUS_ACTIVE, $data->userstatus);
        $this->assertSame('', $data->fullname);
        $this->assertSame('', $data->idnumber);
        $this->assertSame('', $data->description);
        $this->assertSame(array(), $data->availablefor);

        $type = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 1, 'allowdeleted' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi',
            'items' => 'core_user-additionalnames,core_user-otherfields'));
        $data = purge_type::prepare_for_add(target_user::STATUS_SUSPENDED, $type->id);
        $this->assertSame('0', $data->id);
        $this->assertSame((string)target_user::STATUS_DELETED, $data->userstatus);
        $this->assertSame('Copy of XX name', $data->fullname);
        $this->assertSame('', $data->idnumber);
        $this->assertSame($type->description, $data->description);
        $this->assertSame(array('allowmanual', 'allowdeleted'), $data->availablefor);
        $this->assertSame(array('core_user-additionalnames', 'core_user-otherfields'), $data->grp_core_user);
    }

    public function test_prepare_for_update() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 1, 'allowdeleted' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi',
            'items' => 'core_user-additionalnames,core_user-otherfields'));

        $data = purge_type::prepare_for_update($type->id);
        $this->assertSame($type->id, $data->id);
        $this->assertSame($type->userstatus, $data->userstatus);
        $this->assertSame($type->fullname, $data->fullname);
        $this->assertSame($type->idnumber, $data->idnumber);
        $this->assertSame($type->description, $data->description);
        $this->assertSame(array('allowmanual', 'allowdeleted'), $data->availablefor);
        $this->assertSame(array('core_user-additionalnames', 'core_user-otherfields'), $data->grp_core_user);
    }

    public function test_is_deletable() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typeactive = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1));
        $typeasuspedned = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowsuspended' => 1));
        $typedeleted = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowdeleted' => 1));
        $user = $this->getDataGenerator()->create_user();
        $extra = util::get_user_extras($user->id);
        $syscontext = context_system::instance();

        $this->assertTrue(purge_type::is_deletable($typeactive->id));
        $this->assertTrue(purge_type::is_deletable($typeasuspedned->id));
        $this->assertTrue(purge_type::is_deletable($typedeleted->id));

        set_config('defaultsuspendedpurgetypeid', $typeasuspedned->id, 'totara_userdata');
        $this->assertFalse(purge_type::is_deletable($typeasuspedned->id));
        set_config('defaultsuspendedpurgetypeid', '', 'totara_userdata');
        $this->assertTrue(purge_type::is_deletable($typeasuspedned->id));

        set_config('defaultdeletedpurgetypeid', $typedeleted->id, 'totara_userdata');
        $this->assertFalse(purge_type::is_deletable($typedeleted->id));
        set_config('defaultdeletedpurgetypeid', '', 'totara_userdata');
        $this->assertTrue(purge_type::is_deletable($typedeleted->id));

        $extra->suspendedpurgetypeid = $typeasuspedned->id;
        $DB->update_record('totara_userdata_user', $extra);
        $this->assertFalse(purge_type::is_deletable($typeasuspedned->id));
        $extra->suspendedpurgetypeid = null;
        $DB->update_record('totara_userdata_user', $extra);
        $this->assertTrue(purge_type::is_deletable($typeasuspedned->id));

        $extra->deletedpurgetypeid = $typeasuspedned->id;
        $DB->update_record('totara_userdata_user', $extra);
        $this->assertFalse(purge_type::is_deletable($typeasuspedned->id));
        $extra->deletedpurgetypeid = null;
        $DB->update_record('totara_userdata_user', $extra);
        $this->assertTrue(purge_type::is_deletable($typeasuspedned->id));

        manager::create_purge($user->id, $syscontext->id, $typeactive->id, 'manual');
        $this->assertFalse(purge_type::is_deletable($typeactive->id));
    }

    public function test_delete() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');
        $type = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1));

        $this->assertTrue($DB->record_exists('totara_userdata_purge_type', array('id' => $type->id)));
        purge_type::delete($type->id);
        $this->assertFalse($DB->record_exists('totara_userdata_purge_type', array('id' => $type->id)));
    }

    public function test_purge_edit_add() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $othertype = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 1, 'allowdeleted' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi', 'timecreated' => '1010', 'usercreated' => $user->id,
            'items' => 'core_user-additionalnames,core_user-otherfields'));

        $data = purge_type::prepare_for_add(0, $othertype->id);
        $data->fullname = 'YY';
        $data->idnumber = 'xxxx';
        $data->description = 'haha';
        $data->availablefor = array('allowmanual', 'allowdeleted');
        $data->grp_core_user = array('core_user-additionalnames', 'core_user-username');
        $data->repurge = '0';

        $this->setUser($otheruser);
        $this->setCurrentTimeStart();
        $newtype = purge_type::edit($data);
        $this->assertSame($data->fullname, $newtype->fullname);
        $this->assertSame($data->idnumber, $newtype->idnumber);
        $this->assertSame($data->description, $newtype->description);
        $this->assertSame('1', $newtype->allowmanual);
        $this->assertSame('0', $newtype->allowsuspended);
        $this->assertSame('1', $newtype->allowdeleted);
        $this->assertSame($otheruser->id, $newtype->usercreated);
        $this->assertTimeCurrent($newtype->timecreated);
        $this->assertTimeCurrent($newtype->timechanged);
        $additionanames = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $newtype->id, 'component' => 'core_user', 'name' => 'additionalnames', 'purgedata' => 1), '*', MUST_EXIST);
        $this->assertTimeCurrent($additionanames->timechanged);
        $username = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $newtype->id, 'component' => 'core_user', 'name' => 'username', 'purgedata' => 1), '*', MUST_EXIST);
        $this->assertTimeCurrent($username->timechanged);
        $otherfields = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $newtype->id, 'component' => 'core_user', 'name' => 'otherfields', 'purgedata' => 0), '*', MUST_EXIST);
        $this->assertTimeCurrent($otherfields->timechanged);
    }

    public function test_purge_edit_update() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $type = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 1, 'allowdeleted' => 1,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi', 'timecreated' => '1010', 'usercreated' => $user->id,
            'items' => 'core_user-additionalnames,core_user-otherfields'));
        $this->assertSame('1010', $type->timecreated);
        $this->assertSame('1010', $type->timechanged);

        $data = purge_type::prepare_for_update($type->id);
        $data->fullname = 'YY';
        $data->idnumber = 'xxxx';
        $data->description = 'haha';
        $data->availablefor = array('allowmanual');
        $data->grp_core_user = array('core_user-additionalnames', 'core_user-username');
        $data->repurge = '0';

        $this->setUser($otheruser);
        $this->setCurrentTimeStart();
        $updatedtype = purge_type::edit($data);
        $this->assertSame($data->fullname, $updatedtype->fullname);
        $this->assertSame($data->idnumber, $updatedtype->idnumber);
        $this->assertSame($data->description, $updatedtype->description);
        $this->assertSame('1', $updatedtype->allowmanual);
        $this->assertSame('0', $updatedtype->allowsuspended);
        $this->assertSame('0', $updatedtype->allowdeleted);
        $this->assertSame($type->usercreated, $updatedtype->usercreated);
        $this->assertSame($type->timecreated, $updatedtype->timecreated);
        $this->assertSame($type->timechanged, $updatedtype->timechanged);
        $additionanames = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'component' => 'core_user', 'name' => 'additionalnames', 'purgedata' => 1), '*', MUST_EXIST);
        $this->assertSame($type->timechanged, $additionanames->timechanged);
        $username = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'component' => 'core_user', 'name' => 'username', 'purgedata' => 1), '*', MUST_EXIST);
        $this->assertTimeCurrent($username->timechanged);
        $otherfields = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'component' => 'core_user', 'name' => 'otherfields', 'purgedata' => 0), '*', MUST_EXIST);
        $this->assertTimeCurrent($otherfields->timechanged);

        $data->repurge = '1';
        $this->setCurrentTimeStart();
        $updatedtype = purge_type::edit($data);
        $this->assertTimeCurrent($updatedtype->timechanged);

        // Simulate item removal during upgrade.
        $oldfield = clone($username);
        unset($oldfield->id);
        $oldfield->name = 'xxxyyy';
        $DB->insert_record('totara_userdata_purge_type_item', $oldfield);
        $this->assertTrue($DB->record_exists('totara_userdata_purge_type_item', array('component' => $oldfield->component, 'name' => $oldfield->name)));
        $data = purge_type::prepare_for_update($type->id);
        purge_type::edit($data);
        $this->assertFalse($DB->record_exists('totara_userdata_purge_type_item', array('component' => $oldfield->component, 'name' => $oldfield->name)));
    }

    public function test_purge_edit_update_active() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $type = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 0,
            'fullname' => 'XX name', 'idnumber' => 'idxxx', 'description' => 'hihi', 'timecreated' => '1010', 'usercreated' => $user->id,
            'items' => 'core_user-additionalnames,core_user-otherfields'));
        $this->assertSame('1010', $type->timecreated);
        $this->assertSame('1010', $type->timechanged);

        $data = purge_type::prepare_for_update($type->id);
        $data->fullname = 'YY';
        $data->idnumber = 'xxxx';
        $data->description = 'haha';
        $data->availablefor = array('allowmanual');
        $data->grp_core_user = array('core_user-additionalnames', 'core_user-picture');

        $this->setUser($otheruser);
        $this->setCurrentTimeStart();
        $updatedtype = purge_type::edit($data);
        $this->assertSame($data->fullname, $updatedtype->fullname);
        $this->assertSame($data->idnumber, $updatedtype->idnumber);
        $this->assertSame($data->description, $updatedtype->description);
        $this->assertSame('1', $updatedtype->allowmanual);
        $this->assertSame('0', $updatedtype->allowsuspended);
        $this->assertSame('0', $updatedtype->allowdeleted);
        $this->assertSame($type->usercreated, $updatedtype->usercreated);
        $this->assertSame($type->timecreated, $updatedtype->timecreated);
        $this->assertTimeCurrent($updatedtype->timechanged);
        $additionanames = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'component' => 'core_user', 'name' => 'additionalnames', 'purgedata' => 1), '*', MUST_EXIST);
        $this->assertSame($type->timechanged, $additionanames->timechanged);
        $username = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'component' => 'core_user', 'name' => 'picture', 'purgedata' => 1), '*', MUST_EXIST);
        $this->assertTimeCurrent($username->timechanged);
        $otherfields = $DB->get_record('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'component' => 'core_user', 'name' => 'otherfields', 'purgedata' => 0), '*', MUST_EXIST);
        $this->assertTimeCurrent($otherfields->timechanged);
    }

    public function test_count_repurged_users() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $suspendedtype = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED));
        $deletedtype = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED));
        $activetype = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE));

        $useractive = $this->getDataGenerator()->create_user();
        $usersuspended = $this->getDataGenerator()->create_user(array('suspended' => 1));
        $userdeleted = $this->getDataGenerator()->create_user(array('suspended' => 1, 'deleted' => 1));

        $extra = util::get_user_extras($useractive->id);
        $extra->suspendedpurgetypeid = $suspendedtype->id;
        $extra->deletedpurgetypeid = $deletedtype->id;
        $DB->update_record('totara_userdata_user', $extra);

        $extra = util::get_user_extras($usersuspended->id);
        $extra->suspendedpurgetypeid = $suspendedtype->id;
        $extra->deletedpurgetypeid = $deletedtype->id;
        $DB->update_record('totara_userdata_user', $extra);

        $extra = util::get_user_extras($userdeleted->id);
        $extra->suspendedpurgetypeid = $suspendedtype->id;
        $extra->deletedpurgetypeid = $deletedtype->id;
        $DB->update_record('totara_userdata_user', $extra);

        $this->assertEquals(1, purge_type::count_repurged_users($suspendedtype->id));
        $this->assertEquals(1, purge_type::count_repurged_users($deletedtype->id));
        $this->assertEquals(0, purge_type::count_repurged_users($activetype->id));
    }

    public function test_get_new_items() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typeactive = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $typesuspended = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $typedeleted = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'items' => 'core-email'));

        $this->assertSame(array(), purge_type::get_new_items($typeactive->id));
        $this->assertSame(array(), purge_type::get_new_items($typesuspended->id));
        $this->assertSame(array(), purge_type::get_new_items($typedeleted->id));

        $DB->delete_records('totara_userdata_purge_type_item', array('name' => 'username', 'component' => 'core_user'));
        $this->assertSame(array(), purge_type::get_new_items($typeactive->id));
        $this->assertSame(array(), purge_type::get_new_items($typesuspended->id));
        $this->assertSame(array('core_user-username' => 'core_user\\userdata\\username'), purge_type::get_new_items($typedeleted->id));
    }

    public function test_trigger_manual_purge() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');
        $typeactive = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1, 'items' => 'core_user-additionalnames'));
        $activeuser = $this->getDataGenerator()->create_user(array('middlename' => 'midddle'));
        $creator = $this->getDataGenerator()->create_user();
        $syscontext = context_system::instance();
        $this->assertSame('midddle', $activeuser->middlename);

        $this->setUser($creator);
        $taskid = purge_type::trigger_manual_purge($typeactive->id, $activeuser->id, $syscontext->id);
        $taskrecord = $DB->get_record('task_adhoc', array('id' => $taskid), '*', MUST_EXIST);
        $task = \core\task\manager::adhoc_task_from_record($taskrecord);
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $task->get_custom_data()), '*', MUST_EXIST);
        $this->assertSame('manual', $purge->origin);
        $this->assertSame($activeuser->id, $purge->userid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertNull($purge->result);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
    }
}
