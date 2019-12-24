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

use totara_form\form\element\tel,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\tel class.
 */
class totara_form_element_tel_testcase extends advanced_testcase {
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
                /** @var tel $tel1 */
                $tel1 = $model->add(new tel('sometel1', 'Some tel 1'));
                /** @var tel $tel2 */
                $tel2 = $model->add(new tel('sometel2', 'Some tel 3'));
                /** @var tel $tel3 */
                $tel3 = $model->add(new tel('sometel3', 'Some tel 3'));
                $tel3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('', $tel1->get_field_value());
                $testcase->assertSame('999 22', $tel2->get_field_value());
                $testcase->assertSame('999 33', $tel3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'sometel2' => '999 22',
            'sometel3' => '999 33',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var tel $tel1 */
                $tel1 = $model->add(new tel('sometel1', 'Some tel 1'));
                /** @var tel $tel2 */
                $tel2 = $model->add(new tel('sometel2', 'Some tel 3'));
                /** @var tel $tel3 */
                $tel3 = $model->add(new tel('sometel3', 'Some tel 3'));
                $tel3->set_frozen(true);
                /** @var tel $tel4 */
                $tel4 = $model->add(new tel('sometel4', 'Some tel 4'));
                $tel4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('999 111', $tel1->get_field_value());
                $testcase->assertSame('999 22', $tel2->get_field_value());
                $testcase->assertSame('999 33', $tel3->get_field_value());
                $testcase->assertSame('', $tel4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'sometel1' => '999 111',
            'sometel3' => '999 333',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'sometel2' => '999 22',
            'sometel3' => '999 33',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'sometel1' => '999 111',
            'sometel2' => '999 22',
            'sometel3' => '999 33',
            'sometel4' => null,
        );
        $this->assertSame($expected, $data);
    }
}
