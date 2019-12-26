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

use \totara_catalog\task\provider_active_task;

/**
 * @group totara_catalog
 */
class totara_catalog_provider_active_task_testcase extends advanced_testcase {

    private $provider_active_task = null;

    protected function setUp() {
        parent::setup();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->provider_active_task = new provider_active_task();
    }

    protected function tearDown() {
        $this->provider_active_task = null;
        parent::tearDown();
    }

    public function test_execute() {
        global $DB;

        // create test courses
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // delete existing catalog records
        $DB->delete_records('catalog');

        // check count after activate course provider
        $this->provider_active_task->set_custom_data(['objecttype' => 'course']);
        $this->provider_active_task->execute();
        $count = $DB->count_records('catalog');
        $this->assertSame(2, $count);
    }
}
