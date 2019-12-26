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
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

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
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $sessiondata['datetimeknown'] = '1';
        $session = facetoface_get_session($sessionid);
        $seminarevent = new \mod_facetoface\seminar_event($sessionid);
        $seminar = $seminarevent->get_seminar();

        // Allocate to session by manager.
        $this->setUser($manager);
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($user1->id, new \mod_facetoface\seminar_event($session->id)));
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($user2->id, new \mod_facetoface\seminar_event($session->id)));

        $this->execute_adhoc_tasks();
        $sink = $this->redirectMessages();
        \mod_facetoface\reservations::remove_allocations($seminarevent, $seminar, array($user1->id), true, $manager->id);
        $this->execute_adhoc_tasks();
        $this->assertSame(1, $sink->count());
        $messages = $sink->get_messages();
        $sink->clear();

        $this->assertContains('BOOKING CANCELLED', $messages[0]->fullmessage);
        $this->assertEquals($user1->id, $messages[0]->useridto);

        $sink = $this->redirectMessages();
        \mod_facetoface\reservations::remove_allocations($seminarevent, $seminar, array($user2->id), false, $manager->id);
        $this->execute_adhoc_tasks();
        $this->assertSame(1, $sink->count());
        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertContains('BOOKING CANCELLED', $messages[0]->fullmessage);
        $this->assertEquals($user2->id, $messages[0]->useridto);
    }
}
