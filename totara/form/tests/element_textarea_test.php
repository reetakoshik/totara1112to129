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

use totara_form\form\element\textarea,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\textarea class.
 */
class totara_form_element_textarea_testcase extends advanced_testcase {
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
                /** @var textarea $textarea1 */
                $textarea1 = $model->add(new textarea('sometextarea1', 'Some textarea 1', PARAM_RAW));
                /** @var textarea $textarea2 */
                $textarea2 = $model->add(new textarea('sometextarea2', 'Some textarea 2', PARAM_RAW));
                $textarea2->set_frozen(true);
                /** @var textarea $textarea3 */
                $textarea3 = $model->add(new textarea('sometextarea3', 'Some textarea 3', PARAM_RAW));
                $textarea3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('Current textarea 1', $textarea1->get_field_value());
                $testcase->assertSame('Current textarea 2', $textarea2->get_field_value());
                $testcase->assertSame('', $textarea3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);

        $currentdata = array('sometextarea1' => 'Current textarea 1', 'sometextarea2' => 'Current textarea 2');
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea1 */
                $textarea1 = $model->add(new textarea('sometextarea1', 'Some textarea 1', PARAM_RAW));
                /** @var textarea $textarea2 */
                $textarea2 = $model->add(new textarea('sometextarea2', 'Some textarea 2', PARAM_RAW));
                /** @var textarea $textarea3 */
                $textarea3 = $model->add(new textarea('sometextarea3', 'Some textarea 3', PARAM_RAW));
                $textarea3->set_frozen(true);
                /** @var textarea $textarea4 */
                $textarea4 = $model->add(new textarea('sometextarea4', 'Some textarea 4', PARAM_RAW));
                $textarea4->set_frozen(true);
                /** @var textarea $textarea5 */
                $textarea5 = $model->add(new textarea('sometextarea5', 'Some textarea 5', PARAM_RAW));
                $textarea5->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('Entered textarea 1', $textarea1->get_field_value());
                $testcase->assertSame('Current textarea 2', $textarea2->get_field_value());
                $testcase->assertSame('Current textarea 3', $textarea3->get_field_value());
                $testcase->assertSame('Current textarea 4', $textarea4->get_field_value());
                $testcase->assertSame('', $textarea5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'sometextarea1' => 'Entered textarea 1',
            'sometextarea2' => array('yyyy'),
            'sometextarea3' => 'Some textarea 3',
            'sometextarea4' => 'xxxx',
            'sometextarea5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'sometextarea1' => 'Current textarea 1',
            'sometextarea2' => 'Current textarea 2',
            'sometextarea3' => 'Current textarea 3',
            'sometextarea4' => 'Current textarea 4',
        );
        $expected = array(
            'sometextarea1' => 'Entered textarea 1',
            'sometextarea2' => 'Current textarea 2',
            'sometextarea3' => 'Current textarea 3',
            'sometextarea4' => 'Current textarea 4',
            'sometextarea5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);
    }

