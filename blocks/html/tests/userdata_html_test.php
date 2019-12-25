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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package block_html
 */

namespace block_html\userdata;

use advanced_testcase;
use block_base;
use context_helper;
use context_system;
use context_user;
use moodle_page;
use stored_file;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of username
 *
 * @group totara_userdata
 */
class block_html_userdata_html_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $actualcontextlevels = html::get_compatible_context_levels();
        sort($actualcontextlevels);
        $this->assertEquals($expectedcontextlevels, $actualcontextlevels);
    }

    /**
     * Testing abilities, is_purgeable|countable|exportable()
     */
    public function test_abilities() {
        $this->assertTrue(html::is_countable());
        $this->assertTrue(html::is_exportable());
        $this->assertTrue(html::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(html::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(html::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Create fixtures for our tests.
     */
    private function create_fixtures() {
        $this->resetAfterTest(true);

        $fixtures = new class() {
            /** @var target_user */
            public $user, $controluser;
            /** @var \block_base */
            public $block1, $block2, $block3, $block4, $block5;
        };

        $fixtures->user = new target_user($this->getDataGenerator()->create_user(['username' => 'user1']));
        $fixtures->controluser = new target_user($this->getDataGenerator()->create_user(['username' => 'controluser']));

        $fixtures->block1 = $this->create_block_instance($fixtures->user, 'User1 Block1', 'User1 test block 1');
        $fixtures->block2 = $this->create_block_instance($fixtures->user, 'User1 Block2', 'User1 test block 2');
        $fixtures->block3 = $this->create_block_instance($fixtures->controluser, 'User2 Block1', 'User2 test block 1');
        $fixtures->block4 = $this->create_block_instance($fixtures->controluser, 'User2 Block2', 'User2 test block 2');
        $fixtures->block5 = $this->create_block_instance($fixtures->controluser, 'User2 Block3', 'User2 test block 3');

        return $fixtures;
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context() {
        $fixtures = $this->create_fixtures();

        $file1 = $this->create_file($fixtures->block1);
        $file2 = $this->create_file($fixtures->block1);
        $file3 = $this->create_file($fixtures->block2);
        $file4 = $this->create_file($fixtures->block3);
        $file5 = $this->create_file($fixtures->block4);

        // Purge active user.
        $result = html::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assert_has_block_count(0, $fixtures->user);

        // Check that files are purged as well.
        $fs = get_file_storage();
        $this->assertEmpty($fs->get_file_by_id($file1->get_id()));
        $this->assertEmpty($fs->get_file_by_id($file2->get_id()));
        $this->assertEmpty($fs->get_file_by_id($file3->get_id()));

        // Control user must not be affected.
        $this->assert_has_block_count(3, $fixtures->controluser);
        $this->assertNotEmpty($fs->get_file_by_id($file4->get_id()));
        $this->assertNotEmpty($fs->get_file_by_id($file5->get_id()));
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context_suspended_user() {
        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->suspend_user($fixtures->user->id));

        // Purge active user.
        $result = html::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assert_has_block_count(0, $fixtures->user);

        // Control user must not be affected.
        $this->assert_has_block_count(3, $fixtures->controluser);
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge_system_context_deleted_user() {
        $fixtures = $this->create_fixtures();
        $fixtures->user = new target_user($this->delete_user($fixtures->user->id));

        // Purge active user.
        $result = html::execute_purge($fixtures->user, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assert_has_block_count(0, $fixtures->user);

        // Control user must not be affected.
        $this->assert_has_block_count(3, $fixtures->controluser);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $fixtures = $this->create_fixtures();

        // Do the count.
        $result = html::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(2, $result);

        $result = html::execute_count($fixtures->controluser, context_system::instance());
        $this->assertEquals(3, $result);

        // Purge data.
        html::execute_purge($fixtures->user, context_system::instance());

        $result = html::execute_count($fixtures->user, context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly exported
     */
    public function test_export() {
        $fixtures = $this->create_fixtures();

        // Export data.
        $result = html::execute_export($fixtures->user, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        $this->assertCount(0, $result->files);

        $titles = array_column($result->data, 'title');
        $contents = array_column($result->data, 'content');
        $files = array_column($result->data, 'files');
        $this->assertNotEmpty($titles);
        $this->assertNotEmpty($contents);
        $this->assertNotEmpty($files);

        $this->assertContains('User1 Block1', $titles);
        $this->assertContains('User1 Block2', $titles);

        $this->assertContains(
            $fixtures->block1->get_content(),
            $contents,
            'Block1 not found in export',
            false,
            false
        );
        $this->assertContains(
            $fixtures->block2->get_content(),
            $contents,
            'Block2 not found in export',
            false,
            false
        );
    }

    /**
     * test if files are correctly exported
     */
    public function test_export_with_files() {
        $fixtures = $this->create_fixtures();

        $file1 = $this->create_file($fixtures->block1);
        $file2 = $this->create_file($fixtures->block1);
        $file3 = $this->create_file($fixtures->block2);

        // Export data.
        $result = html::execute_export($fixtures->user, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        $this->assertCount(3, $result->files);

        $files = [];
        foreach ($result->data as $data) {
            $files = array_merge($files, $data['files']);
        }
        $this->assertNotEmpty($files);

        $fileids = [];
        foreach ($result->files as $file) {
            $fileids[] = $file->get_id();
        }
        $this->assertContains($file1->get_id(), $fileids);
        $this->assertContains($file2->get_id(), $fileids);
        $this->assertContains($file3->get_id(), $fileids);

        $this->assertContains([
            'fileid' => $file1->get_id(),
            'filename' => $file1->get_filename(),
            'contenthash' => $file1->get_contenthash()
        ], $files);

        $this->assertContains([
            'fileid' => $file2->get_id(),
            'filename' => $file2->get_filename(),
            'contenthash' => $file2->get_contenthash()
        ], $files);

        $this->assertContains([
            'fileid' => $file3->get_id(),
            'filename' => $file3->get_filename(),
            'contenthash' => $file3->get_contenthash()
        ], $files);
    }

    /**
     * @param $expectedcount
     * @param target_user $user
     */
    private function assert_has_block_count($expectedcount, target_user $user) {
        global $DB;
        $count = $DB->count_records(
            'block_instances',
            ['blockname' => 'html', 'parentcontextid' => $user->contextid]
        );
        $this->assertEquals($expectedcount, $count);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function suspend_user(int $userid): \stdClass {
        global $DB;
        // Note that we don't properly delete the user, in fact we just simulate it.
        $DB->set_field('user', 'suspended', '1', ['id' => $userid]);
        return $DB->get_record('user', ['id' => $userid]);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function delete_user(int $userid): \stdClass {
        global $DB;
        // Note that we don't properly delete the user, in fact we just simulate it.
        $DB->set_field('user', 'deleted', '1', ['id' => $userid]);
        context_helper::delete_instance(CONTEXT_USER, $userid);
        return $DB->get_record('user', ['id' => $userid]);
    }

    /**
     * Create a block instance
     *
     * @param target_user $user
     * @param string $title
     * @param string $content
     * @return block_base
     */
    protected function create_block_instance(target_user $user, string $title, string $content): block_base {
        global $DB;

        $config = (object) [
            'title' => $title,
            'text' => $content,
            'format' => FORMAT_HTML
        ];

        $page = new moodle_page();
        $page->set_context(context_user::instance($user->id));
        $page->blocks->get_regions();
        $page->blocks->add_block('html', BLOCK_POS_LEFT, 0, false, '*', null);

        // Load last inserted block.
        $sql = "SELECT * FROM {block_instances} WHERE blockname = 'html' ORDER BY id DESC";
        $block = $DB->get_record_sql($sql, [], IGNORE_MULTIPLE);
        // Store config data.
        $configdata = base64_encode(serialize($config));

        $common_config = json_encode([
            'title' => $config->title,
            'override_title' => true,
        ]);

        $DB->update_record('block_instances', (object) [
            'id' => $block->id,
            'configdata' => $configdata,
            'common_config' => $common_config,
        ]);

        $block->configdata = $configdata;
        $block->common_config = $common_config;

        return block_instance('html', $block, $page);
    }

    /**
     * @param block_base $block1
     * @return stored_file
     */
    private function create_file(block_base $block1): stored_file {
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $block1->context->id,
            'component' => 'block_html',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => random_string(20)
        ];
        $file = $fs->create_file_from_string($filerecord, random_string(20));
        return $fs->get_file_by_id($file->get_id());
    }
}
