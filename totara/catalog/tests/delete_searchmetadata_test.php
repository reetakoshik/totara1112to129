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
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

use totara_catalog\search_metadata\search_metadata;

class totara_catalog_delete_searchmetadata_testcase extends advanced_testcase {
    /**
     * Create a course and a search metadata that is link to that course. Then call to
     * delete_course function and expecting the search_metadata record to be deleted when the course
     * is deleted as well.
     *
     * @return void
     */
    public function test_delete_searchmetadata_via_observer_for_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $gen = static::getDataGenerator();

        $course = $gen->create_course([], ['createsection' => true]);

        $metadata = new search_metadata();
        $metadata->set_instanceid($course->id);
        $metadata->set_plugintype('core');
        $metadata->set_pluginname('course');
        $metadata->set_value("hello world this is a keyword");
        $metadata->save();

        delete_course($course, false);

        // After the course is deleted, check for the existing of metadata record.
        static::assertNotEmpty($metadata->id);
        static::assertNotEmpty($metadata->instanceid);

        static::assertFalse(
            $DB->record_exists(search_metadata::DBTABLE, ['id' => $metadata->id])
        );

        static::assertFalse(
            $DB->record_exists('course', ['id' => $metadata->instanceid])
        );
    }

    /**
     * Create a program and search metadata that is link to that program. Then call to delete functionality of program
     * and expecting the search_metadata to be deleted as well once the program is deleted.
     */
    public function test_delete_search_metadata_via_observer_for_program(): void {
        global $DB;

        $this->resetAfterTest(true);
        $gen = static::getDataGenerator();

        /** @var totara_program_generator $proggen */
        $proggen = $gen->get_plugin_generator('totara_program');
        $prog = $proggen->create_program();

        $metadata = new search_metadata();
        $metadata->set_value('Hello World this is a keyword');
        $metadata->set_plugintype('totara');
        $metadata->set_pluginname('program');
        $metadata->set_instanceid($prog->id);
        $metadata->save();

        $prog->delete();

        // After program is deleted, check for the existing of metadata record.
        static::assertNotEmpty($metadata->id);
        static::assertNotEmpty($metadata->instanceid);

        static::assertFalse(
            $DB->record_exists(search_metadata::DBTABLE, ['id' => $metadata->id])
        );

        static::assertFalse(
            $DB->record_exists('prog', ['id' => $metadata->instanceid])
        );
    }

    /**
     * Create a certification and a metadata search record for that certification. Then start deleting the certification
     * and expect the record of search metadata to be gone too.
     */
    public function test_delete_search_metadata_via_observer_for_certification(): void {
        global $DB;

        $this->resetAfterTest(true);
        $gen = static::getDataGenerator();

        /** @var totara_program_generator $proggen */
        $proggen = $gen->get_plugin_generator('totara_program');
        $id = $proggen->create_certification();

        // Program and certification are pretty much the same, lets keep it that way.
        $metadata = new search_metadata();
        $metadata->set_instanceid($id);
        $metadata->set_plugintype('totara');
        $metadata->set_pluginname('program');
        $metadata->set_value('Hello world this is not a keyword');
        $metadata->save();

        $cert = new program($id);
        $cert->delete();

        // Start checking the existing of metadata record.
        static::assertNotEmpty($metadata->id);
        static::assertNotEmpty($metadata->instanceid);

        static::assertFalse(
            $DB->record_exists(search_metadata::DBTABLE, ['id' => $metadata->id])
        );

        static::assertFalse(
            $DB->record_exists('prog', ['id' => $metadata->instanceid])
        );
    }
}