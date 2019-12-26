<?php
/*
 * This file is part of Totara LMS
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use totara_core\totara\menu\helper;
use totara_core\totara\menu\item;

/**
 * Main menu helper tests.
 */
class totara_core_menu_helper_testcase extends advanced_testcase {
    public function test_get_unused_container_id() {
        global $DB;
        $this->resetAfterTest();

        $id = helper::get_unused_container_id();
        $record = $DB->get_record('totara_navigation', array('id' => $id), '*', MUST_EXIST);
        $this->assertSame('\totara_core\totara\menu\unused', $record->classname);
        $this->assertSame('0', $record->custom);
        $this->assertSame('0', $record->parentid);
        $this->assertSame('1', $record->sortorder);
        $this->assertSame($id, helper::get_unused_container_id());

        $DB->delete_records('totara_navigation', array());
        $id = helper::get_unused_container_id();
        $record = $DB->get_record('totara_navigation', array('id' => $id), '*', MUST_EXIST);
        $this->assertSame('\totara_core\totara\menu\unused', $record->classname);
        $this->assertSame('0', $record->custom);
        $this->assertSame('0', $record->parentid);
        $this->assertSame('1', $record->sortorder);

        $record->custom = 1;
        $record->parentid = 22;
        $record->sortorder = 99999;
        $DB->update_record('totara_navigation', $record);
        $id = helper::get_unused_container_id();
        $record = $DB->get_record('totara_navigation', array('id' => $id), '*', MUST_EXIST);
        $this->assertSame('\totara_core\totara\menu\unused', $record->classname);
        $this->assertSame('0', $record->custom);
        $this->assertSame('0', $record->parentid);
        $this->assertSame('1', $record->sortorder);
    }

    public function test_add_default_items() {
        global $DB;
        $this->resetAfterTest();

        $defaultitems = $DB->get_records_menu('totara_navigation', array(), 'classname ASC', 'classname AS c1, classname AS c2');
        $classes = \core_component::get_namespace_classes('totara\menu', 'totara_core\totara\menu\item', null, true);

        $DB->delete_records('totara_navigation', array());

        helper::add_default_items();

        $resultitems = $DB->get_records_menu('totara_navigation', array(), 'classname ASC', 'classname AS c1, classname AS c2');
        $this->assertSame($defaultitems, $resultitems);
        $this->assertCount(count($classes) - 2, $resultitems); // item and container class are for custom classes.
    }

    public function test_add_custom_menu_item() {
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_SHOW;

        $rev = helper::get_cache_revision();
        $this->setCurrentTimeStart();
        $container = helper::add_custom_menu_item($data);
        $this->assertInstanceOf('stdClass', $container);
        $this->assertSame($data->parentid, $container->parentid);
        $this->assertSame($data->title, $container->title);
        $this->assertSame('', $container->url);
        $this->assertSame('\totara_core\totara\menu\container', $container->classname);
        $this->assertSame('1', $container->custom);
        $this->assertSame('1', $container->customtitle);
        $this->assertSame($data->visibility, $container->visibility);
        $this->assertSame($container->visibility, $container->visibilityold);
        $this->assertSame('', $container->targetattr);
        $this->assertTimeCurrent($container->timemodified);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';

        $rev = helper::get_cache_revision();
        $this->setCurrentTimeStart();
        $item1 = helper::add_custom_menu_item($data);
        $this->assertInstanceOf('stdClass', $item1);
        $this->assertSame($data->parentid, $item1->parentid);
        $this->assertSame($data->title, $item1->title);
        $this->assertSame($data->url, $item1->url);
        $this->assertSame('\totara_core\totara\menu\item', $item1->classname);
        $this->assertSame('1', $item1->custom);
        $this->assertSame('1', $item1->customtitle);
        $this->assertSame($data->visibility, $item1->visibility);
        $this->assertSame($item1->visibility, $item1->visibilityold);
        $this->assertSame($data->targetattr, $item1->targetattr);
        $this->assertTimeCurrent($item1->timemodified);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 2';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item2 = helper::add_custom_menu_item($data);
        $this->assertEquals($item1->sortorder + 10, $item2->sortorder);
    }

