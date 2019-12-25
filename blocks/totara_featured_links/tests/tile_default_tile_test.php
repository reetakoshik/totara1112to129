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
 * Tests the methods on the block_totara_featured_links\tile\default_tile class
 */
class block_totara_featured_links_tile_default_tile_testcase extends test_helper {

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
     * Tests the logic for the accessibility text
     */
    public function test_get_accessibility_text() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $access_text = $tile1->get_accessibility_text();
        $this->assertTrue(isset($access_text['sr-only']));

        $data = $this->get_protected_property($tile1, 'data');
        $data->textbody = 'textbody';
        $this->set_protected_property($tile1, 'data', $data);
        $this->assertEquals('textbody', $tile1->get_accessibility_text()['sr-only']);
        $data->heading = 'heading';
        $this->set_protected_property($tile1, 'data', $data);
        $this->assertEquals('heading', $tile1->get_accessibility_text()['sr-only']);
        $data->alt_text = 'alt_text';
        $this->set_protected_property($tile1, 'data', $data);
        $this->assertEquals('alt_text', $tile1->get_accessibility_text()['sr-only']);
    }

    /**
     * Tests the custom save function for the default tile
     */
    public function test_tile_custom_save() {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($instance->id);
        $data = new \stdClass();
        $data->type = 'block_totara_featured_links-default_tile';
        $data->sortorder = 4;
        $data->url = 'www.example.com';
        $data->heading = 'some heading';
        $data->textbody = 'some textbody';
        $data->background_color = '#123abc';
        $data->alt_text = 'This is some alternative text';

        $tile1->save_content($data);
        $tile_real = new block_totara_featured_links\tile\default_tile($DB->get_record('block_totara_featured_links_tiles', ['id' => $tile1->id]));

        $this->assertSame('some heading', $this->get_protected_property($tile_real, 'data')->heading);
        $this->assertSame('some textbody', $this->get_protected_property($tile_real, 'data')->textbody);
        $this->assertSame('#123abc', $this->get_protected_property($tile_real, 'data')->background_color);
        $this->assertSame('This is some alternative text', $this->get_protected_property($tile_real, 'data')->alt_text);
        // Checks urls with out wwwroot, /, http:// or https:// get http:// appended to them.
        $this->assertSame('https://www.example.com', $this->get_protected_property($tile_real, 'data')->url);
        // Check wwwroot identification.
        $data->url = $CFG->wwwroot . '/';
        $tile1->save_content($data);
        $this->refresh_tiles($tile1);
        $this->assertSame('/', $this->get_protected_property($tile1, 'data')->url);
        // Check urls that are to be left alone are.
        $data->url = '/www.example.com';
        $tile1->save_content($data);
        $this->refresh_tiles($tile1);
        $this->assertSame('/www.example.com', $this->get_protected_property($tile1, 'data')->url);
        // Makes sure urls with https:// do not get http:// appended to them.
        $data->url = 'https://www.example.com';
        $tile1->save_content($data);
        $this->refresh_tiles($tile1);
        $this->assertSame('https://www.example.com', $this->get_protected_property($tile1, 'data')->url);
        // Tests using part of the wwwroot.
        $data->url = preg_replace('/^(https:\/\/)|(http:\/\/)/', '', $CFG->wwwroot) . '/index.php';
        $tile1->save_content($data);
        $this->refresh_tiles($tile1);
        $this->assertSame('/index.php', $this->get_protected_property($tile1, 'data')->url);
        // Tests just the wwwroot.
        $data->url = $CFG->wwwroot;
        $tile1->save_content($data);
        $this->refresh_tiles($tile1);
        $this->assertSame('/', $this->get_protected_property($tile1, 'data')->url);
    }

    /**
     * renders the default tile with a background
     */
    public function test_tile_render_background() {
        global $CFG , $PAGE;
        $PAGE->set_url('/');
        $this->resetAfterTest();
        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($instance->id);
        $context = \context_block::instance($instance->id);

        $file_url = $CFG->dirroot.'/blocks/totara_featured_links/tests/fixtures/test.png';
        $fs = get_file_storage();
        $file_record = ['contextid' => $context->__get('id'),
            'component' => 'block_totara_featured_links',
            'filearea' => 'tile_background',
            'itemid' => 123456,
            'filepath' => '/',
            'filename' => 'background.png',
            'timecreated' => time(),
            'timemodified' => time()];
        $fs->create_file_from_pathname($file_record, $file_url);

        $files = $fs->get_area_files($context->__get('id'),
            'block_totara_featured_links',
            'tile_background',
            123456,
            '',
            false);

        $file = reset($files);
        $data = $this->get_protected_property($tile1, 'data');
        $data->background_img = $file->get_filename();
        $this->set_protected_property($tile1, 'data', $data);

        $this->call_protected_method($tile1, 'encode_data');
        $this->call_protected_method($tile1, 'decode_data');

        $content = $tile1->render_content_wrapper($PAGE->get_renderer('core'), []);
        $this->assertContains($tile1->id, $content);
    }

    /**
     * Makes sure that the top class is added to the content when its selected.
     */
    public function test_render_content() {
        global $PAGE;
        $PAGE->set_url('/');
        $this->setAdminUser();
        $this->resetAfterTest();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $data = new \stdClass();
        $data->type = 'block_totara_featured_links-default_tile';
        $data->sortorder = 1;
        $data->url = 'www.example.com';
        $data->heading = 'aih';
        $data->textbody = 'jdbgls';
        $data->heading_location = 'top';
        $tile1->save_content($data);

        $this->refresh_tiles($tile1);

        $content = $tile1->render_content_wrapper($PAGE->get_renderer('core'), []);
        $this->assertStringStartsWith('<div', $content);
        $this->assertStringEndsWith('</div>', $content);
        $this->assertContains('block-totara-featured-links-content-top', $content);
    }
}