    public function test_param_type() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea1 */
                $textarea1 = $model->add(new textarea('sometextarea1', 'Some textarea 1', PARAM_ALPHANUM));
                /** @var textarea $textarea2 */
                $textarea2 = $model->add(new textarea('sometextarea2', 'Some textarea 2', PARAM_ALPHANUM));
                /** @var textarea $textarea3 */
                $textarea3 = $model->add(new textarea('sometextarea3', 'Some textarea 3', PARAM_ALPHANUM));
                $textarea3->set_frozen(true);
                /** @var textarea $textarea4 */
                $textarea4 = $model->add(new textarea('sometextarea4', 'Some textarea 4', PARAM_ALPHANUM));
                $textarea4->set_frozen(true);
                /** @var textarea $textarea5 */
                $textarea5 = $model->add(new textarea('sometextarea5', 'Some textarea 5', PARAM_ALPHANUM));
                $textarea5->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('Enteredtextarea1', $textarea1->get_field_value());
                $testcase->assertSame('Currenttextarea2', $textarea2->get_field_value());
                $testcase->assertSame('Currenttextarea3', $textarea3->get_field_value());
                $testcase->assertSame('Currenttextarea4', $textarea4->get_field_value());
                $testcase->assertSame('', $textarea5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'sometextarea1' => 'Entered textarea 1',
            'sometextarea2' => array('yyyy'),
            'sometextarea3' => 'Some textarea 3',
            'sometextarea4' => 'xxxx',
            'sometextarea5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'sometextarea1' => 'Current textarea 1',
            'sometextarea2' => 'Current textarea 2',
            'sometextarea3' => 'Current textarea 3',
            'sometextarea4' => 'Current textarea 4',
        );
        $expected = array(
            'sometextarea1' => 'Enteredtextarea1',
            'sometextarea2' => 'Currenttextarea2',
            'sometextarea3' => 'Currenttextarea3',
            'sometextarea4' => 'Currenttextarea4',
            'sometextarea5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        // Make sure '' value is not cleaned.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea1 */
                $textarea1 = $model->add(new textarea('sometextarea1', 'Some textarea 1', PARAM_INT));
                /** @var textarea $textarea2 */
                $textarea2 = $model->add(new textarea('sometextarea2', 'Some textarea 2', PARAM_INT));
                /** @var textarea $textarea3 */
                $textarea3 = $model->add(new textarea('sometextarea3', 'Some textarea 3', PARAM_INT));
                $textarea3->set_frozen(true);
                /** @var textarea $textarea4 */
                $textarea4 = $model->add(new textarea('sometextarea4', 'Some textarea 4', PARAM_INT));
                $textarea4->set_frozen(true);
                /** @var textarea $textarea5 */
                $textarea5 = $model->add(new textarea('sometextarea5', 'Some textarea 5', PARAM_INT));
                $textarea5->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('1', $textarea1->get_field_value());
                $testcase->assertSame('', $textarea2->get_field_value());
                $testcase->assertSame('30', $textarea3->get_field_value());
                $testcase->assertSame('40', $textarea4->get_field_value());
                $testcase->assertSame('', $textarea5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'sometextarea1' => '1 xxfdsfd s',
            'sometextarea2' => '',
            'sometextarea3' => '3 xx x',
            'sometextarea4' => 'xxxx',
            'sometextarea5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'sometextarea1' => '',
            'sometextarea2' => '20 cxcx ',
            'sometextarea3' => '30 cxcx ',
            'sometextarea4' => '40 cxc',
        );
        $expected = array(
            'sometextarea1' => 1,
            'sometextarea2' => '',
            'sometextarea3' => 30,
            'sometextarea4' => 40,
            'sometextarea5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        // Make sure the PARAM_TYPE is required.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(@new textarea('sometextarea', 'Some textarea'));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array('sometextarea' => 'Some textarea 1');
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
                $model->add(new textarea('sometextarea', 'Some textarea', PARAM_RAW, 'xxx'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea */
                $textarea = $model->add(new textarea('sometextarea', 'Some textarea', PARAM_RAW));
                $textarea->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometextarea' => 'Some textarea 1');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometextarea'], $data->sometextarea);

        $postdata = array('sometextarea' => '0');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometextarea'], $data->sometextarea);

        $postdata = array('sometextarea' => '');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_maxlength() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea */
                $textarea = $model->add(new textarea('sometextarea', 'Some textarea', PARAM_RAW));
                $textarea->set_attribute('maxlength', null);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometextarea' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometextarea'], $data->sometextarea);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea */
                $textarea = $model->add(new textarea('sometextarea', 'Some textarea', PARAM_RAW));
                $textarea->set_attribute('maxlength', 11);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometextarea' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometextarea'], $data->sometextarea);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea */
                $textarea = $model->add(new textarea('sometextarea', 'Some textarea', PARAM_RAW));
                $textarea->set_attribute('maxlength', 10);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometextarea' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertSame($postdata['sometextarea'], $data->sometextarea);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var textarea $textarea */
                $textarea = $model->add(new textarea('sometextarea', 'Some textarea', PARAM_RAW));
                $textarea->set_attribute('maxlength', 9);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('sometextarea' => '1234567890');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
