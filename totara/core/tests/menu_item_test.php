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

use totara_core\totara\menu\item;
use totara_core\totara\menu\container;
use totara_core\totara\menu\helper;

/**
 * Main menu item tests.
 */
class totara_core_menu_item_testcase extends advanced_testcase {
    public function test_constructor() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $instance = new container($container);
        $this->assertDebuggingCalled('Deprecated item::__construct() call, use item::create_instance() instead.');

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);

        $instance = new item($container);
        $this->assertDebuggingCalled('Deprecated item::__construct() call, use item::create_instance() instead.');
    }

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            $this->assertInstanceOf('\totara_core\totara\menu\item', $instance);
        }

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $instance = item::create_instance($container);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);

        $defaultcontainer = $DB->get_record('totara_navigation', array('classname' => '\totara_appraisal\totara\menu\appraisal'), '*', MUST_EXIST);
        $instance = item::create_instance($defaultcontainer);
        $this->assertInstanceOf('totara_appraisal\totara\menu\appraisal', $instance);

        $defaultitem = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\myteam'), '*', MUST_EXIST);
        $instance = item::create_instance($defaultitem);
        $this->assertInstanceOf('\totara_core\totara\menu\myteam', $instance);

        // Test invalid data silently returns null.

        $defaultitem->classname = '';
        $this->assertNull(item::create_instance($defaultitem));

        $defaultitem->classname = '\xx\xx';
        $this->assertNull(item::create_instance($defaultitem));

        $defaultitem->classname = 'stdClass';
        $this->assertNull(item::create_instance($defaultitem));

        $defaultcontainer->custom = '1';
        $this->assertNull(item::create_instance($defaultcontainer));

        $item->custom = '0';
        $this->assertNull(item::create_instance($item));

        $container->custom = '0';
        $this->assertNull(item::create_instance($container));

        $item->custom = '1';
        $item->id = '0';
        $this->assertDebuggingNotCalled();
        $this->assertNull(item::create_instance($item));
        $this->assertDebuggingCalled('Incorrect constructor call, fake data is not allowed any more, use real database record');
    }

    public function test_is_custom() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $instance = item::create_instance($container);
        $this->assertTrue($instance->is_custom());

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);
        $this->assertTrue($instance->is_custom());

        $defaultcontainer = $DB->get_record('totara_navigation', array('classname' => '\totara_appraisal\totara\menu\appraisal'), '*', MUST_EXIST);
        $instance = item::create_instance($defaultcontainer);
        $this->assertFalse($instance->is_custom());

        $defaultitem = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\myteam'), '*', MUST_EXIST);
        $instance = item::create_instance($defaultitem);
        $this->assertFalse($instance->is_custom());
    }

    public function test_is_container() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);

        $instance = item::create_instance($container);
        $this->assertTrue($instance->is_container());

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);
        $this->assertFalse($instance->is_container());

        $defaultcontainer = $DB->get_record('totara_navigation', array('classname' => '\totara_appraisal\totara\menu\appraisal'), '*', MUST_EXIST);
        $instance = item::create_instance($defaultcontainer);
        $this->assertTrue($instance->is_container());

        $defaultitem = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\myteam'), '*', MUST_EXIST);
        $instance = item::create_instance($defaultitem);
        $this->assertFalse($instance->is_container());
    }

    public function test_get_id() {
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = 0;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);

        $this->assertSame($item->id, $instance->get_id());
    }

    public function test_parentid() {
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
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);

        $this->assertSame($item->parentid, $instance->get_parentid());
    }

    public function test_set_parentid() {
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
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);

        $instance->set_parentid(0);
        $this->assertDebuggingCalled('item::set_parentid() was deprecated, do not use it');

        $this->assertSame($item->parentid, $instance->get_parentid());
    }

    public function test_get_title() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);
        $instance = item::create_instance($container);
        $this->assertSame($data->title, $instance->get_title());

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);
        $this->assertSame($data->title, $instance->get_title());

        $performance = $DB->get_record('totara_navigation', array('classname' => '\totara_appraisal\totara\menu\appraisal'), '*', MUST_EXIST);
        $instance = item::create_instance($performance);
        $this->assertSame('Performance', $instance->get_title());

        $performance->customtitle = '1';
        $performance->title = 'Pokus';
        $instance = item::create_instance($performance);
        $this->assertSame('Pokus', $instance->get_title());

        $performance->customtitle = '0';
        $instance = item::create_instance($performance);
        $this->assertSame('Performance', $instance->get_title());

        $myteam = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\myteam'), '*', MUST_EXIST);
        $instance = item::create_instance($myteam);
        $this->assertSame('Team', $instance->get_title());

        $myteam->customtitle = '1';
        $myteam->title = 'Hokus';
        $instance = item::create_instance($myteam);
        $this->assertSame('Hokus', $instance->get_title());

        $myteam->customtitle = '0';
        $instance = item::create_instance($myteam);
        $this->assertSame('Team', $instance->get_title());
    }

    public function test_get_default_admin_help() {
        global $DB;

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            $help = $instance->get_default_admin_help();
            if (!is_null($help)) {
                $this->assertCount(2, $help, 'menu admin help is supposed to be array with two items: ' . $record->classname);
            }
        }
    }

    public function test_get_classname() {
        global $DB;

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            $this->assertSame('\\' . get_class($instance), $instance->get_classname());
        }
    }

    public function test_get_visibility() {
        global $DB;

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            $this->assertSame($record->visibility, $instance->get_visibility());
        }
    }

    public function test_get_default_visibility() {
        global $DB;

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            $this->assertIsBool($instance->get_default_visibility(), 'item::get_default_visibility() is now supposed to return bools');
        }
    }

    public function test_is_disabled() {
        global $DB;

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            $this->assertIsBool($instance->is_disabled(), 'item::is_disabled() is supposed to return bools');
        }
    }

    public function test_get_name() {
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
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            $this->assertSame('totaramenuitem' . $instance->get_id(), $instance->get_name());
        }
    }

    public function test_get_parent() {
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
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item = helper::add_custom_menu_item($data);

        $records = $DB->get_records('totara_navigation', array());
        foreach ($records as $record) {
            $instance = item::create_instance($record);
            if ($instance->get_parentid()) {
                $this->assertSame('totaramenuitem' . $instance->get_parentid(), $instance->get_parent());
            } else {
                $this->assertSame('', $instance->get_parent());
            }
        }
    }

    public function test_get_targetattr() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container = helper::add_custom_menu_item($data);
        $instance = item::create_instance($container);
        $this->assertSame('', $instance->get_targetattr());

        $container->targetattr = '_blank';
        $instance = item::create_instance($container);
        $this->assertSame('', $instance->get_targetattr());

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container->id;
        $data->title = 'Test item';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_self';
        $item = helper::add_custom_menu_item($data);
        $instance = item::create_instance($item);
        $this->assertSame('', $instance->get_targetattr());

        $item->targetattr = '_blank';
        $instance = item::create_instance($item);
        $this->assertSame('_blank', $instance->get_targetattr());

        $item->targetattr = '_xzs';
        $instance = item::create_instance($item);
        $this->assertSame('', $instance->get_targetattr());

        $performance = $DB->get_record('totara_navigation', array('classname' => '\totara_appraisal\totara\menu\appraisal'), '*', MUST_EXIST);
        $instance = item::create_instance($performance);
        $this->assertSame('', $instance->get_targetattr());

        $performance->targetattr = '_blank';
        $instance = item::create_instance($performance);
        $this->assertSame('', $instance->get_targetattr());

        $myteam = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\myteam'), '*', MUST_EXIST);
        $instance = item::create_instance($myteam);
        $this->assertSame('', $instance->get_targetattr());

        $performance->targetattr = '_blank';
        $instance = item::create_instance($performance);
        $this->assertSame('', $instance->get_targetattr());

        $performance->targetattr = '_self';
        $instance = item::create_instance($performance);
        $this->assertSame('', $instance->get_targetattr());
    }

    public function test_replace_url_parameter_placeholders() {
        global $COURSE;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->setUser($user);
        $COURSE = $course;

        $url = 'http://example.com/##username##/index.php?id=##userid##&course=##courseid###xxx##useremail##';
        $result = item::replace_url_parameter_placeholders($url);
        $encodedemail = urlencode($user->email);
        $encodedusername = urlencode($user->username);
        $this->assertSame("http://example.com/{$encodedusername}/index.php?id={$user->id}&course={$course->id}#xxx{$encodedemail}", $result);
    }
}
