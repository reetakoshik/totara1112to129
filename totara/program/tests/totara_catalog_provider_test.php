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
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program;

use core\task\manager as task_manager;
use totara_catalog\local\config;
use totara_catalog\provider;
use totara_catalog\dataformatter\formatter;
use totara_program\totara_catalog\program as program_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_program_totara_catalog_provider_testcase extends \advanced_testcase {

    /**
     * @var program_provider
     */
    private $provider = null;

    /**
     * @var \program
     */
    private $program = null;

    protected function setUp() {
        parent::setup();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->provider = new program_provider();
        /** @var \totara_program_generator $program_generator */
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->program = $program_generator->create_program();
    }

    protected function tearDown() {
        $this->provider = null;
        $this->program = null;
        parent::tearDown();
    }

    public function test_get_name() {
        $this->assertSame(get_string('programs', 'totara_program'), $this->provider->get_name());
    }

    public function test_get_object_type() {
        $this->assertSame('program', $this->provider->get_object_type());
    }

    public function test_get_manage_link() {
        $link = $this->provider->get_manage_link($this->program->id);

        $url = new \moodle_url('/totara/program/edit.php', ['id' => $this->program->id]);
        $this->assertSame(get_string('editprogramdetails', 'totara_program'), $link->label);
        $this->assertSame($url->out(), $link->url);
    }

    public function test_get_details_link() {
        $link = $this->provider->get_details_link($this->program->id);
        $this->assertArrayHasKey('description', (array)$link);
    }

    public function test_get_object_table() {
        $this->assertSame('{prog}', $this->provider->get_object_table());
    }

    public function test_get_objectid_field() {
        $this->assertSame('id', $this->provider->get_objectid_field());
    }

    public function test_get_data_holder_config() {

        $this->assertNotEmpty($this->provider->get_data_holder_config('sort'));
        $this->assertNotEmpty($this->provider->get_data_holder_config('fts'));
        $this->assertNotEmpty($this->provider->get_data_holder_config('image'));

        $ftsconfig = $this->provider->get_data_holder_config('fts');
        $this->assertArrayHasKey('high', $ftsconfig);
        $this->assertArrayHasKey('medium', $ftsconfig);
        $this->assertArrayHasKey('low', $ftsconfig);
    }

    public function test_get_config() {
        $config = config::instance();
        $config->update(
            [
                'rich_text'               => ['program' => 'test_placeholder'],
                'details_additional_text' => ['program' => []],
            ]
        );
        $this->assertSame('test_placeholder', $this->provider->get_config('rich_text'));
        $this->assertEmpty($this->provider->get_config('details_additional_text'));
    }

    public function test_can_see() {
        $result = $this->provider->can_see([(object)['objectid' => $this->program->id]]);
        $this->assertTrue($result[$this->program->id]);
    }

    public function test_get_all_objects_sql() {
        $result = $this->provider->get_all_objects_sql();
        $this->assertNotEmpty($result[0]);
        $this->assertArrayHasKey('programcontextlevel', $result[1]);
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
        program_provider::change_status(provider::PROVIDER_STATUS_INACTIVE);
        $count = $DB->count_records('catalog', ['objecttype' => program_provider::get_object_type()]);
        $this->assertEmpty($count);

        // check active status
        program_provider::change_status(provider::PROVIDER_STATUS_ACTIVE);
        $this->assertEquals(1, $DB->count_records('task_adhoc'));
        $task = task_manager::get_next_adhoc_task(time());
        $task->execute();
        task_manager::adhoc_task_complete($task);

        $count = $DB->count_records('catalog', ['objecttype' => program_provider::get_object_type()]);
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
