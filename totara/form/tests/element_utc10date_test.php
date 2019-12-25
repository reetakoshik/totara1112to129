<?php
/*
 * This file is part of Totara LMS
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_form
 */

use totara_form\form\element\utc10date,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form;

/**
 * Test for \totara_form\form\element\utc10date class.
 */
class totara_form_element_utc10date_testcase extends advanced_testcase {
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
        $utc10date = new \DateTime('2000-01-01T04:55:00', \core_date::get_user_timezone_object('UTC'));
        $this->assertSame(946702500, $utc10date->getTimestamp());

        $utc10date = new \DateTime('2000-01-01T04:55', \core_date::get_user_timezone_object('UTC'));
        $this->assertSame(946702500, $utc10date->getTimestamp());

        $utc10date = new \DateTime('2000-01-01 04:55:00', \core_date::get_user_timezone_object('UTC'));
        $this->assertSame(946702500, $utc10date->getTimestamp());

        $utc10date = new \DateTime('2000-01-01 04:55', \core_date::get_user_timezone_object('UTC'));
        $this->assertSame(946702500, $utc10date->getTimestamp());

        $utc10date = new \DateTime('2000-01-01T10:00:00', \core_date::get_user_timezone_object('UTC'));
        $this->assertSame(946720800, $utc10date->getTimestamp());

        $utc10date = new \DateTime('2000-01-02T10:00:00', \core_date::get_user_timezone_object('UTC'));
        $this->assertSame(946807200, $utc10date->getTimestamp());

        $utc10date = new \DateTime('@946720800');
        $this->assertSame('+00:00', $utc10date->getTimezone()->getName());
    }

    public function test_no_post() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var utc10date $utc10date1 */
                $utc10date1 = $model->add(new utc10date('someutc10date1', 'Some utc10date 1'));
                /** @var utc10date $utc10date2 */
                $utc10date2 = $model->add(new utc10date('someutc10date2', 'Some utc10date 2'));
                /** @var utc10date $utc10date3 */
                $utc10date3 = $model->add(new utc10date('someutc10date3', 'Some utc10date 3'));
                $utc10date3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('isodate' => ''), $utc10date1->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01'), $utc10date2->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01'), $utc10date3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someutc10date2' => 946702500,
            'someutc10date3' => 946720800,
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var utc10date $utc10date1 */
                $utc10date1 = $model->add(new utc10date('someutc10date1', 'Some utc10date 1'));
                /** @var utc10date $utc10date2 */
                $utc10date2 = $model->add(new utc10date('someutc10date2', 'Some utc10date 2'));
                /** @var utc10date $utc10date3 */
                $utc10date3 = $model->add(new utc10date('someutc10date3', 'Some utc10date 3'));
                $utc10date3->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('isodate' => '2000-01-01'), $utc10date1->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01'), $utc10date2->get_field_value());
                $testcase->assertSame(array('isodate' => '2000-01-01'), $utc10date3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $currentdata = array(
            'someutc10date2' => 946702500,
            'someutc10date3' => 946720800,
        );
        $postdata = array(
            'someutc10date1' => array('isodate' => '2000-01-01'),
            'someutc10date3' => array('isodate' => '2000-01-01'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someutc10date1' => 946720800,
            'someutc10date2' => 946720800,
            'someutc10date3' => 946720800,
        );
        $this->assertSame($expected, $data);
    }

    public function test_validation() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var utc10date $utc10date1 */
                $utc10date1 = $model->add(new utc10date('someutc10date1', 'Some utc10date 1'));

                // Test the form field values.
                $testcase->assertSame(array('isodate' => 'x2000-01-01'), $utc10date1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'someutc10date1' => array('isodate' => 'x2000-01-01'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var utc10date $utc10date1 */
                $utc10date1 = $model->add(new utc10date('someutc10date1', 'Some utc10date 1'));

                // Test the form field values.
                $testcase->assertSame(array('isodate' => ''), $utc10date1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'someutc10date1' => array('isodate' => array('x2000-01-01')),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var utc10date $utc10date1 */
                $utc10date1 = $model->add(new utc10date('someutc10date1', 'Some utc10date 1'));
                $utc10date1->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('isodate' => '2000-01-01'), $utc10date1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);
        $postdata = array(
            'someutc10date1' => array('isodate' => 'x2000-01-01'),
        );
        $currentdata = array(
            'someutc10date1' => 946698900,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someutc10date1' => 946698900,
        );
        $this->assertSame($expected, $data);
    }

    public function test_behat_normalise_value_pre_set() {
        $this->assertSame('2017-12-23', \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('2017/12/23'));
        $this->assertSame('2017-12-23', \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('2017-12-23'));
        $this->assertSame('2017-12-23', \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('2017 12 23'));
        $this->assertSame('2017-12-23', \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('17/12/23'));
        $this->assertSame('1975-01-01', \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set(' 1975/1/1 '));

        $tomorrow = new \DateTime();
        $tomorrow = $tomorrow->add(new \DateInterval('P1D'));
        $yesterday = new \DateTime();
        $yesterday = $yesterday->sub(new \DateInterval('P1D'));

        $someday = new \DateTime();
        $someday->add(new \DateInterval('P1Y2M10DT2H30M'));

        $this->assertSame($tomorrow->format('Y-m-d'), \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('+P1D'));
        $this->assertSame($tomorrow->format('Y-m-d'), \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set(' +P1D '));
        $this->assertSame($yesterday->format('Y-m-d'), \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('-P1D'));
        $this->assertSame($someday->format('Y-m-d'), \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('+P1Y2M10DT2H30M'));

        try {
            \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('2017*12*23');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('P1D');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            \totara_form\form\element\behat_helper\utc10date::normalise_value_pre_set('+P1X');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
    }
}
