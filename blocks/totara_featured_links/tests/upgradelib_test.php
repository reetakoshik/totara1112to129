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
use block_totara_featured_links\tile\default_tile;
use block_totara_featured_links\tile\gallery_tile;

defined('MOODLE_INTERNAL') || die();


/**
 * Class block_totara_featured_links_upgradelib_testcase
 * Tests the upgrade steps for the featured links block
 */
class block_totara_featured_links_upgradelib_testcase extends advanced_testcase {

    private function get_setup_data() {
        global $DB;
        $data = new class() {
            /** @var \block_totara_featured_links\tile\gallery_tile $oldgallerytile */
            public $oldgallerytile;
            /** @var block_totara_featured_links $block */
            public $block;
        };

        /** @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
        $data->block = $blockgenerator->create_instance();

        // First old gallery tile.
        $data->oldgallerytile = $blockgenerator->create_gallery_tile($data->block->id);

        $fs = get_file_storage();
        $file_record = [
            'contextid' => context_block::instance($data->block->id)->id,
            'component' => 'block_totara_featured_links',
            'filearea' => 'tile_backgrounds',
            'itemid' => $data->oldgallerytile->id,
            'filepath' => '/',
            'filename' => 'image1.png'
        ];
        $fs->create_file_from_string($file_record, 'test file');

        $file_record = [
            'contextid' => context_block::instance($data->block->id)->id,
            'component' => 'block_totara_featured_links',
            'filearea' => 'tile_backgrounds',
            'itemid' => $data->oldgallerytile->id,
            'filepath' => '/',
            'filename' => 'image2.png'
        ];
        $fs->create_file_from_string($file_record, 'test file');

        $databaserow = $DB->get_record('block_totara_featured_links_tiles', ['id' => $data->oldgallerytile->id]);
        $tiledata = json_decode($databaserow->dataraw);
        $tiledata->url = '/';
        $tiledata->heading = 'heading';
        $tiledata->textbody = 'text';
        $tiledata->background_imgs = ['image1.png', 'image2.png'];
        $databaserow->dataraw = json_encode($tiledata);
        $DB->update_record('block_totara_featured_links_tiles', $databaserow);

        $datastring = $DB->get_field('block_totara_featured_links_tiles', 'dataraw', ['id' => $data->oldgallerytile->id]);
        $this->assertContains('heading', $datastring);
        $this->assertContains('text', $datastring);
        $this->assertContains('/', $datastring);

        // Second old gallery tile.
        $data->oldgallerytile2 = $blockgenerator->create_gallery_tile($data->block->id);

        $fs = get_file_storage();
        $file_record = [
            'contextid' => context_block::instance($data->block->id)->id,
            'component' => 'block_totara_featured_links',
            'filearea' => 'tile_backgrounds',
            'itemid' => $data->oldgallerytile2->id,
            'filepath' => '/',
            'filename' => 'image3.png'
        ];
        $fs->create_file_from_string($file_record, 'test file');

        $file_record = [
            'contextid' => context_block::instance($data->block->id)->id,
            'component' => 'block_totara_featured_links',
            'filearea' => 'tile_backgrounds',
            'itemid' => $data->oldgallerytile2->id,
            'filepath' => '/',
            'filename' => 'image4.png'
        ];
        $fs->create_file_from_string($file_record, 'test file');

        $databaserow = $DB->get_record('block_totara_featured_links_tiles', ['id' => $data->oldgallerytile2->id]);
        $tiledata = json_decode($databaserow->dataraw);
        $tiledata->url = '/';
        $tiledata->heading = 'heading';
        $tiledata->textbody = 'text';
        $tiledata->background_imgs = ['image3.png', 'image4.png'];
        $databaserow->dataraw = json_encode($tiledata);
        $DB->update_record('block_totara_featured_links_tiles', $databaserow);

        $datastring = $DB->get_field('block_totara_featured_links_tiles', 'dataraw', ['id' => $data->oldgallerytile2->id]);
        $this->assertContains('heading', $datastring);
        $this->assertContains('text', $datastring);
        $this->assertContains('/', $datastring);

        return $data;
    }

    /**
     * Tests the new type of gallery tiles are generated properly
     */
    public function test_upgrading_a_gallery_tile_makes_correct_static_tiles() {
        global $DB, $CFG;
        $this->resetAfterTest();
        $data = $this->get_setup_data();

        require_once($CFG->dirroot.'/blocks/totara_featured_links/db/upgradelib.php');

        split_gallery_tiles_into_subtiles();
        $fs = get_file_storage();

        // Test the first gallery was reestablish correctly.
        $newtiles = $DB->get_records('block_totara_featured_links_tiles', ['parentid' => $data->oldgallerytile->id]);
        $expectedimages = ['image1.png', 'image2.png'];
        foreach ($newtiles as $newtile) {
            /** @var default_tile $newtileinstance */
            $newtileinstance = base::get_tile_instance($newtile);
            $this->assertInstanceOf(default_tile::class, $newtileinstance);
            $this->assertEquals('heading', $newtileinstance->data->heading);
            $this->assertEquals('text', $newtileinstance->data->textbody);

            $key = array_search($newtileinstance->data->background_img, $expectedimages);
            if ($key !== false) {
                unset($expectedimages[$key]);
            } else {
                $this->fail('The image was not an expected value');
            }

            $this->assertNotFalse($fs->get_file(
                context_block::instance($data->block->id)->id,
                'block_totara_featured_links',
                'tile_background',
                $newtileinstance->id,
                '/',
                $newtileinstance->data->background_img
            ));
        }
        $this->assertEmpty($expectedimages);

        // Test the second gallery was reestablish correctly.
        $newtiles = $DB->get_records('block_totara_featured_links_tiles', ['parentid' => $data->oldgallerytile2->id]);
        $expectedimages = ['image3.png', 'image4.png'];
        foreach ($newtiles as $newtile) {
            /** @var default_tile $newtileinstance */
            $newtileinstance = base::get_tile_instance($newtile);
            $this->assertInstanceOf(default_tile::class, $newtileinstance);
            $this->assertEquals('heading', $newtileinstance->data->heading);
            $this->assertEquals('text', $newtileinstance->data->textbody);

            $key = array_search($newtileinstance->data->background_img, $expectedimages);
            if ($key !== false) {
                unset($expectedimages[$key]);
            } else {
                $this->fail('The image was not an expected value');
            }

            $this->assertNotFalse($fs->get_file(
                context_block::instance($data->block->id)->id,
                'block_totara_featured_links',
                'tile_background',
                $newtileinstance->id,
                '/',
                $newtileinstance->data->background_img
            ));
        }
        $this->assertEmpty($expectedimages);
    }


    private function get_data_with_empty_heading_location() {
        $data = $this->get_setup_data();

        /** @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');

        $block = $data->block;

        // Creating program with default location.
        $data->programtilewithlocation = $blockgenerator->create_program_tile($block->id);
        $data->programtilewithlocation->data->url = '/';
        $data->programtilewithlocation->data->heading_location = 'bottom';
        $data->programtilewithlocation->save_content($data->programtilewithlocation->data);

        // Creating program without default location.
        $data->programtilewithoutlocation = $blockgenerator->create_program_tile($block->id);
        $data->programtilewithoutlocation->data->url = '/';
        $data->programtilewithoutlocation->save_content($data->programtilewithoutlocation->data);

        // Creating certification with default location.
        $data->certificationtilewithlocation = $blockgenerator->create_certification_tile($block->id);
        $data->certificationtilewithlocation->data->url = '/';
        $data->certificationtilewithlocation->data->heading_location = 'bottom';
        $data->certificationtilewithlocation->save_content($data->certificationtilewithlocation->data);

        // Creating certification without default location.
        $data->certificationtilewithoutlocation = $blockgenerator->create_certification_tile($block->id);
        $data->certificationtilewithoutlocation->data->url = '/';
        $data->certificationtilewithoutlocation->save_content($data->certificationtilewithoutlocation->data);

        // Lets trigger upgrade step to convert old gallery data to the new structure.
        split_gallery_tiles_into_subtiles();

        return $data;
    }

    /**
     * Test that the default heading_location is added correctly when it is not set
     * and that it doesnt effect the other heading locations
     */
    public function test_setting_default_heading_location_on_gallery_program_and_certification_tile() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/totara_featured_links/db/upgradelib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->get_data_with_empty_heading_location();

        // Test data before the upgrade of heading location.
        $this->assertFalse(isset($data->programtilewithoutlocation->data->heading_location));
        $this->assertEquals('bottom', $data->programtilewithlocation->data->heading_location);

        $this->assertFalse(isset($data->certificationtilewithoutlocation->data->heading_location));
        $this->assertEquals('bottom', $data->certificationtilewithlocation->data->heading_location);

        $newtiles = $DB->get_records('block_totara_featured_links_tiles', ['parentid' => $data->oldgallerytile->id]);
        foreach ($newtiles as $newtile) {
            /** @var default_tile $newtileinstance */
            $newtileinstance = base::get_tile_instance($newtile);
            $this->assertFalse(isset($newtileinstance->data->heading_location));
        }

        btfl_upgrade_set_default_heading_location();

        $data->programtilewithlocation = base::get_tile_instance($data->programtilewithlocation->id);
        $data->programtilewithoutlocation = base::get_tile_instance($data->programtilewithoutlocation->id);
        $data->certificationtilewithlocation = base::get_tile_instance($data->certificationtilewithlocation->id);
        $data->certificationtilewithoutlocation = base::get_tile_instance($data->certificationtilewithoutlocation->id);

        $this->assertEquals(base::HEADING_TOP, $data->programtilewithoutlocation->data->heading_location);
        $this->assertEquals('bottom', $data->programtilewithlocation->data->heading_location);
        $this->assertEquals(base::HEADING_TOP, $data->certificationtilewithoutlocation->data->heading_location);
        $this->assertEquals('bottom', $data->certificationtilewithlocation->data->heading_location);

        $newtiles = $DB->get_records('block_totara_featured_links_tiles', ['parentid' => $data->oldgallerytile->id]);
        foreach ($newtiles as $newtile) {
            /** @var default_tile $newtileinstance */
            $newtileinstance = base::get_tile_instance($newtile);
            $this->assertEquals(base::HEADING_TOP, $newtileinstance->data->heading_location);
        }
    }
}
