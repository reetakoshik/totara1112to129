<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_tag
 */

defined('MOODLE_INTERNAL') || die();

use totara_catalog\local\{config, param_processor};


/**
 * @group totara_catalog
 */
class core_tag_totara_catalog_tag_string_case_testcase extends advanced_testcase {
    /**
     * Test rendering to expect whether the tag is using the original case that user input. And to not
     * expecting the lower case name of tag.
     *
     * This test suite is for course.
     *
     * @return void
     */
    public function test_get_course_tag_rawname(): void {
        global $PAGE, $OUTPUT;
        $PAGE->set_url("/");

        $this->resetAfterTest();
        static::setAdminUser();

        $gen = static::getDataGenerator();
        $course = $gen->create_course();

        \core_tag_tag::add_item_tag(
            'core',
            'course',
            $course->id,
            \context_course::instance($course->id),
            'Hello World'
        );

        $config = config::instance();
        $configdata = $config->get();

        // Change the item_additional_text to include the tags, so that it is able to test what the rendered content is.
        if (!array_key_exists('item_additional_text', $configdata)) {
            static::fail("No key `item_additional_text` in config");
        }

        $configdata['item_additional_text']['course'] = ['catalog_learning_type', 'tags'];
        $config->update($configdata);

        $catalog = param_processor::get_template();
        $renderer = $OUTPUT->render($catalog);

        static::assertContains(
            "Hello World",
            $renderer,
            "The tag name must be rendedered with the same character case as the value inserted"
        );

        static::assertNotContains("hello world", $renderer);
    }

    /**
     * Test rendering to expect whether the tag is using the original case that user input. And to not expecting
     * that the rendered content contains the lowercase name of tag.
     *
     * This test suite is for program.
     *
     * @return void
     */
    public function test_get_program_tag_rawname(): void {
        global $PAGE, $OUTPUT;
        $PAGE->set_url("/");

        $this->resetAfterTest();
        static::setAdminUser();

        $gen = static::getDataGenerator();

        /** @var totara_program_generator $proggen */
        $proggen = $gen->get_plugin_generator('totara_program');
        $program = $proggen->create_program([]);


        \core_tag_tag::add_item_tag(
            'totara',
            'prog',
            $program->id,
            \context_program::instance($program->id),
            'Hello World'
        );

        $config = config::instance();
        $configdata = $config->get();

        if (!array_key_exists('item_additional_text', $configdata)) {
            static::fail("No key `item_additional_text` in config");
        }

        $configdata['item_additional_text']['program'] = ['catalog_learning_type', 'tags'];
        $config->update($configdata);

        $catalog = param_processor::get_template();
        $rendered = $OUTPUT->render($catalog);

        static::assertContains(
            'Hello World',
            $rendered,
            "The tag name must be rendedered with the same character case as the value inserted"
        );

        static::assertNotContains('hello world', $rendered);
    }
}