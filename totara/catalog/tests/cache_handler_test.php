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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the cache handler class for catalog component.
 *
 * @group totara_catalog
 */
class totara_catalog_cache_handler_testcase extends advanced_testcase {

    public function test_reset_all_caches() {
        $this->resetAfterTest();

        // Providers are enabled by default, so there is some data.
        $this->assertGreaterThan(1, count(\totara_catalog\local\feature_handler::instance()->get_all_features()));
        $this->assertGreaterThan(5, count(\totara_catalog\local\filter_handler::instance()->get_all_filters()));
        $this->assertNotEmpty(\totara_catalog\provider_handler::instance()->get_active_providers());
        $this->assertTrue(\totara_catalog\local\config::instance()->is_provider_active('course'));

        // Switch off the providers.
        set_config('learning_types_in_catalog', "[]", 'totara_catalog');

        // Check that the providers are still using the old cache (to prove our test is valid).
        $this->assertGreaterThan(1, count(\totara_catalog\local\feature_handler::instance()->get_all_features()));
        $this->assertGreaterThan(5, count(\totara_catalog\local\filter_handler::instance()->get_all_filters()));
        $this->assertNotEmpty(\totara_catalog\provider_handler::instance()->get_active_providers());
        $this->assertTrue(\totara_catalog\local\config::instance()->is_provider_active('course'));

        // Reset the caches.
        \totara_catalog\cache_handler::reset_all_caches();

        // Show that the singletons now return the updated information, so must have been reset.
        $this->assertEquals(1, count(\totara_catalog\local\feature_handler::instance()->get_all_features()));
        $this->assertEquals(5, count(\totara_catalog\local\filter_handler::instance()->get_all_filters()));
        $this->assertEmpty(\totara_catalog\provider_handler::instance()->get_active_providers());
        $this->assertFalse(\totara_catalog\local\config::instance()->is_provider_active('course'));
    }
}
