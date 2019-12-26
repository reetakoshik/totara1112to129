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
 * @package core_tag
 * @category totara_catalog
 */

namespace core_tag\totara_catalog;

use totara_catalog\catalog_retrieval;
use totara_catalog\filter;
use totara_catalog\local\filter_handler;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class core_tag_totara_catalog_tag_filters_testcase extends \advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Generates test courses.
     *
     * @param array $all_tags available tags.
     * @param array $attached_tags "attached" tag names.
     * @param array $all_items existing generated items.
     * @param array $all_items_by_tag mapping of existing tag ids to arrays of
     *        names of items with those tags.
     * @param array $all_tagged_items list of tagged item fullnames.
     * @param int $no_of_items no of items to generate.
     * @param int $tags_per_item no of tags to attach to one item.
     *
     * @return array (array, array, array, array) tuple representing (updated
     *         attached tags, updated mapping of tag ids to items, updated all
     *         items, updated tagged items).
     */
    private function generate_courses(
        array $all_tags,
        array $attached_tags,
        array $all_items,
        array $all_items_by_tag,
        array $all_tagged_items,
        int $no_of_items,
        int $tags_per_item
    ): array {
        $generator = $this->getDataGenerator();
        $item_type = 'course';

        for ($i = 0; $i < $no_of_items; $i++) {
            $fullname = "tagged test $item_type name $i";
            $all_items[] = $fullname;
            $all_tagged_items[] = $fullname;

            $item_id = $generator->create_course(['fullname' => $fullname])->id;
            $context = \context_course::instance($item_id);

            for ($j = 0; $j < $tags_per_item; $j++) {
                $k = rand(1, count($all_tags));
                $tag = $all_tags[$k - 1];

                \core_tag_tag::add_item_tag(
                    'core',
                    $item_type,
                    $item_id,
                    $context,
                    $tag->rawname
                );

                $items_for_tag = array_key_exists($tag->id, $all_items_by_tag)
                                 ? $all_items_by_tag[$tag->id]
                                 : [];

                if (!in_array($fullname, $items_for_tag)) {
                    $items_for_tag[] = $fullname;
                }
                $all_items_by_tag[$tag->id] = $items_for_tag;

                if (!in_array($tag->name, $attached_tags)) {
                    $attached_tags[] = $tag->name;
                }
            }
        }

        // Create some items with no tags. These should not be picked during the
        // filtering although the catalog will still know of them.
        for ($i = 0; $i < 10; $i++) {
            $fullname = "untagged test $item_type name $i";
            $all_items[] = $fullname;

            $generator->create_course(['fullname' => $fullname]);
        }

        return [$attached_tags, $all_items_by_tag, $all_items, $all_tagged_items];
    }

    /**
     * Generates test programs.
     *
     * @param array $all_tags available tags.
     * @param array $attached_tags "attached" tag names.
     * @param array $all_items existing generated items.
     * @param array $all_items_by_tag mapping of existing tag ids to arrays of
     *        names of items with those tags.
     * @param array $all_tagged_items list of tagged item fullnames.
     * @param int $no_of_items no of items to generate.
     * @param int $tags_per_item no of tags to attach to one item.
     *
     * @return array (array, array, array, array) tuple representing (updated
     *         attached tags, updated mapping of tag ids to items, updated all
     *         items, updated tagged items).
     */
    private function generate_programs(
        array $all_tags,
        array $attached_tags,
        array $all_items,
        array $all_items_by_tag,
        array $all_tagged_items,
        int $no_of_items,
        int $tags_per_item
    ): array {
        /** @var \totara_program_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $item_type = 'prog';

        $tag = null;

        for ($i = 0; $i < $no_of_items; $i++) {
            $fullname = "tagged test $item_type name $i";
            $all_items[] = $fullname;
            $all_tagged_items[] = $fullname;

            $item_id = $generator->create_program(['fullname' => $fullname])->id;
            $context = \context_program::instance($item_id);

            for ($j = 0; $j < $tags_per_item; $j++) {
                $k = rand(1, count($all_tags));
                $tag = $all_tags[$k - 1];

                \core_tag_tag::add_item_tag(
                    'totara_program',
                    $item_type,
                    $item_id,
                    $context,
                    $tag->rawname
                );

                $items_for_tag = array_key_exists($tag->id, $all_items_by_tag)
                                 ? $all_items_by_tag[$tag->id]
                                 : [];

                if (!in_array($fullname, $items_for_tag)) {
                    $items_for_tag[] = $fullname;
                }
                $all_items_by_tag[$tag->id] = $items_for_tag;

                if (!in_array($tag->name, $attached_tags)) {
                    $attached_tags[] = $tag->name;
                }
            }
        }

        // Create some items with no tags. These should not be picked during the
        // filtering although the catalog will still know of them.
        for ($i = 0; $i < 10; $i++) {
            $fullname = "untagged test $item_type name $i";
            $all_items[] = $fullname;

            $generator->create_program(['fullname' => $fullname]);

            if (!in_array($tag->name, $attached_tags)) {
                $attached_tags[] = $tag->name;
            }
        }

        return [$attached_tags, $all_items_by_tag, $all_items, $all_tagged_items];
    }

    /**
     * Generates test certifications.
     *
     * @param array $all_tags available tags.
     * @param array $attached_tags "attached" tag names.
     * @param array $all_items existing generated items.
     * @param array $all_items_by_tag mapping of existing tag ids to arrays of
     *        names of items with those tags.
     * @param array $all_tagged_items list of tagged item fullnames.
     * @param int $no_of_items no of items to generate.
     * @param int $tags_per_item no of tags to attach to one item.
     *
     * @return array (array, array, array, array) tuple representing (updated
     *         attached tags, updated mapping of tag ids to items, updated all
     *         items, updated tagged items).
     */
    private function generate_certifications(
        array $all_tags,
        array $attached_tags,
        array $all_items,
        array $all_items_by_tag,
        array $all_tagged_items,
        int $no_of_items,
        int $tags_per_item
    ): array {
        /** @var \totara_program_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $item_type = 'prog';

        for ($i = 0; $i < $no_of_items; $i++) {
            $fullname = "tagged test cert name $i";
            $all_items[] = $fullname;
            $all_tagged_items[] = $fullname;

            $item_id = $generator->create_certification(['fullname' => $fullname]);
            $context = \context_program::instance($item_id);

            for ($j = 0; $j < $tags_per_item; $j++) {
                $k = rand(1, count($all_tags));
                $tag = $all_tags[$k - 1];

                \core_tag_tag::add_item_tag(
                    'totara_program',
                    $item_type,
                    $item_id,
                    $context,
                    $tag->rawname
                );

                $items_for_tag = array_key_exists($tag->id, $all_items_by_tag)
                                 ? $all_items_by_tag[$tag->id]
                                 : [];

                if (!in_array($fullname, $items_for_tag)) {
                    $items_for_tag[] = $fullname;
                }
                $all_items_by_tag[$tag->id] = $items_for_tag;

                if (!in_array($tag->name, $attached_tags)) {
                    $attached_tags[] = $tag->name;
                }
            }
        }

        // Create some items with no tags. These should not be picked during the
        // filtering although the catalog will still know of them.
        for ($i = 0; $i < 10; $i++) {
            $fullname = "untagged test cert name $i";
            $all_items[] = $fullname;

            $generator->create_certification(['fullname' => $fullname]);
        }

        return [$attached_tags, $all_items_by_tag, $all_items, $all_tagged_items];
    }

    /**
     * Generates test data.
     *
     * @param int $course_count
     * @param int $program_count
     * @param int $certification_count
     * @param int $tags_per_item
     * @return array (attached tags, mapping of tags to items, tag filters, all
     *         items, tagged items) tuple.
     */
    private function generate(
        $course_count = 15,
        $program_count = 15,
        $certification_count = 15,
        $tags_per_item = 3
    ): array {
        $this->setAdminUser();

        $all_tags = [];
        $generator = $this->getDataGenerator();
        for ($i = 0; $i < 20; $i++) {
            $tag = $generator->create_tag();
            $all_tags[] = $tag;
        }

        $items = [];
        $items_by_tag = [];
        $attached_tags = [];
        $tagged_items = [];

        [$attached_tags, $items_by_tag, $items, $tagged_items] = $this->generate_courses(
            $all_tags,
            $attached_tags,
            $items,
            $items_by_tag,
            $tagged_items,
            $course_count,
            $tags_per_item
        );

        [$attached_tags, $items_by_tag, $items, $tagged_items] = $this->generate_programs(
            $all_tags,
            $attached_tags,
            $items,
            $items_by_tag,
            $tagged_items,
            $program_count,
            $tags_per_item
        );

        [$attached_tags, $items_by_tag, $items, $tagged_items] = $this->generate_certifications(
            $all_tags,
            $attached_tags,
            $items,
            $items_by_tag,
            $tagged_items,
            $certification_count,
            $tags_per_item
        );

        // Find course tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('core', 'course');

        // Filters were removed in setUp(); the line below indirectly loads the
        // (merged) tag filter among other filters.
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

        $this->assertNotNull($panel_filter, "tag panel filter not loaded");
        $this->assertNotNull($browse_filter, "tag browse filter not loaded");
        $filters = [$panel_filter, $browse_filter];

        return [$attached_tags, $items_by_tag, $filters, $items, $tagged_items];
    }

    public function test_tag_panel_filter() {
        [$attached_tags, $items_by_tag, $filters, $all_items, $tagged_items] = $this->generate();

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
        foreach ($items_by_tag as $tag => $items) {
            $filter_data->set_current_data([$tag]);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($items), $result->objects, "wrong item count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $items, "wrong items for tag");
            }
        }

        // Test multiple filter selection.
        $filter_data->set_current_data(array_keys($items_by_tag));
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($tagged_items), $result->objects, "wrong item count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $tagged_items, "wrong items for multi selected tags");
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all items *including untagged ones*.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_items, "wrong items for empty tag");
        }

        // Test filter with non existent tag.
        $filter_data->set_current_data([123]);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");
    }

    public function test_tag_browse_filter() {
        [$attached_tags, $items_by_tag, $filters, $all_items, ] = $this->generate();

        /** @var filter $filter */
        $filter = $filters[1]; // Browse filter.
        /** @var single $filter_selector */
        $filter_selector = $filter->selector;

        // Test that display options show only those tags that are attached to an
        // item. Also, unlike the panel filter, the browse filter has an "all"
        // option.
        $filter_tags = array_slice($filter_selector->get_options(), 1);
        $this->assertEquals(count($attached_tags), count($filter_tags), "wrong tag count");
        foreach ($filter_tags as $tag) {
            $this->assertContains((string)$tag->name, $attached_tags, "unknown tag label");
        };

        // Test filtering by a single, specific tag.
        $catalog = new catalog_retrieval();
        $filter_data = $filter->datafilter;
        foreach ($items_by_tag as $tag => $items) {
            // Unlike the panel filter, the browse filter expects a single value
            // for matching.
            $filter_data->set_current_data($tag);
            $result = $catalog->get_page_of_objects(1000, 0);

            $this->assertCount(count($items), $result->objects, "wrong item count");
            foreach ($result->objects as $retrieved) {
                $this->assertContains($retrieved->sorttext, $items, "wrong items for tag");
            }
        }

        // Test empty filter selection. This should disable the filter and thus
        // returns all items *including untagged ones*.
        $filter_data->set_current_data(null);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(count($all_items), $result->objects, "wrong item count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_items, "wrong items for empty tag");
        }

        // Test filter with non existent tag.
        $filter_data->set_current_data(123);
        $result = $catalog->get_page_of_objects(1000, 0);
        $this->assertCount(0, $result->objects, "unknown data retrieved");
    }
}
