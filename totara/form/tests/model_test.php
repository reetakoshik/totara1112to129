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
    totara_form\form\group\section,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\model class.
 */
class totara_form_model_testcase extends advanced_testcase {
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

    public function test_compare() {
        $this->assertTrue(model::compare('aa', model::OP_EQUALS, 'aa'));
        $this->assertTrue(model::compare('', model::OP_EQUALS, ''));
        $this->assertTrue(model::compare('', model::OP_EQUALS, null));
        $this->assertTrue(model::compare('1', model::OP_EQUALS, true));
        $this->assertTrue(model::compare('', model::OP_EQUALS, false));
        $this->assertFalse(model::compare('aa', model::OP_EQUALS, 'bb'));
        $this->assertFalse(model::compare('aa', model::OP_EQUALS, array('aa')));
        $this->assertFalse(model::compare(array('aa'), model::OP_EQUALS, 'aa'));
        $this->assertFalse(model::compare(array('aa'), model::OP_EQUALS, array('aa')));

        $this->assertTrue(model::compare('', model::OP_EMPTY));
        $this->assertTrue(model::compare(null, model::OP_EMPTY));
        $this->assertTrue(model::compare('0', model::OP_EMPTY));
        $this->assertTrue(model::compare(array(), model::OP_EMPTY));
        $this->assertTrue(model::compare(false, model::OP_EMPTY));
        $this->assertFalse(model::compare(' ', model::OP_EMPTY));
        $this->assertFalse(model::compare('a', model::OP_EMPTY));
        $this->assertFalse(model::compare('false', model::OP_EMPTY));
        $this->assertFalse(model::compare(array(false), model::OP_EMPTY));
        $this->assertFalse(model::compare(true, model::OP_EMPTY));

        $this->assertTrue(model::compare('aa', model::OP_FILLED));
        $this->assertTrue(model::compare(' ', model::OP_FILLED));
        $this->assertTrue(model::compare('0', model::OP_FILLED));
        $this->assertTrue(model::compare(true, model::OP_FILLED));
        $this->assertTrue(model::compare(false, model::OP_FILLED));
        $this->assertFalse(model::compare('', model::OP_FILLED));
        $this->assertFalse(model::compare(null, model::OP_FILLED));
        $this->assertFalse(model::compare(array(), model::OP_FILLED));
        $this->assertFalse(model::compare(array(false), model::OP_FILLED));

        $this->assertTrue(model::compare('aa', model::OP_NOT_EQUALS, 'bb'));
        $this->assertFalse(model::compare('aa', model::OP_NOT_EQUALS, 'aa'));
        $this->assertFalse(model::compare('', model::OP_NOT_EQUALS, ''));
        $this->assertFalse(model::compare('', model::OP_NOT_EQUALS, null));
        $this->assertFalse(model::compare('1', model::OP_NOT_EQUALS, true));
        $this->assertFalse(model::compare('', model::OP_NOT_EQUALS, false));
        $this->assertFalse(model::compare('aa', model::OP_NOT_EQUALS, array('aa')));
        $this->assertFalse(model::compare(array('aa'), model::OP_NOT_EQUALS, 'aa'));
        $this->assertFalse(model::compare(array('aa'), model::OP_NOT_EQUALS, array('aa')));

        $this->assertTrue(model::compare(' ', model::OP_NOT_EMPTY));
        $this->assertTrue(model::compare('a', model::OP_NOT_EMPTY));
        $this->assertTrue(model::compare('false', model::OP_NOT_EMPTY));
        $this->assertTrue(model::compare(array(false), model::OP_NOT_EMPTY));
        $this->assertTrue(model::compare(true, model::OP_NOT_EMPTY));
        $this->assertFalse(model::compare('', model::OP_NOT_EMPTY));
        $this->assertFalse(model::compare(null, model::OP_NOT_EMPTY));
        $this->assertFalse(model::compare('0', model::OP_NOT_EMPTY));
        $this->assertFalse(model::compare(array(), model::OP_NOT_EMPTY));
        $this->assertFalse(model::compare(false, model::OP_NOT_EMPTY));

        $this->assertTrue(model::compare('', model::OP_NOT_FILLED));
        $this->assertTrue(model::compare(null, model::OP_NOT_FILLED));
        $this->assertFalse(model::compare(array(false), model::OP_NOT_FILLED));
        $this->assertFalse(model::compare(array(), model::OP_NOT_FILLED));
        $this->assertFalse(model::compare('aa', model::OP_NOT_FILLED));
        $this->assertFalse(model::compare(' ', model::OP_NOT_FILLED));
        $this->assertFalse(model::compare('0', model::OP_NOT_FILLED));
        $this->assertFalse(model::compare(true, model::OP_NOT_FILLED));
        $this->assertFalse(model::compare(false, model::OP_NOT_FILLED));
    }

