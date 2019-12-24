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

use totara_form\form\element\multiselect,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\multiselect class.
 */
class totara_form_element_multiselect_testcase extends advanced_testcase {
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
                /** @var multiselect $multiselect1 */
                $multiselect1 = $model->add(new multiselect('somemultiselect1', 'Some multiselect 1', $options));
                /** @var multiselect $multiselect2 */
                $multiselect2 = $model->add(new multiselect('somemultiselect2', 'Some multiselect 2', $options));
                /** @var multiselect $multiselect3 */
                $multiselect3 = $model->add(new multiselect('somemultiselect3', 'Some multiselect 3', $options));
                $multiselect3->set_frozen(true);
                /** @var multiselect $multiselect4 */
                $multiselect4 = $model->add(new multiselect('somemultiselect4', 'Some multiselect 4', $options));
                $multiselect4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('r'), $multiselect1->get_field_value());
                $testcase->assertSame(array(), $multiselect2->get_field_value());
                $testcase->assertSame(array('g', 'b'), $multiselect3->get_field_value());
                $testcase->assertSame(array(), $multiselect4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somemultiselect1' => array('r'),
            'somemultiselect3' => array('g', 'b'),
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var multiselect $multiselect1 */
                $multiselect1 = $model->add(new multiselect('somemultiselect1', 'Some multiselect 1', $options));
                /** @var multiselect $multiselect2 */
                $multiselect2 = $model->add(new multiselect('somemultiselect2', 'Some multiselect 2', $options));
                /** @var multiselect $multiselect3 */
                $multiselect3 = $model->add(new multiselect('somemultiselect3', 'Some multiselect 3', $options));
                $multiselect3->set_frozen(true);
                /** @var multiselect $multiselect4 */
                $multiselect4 = $model->add(new multiselect('somemultiselect4', 'Some multiselect 4', $options));
                $multiselect4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('r'), $multiselect1->get_field_value());
                $testcase->assertSame(array('b'), $multiselect2->get_field_value());
                $testcase->assertSame(array('g', 'b'), $multiselect3->get_field_value());
                $testcase->assertSame(array(), $multiselect4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somemultiselect2' => array('b'),
            'somemultiselect3' => array(),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somemultiselect1' => array('r'),
            'somemultiselect3' => array('g', 'b'),
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somemultiselect1' => array('r'),
            'somemultiselect2' => array('b'),
            'somemultiselect3' => array('g', 'b'),
            'somemultiselect4' => null,
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var multiselect $multiselect1 */
                $multiselect1 = $model->add(new multiselect('somemultiselect1', 'Some multiselect 1', $options));

                // Test the form field values.
                $testcase->assertSame(array('x'), $multiselect1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somemultiselect1' => array('x'),
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
                /** @var multiselect $multiselect1 */
                $multiselect1 = $model->add(new multiselect('somemultiselect1', 'Some multiselect 1', $options));
                $multiselect1->set_attribute('required', true);

                // Test the form field values.
                $testcase->assertSame(array(), $multiselect1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somemultiselect1' => array(),
        );
        $currentdata = array(
            'somemultiselect1' => array('r'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var multiselect $multiselect1 */
                $multiselect1 = $model->add(new multiselect('somemultiselect1', 'Some multiselect 1', $options));
                $multiselect1->set_attribute('required', true);
                $multiselect1->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array(), $multiselect1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somemultiselect1' => array(),
        );
        $currentdata = array(
            'somemultiselect1' => array(),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somemultiselect1' => array(),
        );
        $this->assertSame($expected, $data);
    }

    public function test_incorrect_current() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');

                $testcase->assertDebuggingNotCalled();
                /** @var multiselect $multiselect1 */
                $multiselect1 = $model->add(new multiselect('somemultiselect1', 'Some multiselect 1', $options));
                $testcase->assertDebuggingCalled();

                $testcase->assertDebuggingNotCalled();
                /** @var multiselect $multiselect2 */
                $multiselect2 = $model->add(new multiselect('somemultiselect2', 'Some multiselect 2', $options));
                $testcase->assertDebuggingCalled();

                $testcase->assertDebuggingNotCalled();
                /** @var multiselect $multiselect3 */
                $multiselect3 = $model->add(new multiselect('somemultiselect3', 'Some multiselect 3', $options));
                $multiselect3->set_frozen(true);
                $testcase->assertDebuggingCalled();

                // Test the form field values.
                $testcase->assertSame(array('b'), $multiselect1->get_field_value());
                $testcase->assertSame(array('g'), $multiselect2->get_field_value());
                $testcase->assertSame(array('b'), $multiselect3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somemultiselect1' => array('b'),
            'somemultiselect3' => array('b'),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somemultiselect1' => array('x', 'r'),
            'somemultiselect2' => array('x', 'g'),
            'somemultiselect3' => array('y', 'b'),
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somemultiselect1' => array('b'),
            'somemultiselect2' => array('g'),
            'somemultiselect3' => array('b'),
        );
        $this->assertSame($expected, $data);
    }

    public function test_current() {
        global $OUTPUT;

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('1' => 'One', '' => 'Nothing', 'a' => 'lower a', 'A' => 'UPPER A');
                $model->add(new multiselect('somemultiselect1', 'Some select 1', $options));
                $model->add(new multiselect('somemultiselect2', 'Some select 2', $options));
                $model->add(new multiselect('somemultiselect3', 'Some select 3', $options));
                $model->add(new multiselect('somemultiselect4', 'Some select 4', $options));
                $model->add(new multiselect('somemultiselect5', 'Some select 5', $options));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somemultiselect1' => array(1),
            'somemultiselect2' => array('1'),
            'somemultiselect3' => array(''),
            'somemultiselect4' => array('a'),
        );
        $form = new test_form($currentdata);
        $data = $form->export_for_template($OUTPUT);

        $this->assertSame('somemultiselect1', $data['items'][0]['name']);
        $this->assertSame('One', $data['items'][0]['options'][0]['text']);
        $this->assertSame('1', $data['items'][0]['options'][0]['value']);
        $this->assertTrue($data['items'][0]['options'][0]['selected']);

        $this->assertSame('somemultiselect2', $data['items'][1]['name']);
        $this->assertSame('One', $data['items'][1]['options'][0]['text']);
        $this->assertSame('1', $data['items'][1]['options'][0]['value']);
        $this->assertTrue($data['items'][1]['options'][0]['selected']);

        $this->assertSame('somemultiselect3', $data['items'][2]['name']);
        $this->assertSame('Nothing', $data['items'][2]['options'][1]['text']);
        $this->assertSame('', $data['items'][2]['options'][1]['value']);
        $this->assertTrue($data['items'][2]['options'][1]['selected']);

        $this->assertSame('somemultiselect4', $data['items'][3]['name']);
        $this->assertSame('lower a', $data['items'][3]['options'][2]['text']);
        $this->assertSame('a', $data['items'][3]['options'][2]['value']);
        $this->assertTrue($data['items'][3]['options'][2]['selected']);
        $this->assertSame('UPPER A', $data['items'][3]['options'][3]['text']);
        $this->assertSame('A', $data['items'][3]['options'][3]['value']);
        $this->assertFalse($data['items'][3]['options'][3]['selected']);

        $this->assertSame('somemultiselect5', $data['items'][4]['name']);
        $this->assertFalse($data['items'][4]['options'][0]['selected']);
        $this->assertFalse($data['items'][4]['options'][1]['selected']);
        $this->assertFalse($data['items'][4]['options'][2]['selected']);
        $this->assertFalse($data['items'][4]['options'][3]['selected']);
    }

    public function test_extra_params() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertDebuggingNotCalled();
                $model->add(new multiselect('somemultiselect1', 'Some multiselect 1', array('a' => 'b', 'c' => 'd'), 'xx'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $form = new test_form();
    }
}
