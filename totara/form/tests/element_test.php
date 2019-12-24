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
 * Indirect tests for \totara_form\form\element class.
 *
 * Use basic elements that do not override tested methods.
 */
class totara_form_element_testcase extends advanced_testcase {
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

    public function test_invalid_name() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                try {
                    $model->add(new hidden('some element', PARAM_RAW));
                    $testcase->fail('Coding exception expected when invalid name specified');
                } catch (\moodle_exception $e) {
                    $testcase->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid element name', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new hidden('some__element', PARAM_RAW));
                try {
                    $model->add(new hidden('some___element', PARAM_RAW));
                    $testcase->fail('Coding exception expected when invalid name specified');
                } catch (\moodle_exception $e) {
                    $testcase->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid element name', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                try {
                    $model->add(new hidden('someelement[]', PARAM_RAW));
                    $testcase->fail('Coding exception expected when invalid name specified');
                } catch (\moodle_exception $e) {
                    $testcase->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid element name', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                try {
                    $model->add(new hidden('sesskey', PARAM_RAW));
                    $testcase->fail('Coding exception expected when invalid name specified');
                } catch (\moodle_exception $e) {
                    $testcase->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid element name', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_duplicate_name() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new hidden('someelement', PARAM_RAW));
                try {
                    $model->add(new hidden('someelement', PARAM_RAW));
                    $testcase->fail('Coding exception expected when invalid name specified');
                } catch (\moodle_exception $e) {
                    $testcase->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Duplicate name "someelement" detected!', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }
}
