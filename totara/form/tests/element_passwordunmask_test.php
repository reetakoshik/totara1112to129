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

use totara_form\form\element\passwordunmask,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\passwordunmask class.
 */
class totara_form_element_passwordunmask_testcase extends advanced_testcase {
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
                /** @var passwordunmask $passwordunmask1 */
                $passwordunmask1 = $model->add(new passwordunmask('somepasswordunmask1', 'Some passwordunmask 1'));
                /** @var passwordunmask $passwordunmask2 */
                $passwordunmask2 = $model->add(new passwordunmask('somepasswordunmask2', 'Some passwordunmask 3'));
                /** @var passwordunmask $passwordunmask3 */
                $passwordunmask3 = $model->add(new passwordunmask('somepasswordunmask3', 'Some passwordunmask 3'));
                $passwordunmask3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('', $passwordunmask1->get_field_value());
                $testcase->assertSame('9922', $passwordunmask2->get_field_value());
                $testcase->assertSame('9933', $passwordunmask3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somepasswordunmask2' => '9922',
            'somepasswordunmask3' => '9933',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var passwordunmask $passwordunmask1 */
                $passwordunmask1 = $model->add(new passwordunmask('somepasswordunmask1', 'Some passwordunmask 1'));
                /** @var passwordunmask $passwordunmask2 */
                $passwordunmask2 = $model->add(new passwordunmask('somepasswordunmask2', 'Some passwordunmask 3'));
                /** @var passwordunmask $passwordunmask3 */
                $passwordunmask3 = $model->add(new passwordunmask('somepasswordunmask3', 'Some passwordunmask 3'));
                $passwordunmask3->set_frozen(true);
                /** @var passwordunmask $passwordunmask4 */
                $passwordunmask4 = $model->add(new passwordunmask('somepasswordunmask4', 'Some passwordunmask 4'));
                $passwordunmask4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('99111', $passwordunmask1->get_field_value());
                $testcase->assertSame('9922', $passwordunmask2->get_field_value());
                $testcase->assertSame('9933', $passwordunmask3->get_field_value());
                $testcase->assertSame('', $passwordunmask4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somepasswordunmask1' => '99111',
            'somepasswordunmask3' => '99333',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somepasswordunmask2' => '9922',
            'somepasswordunmask3' => '9933',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somepasswordunmask1' => '99111',
            'somepasswordunmask2' => '9922',
            'somepasswordunmask3' => '9933',
            'somepasswordunmask4' => null,
        );
        $this->assertSame($expected, $data);
    }
}
