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

use totara_form\form\element\number,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\number class.
 */
class totara_form_element_number_testcase extends advanced_testcase {
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
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                /** @var \totara_form\form\element\number $number2 */
                $number2 = $model->add(new number('somenumber2', 'Some number 3'));
                /** @var \totara_form\form\element\number $number3 */
                $number3 = $model->add(new number('somenumber3', 'Some number 3'));
                $number3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('', $number1->get_field_value());
                $testcase->assertSame('22', $number2->get_field_value());
                $testcase->assertSame('33', $number3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somenumber2' => '22',
            'somenumber3' => '33',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                /** @var \totara_form\form\element\number $number2 */
                $number2 = $model->add(new number('somenumber2', 'Some number 3'));
                /** @var \totara_form\form\element\number $number3 */
                $number3 = $model->add(new number('somenumber3', 'Some number 3'));
                $number3->set_frozen(true);
                /** @var \totara_form\form\element\number $number4 */
                $number4 = $model->add(new number('somenumber4', 'Some number 4'));
                $number4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('111', $number1->get_field_value());
                $testcase->assertSame('22', $number2->get_field_value());
                $testcase->assertSame('33', $number3->get_field_value());
                $testcase->assertSame('', $number4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '111',
            'somenumber3' => '333',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somenumber2' => '22',
            'somenumber3' => '33',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somenumber1' => '111',
            'somenumber2' => '22',
            'somenumber3' => '33',
            'somenumber4' => null,
        );
        $this->assertSame($expected, $data);
    }

    public function test_validation_number() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                $number1->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('11', $number1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => 'cxcxcxcx',
        );
        $currentdata = array(
            'somenumber1' => '11',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somenumber1' => '11',
        );
        $this->assertSame($expected, $data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));

                // Test the form field values.
                $testcase->assertSame('11.11', $number1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '11.11',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_validation_max() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                $number1->set_attribute('max', 10);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '11',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somenumber1' => '10',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '10'), $data);

        $postdata = array(
            'somenumber1' => '9',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '9'), $data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                $number1->set_attribute('max', -10);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '9',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somenumber1' => '-10',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '-10'), $data);

        $postdata = array(
            'somenumber1' => '-11',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '-11'), $data);
    }

    public function test_validation_min() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                $number1->set_attribute('min', 10);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '9',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somenumber1' => '10',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '10'), $data);

        $postdata = array(
            'somenumber1' => '11',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '11'), $data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                $number1->set_attribute('min', -10);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '-11',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somenumber1' => '-10',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '-10'), $data);

        $postdata = array(
            'somenumber1' => '10',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '10'), $data);
    }

    public function test_validation_step() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                $number1->set_attribute('min', 2);
                $number1->set_attribute('step', 3);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somenumber1' => '1',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somenumber1' => '3',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'somenumber1' => '2',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '2'), $data);

        $postdata = array(
            'somenumber1' => '5',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '5'), $data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var \totara_form\form\element\number $number1 */
                $number1 = $model->add(new number('somenumber1', 'Some number 1'));
                $number1->set_attribute('min', -2);
                $number1->set_attribute('step', 3);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somenumber1' => '1',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '1'), $data);

        $postdata = array(
            'somenumber1' => '-2',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('somenumber1' => '-2'), $data);

        $postdata = array(
            'somenumber1' => '-5',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