    public function test_update_menu_item() {
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container 1';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container1 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->id = $container1->id;
        $data->parentid = (string)helper::get_unused_container_id();
        $data->title = 'Updated container 1';
        $data->visibility = (string)item::VISIBILITY_HIDE;

        $rev = helper::get_cache_revision();
        $this->setCurrentTimeStart();
        $record = helper::update_menu_item($data);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertSame($data->parentid, $record->parentid);
        $this->assertSame($data->title, $record->title);
        $this->assertSame('', $record->url);
        $this->assertSame('\totara_core\totara\menu\container', $record->classname);
        $this->assertSame('1', $record->custom);
        $this->assertSame('1', $record->customtitle);
        $this->assertSame($data->visibility, $record->visibility);
        $this->assertSame((string)item::VISIBILITY_SHOW, $record->visibilityold);
        $this->assertSame('', $record->targetattr);
        $this->assertTimeCurrent($record->timemodified);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $record->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_CUSTOM;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->id = $item1->id;
        $data->parentid = '0';
        $data->title = 'Updated item 1';
        $data->url = '/yy';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $data->targetattr = '_self';

        $rev = helper::get_cache_revision();
        $this->setCurrentTimeStart();
        $record = helper::update_menu_item($data);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertSame($data->parentid, $record->parentid);
        $this->assertSame($data->title, $record->title);
        $this->assertSame($data->url, $record->url);
        $this->assertSame('\totara_core\totara\menu\item', $record->classname);
        $this->assertSame('1', $record->custom);
        $this->assertSame('1', $record->customtitle);
        $this->assertSame($data->visibility, $record->visibility);
        $this->assertSame((string)item::VISIBILITY_CUSTOM, $record->visibilityold);
        $this->assertSame($data->targetattr, $record->targetattr);
        $this->assertTimeCurrent($record->timemodified);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        // Check extra data is ignored.
        $data = clone($record);
        $data->timemodified = time() + 1000;
        $data->classname = '\xxx';
        $data->custom = 0;
        $data->sortorder = '-10';

        $this->setCurrentTimeStart();
        $newrecord = helper::update_menu_item($data);
        $this->assertTimeCurrent($newrecord->timemodified);
        unset($newrecord->timemodified);
        unset($record->timemodified);
        $this->assertSame((array)$record, (array)$newrecord);
    }

    public function test_is_item_deletable() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container 1';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container1 = helper::add_custom_menu_item($data);

        $this->assertTrue(helper::is_item_deletable($container1->id));

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container1->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_CUSTOM;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $this->assertFalse(helper::is_item_deletable($container1->id));
        $this->assertTrue(helper::is_item_deletable($item1->id));

        $record = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\myteam'));
        $this->assertFalse(helper::is_item_deletable($record->id));

        $record->classname = '\x\x';
        $DB->update_record('totara_navigation', $record);
        $this->assertTrue(helper::is_item_deletable($record->id));

