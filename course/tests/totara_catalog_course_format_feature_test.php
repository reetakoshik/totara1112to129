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
class core_course_totara_catalog_course_format_feature_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $course_count
     * @return array (format labels, mapping of formats to courses, format filters,
     *         all courses) tuple.
     */
    private function generate($course_count = 15): array {
        $this->setAdminUser();

        $format_labels = [];
        $strings = get_string_manager();
        $available_formats = get_sorted_course_formats(true);
        foreach ($available_formats as $format) {
            $format_labels[] = $strings->get_string('pluginname', "format_$format");
        }

        // Formats are randomly assigned to courses.
        $format_courses = [];
        $all_courses = [];

        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $course_count; $i++) {
            $j = $i % count($available_formats);
            $format = $available_formats[$j];

            $course = $generator->create_course(['format' => $format]);
            $all_courses[] = $course->fullname;

            $courses = array_key_exists($format, $format_courses)
                       ? $format_courses[$format]
                       : [];
            $courses[] = $course->fullname;
            $format_courses[$format] = $courses;
        }

        /** @var \totara_catalog\feature $feature */
        $feature = null;
        foreach (feature_handler::instance()->get_all_features() as $existing) {
            if ($existing->key === 'course_format_ftrd') {
                $feature = $existing;
                break;
            }
        }
        $this->assertNotNull($feature, "feature not loaded");

        return [$format_labels, $format_courses, $feature, $all_courses];
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

    public function test_format_feature() {
        [$format_labels, $format_courses, $feature, $all_courses] = $this->generate();

        foreach ($format_courses as $format => $courses) {
            $result = $this->featured_learning_result($feature->key, $format);

            foreach ($result->objects as $i => $retrieved) {
                if ($i < count($courses)) {
                    $this->assertContains($retrieved->sorttext, $courses, "wrong featured for format");
                    $this->assertSame(1, (int)$retrieved->featured, "featured course not at top of retrieved");
                } else {
                    $this->assertContains($retrieved->sorttext, $all_courses, "unknown course");
                    $this->assertSame(0, (int)$retrieved->featured, "non featured course at top of retrieved");
                }
            }
        }

        // Test feature with non existent option. This is not possible via the
        // UI, but nonetheless it is possible programmatically.
        $result = $this->featured_learning_result($feature->key, 'unknown format');
        $this->assertCount(count($all_courses), $result->objects, "wrong retrieved count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "unknown course");
            $this->assertSame(0, (int)$retrieved->featured, "featured course present");
        }

        // Test disabled feature selection even if a valid option is there.
        $result = $this->featured_learning_result($feature->key, $format_labels[0], false);
        $this->assertCount(count($all_courses), $result->objects, "wrong retrieved count");

        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_courses, "unknown item");
            $this->assertObjectNotHasAttribute('featured', $retrieved, "featured field exists");
        }
    }
}
