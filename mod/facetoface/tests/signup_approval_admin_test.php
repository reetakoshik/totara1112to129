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
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

use mod_facetoface\{seminar, seminar_event, signup, seminar_session, signup_list};
use mod_facetoface\signup\state\{booked, requestedadmin, requested};
use totara_job\job_assignment;

/**
 * A unit test of checking whether the sign-up's state is going to switch or not, when the approval type of
 * seminar is switching in between the type of admin approval and manager approval.
 *
 * Class mod_facetoface_signup_approval_admin_testcase
 */
class mod_facetoface_signup_approval_admin_testcase extends advanced_testcase {
    /**
     * Creating a seminar with approval type as the managers and administrative approval here, with the admin Id
     * @return seminar
     * @throws coding_exception
     */
    private function create_facetoface(): seminar {
        global $USER;
        if (empty($USER->id)) {
            throw new coding_exception("Please set up the admin user first");
        }

        $generator = $this->getDataGenerator();

        $course = $this->getDataGenerator()->create_course(null, ['createsections' => true]);

        /** @var mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgenerator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_ADMIN,
            'approvaladmins' => $USER->id
        ]);

        return new seminar($f2f->id);
    }

    /**
     * @param int $numberofusers
     * @return stdClass[]
     */
    private function create_users(int $numberofusers): array {
        $generator = $this->getDataGenerator();
        $manager = $generator->create_user();
        $managerja = job_assignment::create_default($manager->id);

        $users = [];
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
     * @return seminar
     */
    private function get_facetoface(): seminar {
        $f2f = $this->create_facetoface();

        // Create event here
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($f2f->get_id())->save();

        // Create session dates here
        $time = time() + 3600;
        $session = new seminar_session();
        $session->set_sessionid($seminarevent->get_id())
            ->set_timestart($time)
            ->set_timefinish($time + 7200)
            ->save();

        return $f2f;
    }

    /**
     * @param int $numberofsignups
     * @param seminar $seminar
     * @param string $state
     * @return void
     */
    private function create_signups(int $numberofsignups, seminar $seminar, string $state): void {
        $users = $this->create_users($numberofsignups);
        $generator = $this->getDataGenerator();
        /** @var seminar_event $seminarevent */
        $seminarevent = $seminar->get_events()->current();

        /** @var stdClass $user */
        foreach ($users as $user) {
            $generator->enrol_user($user->id, $seminar->get_course());
            $signup = new signup();
            $signup->set_sessionid($seminarevent->get_id())->set_userid($user->id);
            $signup->save();
            $signup->switch_state($state);
        }
    }

    /**
     * A short summary about the test suite: Where user's were signed up as Requested state. However, when the approval type
     * of a seminar, is changed into the approval_manager (from approval_admin) the requested state should not change
     * to booked. Rather than from requested to booked, as the approval type is still a approval by anyone else
     * @throws coding_exception
     * @return void
     */
    public function test_signup_is_not_changing_state_when_approval_type_change(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $f2f = $this->get_facetoface();
        $this->create_signups(2, $f2f, requested::class);

        /** @var stdClass $stdfacetoface */
        $stdfacetoface = $DB->get_record("facetoface", ['id' => $f2f->get_id()], "*",MUST_EXIST);
        $stdfacetoface->instance = $f2f->get_id();
        // Tweaking the facetoface approvaltype here, where it is going to be seminar::APPROVAL_MANAGER, which was
        // transform from APPROVAL_ADMIN
        $stdfacetoface->approvaltype = seminar::APPROVAL_MANAGER;
        $result = facetoface_update_instance($stdfacetoface);
        if (!$result) {
            $this->fail("Unable to update facetoface instance for test");
        }

        /** @var seminar_event $seminarevent */
        $seminarevent = $f2f->get_events()->current();
        $signuplist = new signup_list(['sessionid' => $seminarevent->get_id()]);

        /** @var signup $signup */
        foreach ($signuplist as $signup) {
            $state = $signup->get_state();
            $this->assertInstanceOf(requested::class, $state);
        }
    }

    /**
     * A short summary about test suite: any sign up with the state as requested admin should move to booked,
     * if the seminar's approval type has switched from approval_admin to approval_manager. Since they got approved from
     * the manager already, therefore they should be moved to booked.
     *
     * @return void
     * @throws coding_exception
     */
    public function test_signup_is_changing_from_requested_admin_to_booked_when_approval_type_change(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $f2f = $this->get_facetoface();
        $this->create_signups(2, $f2f, requested::class);
        /** @var seminar_event $seminarevent */
        $seminarevent = $f2f->get_events()->current();
        $signups = new signup_list(['sessionid' => $seminarevent->get_id()]);

        // Changing to requestedadmin state here, so that sign up can be moved to booked state
        /** @var signup $signup */
        foreach ($signups as $signup) {
            $signup->switch_state(requestedadmin::class);
        }


        /** @var stdClass $stdfacetoface */
        $stdfacetoface = $DB->get_record("facetoface", ["id" => $f2f->get_id()], "*", MUST_EXIST);
        $stdfacetoface->instance = $f2f->get_id();

        // Switching the approval type here from APPROVAL_ADMIN to APPROVAL_MANAGER
        $stdfacetoface->approvaltype = seminar::APPROVAL_MANAGER;
        facetoface_update_instance($stdfacetoface);

        $signups->rewind();
        foreach ($signups as $signup) {
            $this->assertInstanceOf(booked::class, $signup->get_state());
        }
    }
}