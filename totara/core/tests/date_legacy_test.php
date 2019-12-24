<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_date_legacy_testcase extends advanced_testcase {
    public function test_totara_date_parse_from_format() {
        global $USER;

        $this->resetAfterTest();

        $timezones = core_date::get_list_of_timezones(null, true);
        $timezones = array_keys($timezones);

        $dates = array(
            array(2, 1, 0, 40, 40),
            array(4, 3, 0, 30, 22),
            array(9, 5, 0, 20, 19),
            array(11, 28, 0, 10, 45),
        );
        $years = array(1999, 2009, 2014, 2018);

        $format = 'j. n. Y. H:i:s';

        // First getting using user timezone.

        foreach ($timezones as $tz) {
            $this->setTimezone('Pacific/Auckland', 'Pacific/Auckland');
            $USER->timezone = $tz;
            foreach ($years as $year) {
                foreach ($dates as $date) {
                    $expected = new DateTime('now', new DateTimeZone(($tz == 99 ? 'Pacific/Auckland' : $tz)));
                    $expected->setDate($year, $date[0], $date[1]);
                    $expected->setTime($date[2], $date[3], $date[4]);
                    $string = $expected->format($format);
                    $result = totara_date_parse_from_format($format, $string);
                    $this->assertSame($expected->getTimestamp(), $result, "$string in $tz is expected to have different timestamp");
                    $result = totara_date_parse_from_format($format, $string);
                    $this->assertSame($expected->getTimestamp(), $result, "$string in $tz is expected to have different timestamp");
                }
            }
        }

        foreach ($timezones as $tz) {
            $this->setTimezone('99', 'Pacific/Auckland');
            $USER->timezone = $tz;
            foreach ($years as $year) {
                foreach ($dates as $date) {
                    $expected = new DateTime('now', new DateTimeZone(($tz == 99 ? 'Pacific/Auckland' : $tz)));
                    $expected->setDate($year, $date[0], $date[1]);
                    $expected->setTime($date[2], $date[3], $date[4]);
                    $string = $expected->format($format);
                    $result = totara_date_parse_from_format($format, $string);
                    $this->assertSame($expected->getTimestamp(), $result, "$string in $tz is expected to have different timestamp");
                    $result = totara_date_parse_from_format($format, $string);
                    $this->assertSame($expected->getTimestamp(), $result, "$string in $tz is expected to have different timestamp");
                }
            }
        }

        // Next in server default timezone.

        foreach ($timezones as $tz) {
            $this->setTimezone($tz, $tz == 99 ? 'Pacific/Auckland' : $tz);
            $USER->timezone = 'Europe/Paris';
            foreach ($years as $year) {
                foreach ($dates as $date) {
                    $expected = new DateTime('now', new DateTimeZone(($tz == 99 ? 'Pacific/Auckland' : $tz)));
                    $expected->setDate($year, $date[0], $date[1]);
                    $expected->setTime($date[2], $date[3], $date[4]);
                    $string = $expected->format($format);
                    $result = totara_date_parse_from_format($format, $string, true);
                    $this->assertSame($expected->getTimestamp(), $result, "$string in $tz is expected to have different timestamp");
                }
            }
        }

        foreach ($timezones as $tz) {
            $this->setTimezone('99', $tz == 99 ? 'Pacific/Auckland' : $tz);
            $USER->timezone = 'Europe/Paris';
            foreach ($years as $year) {
                foreach ($dates as $date) {
                    $expected = new DateTime('now', new DateTimeZone(($tz == 99 ? 'Pacific/Auckland' : $tz)));
                    $expected->setDate($year, $date[0], $date[1]);
                    $expected->setTime($date[2], $date[3], $date[4]);
                    $string = $expected->format($format);
                    $result = totara_date_parse_from_format($format, $string, true);
                    $this->assertSame($expected->getTimestamp(), $result, "$string in $tz is expected to have different timestamp");
                }
            }
        }

        // Test ignoring of integers.
        $result = totara_date_parse_from_format('j. n. Y', '158803200', true);
        $this->assertEmpty(0, $result);

        // Test timezone forcing.
        $result = totara_date_parse_from_format('j. n. Y', '13. 1. 1975', true, 'UTC');
        $this->assertSame(158803200, $result);
    }

    public function test_totara_get_clean_timezone_list() {
        $phpzones = DateTimeZone::listIdentifiers();

        $zones = totara_get_clean_timezone_list();
        foreach ($zones as $k => $v) {
            $this->assertInternalType('integer', $k);
            $this->assertContains($v , $phpzones);
        }

        $zones = totara_get_clean_timezone_list(true);
        foreach ($zones as $k => $v) {
            $this->assertSame($v, $k);
            $this->assertContains($v , $phpzones);
        }
    }
}

