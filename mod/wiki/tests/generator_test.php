<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod_wiki generator tests
 *
 * @package    mod_wiki
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Genarator tests class for mod_wiki.
 *
 * @package    mod_wiki
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wiki_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('wiki', array('course' => $course->id)));
        $wiki = $this->getDataGenerator()->create_module('wiki', array('course' => $course));
        $records = $DB->get_records('wiki', array('course' => $course->id), 'id');
        $this->assertEquals(1, count($records));
        $this->assertTrue(array_key_exists($wiki->id, $records));

        $params = array('course' => $course->id, 'name' => 'Another wiki');
        $wiki = $this->getDataGenerator()->create_module('wiki', $params);
        $records = $DB->get_records('wiki', array('course' => $course->id), 'id');
        $this->assertEquals(2, count($records));
        $this->assertEquals('Another wiki', $records[$wiki->id]->name);
    }

    public function test_create_content() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $wiki = $this->getDataGenerator()->create_module('wiki', array('course' => $course));
        $wikigenerator = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        $page1 = $wikigenerator->create_first_page($wiki);
        $page2 = $wikigenerator->create_content($wiki);
        $page3 = $wikigenerator->create_content($wiki, array('title' => 'Custom title', 'tags' => array('Cats', 'mice')));
        unset($wiki->cmid);
        $page4 = $wikigenerator->create_content($wiki, array('tags' => 'Cats, dogs'));
        $subwikis = $DB->get_records('wiki_subwikis', array('wikiid' => $wiki->id), 'id');
        $this->assertEquals(1, count($subwikis));
        $subwikiid = key($subwikis);
        $records = $DB->get_records('wiki_pages', array('subwikiid' => $subwikiid), 'id');
        $this->assertEquals(4, count($records));
        $this->assertEquals($page1->id, $records[$page1->id]->id);
        $this->assertEquals($page2->id, $records[$page2->id]->id);
        $this->assertEquals($page3->id, $records[$page3->id]->id);
        $this->assertEquals('Custom title', $records[$page3->id]->title);
        $this->assertEquals(array('Cats', 'mice'),
                array_values(core_tag_tag::get_item_tags_array('mod_wiki', 'wiki_pages', $page3->id)));
        $this->assertEquals(array('Cats', 'dogs'),
                array_values(core_tag_tag::get_item_tags_array('mod_wiki', 'wiki_pages', $page4->id)));
    }

    public function test_create_content_individual() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $wiki = $this->getDataGenerator()->create_module('wiki',
                array('course' => $course, 'wikimode' => 'individual'));
        $wikigenerator = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        $page1 = $wikigenerator->create_first_page($wiki);
        $page2 = $wikigenerator->create_content($wiki);
        $page3 = $wikigenerator->create_content($wiki, array('title' => 'Custom title for admin'));
        $subwikis = $DB->get_records('wiki_subwikis', array('wikiid' => $wiki->id), 'id');
        $this->assertEquals(1, count($subwikis));
        $subwikiid = key($subwikis);
        $records = $DB->get_records('wiki_pages', array('subwikiid' => $subwikiid), 'id');
        $this->assertEquals(3, count($records));
        $this->assertEquals($page1->id, $records[$page1->id]->id);
        $this->assertEquals($page2->id, $records[$page2->id]->id);
        $this->assertEquals($page3->id, $records[$page3->id]->id);
        $this->assertEquals('Custom title for admin', $records[$page3->id]->title);

        $user = $this->getDataGenerator()->create_user();
        $role = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);
        $this->setUser($user);

        $page1s = $wikigenerator->create_first_page($wiki);
        $page2s = $wikigenerator->create_content($wiki);
        $page3s = $wikigenerator->create_content($wiki, array('title' => 'Custom title for student'));
        $subwikis = $DB->get_records('wiki_subwikis', array('wikiid' => $wiki->id), 'id');
        $this->assertEquals(2, count($subwikis));
        next($subwikis);
        $subwikiid = key($subwikis);
        $records = $DB->get_records('wiki_pages', array('subwikiid' => $subwikiid), 'id');
        $this->assertEquals(3, count($records));
        $this->assertEquals($page1s->id, $records[$page1s->id]->id);
        $this->assertEquals($page2s->id, $records[$page2s->id]->id);
        $this->assertEquals($page3s->id, $records[$page3s->id]->id);
        $this->assertEquals('Custom title for student', $records[$page3s->id]->title);
    }

    /**
     * @test It updates page with a given content.
     */
    public function test_update_page() {
        global $DB;

        /** @var \mod_wiki_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        [$wiki, $page, $user] = $this->create_wiki();

        $editor = $this->getDataGenerator()->create_user();
        $version = $gen->update_page($page->id, 'New content', $editor);

        $page = $DB->get_record('wiki_pages', ['id' => $version->pageid]);

        $this->setUser($editor);

        $this->assertEquals($editor->id, $version->userid);
        $this->assertEquals(2, $version->version);
        $this->assertEquals('New content', $version->content);
        $this->assertEquals("New content\n", $page->cachedcontent);
    }

    /**
     * @test It creates a wiki page synonym.
     */
    public function test_create_page_synonym() {
        global $DB;

        /** @var \mod_wiki_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        [$wiki, $page, $user] = $this->create_wiki();

        $synonym = $gen->create_page_synonym($page->id, 'New synonym');

        $synonym = $DB->get_record('wiki_synonyms', ['id' => $synonym->id], '*', MUST_EXIST);

        $this->assertEquals('New synonym', $synonym->pagesynonym);
        $this->assertEquals($page->subwikiid, $synonym->subwikiid);
        $this->assertEquals($page->id, $synonym->pageid);
    }

    /**
     * @test It locks a wiki page.
     */
    public function test_lock_page() {
        global $DB;

        /** @var \mod_wiki_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        [$wiki, $page, $user] = $this->create_wiki();

        $locker = $this->getDataGenerator()->create_user();

        $lock = $gen->lock_page($page->id, $locker->id, $time = time() - 10, ['sectionname' => 'Test']);

        $lock = $DB->get_record('wiki_locks', ['id' => $lock->id], '*', MUST_EXIST);

        $this->assertEquals($page->id, $lock->pageid);
        $this->assertEquals('Test', $lock->sectionname);
        $this->assertEquals($locker->id, $lock->userid);
        $this->assertEquals($time, $lock->lockedat);
    }

    /**
     * @test It posts a comment to a wiki page.
     */
    public function test_post_comment() {
        global $DB;

        /** @var \mod_wiki_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        [$wiki, $page, $user] = $this->create_wiki();

        $module = get_coursemodule_from_instance('wiki', $wiki->id);
        $context = context_module::instance($module->id);

        $commenter = $this->getDataGenerator()->create_user();
        $comment = $gen->post_comment($page->id, 'Comment', $commenter, ['timecreated' => $time = time() - 10]);
        $comment = $DB->get_record('comments', ['id' => $comment->id]);

        $this->assertEquals($context->id, $comment->contextid);
        $this->assertEquals('mod_wiki', $comment->component);
        $this->assertEquals('wiki_page', $comment->commentarea);
        $this->assertEquals($commenter->id, $comment->userid);
        $this->assertEquals($page->id, $comment->itemid);
        $this->assertEquals('Comment', $comment->content);
        $this->assertEquals($time, $comment->timecreated);
    }

    /**
     * @test It adds file to a wiki.
     */
    public function test_add_file() {
        /** @var \mod_wiki_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        [$wiki, $page, $user] = $this->create_wiki();

        $uploader = $this->getDataGenerator()->create_user();

        $module = get_coursemodule_from_instance('wiki', $wiki->id);
        $context = context_module::instance($module->id);

        $this->setUser($uploader);
        $file = $gen->add_file($page->subwikiid);

        /** @var \stored_file[] $files */
        $files = get_file_storage()->get_area_files($context->id, 'mod_wiki', 'attachments', $page->subwikiid, 'filename', false);
        // Reset keys.
        $files = array_values($files);

        $this->assertCount(1, $files);
        $this->assertEquals($file->get_id(), $files[0]->get_id());
        $this->assertInstanceOf('stored_file', $files[0]);
        $this->assertEquals($page->subwikiid, $files[0]->get_itemid());
        $this->assertEquals($uploader->id, $files[0]->get_userid());
        $this->assertEquals('meaningful_text.txt', $files[0]->get_filename());
    }

    /**
     * Seed some dummy data to assert.
     *
     * @return array [$wiki, $page, $user]
     */
    protected function create_wiki() {
        global $DB;

        $this->resetAfterTest();

        /** @var \mod_wiki_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_wiki');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_record('role', ['shortname' => 'student'])->id);

        // Create wiki.
        $wiki = $gen->create_instance(['course' => $course]);

        global $USER;

        $currentuser = $USER;
        $this->setUser($user);

        // Create page.
        $page = $gen->create_page($wiki, [], $user->id);

        $this->setUser($currentuser);

        return [
            $wiki,
            $page,
            $user
        ];
    }
}
