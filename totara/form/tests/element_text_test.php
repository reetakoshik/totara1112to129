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

use totara_form\form\element\text,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\text class.
 */
class totara_form_element_text_testcase extends advanced_testcase {
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
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text1 */
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                /** @var text $text2 */
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_RAW));
                $text2->set_frozen(true);
                /** @var text $text3 */
                $text3 = $model->add(new text('sometext3', 'Some text 3', PARAM_RAW));
                $text3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('Current text 1', $text1->get_field_value());
                $testcase->assertSame('Current text 2', $text2->get_field_value());
                $testcase->assertSame('', $text3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);

        $currentdata = array('sometext1' => 'Current text 1', 'sometext2' => 'Current text 2');
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text1 */
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                /** @var text $text2 */
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_RAW));
                /** @var text $text3 */
                $text3 = $model->add(new text('sometext3', 'Some text 3', PARAM_RAW));
                $text3->set_frozen(true);
                /** @var text $text4 */
                $text4 = $model->add(new text('sometext4', 'Some text 4', PARAM_RAW));
                $text4->set_frozen(true);
                /** @var text $text5 */
                $text5 = $model->add(new text('sometext5', 'Some text 5', PARAM_RAW));
                $text5->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('Entered text 1', $text1->get_field_value());
                $testcase->assertSame('Current text 2', $text2->get_field_value());
                $testcase->assertSame('Current text 3', $text3->get_field_value());
                $testcase->assertSame('Current text 4', $text4->get_field_value());
                $testcase->assertSame('', $text5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'sometext1' => 'Entered text 1',
            'sometext2' => array('yyyy'),
            'sometext3' => 'Some text 3',
            'sometext4' => 'xxxx',
            'sometext5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'sometext1' => 'Current text 1',
            'sometext2' => 'Current text 2',
            'sometext3' => 'Current text 3',
            'sometext4' => 'Current text 4',
        );
        $expected = array(
            'sometext1' => 'Entered text 1',
            'sometext2' => 'Current text 2',
            'sometext3' => 'Current text 3',
            'sometext4' => 'Current text 4',
            'sometext5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);
    }

    public function test_param_type() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text1 */
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_ALPHANUM));
                /** @var text $text2 */
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_ALPHANUM));
                /** @var text $text3 */
                $text3 = $model->add(new text('sometext3', 'Some text 3', PARAM_ALPHANUM));
                $text3->set_frozen(true);
                /** @var text $text4 */
                $text4 = $model->add(new text('sometext4', 'Some text 4', PARAM_ALPHANUM));
                $text4->set_frozen(true);
                /** @var text $text5 */
                $text5 = $model->add(new text('sometext5', 'Some text 5', PARAM_ALPHANUM));
                $text5->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('Enteredtext1', $text1->get_field_value());
                $testcase->assertSame('Currenttext2', $text2->get_field_value());
                $testcase->assertSame('Currenttext3', $text3->get_field_value());
                $testcase->assertSame('Currenttext4', $text4->get_field_value());
                $testcase->assertSame('', $text5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'sometext1' => 'Entered text 1',
            'sometext2' => array('yyyy'),
            'sometext3' => 'Some text 3',
            'sometext4' => 'xxxx',
            'sometext5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'sometext1' => 'Current text 1',
            'sometext2' => 'Current text 2',
            'sometext3' => 'Current text 3',
            'sometext4' => 'Current text 4',
        );
        $expected = array(
            'sometext1' => 'Enteredtext1',
            'sometext2' => 'Currenttext2',
            'sometext3' => 'Currenttext3',
            'sometext4' => 'Currenttext4',
            'sometext5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        // Make sure '' value is not cleaned.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text1 */
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_INT));
                /** @var text $text2 */
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_INT));
                /** @var text $text3 */
                $text3 = $model->add(new text('sometext3', 'Some text 3', PARAM_INT));
                $text3->set_frozen(true);
                /** @var text $text4 */
                $text4 = $model->add(new text('sometext4', 'Some text 4', PARAM_INT));
                $text4->set_frozen(true);
                /** @var text $text5 */
                $text5 = $model->add(new text('sometext5', 'Some text 5', PARAM_INT));
                $text5->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('1', $text1->get_field_value());
                $testcase->assertSame('', $text2->get_field_value());
                $testcase->assertSame('30', $text3->get_field_value());
                $testcase->assertSame('40', $text4->get_field_value());
                $testcase->assertSame('', $text5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'sometext1' => '1 xxfdsfd s',
            'sometext2' => '',
            'sometext3' => '3 xx x',
            'sometext4' => 'xxxx',
            'sometext5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'sometext1' => '',
            'sometext2' => '20 cxcx ',
            'sometext3' => '30 cxcx ',
            'sometext4' => '40 cxc',
        );
        $expected = array(
            'sometext1' => 1,
            'sometext2' => '',
            'sometext3' => 30,
            'sometext4' => 40,
            'sometext5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        // Make sure the PARAM_TYPE is required.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(@new text('sometext', 'Some text'));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array('sometext' => 'Some text 1');
        test_form::phpunit_set_post_data($postdata);

        try {
            new test_form();
            $this->fail('Coding exception expected when type not specified');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: $paramtype parameter must be specified', $e->getMessage());
        } catch (Error $e) {
            // PHP 7.1 requires all arguments.
            $this->assertInstanceOf('ArgumentCountError', $e);
        }

        // Warn developers if there are too many parameters in constructor.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertDebuggingNotCalled();
                $model->add(new text('sometext', 'Some text', PARAM_RAW, 'xxx'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text */
                $text = $model->add(new text('sometext', 'Some text', PARAM_RAW));
                $text->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometext' => 'Some text 1');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometext'], $data->sometext);

        $postdata = array('sometext' => '0');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometext'], $data->sometext);

        $postdata = array('sometext' => '');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_maxlength() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text */
                $text = $model->add(new text('sometext', 'Some text', PARAM_RAW));
                $text->set_attribute('maxlength', null);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometext' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometext'], $data->sometext);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text */
                $text = $model->add(new text('sometext', 'Some text', PARAM_RAW));
                $text->set_attribute('maxlength', 11);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometext' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometext'], $data->sometext);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text */
                $text = $model->add(new text('sometext', 'Some text', PARAM_RAW));
                $text->set_attribute('maxlength', 10);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometext' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometext'], $data->sometext);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text */
                $text = $model->add(new text('sometext', 'Some text', PARAM_RAW));
                $text->set_attribute('maxlength', 9);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometext' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
