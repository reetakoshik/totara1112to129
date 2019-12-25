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
use totara_catalog\local\config;
use totara_catalog\local\feature_handler;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Class feature_handler_test
 *
 * Test feature handler methods.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_feature_handler_testcase extends advanced_testcase {

    /**
     * Test get_all_features() method.
     */
    public function test_get_all_features() {
        $this->resetAfterTest();

        $features = feature_handler::instance()->get_all_features();
        $this->assertCount(4, $features);
        $keys = [];
        foreach ($features as $feature) {
            $this->assertInstanceOf(feature::class, $feature);
            $keys[] = $feature->key;
        }

        // Find course and prog tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('totara_program', 'prog');

        // Make sure the expected default features are there.
        $this->assertEquals(
            [
                'course_format_ftrd',
                'tag_' . $tagcollectionid,
                'course_type_ftrd',
                'cat_cgry_ftrd',
            ],
            $keys
        );

        // Make sure features are returned for active providers only.
        config::instance()->update(['learning_types_in_catalog' => ['program', 'certification']]);
        feature_handler::instance()->reset_cache();
        provider_handler::instance()->reset_cache();
        $features = feature_handler::instance()->get_all_features();
        $this->assertCount(2, $features);
        $keys = [];
        foreach ($features as $feature) {
            $this->assertInstanceOf(feature::class, $feature);
            $keys[] = $feature->key;
        }
        // Make sure no course features are there.
        $this->assertEquals(
            [
                'tag_' . $tagcollectionid,
                'cat_cgry_ftrd',
            ],
            $keys
        );
    }

    /**
     * Test get_current_feature() method.
     */
    public function test_get_current_feature() {
        $this->resetAfterTest();

        // Default should be null (no active current feature).
        $current_feature = feature_handler::instance()->get_current_feature();
        $this->assertNull($current_feature);

        // Set 'Miscellaneous' category as current feature.
        $c1 = $this->getDataGenerator()->create_category(['name' => 'Test category']);
        $config = config::instance();
        $config->update(
            [
                'featured_learning_enabled' => '1',
                'featured_learning_source' => 'cat_cgry_ftrd',
                'featured_learning_value' => $c1->id,
            ]
        );

        feature_handler::instance()->reset_cache();
        $current_feature = feature_handler::instance()->get_current_feature();
        $this->assertInstanceOf(feature::class, $current_feature);
        $this->assertSame('cat_cgry_ftrd', $current_feature->key);
        $datafilter = $current_feature->datafilter;

        // Use reflection so we can look at the filter source to verify the correct category id is in the current feature.
        $refl = new ReflectionClass(filter::class);
        $refl_property = $refl->getProperty('sources');
        $refl_property->setAccessible(true);
        $sources = $refl_property->getValue($datafilter);
        $this->assertSame($c1->id, $sources[0]->additionalparams['cat_cgry_ftrd_id']);
    }
}
