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

use totara_form\form\element\datetime,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\datetime class.
 */
class totara_form_element_datetime_testcase extends advanced_testcase {
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

    public function test_php_parsing() {
        $datetime = new \DateTime('2000-01-01T04:55:00', \core_date::get_user_timezone_object('Europe/Prague'));
        $this->assertSame(946698900, $datetime->getTimestamp());

        $datetime = new \DateTime('2000-01-01T04:55', \core_date::get_user_timezone_object('Europe/Prague'));
        $this->assertSame(946698900, $datetime->getTimestamp());

        $datetime = new \DateTime('2000-01-01 04:55:00', \core_date::get_user_timezone_object('Europe/Prague'));
        $this->assertSame(946698900, $datetime->getTimestamp());

        $datetime = new \DateTime('2000-01-01 04:55', \core_date::get_user_timezone_object('Europe/Prague'));
        $this->assertSame(946698900, $datetime->getTimestamp());
    }

    public function test_no_post() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var datetime $datetime1 */
                $datetime1 = $model->add(new datetime('somedatetime1', 'Some datetime 1'));
                /** @var datetime $datetime2 */
                $datetime2 = $model->add(new datetime('somedatetime2', 'Some datetime 3', 'Europe/Prague'));
                /** @var datetime $datetime3 */
                $datetime3 = $model->add(new datetime('somedatetime3', 'Some datetime 3'));
                $datetime3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('isodate' => '', 'tz' => '99'), $datetime1->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01T04:55:00', 'tz' => 'Europe/Prague'), $datetime2->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01T11:55:00', 'tz' => '99'), $datetime3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somedatetime2' => 946698900,
            'somedatetime3' => 946698900,
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var datetime $datetime1 */
                $datetime1 = $model->add(new datetime('somedatetime1', 'Some datetime 1'));
                /** @var datetime $datetime2 */
                $datetime2 = $model->add(new datetime('somedatetime2', 'Some datetime 2', 'Europe/Prague'));
                /** @var datetime $datetime3 */
                $datetime3 = $model->add(new datetime('somedatetime3', 'Some datetime 3'));
                $datetime3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('isodate' => '2000-01-01T03:55:00', 'tz' => '99'), $datetime1->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01T04:55:00', 'tz' => 'Europe/Prague'), $datetime2->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01T11:55:00', 'tz' => '99'), $datetime3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $currentdata = array(
            'somedatetime2' => 946698900,
            'somedatetime3' => 946698900,
        );
        $postdata = array(
            'somedatetime1' => array('isodate' => '2000-01-01T03:55:00', 'tz' => '99'),
            'somedatetime3' => array('isodate' => '2000-01-01T04:55:00', 'tz' => 'Europe/London'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somedatetime1' => 946670100,
            'somedatetime2' => 946698900,
            'somedatetime3' => 946698900,
        );
        $this->assertSame($expected, $data);

        // The time string may have slight modifications.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new datetime('somedatetime1', 'Some datetime 1'));
                $model->add(new datetime('somedatetime2', 'Some datetime 2'));
                $model->add(new datetime('somedatetime3', 'Some datetime 3'));
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somedatetime1' => array('isodate' => '2000-01-01 04:55:00', 'tz' => '99'),
            'somedatetime2' => array('isodate' => '2000-01-01 04:55', 'tz' => '99'),
            'somedatetime3' => array('isodate' => '2000-01-01T04:55', 'tz' => '99'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = (array)$form->get_data();
        $expected = array(
            'somedatetime1' => 946673700,
            'somedatetime2' => 946673700,
            'somedatetime3' => 946673700,
        );
        $this->assertSame($expected, $data);
    }

    public function test_validation() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var datetime $datetime1 */
                $datetime1 = $model->add(new datetime('somedatetime1', 'Some datetime 1'));

                // Test the form field values.
                $testcase->assertSame(array('isodate' => 'x2000-01-01T03:55:00', 'tz' => '99'), $datetime1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somedatetime1' => array('isodate' => 'x2000-01-01T03:55:00', 'tz' => '99'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var datetime $datetime1 */
                $datetime1 = $model->add(new datetime('somedatetime1', 'Some datetime 1'));

                // Test the form field values.
                $testcase->assertSame(array('isodate' => '', 'tz' => '99'), $datetime1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somedatetime1' => array('isodate' => array('x2000-01-01T03:55:00'), 'tz' => '99'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var datetime $datetime1 */
                $datetime1 = $model->add(new datetime('somedatetime1', 'Some datetime 1'));
                $datetime1->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('isodate' => '2000-01-01T11:55:00', 'tz' => '99'), $datetime1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'somedatetime1' => array('isodate' => 'x2000-01-01T03:55:00', 'tz' => '99'),
        );
        $currentdata = array(
            'somedatetime1' => 946698900,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somedatetime1' => 946698900,
        );
        $this->assertSame($expected, $data);
    }

    public function test_behat_normalise_value_pre_set() {
        $this->assertSame('2017-12-23T13:27', \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017-12-23T13:27'));
        $this->assertSame('2017-12-23T13:27', \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('17-12-23T13:27'));
        $this->assertSame('2017-12-23T13:27', \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017 12 23 13:27'));
        $this->assertSame('2017-12-23T13:27', \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set(' 2017-12-23T13:27 '));
        $this->assertSame('2017-12-23T13:27', \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017/12/23 13:27'));
        $this->assertSame('2017-12-23T13:27', \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017/12/23 13:27'));
        $this->assertSame('2017-12-23T15:45', \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017/12/23'));

        $tomorrow = new \DateTime();
        $tomorrow = $tomorrow->add(new \DateInterval('P1D'));
        $yesterday = new \DateTime();
        $yesterday = $yesterday->sub(new \DateInterval('P1D'));

        $someday = new \DateTime();
        $someday->add(new \DateInterval('P1Y2M10DT2H30M'));

        $this->assertStringStartsWith($tomorrow->format('Y-m-d\TH:'), \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('+P1D'));
        $this->assertStringStartsWith($tomorrow->format('Y-m-d\TH:'), \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set(' +P1D '));
        $this->assertStringStartsWith($yesterday->format('Y-m-d\TH:'), \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('-P1D'));
        $this->assertStringStartsWith($someday->format('Y-m-d\TH:'), \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('+P1Y2M10DT2H30M'));

        try {
            \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017-12-23T13:27:45');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017/12/23 13');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('2017*12*23');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('P1D');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            \totara_form\form\element\behat_helper\datetime::normalise_value_pre_set('+P1X');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
    }
}
