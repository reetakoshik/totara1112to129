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


use block_totara_featured_links\tile\base;
use block_totara_featured_links\tile\gallery_tile;

require_once('test_helper.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_totara_featured_links_tile_gallery_tile_testcase
 * Test the gallery_tile class
 */
class block_totara_featured_links_tile_gallery_tile_testcase extends test_helper {
    /**
     * The block generator instance for the test.
     * @var block_totara_featured_links_generator $generator
     */
    protected $blockgenerator;

    /**
     * Gets executed before every test case.
     */
    public function setUp() {
        parent::setUp();
        $this->blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
    }

    public function tearDown() {
        parent::tearDown();
        $this->blockgenerator = null;
    }

    /**
     * Makes sure that the saving works properly cause this is different from the default tile.
     */
    public function test_tile_custom_save() {
        global $DB;
        $this->resetAfterTest();
        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_gallery_tile($instance->id);
        $data = new \stdClass();
        $data->type = 'block_totara_featured_links-gallery_tile';
        $data->sortorder = 4;
        $data->interval = 10;

        $tile1->save_content($data);
        $tile_real = new block_totara_featured_links\tile\gallery_tile($DB->get_record('block_totara_featured_links_tiles', ['id' => $tile1->id]));
        $this->assertSame(10,  $this->get_protected_property($tile_real, 'data')->interval);
    }

    /**
     * Makes sure all the sub tiles are retrieved from the database correctly.
     */
    public function test_get_subtiles() {
        global $DB;
        $this->resetAfterTest();

        $instance = $this->blockgenerator->create_instance();
        $gallerytile = $this->blockgenerator->create_gallery_tile($instance->id);

        $statictile1 = $this->blockgenerator->create_default_tile($instance->id);
        $statictile2 = $this->blockgenerator->create_default_tile($instance->id);
        $statictile3 = $this->blockgenerator->create_default_tile($instance->id);

        $statictile1->parentid = $gallerytile->id;
        $statictile2->parentid = $gallerytile->id;
        $statictile3->parentid = $gallerytile->id;

        $DB->update_record('block_totara_featured_links_tiles', $statictile1);
        $DB->update_record('block_totara_featured_links_tiles', $statictile2);
        $DB->update_record('block_totara_featured_links_tiles', $statictile3);

        $gallerytile = base::get_tile_instance($gallerytile->id);

        $subtiles = $gallerytile->get_subtiles();
        $this->assertEquals(3, count($subtiles));
        $expected_subtiles = [$statictile1->id, $statictile2->id, $statictile3->id];

        foreach ($subtiles as $subtile) {
            $this->assertTrue(in_array($subtile->id, $expected_subtiles));
            unset($expected_subtiles[array_search($subtile->id, $expected_subtiles)]);
        }
        $this->assertEquals(0, count($expected_subtiles));
    }

    /**
     * When a gallery tile is empty is should only be shown in edit mode.
     */
    public function test_render_hide_when_no_subtiles() {
        global $DB, $PAGE;
        $PAGE->set_url('/');
        $this->resetAfterTest();
        $this->setAdminUser();

        $instance = $this->blockgenerator->create_instance();
        $gallerytile = $this->blockgenerator->create_gallery_tile($instance->id);

        $emptycontent = $gallerytile->render_content_wrapper($PAGE->get_renderer('core'),
            ['editing' => false]);
        $this->assertEquals('', $emptycontent);
        $nonemptycontent = $gallerytile->render_content_wrapper($PAGE->get_renderer('core'),
            ['editing' => true]);
        $this->assertNotEquals('', $nonemptycontent);

        $subtile = $this->blockgenerator->create_default_tile($instance->id);
        $subtile->parentid = $gallerytile->id;
        $DB->update_record('block_totara_featured_links_tiles', $subtile);

        $this->refresh_tiles($gallerytile);

        $nonemptycontent = $gallerytile->render_content_wrapper($PAGE->get_renderer('core'),
            ['editing' => false]);
        $this->assertNotEquals('', $nonemptycontent);
        $nonemptycontent = $gallerytile->render_content_wrapper($PAGE->get_renderer('core'),
            ['editing' => true]);
        $this->assertNotEquals('', $nonemptycontent);
    }
}