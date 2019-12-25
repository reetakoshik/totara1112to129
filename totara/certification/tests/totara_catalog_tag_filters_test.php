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
 * @package totara_certification
 * @category totara_catalog
 */

namespace totara_certification\totara_catalog\certification;

use totara_catalog\catalog_retrieval;
use totara_catalog\filter;
use totara_catalog\local\filter_handler;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_certification_totara_catalog_tag_filters_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test data.
     *
     * @param int $certification_count
     * @param int $tags_per_certification
     * @return array ("attached" tag names, mapping of tag ids to certifications,
     *         tag filters, all certifications, tagged certifications) tuple.
     */
    private function generate($certification_count = 15, $tags_per_certification = 3): array {
        $this->setAdminUser();

        $available_tags = [];
        $generator = $this->getDataGenerator();
        for ($i = 0; $i < 20; $i++) {
            $tag = $generator->create_tag();
            $available_tags[] = $tag;
        }

        $certifications_by_tag = [];
        $all_certifications = [];
        $tagged_certifications = [];
        $attached_tags = [];

        /** @var \totara_program_generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        for ($i = 0; $i < $certification_count; $i++) {
            $fullname = "tagged test certification name $i";
            $certification_id = $program_generator->create_certification(
                [
                    'fullname' => $fullname
                ]
            );
            $all_certifications[] = $fullname;
            $tagged_certifications[] = $fullname;

            $context = \context_program::instance($certification_id);
            for ($j = 0; $j < $tags_per_certification; $j++) {
                $k = rand(1, count($available_tags));
                $tag = $available_tags[$k - 1];
                \core_tag_tag::add_item_tag('totara_prog', 'prog', $certification_id, $context, $tag->rawname);

                $certifications = array_key_exists($tag->id, $certifications_by_tag)
                           ? $certifications_by_tag[$tag->id]
                           : [];

                if (!in_array($fullname, $certifications)) {
                    $certifications[] = $fullname;
                }
                $certifications_by_tag[$tag->id] = $certifications;

                if (!in_array($tag->name, $attached_tags)) {
                    $attached_tags[] = $tag->name;
                }
            }
        }

        // Create some certifications with no tags. These should not be picked
        // during the filtering although the catalog will still know of them.
        for ($i = 0; $i < 10; $i++) {
            $fullname = "untagged test certification name $i";
            $all_certifications[] = $fullname;

            $program_generator->create_certification(
                [
                    'fullname' => $fullname
                ]
            );
        }

        // Find prog and cert tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('totara_program', 'prog');

        // Filters were removed in setUp(); the line below indirectly loads the
        // certification_tag_filter among other certification filters. All the
        // filters are initially inactive.
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

        $this->assertNotNull($panel_filter, "certification tag panel filter not loaded");
        $this->assertNotNull($browse_filter, "certification tag browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        return [$attached_tags, $certifications_by_tag, $filters, $all_certifications, $tagged_certifications];
    }

    public function test_tag_panel_filter() {
        [$attached_tags, $certifications_by_tag, $filters, $all_certifications, $tagged_certifications] = $this->generate();

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
        foreach ($certifications_by_tag as $tag => $certifications) {
            $filter_data->set_current_data([$tag]);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($certifications), $result->objects, "wrong program count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $certifications, "wrong certifications for tag");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($certifications_by_tag));
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($tagged_certifications), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $tagged_certifications, "wrong certifications for multi selected tags");
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all programs *including untagged ones*.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_certifications), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_certifications, "wrong certifications for empty tag");
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
        [$attached_tags, $certifications_by_tag, $filters, $all_certifications, ] = $this->generate();

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
        foreach ($certifications_by_tag as $tag => $certifications) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($tag);
            $result = $catalog->get_page_of_objects(1000, 0);

            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $certifications, "wrong certifications for tag");
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all certifications *including untagged ones*.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_certifications), $result->objects, "wrong program count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_certifications, "wrong certifications for empty tag");
        }

        // Test filter with non existent tag.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");

        // Test filter with invalid tag value.
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('equal filter only accepts null, int, string or bool data');
        $filter_data->set_current_data(array_keys($certifications_by_tag));
    }
}
