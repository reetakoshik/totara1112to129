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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests reservation functions
 */
class mod_facetoface_reservation_testcase extends advanced_testcase {
    /**
     * Check that users deallocated correctly
     */
    public function test_facetoface_remove_allocations() {
        $this->resetAfterTest(true);

        $manager = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array(
            'course' => $course->id,
            'multiplesessions' => 1,
            'managerreserve' => 1,
            'maxmanagerreserves' => 2
        ));
        // Create session.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 5,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'datetimeknown' => 1
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // Allocate to session by manager.
        $this->setUser($manager);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user1->id);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user2->id);

        $sink = $this->redirectMessages();
        facetoface_remove_allocations($session, $facetoface, $course, array($user1->id), true, $manager->id);
        $this->assertSame(1, $sink->count());
        $messages = $sink->get_messages();
        $sink->clear();

        $this->assertContains('BOOKING CANCELLED', $messages[0]->fullmessage);
        $this->assertEquals($user1->id, $messages[0]->useridto);

        $sink = $this->redirectMessages();
        facetoface_remove_allocations($session, $facetoface, $course, array($user2->id), false, $manager->id);
        $this->assertSame(1, $sink->count());
        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertContains('BOOKING CANCELLED', $messages[0]->fullmessage);
        $this->assertEquals($user2->id, $messages[0]->useridto);
    }
}
