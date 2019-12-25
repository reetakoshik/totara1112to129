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
class totara_catalog_merge_select_search_text_testcase extends advanced_testcase {

    public function test_set_placeholder_hidden() {
        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $template = $search->get_template();
        $data = $template->get_template_data();
        $this->assertTrue($data['placeholder_show']);

        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $search->set_placeholder_hidden();
        $template = $search->get_template();
        $data = $template->get_template_data();
        $this->assertFalse($data['placeholder_show']);
    }

    public function test_can_merge() {
        $search1 = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $template = $search1->get_template();
        $data = $template->get_template_data();
        $this->assertTrue($data['placeholder_show']);

        $this->assertTrue($search1->can_merge($search1));

        $search2 = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $search2->set_placeholder_hidden();
        $template = $search2->get_template();
        $data = $template->get_template_data();
        $this->assertFalse($data['placeholder_show']); // Prevents merging.

        $this->assertFalse($search1->can_merge($search2));
    }

    public function test_get_template() {
        $search = new \totara_catalog\merge_select\search_text('testmergeselectkey', 'testtitle');
        $search->set_title_hidden();
        $search->set_current_data(['testmergeselectkey' => 'testsearchstring']);

        $template = $search->get_template();

        $this->assertEquals('totara_core\output\select_search_text', get_class($template));
        $data = $template->get_template_data();

        $expecteddata = [
            'key' => 'testmergeselectkey',
            'title' => 'testtitle',
            'title_hidden' => true,
            'current_val' => 'testsearchstring',
            'placeholder_show' => true,
            'has_hint_icon' => false,
        ];
        $this->assertEquals($expecteddata, $data);
    }
}
