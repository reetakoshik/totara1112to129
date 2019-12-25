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

use totara_form\form\element\checkboxes,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\checkboxes class.
 */
class totara_form_element_checkboxes_testcase extends advanced_testcase {
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
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                /** @var checkboxes $checkboxes2 */
                $checkboxes2 = $model->add(new checkboxes('somecheckboxes2', 'Some checkboxes 2', $options));
                /** @var checkboxes $checkboxes3 */
                $checkboxes3 = $model->add(new checkboxes('somecheckboxes3', 'Some checkboxes 3', $options));
                $checkboxes3->set_frozen(true);
                /** @var checkboxes $checkboxes4 */
                $checkboxes4 = $model->add(new checkboxes('somecheckboxes4', 'Some checkboxes 4', $options));
                $checkboxes4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('r'), $checkboxes1->get_field_value());
                $testcase->assertSame(array(), $checkboxes2->get_field_value());
                $testcase->assertSame(array('g', 'b'), $checkboxes3->get_field_value());
                $testcase->assertSame(array(), $checkboxes4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somecheckboxes1' => array('r'),
            'somecheckboxes3' => array('g', 'b'),
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                /** @var checkboxes $checkboxes2 */
                $checkboxes2 = $model->add(new checkboxes('somecheckboxes2', 'Some checkboxes 2', $options));
                /** @var checkboxes $checkboxes3 */
                $checkboxes3 = $model->add(new checkboxes('somecheckboxes3', 'Some checkboxes 3', $options));
                $checkboxes3->set_frozen(true);
                /** @var checkboxes $checkboxes4 */
                $checkboxes4 = $model->add(new checkboxes('somecheckboxes4', 'Some checkboxes 4', $options));
                $checkboxes4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('r'), $checkboxes1->get_field_value());
                $testcase->assertSame(array('b'), $checkboxes2->get_field_value());
                $testcase->assertSame(array('g', 'b'), $checkboxes3->get_field_value());
                $testcase->assertSame(array(), $checkboxes4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes2' => array('b'),
            'somecheckboxes3' => array(),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckboxes1' => array('r'),
            'somecheckboxes3' => array('g', 'b'),
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckboxes1' => array('r'),
            'somecheckboxes2' => array('b'),
            'somecheckboxes3' => array('g', 'b'),
            'somecheckboxes4' => null,
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));

                // Test the form field values.
                $testcase->assertSame(array('x'), $checkboxes1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array('x'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                $checkboxes1->set_attribute('required', true);

                // Test the form field values.
                $testcase->assertSame(array(), $checkboxes1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array(),
        );
        $currentdata = array(
            'somecheckboxes1' => array('r'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                $checkboxes1->set_attribute('required', true);
                $checkboxes1->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array(), $checkboxes1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array(),
        );
        $currentdata = array(
            'somecheckboxes1' => array(),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckboxes1' => array(),
        );
        $this->assertSame($expected, $data);
    }

    public function test_incorrect_current() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');

                $testcase->assertDebuggingNotCalled();
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                $testcase->assertDebuggingCalled();

                $testcase->assertDebuggingNotCalled();
                /** @var checkboxes $checkboxes2 */
                $checkboxes2 = $model->add(new checkboxes('somecheckboxes2', 'Some checkboxes 2', $options));
                $testcase->assertDebuggingCalled();

                $testcase->assertDebuggingNotCalled();
                /** @var checkboxes $checkboxes3 */
                $checkboxes3 = $model->add(new checkboxes('somecheckboxes3', 'Some checkboxes 3', $options));
                $checkboxes3->set_frozen(true);
                $testcase->assertDebuggingCalled();

                // Test the form field values.
                $testcase->assertSame(array('b'), $checkboxes1->get_field_value());
                $testcase->assertSame(array('g'), $checkboxes2->get_field_value());
                $testcase->assertSame(array('b'), $checkboxes3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array('b'),
            'somecheckboxes3' => array('b'),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckboxes1' => array('x', 'r'),
            'somecheckboxes2' => array('x', 'g'),
            'somecheckboxes3' => array('y', 'b'),
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckboxes1' => array('b'),
            'somecheckboxes2' => array('g'),
            'somecheckboxes3' => array('b'),
        );
        $this->assertSame($expected, $data);
    }

    public function test_current() {
        global $OUTPUT;

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('1' => 'One', '' => 'Nothing', 'a' => 'lower a', 'A' => 'UPPER A');
                $model->add(new checkboxes('somecheckboxes1', 'Some select 1', $options));
                $model->add(new checkboxes('somecheckboxes2', 'Some select 2', $options));
                $model->add(new checkboxes('somecheckboxes3', 'Some select 3', $options));
                $model->add(new checkboxes('somecheckboxes4', 'Some select 4', $options));
                $model->add(new checkboxes('somecheckboxes5', 'Some select 5', $options));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somecheckboxes1' => array(1),
            'somecheckboxes2' => array('1'),
            'somecheckboxes3' => array(''),
            'somecheckboxes4' => array('a'),
        );
        $form = new test_form($currentdata);
        $data = $form->export_for_template($OUTPUT);

        $this->assertSame('somecheckboxes1', $data['items'][0]['name']);
        $this->assertSame('One', $data['items'][0]['options'][0]['text']);
        $this->assertSame('1', $data['items'][0]['options'][0]['value']);
        $this->assertTrue($data['items'][0]['options'][0]['checked']);

        $this->assertSame('somecheckboxes2', $data['items'][1]['name']);
        $this->assertSame('One', $data['items'][1]['options'][0]['text']);
        $this->assertSame('1', $data['items'][1]['options'][0]['value']);
        $this->assertTrue($data['items'][1]['options'][0]['checked']);

        $this->assertSame('somecheckboxes3', $data['items'][2]['name']);
        $this->assertSame('Nothing', $data['items'][2]['options'][1]['text']);
        $this->assertSame('', $data['items'][2]['options'][1]['value']);
        $this->assertTrue($data['items'][2]['options'][1]['checked']);

        $this->assertSame('somecheckboxes4', $data['items'][3]['name']);
        $this->assertSame('lower a', $data['items'][3]['options'][2]['text']);
        $this->assertSame('a', $data['items'][3]['options'][2]['value']);
        $this->assertTrue($data['items'][3]['options'][2]['checked']);
        $this->assertSame('UPPER A', $data['items'][3]['options'][3]['text']);
        $this->assertSame('A', $data['items'][3]['options'][3]['value']);
        $this->assertFalse($data['items'][3]['options'][3]['checked']);

        $this->assertSame('somecheckboxes5', $data['items'][4]['name']);
        $this->assertFalse($data['items'][4]['options'][0]['checked']);
        $this->assertFalse($data['items'][4]['options'][1]['checked']);
        $this->assertFalse($data['items'][4]['options'][2]['checked']);
        $this->assertFalse($data['items'][4]['options'][3]['checked']);
    }

    public function test_extra_params() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertDebuggingNotCalled();
                $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', array('a' => 'b', 'c' => 'd'), 'xx'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $form = new test_form();
    }

    public function test_option_help() {
        global $OUTPUT;
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                $checkboxes1->add_option_help('r', 'pos_description', 'totara_core');
                /** @var checkboxes $checkboxes2 */
                $checkboxes2 = $model->add(new checkboxes('somecheckboxes2', 'Some checkboxes 2', $options));
                /** @var checkboxes $checkboxes3 */
                $checkboxes3 = $model->add(new checkboxes('somecheckboxes3', 'Some checkboxes 3', $options));
                $testcase->assertDebuggingNotCalled();
                $checkboxes1->add_option_help('illegal', 'pathtowkhtmltopdf', 'totara_core');
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        $form = new test_form();
        $html = $form->render();
        $expected = $OUTPUT->help_icon('pos_description', 'totara_core', '');
        $notexpected = $OUTPUT->help_icon('pathtowkhtmltopdf', 'totara_core', '');
        $this->assertContains($expected, $html);
        $this->assertNotContains($notexpected, $html);
    }
}
