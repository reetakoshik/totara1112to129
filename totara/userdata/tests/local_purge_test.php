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
use totara_userdata\userdata\target_user;
use totara_userdata\local\purge;
use totara_userdata\local\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the purge class.
 */
class totara_userdata_local_purge_testcase extends advanced_testcase {
    public function test_get_origins() {
        $origins = purge::get_origins();
        $this->assertCount(4, $origins);
        $this->assertArrayHasKey('manual', $origins);
        $this->assertArrayHasKey('deleted', $origins);
        $this->assertArrayHasKey('suspended', $origins);
        $this->assertArrayHasKey('other', $origins);
    }

    public function test_get_purgeable_item_classes() {
        $classes = purge::get_purgeable_item_classes(target_user::STATUS_ACTIVE);
        foreach ($classes as $class) {
            /** @var item $class this is not a real instance */
            $this->assertTrue($class::is_purgeable(target_user::STATUS_ACTIVE));
        }
        $classes = purge::get_purgeable_item_classes(target_user::STATUS_SUSPENDED);
        foreach ($classes as $class) {
            /** @var item $class this is not a real instance */
            $this->assertTrue($class::is_purgeable(target_user::STATUS_SUSPENDED));
        }
        $classes = purge::get_purgeable_item_classes(target_user::STATUS_DELETED);
        foreach ($classes as $class) {
            /** @var item $class this is not a real instance */
            $this->assertTrue($class::is_purgeable(target_user::STATUS_DELETED));
        }
    }

    public function test_get_purgeable_items_grouped_list() {
        $maincomponents = purge::get_purgeable_items_grouped_list(target_user::STATUS_ACTIVE);
        foreach ($maincomponents as $maincomponent => $classes) {
            foreach ($classes as $class) {
                /** @var item $class this is not a real instance */
                $this->assertTrue($class::is_purgeable(target_user::STATUS_ACTIVE));
            }
        }
        $maincomponents = purge::get_purgeable_items_grouped_list(target_user::STATUS_SUSPENDED);
        foreach ($maincomponents as $maincomponent => $classes) {
            foreach ($classes as $class) {
                /** @var item $class this is not a real instance */
                $this->assertTrue($class::is_purgeable(target_user::STATUS_SUSPENDED));
            }
        }
        $maincomponents = purge::get_purgeable_items_grouped_list(target_user::STATUS_DELETED);
        foreach ($maincomponents as $maincomponent => $classes) {
            foreach ($classes as $class) {
                /** @var item $class this is not a real instance */
                $this->assertTrue($class::is_purgeable(target_user::STATUS_DELETED));
            }
        }
    }

    public function test_is_execution_pending() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typeactive = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $typesuspended = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowsuspended' => 1, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $typedeleted = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowdeleted' => 1, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $activeuser = $this->getDataGenerator()->create_user(array('middlename' => 'Active', 'city' => 'Somewhere'));
        $suspendeduser = $this->getDataGenerator()->create_user(array('suspended' => 1, 'middlename' => 'Suspended', 'city' => 'Elsewhere'));
        $deleteduser = $this->getDataGenerator()->create_user(array('deleted' => 1, 'middlename' => 'Deleted', 'city' => 'Nowhere'));
        $otheruser = $this->getDataGenerator()->create_user();
        util::sync_totara_userdata_user_table();

