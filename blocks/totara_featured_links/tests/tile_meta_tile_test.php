<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

use block_totara_featured_links\tile\base;
use block_totara_featured_links\tile\gallery_tile;

require_once('test_helper.php');

/**
 * Tests for meta tiles as they have subtiles
 */
class block_totara_featured_links_tile_meta_tile_testcase extends test_helper {

    /**
     * creates a block with 2 parent tiles and a number of subtiles.
     */
    private function setup_block_with_subtiles() {
        $data = new class() {
            /** @var stdClass */
            public $block;
            /** @var gallery_tile */
            public $gallerytile;
            /** @var gallery_tile */
            public $emptygallerytile;
            /** @var base */
            public $subtile1, $subtile2, $subtile3;
        };
        /** @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
        $data->block = $blockgenerator->create_instance();

        $data->gallerytile = $blockgenerator->create_gallery_tile($data->block->id);
        $data->emptygallerytile = $blockgenerator->create_gallery_tile($data->block->id);

        $data->subtile1 = $blockgenerator->create_default_tile($data->block->id, $data->gallerytile->id);
        $data->subtile2 = $blockgenerator->create_default_tile($data->block->id, $data->gallerytile->id);
        $data->subtile3 = $blockgenerator->create_default_tile($data->block->id, $data->gallerytile->id);

        return $data;
    }


    /**
     * Test that the ordering of subtitles is independent from parent tiles and tiles that have a different parent.
     */
    public function test_sortorder_with_subtiles() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->setup_block_with_subtiles();
        /** @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');

        $this->assertEquals(1, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->gallerytile->id]));
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->emptygallerytile->id]));

        $this->assertEquals(1, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->subtile1->id]));
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->subtile2->id]));
        $this->assertEquals(3, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->subtile3->id]));

        $newtile1 = $blockgenerator->create_program_tile($data->block->id, $data->emptygallerytile->id);
        $newtile2 = $blockgenerator->create_certification_tile($data->block->id, $data->emptygallerytile->id);

        $newparenttile = $blockgenerator->create_course_tile($data->block->id);

        // Check that the new tiles are in the right orders.
        $this->assertEquals(3, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $newparenttile->id]));
        $this->assertEquals(1, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $newtile1->id]));
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $newtile2->id]));

        // Check that the existing tiles were not changed.
        $this->assertEquals(1, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->gallerytile->id]));
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->emptygallerytile->id]));

        $this->assertEquals(1, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->subtile1->id]));
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->subtile2->id]));
        $this->assertEquals(3, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $data->subtile3->id]));
    }

    /**
     * Test that cloning a tile will clone all the subtiles.
     */
    public function test_clone() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $data = $this->setup_block_with_subtiles();
        /** @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');

        $newblock = $blockgenerator->create_instance();

        $newblockinstance = block_instance('totara_featured_links', $newblock);
        $newblockinstance->instance_copy($data->block->id);

        $newparenttile = $DB->get_record('block_totara_featured_links_tiles', [
            'blockid' => $newblock->id,
            'parentid' => 0,
            'sortorder' => 1
        ]);
        $this->assertNotFalse($newparenttile);

        $newsubtiles = $DB->get_records('block_totara_featured_links_tiles', [
            'blockid' => $newblock->id,
            'parentid' => $newparenttile->id
        ]);
        $this->assertCount(3, $newsubtiles);
    }

    /**
     * Makes sure that the parentid links are maintained when the block is cloned.
     */
    public function test_clone_parentid_links_are_maintained() {
        global $DB;
        $this->resetAfterTest();
        /** @var block_totara_featured_links_generator $featuredlinksgenerator */
        $featuredlinksgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');

        $block1data = $featuredlinksgenerator->create_instance();

        $gallerytile1 = $featuredlinksgenerator->create_gallery_tile($block1data->id);
        $gallerytile2 = $featuredlinksgenerator->create_gallery_tile($block1data->id, $gallerytile1->id);
        $gallerytile3 = $featuredlinksgenerator->create_gallery_tile($block1data->id, $gallerytile2->id);

        $block1instance = block_instance('totara_featured_links', $block1data);
        $block1instance->instance_copy($block1data->id);

        $gallerytile1clonedata = $DB->get_record_sql(
            'SELECT * 
             FROM {block_totara_featured_links_tiles}
             WHERE parentid = 0 AND id != :block1id',
            ['block1id' => $gallerytile1->id]
        );

        $this->assertNotFalse($gallerytile1clonedata);

        $gallerytile2clonedata = $DB->get_record('block_totara_featured_links_tiles', ['parentid' => $gallerytile1clonedata->id]);
        $this->assertNotFalse($gallerytile2clonedata);
        $this->assertNotFalse($DB->get_record('block_totara_featured_links_tiles', ['parentid' => $gallerytile2clonedata->id]));
    }


}