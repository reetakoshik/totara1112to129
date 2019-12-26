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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_catalog
 */

use totara_catalog\catalog_retrieval;
use totara_catalog\feature;
use totara_catalog\cache_handler;
use totara_catalog\local\config;
use totara_catalog\local\feature_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_category_feature_testcase extends advanced_testcase {
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $category_count no of categories to generate.
     * @param int $course_count no of courses to generate.
     *
     * @return array (mapping of categories to courses, feature, all courses)
     *         tuple.
     */
    private function generate(int $category_count = 5, int $course_count = 20): array {
        $generated_categories = [];
        $all_categories = [];
        for ($i = 0; $i < $category_count; $i++) {
            // The category filter works by filtering on the category context id,
            // not the category id.
            $category_id = $this->getDataGenerator()->create_category()->id;
            $context_id = context_coursecat::instance($category_id)->id;
            $generated_categories[] = [$context_id, $category_id];
            $all_categories[] = $context_id;
        }

        // Creating courses indirectly updates the catalog.
        $category_courses = [];
        $all_courses = [];
        for ($i = 0; $i < $course_count; $i++) {
            $j = $i % count($generated_categories);
            [$context_id, $category_id] = $generated_categories[$j];

            if (!array_key_exists($context_id, $category_courses)) {
                $category_courses[$context_id] = [];
            }

            $course = $this->getDataGenerator()->create_course(['category' => $category_id]);
            $category_courses[$context_id][] = $course->fullname;
            $all_courses[] = $course->fullname;
        }

        /** @var \totara_catalog\feature $feature */
        $feature = null;
        foreach (feature_handler::instance()->get_all_features() as $existing) {
            if ($existing->key === 'cat_cgry_ftrd') {
                $feature = $existing;
                break;
            }
        }
        $this->assertNotNull($feature, "feature not loaded");

        return [$all_categories, $category_courses, $feature, $all_courses];
    }

    /**
     * Returns the catalog search result after setting up the specified featured
     * learning options.
     *
     * @param string $source featured learning source.
     * @param string $value featured learning value.
     * @param bool $enabled whether the catalog featured learning facility is
     *        enabled.
     *
     * @return \stdClass retrieval result.
     */
    private function featured_learning_result(
        string $source,
        string $value,
        bool $enabled = true
    ): \stdClass {
        cache_handler::reset_all_caches();
        config::instance()->update(
            [
                'featured_learning_enabled' => $enabled,
                'featured_learning_source' => $source,
                'featured_learning_value' => $value
            ]
        );

        $catalog = new catalog_retrieval();
        return $catalog->get_page_of_objects(1000, 0);
    }

    public function test_category_feature() {
        [$all_categories, $category_courses, $feature, $all_courses] = $this->generate();

        foreach ($category_courses as $context_id => $courses) {
            $result = $this->featured_learning_result($feature->key, $context_id);

            foreach ($result->objects as $i => $retrieved) {
                if ($i < count($courses)) {
                    $this->assertContains($retrieved->sorttext, $courses, "wrong featured for category");
                    $this->assertSame(1, (int)$retrieved->featured, "featured course not at top of retrieved");
                } else {
                    $this->assertContains($retrieved->sorttext, $all_courses, "unknown course");
                    $this->assertSame(0, (int)$retrieved->featured, "non featured course at top of retrieved");
                }
            }
        }

        // Test feature with non existent option. This is not possible via the
        // UI, but nonetheless it is possible programmatically.
        $result = $this->featured_learning_result($feature->key, 9922);
        $this->assertCount(count($all_courses), $result->objects, "wrong retrieved count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "unknown course");
            $this->assertSame(0, (int)$retrieved->featured, "featured course present");
        }

        // Test disabled feature selection even if a valid option is there.
        $result = $this->featured_learning_result($feature->key, $all_categories[0], false);
        $this->assertCount(count($all_courses), $result->objects, "wrong retrieved count");

        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "unknown item");
            $this->assertObjectNotHasAttribute('featured', $retrieved, "featured field exists");
        }
    }
}
