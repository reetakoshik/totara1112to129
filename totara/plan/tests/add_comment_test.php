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
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot."/totara/plan/lib.php");

/**
 * A unit test for checking whether the email is
 * sending or not base on the user's preference
 * when a new comment is added to their plan
 * Class add_comment_test
 */
class add_comment_test extends advanced_testcase {
    /**
     * @param stdClass $user
     * @return stdClass
     * @throws dml_exception
     */
    private function create_plan(\stdClass $user): stdClass {
        global $DB;
        $plan = [
            'templateid' => 1,
            'name' => 'plan 1',
            'userid' => $user->id,
            'startdate' => strtotime("2018-01-02"),
            'enddate' => strtotime("2018-01-03"),
            'status' => 50,
            'createdby' => 0
        ];

        $id = $DB->insert_record("dp_plan", (object) $plan);
        $plan['id'] = $id;

        return (object) $plan;
    }

    /**
     * @param stdClass  $user
     * @param bool      $issendingemail
     * @throws dml_exception
     */
    private function prepare_user_preferences(\stdClass $user, $issendingemail=false): void {
        global $DB;

        $value = $issendingemail ? "email" : "none";
        $DB->insert_record("user_preferences", (object)[
            'userid' => $user->id,
            'name' => \totara_plan\add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDIN,
            'value' => $value
        ]);

        $DB->insert_record("user_preferences", (object)[
            'userid' => $user->id,
            'name' => \totara_plan\add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDOFF,
            'value' => $value
        ]);
    }

    private function create_comment(\stdClass $user, \stdClass $plan): stdClass {
        $comment = new \stdClass();
        $comment->commentarea = "plan_overview";
        $comment->contextid = 1;
        $comment->component ="totara_plan";
        $comment->content = "Hello world";
        $comment->itemid = $plan->id;
        $comment->userid = $user->id;
        $comment->timecreated=time();

        return $comment;
    }

    /**
     * The test suite of adding a new comment,
     * and the method would check for user's preferences
     * to determine whether sending email or not. The result should be not sending
     *
     * If the notification is sending out, then the table messages should have new row
     */
    public function test_not_sending_email(): void {
        global $DB;
        $sql = /** @lang text */"SELECT * FROM {message}";
        $DB->get_counted_records_sql($sql, null, 0, 0, $before);

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->prepare_user_preferences($user);
        $plan = $this->create_plan($user);

        $comment = $this->create_comment($user, $plan);
        totara_plan_comment_add($comment);

        $DB->get_counted_records_sql($sql, null, 0, 0, $after);
        $this->assertEquals($before, $after);
    }
}