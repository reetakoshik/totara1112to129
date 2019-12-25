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

use totara_form\form\element\filepicker,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\filepicker class.
 */
class totara_form_element_filepicker_testcase extends advanced_testcase {
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
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));

                // Test the form field values.
                $testcase->assertNotEquals(666, $filepicker1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array('somefilepicker1' => 666);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);
    }

    public function test_submission() {
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
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertCount(1, $files->somefilepicker1);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);

        // Make sure second file is ignored.
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'zzzz.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertCount(1, $files->somefilepicker1);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);
    }

    public function test_required() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
                $filepicker1->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);

        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);
    }

    public function test_frozen() {
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
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
                $filepicker1->set_frozen(true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertSame(array(), (array)$data);
        $this->assertSame(array('somefilepicker1' => array()), (array)$files);
    }

    public function test_maxbytes() {
        global $USER;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
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
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1', array('maxbytes' => 7)));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1', array('maxbytes' => 6)));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1', array('maxbytes' => 5)));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1', array('maxbytes' => 4)));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);
    }

    public function test_accept() {
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
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1', array('accept' => '*')));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1', array('accept' => array('text/html', 'text/plain'))));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filepicker $filepicker1 */
                $filepicker1 = $model->add(new filepicker('somefilepicker1', 'Some filepicker 1', array('accept' => array('text/html', 'text/xml', 'video'))));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        //$this->assertNull($data);
        $this->assertNull($files);
    }
}
