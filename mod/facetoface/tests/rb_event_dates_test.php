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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_rb_event_dates_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_event_time_no_timezone() {
        global $CFG;

        $CFG->facetoface_displaysessiontimezones = false;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook'))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        // Testing display function.
        $display = \mod_facetoface\rb\display\event_time::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_time::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_time::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);
    }

    public function test_event_time_timezone() {
        global $CFG;

        $CFG->facetoface_displaysessiontimezones = true;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook',array('extrafields' => array('timezone' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();
        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = 'Australia/Perth';

        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \mod_facetoface\rb\display\event_time::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('4:25 PM Pacific/Auckland', $display);

        $display = \mod_facetoface\rb\display\event_time::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('4:25 PM Pacific/Auckland', $display);

        $display = \mod_facetoface\rb\display\event_time::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $row->$extrafieldrow = 'Australia/Perth';
        $display = \mod_facetoface\rb\display\event_time::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('11:25 AM Australia/Perth', $display);

        $display = \mod_facetoface\rb\display\event_time::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('11:25 AM Australia/Perth', $display);

        $display = \mod_facetoface\rb\display\event_time::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    public function test_event_date_timezone() {
        global $CFG;

        $CFG->facetoface_displaysessiontimezones = true;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();
        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = 'Australia/Perth';

        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \mod_facetoface\rb\display\event_date::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);

        $display = \mod_facetoface\rb\display\event_date::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland', $display);

        $display = \mod_facetoface\rb\display\event_date::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $row->$extrafieldrow = 'Australia/Perth';
        $display = \mod_facetoface\rb\display\event_date::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);

        $display = \mod_facetoface\rb\display\event_date::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth', $display);

        $display = \mod_facetoface\rb\display\event_date::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    public function test_event_date_no_timezone() {
        global $CFG;

        $CFG->facetoface_displaysessiontimezones = false;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();
        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = 'Australia/Perth';

        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \mod_facetoface\rb\display\event_date::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_date::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_date::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $row->$extrafieldrow = 'Australia/Perth';
        $display = \mod_facetoface\rb\display\event_date::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_date::display('1514345115', $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_date::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    public function test_event_dates_period_timezone() {
        global $CFG;

        $CFG->facetoface_displaysessiontimezones = true;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Two dates.
        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true, 'finishdate' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'finishdate');
        $row->$extrafieldrow = 1514345115 + 86400;

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = 'Australia/Perth';

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 4:25 PM Pacific/Auckland to 28 December 2017, 4:25 PM Pacific/Auckland', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('Before 28 December 2017, 4:25 PM Pacific/Auckland', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Australia/Perth';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM Australia/Perth to 28 December 2017, 11:25 AM Australia/Perth', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('Before 28 December 2017, 11:25 AM Australia/Perth', $display);

        // Reset.
        $CFG->forcetimezone = '99';

        // Start date only.
        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true, 'finishdate' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'finishdate');
        $row->$extrafieldrow = null;

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = 'Australia/Perth';

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 4:25 PM Pacific/Auckland', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Australia/Perth';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 11:25 AM Australia/Perth', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }

    public function test_event_dates_period_no_timezone() {
        global $CFG;

        $CFG->facetoface_displaysessiontimezones = false;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create report.
        $rid = $this->create_report('facetoface_events', 'Test f2f events');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Two dates.
        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true, 'finishdate' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'finishdate');
        $row->$extrafieldrow = 1514345115 + 86400;

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = 'Australia/Perth';

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM to 28 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('Before 28 December 2017, 11:25 AM', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Australia/Perth';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('27 December 2017, 11:25 AM to 28 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('Before 28 December 2017, 11:25 AM', $display);

        // Reset.
        $CFG->forcetimezone = '99';

        // Start date only.
        // Mock objects to use in the display function.
        $column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('session', 'overbookingallowed', 'overbooking', 'overbook', array('extrafields' => array('timezone' => true, 'finishdate' => true))))
            ->getMock();
        $format = "html";
        $row = new stdClass();

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'finishdate');
        $row->$extrafieldrow = null;

        self::assertEquals('99', $CFG->forcetimezone);

        // Testing display function.
        $CFG->forcetimezone = 'Australia/Perth';

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Pacific/Auckland';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        $extrafieldrow = reportbuilder_get_extrafield_alias($column->type, $column->value, 'timezone');
        $row->$extrafieldrow = 'Australia/Perth';
        $display = \mod_facetoface\rb\display\event_dates_period::display(1514345115, $format, $row, $column, $report);
        $this->assertEquals('After 27 December 2017, 11:25 AM', $display);

        $display = \mod_facetoface\rb\display\event_dates_period::display('blah', $format, $row, $column, $report);
        $this->assertEquals('', $display);

        // Reset.
        $CFG->forcetimezone = '99';
    }
}