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
use totara_catalog\feature;
use totara_catalog\cache_handler;
use totara_catalog\local\config;
use totara_catalog\local\feature_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class core_tag_totara_catalog_tag_feature_testcase extends \advanced_testcase {

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
     * @param int $no_of_items no of items to generate.
     * @param int $tags_per_item no of tags to attach to one item.
     *
     * @return array (array, array, array, array) tuple representing (updated
     *         attached tags, updated mapping of tag ids to items, updated all
     *         items).
     */
    private function generate_courses(
        array $all_tags,
        array $attached_tags,
        array $all_items,
        array $all_items_by_tag,
        int $no_of_items,
        int $tags_per_item
    ): array {
        $generator = $this->getDataGenerator();
        $item_type = 'course';

        for ($i = 0; $i < $no_of_items; $i++) {
            $fullname = "tagged test $item_type name $i";
            $all_items[] = $fullname;

            $item_id = $generator->create_course(['fullname' => $fullname])->id;
            $context = \context_course::instance($item_id);

            for ($j = 0; $j < $tags_per_item; $j++) {
                $k = $i % count($all_tags);
                $tag = $all_tags[$k];

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

        return [$attached_tags, $all_items_by_tag, $all_items];
    }

    /**
     * Generates test programs.
     *
     * @param array $all_tags available tags.
     * @param array $attached_tags "attached" tag names.
     * @param array $all_items existing generated items.
     * @param array $all_items_by_tag mapping of existing tag ids to arrays of
     *        names of items with those tags.
     * @param int $no_of_items no of items to generate.
     * @param int $tags_per_item no of tags to attach to one item.
     *
     * @return array (array, array, array, array) tuple representing (updated
     *         attached tags, updated mapping of tag ids to items, updated all
     *         items).
     */
    private function generate_programs(
        array $all_tags,
        array $attached_tags,
        array $all_items,
        array $all_items_by_tag,
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

            $item_id = $generator->create_program(['fullname' => $fullname])->id;
            $context = \context_program::instance($item_id);

            for ($j = 0; $j < $tags_per_item; $j++) {
                $k = $i % count($all_tags);
                $tag = $all_tags[$k];

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

        return [$attached_tags, $all_items_by_tag, $all_items];
    }

    /**
     * Generates test certifications.
     *
     * @param array $all_tags available tags.
     * @param array $attached_tags "attached" tag names.
     * @param array $all_items existing generated items.
     * @param array $all_items_by_tag mapping of existing tag ids to arrays of
     *        names of items with those tags.
     * @param int $no_of_items no of items to generate.
     * @param int $tags_per_item no of tags to attach to one item.
     *
     * @return array (array, array, array, array) tuple representing (updated
     *         attached tags, updated mapping of tag ids to items, updated all
     *         items).
     */
    private function generate_certifications(
        array $all_tags,
        array $attached_tags,
        array $all_items,
        array $all_items_by_tag,
        int $no_of_items,
        int $tags_per_item
    ): array {
        /** @var \totara_program_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $item_type = 'prog';

        for ($i = 0; $i < $no_of_items; $i++) {
            $fullname = "tagged test cert name $i";
            $all_items[] = $fullname;

            $item_id = $generator->create_certification(['fullname' => $fullname]);
            $context = \context_program::instance($item_id);

            for ($j = 0; $j < $tags_per_item; $j++) {
                $k = $i % count($all_tags);
                $tag = $all_tags[$k];

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

        return [$attached_tags, $all_items_by_tag, $all_items];
    }

    /**
     * Generates test data.
     *
     * @param int $course_count
     * @param int $program_count
     * @param int $certification_count
     * @param int $tags_per_item
     * @return array (attached tags, mapping of tags to items, tag filters, all
     *         items) tuple.
     */
    private function generate(
        $course_count = 5,
        $program_count = 3,
        $certification_count = 1,
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

        [$attached_tags, $items_by_tag, $items] = $this->generate_courses(
            $all_tags,
            $attached_tags,
            $items,
            $items_by_tag,
            $course_count,
            $tags_per_item
        );

        [$attached_tags, $items_by_tag, $items] = $this->generate_programs(
            $all_tags,
            $attached_tags,
            $items,
            $items_by_tag,
            $program_count,
            $tags_per_item
        );

        [$attached_tags, $items_by_tag, $items] = $this->generate_certifications(
            $all_tags,
            $attached_tags,
            $items,
            $items_by_tag,
            $certification_count,
            $tags_per_item
        );

        // Find course tag collection id.
        $tagcollectionid = \core_tag_area::get_collection('core', 'course');

        /** @var \totara_catalog\feature $feature */
        $feature = null;
        foreach (feature_handler::instance()->get_all_features() as $existing) {
            if ($existing->key === 'tag_' . $tagcollectionid) {
                $feature = $existing;
                break;
            }
        }
        $this->assertNotNull($feature, "feature not loaded");

        return [$attached_tags, $items_by_tag, $feature, $items];
    }

    /**
     * Returns the catalog search result after setting up the specified featured
     * learning options.
     *
     * @param string $source featured learning source.
     * @param string $value featured learning value.
     * @param bool $enabled whether the catalog featured learning facility is
     *        enabled.
     *
     * @return \stdClass retrieval result.
     */
    private function featured_learning_result(
        string $source,
        string $value,
        bool $enabled = true
    ): \stdClass {
        cache_handler::reset_all_caches();
        config::instance()->update(
            [
                'featured_learning_enabled' => $enabled,
                'featured_learning_source' => $source,
                'featured_learning_value' => $value
            ]
        );

        $catalog = new catalog_retrieval();
        return $catalog->get_page_of_objects(1000, 0);
    }

    public function test_tag_feature() {
        [$attached_tags, $items_by_tag, $feature, $all_items] = $this->generate();

        // Test features by a specific tag.
        foreach ($items_by_tag as $tag => $items) {
            $result = $this->featured_learning_result($feature->key, $tag);
            $this->assertCount(count($all_items), $result->objects, "wrong retrieved count");

            foreach ($result->objects as $i => $retrieved) {
                if ($i < count($items)) {
                    $this->assertContains($retrieved->sorttext, $items, "wrong featured for tag");
                    $this->assertSame(1, (int)$retrieved->featured, "featured item not at top of retrieved");
                } else {
                    $this->assertContains($retrieved->sorttext, $all_items, "unknown item");
                    $this->assertSame(0, (int)$retrieved->featured, "non featured item at top of retrieved");
                }
            }
        }

        // Test feature with non existent option. This is not possible via the
        // UI, but nonetheless it is possible programmatically.
        $result = $this->featured_learning_result($feature->key, 123);
        $this->assertCount(count($all_items), $result->objects, "wrong retrieved count");
        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_items, "unknown item");
            $this->assertSame(0, (int)$retrieved->featured, "featured item exists");
        }

        // Test disabled feature selection even if a valid option is there.
        $result = $this->featured_learning_result($feature->key, $attached_tags[0], false);
        $this->assertCount(count($all_items), $result->objects, "wrong retrieved count");

        foreach ($result->objects as $retrieved) {
            $this->assertContains($retrieved->sorttext, $all_items, "unknown item");
            $this->assertObjectNotHasAttribute('featured', $retrieved, "featured field exists");
        }
    }
}
