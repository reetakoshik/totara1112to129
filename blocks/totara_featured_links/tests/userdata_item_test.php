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

use block_totara_featured_links\tile\base;
use block_totara_featured_links\userdata\totara_featured_links_tiles;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

require_once('test_helper.php');

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_userdata
 * Class block_totara_featured_links_tile_gallery_tile_testcase
 */
class block_totara_featured_links_userdata_item_testcase extends test_helper {

    private function get_setup_data() {
        $this->resetAfterTest();
        $data = new class() {
            /** @var target_user */
            public $user1, $user2;
            /** @var stdClass */
            public $user1block, $user2block, $blocksystem;
            /** @var base */
            public $user1blocktile, $user2blocktile;
            /** @var context_system */
            public $systemcontext;
        };

        $data->systemcontext = context_system::instance();
        /** @var block_totara_featured_links_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
        $data->user1 = new target_user($this->getDataGenerator()->create_user());
        $data->user2 = new target_user($this->getDataGenerator()->create_user());
        $data->user1block = $this->getDataGenerator()->create_block(
            'totara_featured_links',
            ['parentcontextid' => context_user::instance($data->user1->id)->id]
        );
        $data->user2block = $this->getDataGenerator()->create_block(
            'totara_featured_links',
            ['parentcontextid' => context_user::instance($data->user2->id)->id]
        );
        $data->blocksystem = $this->getDataGenerator()->create_block('totara_featured_links');
        $data->user1blocktile = $generator->create_gallery_tile($data->user1block->id);
        $data->user2blocktile = $generator->create_default_tile($data->user2block->id);
        $generator->create_default_tile($data->blocksystem->id);

        $this->assertEquals(1, totara_featured_links_tiles::execute_count($data->user1, $data->systemcontext));

        return $data;
    }

    /**
     * Check that the data exported matches the data in the database.
     */
    public function test_export_data() {
        $data = $this->get_setup_data();

        $fs = get_file_storage();
        $file_record = [
            'contextid' => context_block::instance($data->user1block->id)->id,
            'component' => 'block_totara_featured_links',
            'filearea' => 'tile_backgrounds',
            'itemid' => $data->user1blocktile->id,
            'filepath' => '/',
            'filename' => 'image.png'
        ];
        $fs->create_file_from_string($file_record, 'test file');

        $export = totara_featured_links_tiles::execute_export($data->user1, $data->systemcontext);

        $this->check_export($export, $data->user1->contextid);

        /** @var stored_file $file */
        $file = array_values($export->files)[0];
        $this->assertEquals(1, count($export->files));
        $this->assertEquals($file_record['component'], $file->get_component());
        $this->assertEquals($file_record['itemid'], $file->get_itemid());
        $this->assertEquals($file_record['filename'], $file->get_filename());

        $exportuser2 = totara_featured_links_tiles::execute_export($data->user2, $data->systemcontext);

        $this->assertEmpty($exportuser2->files);

        $emptyuser = new target_user($this->getDataGenerator()->create_user());
        $emptyuserexport = totara_featured_links_tiles::execute_export($emptyuser, $data->systemcontext);
        $this->assertEmpty($emptyuserexport->data);
        $this->assertEmpty($emptyuserexport->files);
    }

    /**
     * @param $export
     * @param $usercontextid
     */
    private function check_export(export $export, int $usercontextid): void {
        global $DB;
        // So the export object is not edited.
        $export = clone($export);
        $blocks = $export->data;
        // Check the tiles match then unset the tiles.
        foreach ($blocks as $block) {
            $tiles = $block->tiles;
            $dbtiles = $DB->get_records('block_totara_featured_links_tiles', ['blockid' => $block->id]);
            sort($dbtiles);
            sort($tiles);
            // Check files match an unset the files.
            foreach ($dbtiles as $index => $dbtile) {
                $params = [
                    'contextid' => context_block::instance($block->id)->id,
                    'itemid' => $dbtile->id
                ];
                $files = $DB->get_records_sql(
                    "SELECT *
                       FROM {files}
                      WHERE contextid = :contextid
                        AND itemid = :itemid
                        AND filename != '.'",
                    $params
                 );
                $dbids = array_values(array_map(function($file) {
                    return $file->id;
                }, $files));
                $ids = array_map(function($file) {
                    return $file['fileid'];
                }, $tiles[$index]->files);
                $this->assertEquals($dbids, $ids);
                unset($tiles[$index]->files);
            }
            $this->assertEquals($dbtiles, $tiles);
            unset($block->tiles);
        }

        $dbblocks = $DB->get_records('block_instances', ['parentcontextid' => $usercontextid]);
        sort($blocks);
        sort($dbblocks);
        $this->assertEquals($dbblocks, $blocks);
    }

    /**
     * Tests that the export does fail when the users context doesnt exist
     */
    public function test_export_with_deleted_user() {
        global $DB;
        $data = $this->get_setup_data();

        $DB->set_field('user', 'deleted', 1, ['id' => $data->user1->id]);
        context_helper::delete_instance(CONTEXT_USER, $data->user1->id);
        $deleteduser = new target_user($DB->get_record('user', ['id' => $data->user1->id]));

        $export = totara_featured_links_tiles::execute_export($deleteduser, $data->systemcontext);
        $this->check_export($export, $data->user1->contextid);
    }

    /**
     * Test the the item counts the number of blocks correctly.
     */
    public function test_count_data() {
        $data = $this->get_setup_data();

        $user1count_1 = totara_featured_links_tiles::execute_count($data->user1, $data->systemcontext);
        $user2count_1 = totara_featured_links_tiles::execute_count($data->user2, $data->systemcontext);

        // New block for user1.
        $data->user1block = $this->getDataGenerator()->create_block(
            'totara_featured_links',
            ['parentcontextid' => context_user::instance($data->user1->id)->id]
        );

        $user1count_2 = totara_featured_links_tiles::execute_count($data->user1, $data->systemcontext);
        $user2count_2 = totara_featured_links_tiles::execute_count($data->user2, $data->systemcontext);

        $this->assertEquals($user1count_1 + 1, $user1count_2);
        $this->assertEquals($user2count_1, $user2count_2);

        // New block for user2.
        $data->user1block = $this->getDataGenerator()->create_block(
            'totara_featured_links',
            ['parentcontextid' => context_user::instance($data->user2->id)->id]
        );

        $user1count_3 = totara_featured_links_tiles::execute_count($data->user1, $data->systemcontext);
        $user2count_3 = totara_featured_links_tiles::execute_count($data->user2, $data->systemcontext);

        $this->assertEquals($user1count_2, $user1count_3);
        $this->assertEquals($user2count_2 + 1, $user2count_3);

        // New block for the system.
        $data->user1block = $this->getDataGenerator()->create_block(
            'totara_featured_links',
            ['parentcontextid' => $data->systemcontext->id]
        );

        $user1count_4 = totara_featured_links_tiles::execute_count($data->user1, $data->systemcontext);
        $user2count_4 = totara_featured_links_tiles::execute_count($data->user2, $data->systemcontext);

        $this->assertEquals($user1count_3, $user1count_4);
        $this->assertEquals($user2count_3, $user2count_4);
    }

    /**
     * Check that a user with no blocks has 0 count.
     */
    public function test_count_zero_when_user_has_no_blocks() {
        $data = $this->get_setup_data();

        $emptyuser = new target_user($this->getDataGenerator()->create_user());
        $emptyusercount = totara_featured_links_tiles::execute_count($emptyuser, $data->systemcontext);
        $this->assertEquals(0, $emptyusercount);
    }

    /**
     * Tests that count works on a user that has being deleted
     * deleting the context when the user gets del
     */
    public function test_count_works_with_deleted_user() {
        global $DB;
        $data = $this->get_setup_data();

        $DB->set_field('user', 'deleted', 1, ['id' => $data->user1->id]);
        context_helper::delete_instance(CONTEXT_USER, $data->user1->id);
        $deleteduser = new target_user($DB->get_record('user', ['id' => $data->user1->id]));

        $countafter = totara_featured_links_tiles::execute_count($deleteduser, $data->systemcontext);
        $this->assertEquals(0, $countafter);
    }
}