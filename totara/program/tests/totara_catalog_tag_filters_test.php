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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program;

use totara_catalog\catalog_retrieval;
use totara_catalog\filter;
use totara_catalog\local\filter_handler;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_program_totara_catalog_tag_filters_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $program_count
     * @param int $tags_per_program
     * @return array ("attached" tag names, mapping of tag ids to programs, tag
     *         filters, all programs, tagged programs) tuple.
     */
    private function generate($program_count = 15, $tags_per_program = 3): array {
        $this->setAdminUser();

        $available_tags = [];
        $generator = $this->getDataGenerator();
        for ($i = 0; $i < 20; $i++) {
            $tag = $generator->create_tag();
            $available_tags[] = $tag;
        }

        $programs_by_tag = [];
        $all_programs = [];
        $tagged_programs = [];
        $attached_tags = [];

        /** @var \totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        for ($i = 0; $i < $program_count; $i++) {
            $program = $program_generator->create_program(
                [
                    'fullname' => "tagged test program name $i"
                ]
            );
            $all_programs[] = $program->fullname;
            $tagged_programs[] = $program->fullname;

            $context = \context_program::instance($program->id);
            for ($j = 0; $j < $tags_per_program; $j++) {
                $k = rand(1, count($available_tags));
                $tag = $available_tags[$k - 1];
                \core_tag_tag::add_item_tag('totara_program', 'prog', $program->id, $context, $tag->rawname);

                $programs = array_key_exists($tag->id, $programs_by_tag)
                           ? $programs_by_tag[$tag->id]
                           : [];

                if (!in_array($program->fullname, $programs)) {
                    $programs[] = $program->fullname;
                }
                $programs_by_tag[$tag->id] = $programs;

                if (!in_array($tag->name, $attached_tags)) {
                    $attached_tags[] = $tag->name;
                }
            }
        }

        // Create some programs with no tags. These should not be picked during
        // the filtering although the catalog will still know of them.
        for ($i = 0; $i < 10; $i++) {
            $all_programs[] = $program_generator->create_program()->fullname;
        }

        // Find prog and cert tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('totara_program', 'prog');

        // Filters were removed in setUp(); the line below indirectly loads the
        // program_tag_filter among other program filters. All the filters are
        // initially inactive.
        $panel_filter = null;
        $browse_filter = null;
        $all_filters = filter_handler::instance()->get_all_filters();
        foreach ($all_filters as $filter) {
            if ($filter->key === 'tag_panel_' . $tagcollectionid) {
                $panel_filter = $filter;
            }

            if ($filter->key === 'tag_browse_' . $tagcollectionid) {
                $browse_filter = $filter;
            }
        }

        $this->assertNotNull($panel_filter, "program tag panel filter not loaded");
        $this->assertNotNull($browse_filter, "program tag browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        return [$attached_tags, $programs_by_tag, $filters, $all_programs, $tagged_programs];
    }

    public function test_tag_panel_filter() {
        [$attached_tags, $programs_by_tag, $filters, $all_programs, $tagged_programs] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[0]; // Panel filter.
        /** @var multi $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only those tags that are attached to a
        // course.
        $filter_tags = $filter_selector->get_options();
        $this->assertCount(count($attached_tags), $filter_tags, "wrong tag count");
        foreach ($filter_tags as $tag) {
            $this->assertContains((string)$tag, $attached_tags, "unknown tag label");
        }

        // Test filtering by a single, specific tag.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_tag as $tag => $programs) {
            $filter_data->set_current_data([$tag]);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for tag");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($programs_by_tag));
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($tagged_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $tagged_programs, "wrong programs for multi selected tags");
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all programs *including untagged ones*.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty tag");
        }
        // Test filter with non existent tag.
        $filter_data->set_current_data([123]);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid tag value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('in or equal search filter only accepts null or array data of int, string or bool');
        $filter_data->set_current_data(123);
    }

    public function test_tag_browse_filter() {
        [$attached_tags, $programs_by_tag, $filters, $all_programs, ] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only those tags that are attached to a
        // program. Also, unlike the panel filter, the browse filter has an "all"
        // option.
        $filter_tags = array_slice($filter_selector->get_options(), 1);
        $this->assertEquals(count($attached_tags), count($filter_tags), "wrong tag count");
        foreach ($filter_tags as $tag) {
            $this->assertContains((string)$tag->name, $attached_tags, "unknown tag label");
        };

        // Test filtering by a single, specific tag.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($programs_by_tag as $tag => $programs) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($tag);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($programs), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $programs, "wrong programs for tag");
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all programs *including untagged ones*.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_programs), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_programs, "wrong programs for empty tag");
        }

        // Test filter with non existent tag.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid tag value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($programs_by_tag));
    }
}
