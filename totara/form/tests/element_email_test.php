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

use totara_form\form\element\email,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\email class.
 */
class totara_form_element_email_testcase extends advanced_testcase {
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
                /** @var email $email1 */
                $email1 = $model->add(new email('someemail1', 'Some email 1'));
                /** @var email $email2 */
                $email2 = $model->add(new email('someemail2', 'Some email 3'));
                /** @var email $email3 */
                $email3 = $model->add(new email('someemail3', 'Some email 3'));
                $email3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('', $email1->get_field_value());
                $testcase->assertSame('petr@example.com', $email2->get_field_value());
                $testcase->assertSame('john@example.com', $email3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someemail2' => 'petr@example.com',
            'someemail3' => 'john@example.com',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var email $email1 */
                $email1 = $model->add(new email('someemail1', 'Some email 1'));
                /** @var email $email2 */
                $email2 = $model->add(new email('someemail2', 'Some email 3'));
                /** @var email $email3 */
                $email3 = $model->add(new email('someemail3', 'Some email 3'));
                $email3->set_frozen(true);
                /** @var email $email4 */
                $email4 = $model->add(new email('someemail4', 'Some email 4'));
                $email4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('lola@example.com', $email1->get_field_value());
                $testcase->assertSame('petr@example.com', $email2->get_field_value());
                $testcase->assertSame('john@example.com', $email3->get_field_value());
                $testcase->assertSame('', $email4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someemail1' => 'lola@example.com',
            'someemail3' => 'vincent@example.com',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someemail2' => 'petr@example.com',
            'someemail3' => 'john@example.com',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someemail1' => 'lola@example.com',
            'someemail2' => 'petr@example.com',
            'someemail3' => 'john@example.com',
            'someemail4' => null,
        );
        $this->assertSame($expected, $data);
    }

    public function test_validation() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var email $email1 */
                $email1 = $model->add(new email('someemail1', 'Some email 1'));
                $email1->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('lola@example.com', $email1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someemail1' => 'cxcxcxcx',
        );
        $currentdata = array(
            'someemail1' => 'lola@example.com',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someemail1' => 'lola@example.com',
        );
        $this->assertSame($expected, $data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var email $email1 */
                $email1 = $model->add(new email('someemail1', 'Some email 1'));

                // Test the form field values.
                $testcase->assertSame(' lola@example.com', $email1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someemail1' => ' lola@example.com',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
