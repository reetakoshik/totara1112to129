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
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course;

defined('MOODLE_INTERNAL') || die();

use core\task\manager as task_manager;
use totara_catalog\local\config;
use totara_catalog\provider;
use totara_catalog\dataformatter\formatter;
use core_course\totara_catalog\course as course_provider;

/**
 * @group totara_catalog
 */
class core_course_totara_catalog_course_provider_testcase extends \advanced_testcase {

    /**
     * @var course_provider
     */
    private $provider = null;

    /**
     * @var \stdClass
     */
    private $course = null;

    protected function setUp() {
        parent::setup();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->provider = new course_provider();
        $this->course = $this->getDataGenerator()->create_course();
    }

    protected function tearDown() {
        $this->provider = null;
        $this->course = null;
        parent::tearDown();
    }

    public function test_get_name() {
        $this->assertSame(get_string('courses', 'moodle'), $this->provider->get_name());
    }

    public function test_get_object_type() {
        $this->assertSame('course', $this->provider->get_object_type());
    }

    public function test_get_manage_link() {
        $link = $this->provider->get_manage_link($this->course->id);
        $this->assertSame(get_string('courselink', 'lti'), $link->label);
        $this->assertSame(course_get_url($this->course->id)->out(), $link->url);
    }

    public function test_get_details_link() {
        $link = $this->provider->get_details_link($this->course->id);
        $this->assertArrayHasKey('description', (array)$link);
    }

    public function test_get_object_table() {
        $this->assertSame('{course}', $this->provider->get_object_table());
    }

    public function test_get_objectid_field() {
        $this->assertSame('id', $this->provider->get_objectid_field());
    }

    public function test_get_data_holder_config() {

        $this->assertNotEmpty($this->provider->get_data_holder_config('sort'));
        $this->assertNotEmpty($this->provider->get_data_holder_config('fts'));
        $this->assertNotEmpty($this->provider->get_data_holder_config('image'));
        $this->assertNotEmpty($this->provider->get_data_holder_config('progressbar'));

        $ftsconfig = $this->provider->get_data_holder_config('fts');
        $this->assertArrayHasKey('high', $ftsconfig);
        $this->assertArrayHasKey('medium', $ftsconfig);
        $this->assertArrayHasKey('low', $ftsconfig);
    }

    public function test_get_config() {
        $config = config::instance();
        $config->update(
            [
                'rich_text'               => ['course' => 'test_placeholder'],
                'details_additional_text' => ['course' => []],
            ]
        );
        $this->assertSame('test_placeholder', $this->provider->get_config('rich_text'));
        $this->assertEmpty($this->provider->get_config('details_additional_text'));
    }

    public function test_can_see() {
        $result = $this->provider->can_see([(object)['objectid' => $this->course->id]]);
        $this->assertTrue($result[$this->course->id]);
    }

    public function test_get_all_objects_sql() {
        $result = $this->provider->get_all_objects_sql();
        $this->assertNotEmpty($result[0]);
        $this->assertArrayHasKey('sitecourseid', $result[1]);
        $this->assertArrayHasKey('coursecontextlevel', $result[1]);
    }

    public function test_is_plugin_enabled() {
        $this->assertTrue($this->provider->is_plugin_enabled());
    }

    public function test_get_buttons() {
        set_config('enablecourserequests', 1);
        $this->assertIsArray($this->provider->get_buttons());
    }

    public function test_change_status() {
        global $DB;

        $DB->delete_records('task_adhoc');

        // check inactive status
        course_provider::change_status(provider::PROVIDER_STATUS_INACTIVE);
        $count = $DB->count_records('catalog', ['objecttype' => course_provider::get_object_type()]);
        $this->assertEmpty($count);

        // check active status
        course_provider::change_status(provider::PROVIDER_STATUS_ACTIVE);
        $this->assertEquals(1, $DB->count_records('task_adhoc'));
        $task = task_manager::get_next_adhoc_task(time());
        $task->execute();
        task_manager::adhoc_task_complete($task);

        $count = $DB->count_records('catalog', ['objecttype' => course_provider::get_object_type()]);
        $this->assertSame(1, $count);
    }

    public function test_get_filters() {
        foreach ($this->provider->get_filters() as $filter) {
            $this->assertInstanceOf('totara_catalog\\filter', $filter);
        }
    }

    public function test_get_features() {
        foreach ($this->provider->get_features() as $features) {
            $this->assertInstanceOf('totara_catalog\\feature', $features);
        }
    }

    public function test_get_dataholders() {
        // check fts data holders
        foreach ($this->provider->get_dataholders(formatter::TYPE_FTS) as $dataholder) {
            $this->assertInstanceOf('totara_catalog\\dataholder', $dataholder);
        }

        // check title data holders
        foreach ($this->provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE) as $dataholder) {
            $this->assertInstanceOf('totara_catalog\\dataholder', $dataholder);
        }
    }
}
