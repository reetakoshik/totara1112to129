<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

require_once('test_helper.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the methods on the \block_totara_featured_links\tile\default_tile class
 */
class block_totara_featured_links_form_element_audience_list_testcase extends test_helper {
    /**
     * The block generator instance for the test.
     * @var block_totara_featured_links_generator $generator
     */
    protected $blockgenerator;

    public function setUp() {
        parent::setUp();
        $this->blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
    }

    /**
     * Test that the form element gets the right audience data from the data base
     */
    public function test_get_audience_data() {
        $this->resetAfterTest();
        $audience1 = $this->getDataGenerator()->create_cohort();
        $instance = $this->blockgenerator->create_instance();
        $this->blockgenerator->create_default_tile($instance->id);

        $cohort_data = \block_totara_featured_links\form\element\audience_list::get_audience_data($audience1->id);

        $this->assertTrue(isset($cohort_data['name']));
        $this->assertTrue(isset($cohort_data['learners']));
        $this->assertEquals(0, $cohort_data['learners']);
    }
}