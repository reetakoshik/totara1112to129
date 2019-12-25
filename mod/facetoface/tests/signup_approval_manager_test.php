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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
use mod_facetoface\{seminar, seminar_event, signup, seminar_session, signup_list};
use mod_facetoface\signup\state\{requested, declined};
use totara_job\job_assignment;


/**
 * Class mod_facetoface_signup_approval_manager_testcase
 */
class mod_facetoface_signup_approval_manager_testcase extends advanced_testcase {
    /**
     * @return seminar
     */
    private function create_facetoface(): seminar {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(null, ['createsections' => true]);

        /** @var mod_facetoface_generator $f2fgen */
        $f2fgen = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_MANAGER
        ]);

        return new seminar($f2f->id);
    }

    /**
     * @param int $numberofusers
     * @return array
     */
    private function create_users(int $numberofusers): array {
        $generator = $this->getDataGenerator();

        // Creating manager here
        $manager = $generator->create_user();
        $managerja = job_assignment::create_default($manager->id);

        $users = array();

        for ($i = 0; $i < $numberofusers; $i++) {
            $user = $generator->create_user();
            $jobassignment = job_assignment::create_default($user->id, [
                'managerjaid' => $managerja->id
            ]);

            $user->jobassignment = $jobassignment;
            $users[] = $user;
        }
        return $users;
    }

    /**
     * @param int $numberofsignups
     * @param seminar $seminar
     * @return void
     */
    private function create_signups_with_requested_state(int $numberofsignups, seminar $seminar): void {
        $generator = $this->getDataGenerator();

        $users = $this->create_users($numberofsignups);
        $event = new seminar_event();
        $event->set_facetoface($seminar->get_id())->save();

        $time = time() + 3600;
        $sessiondate = new seminar_session();
        $sessiondate->set_sessionid($event->get_id())
            ->set_timestart($time)
            ->set_timefinish($time + 7200)
            ->save();

        foreach ($users as $user) {
            $generator->enrol_user($user->id, $seminar->get_course());
            $signup = new signup();
            $signup->set_userid($user->id)
                ->set_sessionid($event->get_id())
                ->save();

            $signup->switch_state(requested::class);
        }
    }

    /**
     * @return void
     */
    public function test_declined_user_is_able_to_re_signup(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $seminar = $this->create_facetoface();
        $this->create_signups_with_requested_state(2, $seminar);

        /** @var seminar_event $seminarevent */
        $seminarevent = $seminar->get_events()->current();
        $signups = new signup_list(['sessionid' => $seminarevent->get_id()]);

        // Switching the state of signup user into declined here, so that we can run the test of switching back to requested
        /** @var signup $signup */
        foreach ($signups as $signup) {
            $signup->switch_state(declined::class);
        }

        $signups->rewind();

        // Moving all the sign up back into requested state here, and assertion is happening from here as well
        /** @var signup $signup */
        foreach ($signups as $signup) {
            $this->assertInstanceOf(declined::class, $signup->get_state());

            $signup->switch_state(requested::class);
            $this->assertInstanceOf(requested::class, $signup->get_state());
        }
    }
}
