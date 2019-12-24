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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

class mod_facetoface_rendering_signup_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_rendering_signup_with_deleted_jobassignment(): void {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course([], ['createsections' => true]);

        /** @var mod_facetoface_generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $session = $f2fgen->create_session_for_course($course);

        // Update facetoface to have job assignment on signup;
        $f2f = $DB->get_record('facetoface', ['id' => $session->facetoface]);
        $f2f->selectjobassignmentonsignup = 1;
        $DB->update_record('facetoface', $f2f);

        // Set config to allow job assignment on signup.
        set_config('facetoface_selectjobassignmentonsignupglobal', 1, null);

        $user = $gen->create_user();
        $ja = \totara_job\job_assignment::create_default($user->id);

        // Signup user to the session with the job assignment here.
        facetoface_user_signup(
            $session,
            $f2f,
            $course,
            'a',
            MDL_F2F_NOTIFICATION_MANUAL,
            MDL_F2F_STATUS_BOOKED,
            $user->id,
            false,
            $USER,
            $ja
        );

        // Then we start hard deleting the job assignment of the user and start rendering the sign
        // up page.
        $DB->delete_records('job_assignment', ['id' => $ja->id]);

        // Start rendering the page.
        $submissions = facetoface_get_user_submissions(
            $f2f->id,
            $user->id,
            MDL_F2F_STATUS_REQUESTED,
            MDL_F2F_STATUS_FULLY_ATTENDED,
            $session->id
        );

        $session->bookedsession = reset($submissions);
        $rendered = facetoface_print_session($session, false);

        $this->assertContains(get_string("missingjobassignment", "facetoface"), $rendered);
    }
}
