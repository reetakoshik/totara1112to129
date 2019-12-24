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
 * Tests for \totara_form\group\section class.
 */
class totara_form_group_section_testcase extends advanced_testcase {
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
                /** @var section $section1 */
                $section1 = $model->add(new section('somesection1', 'Some section 1'));
                $text1 = new text('sometext1', 'Some text 1', PARAM_RAW);
                $result = $section1->add($text1);
                $this->assertSame($text1, $result);
                $text2 = new text('sometext2', 'Some text 2', PARAM_RAW);
                $result = $section1->add($text2);
                $this->assertSame($text2, $result);
                $text3 = new text('sometext3', 'Some text 3', PARAM_RAW);
                $result = $section1->add($text3, 0);
                $this->assertSame($text3, $result);
                $this->assertSame(array($text3, $text1, $text2), $section1->get_items());

                $section2 = $model->add(new section('somesection2', 'Some section 2'));
                try {
                    $section1->add($section2);
                    $this->fail('Exception expected, sections cannot be nested');
                } catch (\moodle_exception $e) {
                    $this->assertInstanceOf('coding_exception', $e);
                    $this->assertEquals('Coding error detected, it must be fixed by a programmer: Section cannot be added to another section!', $e->getMessage());
                }

            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        new test_form();
    }
}
