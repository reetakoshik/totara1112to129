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
class totara_catalog_merge_select_tree_testcase extends advanced_testcase {

    public function test_add_all_option() {
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                ],
            ];
        };

        // Default params.
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->add_all_option();
        $options = $tree->get_options();
        $this->assertCount(3, $options);
        unset($options['testoptionkey1']);
        unset($options['testoptionkey2']);
        $expectedoption = (object)[
            'key' => '',
            'name' => get_string('all'),
            'default' => true,
        ];
        $this->assertEquals($expectedoption, $options[0]);

        // Specified params.
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->add_all_option('testallname', 'testallname');
        $options = $tree->get_options();
        $this->assertCount(3, $options);
        $this->assertCount(3, $options);
        unset($options['testoptionkey1']);
        unset($options['testoptionkey2']);
        $expectedoption = (object)[
            'key' => 'testallname',
            'name' => 'testallname',
            'default' => true,
        ];
        $this->assertEquals($expectedoption, $options[0]);
    }

    public function test_get_options_without_all() {
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                ],
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);

        $options = $tree->get_options();
        $expectedoptions = [
            (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
                'default' => true, // No sorting occurs in trees. The first item is the default.
            ],
            (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
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
        // All comes first and is default, regardless of alphabetical ordering (sorting doesn't happen in trees).
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                ],
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->add_all_option('xtestallname', 'xtestallkey');

        $options = $tree->get_options();
        $expectedoptions = [
            (object)[
                'key' => 'xtestallkey',
                'name' => 'xtestallname',
                'default' => true, // No sorting occurs in trees. The first item is the default.
            ],
            (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
            ],
            (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
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
        $optionsloader1 = function () {
            return [
                (object)[
                    'key' => 'testoptionkey3',
                    'name' => 'testoptionname3',
                ],
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                    'default' => true,
                ],
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader1);

        $options = $tree->get_options();
        $expectedoptions = [
            (object)[
                'key' => 'testoptionkey3',
                'name' => 'testoptionname3',
            ],
            (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
                'default' => true,
            ],
            (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
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
        $optionsloader1 = function () {
            return [
                (object)[
                    'key' => 'testoptionkey3',
                    'name' => 'testoptionname3',
                ],
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                    'default' => true,
                ],
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };

        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader1);

        // Add 'All' between the other two options loaders (order of execution doesn't matter).
        $tree->add_all_option('xtestallname', 'xtestallkey');

        $options = $tree->get_options();
        $expectedoptions = [
            (object)[
                'key' => 'xtestallkey',
                'name' => 'xtestallname',
            ],
            (object)[
                'key' => 'testoptionkey3',
                'name' => 'testoptionname3',
            ],
            (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
                'default' => true,
            ],
            (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
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
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };

        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);

        $tree->add_all_option('xtestallname', 'testoptionkey1');

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Tried to add an \'all\' option with a key already in use');
        $tree->get_options();
    }

    public function test_set_current_data() {
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };

        // When an unknown merge select key has been set, the data is ignored.
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->set_current_data(['randommergeselectkey' => 'randomkeyselected']);
        $this->assertNull($tree->get_data());

        // When the expected merge select key has been set, the data is stored.
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->set_current_data(['testmergeselectkey' => 'randomkeyselected']);
        $this->assertEquals('randomkeyselected', $tree->get_data());

        // When the data is set to the 'All' key, data is set to null.
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->add_all_option('testallname', 'testallkey');
        $tree->set_current_data(['testmergeselectkey' => 'randomkeyselected']);
        $tree->set_current_data(['testmergeselectkey' => 'testallkey']);
        $this->assertNull($tree->get_data());
    }

    public function test_merge_works_if_identical() {
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                ],
            ];
        };

        $tree1 = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree2 = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);

        $this->assertTrue($tree1->can_merge($tree2));
        $tree1->merge($tree2);

        // Options have been unchanged.
        $options = $tree1->get_options();
        $expectedoptions = [
            (object)[
                'key' => 'testoptionkey1',
                'name' => 'testoptionname1',
                'default' => true,
            ],
            (object)[
                'key' => 'testoptionkey2',
                'name' => 'testoptionname2',
            ],
        ];

        $this->assertEquals($expectedoptions, $options);
    }

    public function test_merge_fails_if_different() {
        $optionsloader1 = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };
        $optionsloader2 = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname2',
                ],
            ];
        };

        $tree1 = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader1);
        $tree2 = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader2);

        $this->assertFalse($tree1->can_merge($tree2));
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Tried to merge two selectors that are not identical');
        $tree1->merge($tree2);
    }

    public function test_can_merge() {
        $optionsloader1 = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
            ];
        };
        $optionsloader2 = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname2',
                ],
            ];
        };

        $tree1 = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader1);
        $tree2 = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader1);
        $tree3 = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader2);

        $this->assertTrue($tree1->can_merge($tree2));
        $this->assertFalse($tree1->can_merge($tree3));
    }

    public function test_get_template_and_is_not_flat() {
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                    'children' => [
                        (object)[
                            'key' => 'testoptionkey4',
                            'name' => 'testoptionname4',
                        ],
                    ],
                ],
                (object)[
                    'key' => 'testoptionkey3',
                    'name' => 'testoptionname3',
                ],
            ];
        };
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->set_title_hidden();
        $tree->set_current_data(['testmergeselectkey' => 'testoptionkey4']);

        $template = $tree->get_template();

        $this->assertEquals('totara_core\output\select_tree', get_class($template));
        $data = $template->get_template_data();

        // Just check that there are three top level options and that the second has a child. They've been reformatted.
        $this->assertCount(3, $data['options']);
        $this->assertCount(1, $data['options'][1]->children);
        unset($data['options']);

        $expecteddata = [
            'key' => 'testmergeselectkey',
            'title' => 'testtitle',
            'title_hidden' => true,
            'active_name' => 'testoptionname4',
            'flat_tree' => false,
            'parents_are_selectable' => true,
        ];
        $this->assertEquals($expecteddata, $data);
    }

    public function test_get_template_and_is_flat() {
        $optionsloader = function () {
            return [
                (object)[
                    'key' => 'testoptionkey1',
                    'name' => 'testoptionname1',
                ],
                (object)[
                    'key' => 'testoptionkey2',
                    'name' => 'testoptionname2',
                    'children' => [],
                ],
                (object)[
                    'key' => 'testoptionkey3',
                    'name' => 'testoptionname3',
                ],
            ];
        };
        $tree = new \totara_catalog\merge_select\tree('testmergeselectkey', 'testtitle', $optionsloader);
        $tree->set_title_hidden();
        $tree->set_current_data(['testmergeselectkey' => 'testoptionkey2']);

        $template = $tree->get_template();

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
