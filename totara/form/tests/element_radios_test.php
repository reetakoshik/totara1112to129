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

use totara_form\form\element\radios,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\radios class.
 */
class totara_form_element_radios_testcase extends advanced_testcase {
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
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                /** @var radios $radios3 */
                $radios3 = $model->add(new radios('someradios3', 'Some radios 3', $options));
                $radios3->set_frozen(true);
                /** @var radios $radios4 */
                $radios4 = $model->add(new radios('someradios4', 'Some radios 4', $options));
                $radios4->set_frozen(true);
                /** @var radios $radios5 */
                $radios5 = $model->add(new radios('someradios5', 'Some radios 5', $options));

                // Test the form field values.
                $testcase->assertSame('n', $radios1->get_field_value());
                $testcase->assertSame('n', $radios2->get_field_value());
                $testcase->assertSame('n', $radios3->get_field_value());
                $testcase->assertSame('y', $radios4->get_field_value());
                $testcase->assertSame(null, $radios5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someradios1' => 'n',
            'someradios2' => 'n',
            'someradios3' => 'n',
            'someradios4' => 'y',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                /** @var radios $radios3 */
                $radios3 = $model->add(new radios('someradios3', 'Some radios 3', $options));
                $radios3->set_frozen(true);
                /** @var radios $radios4 */
                $radios4 = $model->add(new radios('someradios4', 'Some radios 4', $options));
                $radios4->set_frozen(true);
                /** @var radios $radios5 */
                $radios5 = $model->add(new radios('someradios5', 'Some radios 5', $options));

                // Test the form field values.
                $testcase->assertSame('n', $radios1->get_field_value());
                $testcase->assertSame('y', $radios2->get_field_value());
                $testcase->assertSame('y', $radios3->get_field_value());
                $testcase->assertSame(null, $radios4->get_field_value());
                $testcase->assertSame('n', $radios5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => 'n',
            'someradios2' => 'y',
            'someradios3' => 'n',
            'someradios4' => 'xxxxx',
            'someradios5' => 'n',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someradios1' => 'n',
            'someradios2' => 'n',
            'someradios3' => 'y',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someradios1' => 'n',
            'someradios2' => 'y',
            'someradios3' => 'y',
            'someradios4' => null,
            'someradios5' => 'n',
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_current_normalisation() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array(1 => 'Yes', 0 => 'No', '' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                /** @var radios $radios3 */
                $radios3 = $model->add(new radios('someradios3', 'Some radios 3', $options));
                $radios3->set_frozen(true);
                /** @var radios $radios4 */
                $radios4 = $model->add(new radios('someradios4', 'Some radios 4', $options));
                $radios4->set_frozen(true);
                /** @var radios $radios5 */
                $radios5 = $model->add(new radios('someradios5', 'Some radios 5', $options));

                // Test the form field values.
                $testcase->assertSame('0', $radios1->get_field_value());
                $testcase->assertSame('1', $radios2->get_field_value());
                $testcase->assertSame('1', $radios3->get_field_value());
                $testcase->assertSame(null, $radios4->get_field_value());
                $testcase->assertSame('', $radios5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => '0',
            'someradios2' => '1',
            'someradios3' => '',
            'someradios4' => '1',
            'someradios5' => '',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someradios1' => 0,
            'someradios2' => 0,
            'someradios3' => 1,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someradios1' => '0',
            'someradios2' => '1',
            'someradios3' => '1',
            'someradios4' => null,
            'someradios5' => '',
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));

                // Test the form field values.
                $testcase->assertSame('xxx', $radios1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => 'xxx',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                $radios1->set_attribute('required', true);
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                $radios2->set_frozen(true);
                $radios2->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => 'y',
            'someradios2' => 'y',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someradios1' => 'n',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someradios1' => 'y',
            'someradios2' => null,
        );
        $this->assertSame($expected, $data);

        $postdata = array();
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_incorrect_current() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                $testcase->assertDebuggingNotCalled();
                $model->add(new radios('someradios1', 'Some radios 1', $options));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array());
        $currentdata = array(
            'someradios1' => 'xx',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_current() {
        global $OUTPUT;

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('1' => 'One', '' => 'Nothing', 'a' => 'lower a', 'A' => 'UPPER A');
                $model->add(new radios('someradio1', 'Some select 1', $options));
                $model->add(new radios('someradio2', 'Some select 2', $options));
                $model->add(new radios('someradio3', 'Some select 3', $options));
                $model->add(new radios('someradio4', 'Some select 4', $options));
                $model->add(new radios('someradio5', 'Some select 5', $options));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someradio1' => 1,
            'someradio2' => '1',
            'someradio3' => '',
            'someradio4' => 'a',
        );
        $form = new test_form($currentdata);
        $data = $form->export_for_template($OUTPUT);

        $this->assertSame('someradio1', $data['items'][0]['name']);
        $this->assertSame('One', $data['items'][0]['options'][0]['text']);
        $this->assertSame('1', $data['items'][0]['options'][0]['value']);
        $this->assertTrue($data['items'][0]['options'][0]['checked']);

        $this->assertSame('someradio2', $data['items'][1]['name']);
        $this->assertSame('One', $data['items'][1]['options'][0]['text']);
        $this->assertSame('1', $data['items'][1]['options'][0]['value']);
        $this->assertTrue($data['items'][1]['options'][0]['checked']);

        $this->assertSame('someradio3', $data['items'][2]['name']);
        $this->assertSame('Nothing', $data['items'][2]['options'][1]['text']);
        $this->assertSame('', $data['items'][2]['options'][1]['value']);
        $this->assertTrue($data['items'][2]['options'][1]['checked']);

        $this->assertSame('someradio4', $data['items'][3]['name']);
        $this->assertSame('lower a', $data['items'][3]['options'][2]['text']);
        $this->assertSame('a', $data['items'][3]['options'][2]['value']);
        $this->assertTrue($data['items'][3]['options'][2]['checked']);
        $this->assertSame('UPPER A', $data['items'][3]['options'][3]['text']);
        $this->assertSame('A', $data['items'][3]['options'][3]['value']);
        $this->assertFalse($data['items'][3]['options'][3]['checked']);

        $this->assertSame('someradio5', $data['items'][4]['name']);
        $this->assertFalse($data['items'][4]['options'][0]['checked']);
        $this->assertFalse($data['items'][4]['options'][1]['checked']);
        $this->assertFalse($data['items'][4]['options'][2]['checked']);
        $this->assertFalse($data['items'][4]['options'][3]['checked']);
    }
}
