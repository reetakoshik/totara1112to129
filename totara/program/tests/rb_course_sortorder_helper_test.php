<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_program
 */
class rb_course_sortorder_helper_testcase extends advanced_testcase {

    /**
     * An integration test of the complete operation of the class.
     */
    public function test_basic_operation() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var totara_program_generator $prog_generator */
        $prog_generator = $generator->get_plugin_generator('totara_program');

        // Generate a program and test it works for a basic program without content.
        $prog = $prog_generator->create_program();
        $order = \totara_program\rb_course_sortorder_helper::get_sortorder($prog->id);
        self::assertSame([], array_values($order));

        // Now add two courses to the program.
        $course1 = $generator->create_course(['fullname' => 'Apples', 'shortname' => 'A', 'idnumber' => 'a']);
        $course2 = $generator->create_course(['fullname' => 'Bananas', 'shortname' => 'B', 'idnumber' => 'b']);
        $prog_generator->add_courses_and_courseset_to_program($prog, [[$course2, $course1]]);
        $prog = new program($prog->id);
        $order = \totara_program\rb_course_sortorder_helper::get_sortorder($prog->id);
        self::assertSame([$course2->id, $course1->id], array_values($order));

        // Add a third course to the program.
        $content = $prog->get_content();
        $coursesets = $content->get_course_sets();
        self::assertCount(1, $coursesets);
        $courseset = reset($coursesets);
        $course3 = $generator->create_course(['fullname' => 'Carrots', 'shortname' => 'C', 'idnumber' => 'c']);
        self::assertTrue($content->add_course($courseset->sortorder, (object)[$courseset->uniqueid . 'courseid' => $course3->id]));
        $content->save_content();

        // Check the program contains the coursesets and courses we expect.
        $prog = new program($prog->id);
        $coursesets = $prog->get_content()->get_course_sets();
        self::assertCount(1, $coursesets);
        self::assertCount(3, current($coursesets)->get_courses());

        // Check that the order has been updated.
        $order = \totara_program\rb_course_sortorder_helper::get_sortorder($prog->id);
        self::assertSame([$course2->id, $course1->id, $course3->id], array_values($order));

        // Delete the program.
        $prog->delete();
        $order = \totara_program\rb_course_sortorder_helper::get_sortorder($prog->id);
        self::assertSame([], array_values($order));
    }

    public function test_deleting_an_empty_program() {
        $this->resetAfterTest();

        /** @var totara_program_generator $prog_generator */
        $prog_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        // Test deleting a newly created program.
        $prog = $prog_generator->create_program();
        $order = \totara_program\rb_course_sortorder_helper::get_sortorder($prog->id);
        self::assertSame([], array_values($order));
        $prog->delete();
        $order = \totara_program\rb_course_sortorder_helper::get_sortorder($prog->id);
        self::assertSame([], array_values($order));
    }

    public function test_get_order_on_invalid_program() {
        $order = \totara_program\rb_course_sortorder_helper::get_sortorder(0);
        self::assertIsArray($order);
        self::assertCount(0, $order);
    }

    public function test_get_order_on_multiple_programs() {
        $this->resetAfterTest();

        /** @var totara_program_generator $prog_generator */
        $prog_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $prog1 = $prog_generator->create_program();
        $prog2 = $prog_generator->create_program();

        $definition = new cache_definition();
        $instance = \totara_program\rb_course_sortorder_helper::get_instance_for_cache($definition);
        $results = $instance->load_many_for_cache([$prog1->id, $prog2->id]);
        self::assertIsArray($results);
        self::assertCount(2, $results);
        self::assertArrayHasKey($prog1->id, $results);
        self::assertArrayHasKey($prog2->id, $results);
        self::assertIsArray($results[$prog1->id]);
        self::assertIsArray($results[$prog2->id]);
        self::assertCount(0, $results[$prog1->id]);
        self::assertCount(0, $results[$prog2->id]);

        $course = $this->getDataGenerator()->create_course();
        $prog_generator->add_courses_and_courseset_to_program($prog1, [[$course]]);

        $results = $instance->load_many_for_cache([$prog1->id, $prog2->id]);
        self::assertIsArray($results);
        self::assertCount(2, $results);
        self::assertArrayHasKey($prog1->id, $results);
        self::assertArrayHasKey($prog2->id, $results);
        self::assertIsArray($results[$prog1->id]);
        self::assertIsArray($results[$prog2->id]);
        self::assertCount(1, $results[$prog1->id]);
        self::assertCount(0, $results[$prog2->id]);

    }

}