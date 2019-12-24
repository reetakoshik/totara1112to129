<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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

/**
 * Tests for core generators.
 */
class totara_core_generator_testcase extends advanced_testcase {
    public function test_create_custom_profile_category() {
        global $DB;
        $this->resetAfterTest();

        $this->assertCount(0, $DB->get_records('user_info_category'));
        $this->assertCount(0, $DB->get_records('user_info_field'));

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $category1 = $generator->create_custom_profile_category(array());
        $this->assertGreaterThan(0, $category1->id);
        $this->assertSame('Custom profile category 1', $category1->name);
        $this->assertSame('1', $category1->sortorder);

        $category2 = $generator->create_custom_profile_category(array());
        $this->assertGreaterThan($category1->id, $category2->id);
        $this->assertSame('Custom profile category 2', $category2->name);
        $this->assertSame('2', $category2->sortorder);

        $category3 = $generator->create_custom_profile_category(array('name' => 'xx', 'sortorder' => 5));
        $this->assertGreaterThan($category2->id, $category3->id);
        $this->assertSame('xx', $category3->name);
        $this->assertSame('3', $category3->sortorder);
    }

    public function test_create_custom_profile_field_errors() {
        global $DB;
        $this->resetAfterTest();

        $this->assertCount(0, $DB->get_records('user_info_category'));
        $this->assertCount(0, $DB->get_records('user_info_field'));

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        try {
            $field = $generator->create_custom_profile_field(array('datatype' => 'xxasa'));
            $this->fail('Exception expected when invalid type specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid custom profile field type in $record->datatype: xxasa', $e->getMessage());
        }

        try {
            $field = $generator->create_custom_profile_field(array());
            $this->fail('Exception expected when no type specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Must specify custom profile field data type in $record->datatype', $e->getMessage());
        }

        $this->assertCount(1, $DB->get_records('user_info_category'));
        $this->assertCount(0, $DB->get_records('user_info_field'));
    }

