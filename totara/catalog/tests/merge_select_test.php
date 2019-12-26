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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the merge_select base class for catalog component.
 *
 * @group totara_catalog
 */
class totara_catalog_merge_select_testcase extends advanced_testcase {

    public function test_set_and_get_title() {
        $multi = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');

        $multi->set_title('newtitle');

        $this->assertEquals('newtitle', $multi->get_title());
    }

    public function test_get_optional_params() {
        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');

        $expectedoptionalparams = [
            new \totara_catalog\optional_param('testmergeselectkey', null, PARAM_RAW),
        ];

        $this->assertEquals($expectedoptionalparams, $search->get_optional_params());
    }

    public function test_set_title_hidden() {
        // Default.
        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');

        $template = $search->get_template();
        $data = $template->get_template_data();

        $this->assertEquals('testtitle', $data['title']);
        $this->assertFalse($data['title_hidden']);

        // Mark hidden.
        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $search->set_title_hidden();

        $template = $search->get_template();
        $data = $template->get_template_data();

        $this->assertEquals('testtitle', $data['title']);
        $this->assertTrue($data['title_hidden']);

        // Mark not hidden.
        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $search->set_title_hidden(false);

        $template = $search->get_template();
        $data = $template->get_template_data();

        $this->assertEquals('testtitle', $data['title']);
        $this->assertFalse($data['title_hidden']);
    }

    public function test_get_and_set_current_data() {
        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $search->set_current_data(['testmergeselectkey' => 'somedata']);

        $this->assertEquals('somedata', $search->get_data());
    }

    public function test_can_merge() {
        // Different classes cannot merge.
        $single = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $multi = new \totara_catalog\merge_select\multi('testmergeselectkey', 'testtitle');
        $this->assertFalse($single->can_merge($multi));

        // Different keys.
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey1', 'testtitle');
        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey2', 'testtitle');
        $this->assertFalse($single1->can_merge($single2));

        // Different titles.
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle1');
        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle2');
        $this->assertFalse($single1->can_merge($single2));

        // Different title hidden.
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single2->set_title_hidden();
        $this->assertFalse($single1->can_merge($single2));

        // Different current data.
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single1->set_current_data(['testmergeselectkey' => 'testdata1']);
        $single2->set_current_data(['testmergeselectkey' => 'testdata2']);
        $this->assertFalse($single1->can_merge($single2));

        // Otherwise they can be merged.
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single1->set_title_hidden();
        $single2->set_title_hidden();
        $single1->set_current_data(['testmergeselectkey' => 'testdata']);
        $single2->set_current_data(['testmergeselectkey' => 'testdata']);
        $this->assertTrue($single1->can_merge($single2));
    }

    public function test_merge_can_merge() {
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');
        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey', 'testtitle');

        $single1->merge($single2);
    }

    public function test_merge_cannot_merge() {
        $single1 = new \totara_catalog\merge_select\single('testmergeselectkey1', 'testtitle');
        $single2 = new \totara_catalog\merge_select\single('testmergeselectkey2', 'testtitle');

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Tried to merge two selectors that are not identical');
        $single1->merge($single2);
    }
}
