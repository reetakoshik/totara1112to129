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
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course;

use totara_catalog\catalog_retrieval;
use totara_catalog\cache_handler;
use totara_catalog\local\config;
use totara_catalog\local\feature_handler;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/course/lib.php");

/**
 * @group totara_catalog
 */
class core_course_totara_catalog_course_type_feature_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $course_count
     * @return array (type labels, mapping of types to courses, type filters,
     *         all courses) tuple.
     */
    private function generate($course_count = 15): array {
        global $TOTARA_COURSE_TYPES;
        $this->setAdminUser();

        $available_types = [];
        $type_labels = [];
        $strings = get_string_manager();
        foreach ($TOTARA_COURSE_TYPES as $type => $type_id) {
            $label = $strings->get_string($type, 'totara_core');
            $available_types[] = $type_id;
            $type_labels[] = $label;
        }

        // Course types are randomly assigned to courses.
        $type_courses = [];
        $all_courses = [];

        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $course_count; $i++) {
            $j = $i % count($available_types);
            $type_id = $available_types[$j];

            $course = $generator->create_course(['coursetype' => $type_id]);
            $all_courses[] = $course->fullname;

            $courses = array_key_exists($type_id, $type_courses)
                       ? $type_courses[$type_id]
                       : [];
            $courses[] = $course->fullname;
            $type_courses[$type_id] = $courses;
        }

        /** @var \totara_catalog\feature $feature */
        $feature = null;
        foreach (feature_handler::instance()->get_all_features() as $existing) {
            if ($existing->key === 'course_type_ftrd') {
                $feature = $existing;
                break;
            }
        }
        $this->assertNotNull($feature, "feature not loaded");

        return [$type_labels, $type_courses, $feature, $all_courses];
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

    public function test_type_feature() {
        [$type_labels, $type_courses, $feature, $all_courses] = $this->generate();

        foreach ($type_courses as $type => $courses) {
            $result = $this->featured_learning_result($feature->key, $type);

            foreach ($result->objects as $i => $retrieved) {
                if ($i < count($courses)) {
                    $this->assertContains($retrieved->sorttext, $courses, "wrong featured for type");
                    $this->assertSame(1, (int)$retrieved->featured, "featured course not at top of retrieved");
                } else {
                    $this->assertContains($retrieved->sorttext, $all_courses, "unknown course");
                    $this->assertSame(0, (int)$retrieved->featured, "non featured course at top of retrieved");
                }
            }
        }

        // Test feature with non existent option. This is not possible via the
        // UI, but nonetheless it is possible programmatically.
        $result = $this->featured_learning_result($feature->key, 3999);
        $this->assertCount(count($all_courses), $result->objects, "wrong retrieved count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "unknown course");
            $this->assertSame(0, (int)$retrieved->featured, "featured course present");
        }

        // Test disabled feature selection even if a valid option is there.
        $result = $this->featured_learning_result($feature->key, $type_labels[0], false);
        $this->assertCount(count($all_courses), $result->objects, "wrong retrieved count");

        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "unknown item");
            $this->assertObjectNotHasAttribute('featured', $retrieved, "featured field exists");
        }
    }
}
