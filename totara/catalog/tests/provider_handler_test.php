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

use totara_catalog\dataformatter\formatter;
use totara_catalog\local\config;
use totara_catalog\local\required_dataholder;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/catalog/tests/config_test_base.php');

/**
 * Class provider_handler_test
 *
 * Test provider_handler class.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_provider_handler_testcase extends config_base_testcase {

    /**
     * @var provider_handler
     */
    private $provider_handler = null;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->provider_handler = provider_handler::instance();
    }

    public function tearDown() {
        $this->provider_handler = null;
        parent::tearDown();
    }

    public function test_get_active_providers() {
        // Default (all enabled).
        $activeproviders = provider_handler::instance()->get_active_providers();
        $this->assertCount(3, $activeproviders);
        $this->assertInstanceOf(totara_program\totara_catalog\program::class, $activeproviders['program']);
        $this->assertInstanceOf(core_course\totara_catalog\course::class, $activeproviders['course']);

        config::instance()->reset_cache();
        provider_handler::instance()->reset_cache();

        config::instance()->update(['learning_types_in_catalog' => ['course']]);
        $activeproviders = provider_handler::instance()->get_active_providers();
        $this->assertCount(1, $activeproviders);
        $this->assertInstanceOf(core_course\totara_catalog\course::class, $activeproviders['course']);

        config::instance()->reset_cache();
        provider_handler::instance()->reset_cache();

        config::instance()->update(['learning_types_in_catalog' => []]);
        $activeproviders = provider_handler::instance()->get_active_providers();
        $this->assertEquals([], $activeproviders);

        config::instance()->reset_cache();
        provider_handler::instance()->reset_cache();

        config::instance()->update(['learning_types_in_catalog' => ['course', 'doesnotexist']]);
        $activeproviders = provider_handler::instance()->get_active_providers();
        $this->assertCount(1, $activeproviders);
        $this->assertInstanceOf(core_course\totara_catalog\course::class, $activeproviders['course']);

        config::instance()->reset_cache();
        provider_handler::instance()->reset_cache();

        config::instance()->update(['learning_types_in_catalog' => 'not_an_array']);
        $activeproviders = provider_handler::instance()->get_active_providers();
        $this->assertCount(3, $activeproviders);
        $this->assertInstanceOf(totara_program\totara_catalog\program::class, $activeproviders['program']);
        $this->assertInstanceOf(core_course\totara_catalog\course::class, $activeproviders['course']);
    }

    public function test_instance() {
        $object = provider_handler::instance();
        $this->assertInstanceOf('totara_catalog\\provider_handler', $object);
    }

    public function test_reset_cache() {
        config::instance()->update(['learning_types_in_catalog' => ['course']]);
        $this->assertArrayHasKey('course', $this->provider_handler->get_active_providers());

        // update providers but still get it from the cache
        config::instance()->update(['learning_types_in_catalog' => ['program']]);
        $this->assertArrayHasKey('course', $this->provider_handler->get_active_providers());

        // reset the cache and get the new providers
        $this->provider_handler->reset_cache();
        $this->assertArrayHasKey('program', $this->provider_handler->get_active_providers());
    }

    public function test_get_all_provider_classes() {
        // test all providers
        config::instance()->update(['learning_types_in_catalog' => ['course', 'program']]);
        $this->assertCount(3, $this->provider_handler->get_all_provider_classes());

        // check active providers
        $this->provider_handler->reset_cache();
        $this->assertCount(2, $this->provider_handler->get_active_providers());
    }

    public function test_is_active() {
        $this->assertTrue($this->provider_handler->is_active('course'));

        config::instance()->update(['learning_types_in_catalog' => ['program']]);
        $this->provider_handler->reset_cache();

        $this->assertFalse($this->provider_handler->is_active('course'));
    }

    public function test_get_provider() {
        foreach ($this->provider_handler->get_active_providers() as $provider) {
            $this->assertInstanceOf('totara_catalog\\provider', $provider);
        }
    }

    /**
     * @expectedException coding_exception
     */
    public function test_get_invalid_provider() {
        $this->provider_handler->get_provider('seminar');
    }

    public function test_get_data_for_objects() {
        $data = $this->provider_handler->get_data_for_objects(
            $this->get_test_objects(['course']),
            ['course' => $this->get_data_holders_by_provider('course', formatter::TYPE_PLACEHOLDER_TITLE)]
        );
        $this->assertCount(2, $data);
        $this->assertSame('web', $data[0]->data[formatter::TYPE_PLACEHOLDER_TEXT]['shortname']);
        $this->assertSame('html', $data[1]->data[formatter::TYPE_PLACEHOLDER_TEXT]['shortname']);
    }

    private function get_test_objects(array $types) {
        $objects = [];
        if (in_array('course', $types)) {
            $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Web course', 'shortname' => 'web']);
            $course2 = $this->getDataGenerator()->create_course(['fullname' => 'HTML course', 'shortname' => 'html']);
            $objects = [
                (object)['objectid' => $course1->id, 'objecttype' => 'course', 'contextid' => context_system::instance()->id],
                (object)['objectid' => $course2->id, 'objecttype' => 'course', 'contextid' => context_system::instance()->id],
            ];
        }

        return $objects;
    }

    private function get_data_holders_by_provider(string $objecttype, int $datatype) {
        $provider = $this->provider_handler->get_active_providers()[$objecttype];
        $dataholders = $provider->get_dataholders($datatype);
        $requireddataholder = [];
        foreach ($dataholders as $dataholder) {
            $requireddataholder[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TEXT);
        }

        return $requireddataholder;
    }
}
