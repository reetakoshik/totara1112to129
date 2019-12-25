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

/**
 * Class mod_facetoface_rendering_signup_testcase
 */
class mod_facetoface_rendering_signup_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_rendering_signup_with_deleted_jobassignment(): void {
        global $PAGE, $CFG, $USER, $DB;
        $PAGE->set_url("/");

        $this->setAdminUser();
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();

        $course = $gen->create_course([], ['createsections' => true]);
        $user = $gen->create_user();
        $ja = \totara_job\job_assignment::create_default($user->id);
        $gen->enrol_user($user->id, $course->id);

        /** @var mod_facetoface_generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator("mod_facetoface");
        $f2f = $f2fgen->create_instance((object)[
            'course' => $course->id,
            'selectjobassignmentonsignup' => 1
        ]);

        $f2f = new \mod_facetoface\seminar($f2f->id);
        $event = new \mod_facetoface\seminar_event();
        $event->set_facetoface($f2f->get_id())->save();

        $session = new \mod_facetoface\seminar_session();
        $session->set_timestart(time())
            ->set_timefinish(time() + 3600)
            ->set_sessionid($event->get_id());

        $session->save();

        $signup = new \mod_facetoface\signup();
        $signup->set_sessionid($event->get_id())
            ->set_userid($user->id)
            ->set_jobassignmentid($ja->id)
            ->save();

        set_config('facetoface_selectjobassignmentonsignupglobal', 1, null);
        \mod_facetoface\signup_helper::signup($signup);

        // Hard deleting the job assignment here, so that the test check the rendering.
        $DB->delete_records('job_assignment', ['id' => $ja->id]);

        // Setting this created user in session, because we need this user to be acting as an actor
        // to load self's record.
        $this->setUser($user);

        /** @var mod_facetoface_renderer $renderer */
        $renderer = $PAGE->get_renderer('mod_facetoface');
        $rendered = $renderer->render_seminar_event($event, false);

        $this->assertContains(get_string('missingjobassignment', 'mod_facetoface'), $rendered);
    }
}
