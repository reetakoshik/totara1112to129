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

use totara_catalog\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Class provider_test
 *
 * Test provider class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_provider_testcase extends advanced_testcase {

    public function setUp() {
        global $DB;

        $this->setAdminUser();
        $DB->delete_records('catalog');
        $this->resetAfterTest();
    }

    public function provider_object_type_data_provider() {
        return [
            ['', false],
            ['_', false],
            ['__', false],
            ['_abc', false],
            ['abc123', false],
            ['abc-abc', false],
            ['abc:abc', false],

            ['a', true],
            ['a_', true],
            ['abc', true],
            ['a_b_c', true],
            ['a_b_c___', true],
            ['a_______b', true],
        ];
    }

    /**
     * @dataProvider provider_object_type_data_provider
     * @param $object_type
     * @param $expected
     */
    public function test_is_valid_object_type($object_type, $expected) {
        $this->assertEquals($expected, provider::is_valid_object_type($object_type));
    }

    public function test_object_update_observer_when_catalog_enabled() {
        global $DB;

        $this->assertSame(0, $DB->count_records('catalog', []));
        set_config('catalogtype', 'totara');
        $this->create_catalog_objects();

        $this->assertSame(3, $DB->count_records('catalog', []));
    }

    public function test_object_update_observer_when_catalog_disabled() {
        global $DB;
        set_config('catalogtype', 'enhanced');
        $this->create_catalog_objects();

        $this->assertSame(0, $DB->count_records('catalog', []));
    }

    private function create_catalog_objects() {
        // create a program
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program_generator->create_program();

        // create a course
        $this->getDataGenerator()->create_course();

        // create a certification
        $program_generator->create_certification();
    }
}
