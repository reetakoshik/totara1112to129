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

use totara_form\form\element\select,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\select class.
 */
class totara_form_element_select_testcase extends advanced_testcase {
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
                /** @var select $select1 */
                $select1 = $model->add(new select('someselect1', 'Some select 1', $options));
                /** @var select $select2 */
                $select2 = $model->add(new select('someselect2', 'Some select 2', $options));
                /** @var select $select3 */
                $select3 = $model->add(new select('someselect3', 'Some select 3', $options));
                $select3->set_frozen(true);
                /** @var select $select4 */
                $select4 = $model->add(new select('someselect4', 'Some select 4', $options));
                $select4->set_frozen(true);
                /** @var select $select5 */
                $select5 = $model->add(new select('someselect5', 'Some select 5', $options));

                // Test the form field values.
                $testcase->assertSame('n', $select1->get_field_value());
                $testcase->assertSame('n', $select2->get_field_value());
                $testcase->assertSame('n', $select3->get_field_value());
                $testcase->assertSame('y', $select4->get_field_value());
                $testcase->assertSame('y', $select5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someselect1' => 'n',
            'someselect2' => 'n',
            'someselect3' => 'n',
            'someselect4' => 'y',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var select $select1 */
                $select1 = $model->add(new select('someselect1', 'Some select 1', $options));
                /** @var select $select2 */
                $select2 = $model->add(new select('someselect2', 'Some select 2', $options));
                /** @var select $select3 */
                $select3 = $model->add(new select('someselect3', 'Some select 3', $options));
                $select3->set_frozen(true);
                /** @var select $select4 */
                $select4 = $model->add(new select('someselect4', 'Some select 4', $options));
                $select4->set_frozen(true);
                /** @var select $select5 */
                $select5 = $model->add(new select('someselect5', 'Some select 5', $options));

                // Test the form field values.
                $testcase->assertSame('n', $select1->get_field_value());
                $testcase->assertSame('y', $select2->get_field_value());
                $testcase->assertSame('y', $select3->get_field_value());
                $testcase->assertSame('y', $select4->get_field_value());
                $testcase->assertSame('n', $select5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someselect1' => 'n',
            'someselect2' => 'y',
            'someselect3' => 'n',
            'someselect4' => 'xxxxx',
            'someselect5' => 'n',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someselect1' => 'n',
            'someselect2' => 'n',
            'someselect3' => 'y',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someselect1' => 'n',
            'someselect2' => 'y',
            'someselect3' => 'y',
            'someselect4' => null,
            'someselect5' => 'n',
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var select $select1 */
                $select1 = $model->add(new select('someselect1', 'Some select 1', $options));

                // Test the form field values.
                $testcase->assertSame('xxx', $select1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someselect1' => 'xxx',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', '' => 'Maybe');
                /** @var select $select1 */
                $select1 = $model->add(new select('someselect1', 'Some select 1', $options));
                $select1->set_attribute('required', true);
                /** @var select $select2 */
                $select2 = $model->add(new select('someselect2', 'Some select 2', $options));
                $select2->set_frozen(true);
                $select2->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someselect1' => 'y',
            'someselect2' => 'y',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someselect1' => 'n',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someselect1' => 'y',
            'someselect2' => null,
        );
        $this->assertSame($expected, $data);

        $postdata = array(
            'someselect1' => '',
        );
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
                $model->add(new select('someselect1', 'Some select 1', $options));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array());
        $currentdata = array(
            'someselect1' => 'xx',
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
                $model->add(new select('someselect1', 'Some select 1', $options));
                $model->add(new select('someselect2', 'Some select 2', $options));
                $model->add(new select('someselect3', 'Some select 3', $options));
                $model->add(new select('someselect4', 'Some select 4', $options));
                $model->add(new select('someselect5', 'Some select 5', $options));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someselect1' => 1,
            'someselect2' => '1',
            'someselect3' => '',
            'someselect4' => 'a',
        );
        $form = new test_form($currentdata);
        $data = $form->export_for_template($OUTPUT);

        $this->assertSame('someselect1', $data['items'][0]['name']);
        $this->assertSame('One', $data['items'][0]['options'][0]['text']);
        $this->assertSame('1', $data['items'][0]['options'][0]['value']);
        $this->assertTrue($data['items'][0]['options'][0]['selected']);

        $this->assertSame('someselect2', $data['items'][1]['name']);
        $this->assertSame('One', $data['items'][1]['options'][0]['text']);
        $this->assertSame('1', $data['items'][1]['options'][0]['value']);
        $this->assertTrue($data['items'][1]['options'][0]['selected']);

        $this->assertSame('someselect3', $data['items'][2]['name']);
        $this->assertSame('Nothing', $data['items'][2]['options'][1]['text']);
        $this->assertSame('', $data['items'][2]['options'][1]['value']);
        $this->assertTrue($data['items'][2]['options'][1]['selected']);

        $this->assertSame('someselect4', $data['items'][3]['name']);
        $this->assertSame('lower a', $data['items'][3]['options'][2]['text']);
        $this->assertSame('a', $data['items'][3]['options'][2]['value']);
        $this->assertTrue($data['items'][3]['options'][2]['selected']);
        $this->assertSame('UPPER A', $data['items'][3]['options'][3]['text']);
        $this->assertSame('A', $data['items'][3]['options'][3]['value']);
        $this->assertFalse($data['items'][3]['options'][3]['selected']);

        $this->assertSame('someselect5', $data['items'][4]['name']);
        $this->assertTrue($data['items'][4]['options'][0]['selected']);
        $this->assertFalse($data['items'][4]['options'][1]['selected']);
        $this->assertFalse($data['items'][4]['options'][2]['selected']);
        $this->assertFalse($data['items'][4]['options'][3]['selected']);
    }

    public function test_set_optgroups() {
        global $OUTPUT;

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe', '3' => 'Certainly', '-1' => 'Hell no!');
                /** @var select $select */
                $select = $model->add(new select('someselect', 'Some select', $options));
                $select->set_optgroups(array(
                    'Positive' => array('y', '3'),
                    'Negative' => array('-1', 'n', '-10'),
                    'Rubbish' => array('xxxx'),
                    'Numbers' => array('3', '-1'),
                ));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someselect' => '3',
        );
        $form = new test_form($currentdata);
        $data = $form->export_for_template($OUTPUT);

        $expected = array(
            array(
                'group' => true,
                'label' => 'Positive',
                'options' =>
                    array(
                        array(
                            'value' => 'y',
                            'text' => 'Yes',
                            'selected' => false,
                        ),
                        array(
                            'value' => '3',
                            'text' => 'Certainly',
                            'selected' => true,
                        ),
                    ),
            ),
            array(
                'group' => true,
                'label' => 'Negative',
                'options' =>
                    array(
                        array(
                            'value' => '-1',
                            'text' => 'Hell no!',
                            'selected' => false,
                        ),
                        array(
                            'value' => 'n',
                            'text' => 'No',
                            'selected' => false,
                        ),
                    ),
            ),
            array(
                'value' => 'm',
                'text' => 'Maybe',
                'selected' => false,
            ),
            array(
                'group' => true,
                'label' => 'Numbers',
                'options' =>
                    array(
                        array(
                            'value' => '3',
                            'text' => 'Certainly',
                            'selected' => false,
                        ),
                        array(
                            'value' => '-1',
                            'text' => 'Hell no!',
                            'selected' => false,
                        ),
                    ),
            ),
        );

        $this->assertSame($expected, $data['items'][0]['options']);
    }
}