    public function test_is_valid_name() {
        $this->assertFalse(model::is_valid_name(''));
        $this->assertFalse(model::is_valid_name('sesskey'));
        $this->assertFalse(model::is_valid_name(null));
        $this->assertFalse(model::is_valid_name('a___a'));
        $this->assertFalse(model::is_valid_name('___a'));
        $this->assertFalse(model::is_valid_name('AA'));
        $this->assertFalse(model::is_valid_name('2aa'));

        $this->assertTrue(model::is_valid_name('aa'));
        $this->assertTrue(model::is_valid_name('a2a'));
        $this->assertTrue(model::is_valid_name('__aa'));
        $this->assertTrue(model::is_valid_name('a_a'));
        $this->assertTrue(model::is_valid_name('az1212_'));
    }

    public function test_context() {
        global $PAGE;
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $context1 = \context_course::instance($course1->id);
        $context2 = \context_course::instance($course2->id);

        $PAGE->set_context($context1);
        $this->assertSame($context1, $PAGE->context);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase, array $parameters) {
                $testcase->assertSame($parameters[0], $model->get_default_context());

                $model->set_default_context($parameters[1]);
                $testcase->assertSame($parameters[1], $model->get_default_context());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form(null, array($context1, $context2));
    }

    public function test_get_name() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame('', $model->get_name());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_is_name_used() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertTrue($model->is_name_used(''));
                $testcase->assertTrue($model->is_name_used('sesskey'));
                $testcase->assertTrue($model->is_name_used('___'));
                $testcase->assertTrue($model->is_name_used('___xx'));
                $testcase->assertFalse($model->is_name_used('ddsaadsds'));
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_is_finalised() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertFalse($model->is_finalised());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_get_items() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame(array(), $model->get_items());

                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_RAW));
                $testcase->assertSame(array(0 => $text1, 1 => $text2), $model->get_items());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_add() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_RAW));
                $testcase->assertSame(array($text1, $text2), $model->get_items());

                $text3 = $model->add(new text('sometext3', 'Some text 3', PARAM_RAW), 1);
                $testcase->assertSame(array($text1, $text3, $text2), $model->get_items());

                $text4 = $model->add(new text('sometext4', 'Some text 4', PARAM_RAW));
                $testcase->assertSame(array($text1, $text3, $text2, $text4), $model->get_items());

                $section1 = $model->add(new section('somesection1', 'Some section 1'));
                $testcase->assertSame(array($text1, $text3, $text2, $text4, $section1), $model->get_items());

                $text5 = $model->add(new text('sometext5', 'Some text 5', PARAM_RAW));
                $testcase->assertSame(array($text1, $text3, $text2, $text4, $section1), $model->get_items());
                $testcase->assertSame(array($text5), $section1->get_items());

                $section2 = $model->add(new section('somesection2', 'Some section 2'), 0);
                $text6 = $model->add(new text('sometext6', 'Some text 6', PARAM_RAW));
                $testcase->assertSame(array($section2, $text1, $text3, $text2, $text4, $section1), $model->get_items());
                $testcase->assertSame(array($text5), $section1->get_items());
                $testcase->assertSame(array($text6), $section2->get_items());

                // Prevent duplicate names.
                $duplicate = new text('sometext6', 'Some text 6', PARAM_RAW);
                try {
                    $model->add($duplicate);
                    $this->fail('Exception expected on duplicate names');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Duplicate name "sometext6" detected!', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_remove() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_RAW));
                $testcase->assertSame(array($text1, $text2), $model->get_items());

                $this->assertTrue($model->remove($text1));
                $testcase->assertSame(array($text2), $model->get_items());

                $this->assertFalse($model->remove($text1));

                $model->add($text1);
                $testcase->assertSame(array($text2, $text1), $model->get_items());

                $section1 = $model->add(new section('somesection1', 'Some section 1'));
                $text3 = $model->add(new text('sometext3', 'Some text 3', PARAM_RAW));
                $testcase->assertSame(array($text3), $section1->get_items());

                $this->assertTrue($model->remove($text3));
                $testcase->assertSame(array(), $section1->get_items());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_get_model() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame($model, $model->get_model());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_set_parent() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $section = new section('somesection1', 'Some section 1');
                try {
                    $model->set_parent($section);
                    $this->fail('Exception expected');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Model cannot have a parent!', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_get_parent() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertNull($model->get_parent());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_frozen() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $section1 = $model->add(new section('somesection1', 'Some section 1'));
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_RAW));
                $testcase->assertFalse($model->is_frozen());

                $model->set_frozen(true);
                $testcase->assertTrue($model->is_frozen());
                $testcase->assertTrue($text1->is_frozen());

                $model->set_frozen(false);
                $testcase->assertFalse($model->is_frozen());
                $testcase->assertFalse($text1->is_frozen());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_finalise() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertFalse($model->is_finalised());
                // Users must not finalise in definition!
                $model->finalise();
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);

        try {
            new test_form();
            $this->fail('Exception expected on direct model::finalise() use');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Cannot change form model, it is already finalise', $e->getMessage());
        }
    }

    public function test_get_id_suffix() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame('totara_form_test_test_form', $model->get_id_suffix());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame('xxx', $model->get_id_suffix());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form(null, null, 'xxx');
    }

    public function test_get_id() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame('tf_fid_totara_form_test_test_form', $model->get_id());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame('tf_fid_xxx', $model->get_id());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form(null, null, 'xxx');
    }

    public function test_require_finalised() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                try {
                    $model->require_finalised();
                    $this->fail('Exception expected');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid form model action, form is not finalised yet', $e->getMessage());
                }
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_require_not_finalised() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->require_not_finalised();
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_add_action_buttons() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $section1 = $model->add(new section('somesection1', 'Some section 1'));
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                $model->add_action_buttons();
                $buttongroup = $model->find('actionbuttonsgroup', 'get_name', 'totara_form\form\group\buttons');
                $submitbutton = $model->find('submitbutton', 'get_name', 'totara_form\form\element\action_button');
                $cancelbutton = $model->find('cancelbutton', 'get_name', 'totara_form\form\element\action_button');
                $testcase->assertSame(array($section1, $buttongroup), $model->get_items());
                $testcase->assertSame(array($submitbutton, $cancelbutton), $buttongroup->get_items());
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }

    public function test_find() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $section1 = $model->add(new section('somesection1', 'Some section 1'));
                $text1 = $model->add(new text('sometext1', 'Some text 1', PARAM_RAW));
                $text2 = $model->add(new text('sometext2', 'Some text 2', PARAM_RAW));
                $section2 = $model->add(new section('somesection2', 'Some section 2'));
                $text3 = $model->add(new text('sometext3', 'Some text 3', PARAM_RAW));
                $text4 = $model->add(new text('sometext4', 'Some text 4', PARAM_RAW));

                $result = $model->find('sometext2', 'get_name');
                $this->assertSame($text2, $result);

                $result = $model->find('sometext2', 'get_name', 'totara_form\item');
                $this->assertSame($text2, $result);

                $result = $model->find('sometext2', 'get_name', 'totara_form\element');
                $this->assertSame($text2, $result);

                $result = $model->find('sometext2', 'get_name', 'totara_form\group');
                $this->assertNull($result);

                $result = $model->find('sometext2', 'get_name', 'totara_form\element', false);
                $this->assertNull($result);

                $result = $model->find('somesection2', 'get_name', 'totara_form\group', true);
                $this->assertSame($section2, $result);

                $result = $model->find('somesection2', 'get_name', 'totara_form\group', false);
                $this->assertSame($section2, $result);

                $result = $model->find(true, 'is_name_used', 'totara_form\item', true, array('sometext2'));
                $this->assertSame($text2, $result);

                $result = $model->find('0', 'is_name_used', 'totara_form\item', true, array('sometext2'), false);
                $this->assertSame($section1, $result);

                $result = $model->find('0', 'is_name_used', 'totara_form\item', true, array('sometext2'), true);
                $this->assertNull($result);
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();

    }

    public function test_get_current_data() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame(array('a' => 'x'), $model->get_current_data('a'));
                $testcase->assertSame(array('b' => 'y'), $model->get_current_data('b'));
                $testcase->assertSame(array(), $model->get_current_data('c'));
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form(array('a' => 'x', 'b' => 'y'));

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame(array(), $model->get_current_data(null));
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertSame(array('a' => 'x', 'b' => 'y'), $model->get_current_data(null));
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form(array('a' => 'x', 'b' => 'y'));
    }
}
