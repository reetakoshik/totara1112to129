<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

use totara_form\file_area,
    totara_form\form\element\filemanager,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\filemanager class.
 */
class totara_form_element_filemanager_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();
        require_once(__DIR__  . '/fixtures/test_form.php');
        test_form::phpunit_reset();
        $this->resetAfterTest();
    }

    protected function tearDown() {
        test_form::phpunit_reset();
        parent::tearDown();
    }

    public function test_no_post() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'test',
            'itemid' => 6,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $file = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filemanager $filemanager1 */
                $filemanager1 = $model->add(new filemanager('somefilemanager1', 'Some filemanager 1'));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somefilemanager1' => new file_area($usercontext, 'user', 'test', 6),
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);
    }

    public function test_submission_no_current() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filemanager $filemanager1 */
                $filemanager1 = $model->add(new filemanager('somefilemanager1', 'Some filemanager 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilemanager1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertCount(2, $files->somefilemanager1);
        $this->assertEquals('.', $files->somefilemanager1[0]->get_filename());
        $this->assertEquals($draftfile, $files->somefilemanager1[1]);
    }

    public function test_submission_current() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus2.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'haha');
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'test',
            'itemid' => 6,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $file = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filemanager $filemanager1 */
                $filemanager1 = $model->add(new filemanager('somefilemanager1', 'Some filemanager 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilemanager1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somefilemanager1' => new file_area($usercontext, 'user', 'test', 6),
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertCount(2, $files->somefilemanager1);
        $this->assertEquals('.', $files->somefilemanager1[0]->get_filename());
        $this->assertEquals($draftfile, $files->somefilemanager1[1]);

        $form->update_file_area('somefilemanager1');
        $this->assertTrue($fs->file_exists($usercontext->id, 'user', 'test', 6, '/', 'pokus2.txt'));
        $this->assertFalse($fs->file_exists($usercontext->id, 'user', 'test', 6, '/', 'pokus.txt'));
    }

    public function test_submission_current_frozen() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus2.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'haha');
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'test',
            'itemid' => 6,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $file = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filemanager $filemanager1 */
                $filemanager1 = $model->add(new filemanager('somefilemanager1', 'Some filemanager 1'));
                $filemanager1->set_frozen(true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilemanager1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somefilemanager1' => new file_area($usercontext, 'user', 'test', 6),
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertCount(2, $files->somefilemanager1);
        $this->assertEquals('.', $files->somefilemanager1[0]->get_filename());
        $this->assertEquals($file->get_filename(), $files->somefilemanager1[1]->get_filename());

        $form->update_file_area('somefilemanager1');
        $this->assertFalse($fs->file_exists($usercontext->id, 'user', 'test', 6, '/', 'pokus2.txt'));
        $this->assertTrue($fs->file_exists($usercontext->id, 'user', 'test', 6, '/', 'pokus.txt'));
    }

    public function test_validation() {
        // TODO TL-9423: test file validation.
    }
}
