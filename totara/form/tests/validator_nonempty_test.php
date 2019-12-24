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

use totara_form\form\element\text,
    \totara_form\form\validator\nonempty,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\text class.
 */
class totara_form_validator_nonempty_testcase extends advanced_testcase {
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

    public function test_validator() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var text $text1 */
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                $text1->add_validator(new nonempty('Some error'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'sometext1' => '1',
        );
        test_form::phpunit_set_post_data($postdata);
        $expected = array(
            'sometext1' => '1',
        );
        $form = new test_form();
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        $postdata = array(
            'sometext1' => '0',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);

        $postdata = array(
            'sometext1' => '',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
