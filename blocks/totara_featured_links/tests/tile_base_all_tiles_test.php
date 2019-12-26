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

use block_totara_featured_links\tile\course_tile;

require_once('test_helper.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_totara_featured_links_tile_base_children_test
 * This tests all the children of the base class to see if there are any general errors in the way that they work.
 */
class block_totara_featured_links_tile_base_all_tiles_testcase extends test_helper {
    /**
     * The block generator instance for the test.
     * @var block_totara_featured_links_generator $generator
     */
    protected $blockgenerator;
    protected $tile_types = [''];

    /**
     * Gets executed before every test case.
     */
    public function setUp() {
        parent::setUp();
        $this->blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
        $this->tile_types = \core_component::get_namespace_classes('tile', 'block_totara_featured_links\tile\base');
    }

    public function tearDown() {
        parent::tearDown();
        $this->blockgenerator = null;
        $this->tile_types = null;
    }

    /**
     * Tests the add_tile() method on all the children of the base class
     * Just makes sure the tile types don't throw exceptions where they shoudln't
     *
     */
    public function test_add() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        foreach ($this->tile_types as $index => $tile_type) {
            $tile_instance = $this->blockgenerator->create_tile($blockinstance->id, $tile_type);
            $this->assertInstanceOf($tile_type, $tile_instance);
            $this->assertEquals($index+1, $tile_instance->sortorder);
        }
    }

    /**
     * Tests the add_tile() method.
     * Where the block id passed to the method is incorrect
     */
    public function test_add_no_id() {
        $this->resetAfterTest();
        $this->blockgenerator->create_instance();
        foreach ($this->tile_types as $tile_type) {
            try {
                $this->blockgenerator->create_tile(-1, $tile_type);
                $this->fail('trying to create a tile with invalid block id should throw an exception');
            } catch (\Exception $e) {
                $this->assertEquals('Coding error detected, it must be fixed by a programmer: The Block instance id was not found', $e->getMessage());
            }
            // Make sure you cant put random values at the constructor.
            try {
                new $tile_type(-1);
                $this->fail('Should not reach this exception');
            } catch (\dml_missing_record_exception $e) {
                $this->assertEquals("Can not find data record in database. (SELECT * FROM {block_totara_featured_links_tiles} WHERE id = ?\n[array (\n  0 => -1,\n)])",
                    $e->getMessage());
            }
        }
    }

    /**
     * Tests the edit_content_form() method on all children of the base class
     * Also makes sure that you can't pass dumb stuff to it
     */
    public function test_get_content_form() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        foreach ($this->tile_types as $tile_type) {
            $tile_instance = $this->blockgenerator->create_tile($blockinstance->id, $tile_type);
            // Refresh the tile_instance object.
            $tile_instance = new $tile_type($tile_instance->id);
            try {
                $tile_instance->get_content_form(['blockinstanceid' => -1, 'tileid' => -1]);
                $this->fail('Getting content form with invalid block id and tile id should throw an exception Type: '.$tile_type);
            } catch (\coding_exception $e) {
                $this->assertEquals('Coding error detected, it must be fixed by a programmer: The block id in parameters did not match the block id for the tile',
                    $e->getMessage());
            }
            $edit_form = $tile_instance->get_content_form(['blockinstanceid' => $blockinstance->id, 'tileid' => $tile_instance->id]);
            $this->assertInstanceOf('\block_totara_featured_links\tile\base_form_content', $edit_form);
        }
    }

    /**
     * Tests the block_totara_featured_links\tile\default_tile::edit_auth_form() method
     * Also makes sure that you can't pass dumb stuff to it
     */
    public function test_get_visibility_form() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        foreach ($this->tile_types as $tile_type) {
            $tile_instance = $this->blockgenerator->create_tile($blockinstance->id, $tile_type);

            if ($tile_type == course_tile::class) {
                $data = new \stdClass();
                $course = $this->getDataGenerator()->create_course();
                $data->course_name_id = $course->id;
                $tile_instance->save_content($data);
                $this->refresh_tiles($tile_instance);
            }

            try {
                $tile_instance->get_visibility_form(['blockinstanceid' => -1, 'tileid' => -1]);
                $this->fail('Getting the visibility form with invalid block id and tile id should throw an exception');
            } catch (\Exception $e) {
                $this->assertEquals('Coding error detected, it must be fixed by a programmer: The block for the tile does not exists', $e->getMessage());
            }
            try {
                $tile_instance->get_visibility_form(['blockinstanceid' => $blockinstance->id, 'tileid' => -1]);
                $this->fail('Getting the visibility form with invalid tile id should throw an exception');
            } catch (\Exception $e) {
                $this->assertEquals('Coding error detected, it must be fixed by a programmer: The tile does not exist', $e->getMessage());
            }
            $edit_form = $tile_instance->get_visibility_form(['blockinstanceid' => $blockinstance->id, 'tileid' => $tile_instance->id]);
            $this->assertInstanceOf('\block_totara_featured_links\tile\base_form_visibility', $edit_form);
        }
    }

    /**
     * Makes sure that all the tiles can be rendered with no values.
     * Also makes sure that the rendered content is wrapped in a div.
     */
    public function test_render_content() {
        global $PAGE, $DB;
        $PAGE->set_url('/');
        $this->resetAfterTest();
        $blockinstance = $this->blockgenerator->create_instance();
        foreach ($this->tile_types as $tile_type) {
            $tile = $this->blockgenerator->create_tile($blockinstance->id, $tile_type);

            if ($tile_type == course_tile::class) {
                $data = new \stdClass();
                $course = $this->getDataGenerator()->create_course();
                $data->course_name_id = $course->id;
                $tile->save_content($data);
                $this->refresh_tiles($tile);
            }
            if ($tile_type == 'block_totara_featured_links\tile\gallery_tile') {
                $subtile = $this->blockgenerator->create_default_tile($blockinstance->id, $tile->id);
                $this->refresh_tiles($tile);
                $this->assertEquals($tile->id, $subtile->parentid);
                $this->assertCount(1, $tile->get_subtiles());
            }

            $content = $tile->render_content_wrapper($PAGE->get_renderer('core'), []);
            $this->assertStringStartsWith('<div', $content);
            $this->assertStringEndsWith('</div>', $content);
        }
    }
}