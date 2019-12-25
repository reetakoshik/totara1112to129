<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_manage_reserve_testcase extends \advanced_testcase {
    /**
     * Test that allocations do not depend on free space
     */
    public function test_limit_reserveinfo_to_capacity_empty() {
        $reserveinfo = $this->seed_reservations();

        $seminarevent = self::mock_seminar_event(42);
        $result = \mod_facetoface\reservations::limit_info_to_capacity_left($seminarevent, $reserveinfo, 0);

        // Allocation should not be affected
        $this->assertEquals(3, $result['allocated'][42]);
        $this->assertEquals(12, $result['maxallocate'][42]);
        $this->assertEquals(9, $result['allocate'][42]);
        // Reserve should depend on available spaces.
        $this->assertEquals(3, $result['reserved'][42]);
        $this->assertEquals(3, $result['maxreserve'][42]);
        $this->assertEquals(0, $result['reserve'][42]);
    }

    /**
     * Test that reservation is limited by non-zero capacity
     */
    public function test_limit_reserveinfo_to_capacity_one() {
        $reserveinfo = $this->seed_reservations();

        $seminarevent = self::mock_seminar_event(42);
        $result = \mod_facetoface\reservations::limit_info_to_capacity_left($seminarevent, $reserveinfo, 1);

        // Allocation should not be affected
        $this->assertEquals(3, $result['allocated'][42]);
        $this->assertEquals(12, $result['maxallocate'][42]);
        $this->assertEquals(9, $result['allocate'][42]);
        // Reserve should depend on available spaces.
        $this->assertEquals(3, $result['reserved'][42]);
        $this->assertEquals(4, $result['maxreserve'][42]);
        $this->assertEquals(1, $result['reserve'][42]);
    }


    /**
     * Test that reservation is not changed when there enough capacity
     */
    //
    public function test_limit_reserveinfo_to_capacity_enough() {
        $reserveinfo = $this->seed_reservations();

        $seminarevent = self::mock_seminar_event(42);
        $result = \mod_facetoface\reservations::limit_info_to_capacity_left($seminarevent, $reserveinfo, 10);

        // Allocation should not be affected
        $this->assertEquals(3, $result['allocated'][42]);
        $this->assertEquals(12, $result['maxallocate'][42]);
        $this->assertEquals(9, $result['allocate'][42]);
        // Reserve should depend on available spaces.
        $this->assertEquals(3, $result['reserved'][42]);
        $this->assertEquals(9, $result['maxreserve'][42]);
        $this->assertEquals(6, $result['reserve'][42]);
    }

    /**
     * @return array Reserve info
     */
    protected function seed_reservations() {
        $reserveinfo = [
            'allocated' => ['all' => 7, 42 => 3, 70 => 4],
            'reserved' => ['all' => 7, 42 => 3, 70 => 4],
        ];
        $reserveinfo = $this->mock_can_reserve_or_allocate($reserveinfo, 20);

        $this->assertEquals(3, $reserveinfo['allocated'][42]);
        $this->assertEquals(12, $reserveinfo['maxallocate'][42]);
        $this->assertEquals(9, $reserveinfo['allocate'][42]);
        // Reserve should depend on available spaces.
        $this->assertEquals(3, $reserveinfo['reserved'][42]);
        $this->assertEquals(9, $reserveinfo['maxreserve'][42]);
        $this->assertEquals(6, $reserveinfo['reserve'][42]);

        return $reserveinfo;
    }
    /**
     * This is reimplementation of logic in facetoface_can_reserve_or_allocate to provide consistent results
     * @param array $ri Reserve info with allocated and reserved keys
     * @param int $maxreserve Maximum reservation available for manager
     * @return array
     */
    protected function mock_can_reserve_or_allocate(array $ri, $maxreserve) {
        // $ri Reserve Info.
        foreach (['maxallocate', 'allocate', 'maxreserve', 'reserve'] as $initkey) {
            if (!isset($ri[$initkey])) {
                $ri[$initkey] = [];
            }
        }
        foreach ($ri['allocated'] as $key => $unused) {
            $ri['maxallocate'][$key] = $maxreserve - ($ri['allocated']['all'] - $ri['allocated'][$key]) - ($ri['reserved']['all'] - $ri['reserved'][$key]);
            $ri['allocate'][$key] = $ri['maxallocate'][$key] - $ri['allocated'][$key];
            $ri['maxreserve'][$key] = $maxreserve - $ri['allocated']['all'] - ($ri['reserved']['all'] - $ri['reserved'][$key]);
            $ri['reserve'][$key] = $ri['maxreserve'][$key] - $ri['reserved'][$key];
        }
        return $ri;
    }

    /**
     * Check that facetoface_get_users_by_status returns correct results when include reserve is used.
     */
    public function test_facetoface_get_users_by_status_include_reserve() {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $time = time();
        $day = 86400;

        $facetoface = $facetofacegenerator->create_instance(['course' => $course1->id]);
        $session1id = $facetofacegenerator->add_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time + ($day / 24) * 36,
                    'timefinish' => $time + ($day / 24) * 38,
                    'sessiontimezone' => 'Pacific/Auckland',
                ]
            ]
        ]);
        $session1 = new \mod_facetoface\seminar_event($session1id);

        \mod_facetoface\reservations::add($session1, $manager->id, 2, 0);
        $sink = $this->redirectMessages();
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($user1->id, new \mod_facetoface\seminar_event($session1->get_id()))->set_skipmanagernotification());
        $sink->close();

        $records = facetoface_get_users_by_status($session1->get_id(), \mod_facetoface\signup\state\booked::get_code(), '', true);
        $this->assertCount(3, $records);
        $signupcnt = 0;
        foreach ($records as $record) {
            if (empty($record->email)) {
                $signupcnt++;
            }
        }
        $this->assertEquals(2, $signupcnt);
    }

    /**
     * Create mock of seminar event without storing it in database.
     * It is not generator.
     * @param $id
     * @return \mod_facetoface\seminar_event
     */
    protected static function mock_seminar_event(int $id) : \mod_facetoface\seminar_event {
        $seminarevent = new \mod_facetoface\seminar_event();

        $reflection = new \ReflectionClass($seminarevent);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($seminarevent, $id);

        return $seminarevent;
    }

}