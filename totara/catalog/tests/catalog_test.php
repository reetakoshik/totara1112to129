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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

use totara_catalog\local\catalog_storage;
use totara_catalog\local\full_text_search_filter;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_catalog_catalog_testcase extends advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_get_default_search_relevance_weight() {
        global $CFG;
        unset($CFG->catalogrelevanceweight);
        $result = full_text_search_filter::get_search_relevance_weight();
        $this->assertSame($result['ftshigh'], full_text_search_filter::FTS_HEIGH_WEIGHT);
        $this->assertSame($result['ftsmedium'], full_text_search_filter::FTS_MEDIUM_WEIGHT);
        $this->assertSame($result['ftslow'], full_text_search_filter::FTS_LOW_WEIGHT);
    }

    public function test_populate_provider_data() {
        global $DB;
        $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course();
        $DB->delete_records('catalog');
        catalog_storage::populate_provider_data(provider_handler::instance()->get_provider('course'));
        $count = $DB->count_records('catalog');
        $this->assertSame(2, $count);
    }

    public function test_populate_provider_data_and_delete_unwanted() {
        global $DB;
        $objecttype = \core_course\totara_catalog\course::get_object_type();
        $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course();
        $DB->insert_record('catalog', (object)['objectid' => 100, 'contextid' => 100, 'objecttype' => $objecttype]);
        catalog_storage::populate_provider_data(provider_handler::instance()->get_provider('course'));
        $record = $DB->get_record('catalog', ['objectid' => 100, 'contextid' => 100, 'objecttype' => $objecttype]);
        $this->assertEmpty($record);
    }
}