        $this->assertFalse(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));

        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive->id, 'manual');
        $this->assertTrue(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $purge->timestarted = time();
        $purge->timefinished = time();
        $purge->result = item::RESULT_STATUS_SUCCESS;
        $DB->update_record('totara_userdata_purge', $purge);
        $this->assertFalse(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));

        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive->id, 'manual');
        $this->assertTrue(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $purge->timestarted = time();
        $purge->timefinished = time();
        $purge->result = item::RESULT_STATUS_ERROR;
        $DB->update_record('totara_userdata_purge', $purge);
        $this->assertFalse(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));

        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive->id, 'manual');
        $this->assertTrue(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $purge->result = item::RESULT_STATUS_CANCELLED;
        $DB->update_record('totara_userdata_purge', $purge);
        $this->assertFalse(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));

        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive->id, 'manual');
        $this->assertTrue(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $purge->timestarted = time();
        $purge->result = item::RESULT_STATUS_TIMEDOUT;
        $DB->update_record('totara_userdata_purge', $purge);
        $this->assertFalse(purge::is_execution_pending('manual', $typeactive->id, $activeuser->id, $syscontext->id));
    }

    public function test_purge_items() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typeactive = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $typesuspended = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowsuspended' => 1, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $typedeleted = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowdeleted' => 1, 'items' => 'core_user-additionalnames,core_user-otherfields'));
        $activeuser = $this->getDataGenerator()->create_user(array('middlename' => 'Active', 'city' => 'Somewhere'));
        $suspendeduser = $this->getDataGenerator()->create_user(array('suspended' => 1, 'middlename' => 'Suspended', 'city' => 'Elsewhere'));
        $deleteduser = $this->getDataGenerator()->create_user(array('deleted' => 1, 'middlename' => 'Deleted', 'city' => 'Nowhere'));
        $otheruser = $this->getDataGenerator()->create_user();
        util::sync_totara_userdata_user_table();

        $this->setUser($otheruser);
        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive->id, 'manual');
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $purge->timestarted = (string)time();
        $DB->update_record('totara_userdata_purge', $purge);
        $this->setCurrentTimeStart();
        $result = purge::purge_items($purge);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $newpurge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertEquals($purge, $newpurge);
        $items = $DB->get_records('totara_userdata_purge_item', array('purgeid' => $purge->id), 'component ASC,name ASC');
        $items = array_combine(array_column($items, 'name'), $items);
        $this->assertCount(2, $items);
        $this->assertSame('core_user', $items['additionalnames']->component);
        $this->assertTimeCurrent($items['additionalnames']->timestarted);
        $this->assertGreaterThanOrEqual($items['additionalnames']->timestarted, $items['additionalnames']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['additionalnames']->result);
        $this->assertSame('core_user', $items['otherfields']->component);
        $this->assertTimeCurrent($items['otherfields']->timestarted);
        $this->assertGreaterThanOrEqual($items['otherfields']->timestarted, $items['otherfields']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['otherfields']->result);
        $newactiveuser = $DB->get_record('user', array('id' => $activeuser->id));
        $this->assertNull($newactiveuser->middlename);
        $this->assertSame('', $newactiveuser->city);

        $this->setUser($otheruser);
        $purgeid = manager::create_purge($suspendeduser->id, $syscontext->id, $typesuspended->id, 'suspended');
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $purge->timestarted = (string)time();
        $DB->update_record('totara_userdata_purge', $purge);
        $this->setCurrentTimeStart();
        $result = purge::purge_items($purge);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $newpurge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertEquals($purge, $newpurge);
        $items = $DB->get_records('totara_userdata_purge_item', array('purgeid' => $purge->id), 'component ASC,name ASC');
        $items = array_combine(array_column($items, 'name'), $items);
        $this->assertCount(2, $items);
        $this->assertSame('core_user', $items['additionalnames']->component);
        $this->assertTimeCurrent($items['additionalnames']->timestarted);
        $this->assertGreaterThanOrEqual($items['additionalnames']->timestarted, $items['additionalnames']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['additionalnames']->result);
        $this->assertSame('core_user', $items['otherfields']->component);
        $this->assertTimeCurrent($items['otherfields']->timestarted);
        $this->assertGreaterThanOrEqual($items['otherfields']->timestarted, $items['otherfields']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['otherfields']->result);
        $newsuspendeduser = $DB->get_record('user', array('id' => $suspendeduser->id));
        $this->assertNull($newsuspendeduser->middlename);
        $this->assertSame('', $newsuspendeduser->city);

        $this->setUser($otheruser);
        $purgeid = manager::create_purge($deleteduser->id, $syscontext->id, $typedeleted->id, 'deleted');
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $purge->timestarted = (string)time();
        $DB->update_record('totara_userdata_purge', $purge);
        $this->setCurrentTimeStart();
        $result = purge::purge_items($purge);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $newpurge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertEquals($purge, $newpurge);
        $items = $DB->get_records('totara_userdata_purge_item', array('purgeid' => $purge->id), 'component ASC,name ASC');
        $items = array_combine(array_column($items, 'name'), $items);
        $this->assertCount(2, $items);
        $this->assertSame('core_user', $items['additionalnames']->component);
        $this->assertTimeCurrent($items['additionalnames']->timestarted);
        $this->assertGreaterThanOrEqual($items['additionalnames']->timestarted, $items['additionalnames']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['additionalnames']->result);
        $this->assertSame('core_user', $items['otherfields']->component);
        $this->assertTimeCurrent($items['otherfields']->timestarted);
        $this->assertGreaterThanOrEqual($items['otherfields']->timestarted, $items['otherfields']->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $items['otherfields']->result);
        $newsuspendeduser = $DB->get_record('user', array('id' => $suspendeduser->id));
        $this->assertNull($newsuspendeduser->middlename);
        $this->assertSame('', $newsuspendeduser->city);
    }
}
