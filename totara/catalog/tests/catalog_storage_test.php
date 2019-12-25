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

use totara_catalog\local\catalog_storage;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_catalog_storage_testcase extends advanced_testcase {

    public function setUp() {
        global $DB;

        parent::setUp();
        $DB->delete_records('catalog');
        $this->resetAfterTest(true);
    }

    public function test_update_records() {
        global $DB;
        $this->assertSame(0, $DB->count_records('catalog'), "catalog records exist after delete");

        // The catalog listens for course/program/certification CRUD events and then invokes
        // catalog_storage::update_records(). Turning this off allows for direct testing of
        // catalog_storage::update_records().
        $sink = $this->redirectEvents();

        $raw = [];
        $type = 'course';
        $context = context_system::instance();
        for ($i = 0; $i < 3; $i++) {
            $raw[] = (object)[
                'contextid' => $context->id,
                'objectid' => $this->getDataGenerator()->create_course()->id,
                'objecttype' => $type
            ];
        }
        catalog_storage::update_records($raw);
        $this->assertSame(count($raw), $DB->count_records('catalog'), "wrong catalog records count");

        $updated_course_ids = [];
        $updated_name = "updated course name";
        foreach ($raw as $record) {
            $updated_course_id[] = $record->objectid;
            $record->sorttype = $updated_name;
        }

        for ($i = 0; $i < 5; $i++) {
            $raw[] = (object)[
                'contextid' => $context->id,
                'objectid' => $this->getDataGenerator()->create_course()->id,
                'objecttype' => $type
            ];
        }
        catalog_storage::update_records($raw);

        $records = $DB->get_records('catalog');
        $this->assertCount(count($raw), $records, "wrong catalog records count");
        foreach ($records as $record) {
            if (in_array($record->objectid, $updated_course_ids)) {
                $this->assertSame($updated_name, $record->sorttext, "wrong sort text for updated record");
            }

            $this->assertSame($type, $record->objecttype, "wrong type");
            $this->assertEquals($context->id, $record->contextid, "wrong context id");
        }

        $sink->close();
    }

    public function test_update_records_for_same_object_data() {
        global $DB;
        $objects = [];
        $type = 'course';
        $context = context_system::instance();
        for ($i = 0; $i < 3; $i++) {
            $objects[] = (object)[
                'contextid'  => $context->id,
                'objectid'   => $this->getDataGenerator()->create_course()->id,
                'objecttype' => $type,
            ];
        }

        catalog_storage::update_records($objects);
        $this->assertSame(count($objects), $DB->count_records('catalog'));

        // repopulate same object data
        $provider = provider_handler::instance()->get_provider($type);
        catalog_storage::populate_provider_data($provider);

        $this->assertSame(count($objects), $DB->count_records('catalog'));
    }

    public function test_delete_records() {
        global $DB;
        $this->assertSame(0, $DB->count_records('catalog'), "catalog records exist after delete");

        // The algorithm for update/delete matches catalog records not by record id but the
        // object id/object type combination. Therefore this test creates records directly in
        // the catalog table to force object ids to be the same but for different object types.
        // Using the normal $generator->create_XYZ(), then catalog_storage::populate_provider_data()
        // does not allow this to happen.
        $no_of_records = 20;
        $types = [
            'course' => [],
            'program' => [],
            'certification' => []
        ];
        foreach (array_keys($types) as $type) {
            for ($i = 0; $i < $no_of_records; $i++) {
                $object_id = 12300 + $i;

                $record = [
                    'contextid' => 100,
                    'objectid' => $object_id,
                    'objecttype' => $type,
                    'sorttext' => "$type $i"
                ];
                $DB->insert_record('catalog', (object)$record, true);

                $types[$type][] = $object_id;
            }
        }
        $this->assertSame(
            $no_of_records * count($types),
            $DB->count_records('catalog'),
            "catalog records do not exist after insert"
        );

        foreach (array_keys($types) as $type) {
            $ids = $types[$type];
            catalog_storage::delete_records($type, $ids);

            $exists = catalog_storage::has_provider_data($type);
            $this->assertFalse($exists, "'$type' records exist in catalog after delete()");
        }

        $this->assertSame(0, $DB->count_records('catalog'), "catalog records exist after delete");
    }

    public function test_populate_and_delete() {
        global $DB;
        $this->assertSame(0, $DB->count_records('catalog'), "catalog records exist after delete");

        $generator = $this->getDataGenerator();
        /** @var totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        for ($i = 0; $i < 20; $i++) {
            $generator->create_course();
            $program_generator->create_program();
            $program_generator->create_certification();
        }

        $types = ['course', 'program', 'certification'];
        $provider_handler = provider_handler::instance();
        foreach ($types as $type) {
            $provider = $provider_handler->get_provider($type);
            catalog_storage::populate_provider_data($provider);

            $exists = catalog_storage::has_provider_data($type);
            $this->assertTrue($exists, "'$type' records do not exist in catalog after populate()");
        }

        // Robustness check to see if the methods still work if an unknown type is passed in.
        $unknown = "does not exist";
        $exists = catalog_storage::has_provider_data($unknown);
        $this->assertFalse($exists, "'$unknown' records exist in catalog after populate()");
        catalog_storage::delete_provider_data($unknown);

        foreach ($types as $type) {
            catalog_storage::delete_provider_data($type);

            $exists = catalog_storage::has_provider_data($type);
            $this->assertFalse($exists, "'$type' records exist in catalog after delete()");
        }

        $this->assertSame(0, $DB->count_records('catalog'), "catalog records exist after all deleted");
    }
}
