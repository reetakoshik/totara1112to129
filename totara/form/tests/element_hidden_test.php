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

use totara_form\form\element\hidden,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\hidden class.
 */
class totara_form_element_hidden_testcase extends advanced_testcase {
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
                /** @var hidden $hidden1 */
                $hidden1 = $model->add(new hidden('somehidden1', PARAM_RAW));
                /** @var hidden $hidden2 */
                $hidden2 = $model->add(new hidden('somehidden2', PARAM_RAW));
                /** @var hidden $hidden3 */
                $hidden3 = $model->add(new hidden('somehidden3', PARAM_RAW));

                // Test the form field values.
                $testcase->assertSame('Current hidden 1', $hidden1->get_field_value());
                $testcase->assertSame('', $hidden2->get_field_value());
                $testcase->assertSame('', $hidden3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);

        $currentdata = array('somehidden1' => 'Current hidden 1', 'somehidden2' => array('Current hidden 2'));
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var hidden $hidden1 */
                $hidden1 = $model->add(new hidden('somehidden1', PARAM_RAW));
                /** @var hidden $hidden2 */
                $hidden2 = $model->add(new hidden('somehidden2', PARAM_RAW));
                /** @var hidden $hidden3 */
                $hidden3 = $model->add(new hidden('somehidden3', PARAM_RAW));
                /** @var hidden $hidden4 */
                $hidden4 = $model->add(new hidden('somehidden4', PARAM_RAW));
                /** @var hidden $hidden5 */
                $hidden5 = $model->add(new hidden('somehidden5', PARAM_RAW));

                // Test the form field values.
                $testcase->assertSame('Current hidden 1', $hidden1->get_field_value());
                $testcase->assertSame('Current hidden 2', $hidden2->get_field_value());
                $testcase->assertSame('Current hidden 3', $hidden3->get_field_value());
                $testcase->assertSame('Current hidden 4', $hidden4->get_field_value());
                $testcase->assertSame('', $hidden5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somehidden1' => 'Entered hidden 1',
            'somehidden2' => array('yyyy'),
            'somehidden3' => 'Some hidden 3',
            'somehidden4' => 'xxxx',
            'somehidden5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somehidden1' => 'Current hidden 1',
            'somehidden2' => 'Current hidden 2',
            'somehidden3' => 'Current hidden 3',
            'somehidden4' => 'Current hidden 4',
        );
        $expected = array(
            'somehidden1' => 'Current hidden 1',
            'somehidden2' => 'Current hidden 2',
            'somehidden3' => 'Current hidden 3',
            'somehidden4' => 'Current hidden 4',
            'somehidden5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);
    }

    public function test_param_type() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var hidden $hidden1 */
                $hidden1 = $model->add(new hidden('somehidden1', PARAM_ALPHANUM));
                /** @var hidden $hidden2 */
                $hidden2 = $model->add(new hidden('somehidden2', PARAM_ALPHANUM));
                /** @var hidden $hidden3 */
                $hidden3 = $model->add(new hidden('somehidden3', PARAM_ALPHANUM));
                /** @var hidden $hidden4 */
                $hidden4 = $model->add(new hidden('somehidden4', PARAM_ALPHANUM));
                /** @var hidden $hidden5 */
                $hidden5 = $model->add(new hidden('somehidden5', PARAM_ALPHANUM));

                // Test the form field values.
                $testcase->assertSame('Currenthidden1', $hidden1->get_field_value());
                $testcase->assertSame('Currenthidden2', $hidden2->get_field_value());
                $testcase->assertSame('Currenthidden3', $hidden3->get_field_value());
                $testcase->assertSame('Currenthidden4', $hidden4->get_field_value());
                $testcase->assertSame('', $hidden5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somehidden1' => 'Entered hidden 1',
            'somehidden2' => array('yyyy'),
            'somehidden3' => 'Some hidden 3',
            'somehidden4' => 'xxxx',
            'somehidden5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somehidden1' => 'Current hidden 1',
            'somehidden2' => 'Current hidden 2',
            'somehidden3' => 'Current hidden 3',
            'somehidden4' => 'Current hidden 4',
        );
        $expected = array(
            'somehidden1' => 'Currenthidden1',
            'somehidden2' => 'Currenthidden2',
            'somehidden3' => 'Currenthidden3',
            'somehidden4' => 'Currenthidden4',
            'somehidden5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        // Make sure '' value is not cleaned.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var hidden $hidden1 */
                $hidden1 = $model->add(new hidden('somehidden1', PARAM_INT));
                /** @var hidden $hidden2 */
                $hidden2 = $model->add(new hidden('somehidden2', PARAM_INT));
                /** @var hidden $hidden3 */
                $hidden3 = $model->add(new hidden('somehidden3', PARAM_INT));
                $hidden3->set_frozen(true);
                /** @var hidden $hidden4 */
                $hidden4 = $model->add(new hidden('somehidden4', PARAM_INT));
                $hidden4->set_frozen(true);
                /** @var hidden $hidden5 */
                $hidden5 = $model->add(new hidden('somehidden5', PARAM_INT));
                $hidden5->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('', $hidden1->get_field_value());
                $testcase->assertSame('20', $hidden2->get_field_value());
                $testcase->assertSame('30', $hidden3->get_field_value());
                $testcase->assertSame('40', $hidden4->get_field_value());
                $testcase->assertSame('', $hidden5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somehidden1' => '1 xxfdsfd s',
            'somehidden2' => '',
            'somehidden3' => '3 xx x',
            'somehidden4' => 'xxxx',
            'somehidden5' => 'zzzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somehidden1' => '',
            'somehidden2' => '20 cxcx ',
            'somehidden3' => '30 cxcx ',
            'somehidden4' => '40 cxc',
        );
        $expected = array(
            'somehidden1' => '',
            'somehidden2' => 20,
            'somehidden3' => 30,
            'somehidden4' => 40,
            'somehidden5' => null,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        // Make sure the PARAM_TYPE is required.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(@new hidden('somehidden'));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array('somehidden' => 'Some hidden 1');
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
                $model->add(new hidden('somehidden', PARAM_RAW, 'xxx'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }
}
