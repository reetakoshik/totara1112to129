<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_form
 */

use totara_form\form\element\text,
    totara_form\form\group\wizard,
    totara_form\form\group\wizard_stage,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form,
    totara_form\form\group\section;

/**
 * Tests for \totara_form\group\wizard class.
 */
class totara_form_group_wizard_testcase extends advanced_testcase {
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
                /** @var wizard $wizard */
                $wizard = $model->add(new wizard('my_wizard'));

                $stage1 = new wizard_stage('stage1', 'Personal data');
                $result = $wizard->add_stage($stage1);
                $this->assertSame($stage1, $result);
                $stage1->add(new text('firstname', 'First name', PARAM_TEXT));

                $stage2 = new wizard_stage('stage2', 'Learning records');
                $result = $wizard->add_stage($stage2);
                $this->assertSame($stage2, $result);
                $stage2->add(new text('lastname', 'Last name', PARAM_TEXT));

                $this->assertSame([$stage1, $stage2], $wizard->get_items());

                $section = new section('testsection', 'Test section');
                try {
                    $wizard->add($section);
                    $this->fail('Exception expected, only wizard stages can be added to a wizard.');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Only wizard stages can be added to a wizard', $e->getMessage());
                }

                try {
                    $stage2->add($stage1);
                    $this->fail('Exception expected, wizard stages cannot be added to another stage.');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Wizard stages cannot be added to another stage.', $e->getMessage());
                }

                try {
                    $stage2->set_parent($stage1);
                    $this->fail('Exception expected, wizard stages can only be added to Wizards.');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Wizard stages can only be added to Wizards.', $e->getMessage());
                }
            }
        );
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_is_form_cancelled() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var wizard $wizard */
                $wizard = $model->add(new wizard('my_wizard'));
            }
        );
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data([
            'my_wizard__changestage' => wizard::FORM_CANCELLED
        ]);
        $form = new test_form();
        $this->assertTrue($form->is_cancelled());
    }

    public function get_requested_current_stage_data_provider() {
        return [
            [
                [
                    'my_wizard__changestage' => 'test_stage2'
                ],
                'test_stage2'
            ],
            [
                [
                    'my_wizard__changestage' => 'Next',
                    'my_wizard__nextstage' => 'test_stage3'
                ],
                'test_stage3'
            ],
            [
                [
                    'my_wizard__changestage' => 'Prev',
                    'my_wizard__prevstage' => 'test_stage1'
                ],
                'test_stage1'
            ],
            [
                [
                    'my_wizard__prevstage' => 'test_stage1'
                ],
                null
            ],
            [
                [],
                null
            ],
        ];
    }

    /**
     * @dataProvider get_requested_current_stage_data_provider
     * @param array $postdata
     * @param mixed $expected_result
     */
    public function test_get_requested_current_stage(array $postdata, $expected_result) {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var wizard $wizard */
                $wizard = $model->add(new wizard('my_wizard'));
            }
        );
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $items = $definition->model->get_items();
        /** @var wizard $wizard */
        $wizard = $items[0];
        $this->assertEquals($expected_result, $wizard->get_requested_current_stage());
    }
}
