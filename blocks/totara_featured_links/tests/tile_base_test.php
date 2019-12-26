<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

require_once('test_helper.php');

use block_totara_featured_links\tile\base;
use block_totara_featured_links\tile\default_tile;


defined('MOODLE_INTERNAL') || die();

/**
 * Tests the static methods on the abstract block_totara_featured_links\tile\base class
 */
class block_totara_featured_links_tile_base_testcase extends test_helper {

    /**
     * The block generator instance for the test.
     * @var block_totara_featured_links_generator $generator
     */
    protected $blockgenerator;

    /**
     * Gets executed before every test case.
     */
    public function setUp() {
        parent::setUp();
        $this->blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
    }

    public function tearDown() {
        parent::tearDown();
        $this->blockgenerator = null;
    }

    /**
     * Tests the block_totara_featured_links\tile\base::get_tile_instance() method.
     */
    public function test_get_tile_instance() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // First up test with a real id.
        $blockinstance = $this->blockgenerator->create_instance();
        $tile = $this->blockgenerator->create_default_tile($blockinstance->id);
        $expected = '\block_totara_featured_links\tile\default_tile';
        $this->assertInstanceOf($expected, base::get_tile_instance($tile->id));

        // Now test with an id that can't possibly exist.
        $this->expectException('dml_missing_record_exception', 'Can not find data record in database');
        base::get_tile_instance(-1);
    }

    /**
     * Tests the block_totara_featured_links\tile\base::squash_ordering() method.
     */
    public function test_squash_ordering() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $blockinstance1 = $this->blockgenerator->create_instance();
        $blockinstance2 = $this->blockgenerator->create_instance();

        $tile1_a = $this->blockgenerator->create_default_tile($blockinstance1->id);
        $tile1_b = $this->blockgenerator->create_default_tile($blockinstance1->id);
        $tile1_c = $this->blockgenerator->create_default_tile($blockinstance1->id);

        $tile2_a = $this->blockgenerator->create_default_tile($blockinstance2->id);
        $tile2_b = $this->blockgenerator->create_default_tile($blockinstance2->id);
        $tile2_c = $this->blockgenerator->create_default_tile($blockinstance2->id);

        // Test that both blocks have their tiles in the correct order.
        $this->assertEquals(1, $tile1_a->sortorder);
        $this->assertEquals(2, $tile1_b->sortorder);
        $this->assertEquals(3, $tile1_c->sortorder);

        $this->assertEquals(1, $tile2_a->sortorder);
        $this->assertEquals(2, $tile2_b->sortorder);
        $this->assertEquals(3, $tile2_c->sortorder);

        // Now mess them up.
        // We do this directly in the database as we only want to test the base method, and not the blocks sortorder method.
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '5', ['id' => $tile1_a->id]);
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '9', ['id' => $tile1_b->id]);
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '7', ['id' => $tile1_c->id]);
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '8', ['id' => $tile2_a->id]);
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '6', ['id' => $tile2_b->id]);
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '4', ['id' => $tile2_c->id]);

        // Refresh the objects and confirm the sortorder.
        $tile1_a = new default_tile($tile1_a->id);
        $tile1_b = new default_tile($tile1_b->id);
        $tile1_c = new default_tile($tile1_c->id);

        $tile2_a = new default_tile($tile2_a->id);
        $tile2_b = new default_tile($tile2_b->id);
        $tile2_c = new default_tile($tile2_c->id);

        $this->assertEquals(5, $tile1_a->sortorder);
        $this->assertEquals(9, $tile1_b->sortorder);
        $this->assertEquals(7, $tile1_c->sortorder);

        $this->assertEquals(8, $tile2_a->sortorder);
        $this->assertEquals(6, $tile2_b->sortorder);
        $this->assertEquals(4, $tile2_c->sortorder);

        // Now run squash ordering on the second block instance.
        base::squash_ordering($blockinstance2->id);

        // Refresh the objects and confirm the sortorder.
        $tile1_a = new default_tile($tile1_a->id);
        $tile1_b = new default_tile($tile1_b->id);
        $tile1_c = new default_tile($tile1_c->id);

        $tile2_a = new default_tile($tile2_a->id);
        $tile2_b = new default_tile($tile2_b->id);
        $tile2_c = new default_tile($tile2_c->id);

        $this->assertEquals(5, $tile1_a->sortorder);
        $this->assertEquals(9, $tile1_b->sortorder);
        $this->assertEquals(7, $tile1_c->sortorder);

        $this->assertEquals(3, $tile2_a->sortorder);
        $this->assertEquals(2, $tile2_b->sortorder);
        $this->assertEquals(1, $tile2_c->sortorder);

        // Now run squash ordering on the first block instance.
        base::squash_ordering($blockinstance1->id);

        // Refresh the objects and confirm the sortorder.
        $tile1_a = new default_tile($tile1_a->id);
        $tile1_b = new default_tile($tile1_b->id);
        $tile1_c = new default_tile($tile1_c->id);

        $tile2_a = new default_tile($tile2_a->id);
        $tile2_b = new default_tile($tile2_b->id);
        $tile2_c = new default_tile($tile2_c->id);

        $this->assertEquals(1, $tile1_a->sortorder);
        $this->assertEquals(3, $tile1_b->sortorder);
        $this->assertEquals(2, $tile1_c->sortorder);

        $this->assertEquals(3, $tile2_a->sortorder);
        $this->assertEquals(2, $tile2_b->sortorder);
        $this->assertEquals(1, $tile2_c->sortorder);
    }

    /**
     * Tests the block_totara_featured_links\tile\base::squash_ordering() method on a block
     * instance with no tiles.
     *
     * This is a simple test, there are no tiles, so there is no action taken.
     * We are really just testing that it doesn't error!
     */
    public function test_squash_ordering_without_tiles() {
        $this->resetAfterTest(); // Changing the database, we must reset.

        $blockinstance = $this->blockgenerator->create_instance();
        base::squash_ordering($blockinstance->id);
    }

    public function test_squash_ordering_of_subtiles() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        /** @var block_totara_featured_links_generator $featuredlinksgenerator */
        $featuredlinksgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');

        $block1 = $featuredlinksgenerator->create_instance();

        $data = new stdClass();
        $data->sortorder = 2;
        $gallerytile1 = $featuredlinksgenerator->create_gallery_tile($block1->id, 0, $data);
        $data->sortorder = 1;
        $gallerytile2 = $featuredlinksgenerator->create_gallery_tile($block1->id, 0, $data);
        $data->parentid = $gallerytile1->id;
        $defaulttile1 = $featuredlinksgenerator->create_default_tile($block1->id, $gallerytile1->id, $data);
        $data->sortorder = 4;
        $data->parentid = $gallerytile1->id;
        $defaulttile2 = $featuredlinksgenerator->create_default_tile($block1->id, $gallerytile1->id, $data);

        base::squash_ordering($block1->id);

        $gallerytile1data = $DB->get_record('block_totara_featured_links_tiles', ['id' => $gallerytile1->id]);
        $gallerytile2data = $DB->get_record('block_totara_featured_links_tiles', ['id' => $gallerytile2->id]);

        $this->assertEquals(2, $gallerytile1data->sortorder);
        $this->assertEquals(1, $gallerytile2data->sortorder);

        $defaulttile1data = $DB->get_record('block_totara_featured_links_tiles', ['id' => $defaulttile1->id]);
        $defaulttile2data = $DB->get_record('block_totara_featured_links_tiles', ['id' => $defaulttile2->id]);

        $this->assertEquals($defaulttile1->sortorder, $defaulttile1data->sortorder);
        $this->assertEquals($defaulttile2->sortorder, $defaulttile2data->sortorder);

        base::squash_ordering($block1->id, $gallerytile1->id);

        $defaulttile1data = $DB->get_record('block_totara_featured_links_tiles', ['id' => $defaulttile1->id]);
        $defaulttile2data = $DB->get_record('block_totara_featured_links_tiles', ['id' => $defaulttile2->id]);

        $this->assertEquals(1, $defaulttile1data->sortorder);
        $this->assertEquals(2, $defaulttile2data->sortorder);

    }

    /**
     * Tests the block_totara_featured_links\tile\base::squash_ordering() method on a block
     * instance with tiles which have the same sortorder.
     */
    public function test_squash_ordering_with_duplicate_sort_values() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $tile2 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $tile3 = $this->blockgenerator->create_default_tile($blockinstance->id);

        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '5', ['id' => $tile1->id]);
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '5', ['id' => $tile2->id]);
        $DB->set_field('block_totara_featured_links_tiles', 'sortorder', '1', ['id' => $tile3->id]);

        // Refresh the objects and confirm the sortorder.
        $tile1 = new default_tile($tile1->id);
        $tile2 = new default_tile($tile2->id);
        $tile3 = new default_tile($tile3->id);

        $this->assertEquals(5, $tile1->sortorder);
        $this->assertEquals(5, $tile2->sortorder);
        $this->assertEquals(1, $tile3->sortorder);

        base::squash_ordering($blockinstance->id);

        // Refresh the objects.
        $tile1 = new default_tile($tile1->id);
        $tile2 = new default_tile($tile2->id);
        $tile3 = new default_tile($tile3->id);

        // We don't know the exact sortorderorder now, what is important is that we no longer have a duplicate.
        // So check that the sortorderorders are unique.
        $sortorders = [
            $tile1->sortorder,
            $tile2->sortorder,
            $tile3->sortorder,
        ];
        // This basically calls array_unique to remove duplicates. If there are duplicates then the number of.
        // values will decrease and will no longer match the number in the original array.
        $this->assertCount(count($sortorders), array_unique($sortorders));
    }

    /**
     * Tests the base::get_name() method.
     */
    public function test_get_name() {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Please Override this function');
        base::get_name();
    }

    public function test_get_next_sortorder() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $base = $this->getMockForAbstractClass('\block_totara_featured_links\tile\base');
        $blockinstance = $this->blockgenerator->create_instance();
        $this->assertEquals(1, $this->call_protected_method($base, 'get_next_sortorder', [$blockinstance->id]));
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $this->assertEquals(2, $this->call_protected_method($base, 'get_next_sortorder', [$blockinstance->id]));
        $tile2 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $this->assertEquals(3, $this->call_protected_method($base, 'get_next_sortorder', [$blockinstance->id]));
        $tile1->remove_tile();
        $this->assertEquals(2, $this->call_protected_method($base, 'get_next_sortorder', [$blockinstance->id]));
        $tile2->remove_tile();
        $this->assertEquals(1, $this->call_protected_method($base, 'get_next_sortorder', [$blockinstance->id]));
    }

    public function test_remove_tile() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $tile2 = $this->blockgenerator->create_default_tile($blockinstance->id);
        // Remove the tile.
        $this->assertTrue($tile1->remove_tile(), 'The tile failed to be removed');
        // Check that the tile is no longer in the database.
        $this->assertEmpty($DB->get_record('block_totara_featured_links_tiles', ['id' => $tile1->id]), 'The tile was not removed from the database');
        // Make sure you cant remove the same tile twice.
        $this->assertFalse($tile1->remove_tile());
        // The other tile should still exist.
        $this->assertNotEmpty($DB->get_record('block_totara_featured_links_tiles', ['id' => $tile2->id]), 'The wrong tile was removed');
    }

    public function test_save() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($instance->id);
        $tile2 = $this->blockgenerator->create_default_tile($instance->id);
        $tile1_modify_time = $tile1->timemodified;
        $this->waitForSecond();
        $data = new \stdClass();
        $data->type = 'not_a_plugin,not_a_class';
        $data->sortorder = 4;
        $data->url = 'www.example.com';
        $tile1->save_content($data);
        // Check the type was updated.
        $this->assertEquals('not_a_plugin,not_a_class', $DB->get_field('block_totara_featured_links_tiles', 'type', ['id' => $tile1->id]));
        // Check the ordering changed.
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $tile1->id]));
        $this->assertEquals(1, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $tile2->id]));
        // Check that the time modified changed.
        $this->assertNotEquals($tile1_modify_time, $DB->get_field('block_totara_featured_links_tiles', 'timemodified', ['id' => $tile1->id]));
    }

    public function test_save_ordering() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $instance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($instance->id);
        $tile2 = $this->blockgenerator->create_default_tile($instance->id);
        $tile3 = $this->blockgenerator->create_default_tile($instance->id);
        $tile4 = $this->blockgenerator->create_default_tile($instance->id);
        $tile5 = $this->blockgenerator->create_default_tile($instance->id);

        // Move 1 to the end.
        $tile1->sortorder = 5;
        $this->call_protected_method($tile1, 'save_ordering');
        $this->refresh_tiles($tile1, $tile2, $tile3, $tile4, $tile5);
        $this->assertEquals(5, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $tile1->id]));
        // Move 4 past the end.
        $tile4->sortorder = 10;
        $this->call_protected_method($tile4, 'save_ordering');
        $this->refresh_tiles($tile1, $tile2, $tile3, $tile4, $tile5);
        $this->assertEquals(5, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $tile4->id]));
        // Move 3 to its current position (2).
        $this->call_protected_method($tile3, 'save_ordering');
        $this->refresh_tiles($tile1, $tile2, $tile3, $tile4, $tile5);
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $tile3->id]));
        // Moving 4 to past the start.
        $tile4->sortorder = -10;
        $this->call_protected_method($tile4, 'save_ordering');
        $this->refresh_tiles($tile1, $tile2, $tile3, $tile4, $tile5);
        $this->assertEquals(1, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $tile4->id]));
        // Move 3 down one to 2.
        $tile3->sortorder = 2;
        $this->call_protected_method($tile3, 'save_ordering');
        $this->refresh_tiles($tile1, $tile2, $tile3, $tile4, $tile5);
        $this->assertEquals(2, $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $tile3->id]));
    }

    public function test_save_visibility () {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $data = new \stdClass();
        $data->visibility = base::VISIBILITY_HIDE;
        $tile1->save_visibility($data);

        $this->assertEquals((string)base::VISIBILITY_HIDE, $DB->get_field('block_totara_featured_links_tiles', 'visibility', ['id' => $tile1->id]));
        $this->assertEquals(base::AGGREGATION_ANY, $DB->get_field('block_totara_featured_links_tiles', 'audienceaggregation', ['id' => $tile1->id]));
        $this->assertEquals(base::AGGREGATION_ANY, $DB->get_field('block_totara_featured_links_tiles', 'overallaggregation', ['id' => $tile1->id]));

        $data->audience_showing = 1;
        $data->preset_showing = 1;
        $data->tile_rules_showing = 1;
        $tile1->save_visibility($data);
        // The *_showing fields shouldn't change because the visibility is not show.
        $this->assertEquals('0', $DB->get_field('block_totara_featured_links_tiles', 'audienceshowing', ['id' => $tile1->id]));
        $this->assertEquals('0', $DB->get_field('block_totara_featured_links_tiles', 'presetshowing', ['id' => $tile1->id]));
        $this->assertEquals('0', $DB->get_field('block_totara_featured_links_tiles', 'tilerulesshowing', ['id' => $tile1->id]));

        $data->visibility = base::VISIBILITY_CUSTOM;
        $audience1 = $this->getDataGenerator()->create_cohort();
        $audience2 = $this->getDataGenerator()->create_cohort();
        $data->audiences_visible = $audience1->id.','.$audience2->id;
        $data->presets_checkboxes = ['loggedin'];
        $tile1->save_visibility($data);
        // The *_showing fields should now all be 1.
        $this->assertEquals('1', $DB->get_field('block_totara_featured_links_tiles', 'audienceshowing', ['id' => $tile1->id]));
        $this->assertEquals('1', $DB->get_field('block_totara_featured_links_tiles', 'presetshowing', ['id' => $tile1->id]));
        $this->assertEquals('1', $DB->get_field('block_totara_featured_links_tiles', 'tilerulesshowing', ['id' => $tile1->id]));

        $data->visibility = base::VISIBILITY_HIDE;
        $tile1->save_visibility($data);
        $this->assertFalse($DB->record_exists(
            'cohort_visibility',
            ['instanceid' => $tile1->id, 'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS, 'cohortid' => $audience1->id])
        );

    }

    public function test_is_visible() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $this->assertTrue($tile1->is_visible());
        $data = new \stdClass();
        $data->visibility = base::VISIBILITY_HIDE;
        $tile1->save_visibility($data);
        $this->assertFalse($tile1->is_visible());
    }

    public function test_is_visible_preset() {
        $this->resetAfterTest();

        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $data = new \stdClass();
        $data->visibility = base::VISIBILITY_CUSTOM;
        $data->preset_showing = 1;
        $data->presets_checkboxes = ['loggedin'];
        $tile1->save_visibility($data);

        $this->assertFalse($tile1->is_visible());
        $this->setAdminUser();
        $this->assertTrue($tile1->is_visible());
    }

    public function test_is_visible_audience() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $audience1 = $this->getDataGenerator()->create_cohort();
        $audience2 = $this->getDataGenerator()->create_cohort();
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $this->setAdminUser();
        $generator->create_cohort_member(['userid' => $USER->id, 'cohortid' => $audience1->id]);
        $this->setGuestUser();

        $data = new \stdClass();
        $data->visibility = base::VISIBILITY_CUSTOM;
        $data->audience_showing = 1;
        $data->audiences_visible = $audience1->id;
        $tile1->save_visibility($data);
        $tile1 = base::get_tile_instance($tile1->id); // Refreshing the tile data.
        $this->assertFalse($tile1->is_visible());
        $this->setAdminUser();
        $this->assertTrue($tile1->is_visible());
    }

    public function test_is_visible_aggregation() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $audience1 = $this->getDataGenerator()->create_cohort();
        $audience2 = $this->getDataGenerator()->create_cohort();
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $this->setAdminUser();
        $generator->create_cohort_member(['userid' => $USER->id, 'cohortid' => $audience1->id]);
        $this->setGuestUser();
        $generator->create_cohort_member(['userid' => $USER->id, 'cohortid' => $audience2->id]);

        $data = new \stdClass();
        $data->visibility = base::VISIBILITY_CUSTOM;
        $data->audience_showing = 1;
        $data->audiences_visible = $audience1->id.','.$audience2->id;
        $tile1->save_visibility($data);
        $this->refresh_tiles($tile1);
        $this->assertTrue($tile1->is_visible());

        $data->audience_aggregation = base::AGGREGATION_ALL;
        $tile1->save_visibility($data);
        $this->refresh_tiles($tile1);
        $this->assertFalse($tile1->is_visible());

        $data->audience_showing = 0;
        $data->preset_showing = 1;
        $data->presets_checkboxes = ['loggedin', 'admin'];
        $tile1->save_visibility($data);
        $this->refresh_tiles($tile1);
        $this->assertTrue($tile1->is_visible());

        $data->presets_aggregation = base::AGGREGATION_ALL;
        $tile1->save_visibility($data);
        $this->refresh_tiles($tile1);
        $this->assertFalse($tile1->is_visible());

        $this->setAdminUser();
        $data->audience_showing = 1;
        $tile1->save_visibility($data);
        $this->refresh_tiles($tile1);
        $this->assertTrue($tile1->is_visible());

        $data->overall_aggregation = base::AGGREGATION_ALL;
        $tile1->save_visibility($data);
        $this->refresh_tiles($tile1);
        $this->assertFalse($tile1->is_visible());

    }

    public function test_get_visibility_form_data() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $data = $tile1->get_visibility_form_data();
        $required_keys = ['visibility', 'audiences_visible', 'audience_aggregation', 'presets_aggregation', 'overall_aggregation', 'audience_showing', 'preset_showing'];
        foreach ($required_keys as $key) {
            $this->assertTrue(isset($data[$key]), 'The key: '. $key.' does not exist in the array');
        }
    }

    public function test_export_form_template_add_tile() {
        global $PAGE;
        $PAGE->set_url(new \moodle_url('/'));
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $data = base::export_for_template_add_tile($blockinstance->id);
        $this->assertEquals('array', gettype($data));
    }

    public function test_is_visibility_applicable() {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $blockinstance = $this->blockgenerator->create_instance();
        $tile1 = $this->blockgenerator->create_default_tile($blockinstance->id);
        $this->assertTrue($tile1->is_visibility_applicable());
        $block_data = $DB->get_record('block_instances', ['id' => $tile1->blockid]);
        $block_data->pagetypepattern = 'totara-dashboard-1'; // Sets the block to be on a dashboard.

        $block_data->parentcontextid = array_values($DB->get_records('context', ['contextlevel' => 30], '', 'id'))[0]->id; // Sets the context to a users context.
        $DB->update_record('block_instances', $block_data);
        $this->assertFalse($tile1->is_visibility_applicable());
    }
}