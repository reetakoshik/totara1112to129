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

use totara_form\form\element\action_button,
    totara_form\form\element\text,
    totara_form\form\group\buttons,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Tests for \totara_form\group\buttons class.
 */
class totara_form_group_buttons_testcase extends advanced_testcase {
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

    public function test_add() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var buttons $buttons1 */
                $buttons1 = $model->add(new buttons('somebuttons1', 'Some buttons 1'));
                $action_button1 = new action_button('someaction_button1', 'Some action_button 1', PARAM_RAW);
                $result = $buttons1->add($action_button1);
                $this->assertSame($action_button1, $result);
                $action_button2 = new action_button('someaction_button2', 'Some action_button 2', PARAM_RAW);
                $result = $buttons1->add($action_button2);
                $this->assertSame($action_button2, $result);
                $action_button3 = new action_button('someaction_button3', 'Some action_button 3', PARAM_RAW);
                $result = $buttons1->add($action_button3, 0);
                $this->assertSame($action_button3, $result);
                $this->assertSame(array($action_button3, $action_button1, $action_button2), $buttons1->get_items());

                $text1 = new text('sometext1', 'Some text 1', PARAM_RAW);
                try {
                    $buttons1->add($text1);
                    $this->fail('Exception expected, only buttons can be added');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Button group can contain action_buttons only!', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }
}
