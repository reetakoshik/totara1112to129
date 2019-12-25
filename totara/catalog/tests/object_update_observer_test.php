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

use totara_certification\totara_catalog\certification as certification_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_object_update_observer_testcase extends advanced_testcase {

    public function test_process_with_register_for_update() {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        // Create course created event (without actually creating the course - it was created earlier).
        $event = \core\event\course_created::create(
            [
                'objectid' => $course->id,
                'context'  => \context_course::instance($course->id),
                'other'    => [
                    'shortname' => $course->shortname,
                    'fullname'  => $course->fullname,
                ],
            ]
        );

        // Get the observer.
        $observer = new core_course\totara_catalog\course\observer\course('course', $event);

        // Delete the course from the catalog if it is there already.
        $DB->delete_records('catalog', ['objectid' => $course->id, 'objecttype' => 'course']);

        // Process the observer. This should cause init_change_objects to be called, which in turn marks the course
        // for creation in the catalog, then the creations are processed.
        $observer->process();

        // Check that the record has been created in the catalog.
        $this->assertEquals(
            1,
            $DB->count_records('catalog', ['objectid' => $course->id, 'objecttype' => 'course'])
        );
    }

    public function test__process_with_register_for_delete() {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        // Create course deleted event (without actually deleting the course).
        $event = \core\event\course_deleted::create(
            [
                'objectid' => $course->id,
                'context'  => \context_course::instance($course->id),
                'other'    => [
                    'shortname' => $course->shortname,
                    'fullname'  => $course->fullname,
                ],
            ]
        );

        // Get the observer.
        $observer = new core_course\totara_catalog\course\observer\course_delete('course', $event);

        // Check that the course is in the catalog.
        $this->assertEquals(
            1,
            $DB->count_records('catalog', ['objectid' => $course->id, 'objecttype' => 'course'])
        );

        // Process the observer. This should cause init_change_objects to be called, which in turn marks the course
        // for deletion from the catalog, then the deletions are processed.
        $observer->process();

        // Check that the record has been deleted from the catalog.
        $this->assertEquals(
            0,
            $DB->count_records('catalog', ['objectid' => $course->id, 'objecttype' => 'course'])
        );
    }

    public function test_process_with_inactive_provider() {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        // Create course created event (without actually creating the course - it was created earlier).
        $event = \core\event\course_created::create(
            [
                'objectid' => $course->id,
                'context'  => \context_course::instance($course->id),
                'other'    => [
                    'shortname' => $course->shortname,
                    'fullname'  => $course->fullname,
                ],
            ]
        );

        // Get the observer.
        $observer = new core_course\totara_catalog\course\observer\course('course', $event);

        // Turn off the course provider.
        \totara_catalog\local\config::instance()->update(['learning_types_in_catalog' => ['program']]);
        \totara_catalog\cache_handler::reset_all_caches();

        // Check that the record is NOT in the catalog, because it was removed when the provider was disabled.
        $this->assertEquals(
            0,
            $DB->count_records('catalog', ['objectid' => $course->id, 'objecttype' => 'course'])
        );

        // Process the observer. Nothing should happen in the catalog.
        $observer->process();

        // Check that the record has NOT been created in the catalog.
        $this->assertEquals(
            0,
            $DB->count_records('catalog', ['objectid' => $course->id, 'objecttype' => 'course'])
        );
    }

    public function test_certification_with_register_for_delete() {
        global $DB;

        $this->provider = new certification_provider();
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $programid = $program_generator->create_certification();
        $certification = new program($programid);

        // Create certification created event.
        $event = \totara_program\event\program_created::create(
            [
                'objectid' => $certification->id,
                'context'  => \context_program::instance($certification->id),
                'other'    => [
                    'certifid' => $certification->certifid,
                ],
            ]
        );

        // Get the observer.
        $observer = new totara_certification\totara_catalog\certification\observer\certification_deleted('certification', $event);

        // Check that certification is in the catalog.
        $this->assertEquals(
            1,
            $DB->count_records('catalog', ['objectid' => $certification->id, 'objecttype' => 'certification'])
        );

        // Delete the certification.
        $certification->delete();

        // Process the observer.
        $observer->process();

        // Check that certification records have been deleted and certification is removed from the catalog.
        $this->assertEquals(
            0,
            $DB->count_records('certif', ['id' => $certification->certifid])
        );

        $this->assertEquals(
            0,
            $DB->count_records('prog', ['id' => $certification->id])
        );

        $this->assertEquals(
            0,
            $DB->count_records('catalog', ['objectid' => $certification->id, 'objecttype' => 'certification'])
        );
    }
}
