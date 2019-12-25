<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

class mod_facetoface_get_previous_events_testcase extends advanced_testcase {

    public function test_previous_events_with_settings() {

        $this->resetAfterTest();
        $this->setAdminUser();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id));

        // Add past events 365 days old from now.
        $time = round((time() - YEARSECS/1.1), 0, PHP_ROUND_HALF_UP);
        for ($i = 1; $i <= 10; $i++) {
            $record = [
                'facetoface' => $facetoface->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time,
                        'timefinish' => $time + HOURSECS, // 1 hour session
                        'sessiontimezone' => 'Pacific/Auckland',
                        'roomid' => 0,
                        'assetids' => []
                    ],
                ]
            ];
            $facetofacegenerator->add_session((object)$record);
        }

        // Add past events 180 days old from now.
        $time = round((time() - YEARSECS/2.1), 0, PHP_ROUND_HALF_UP);
        for ($i = 1; $i <= 10; $i++) {
            $record = [
                'facetoface' => $facetoface->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time,
                        'timefinish' => $time + HOURSECS, // 1 hour session
                        'sessiontimezone' => 'Pacific/Auckland',
                        'roomid' => 0,
                        'assetids' => []
                    ],
                ]
            ];
            $facetofacegenerator->add_session((object)$record);
        }

        // Add past events 90 days old from now.
        $time = round((time() - (YEARSECS/2)/2.1), 0, PHP_ROUND_HALF_UP);
        for ($i = 1; $i <= 10; $i++) {
            $record = [
                'facetoface' => $facetoface->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time,
                        'timefinish' => $time + HOURSECS, // 1 hour session
                        'sessiontimezone' => 'Pacific/Auckland',
                        'roomid' => 0,
                        'assetids' => []
                    ],
                ]
            ];
            $facetofacegenerator->add_session((object)$record);
        }

        // Add past events 30 days old from now.
        $time = round((time() - ((YEARSECS/2)/2)/3.1), 0, PHP_ROUND_HALF_UP);
        for ($i = 1; $i <= 10; $i++) {
            $record = [
                'facetoface' => $facetoface->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time,
                        'timefinish' => $time + HOURSECS, // 1 hour session
                        'sessiontimezone' => 'Pacific/Auckland',
                        'roomid' => 0,
                        'assetids' => []
                    ],
                ]
            ];
            $facetofacegenerator->add_session((object)$record);
        }

        // Set Previous events time period to 'Show all previous events'
        set_config('facetoface_previouseventstimeperiod', '0');
        $sessions = facetoface_get_sessions_where_timestart($facetoface->id);
        $this->assertEquals(40, count($sessions));

        // Set Previous events time period to '365' days
        set_config('facetoface_previouseventstimeperiod', '365');
        $sessions = facetoface_get_sessions_where_timestart($facetoface->id);
        $this->assertEquals(40, count($sessions));

        // Set Previous events time period to '180' days
        set_config('facetoface_previouseventstimeperiod', '180');
        $sessions = facetoface_get_sessions_where_timestart($facetoface->id);
        $this->assertEquals(30, count($sessions));

        // Set Previous events time period to '90' days
        set_config('facetoface_previouseventstimeperiod', '90');
        $sessions = facetoface_get_sessions_where_timestart($facetoface->id);
        $this->assertEquals(20, count($sessions));

        // Set Previous events time period to '30' days
        set_config('facetoface_previouseventstimeperiod', '30');
        $sessions = facetoface_get_sessions_where_timestart($facetoface->id);
        $this->assertEquals(10, count($sessions));
    }
}