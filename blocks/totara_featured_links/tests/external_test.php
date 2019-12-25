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
 * Tests the methods on the {@link block_totara_featured_links\tile\default_tile} class
 */
class block_totara_featured_links_external_testcase extends test_helper {

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
     * Tests removing
     */
    public function test_remove_tile() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($instance->id);

        // Add visibility for audiences.
        $audience1 = $this->getDataGenerator()->create_cohort();
        $audience2 = $this->getDataGenerator()->create_cohort();
        $data = new \stdClass();
        $data->visibility = block_totara_featured_links\tile\base::VISIBILITY_CUSTOM;
        $data->audience_showing = 1;
        $data->audiences_visible = $audience1->id.','.$audience2->id;
        $tile1->save_visibility($data);

        $this->assertTrue($DB->record_exists('block_totara_featured_links_tiles', ['id' => $tile1->id]));
        $this->assertTrue($DB->record_exists(
            'cohort_visibility',
            ['instanceid' => $tile1->id, 'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS])
        );
        $this->assertTrue(block_totara_featured_links\external::remove_tile($tile1->id));
        $this->assertFalse(block_totara_featured_links\external::remove_tile($tile1->id));
        $this->assertFalse($DB->record_exists('block_totara_featured_links_tiles', ['id' => $tile1->id]));
        $this->assertFalse($DB->record_exists(
            'cohort_visibility',
            ['instanceid' => $tile1->id, 'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS])
        );
    }

    public function test_remove_tile_not_loggedin() {
        global $DB;
        $this->resetAfterTest();

        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($instance->id);
        $this->setUser(null);

        try {
            block_totara_featured_links\external::remove_tile($tile1->id);
            $this->fail('Removing a tile when not logged in should be prohibited');
        } catch (\Exception $e) {
            $this->assertEquals('Course or activity not accessible. (You are not logged in)', $e->getMessage());
        }
        $this->assertTrue($DB->record_exists('block_totara_featured_links_tiles', ['id' => $tile1->id]));
        $this->setGuestUser();
        try {
            block_totara_featured_links\external::remove_tile($tile1->id);
            $this->fail('Removing a tile when being a guest should not be allowed');
        } catch (\Exception $e) {
            $message = 'error/'.get_string('cannot_edit_tile', 'block_totara_featured_links').PHP_EOL.'$a contents: ';
            $this->assertEquals($message, $e->getMessage());
        }
        $this->assertTrue($DB->record_exists('block_totara_featured_links_tiles', ['id' => $tile1->id]));
    }

    public function test_reorder_tiles() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($instance->id);
        $tile2 = $this->blockgenerator->create_gallery_tile($instance->id);
        $tile3 = $this->blockgenerator->create_course_tile($instance->id);
        $tile4 = $this->blockgenerator->create_default_tile($instance->id);

        $this->assertEquals(1, $tile1->sortorder);
        $this->assertEquals(2, $tile2->sortorder);
        $this->assertEquals(3, $tile3->sortorder);
        $this->assertEquals(4, $tile4->sortorder);

        $tile_array = [
            'block-totara-featured-links-tile-'.$tile1->id,
            'block-totara-featured-links-tile-'.$tile4->id,
            'block-totara-featured-links-tile-'.$tile2->id,
            'block-totara-featured-links-tile-'.$tile3->id];
        block_totara_featured_links\external::reorder_tiles($tile_array);
        $tile_array = [
            'block-totara-featured-links-tile-'.$tile1->id,
            'block-totara-featured-links-tile-'.$tile4->id,
            'block-totara-featured-links-tile-'.$tile3->id,
            'block-totara-featured-links-tile-'.$tile2->id];
        block_totara_featured_links\external::reorder_tiles($tile_array);
        $this->refresh_tiles($tile1, $tile2, $tile3, $tile4);

        $this->assertEquals(1, $tile1->sortorder);
        $this->assertEquals(2, $tile4->sortorder);
        $this->assertEquals(3, $tile3->sortorder);
        $this->assertEquals(4, $tile2->sortorder);

        $tile_array = [
            'block-totara-featured-links-tile-'.$tile1->id,
            'block-totara-featured-links-tile-'.$tile4->id,
            'block-totara-featured-links-tile-'.$tile3->id,
            'block-totara-featured-links-tile-'.$tile2->id];
        block_totara_featured_links\external::reorder_tiles($tile_array);
        $this->refresh_tiles($tile1, $tile2, $tile3, $tile4);

        $this->assertEquals(1, $tile1->sortorder);
        $this->assertEquals(3, $tile3->sortorder);
        $this->assertEquals(4, $tile2->sortorder);
        $this->assertEquals(2, $tile4->sortorder);
    }
}
