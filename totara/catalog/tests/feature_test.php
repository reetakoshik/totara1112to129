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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

use totara_catalog\datasearch\filter;
use totara_catalog\feature;
use totara_catalog\local\category_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Class feature_test
 *
 * Tests for feature class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_feature_testcase extends advanced_testcase {

    /**
     * Test get_options() method.
     */
    public function test_get_options() {
        /** @var filter $datafilter */
        $datafilter = $this->get_mock_filter();
        $optionsloader = function () {
            return [
                0 => 'c_option',
                1 => 'a_option',
                2 => 'b_option'
            ];
        };
        $sorted_options = [
            1 => 'a_option',
            2 => 'b_option',
            0 => 'c_option'
        ];
        $feature = new feature('test_feature', 'Test feature', $datafilter, 'test_category');
        $feature->add_options_loader($optionsloader);
        $this->assertSame($sorted_options, $feature->get_options());
    }

    /**
     * Test can_merge() method.
     */
    public function test_can_merge() {
        // Datafilters can't merge, so features can't merge.
        $optionsloader = function () {
            return [
                'key1' => 'a_option',
                'key2' => 'b_option',
                'key3' => 'c_option',
            ];
        };
        $datafilter = $this->get_mock_filter(false);
        $feature1 = new feature('test_feature1', 'Test feature1', $datafilter, 'test_category1');
        $feature1->add_options_loader($optionsloader);
        $feature2 = new feature('test_feature2', 'Test feature2', $datafilter, 'test_category2');
        $feature2->add_options_loader($optionsloader);
        $this->assertFalse($feature1->can_merge($feature2));
        $this->assertFalse($feature2->can_merge($feature1));

        // Datafilters can merge, and different option values for the same key doesn't prevent this.
        $datafilter = $this->get_mock_filter(true);
        $optionsloader1 = function () {
            return [
                'key1' => 'a_option',
                'key2' => 'b_option',
                'key3' => 'c_option',
            ];
        };
        $optionsloader2 = function () {
            return [
                'key1' => 'a_option',
                'key2' => 'b_option',
                'key3' => 'orangutan',
            ];
        };
        $feature1 = new feature('test_feature1', 'Test feature1', $datafilter, 'test_category1');
        $feature1->add_options_loader($optionsloader1);
        $feature2 = new feature('test_feature2', 'Test feature2', $datafilter, 'test_category2');
        $feature2->add_options_loader($optionsloader2);
        $this->assertTrue($feature1->can_merge($feature2));
        $this->assertTrue($feature2->can_merge($feature1));
    }

    /**
     * Test merge() method.
     */
    public function test_merge() {
        $datafilter = $this->get_mock_filter(true);

        $optionsloader1 = function () {
            return [
                'key1' => 'a_option',
                'key2' => 'b_option',
                'unique_key1' => 'orangutan',
            ];
        };
        $optionsloader2 = function () {
            return [
                'key1' => 'a_option',
                'key2' => 'b_option',
                'unique_key2' => 'jones',
            ];
        };
        $feature1 = new feature('test_feature1', 'Test feature1', $datafilter, 'test_category1');
        $feature1->add_options_loader($optionsloader1);
        $feature2 = new feature('test_feature2', 'Test feature2', $datafilter, 'test_category2');
        $feature2->add_options_loader($optionsloader2);

        $datafilter->expects($this->once())
            ->method('merge')
            ->with($this->equalTo($datafilter));

        $feature1->merge($feature2);

        $this->assertSame(
            [
                'key1' => 'a_option',
                'key2' => 'b_option',
                'unique_key2' => 'jones',
                'unique_key1' => 'orangutan',
            ],
            $feature1->get_options()
        );
    }

    /**
     * Create a mock filter object.
     *
     * @param bool $return_value_for_can_merge
     * @return filter
     */
    private function get_mock_filter(bool $return_value_for_can_merge = true): filter {
        /** @var filter $datafilter */
        $datafilter = $this->getMockForAbstractClass(
            filter::class,
            ['mock_filter'],
            '',
            true,
            true,
            true,
            ['can_merge', 'merge']
        );

        $datafilter->expects($this->any())
            ->method('can_merge')
            ->will($this->returnValue($return_value_for_can_merge));

        return $datafilter;
    }

    /**
     * Test special feature class category_feature.
     */
    public function test_category_feature() {
        global $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $c1 = $generator->create_category(['name' => 'First level 1']);
        $c2 = $generator->create_category(['name' => 'Second level', 'parent' => $c1->id]);
        $c3 = $generator->create_category(['name' => 'Third level', 'parent' => $c2->id]);
        $c4 = $generator->create_category(['name' => 'First level 2']);

        // Check properties.
        $feature = category_feature::create();
        $this->assertSame('cat_cgry_ftrd', $feature->key);
        $this->assertSame('Category', $feature->title);
        $this->assertInstanceOf(filter::class, $feature->datafilter);
        $this->assertEquals(get_string('default_option_group', 'totara_catalog'), $feature->category);

        // Check get_options() method.
        $options = $feature->get_options();
        $this->assertCount(5, $options);

        $misc_default_cat = $DB->get_record('course_categories', ['name' => 'Miscellaneous']);
        $context_misc = context_coursecat::instance($misc_default_cat->id);
        $context_c1 = context_coursecat::instance($c1->id);
        $context_c2 = context_coursecat::instance($c2->id);
        $context_c3 = context_coursecat::instance($c3->id);
        $context_c4 = context_coursecat::instance($c4->id);

        $this->assertSame('First level 1', $options[$context_c1->id]);
        $this->assertSame('First level 1 / Second level', $options[$context_c2->id]);
        $this->assertSame('First level 1 / Second level / Third level', $options[$context_c3->id]);
        $this->assertSame('First level 2', $options[$context_c4->id]);
        $this->assertSame('Miscellaneous', $options[$context_misc->id]);
    }
}