    public function test_create_custom_profile_field_checkbox() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_profile_field(array('datatype' => 'checkbox'));
        $this->assertGreaterThan(0, $field->id);
        $this->assertSame('field1', $field->shortname);
        $this->assertSame('Custom profile field 1', $field->name);
        $this->assertSame('checkbox', $field->datatype);
        $this->assertSame('Some description 1', $field->description);
        $this->assertSame('1', $field->descriptionformat);
        $this->assertGreaterThan(0, $field->categoryid);
        $this->assertSame('1', $field->sortorder);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->locked);
        $this->assertSame('2', $field->visible);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->signup);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_profile_field(array('datatype' => 'checkbox', 'defaultdata' => 1));
        $this->assertSame('field2', $field->shortname);
        $this->assertSame('Custom profile field 2', $field->name);
        $this->assertSame('checkbox', $field->datatype);
        $this->assertSame('Some description 2', $field->description);
        $this->assertSame('1', $field->descriptionformat);
        $this->assertSame('2', $field->sortorder);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->locked);
        $this->assertSame('2', $field->visible);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->signup);
        $this->assertSame('1', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_profile_field(array('datatype' => 'checkbox', 'defaultdata' => 0));
        $this->assertSame('field3', $field->shortname);
        $this->assertSame('Custom profile field 3', $field->name);
        $this->assertSame('checkbox', $field->datatype);
        $this->assertSame('Some description 3', $field->description);
        $this->assertSame('1', $field->descriptionformat);
        $this->assertSame('3', $field->sortorder);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->locked);
        $this->assertSame('2', $field->visible);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->signup);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_profile_field_date() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_profile_field(array('datatype' => 'date'));
        $this->assertSame('date', $field->datatype);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_profile_field_datetime() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $category = $generator->create_custom_profile_category();
        $field = $generator->create_custom_profile_field(
            array('datatype' => 'datetime', 'shortname' => 'somedate', 'name' => 'Some Name', 'param3' => 0,
                'description' => 'Some Desc', 'descriptionformat' => FORMAT_PLAIN, 'categoryid' => $category->id));
        $this->assertGreaterThan(0, $field->id);
        $this->assertSame('somedate', $field->shortname);
        $this->assertSame('Some Name', $field->name);
        $this->assertSame('datetime', $field->datatype);
        $this->assertSame('Some Desc', $field->description);
        $this->assertSame('2', $field->descriptionformat);
        $this->assertEquals($category->id, $field->categoryid);
        $this->assertSame('1', $field->sortorder);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->locked);
        $this->assertSame('2', $field->visible);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->signup);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame(strftime('%Y'), $field->param1);
        $this->assertSame(strftime('%Y'), $field->param2);
        $this->assertSame(null, $field->param3); // Do not show time.
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_profile_field(array('datatype' => 'datetime', 'param1' => 1975, 'param2' => 2014, 'param3' => 1));
        $this->assertSame('datetime', $field->datatype);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame('1975', $field->param1);
        $this->assertSame('2014', $field->param2);
        $this->assertSame('1', $field->param3); // Shwo time.
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_profile_field_menu() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_profile_field(array('datatype' => 'menu', 'param1' => "xx\nyy\nzz", 'defaultdata' => 'yy'));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('yy', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_profile_field(array('datatype' => 'menu', 'param1' => "xx/yy/zz", 'defaultdata' => ''));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_profile_field(array('datatype' => 'menu', 'param1' => array('xx', 'yy', 'zz'), 'defaultdata' => ''));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        try {
            $field = $generator->create_custom_profile_field(array('datatype' => 'menu', 'param1' => "", 'defaultdata' => ''));
            $this->fail('Exception expected when menu options not set');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Menu field requires at least 2 options in $record->param1', $e->getMessage());
        }
        try {
            $field = $generator->create_custom_profile_field(array('datatype' => 'menu', 'param1' => "aa\nbb", 'defaultdata' => 'cc'));
            $this->fail('Exception expected when menu options not set');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Menu field requires default to be one of the options in $record->param1', $e->getMessage());
        }
    }

    public function test_create_custom_profile_field_text() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_profile_field(array('datatype' => 'text'));
        $this->assertSame('text', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame('0', $field->defaultdataformat);
        $this->assertSame('30', $field->param1);
        $this->assertSame('2048', $field->param2);
        $this->assertSame('0', $field->param3);
        $this->assertSame('', $field->param4);
        $this->assertSame('', $field->param5);
    }

    public function test_create_custom_profile_field_textarea() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_profile_field(array('datatype' => 'textarea', 'defaultdata' => 'xxx'));
        $this->assertSame('textarea', $field->datatype);
        $this->assertSame('xxx', $field->defaultdata);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_course_field_errors() {
        global $DB;
        $this->resetAfterTest();

        $this->assertCount(0, $DB->get_records('course_info_field'));

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        try {
            $field = $generator->create_custom_course_field(array('datatype' => 'xxasa'));
            $this->fail('Exception expected when invalid type specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid custom Totara field type in $record->datatype: xxasa', $e->getMessage());
        }

        try {
            $field = $generator->create_custom_course_field(array());
            $this->fail('Exception expected when no type specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Must specify custom Totara field data type in $record->datatype', $e->getMessage());
        }

        $this->assertCount(0, $DB->get_records('course_info_field'));
    }

    public function test_create_custom_course_field_checkbox() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_course_field(array('datatype' => 'checkbox'));
        $this->assertGreaterThan(0, $field->id);
        $this->assertSame('field1', $field->shortname);
        $this->assertSame('Custom field 1', $field->fullname);
        $this->assertSame('checkbox', $field->datatype);
        $this->assertSame('Some description 1', $field->description);
        $this->assertSame('1', $field->sortorder);
        $this->assertSame('0', $field->hidden);
        $this->assertSame('0', $field->locked);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_course_field(array('datatype' => 'checkbox', 'defaultdata' => 1));
        $this->assertSame('field2', $field->shortname);
        $this->assertSame('Custom field 2', $field->fullname);
        $this->assertSame('checkbox', $field->datatype);
        $this->assertSame('Some description 2', $field->description);
        $this->assertSame('2', $field->sortorder);
        $this->assertSame('0', $field->hidden);
        $this->assertSame('0', $field->locked);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('1', $field->defaultdata);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_course_field(array('datatype' => 'checkbox', 'defaultdata' => 0));
        $this->assertSame('field3', $field->shortname);
        $this->assertSame('Custom field 3', $field->fullname);
        $this->assertSame('checkbox', $field->datatype);
        $this->assertSame('Some description 3', $field->description);
        $this->assertSame('3', $field->sortorder);
        $this->assertSame('0', $field->hidden);
        $this->assertSame('0', $field->locked);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_course_field_datetime() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_course_field(
            array('datatype' => 'datetime', 'shortname' => 'somedate', 'fullname' => 'Some Name', 'param3' => 0,
                'description' => 'Some Desc', 'descriptionformat' => FORMAT_PLAIN));
        $this->assertGreaterThan(0, $field->id);
        $this->assertSame('somedate', $field->shortname);
        $this->assertSame('Some Name', $field->fullname);
        $this->assertSame('datetime', $field->datatype);
        $this->assertSame('Some Desc', $field->description);
        $this->assertSame('1', $field->sortorder);
        $this->assertSame('0', $field->hidden);
        $this->assertSame('0', $field->locked);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame(strftime('%Y'), $field->param1);
        $this->assertSame(strftime('%Y'), $field->param2);
        $this->assertSame(null, $field->param3); // Do not show time.
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_course_field(array('datatype' => 'datetime', 'param1' => 1975, 'param2' => 2014, 'param3' => 1));
        $this->assertSame('datetime', $field->datatype);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame('1975', $field->param1);
        $this->assertSame('2014', $field->param2);
        $this->assertSame('1', $field->param3); // Shwo time.
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_course_field_menu() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_course_field(array('datatype' => 'menu', 'param1' => "xx\nyy\nzz", 'defaultdata' => 'yy'));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('yy', $field->defaultdata);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_course_field(array('datatype' => 'menu', 'param1' => "xx/yy/zz", 'defaultdata' => ''));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_course_field(array('datatype' => 'menu', 'param1' => array('xx', 'yy', 'zz'), 'defaultdata' => ''));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        try {
            $field = $generator->create_custom_course_field(array('datatype' => 'menu', 'param1' => "", 'defaultdata' => ''));
            $this->fail('Exception expected when menu options not set');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Menu field requires at least 2 options in $record->param1', $e->getMessage());
        }
        try {
            $field = $generator->create_custom_course_field(array('datatype' => 'menu', 'param1' => "aa\nbb", 'defaultdata' => 'cc'));
            $this->fail('Exception expected when menu options not set');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Menu field requires default to be one of the options in $record->param1', $e->getMessage());
        }
    }

    public function test_create_custom_course_field_multiselect() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $options = '[{"option":"prvni","icon":"achieving-success","default":"1","delete":0},{"option":"druhy","icon":"","default":"1","delete":0},{"option":"treti","icon":"forecasting-budgeting-and-strategic-planning","default":"0","delete":0}]';

        $field = $generator->create_custom_course_field(array('datatype' => 'multiselect', 'param1' => $options));
        $this->assertSame('multiselect', $field->datatype);
        $this->assertSame(null, $field->defaultdata);
        $this->assertSame($options, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_course_field(array('datatype' => 'multiselect', 'param1' => json_decode($options, true)));
        $this->assertSame('multiselect', $field->datatype);
        $this->assertSame(null, $field->defaultdata);
        $this->assertSame($options, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_course_field_text() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_course_field(array('datatype' => 'text'));
        $this->assertSame('text', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame('30', $field->param1);
        $this->assertSame('2048', $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_course_field_textarea() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_course_field(array('datatype' => 'textarea', 'defaultdata' => 'xxx'));
        $this->assertSame('textarea', $field->datatype);
        $this->assertSame('xxx', $field->defaultdata);
        $this->assertSame('30', $field->param1);
        $this->assertSame('10', $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_program_field_errors() {
        global $DB;
        $this->resetAfterTest();

        $this->assertCount(0, $DB->get_records('prog_info_field'));

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        try {
            $field = $generator->create_custom_program_field(array('datatype' => 'xxasa'));
            $this->fail('Exception expected when invalid type specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid custom Totara field type in $record->datatype: xxasa', $e->getMessage());
        }

        try {
            $field = $generator->create_custom_program_field(array());
            $this->fail('Exception expected when no type specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Must specify custom Totara field data type in $record->datatype', $e->getMessage());
        }

        $this->assertCount(0, $DB->get_records('prog_info_field'));
    }

    public function test_create_custom_program_field_checkbox() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_program_field(array('datatype' => 'checkbox'));
        $this->assertGreaterThan(0, $field->id);
        $this->assertSame('field1', $field->shortname);
        $this->assertSame('Custom field 1', $field->fullname);
        $this->assertSame('checkbox', $field->datatype);
        $this->assertSame('Some description 1', $field->description);
        $this->assertSame('1', $field->sortorder);
        $this->assertSame('0', $field->hidden);
        $this->assertSame('0', $field->locked);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame(null, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_program_field_datetime() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_program_field(
            array('datatype' => 'datetime', 'shortname' => 'somedate', 'fullname' => 'Some Name', 'param3' => 0,
                'description' => 'Some Desc', 'descriptionformat' => FORMAT_PLAIN));
        $this->assertGreaterThan(0, $field->id);
        $this->assertSame('somedate', $field->shortname);
        $this->assertSame('Some Name', $field->fullname);
        $this->assertSame('datetime', $field->datatype);
        $this->assertSame('Some Desc', $field->description);
        $this->assertSame('1', $field->sortorder);
        $this->assertSame('0', $field->hidden);
        $this->assertSame('0', $field->locked);
        $this->assertSame('0', $field->required);
        $this->assertSame('0', $field->forceunique);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame(strftime('%Y'), $field->param1);
        $this->assertSame(strftime('%Y'), $field->param2);
        $this->assertSame(null, $field->param3); // Do not show time.
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_program_field(array('datatype' => 'datetime', 'param1' => 1975, 'param2' => 2014, 'param3' => 1));
        $this->assertSame('datetime', $field->datatype);
        $this->assertSame('0', $field->defaultdata);
        $this->assertSame('1975', $field->param1);
        $this->assertSame('2014', $field->param2);
        $this->assertSame('1', $field->param3); // Shwo time.
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_program_field_menu() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_program_field(array('datatype' => 'menu', 'param1' => "xx\nyy\nzz", 'defaultdata' => 'yy'));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('yy', $field->defaultdata);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_program_field(array('datatype' => 'menu', 'param1' => "xx/yy/zz", 'defaultdata' => ''));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_program_field(array('datatype' => 'menu', 'param1' => array('xx', 'yy', 'zz'), 'defaultdata' => ''));
        $this->assertSame('menu', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame("xx\nyy\nzz", $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        try {
            $field = $generator->create_custom_program_field(array('datatype' => 'menu', 'param1' => "", 'defaultdata' => ''));
            $this->fail('Exception expected when menu options not set');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Menu field requires at least 2 options in $record->param1', $e->getMessage());
        }
        try {
            $field = $generator->create_custom_program_field(array('datatype' => 'menu', 'param1' => "aa\nbb", 'defaultdata' => 'cc'));
            $this->fail('Exception expected when menu options not set');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Menu field requires default to be one of the options in $record->param1', $e->getMessage());
        }
    }

    public function test_create_custom_program_field_multiselect() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $options = '[{"option":"prvni","icon":"achieving-success","default":"1","delete":0},{"option":"druhy","icon":"","default":"1","delete":0},{"option":"treti","icon":"forecasting-budgeting-and-strategic-planning","default":"0","delete":0}]';

        $field = $generator->create_custom_program_field(array('datatype' => 'multiselect', 'param1' => $options));
        $this->assertSame('multiselect', $field->datatype);
        $this->assertSame(null, $field->defaultdata);
        $this->assertSame($options, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);

        $field = $generator->create_custom_program_field(array('datatype' => 'multiselect', 'param1' => json_decode($options, true)));
        $this->assertSame('multiselect', $field->datatype);
        $this->assertSame(null, $field->defaultdata);
        $this->assertSame($options, $field->param1);
        $this->assertSame(null, $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_program_field_text() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_program_field(array('datatype' => 'text'));
        $this->assertSame('text', $field->datatype);
        $this->assertSame('', $field->defaultdata);
        $this->assertSame('30', $field->param1);
        $this->assertSame('2048', $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }

    public function test_create_custom_program_field_textarea() {
        $this->resetAfterTest();

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field = $generator->create_custom_program_field(array('datatype' => 'textarea', 'defaultdata' => 'xxx'));
        $this->assertSame('textarea', $field->datatype);
        $this->assertSame('xxx', $field->defaultdata);
        $this->assertSame('30', $field->param1);
        $this->assertSame('10', $field->param2);
        $this->assertSame(null, $field->param3);
        $this->assertSame(null, $field->param4);
        $this->assertSame(null, $field->param5);
    }
}
