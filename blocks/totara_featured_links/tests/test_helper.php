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

defined('MOODLE_INTERNAL') || die();

/**
 * Class test_helper
 * This is a helper for the testcases that contains methods for calling protected methods in other classes.
 */
abstract class test_helper extends advanced_testcase {

    /**
     * Sets the values of a protected property
     * @param $object
     * @param $property_name
     * @param $value
     */
    protected function set_protected_property($object, $property_name, $value) {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property_name);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }

    /**
     * Gets the value of a property that is not accessible
     * @param $object
     * @param $property_name
     * @return mixed
     */
    protected function get_protected_property($object, $property_name) {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property_name);
        $reflection_property->setAccessible(true);
        return $reflection_property->getValue($object);
    }
    /**
     * calls a protected method
     * @param object $object
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function call_protected_method($object, $method, array $args = []) {
        $class = new ReflectionClass(get_class($object));
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * Refreshers the data in the tile objects
     * @param array ...$tiles
     */
    protected function refresh_tiles(&...$tiles) {
        foreach ($tiles as &$tile) {
            $tile = block_totara_featured_links\tile\base::get_tile_instance($tile->id);
        }
    }
}