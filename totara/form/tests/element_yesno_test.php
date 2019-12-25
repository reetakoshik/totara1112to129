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

use totara_form\form\element\yesno,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\yesno class.
 */
class totara_form_element_yesno_testcase extends advanced_testcase {
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
                /** @var yesno $yesno1 */
                $yesno1 = $model->add(new yesno('someyesno1', 'Some yesno 1'));
                /** @var yesno $yesno2 */
                $yesno2 = $model->add(new yesno('someyesno2', 'Some yesno 2'));
                /** @var yesno $yesno3 */
                $yesno3 = $model->add(new yesno('someyesno3', 'Some yesno 3'));
                $yesno3->set_frozen(true);
                /** @var yesno $yesno4 */
                $yesno4 = $model->add(new yesno('someyesno4', 'Some yesno 4'));
                $yesno4->set_frozen(true);
                /** @var yesno $yesno5 */
                $yesno5 = $model->add(new yesno('someyesno5', 'Some yesno 5'));

                // Test the form field values.
                $testcase->assertSame('0', $yesno1->get_field_value());
                $testcase->assertSame('0', $yesno2->get_field_value());
                $testcase->assertSame('0', $yesno3->get_field_value());
                $testcase->assertSame('0', $yesno4->get_field_value());
                $testcase->assertSame(null, $yesno5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someyesno1' => '0',
            'someyesno2' => '0',
            'someyesno3' => '0',
            'someyesno4' => '0',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var yesno $yesno1 */
                $yesno1 = $model->add(new yesno('someyesno1', 'Some yesno 1'));
                /** @var yesno $yesno2 */
                $yesno2 = $model->add(new yesno('someyesno2', 'Some yesno 2'));
                /** @var yesno $yesno3 */
                $yesno3 = $model->add(new yesno('someyesno3', 'Some yesno 3'));
                $yesno3->set_frozen(true);
                /** @var yesno $yesno4 */
                $yesno4 = $model->add(new yesno('someyesno4', 'Some yesno 4'));
                $yesno4->set_frozen(true);
                /** @var yesno $yesno5 */
                $yesno5 = $model->add(new yesno('someyesno5', 'Some yesno 5'));

                // Test the form field values.
                $testcase->assertSame('0', $yesno1->get_field_value());
                $testcase->assertSame('0', $yesno2->get_field_value());
                $testcase->assertSame('0', $yesno3->get_field_value());
                $testcase->assertSame(null, $yesno4->get_field_value());
                $testcase->assertSame('0', $yesno5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someyesno1' => '0',
            'someyesno2' => '0',
            'someyesno3' => '0',
            'someyesno4' => 'xxxxx',
            'someyesno5' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someyesno1' => '0',
            'someyesno2' => '0',
            'someyesno3' => '0',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someyesno1' => '0',
            'someyesno2' => '0',
            'someyesno3' => '0',
            'someyesno4' => null,
            'someyesno5' => '0',
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var yesno $yesno1 */
                $yesno1 = $model->add(new yesno('someyesno1', 'Some yesno 1'));

                // Test the form field values.
                $testcase->assertSame('xxx', $yesno1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someyesno1' => 'xxx',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var yesno $yesno1 */
                $yesno1 = $model->add(new yesno('someyesno1', 'Some yesno 1'));
                $yesno1->set_attribute('required', true);
                /** @var yesno $yesno2 */
                $yesno2 = $model->add(new yesno('someyesno2', 'Some yesno 2'));
                $yesno2->set_frozen(true);
                $yesno2->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someyesno1' => '0',
            'someyesno2' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someyesno1' => '0',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someyesno1' => '0',
            'someyesno2' => null,
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
                $testcase->assertDebuggingNotCalled();
                $model->add(new yesno('someyesno1', 'Some yesno 1'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array());
        $currentdata = array(
            'someyesno1' => 'xx',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
