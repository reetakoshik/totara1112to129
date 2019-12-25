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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

class block_current_learning_helper_testcase extends advanced_testcase {

    /**
     * Returns a block config object with alert period at 7 days, and warning period at 6 days.
     *
     * @return stdClass
     */
    protected function get_config() {
        $config = new stdClass;
        $config->alertperiod = (60 * 60 * 24 * 7);
        $config->warningperiod = (60 * 60 * 24 * 6);
        return $config;
    }

    /**
     * Tests that when the due date is in the past we get a danger label, and the alert flag is raised.
     *
     * If the due date is in the past => danger + alert flag.
     */
    public function test_get_duedate_state_for_past_date() {

        $now = time();
        $duedate = $now - (60 * 60 * 24 * 1);

        $result = \block_current_learning\helper::get_duedate_state($duedate, $this->get_config(), $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-danger', $result['state']);
        $this->assertTrue($result['alert']);
    }

    /**
     * If: (duedate - warning period) > now < duedate => danger.
     */
    public function test_duedate_state_well_within_warning_period() {

        $now = time();
        $duedate = $now + (60 * 60 * 24 * 1);

        $result = \block_current_learning\helper::get_duedate_state($duedate, $this->get_config(), $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-danger', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: (duedate - warning period) > now < duedate => danger.
     */
    public function test_duedate_state_just_within_warning_period() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + $config->warningperiod - 60;

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-danger', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: (duedate - warning period) = now < duedate => danger.
     */
    public function test_duedate_state_on_warning_period() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + $config->warningperiod;

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-danger', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: (duedate - alert period)   > now < (duedate - warning period)   => warning.
     */
    public function test_duedate_state_just_past_warning_period() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + $config->warningperiod + 60;

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-warning', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: (duedate - alert period)   > now < (duedate - warning period)   => warning.
     */
    public function test_duedate_state_just_within_alert_period() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + $config->alertperiod - 60;

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-warning', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: (duedate - alert period)   = now < (duedate - warning period)   => warning.
     */
    public function test_duedate_state_on_alert_period() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + $config->alertperiod;

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-warning', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: now < (duedate - alert period) => info.
     */
    public function test_duedate_state_just_before_alert_period() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + $config->alertperiod + 60;

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-info', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: now < (duedate - alert period) => info.
     */
    public function test_duedate_state_past_both_periods() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + (60 * 60 * 24 * 8);

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-info', $result['state']);
        $this->assertFalse($result['alert']);
    }

    /**
     * If: now < (duedate - alert period) => info.
     */
    public function test_duedate_state_in_distant_future() {

        $now = time();
        $config = $this->get_config();
        $duedate = $now + (60 * 60 * 24 * 90);

        $result = \block_current_learning\helper::get_duedate_state($duedate, $config, $now);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['state', 'alert'], array_keys($result), 'Resulting array does not contain the expected keys', 0.0, 10, true);
        $this->assertSame('label-info', $result['state']);
        $this->assertFalse($result['alert']);
    }
}
