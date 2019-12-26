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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\bulk_list;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\seminar;
use mod_facetoface\form\attendees_add_confirm;
use mod_facetoface\attendees_list_helper;

defined('MOODLE_INTERNAL') || die();

class grading_report_testcase extends advanced_testcase {

    public function data_provider_waiter() {
        return [ [1, 44], [ 51, 94 ] ];
    }

    /**
     * @param integer $first
     * @param integer $last
     * @dataProvider data_provider_waiter
     * @large
     */
    public function test_grading_report_by_simulating_add_users_in_attendance_page($first, $last) {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        /** @var mod_facetoface_generator $f2f_gen */
        $f2f_gen = $gen->get_plugin_generator('mod_facetoface');

        $course = $gen->create_course([], ['createsections' => true]);
        $sem = new seminar($f2f_gen->create_instance((object)['course' => $course->id])->id);

        $se = new seminar_event();
        $se->set_facetoface($sem->get_id());
        $se->save();

        $time = time() + DAYSECS;
        $ss = new seminar_session();
        $ss->set_sessionid($se->get_id());
        $ss->set_timestart($time);
        $ss->set_timefinish($time + HOURSECS);
        $ss->save();

        $user = $gen->create_user(['firstname' => 'One', 'lastname' => 'Uno']);

        if (!isset($USER->gradeediting)) {
            $USER->gradeediting = [];
        }
        $USER->gradeediting[$course->id] = false;

        $listid = sprintf('f2f%x', random_int(0, PHP_INT_MAX));
        $currenturl = new moodle_url('/mod/facetoface/attendees/list/add.php', array('s' => $sem->get_id(), 'listid' => $listid));

        $list = new bulk_list($listid, $currenturl, 'add');
        $list->set_user_ids([ $user->id ]);

        // simulate the continue button in mod/facetoface/attendees/list/add.php
        $signup = signup::create($user->id, $se);
        $signup->set_ignoreconflicts(false);

        // can_signup() returns false because the user has not been enroled yet
        $this->assertFalse(signup_helper::can_signup($signup));
        $signuperrors = signup_helper::get_failures($signup);
        $this->assertArrayHasKey('user_is_enrolable', $signuperrors);

        $list->set_validaton_results([]);

        // simulate the confirm button mod/facetoface/attendees/list/addconfirm.php
        $list = new bulk_list($listid);
        $userlist = $list->get_user_ids();
        $this->assertNotCount(0, $userlist);

        $formdata = [
            's' => $se->get_id(),
            'listid' => $listid,
            'notifyuser' => 1,
            'notifymanager' => 1,
        ];
        attendees_add_confirm::mock_submit($formdata);

        $isnotificationactive = facetoface_is_notification_active(MDL_F2F_CONDITION_BOOKING_CONFIRMATION, $sem->get_id(), true);
        $mform = new attendees_add_confirm(null, [
            's' => $se->get_id(),
            'listid' => $listid,
            'isapprovalrequired' => $sem->is_approval_required(),
            'enablecustomfields' => !$list->has_user_data(),
            'ignoreconflicts' => 0,
            'is_notification_active' => $isnotificationactive
        ]);

        $data = $mform->get_data();

        // wait for the test to be executed in the specific time window
        $i = 0;
        $frac = time() % 100;
        while (!($first <= $frac && $frac <= $last)) {
            if ($i++ > 100) {
                $this->fail('Cannot simulate the time window required for this test.');
            }
            sleep(1);
            $frac = time() % 100;
        }
        attendees_list_helper::add($data);

        $ue = $DB->get_records('user_enrolments', array('userid' => $user->id));
        $this->assertCount(1, $ue);

        // simulate grade/report/grader/index.php
        $courseid = $course->id;
        $course = $DB->get_record('course', array('id' => $courseid));
        $this->assertNotNull($course);

        $context = context_course::instance($course->id);
        $page = 0;
        $sortitemid = 0;
        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => $page));
        grade_regrade_final_grades_if_required($course);

        $report = new grade_report_grader($courseid, $gpr, $context, $page, $sortitemid);
        $numusers = $report->get_numusers(true, true);
        $report->load_users();
        $report->load_final_grades();
        $totalusers = $report->get_numusers(true, false);
        $studentsperpage = $report->get_students_per_page();

        $this->assertEquals(1, $numusers);
        $this->assertEquals(1, $totalusers);

        // this conditional branch is from grade/report/grader/index.php
        // though it is never executed in the test case, leave it
        $displayaverages = true;
        if ($numusers == 0) {
            $displayaverages = false;
        }
        $html = $report->get_grade_table($displayaverages);

        // fix incorrectly escaped html entities
        $html = str_replace('scope="col" scope="col"', 'scope="col"', $html);
        $html = preg_replace('/\&(?=(target|sesskey|action|item|itemid|id))/', '&amp;', $html);
        $doc = new DOMDocument();
        $doc->loadHTML($html);

        // the first two rows are headers even though they are in <tbody>
        $trs = $doc
            ->getElementsByTagName('table')[0]
            ->getElementsByTagName('tbody')[0]
            ->getElementsByTagName('tr');
        $this->assertCount(4, $trs);

        // the third row contains a user's full name
        $username = $trs[2]
            ->getElementsByTagName('th')[0]
            ->getElementsByTagName('a')[1]
            ->nodeValue;
        $this->assertEquals(fullname($user), $username);
    }
}
