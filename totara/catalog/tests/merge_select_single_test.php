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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the merge_select base class for catalog component.
 *
 * @group totara_catalog
 */
class totara_catalog_merge_select_single_testcase extends advanced_testcase {

    public function test_add_all_option() {
        // Default params.
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single->add_all_option();
        $options = $single->get_options();
        $this->assertCount(1, $options);
        $expectedoption = (object)[
            'key' => '',
            'name' => get_string('all'),
            'default' => true,
        ];
        $this->assertEquals($expectedoption, $options['']);

        // Specified params.
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single->add_all_option('testallname', 'testallname');
        $options = $single->get_options();
        $this->assertCount(1, $options);
        $expectedoption = (object)[
            'key' => 'testallname',
            'name' => 'testallname',
            'default' => true,
        ];
        $this->assertEquals($expectedoption, $options['testallname']);
    }

    public function test_get_options_without_all() {
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');

        $optionsloader1 = function () {
            return [
                'testoptionkey2' => 'testoptionname2',
                'testoptionkey1' => 'testoptionname1',
            ];
        };
        $single->add_options_loader($optionsloader1);

        $optionsloader2 = function () {
            return [
                'testoptionkey1' => 'testoptionnamex',
                'testoptionkey3' => 'testoptionname3',
            ];
        };
        $single->add_options_loader($optionsloader2);

        $options = $single->get_options();
        $expectedoptions = [
            'testoptionkey2' => (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
                'default' => true, // Alphabetically first item is marked as default.
            ],
            'testoptionkey3' => (object)[
                'key' => 'testoptionkey3',
                'name' => 'testoptionname3',
            ],
            'testoptionkey1' => (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionnamex', // Keys are important, newer names overwrite older ones.
            ],
        ];

        $this->assertEquals($expectedoptions, $options);

        // Check that the records have been ordered correctly.
        $this->assertEquals(
            implode(',', array_keys($expectedoptions)),
            implode(',', array_keys($options))
        );
    }

    public function test_get_options_with_all() {
        // All comes first and is default, regardless of alphabetical ordering.
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');

        $optionsloader1 = function () {
            return [
                'testoptionkey2' => 'testoptionname2',
                'testoptionkey1' => 'testoptionname1',
            ];
        };
        $single->add_options_loader($optionsloader1);

        $optionsloader2 = function () {
            return [
                'testoptionkey1' => 'testoptionnamex',
                'testoptionkey3' => 'testoptionname3',
            ];
        };
        $single->add_options_loader($optionsloader2);

        // Add 'All' between the other two options loaders (order of execution doesn't matter).
        $single->add_all_option('xtestallname', 'xtestallkey');

        $options = $single->get_options();
        $expectedoptions = [
            'xtestallkey' => (object)[
                'key' => 'xtestallkey',
                'name' => 'xtestallname',
                'default' => true,
            ],
            'testoptionkey2' => (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
            ],
            'testoptionkey3' => (object)[
                'key' => 'testoptionkey3',
                'name' => 'testoptionname3',
            ],
            'testoptionkey1' => (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionnamex', // Keys are important, newer names overwrite older ones.
            ],
        ];

        $this->assertEquals($expectedoptions, $options);

        // Check that the records have been ordered correctly.
        $this->assertEquals(
            implode(',', array_keys($expectedoptions)),
            implode(',', array_keys($options))
        );
    }

    public function test_get_options_default_without_all() {
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle', 'testoptionkey2');

        $optionsloader1 = function () {
            return [
                'testoptionkey1' => 'testoptionname1',
                'testoptionkey2' => 'testoptionname2',
                'testoptionkey3' => 'testoptionname3',
            ];
        };
        $single->add_options_loader($optionsloader1);

        $options = $single->get_options();
        $expectedoptions = [
            'testoptionkey1' => (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
            ],
            'testoptionkey2' => (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
                'default' => true,
            ],
            'testoptionkey3' => (object)[
                'key' => 'testoptionkey3',
                'name' => 'testoptionname3',
            ],
        ];

        $this->assertEquals($expectedoptions, $options);

        // Check that the records have been ordered correctly.
        $this->assertEquals(
            implode(',', array_keys($expectedoptions)),
            implode(',', array_keys($options))
        );
    }

