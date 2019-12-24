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

use totara_form\form\element\checkbox,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\checkbox class.
 */
class totara_form_element_checkbox_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();
        require_once(__DIR__ . '/fixtures/test_form.php');
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
                /** @var checkbox $checkbox1 */
                $checkbox1 = $model->add(new checkbox('somecheckbox1', 'Some checkbox 1'));
                /** @var checkbox $checkbox2 */
                $checkbox2 = $model->add(new checkbox('somecheckbox2', 'Some checkbox 2', 'yes', 'no'));
                /** @var checkbox $checkbox3 */
                $checkbox3 = $model->add(new checkbox('somecheckbox3', 'Some checkbox 3'));
                $checkbox3->set_frozen(true);
                /** @var checkbox $checkbox4 */
                $checkbox4 = $model->add(new checkbox('somecheckbox4', 'Some checkbox 4'));
                $checkbox4->set_frozen(true);
                /** @var checkbox $checkbox5 */
                $checkbox5 = $model->add(new checkbox('somecheckbox5', 'Some checkbox 5'));

                // Test the form field values.
                $testcase->assertSame('0', $checkbox1->get_field_value());
                $testcase->assertSame('no', $checkbox2->get_field_value());
                $testcase->assertSame('0', $checkbox3->get_field_value());
                $testcase->assertSame('1', $checkbox4->get_field_value());
                $testcase->assertSame('0', $checkbox5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => '1',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var checkbox $checkbox1 */
                $checkbox1 = $model->add(new checkbox('somecheckbox1', 'Some checkbox 1'));
                /** @var checkbox $checkbox2 */
                $checkbox2 = $model->add(new checkbox('somecheckbox2', 'Some checkbox 2', 'yes', 'no'));
                /** @var checkbox $checkbox3 */
                $checkbox3 = $model->add(new checkbox('somecheckbox3', 'Some checkbox 3'));
                $checkbox3->set_frozen(true);
                /** @var checkbox $checkbox4 */
                $checkbox4 = $model->add(new checkbox('somecheckbox4', 'Some checkbox 4'));
                $checkbox4->set_frozen(true);
                /** @var checkbox $checkbox5 */
                $checkbox5 = $model->add(new checkbox('somecheckbox5', 'Some checkbox 5'));

                // Test the form field values.
                $testcase->assertSame('0', $checkbox1->get_field_value());
                $testcase->assertSame('no', $checkbox2->get_field_value());
                $testcase->assertSame('0', $checkbox3->get_field_value());
                $testcase->assertSame('0', $checkbox4->get_field_value());
                $testcase->assertSame('0', $checkbox5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => 'xxxxx',
            'somecheckbox5' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => null,
            'somecheckbox5' => '0',
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var checkbox $checkbox1 */
                $checkbox1 = $model->add(new checkbox('somecheckbox1', 'Some checkbox 1'));

                // Test the form field values.
                $testcase->assertSame('xxx', $checkbox1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckbox1' => 'xxx',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var checkbox $checkbox1 */
                $checkbox1 = $model->add(new checkbox('somecheckbox1', 'Some checkbox 1'));
                $checkbox1->set_attribute('required', true);
                /** @var checkbox $checkbox2 */
                $checkbox2 = $model->add(new checkbox('somecheckbox2', 'Some checkbox 2', 'yes', 'no'));
                $checkbox2->set_attribute('required', true);
                /** @var checkbox $checkbox3 */
                $checkbox3 = $model->add(new checkbox('somecheckbox3', 'Some checkbox 3'));
                $checkbox3->set_frozen(true);
                $checkbox3->set_attribute('required', true);
                /** @var checkbox $checkbox4 */
                $checkbox4 = $model->add(new checkbox('somecheckbox4', 'Some checkbox 4'));
                $checkbox4->set_frozen(true);
                $checkbox4->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckbox1' => '1',
            'somecheckbox2' => 'yes',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckbox1' => '1',
            'somecheckbox2' => 'yes',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        $this->assertSame($expected, $data);

        $postdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'yes',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somecheckbox1' => '1',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somecheckbox1' => '1',
            'somecheckbox2' => 'xx',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckbox1' => '0',
            'somecheckbox2' => 'no',
            'somecheckbox3' => '0',
            'somecheckbox4' => '0',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_bool_conversion() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var checkbox $checkbox1 */
                $checkbox1 = $model->add(new checkbox('somecheckbox1', 'Some checkbox 1'));
                /** @var checkbox $checkbox2 */
                $checkbox2 = $model->add(new checkbox('somecheckbox2', 'Some checkbox 2'));
                /** @var checkbox $checkbox3 */
                $checkbox3 = $model->add(new checkbox('somecheckbox3', 'Some checkbox 3'));
                /** @var checkbox $checkbox4 */
                $checkbox4 = $model->add(new checkbox('somecheckbox4', 'Some checkbox 4'));
                // Test the form field values.
                $testcase->assertSame('0', $checkbox1->get_field_value());
                $testcase->assertSame('1', $checkbox2->get_field_value());
                $testcase->assertSame('0', $checkbox3->get_field_value());
                $testcase->assertSame('1', $checkbox4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somecheckbox1' => false,
            'somecheckbox2' => true,
            'somecheckbox3' => false,
            'somecheckbox4' => true,
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_incorrect_values() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                try {
                    $model->add(new checkbox('somecheckbox1', 'Some checkbox 1', 'yes', 'yes'));
                    $this->fail('Exception expected when unchecked and checked values match!');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: The checked and unchecked values must be different!', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_incorrect_current() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertDebuggingNotCalled();
                $model->add(new checkbox('somecheckbox1', 'Some checkbox 1'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array());
        $currentdata = array(
            'somecheckbox1' => 'xx',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame(array('somecheckbox1' => '0'), $data);
    }

    public function test_extra_params() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertDebuggingNotCalled();
                $model->add(new checkbox('somecheckbox1', 'Some checkbox 1', '1', '0', 'xx'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $form = new test_form();
    }
}
