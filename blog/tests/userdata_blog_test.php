<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_blog
 */

defined('MOODLE_INTERNAL') || die();

use core_blog\userdata\blog;
use totara_userdata\userdata\target_user;

/**
 * Tests the {@see \core_blog\userdata\blog} class
 *
 * @group totara_userdata
 */
class core_blog_userdata_blog_testcase extends advanced_testcase {

    /**
     * Gets the data for all the tests.
     */
    private function get_data(){
        $this->resetAfterTest();
        $data = new class() {
            /** @var target_user $activeuser */
            public $activeuser;
            /** @var target_user $deleteduser */
            public $deleteduser;
            /** @var array $activeuserblogs */
            public $activeuserblogs;
            /** @var array $deleteduserblogs */
            public $deleteduserblogs;
            /** @var context_system $systemcontext */
            public $systemcontext;
        };
        $data->systemcontext = context_system::instance();
        /** @var core_blog_generator $bloggenerator */
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('core_blog');
        $this->setAdminUser();

        $data->activeuser = new target_user($this->getDataGenerator()->create_user());
        $data->activeuserblogs[] = $bloggenerator->create_instance(['userid' => $data->activeuser->id]);
        $data->activeuserblogs[] = $bloggenerator->create_instance([
            'userid' => $data->activeuser->id,
            'publishstate' => 'draft'
        ]);

        $data->deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));
        $data->deleteduserblogs[] = $bloggenerator->create_instance(['userid' => $data->deleteduser->id]);
        $data->deleteduserblogs[] = $bloggenerator->create_instance([
            'userid' => $data->deleteduser->id,
            'publishstate' => 'draft'
        ]);
        return $data;
    }

    /**
     * Makes sure purging one users posts does not effect another users blogs.
     */
    public function test_purge_removes_only_users_blogs() {
        $data = $this->get_data();

        $result = blog::execute_purge($data->activeuser, $data->systemcontext);
        $this->assertEquals(blog::RESULT_STATUS_SUCCESS, $result);
        // Checks that all the other users blogs are still there.
        $count = blog::execute_count($data->deleteduser, $data->systemcontext);
        $this->assertEquals(count($data->deleteduserblogs), $count);
    }

    /**
     * Tests that the count of blogs is 0 after they have being purged
     */
    public function test_purge_makes_count_zero() {
        $data = $this->get_data();

        $result = blog::execute_purge($data->activeuser, $data->systemcontext);
        $this->assertEquals(blog::RESULT_STATUS_SUCCESS, $result);

        $count = blog::execute_count($data->activeuser, $data->systemcontext);
        $this->assertEquals(0, $count);

        $result = blog::execute_purge($data->deleteduser, $data->systemcontext);
        $this->assertEquals(blog::RESULT_STATUS_SUCCESS, $result);

        $count = blog::execute_count($data->deleteduser, $data->systemcontext);
        $this->assertEquals(0, $count);
    }

    /**
     * Makes sure when a blog is deleted then the comments on the blog
     * are also deleted as the delete function in the blog library
     * doesnt do this by default.
     */
    public function test_purge_removes_blogs_comments() {
        global $CFG;
        require_once($CFG->dirroot . '/comment/lib.php');

        global $DB;
        $data = $this->get_data();

        $systemcontext = context_system::instance();

        $commentareaparams = new \stdClass();
        $commentareaparams->itemid = $data->activeuserblogs[0]->id;
        $commentareaparams->component = 'blog';
        $commentareaparams->context = context_user::instance($data->activeuser->id);
        $commentareaparams->area = 'format_blog';
        (new comment($commentareaparams))->add('thhis is a test comment');

        blog::execute_purge($data->activeuser, $data->systemcontext);

        $count = $DB->count_records('comments', ['itemid' => $data->activeuserblogs[0]->id, 'component' => 'blog']);
        $this->assertEquals(0, $count);
    }

    /**
     * Tests that the files get removed when the blog is purged.
     */
    public function test_purge_removes_files() {
        $data = $this->get_data();

        /** @var core_blog_generator $bloggenerator */
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('core_blog');

        $newblog = $bloggenerator->create_instance([
            'userid' => $data->activeuser->id,
            'attachment' => true
        ]);

        $fs = get_file_storage();
        $attachmentfiledata = [
            'contextid' => SYSCONTEXTID,
            'component' => 'blog',
            'filearea' => 'attachment',
            'itemid' => $newblog->id,
            'filepath' => '/',
            'filename' => 'attach.png'
        ];
        $fs->create_file_from_string($attachmentfiledata, '');
        $contentfiledata = [
            'contextid' => SYSCONTEXTID,
            'component' => 'blog',
            'filearea' => 'post',
            'itemid' => $newblog->id,
            'filepath' => '/',
            'filename' => 'attach.png'
        ];
        $fs->create_file_from_string($contentfiledata, '');

        $result = blog::execute_purge($data->activeuser, $data->systemcontext);
        $this->assertEquals(blog::RESULT_STATUS_SUCCESS, $result);

        $attachmentfile = $fs->get_file(
            $attachmentfiledata['contextid'],
            $attachmentfiledata['component'],
            $attachmentfiledata['filearea'],
            $attachmentfiledata['itemid'],
            $attachmentfiledata['filepath'],
            $attachmentfiledata['filename']
        );
        $this->assertFalse($attachmentfile);
        $contentfile = $fs->get_file(
            $contentfiledata['contextid'],
            $contentfiledata['component'],
            $contentfiledata['filearea'],
            $contentfiledata['itemid'],
            $contentfiledata['filepath'],
            $contentfiledata['filename']
        );
        $this->assertFalse($contentfile);
    }

    /**
     * Tests that when a blog is deleted the tags are removed and no exceptions are thrown
     */
    public function test_purge_removes_tag_instances() {
        global $DB;
        $data = $this->get_data();

        $systemcontext = context_system::instance();

        core_tag_tag::set_item_tags(
            'core',
            'post',
            $data->activeuserblogs[0]->id,
            context_user::instance($data->activeuser->id),
            array('foo', 'bar')
        );

        $tagsexist = $DB->record_exists('tag_instance', ['itemid' => $data->activeuserblogs[0]->id, 'itemtype' => 'post']);
        $this->assertTrue($tagsexist);

        $DB->set_field('user', 'deleted', 1, ['id' => $data->activeuser->id]);
        context_helper::delete_instance(CONTEXT_USER, $data->activeuser->id);
        $deleteduser = new target_user($DB->get_record('user', ['id' => $data->activeuser->id]));

        $result = blog::execute_purge($deleteduser, $systemcontext);
        $this->assertEquals(blog::RESULT_STATUS_SUCCESS, $result);

        $count = blog::execute_count($deleteduser, $systemcontext);
        $this->assertEquals(0, $count);

        $tagsexist = $DB->record_exists('tag_instance', ['itemid' => $data->activeuserblogs[0]->id, 'itemtype' => 'post']);
        $this->assertFalse($tagsexist);
    }

    /**
     * Tests that the export function exports whats in the database
     * also makes sure that the count matches the number of things
     * that are being exported
     */
    public function test_export_match_db() {
        $data = $this->get_data();

        // Export activeuser.
        $export = blog::execute_export($data->activeuser, $data->systemcontext);

        $exportedbloguserids = [];
        foreach ($export->data as $exporteddata) {
            $this->assertArrayHasKey('id', $exporteddata);
            $this->assertArrayHasKey('module', $exporteddata);
            $this->assertArrayHasKey('userid', $exporteddata);
            $this->assertEquals('blog', $exporteddata['module']);
            $exportedbloguserids[] = $exporteddata['id'];
        }
        $this->assertEquals(count($data->activeuserblogs), count($export->data));
        foreach ($data->activeuserblogs as $activeuserblog) {
            $this->assertContains($activeuserblog->id, $exportedbloguserids);
        }

        // Export deleteduser.
        $export = blog::execute_export($data->deleteduser, $data->systemcontext);

        $exportedbloguserids = [];
        foreach ($export->data as $exporteddata) {
            $this->assertArrayHasKey('id', $exporteddata);
            $this->assertArrayHasKey('module', $exporteddata);
            $this->assertArrayHasKey('userid', $exporteddata);
            $this->assertEquals('blog', $exporteddata['module']);
            $exportedbloguserids[] = $exporteddata['id'];
        }
        $this->assertEquals(count($data->deleteduserblogs), count($export->data));
        foreach ($data->deleteduserblogs as $deleteduserblog) {
            $this->assertContains($deleteduserblog->id, $exportedbloguserids);
        }
    }

    /**
     * Tests the files attached to blog's are included in the export.
     */
    public function test_export_includes_attachments() {
        $data = $this->get_data();

        $fs = get_file_storage();
        $attachmentfiledata = [
            'contextid' => SYSCONTEXTID,
            'component' => 'blog',
            'filearea' => 'attachment',
            'itemid' => $data->activeuserblogs[0]->id,
            'filepath' => '/',
            'filename' => 'attach.png'
        ];
        $attachmentfile = $fs->create_file_from_string($attachmentfiledata, '');
        $contentfiledata = [
            'contextid' => SYSCONTEXTID,
            'component' => 'blog',
            'filearea' => 'post',
            'itemid' => $data->activeuserblogs[0]->id,
            'filepath' => '/',
            'filename' => 'attach.png'
        ];
        $contentfile = $fs->create_file_from_string($contentfiledata, '');

        $export = blog::execute_export($data->activeuser, $data->systemcontext);

        $this->assertContains($attachmentfile, $export->files, '', false, false);
        $this->assertContains($contentfile, $export->files, '', false, false);

        foreach ($export->data as $exportedblogentry) {
            $this->assertArrayHasKey('files', $exportedblogentry);
            $this->assertArrayHasKey('attachments', $exportedblogentry['files']);
            $this->assertArrayHasKey('post', $exportedblogentry['files']);
            if ($exportedblogentry['id'] != $data->activeuserblogs[0]->id) {
                $this->assertEmpty($exportedblogentry['files']['attachments']);
                $this->assertEmpty($exportedblogentry['files']['post']);
            } else {
                $this->assertContains(
                    [
                        'fileid' => $attachmentfile->get_id(),
                        'filename' => $attachmentfile->get_filename(),
                        'contenthash' => $attachmentfile->get_contenthash()
                    ],
                    $exportedblogentry['files']['attachments']
                );
                $this->assertContains(
                    [
                        'fileid' => $contentfile->get_id(),
                        'filename' => $contentfile->get_filename(),
                        'contenthash' => $contentfile->get_contenthash()
                    ],
                    $exportedblogentry['files']['post']
                );
            }
        }
    }

    /**
     * Tests that the count works the same for users that are deleted and active.
     */
    public function test_count_returns_expected_value() {
        $data = $this->get_data();

        $activecount = blog::execute_count($data->activeuser, $data->systemcontext);
        $this->assertEquals(count($data->activeuserblogs), $activecount);

        $deletedcount = blog::execute_count($data->deleteduser, $data->systemcontext);
        $this->assertEquals(count($data->deleteduserblogs), $deletedcount);

        // Adding more blogs also increases the counts.
        /** @var core_blog_generator $bloggenerator */
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('core_blog');

        $data->activeuserblogs[] = $bloggenerator->create_instance(['userid' => $data->activeuser->id]);

        $activecount = blog::execute_count($data->activeuser, $data->systemcontext);
        $this->assertEquals(count($data->activeuserblogs), $activecount);

        $data->deleteduserblogs[] = $bloggenerator->create_instance(['userid' => $data->deleteduser->id]);
        $deletedcount = blog::execute_count($data->deleteduser, $data->systemcontext);
        $this->assertEquals(count($data->deleteduserblogs), $deletedcount);
    }
}