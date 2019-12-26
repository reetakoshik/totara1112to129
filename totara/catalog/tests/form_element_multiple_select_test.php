<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
use totara_catalog\form\element\multiple_select,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

class totara_catalog_form_element_multiple_select_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();
        require_once(__DIR__ . '/../../form/tests/fixtures/test_form.php');
        test_form::phpunit_reset();
        $this->resetAfterTest();
    }

    protected function tearDown() {
        test_form::phpunit_reset();
        parent::tearDown();
    }

    public function test_mytest() {
        $definition = new test_definition(
            $this,
            function (model $model) {
                $selected = [
                    'one' => '1 selected',
                    'three' => '3 selected'
                ];
                $icons = [
                    'one' => '1 selected',
                    'two' => '2 selected',
                    'three' => '3 selected',
                    'four' => '4 selected'
                ];

                $ms = new multiple_select('muppet', 'blah');
                $ms->set_attribute('selected', $selected);
                $ms->set_attribute('icons', $icons);
                $model->add($ms);
            }
        );
        test_form::phpunit_set_definition($definition);

        $expected = [
            'muppet' => ['one', 'two']
        ];
        $currentdata = [
            'muppet' => '["one", "two"]'
        ];
        test_form::phpunit_set_post_data($currentdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame($expected, $data);

        $currentdata = [
            'muppet' => '["five"]'
        ];
        test_form::phpunit_set_post_data($currentdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame([], $data);
    }
}
