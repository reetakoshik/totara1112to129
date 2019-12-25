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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package calendartype_gregorian
 */

/**
 * Gregorian calendar class tests.
 */
class calendartype_gregorian_structure_testcase extends advanced_testcase {
    /**
     * Totara: make sure order matches regular full date/time display format.
     */
    public function test_get_date_order() {
        $this->resetAfterTest();

        $calendar = new \calendartype_gregorian\structure();

        $dateinfo = array();
        $dateinfo['day'] = $calendar->get_days();
        $dateinfo['month'] = $calendar->get_months();
        $dateinfo['year'] = $calendar->get_years(2000, 2030);

        $result = $calendar->get_date_order(2000, 2030);
        $this->assertSame($dateinfo, $result);

        // Verify the 'en' lang pack did not change.
        $this->assertSame('%A, %d %B %Y, %I:%M %p', get_string('strftimedaydatetime', 'langconfig'));

        // Switched en_US.
        $this->overrideLangString('strftimedaydatetime', 'langconfig', '%A, %B %d, %Y, %I:%M %p');
        $result = $calendar->get_date_order(2000, 2030);
        $this->assertSame(array('month' => $dateinfo['month'], 'day' => $dateinfo['day'], 'year' => $dateinfo['year']), $result);

        // All reversed.
        $this->overrideLangString('strftimedaydatetime', 'langconfig', '%Y %A %B %d %I:%M %p');
        $result = $calendar->get_date_order(2000, 2030);
        $this->assertSame(array('year' => $dateinfo['year'], 'month' => $dateinfo['month'], 'day' => $dateinfo['day']), $result);

        // Invalid - missing %Y.
        $this->overrideLangString('strftimedaydatetime', 'langconfig', '%A, %B %d, %I:%M %p');
        $result = $calendar->get_date_order(2000, 2030);
        $this->assertSame($dateinfo, $result);

        // Invalid - missing all %.
        $this->overrideLangString('strftimedaydatetime', 'langconfig', 'A B d Y I M p');
        $result = $calendar->get_date_order(2000, 2030);
        $this->assertSame($dateinfo, $result);
    }
}