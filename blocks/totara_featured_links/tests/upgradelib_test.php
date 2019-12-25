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

defined('MOODLE_INTERNAL') || die();

use block_totara_featured_links\tile\base;
use block_totara_featured_links\tile\gallery_tile;
use block_totara_featured_links\tile\program_tile;
use block_totara_featured_links\tile\certification_tile;

/**
 * Tests the upgrade steps for the featured links block.
 */
class block_totara_featured_links_upgradelib_testcase extends advanced_testcase {

    private function get_setup_data() {
        $data = new class() {
            /** @var gallery_tile $gallerytilewithoutlocation */
            public $gallerytilewithoutlocation;
            /** @var gallery_tile $gallerytilewithlocation */
            public $gallerytilewithlocation;
            /** @var program_tile $programwithoutlocation */
            public $programwithoutlocation;
            /** @var program_tile $programtilewithlocation */
            public $programtilewithlocation;
            /** @var certification_tile $certificationwithoutlocation */
            public $certificationwithoutlocation;
            /** @var certification_tile $certificationwithlocation */
            public $certificationwithlocation;
        };

        /** @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');

        $block = $blockgenerator->create_instance();

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

        $data->gallerytilewithlocation = $blockgenerator->create_gallery_tile($block->id);
        $data->gallerytilewithlocation->data->heading_location = 'bottom';
        $data->gallerytilewithlocation->data->url = '/';
        $data->gallerytilewithlocation->save_content($data->gallerytilewithlocation->data);

        $data->gallerytilewithoutlocation = $blockgenerator->create_gallery_tile($block->id);
        $data->gallerytilewithoutlocation->data->url = '/';
        $data->gallerytilewithoutlocation->save_content($data->gallerytilewithoutlocation->data);

        return $data;
    }

    /**
     * Test that the default heading_location is added correctly when it is not set
     * and that it doesnt effect the other heading locations
     */
    public function test_setting_default_heading_location_on_gallery_program_certification_tile() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->get_setup_data();

        require_once($CFG->dirroot.'/blocks/totara_featured_links/db/upgradelib.php');

        $this->assertFalse(isset($data->programtilewithoutlocation->data->heading_location));
        $this->assertEquals('bottom', $data->programtilewithlocation->data->heading_location);
        $this->assertFalse(isset($data->certificationtilewithoutlocation->data->heading_location));
        $this->assertEquals('bottom', $data->certificationtilewithlocation->data->heading_location);
        $this->assertFalse(isset($data->gallerytilewithoutlocation->data->heading_location));
        $this->assertEquals('bottom', $data->gallerytilewithlocation->data->heading_location);

        btfl_upgrade_set_default_heading_location();

        $data->programtilewithoutlocation = base::get_tile_instance($data->programtilewithoutlocation->id);
        $data->programtilewithlocation = base::get_tile_instance($data->programtilewithlocation->id);
        $data->certificationtilewithoutlocation = base::get_tile_instance($data->certificationtilewithoutlocation->id);
        $data->certificationtilewithlocation = base::get_tile_instance($data->certificationtilewithlocation->id);
        $data->gallerytilewithoutlocation = base::get_tile_instance($data->gallerytilewithoutlocation->id);
        $data->gallerytilewithlocation = base::get_tile_instance($data->gallerytilewithlocation->id);

        $this->assertEquals(base::HEADING_TOP, $data->programtilewithoutlocation->data->heading_location);
        $this->assertEquals('bottom', $data->programtilewithlocation->data->heading_location);
        $this->assertEquals(base::HEADING_TOP, $data->certificationtilewithoutlocation->data->heading_location);
        $this->assertEquals('bottom', $data->certificationtilewithlocation->data->heading_location);
        $this->assertEquals(base::HEADING_TOP, $data->gallerytilewithoutlocation->data->heading_location);
        $this->assertEquals('bottom', $data->gallerytilewithlocation->data->heading_location);
    }

}
