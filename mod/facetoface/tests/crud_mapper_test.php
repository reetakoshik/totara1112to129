<?php
/*
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_crud_mapper_testcase extends advanced_testcase {
    /**
     * @return array
     */
    public function provide_mappers(): array {
        return array_map(
            function($classname) {
                return [$classname];
            },
            array_filter(
                array_keys(core_component::get_component_classes_in_namespace('mod_facetoface')),
                function($classname2) {
                    $classes = class_uses($classname2);
                    return in_array(\mod_facetoface\traits\crud_mapper::class, $classes);
                }
            )
        );
    }
    
    /**
     * @dataProvider provide_mappers
     * @return void
     */
    public function test_object_mapping_database(string $className): void {
        global $DB;

        try {
            $refClass = new ReflectionClass($className);
            if (!$refClass->hasConstant('DBTABLE')) {
                $this->fail("Expecting the crud mapper object to have a constant database");
            }

            $table = $refClass->getConstant('DBTABLE');
            $properties = $refClass->getProperties();

            $fields = [];
            foreach ($properties as $property) {
                $doc = $property->getDocComment();
                if (!$doc) {
                    // No php docblock
                    continue;
                }

                if (stripos($doc, "{{$table}}") !== false) {
                    // If the document does contain a table name, then definitely that the property
                    // is mapping with the database
                    $fields[] = $property->getName();
                }
            }

            $columns = $DB->get_columns($table);
            /** @var database_column_info $column */
            foreach ($columns as $column) {
                $message = "Class: '{$className}' -> field: {$column->name}";
                $this->assertContains($column->name, $fields, $message);
            }

            // Assuring the number of columns and number of fields in the crud_mapper object
            // is equal to each other.
            $this->assertEquals(count($columns), count($fields));
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }
    }
}