<?php
/*
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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_customfield
 */

class totara_customfield_area_testcase extends advanced_testcase {

    /**
     * Returns an array of known core customfield areas.
     *
     * @return array
     */
    private function get_known_areas() {
        return array(
            'core_course' => array(
                'course' => array('course', 'course_filemgr')
            ),
            'mod_facetoface' => array(
                'facetofaceasset' => array('facetofaceasset', 'facetofaceasset_filemgr'),
                'facetofacesignup' => array('facetofacesignup', 'facetofacesignup_filemgr'),
                'facetofacecancellation' => array('facetofacecancellation', 'facetofacecancellation_filemgr'),
                'facetofaceroom' => array('facetofaceroom', 'facetofaceroom_filemgr'),
                'facetofacesession' => array('facetofacesession', 'facetofacesession_filemgr'),
                'facetofacesessioncancel' => array('facetofacesessioncancel', 'facetofacesessioncancel_filemgr'),
            ),
            'totara_hierarchy' => array(
                'goal' => array('goal', 'goal_filemgr'),
                'goal_user' => array('goal_user', 'goal_user_filemgr'),
                'position' => array('position', 'position_filemgr'),
                'organisation' => array('organisation', 'organisation_filemgr'),
                'competency' => array('competency', 'competency_filemgr'),
            ),
            'totara_plan' => array(
                'evidence' => array('evidence', 'evidence_filemgr')
            ),
            'totara_program' => array(
                'program' => array('program', 'program_filemgr')
            )
        );
    }

    /**
     * Cleans up after these tests.
     */
    public function tearDown() {

        // Destroy the cached helper instance.
        $instance = \totara_customfield\helper::get_instance();
        unset($instance);

        parent::tearDown();
    }

    /**
     * Test get_area_classes
     */
    public function test_get_area_classes() {
        $expected = array();
        foreach ($this->get_known_areas() as $component => $areas) {
            foreach ($areas as $area => $fileareas) {
                $expected[$area] = "{$component}\\customfield_area\\{$area}";
            }
        }
        $helper = \totara_customfield\helper::get_instance();
        $this->assertEquals($expected, $helper->get_area_classes());
    }

    /**
     * Test get_area_components
     */
    public function test_get_area_components() {
        $expected = array();
        foreach ($this->get_known_areas() as $component => $areas) {
            foreach ($areas as $area => $fileareas) {
                $expected[$area] = $component;
            }
        }
        $helper = \totara_customfield\helper::get_instance();
        $this->assertEquals($expected, $helper->get_area_components());
    }

    /**
     * Test get_filearea_mappings
     */
    public function test_get_filearea_mappings() {
        $expected = array();
        foreach ($this->get_known_areas() as $component => $areas) {
            foreach ($areas as $area => $fileareas) {
                foreach ($fileareas as $filearea) {
                    $expected[$filearea] = "{$component}\\customfield_area\\{$area}";
                }
            }
        }
        $helper = \totara_customfield\helper::get_instance();
        $this->assertEquals($expected, $helper->get_filearea_mappings());
    }

    /**
     * Test area::get_area_name
     */
    public function test_area_classes_get_area_name() {
        $expected = array();
        foreach ($this->get_known_areas() as $component => $areas) {
            foreach ($areas as $area => $fileareas) {
                $expected["{$component}\\customfield_area\\{$area}"] = $area;
            }
        }

        $helper = \totara_customfield\helper::get_instance();
        $classes = $helper->get_area_classes();
        foreach ($classes as $class) {
            $this->assertArrayHasKey($class, $expected);
            $this->assertSame($expected[$class], $class::get_area_name());
            unset($expected[$class]);
        }
        $this->assertEmpty($expected, 'Not all expected classes have an area management class');
    }

    /**
     * Test area::get_component
     */
    public function test_area_classes_get_component() {
        $expected = array();
        foreach ($this->get_known_areas() as $component => $areas) {
            foreach ($areas as $area => $fileareas) {
                $expected["{$component}\\customfield_area\\{$area}"] = $component;
            }
        }

        $helper = \totara_customfield\helper::get_instance();
        $classes = $helper->get_area_classes();
        foreach ($classes as $class) {
            $this->assertArrayHasKey($class, $expected);
            $this->assertSame($expected[$class], $class::get_component());
            unset($expected[$class]);
        }
        $this->assertEmpty($expected, 'Not all expected classes have an area management class');
    }
}