    public function test_get_options_default_with_all() {
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle', 'testoptionkey2');

        $optionsloader1 = function () {
            return [
                'testoptionkey1' => 'testoptionname1',
                'testoptionkey2' => 'testoptionname2',
                'testoptionkey3' => 'testoptionname3',
            ];
        };
        $single->add_options_loader($optionsloader1);

        // Add 'All' between the other two options loaders (order of execution doesn't matter).
        $single->add_all_option('xtestallname', 'xtestallkey');

        $options = $single->get_options();
        $expectedoptions = [
            'xtestallkey' => (object)[
                'key' => 'xtestallkey',
                'name' => 'xtestallname',
            ],
            'testoptionkey1' => (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
            ],
            'testoptionkey2' => (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
                'default' => true,
            ],
            'testoptionkey3' => (object)[
                'key' => 'testoptionkey3',
                'name' => 'testoptionname3',
            ],
        ];

        $this->assertEquals($expectedoptions, $options);

        // Check that the records have been ordered correctly.
        $this->assertEquals(
            implode(',', array_keys($expectedoptions)),
            implode(',', array_keys($options))
        );
    }

    public function test_get_options_all_conflict() {
        $optionsloader1 = function () {
            return [
                'testoptionkey1' => 'testoptionname1',
            ];
        };

        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single->add_options_loader($optionsloader1);

        $single->add_all_option('xtestallname', 'testoptionkey1');

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Tried to add an \'all\' option with a key already in use');
        $single->get_options();
    }

    public function test_set_current_data() {
        // When an unknown key has been set, data is set to null.
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single->set_current_data(['testmergeselectkey' => 'testdatakey']);
        $single->set_current_data(['randommergeselectkey' => 'randomdatakey']);
        $this->assertNull($single->get_data());

        // When expected key is set.
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single->set_current_data(['testmergeselectkey' => 'testdatakey']);
        $this->assertEquals('testdatakey', $single->get_data());

        // When 'All' key is set, data is set to null.
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single->add_all_option('testallname', 'testallkey');
        $single->set_current_data(['testmergeselectkey' => 'testallkey']);
        $this->assertNull($single->get_data());
    }

    public function test_merge() {
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $optionsloader1 = function () {
            return [
                'testoptionkey1' => 'testoptionname1',
            ];
        };
        $single1->add_options_loader($optionsloader1);

        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $optionsloader2 = function () {
            return [
                'testoptionkey2' => 'testoptionname2',
            ];
        };
        $single2->add_options_loader($optionsloader2);

        $this->assertTrue($single1->can_merge($single2));
        $single1->merge($single2);

        // Options have been merged.
        $options = $single1->get_options();
        $expectedoptions = [
            'testoptionkey1' => (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
                'default' => true,
            ],
            'testoptionkey2' => (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
            ],
        ];

        $this->assertEquals($expectedoptions, $options);
    }

    public function test_get_template() {
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $optionsloader = function () {
            return [
                'testoptionkey1' => 'testoptionname1',
                'testoptionkey2' => 'testoptionname2',
                'testoptionkey3' => 'testoptionname3',
            ];
        };
        $single->add_options_loader($optionsloader);
        $single->set_title_hidden();
        $single->set_current_data(['testmergeselectkey' => 'testoptionkey2']);

        $template = $single->get_template();

        $this->assertEquals('totara_core\output\select_tree', get_class($template));
        $data = $template->get_template_data();

        // Just check that there are three options. They've been reformatted.
        $this->assertCount(3, $data['options']);
        unset($data['options']);

        $expecteddata = [
            'key' => 'testmergeselectkey',
            'title' => 'testtitle',
            'title_hidden' => true,
            'active_name' => 'testoptionname2',
            'flat_tree' => true,
            'parents_are_selectable' => true,
        ];
        $this->assertEquals($expecteddata, $data);
    }
}
