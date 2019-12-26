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
 * Unit tests for lib/outputrequirementslibphp.
 *
 * @package   core
 * @category  phpunit
 * @copyright 2012 Petr Škoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/outputrequirementslib.php');


class core_outputrequirementslib_testcase extends advanced_testcase {
    public function test_string_for_js() {
        $this->resetAfterTest();

        $page = new moodle_page();
        $page->requires->string_for_js('course', 'moodle', 1);
        $page->requires->string_for_js('course', 'moodle', 1);
        $this->expectException('coding_exception');
        $page->requires->string_for_js('course', 'moodle', 2);

        // Note: we can not switch languages in phpunit yet,
        //       it would be nice to test that the strings are actually fetched in the footer.
    }

    public function test_one_time_output_normal_case() {
        $page = new moodle_page();
        $this->assertTrue($page->requires->should_create_one_time_item_now('test_item'));
        $this->assertFalse($page->requires->should_create_one_time_item_now('test_item'));
    }

    public function test_one_time_output_repeat_output_throws() {
        $page = new moodle_page();
        $page->requires->set_one_time_item_created('test_item');
        $this->expectException('coding_exception');
        $page->requires->set_one_time_item_created('test_item');
    }

    public function test_one_time_output_different_pages_independent() {
        $firstpage = new moodle_page();
        $secondpage = new moodle_page();
        $this->assertTrue($firstpage->requires->should_create_one_time_item_now('test_item'));
        $this->assertTrue($secondpage->requires->should_create_one_time_item_now('test_item'));
    }

    /**
     * Test for the jquery_plugin method.
     *
     * Test to make sure that backslashes are not generated with either slasharguments set to on or off.
     */
    public function test_jquery_plugin() {
        global $CFG, $PAGE;

        $this->resetAfterTest();

        // With slasharguments on.
        $CFG->slasharguments = 1;

        $page = new moodle_page();
        $requirements = $page->requires;
        // Assert successful method call.
        $this->assertTrue($requirements->jquery_plugin('jquery'));
        $this->assertTrue($requirements->jquery_plugin('ui'));

        // Get the code containing the required jquery plugins.
        /* @var core_renderer $renderer */
        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_MAINTENANCE);
        $requirecode = $requirements->get_top_of_body_code($renderer);
        // Make sure that the generated code does not contain backslashes.
        $this->assertFalse(strpos($requirecode, '\\'), "Output contains backslashes: " . $requirecode);

        // With slasharguments off.
        $CFG->slasharguments = 0;

        $page = new moodle_page();
        $requirements = $page->requires;
        // Assert successful method call.
        $this->assertTrue($requirements->jquery_plugin('jquery'));
        $this->assertTrue($requirements->jquery_plugin('ui'));

        // Get the code containing the required jquery plugins.
        $requirecode = $requirements->get_top_of_body_code($renderer);
        // Make sure that the generated code does not contain backslashes.
        $this->assertFalse(strpos($requirecode, '\\'), "Output contains backslashes: " . $requirecode);
    }

    /**
     * Test that function will not produce syntax error if provided values cannot be json encoded
     */
    public function test_js_call_amd_json() {
        $page = new moodle_page();
        /**
         * @var page_requirements_manager $requirements
         */
        $requirements = $page->requires;

        // Valid case.
        $requirements->js_call_amd('core/add_block_popover', 'a', ['valid', 'text']);
        $code = implode(';', $requirements->get_raw_amd_js_code());
        $this->assertContains('a("valid", "text")', $code);
        $this->assertContains('"core/add_block_popover"', $code);

        // Valid: Empty array.
        $requirements->js_call_amd('core/test', 'd', [[], 'text']);
        $code = implode(';', $requirements->get_raw_amd_js_code());
        $this->assertContains('d([], "text")', $code);

        // Valid: Null value.
        $requirements->js_call_amd('core/test', 'e', [null, null]);
        $code = implode(';', $requirements->get_raw_amd_js_code());
        $this->assertContains('e(null, null)', $code);

        // Invalid UTF-8.
        $requirements->js_call_amd('core/test', 'b', ['invalid' . "\xB1\x31", 'text']);
        $code = implode(';', $requirements->get_raw_amd_js_code());
        $this->assertContains('b(null, "text")', $code);
        $this->assertNotContains('invalid', $code);

        // Invalid type.
        $requirements->js_call_amd('core/test', 'c', [NAN, 'text']);
        $code = implode(';', $requirements->get_raw_amd_js_code());
        $this->assertContains('c(null, "text")', $code);
    }
}
