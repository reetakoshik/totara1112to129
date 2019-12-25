<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for lib/classes/output/mustache_template_finder.php
 *
 * @package   core
 * @category  phpunit
 * @copyright 2015 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\output\mustache_template_finder;

/**
 * Unit tests for the Mustache template finder class (contains logic about
 * resolving mustache template locations.
 */
class core_mustache_template_finder_testcase extends advanced_testcase {

    public function test_get_template_directories_for_component() {
        global $CFG;
        // Test a plugin.
        $dirs = mustache_template_finder::get_template_directories_for_component('mod_assign', 'basis');
        $correct = array(
            $CFG->dirroot . '/theme/basis/templates/mod_assign/',
            $CFG->dirroot . '/theme/roots/templates/mod_assign/',
            $CFG->dirroot . '/theme/base/templates/mod_assign/',
            $CFG->dirroot . '/mod/assign/templates/'
        );
        $this->assertEquals($correct, $dirs);

        // Test a subsystem.
        $dirs = mustache_template_finder::get_template_directories_for_component('core_user', 'basis');
        $correct = array(
            $CFG->dirroot . '/theme/basis/templates/core_user/',
            $CFG->dirroot . '/theme/roots/templates/core_user/',
            $CFG->dirroot . '/theme/base/templates/core_user/',
            $CFG->dirroot . '/user/templates/'
        );
        $this->assertEquals($correct, $dirs);

        // Test core.
        $dirs = mustache_template_finder::get_template_directories_for_component('core', 'basis');
        $correct = array(
            $CFG->dirroot . '/theme/basis/templates/core/',
            $CFG->dirroot . '/theme/roots/templates/core/',
            $CFG->dirroot . '/theme/base/templates/core/',
            $CFG->dirroot . '/lib/templates/'
        );
        $this->assertEquals($correct, $dirs);

        // Test invalid theme.
        $dirs = mustache_template_finder::get_template_directories_for_component('mod_assign', 'xxsdsds');
        $defaulttheme = $CFG->theme;
        $themeconfig = theme_config::load($defaulttheme);
        $this->assertDebuggingCalled('This page should be using theme xxsdsds which cannot be initialised. Falling back to the site theme basis');
        $theme_parents = $themeconfig->parents;
        $correct = array();

        $correct[] = $CFG->dirroot . '/theme/' . $defaulttheme . '/templates/mod_assign/';
        foreach ($theme_parents as $parent) {
            $correct[] = $CFG->dirroot . '/theme/' . $parent . '/templates/mod_assign/';
        }

        $correct[] = $CFG->dirroot . '/mod/assign/templates/';

        $this->assertEquals($correct, $dirs);
    }

    /**
     * Ensure optional $CFG->themedir is added to template search paths.
     *
     * Encapsulate in own method as this test requires us to mutate
     * global state which could lead to unexpected results if assertions
     * are added after it in future.
     */
    public function test_custom_themedir_get_template_directories_for_component() {
        global $CFG;

        // Roll back state changes.
        $this->resetAfterTest();

        $CFG->themedir = '/foo/bar';
        $dirs = mustache_template_finder::get_template_directories_for_component('totara_core', 'basis');
        $correct = array(
            $CFG->dirroot . '/theme/basis/templates/totara_core/',
            '/foo/bar/basis/templates/totara_core/',
            $CFG->dirroot . '/theme/roots/templates/totara_core/',
            '/foo/bar/roots/templates/totara_core/',
            $CFG->dirroot . '/theme/base/templates/totara_core/',
            '/foo/bar/base/templates/totara_core/',
            $CFG->dirroot . '/totara/core/templates/'
        );
        $this->assertEquals($correct, $dirs);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_invalid_get_template_directories_for_component() {
        // Test something invalid.
        $dirs = mustache_template_finder::get_template_directories_for_component('octopus', 'basis');
    }

    public function test_get_template_filepath() {
        global $CFG;

        $filename = mustache_template_finder::get_template_filepath('core/pix_icon', 'basis');
        $correct = $CFG->dirroot . '/lib/templates/pix_icon.mustache';
        $this->assertSame($correct, $filename);
    }

    /**
     * @expectedException moodle_exception
     */
    public function test_invalid_get_template_filepath() {
        // Test something invalid.
        $dirs = mustache_template_finder::get_template_filepath('core/octopus', 'basis');
    }
}
