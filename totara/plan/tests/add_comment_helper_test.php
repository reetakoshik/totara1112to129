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

use totara_plan\add_comment_helper;

/**
 * Class add_comment_helper_test
 * Unit test for assuring that the logic
 * operation is working properly
 */
class add_comment_helper_test extends advanced_testcase
{
    /**
     * Method of creating the user with the custom last access value
     *
     * @param int                       $lastaccess     The epox time
     * @return stdClass
     */
    private function create_user($lastaccess): stdClass {
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'kian',
            'lastname' => 'batman',
            'lastaccess'=> $lastaccess
        ]);

        return $user;
    }

    /**
     * @param stdClass $user
     * @param array $entries
     * @throws dml_exception
     */
    private function create_user_preferences(stdClass $user, array $entries): void {
        global $DB;

        foreach ($entries as $entry) {
            $entry['userid'] = $user->id;
            $DB->insert_record("user_preferences", (object)$entry);
        }
    }

    /**
     * @return array
     */
    public function get_user_state_data(): array {
        return [
            ["2018-02-10 20:25:25", "2018-02-10 20:25:55", "loggedin"],
            ["2018-02-10 20:25:25", "2018-02-10 20:27:40", "loggedoff"]
        ];
    }

    /**
     * @param stdClass $user
     * @throws dml_exception
     */
    public function create_user_session(stdClass $user): void {
        global $DB;
        $time = time();
        $DB->insert_record("sessions", (object)[
            'state' => 0,
            'sid' => uniqid(),
            'userid' => $user->id,
            'timecreated' => $time,
            'timemodified' => $time,
            'firstip' => '127.0.0,1',
            'lastip' => '127.0.0.1',
        ]);
    }

    /**
     * Test suite of checking the logic of getting user state
     * base on different set of data
     *
     * @dataProvider get_user_state_data
     *
     * @param string $lastaccess
     * @param string $now
     * @param string $userstate
     *
     * @return void
     */
    public function test_userstate_within_session(string $lastaccess, string $now, string $userstate): void {
        global $CFG;
        $CFG->block_online_users_timetosee = null;

        $this->resetAfterTest(true);

        $user = $this->create_user(strtotime($lastaccess));
        $this->create_user_session($user);
        $helper = new add_comment_helper($user);

        $result = $helper->get_user_state(strtotime($now), 2);
        $this->assertEquals($userstate, $result);
    }

    /**
     * Test suit of validating the user preference of
     * class add_comment_helper
     *
     * As the method would throw the exception,
     * if there are more than one user preferences
     *
     * @see add_comment_helper::validate
     */
    public function test_validation(): void {
        $this->resetAfterTest(true);
        $user = $this->create_user(time());

        $helper = new add_comment_helper($user);
        $helper->add_user_preference("hello_world", 1);
        $helper->add_user_preference("hello_world2",2);
        $helper->add_user_preference("hello_world3",3);

        $refclass = new ReflectionClass($helper);
        $method = $refclass->getMethod("validate");
        $method->setAccessible(true);

        $this->expectException(\coding_exception::class);
        $method->invoke($helper);
    }

    /**
     * @return array
     */
    public function provide_sending_email_notification_checker_data(): array {
        $loggedoff = add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDOFF;
        $loggedin  = add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDIN;

        return [
            [
                '2018-06-10 20:25:25',
                '2018-06-10 20:26:25',
                [
                    [
                        'name' => $loggedoff,
                        'value' => 'email',
                    ],
                    [
                        'name' => $loggedin,
                        'value' => 'email',
                    ]
                ],
                2,
                true,
            ],
            [
                '2018-02-10 20:25:25',
                '2018-02-10 20:27:55',
                [
                    [
                        'name' => $loggedoff,
                        'value'=> 'none'
                    ],
                    [
                        'name' => $loggedin,
                        'value' => 'none'
                    ]
                ],
                2,
                false,
            ],
            [
                '2018-09-10 12:21:21',
                '2018-09-10 12:24:25',
                [
                    [
                        'name' =>  $loggedoff,
                        'value' => 'email'
                    ],
                    [
                        'name' => $loggedin,
                        'value' => 'none'
                    ]
                ],
                2,
                true
            ],
            [
                '2018-05-15 21:21:12',
                '2018-05-15 21:22:30',
                [
                    [
                        'name' => $loggedoff,
                        'value' => 'none'
                    ],
                    [
                        'name' => $loggedin,
                        'value' => 'email'
                    ]
                ],
                2,
                true
            ]
        ];
    }

    /**
     * Test suit of the validation base on user preference, to
     * determine whether adding comment should send an email out or not
     *
     * @param string    $lastaccess
     * @param string    $timechecked
     * @param array     $preferrences
     * @param int       $defaultminute
     * @param bool      $expected
     *
     * @dataProvider provide_sending_email_notification_checker_data
     */
    public function test_is_sending_email_notification_within_session(string $lastaccess, string $timechecked, array $preferrences, int $defaultminute, bool $expected): void {
        global $CFG;
        $CFG->block_online_users_timetosee = null;

        $this->resetAfterTest(true);
        $user = $this->create_user(strtotime($lastaccess));
        $this->create_user_session($user);
        $this->create_user_preferences($user, $preferrences);

        $helper = new add_comment_helper($user);

        $loggedin = add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDIN;
        $helper->add_user_preference($loggedin, get_user_preferences($loggedin, "none", $user));

        $loggedoff = add_comment_helper::COMPETENCY_PLAN_COMMENT_LOGGEDOFF;
        $helper->add_user_preference($loggedoff, get_user_preferences($loggedoff, "none", $user));

        $result = $helper->is_sending_email_notification(strtotime($timechecked), $defaultminute);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test suite checking the query record within
     * table sessions, if the record is not found,
     * then the method should return true, otherwise false
     * if the record is found
     *
     * @see add_comment_helper::is_user_logged_off      For checking the table session to check
     *                                                  whether user is logged off or not
     * @throws ReflectionException
     */
    public function test_is_user_logged_off(): void {
        $this->resetAfterTest(true);

        $time = time();
        $user = $this->create_user($time);
        $helper = new add_comment_helper($user);

        $reflection = new ReflectionClass($helper);
        $method = $reflection->getMethod("is_user_logged_off");
        $method->setAccessible(true);

        $result = $method->invokeArgs($helper, [$time]);

        $this->assertTrue($result);
    }

    /**
     * Test suite for checking whether the time
     * logic checker under if|else statement is working
     * correctly or not
     *
     * @see add_comment_helper::is_user_logged_off
     * @throws ReflectionException
     */
    public function test_is_user_logged_off_with_checking_time_logic(): void {
        global $DB;

        $this->resetAfterTest(true);

        $time = time();
        $user = $this->create_user($time);
        $helper = new add_comment_helper($user);

        $DB->insert_record("sessions", (object)[
            'state' => 0,
            'sid' => uniqid(),
            'userid' => $user->id,
            'timecreated' => $time - 3600,
            'timemodified' => $time - 3601,
            'firstip' => '127.0.0.1',
            'lastip' => '127.0.0.1'
        ]);

        $refclass = new ReflectionClass($helper);
        $method = $refclass->getMethod("is_user_logged_off");
        $method->setAccessible(true);

        $result = $method->invokeArgs($helper, [$time]);
        $this->assertTrue($result);
    }
}