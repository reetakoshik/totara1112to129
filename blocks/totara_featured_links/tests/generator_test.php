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
 * Tests the generator for the totara_featured_links block.
 */
class block_totara_featured_links_generator_testcase extends test_helper {

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

    /**
     * Tests the \block_totara_featured_links\tile\base::get_tile_instance() method.
     *
     * Here we want to test that the generator returns an accurate tile object that matches the real one.
     */
    public function test_create_default_tile() {
        global $DB;
        $this->resetAfterTest(); // Changing the database, we must reset.
        $blockinstance = $this->blockgenerator->create_instance();
        $tile = $this->blockgenerator->create_default_tile($blockinstance->id);

        // Test the basic assumptions, return value, it has been inserted and it exists.
        $this->assertInstanceOf('\block_totara_featured_links\tile\default_tile', $tile); // Check it is a default tile record.
        $this->assertNotEmpty($tile->id); // Verify the ID has been set, useless without this!
        $this->assertTrue($DB->record_exists('block_totara_featured_links_tiles', ['id' => $tile->id]));

        // Manually get the tile instance so that we can compare the real one with the generator one.
        $realtile = new \block_totara_featured_links\tile\default_tile($tile->id);

        $realtile_reflection = new ReflectionClass($realtile);
        $realtile_properties = [];
        foreach ($realtile_reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $realtile_properties[$property->getName()] = $property->getValue($realtile);
        }

        $tile_reflection = new ReflectionClass($tile);
        $tile_properties = [];
        foreach ($tile_reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $tile_properties[$property->getName()] = $property->getValue($tile);
        }

        // We now have an array of public properties from each, lets check they match exactly.
        foreach ($realtile_properties as $expectedname => $expectedvalue) {
            $this->assertArrayHasKey($expectedname, $tile_properties, 'The generated tile does not have the '.$expectedname.' property.');

            $this->assertEquals($expectedvalue, $tile_properties[$expectedname], 'The generated tile '.$expectedname.' property value does not match the real tiles value.');

            // I'm excluding stdClass cause they have to point to the same object so it doesn't make sense for them to be the same here.
            if (!$expectedvalue instanceof \stdClass && !$tile_properties[$expectedname] instanceof \stdClass) {
                $this->assertSame($expectedvalue,
                    $tile_properties[$expectedname],
                    'The generated tile ' . $expectedname . ' property value is not of the same type as the real tiles value.'
                );
            }

            // Remove the property, you'll see why next!
            unset($tile_properties[$expectedname]);
        }

        // If there are any left over properties then they exist on the generated tile, and not the real tile.
        // That is worth an error!
        $this->assertEmpty($tile_properties, 'The generated tile has additional properties: '.join(', ', array_keys($tile_properties)));
    }
}