        $unused = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\unused'));
        $this->assertFalse(helper::is_item_deletable($unused->id));
    }

    public function test_is_item_delete() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container 1';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container1 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container1->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_CUSTOM;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $broken = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\myteam'));
        $broken->classname = '\x\x';
        $DB->update_record('totara_navigation', $broken);

        $unused = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\unused'));

        $rev = helper::get_cache_revision();
        $this->assertFalse(helper::delete_item($container1->id));
        $this->assertTrue(helper::delete_item($item1->id));
        $this->assertTrue(helper::delete_item($container1->id));
        $this->assertTrue(helper::delete_item($broken->id));
        $this->assertFalse(helper::delete_item($unused->id));
        $this->assertGreaterThan($rev, helper::get_cache_revision());
        $this->assertFalse($DB->record_exists('totara_navigation', array('id' => $container1->id)));
        $this->assertFalse($DB->record_exists('totara_navigation', array('id' => $item1->id)));
        $this->assertFalse($DB->record_exists('totara_navigation', array('id' => $broken->id)));
        $this->assertTrue($DB->record_exists('totara_navigation', array('id' => $unused->id)));
    }

    public function test_change_sortorder() {
        global $DB;
        $this->resetAfterTest();

        $this->assertFalse(helper::change_sortorder(-10, true));

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->sortorder = '100';
        $container = helper::add_custom_menu_item($data);
        $this->assertSame($data->sortorder, $container->sortorder);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $data->sortorder = '100';
        $item1 = helper::add_custom_menu_item($data);
        $this->assertSame($data->sortorder, $item1->sortorder);

        // Make sure lone item move does not fail as error.
        $this->assertFalse(helper::change_sortorder($item1->id, true));
        $this->assertFalse(helper::change_sortorder($item1->id, false));

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 2';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $data->sortorder = '101';
        $item2 = helper::add_custom_menu_item($data);
        $this->assertSame($data->sortorder, $item2->sortorder);

        $this->assertFalse(helper::change_sortorder($item2->id, true));

        $rev = helper::get_cache_revision();
        $this->assertTrue(helper::change_sortorder($item2->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $this->assertSame('101', $item1->sortorder);
        $this->assertSame('100', $item2->sortorder);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $this->assertTrue(helper::change_sortorder($item2->id, true));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $this->assertSame('100', $item1->sortorder);
        $this->assertSame('101', $item2->sortorder);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 3';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $data->sortorder = '110';
        $item3 = helper::add_custom_menu_item($data);
        $this->assertSame($data->sortorder, $item3->sortorder);

        $this->assertTrue(helper::change_sortorder($item3->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $item3 = $DB->get_record('totara_navigation', array('id' => $item3->id));
        $this->assertSame('100', $item1->sortorder);
        $this->assertSame('110', $item2->sortorder);
        $this->assertSame('101', $item3->sortorder);

        $this->assertTrue(helper::change_sortorder($item3->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $item3 = $DB->get_record('totara_navigation', array('id' => $item3->id));
        $this->assertSame('101', $item1->sortorder);
        $this->assertSame('110', $item2->sortorder);
        $this->assertSame('100', $item3->sortorder);

        $this->assertFalse(helper::change_sortorder($item3->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $item3 = $DB->get_record('totara_navigation', array('id' => $item3->id));
        $this->assertSame('101', $item1->sortorder);
        $this->assertSame('110', $item2->sortorder);
        $this->assertSame('100', $item3->sortorder);

        $this->assertTrue(helper::change_sortorder($item3->id, true));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $item3 = $DB->get_record('totara_navigation', array('id' => $item3->id));
        $this->assertSame('100', $item1->sortorder);
        $this->assertSame('110', $item2->sortorder);
        $this->assertSame('101', $item3->sortorder);

        $this->assertTrue(helper::change_sortorder($item3->id, true));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $item3 = $DB->get_record('totara_navigation', array('id' => $item3->id));
        $this->assertSame('100', $item1->sortorder);
        $this->assertSame('101', $item2->sortorder);
        $this->assertSame('110', $item3->sortorder);

        $DB->set_field('totara_navigation', 'sortorder', '200', array('parentid' => $container->id));
        $this->assertTrue(helper::change_sortorder($item3->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $item3 = $DB->get_record('totara_navigation', array('id' => $item3->id));
        $this->assertSame('200', $item1->sortorder);
        $this->assertSame('202', $item2->sortorder);
        $this->assertSame('201', $item3->sortorder);

        $DB->set_field('totara_navigation', 'sortorder', '0', array('parentid' => $container->id));
        $this->assertTrue(helper::change_sortorder($item3->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $item2 = $DB->get_record('totara_navigation', array('id' => $item2->id));
        $item3 = $DB->get_record('totara_navigation', array('id' => $item3->id));
        $this->assertSame('2', $item1->sortorder);
        $this->assertSame('4', $item2->sortorder);
        $this->assertSame('3', $item3->sortorder);
    }

    public function test_change_visibility() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $rev = helper::get_cache_revision();
        $this->assertTrue(helper::change_visibility($container->id, false));
        $this->assertTrue(helper::change_visibility($item1->id, true));
        $this->assertSame($rev, helper::get_cache_revision());

        $rev = helper::get_cache_revision();
        $this->assertTrue(helper::change_visibility($item1->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $this->assertSame((string)item::VISIBILITY_HIDE, $item1->visibility);
        $this->assertSame((string)item::VISIBILITY_SHOW, $item1->visibilityold);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $rev = helper::get_cache_revision();
        $this->assertTrue(helper::change_visibility($item1->id, true));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $this->assertSame((string)item::VISIBILITY_SHOW, $item1->visibility);
        $this->assertSame((string)item::VISIBILITY_SHOW, $item1->visibilityold);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $DB->set_field('totara_navigation', 'visibility', item::VISIBILITY_CUSTOM, array('id' => $item1->id));

        $rev = helper::get_cache_revision();
        $this->assertTrue(helper::change_visibility($item1->id, false));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $this->assertSame((string)item::VISIBILITY_HIDE, $item1->visibility);
        $this->assertSame((string)item::VISIBILITY_CUSTOM, $item1->visibilityold);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $rev = helper::get_cache_revision();
        $this->assertTrue(helper::change_visibility($item1->id, true));
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $this->assertSame((string)item::VISIBILITY_CUSTOM, $item1->visibility);
        $this->assertSame((string)item::VISIBILITY_CUSTOM, $item1->visibilityold);
        $this->assertGreaterThan($rev, helper::get_cache_revision());

        $rev = helper::get_cache_revision();
        $this->assertTrue(helper::change_visibility($item1->id, true));
        $this->assertSame($rev, helper::get_cache_revision());
    }

    public function test_reset_menu() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $previtems = $DB->get_records_menu('totara_navigation', array(), 'classname ASC', 'classname AS c1, classname AS c2');
        $previds = $DB->get_records_menu('totara_navigation', array(), 'id ASC', 'id AS c1, id AS c2');

        $rev = helper::get_cache_revision();
        helper::reset_menu(true);
        $resultitems = $DB->get_records_menu('totara_navigation', array(), 'classname ASC', 'classname AS c1, classname AS c2');
        $currentids = $DB->get_records_menu('totara_navigation', array(), 'id ASC', 'id AS c1, id AS c2');
        $this->assertSame($previtems, $resultitems);
        $this->assertGreaterThan($rev, helper::get_cache_revision());
        $unusedcontainerid = helper::get_unused_container_id();
        $container = $DB->get_record('totara_navigation', array('id' => $container->id));
        $this->assertEquals($unusedcontainerid, $container->parentid);
        $item1 = $DB->get_record('totara_navigation', array('id' => $item1->id));
        $this->assertEquals($unusedcontainerid, $item1->parentid);
        unset($previds[$container->id]);
        unset($previds[$item1->id]);
        unset($currentids[$container->id]);
        unset($currentids[$item1->id]);
        $this->assertSame(array($unusedcontainerid => (string)$unusedcontainerid), array_intersect($previds, $currentids));

        $previds = $DB->get_records_menu('totara_navigation', array(), 'id ASC', 'id AS c1, id AS c2');
        $rev = helper::get_cache_revision();
        helper::reset_menu(false);
        $resultitems = $DB->get_records_menu('totara_navigation', array(), 'classname ASC', 'classname AS c1, classname AS c2');
        $currentids = $DB->get_records_menu('totara_navigation', array(), 'id ASC', 'id AS c1, id AS c2');
        $this->assertSame(count($previtems) - 2, count($resultitems));
        $this->assertGreaterThan($rev, helper::get_cache_revision());
        $this->assertFalse($DB->record_exists('totara_navigation', array('id' => $container->id)));
        $this->assertFalse($DB->record_exists('totara_navigation', array('id' => $item1->id)));
        $this->assertSame(array(), array_intersect($previds, $currentids));
    }

    public function test_create_parentid_form_options() {
        global $DB;
        $this->resetAfterTest();

        $unusedcontainerid = helper::get_unused_container_id();

        // Remove items from non-standard plugins.
        $items = $DB->get_records('totara_navigation');
        foreach ($items as $item) {
            $parts = explode('\\', ltrim($item->classname, '\\'));
            $component = reset($parts);
            list($plugin_type, $plugin_name) = core_component::normalize_component($component);
            $standardplugins = core_plugin_manager::standard_plugins_list($plugin_type);
            if (!in_array($plugin_name, $standardplugins)) {
                $DB->delete_records('totara_navigation', array('id' => $item->id));
            }
        }

        $defaultoptions = helper::create_parentid_form_options(0);
        // Note: update following test to match default menu structure if it changes.
        $this->assertSame(array('Top', 'Performance', 'Find Learning (Legacy catalogues)', 'Unused'), array_values($defaultoptions));

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container 1';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container1 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $container1->id;
        $data->title = 'Test sub container 2';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container2 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $container2->id;
        $data->title = 'Test sub sub container 3';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container3 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $unusedcontainerid;
        $data->title = 'Unused container 4';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container4 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = -10;
        $data->title = 'Broken container 5';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container5 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $container3->id;
        $data->title = 'Too deep container 6';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container6 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container1->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container2->id;
        $data->title = 'Test item 2';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item2 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container3->id;
        $data->title = 'Test item 3';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item3 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container4->id;
        $data->title = 'Test item 4';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item4 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container5->id;
        $data->title = 'Test item 5';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item5 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container6->id;
        $data->title = 'Test item 6';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item6 = helper::add_custom_menu_item($data);

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options(0));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options(0, 2));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options(0, 1));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($container1->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($container2->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($container3->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($container4->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($container5->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($container6->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($item1->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($item2->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($item3->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($item4->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($item5->id));

        $expected = $defaultoptions;
        unset($expected[$unusedcontainerid]);
        $expected[$container1->id] = $container1->title;
        $expected[$container2->id] = $container1->title . ' / ' . $container2->title;
        $expected[$container3->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title;
        $expected[$container6->id] = $container1->title . ' / ' . $container2->title . ' / ' . $container3->title . ' / ' . $container6->title;
        $expected[$unusedcontainerid] = $defaultoptions[$unusedcontainerid];
        $this->assertSame($expected, helper::create_parentid_form_options($item6->id));
    }

    public function test_validate_item_url() {
        $this->assertNull(helper::validate_item_url('/xx'));
        $this->assertNull(helper::validate_item_url('http://example.com'));
        $this->assertNull(helper::validate_item_url('ftp://example.com/xx.txt'));

        $this->assertSame('Required', helper::validate_item_url(''));
        $this->assertSame('Required', helper::validate_item_url(' '));

        $error = 'Menu url address is invalid. Use "/" for a relative link of your domain name or full address for external link, i.e. http://extdomain.com';

        $this->assertSame($error, helper::validate_item_url('ss'));
        $this->assertSame($error, helper::validate_item_url('javascript:xxx'));
        $this->assertSame($error, helper::validate_item_url('http:/xx'));
    }

    public function test_get_admin_edit_rowid() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $items = $DB->get_records('totara_navigation', array());
        foreach ($items as $item) {
            $this->assertSame('totaramenuedititem' . $item->id, helper::get_admin_edit_rowid($item->id));
        }
    }

    public function test_get_admin_edit_return_url() {
        global $DB, $CFG;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $items = $DB->get_records('totara_navigation', array());
        foreach ($items as $item) {
            $url = helper::get_admin_edit_return_url($item->id)->out(false);
            if ($item->parentid) {
                $this->assertSame("{$CFG->wwwroot}/totara/core/menu/index.php#" . helper::get_admin_edit_rowid($item->parentid), $url);
            } else {
                $this->assertSame("{$CFG->wwwroot}/totara/core/menu/index.php", $url);
            }
        }
    }

    public function test_get_cache_revision() {
        global $CFG;
        $this->resetAfterTest();

        $rev = helper::get_cache_revision();
        $this->assertIsInt($rev);
        $this->assertGreaterThan(1, $rev);
        $this->assertSame($rev, helper::get_cache_revision());


        $now = time();
        unset($CFG->totaramenurev);
        $rev = helper::get_cache_revision();
        $this->assertIsInt($rev);
        $this->assertGreaterThanOrEqual($now, $rev);
    }

    public function test_bump_cache_revision() {
        global $CFG;
        $this->resetAfterTest();

        $oldrev = helper::get_cache_revision();

        $rev = helper::bump_cache_revision();
        $this->assertIsInt($rev);
        $this->assertGreaterThan($oldrev, $rev);
        $this->assertSame($rev, helper::get_cache_revision());

        $now = time();
        unset($CFG->totaramenurev);
        $rev = helper::bump_cache_revision();
        $this->assertIsInt($rev);
        $this->assertGreaterThanOrEqual($now, $rev);
    }
}
