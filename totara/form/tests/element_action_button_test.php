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
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\action_button class.
 */
class totara_form_element_action_button_testcase extends advanced_testcase {
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
                /** @var action_button $submitbutton */
                $submitbutton = $model->add(new action_button('submitbutton', 'Submit', action_button::TYPE_SUBMIT));
                /** @var action_button $cancelbbutton */
                $cancelbbutton = $model->add(new action_button('cancelbutton', 'Cancel', action_button::TYPE_CANCEL));
                /** @var action_button $reloadbutton */
                $reloadbutton = $model->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));

                $testcase->assertFalse($submitbutton->get_field_value());
                $testcase->assertFalse($cancelbbutton->get_field_value());
                $testcase->assertFalse($reloadbutton->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
        $this->assertFalse($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
    }

    public function test_submit() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var action_button $submitbutton */
                $submitbutton = $model->add(new action_button('submitbutton', 'Submit', action_button::TYPE_SUBMIT));
                /** @var action_button $cancelbbutton */
                $cancelbbutton = $model->add(new action_button('cancelbutton', 'Cancel', action_button::TYPE_CANCEL));
                /** @var action_button $reloadbutton */
                $reloadbutton = $model->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));

                $testcase->assertTrue($submitbutton->get_field_value());
                $testcase->assertFalse($cancelbbutton->get_field_value());
                $testcase->assertFalse($reloadbutton->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('submitbutton' => 'Submit');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = (array)$form->get_data();
        $this->assertSame(array('submitbutton' => '1'), $data);
        $this->assertFalse($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());

        // Post with empty value should be fine too.
        $postdata = array('submitbutton' => '');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = (array)$form->get_data();
        $this->assertSame(array('submitbutton' => '1'), $data);
        $this->assertFalse($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
    }

    public function test_multiple_submit() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new action_button('submitbutton1', 'Submit 1', action_button::TYPE_SUBMIT));
                $model->add(new action_button('submitbutton2', 'Submit 2', action_button::TYPE_SUBMIT));
                $model->add(new action_button('submitbutton3', 'Submit 3', action_button::TYPE_SUBMIT));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('submitbutton2' => 'Submit 2');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = (array)$form->get_data();
        $this->assertSame(array('submitbutton1' => '0', 'submitbutton2' => '1', 'submitbutton3' => '0'), $data);
        $this->assertFalse($definition->get_element('submitbutton1')->get_field_value());
        $this->assertTrue($definition->get_element('submitbutton2')->get_field_value());
        $this->assertFalse($definition->get_element('submitbutton3')->get_field_value());
    }

    public function test_reload() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new action_button('submitbutton', 'Submit', action_button::TYPE_SUBMIT));
                $model->add(new action_button('cancelbutton', 'Cancel', action_button::TYPE_CANCEL));
                $model->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('reloadbutton' => 'Reload');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
        $this->assertFalse($definition->get_element('submitbutton')->get_field_value());
        $this->assertFalse($definition->get_element('cancelbutton')->get_field_value());
        $this->assertTrue($definition->get_element('reloadbutton')->get_field_value());
        $this->assertFalse($form->is_cancelled());
        $this->assertTrue($form->is_reloaded());

        // Post with empty value should be fine too.
        $postdata = array('reloadbutton' => '');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
        $this->assertFalse($definition->get_element('submitbutton')->get_field_value());
        $this->assertFalse($definition->get_element('cancelbutton')->get_field_value());
        $this->assertTrue($definition->get_element('reloadbutton')->get_field_value());
        $this->assertFalse($form->is_cancelled());
        $this->assertTrue($form->is_reloaded());

        // Test action priority.
        $postdata = array('submitbutton' => 'x', 'reloadbutton' => 'x');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
        $this->assertTrue($definition->get_element('submitbutton')->get_field_value());
        $this->assertFalse($definition->get_element('cancelbutton')->get_field_value());
        $this->assertTrue($definition->get_element('reloadbutton')->get_field_value());
        $this->assertFalse($form->is_cancelled());
        $this->assertTrue($form->is_reloaded());
    }

    public function test_cancel() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new action_button('submitbutton', 'Submit', action_button::TYPE_SUBMIT));
                $model->add(new action_button('cancelbutton', 'Cancel', action_button::TYPE_CANCEL));
                $model->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('cancelbutton' => 'Cancel');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
        $this->assertFalse($definition->get_element('submitbutton')->get_field_value());
        $this->assertTrue($definition->get_element('cancelbutton')->get_field_value());
        $this->assertFalse($definition->get_element('reloadbutton')->get_field_value());
        $this->assertTrue($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());

        // Post with empty value should be fine too.
        $postdata = array('cancelbutton' => '');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
        $this->assertFalse($definition->get_element('submitbutton')->get_field_value());
        $this->assertTrue($definition->get_element('cancelbutton')->get_field_value());
        $this->assertFalse($definition->get_element('reloadbutton')->get_field_value());
        $this->assertTrue($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());

        // Test action priority.
        $postdata = array('submitbutton' => 'x', 'cancelbutton' => 'x', 'reloadbutton' => 'x');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
        $this->assertTrue($definition->get_element('submitbutton')->get_field_value());
        $this->assertTrue($definition->get_element('cancelbutton')->get_field_value());
        $this->assertTrue($definition->get_element('reloadbutton')->get_field_value());
        $this->assertTrue($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
    }

    public function test_frozen() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var action_button $submitbutton */
                $submitbutton = $model->add(new action_button('submitbutton', 'Submit', action_button::TYPE_SUBMIT));
                $submitbutton->set_frozen(true);
                /** @var action_button $cancelbbutton */
                $cancelbbutton = $model->add(new action_button('cancelbutton', 'Cancel', action_button::TYPE_CANCEL));
                $cancelbbutton->set_frozen(true);
                /** @var action_button $reloadbutton */
                $reloadbutton = $model->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));
                $reloadbutton->set_frozen(true);

                $testcase->assertFalse($submitbutton->get_field_value());
                $testcase->assertFalse($cancelbbutton->get_field_value());
                $testcase->assertFalse($reloadbutton->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array('submitbutton' => 'Submit', 'cancelbutton' => 'Cancel', 'reloadbutton' => 'Reload');
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = (array)$form->get_data();
        $this->assertSame(array('submitbutton' => '0'), $data);
        $this->assertFalse($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
    }
}
