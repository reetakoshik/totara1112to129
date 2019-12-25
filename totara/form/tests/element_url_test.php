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

use totara_form\form\element\url,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\url class.
 */
class totara_form_element_url_testcase extends advanced_testcase {
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
                /** @var url $url1 */
                $url1 = $model->add(new url('someurl1', 'Some url 1'));
                /** @var url $url2 */
                $url2 = $model->add(new url('someurl2', 'Some url 3'));
                /** @var url $url3 */
                $url3 = $model->add(new url('someurl3', 'Some url 3'));
                $url3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('', $url1->get_field_value());
                $testcase->assertSame('http://example.com/22', $url2->get_field_value());
                $testcase->assertSame('http://example.com/33', $url3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someurl2' => 'http://example.com/22',
            'someurl3' => 'http://example.com/33',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var url $url1 */
                $url1 = $model->add(new url('someurl1', 'Some url 1'));
                /** @var url $url2 */
                $url2 = $model->add(new url('someurl2', 'Some url 3'));
                /** @var url $url3 */
                $url3 = $model->add(new url('someurl3', 'Some url 3'));
                $url3->set_frozen(true);
                /** @var url $url4 */
                $url4 = $model->add(new url('someurl4', 'Some url 4'));
                $url4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame('http://example.com/111', $url1->get_field_value());
                $testcase->assertSame('http://example.com/22', $url2->get_field_value());
                $testcase->assertSame('http://example.com/33', $url3->get_field_value());
                $testcase->assertSame('', $url4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someurl1' => 'http://example.com/111',
            'someurl3' => 'http://example.com/333',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someurl2' => 'http://example.com/22',
            'someurl3' => 'http://example.com/33',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someurl1' => 'http://example.com/111',
            'someurl2' => 'http://example.com/22',
            'someurl3' => 'http://example.com/33',
            'someurl4' => null,
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_protocols() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var url $url1 */
                $url1 = $model->add(new url('someurl1', 'Some url 1'));
                /** @var url $url2 */
                $url2 = $model->add(new url('someurl2', 'Some url 3'));
                /** @var url $url3 */
                $url3 = $model->add(new url('someurl3', 'Some url 3'));
                /** @var url $url4 */
                $url4 = $model->add(new url('someurl4', 'Some url 4'));

                // Test the form field values.
                $testcase->assertSame('https://example.com/11', $url1->get_field_value());
                $testcase->assertSame('ftp://example.com/22', $url2->get_field_value());
                $testcase->assertSame('http://example.com', $url3->get_field_value());
                $testcase->assertSame('http://example.com/44', $url4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someurl1' => 'https://example.com/11',
            'someurl2' => 'ftp://example.com/22',
            'someurl3' => 'http://example.com',
            'someurl4' => 'example.com/44',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(array());
        $data = (array)$form->get_data();
        $expected = array(
            'someurl1' => 'https://example.com/11',
            'someurl2' => 'ftp://example.com/22',
            'someurl3' => 'http://example.com',
            'someurl4' => 'http://example.com/44',
        );
        $this->assertSame($expected, $data);
    }

    public function test_validation() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new url('someurl1', 'Some url 1'));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array('someurl1' => 'https://example.com/'));
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('someurl1' => 'https://example.com/'), $data);

        test_form::phpunit_set_post_data(array('someurl1' => 'example.com/'));
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('someurl1' => 'http://example.com/'), $data);

        test_form::phpunit_set_post_data(array('someurl1' => 'ftp://example.com/'));
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('someurl1' => 'ftp://example.com/'), $data);

        test_form::phpunit_set_post_data(array('someurl1' => 'http://example.com/?some[]=1'));
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('someurl1' => 'http://example.com/?some%5B%5D=1'), $data);

        $url = "http://www.example.com/?whatever='\" \t\n&bbb={1,2}&amp;c=<br>";
        $expected = clean_param($url, PARAM_URL);
        $this->assertSame($url, urldecode($expected));
        test_form::phpunit_set_post_data(array('someurl1' => $url));
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('someurl1' => $expected), $data);

        test_form::phpunit_set_post_data(array('someurl1' => ''));
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $this->assertSame(array('someurl1' => ''), $data);

        test_form::phpunit_set_post_data(array('someurl1' => 'file://example.com/'));
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        test_form::phpunit_set_post_data(array('someurl1' => 'mailto:info@example.com'));
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        test_form::phpunit_set_post_data(array('someurl1' => '  '));
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
