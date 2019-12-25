<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, seminar_session, signup};
use mod_facetoface\signup\state\{not_set, booked, fully_attended, partially_attended, no_show};

class mod_facetoface_view_distinct_attendees_testcase extends advanced_testcase {
    /**
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $e = new seminar_event();
        $e->set_facetoface($f2f->id);
        $e->save();

        return $e;
    }

    /**
     * When viewing the embedded report, there is number of records are duplicated multiple times,. Therefore this test suite is
     * assuring that the records are not going to be dupliated anymore, unless there is something else different.
     *
     * @return void
     */
    public function test_viewing_distinct_attendees_in_an_event(): void {
        global $PAGE;
        $PAGE->set_url('/');

        $this->resetAfterTest();
        $this->setAdminUser();

        $e = $this->create_seminar_event();
        $times = [
            [
                'start' => time() + 3600,
                'finish' => time() + (3600 * 2),
            ],
            [
                'start' => time() + (3600 * 3),
                'finish' => time() + (3600 * 4)
            ]
        ];

        foreach ($times as $time) {
            $s = new seminar_session();
            $s->set_sessionid($e->get_id());
            $s->set_timestart($time['start']);
            $s->set_timefinish($time['finish']);
            $s->save();
        }

        $gen = $this->getDataGenerator();
        $users = [];
        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $e->get_seminar()->get_course(), 'student');

            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);
            $users[] = $user;
        }

        $cfg = new rb_config();
        $cfg->set_embeddata(
            [
                'sessionid' => $e->get_id(),
                'status' => [
                    booked::get_code(),
                    not_set::get_code(),
                    fully_attended::get_code(),
                    partially_attended::get_code(),
                    no_show::get_code()
                ]
            ]
        );

        $report = reportbuilder::create_embedded('facetoface_sessions', $cfg);
        $renderer = $PAGE->get_renderer('totara_reportbuilder');

        [$reporthtml, $debughtml] = $renderer->report_html($report, false);

        foreach ($users as $user) {
            $name = fullname($user);
            // Expecting each user should only has one record displaying here.
            $this->assertEquals(1, substr_count($reporthtml, $name));
        }

        // Adding a new columns here, so that it allows the report to duplicate the records, because the data between
        // records are tweaked to be different.
        foreach ($report->columnoptions as $columnoption) {
            if ('date' !== $columnoption->type) {
                continue;
            }

            $value = $columnoption->value;
            if (!in_array($value, ['datefinish', 'sessiondate'])) {
                continue;
            }

            $report->columns[] = $report->src->new_column_from_option(
                $columnoption->type,
                $columnoption->value,
                $columnoption->transform,
                $columnoption->aggregate
            );
        }

        unset($reporthtml);
        unset($debughtml);

        [$reporthtml, $debughtml] = $renderer->report_html($report, false);
        foreach ($users as $user) {
            $name = fullname($user);

            // Expecting each user to have 2 record rows here, because each row contains a distinct data here, therefore the report
            // should be able to show that to the viewer.
            $this->assertEquals(2, substr_count($reporthtml, $name));
        }
    }
}