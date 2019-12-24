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

namespace totara_plan;

defined('MOODLE_INTERNAL') || die();

/**
 * A helper class to check the user's preferences
 * when a comment is being added to their plan.
 *
 * Each instance of this class should only for
 * a single user only.
 *
 * Class comment_helper
 * @package totara_plan
 * @see \add_comment_helper_test    For unit test
 */
class add_comment_helper
{
    /**
     * The user's preferences that is being used for adding a comment on a plan
     */
    const COMPETENCY_PLAN_COMMENT_LOGGEDOFF = "message_provider_moodle_competencyplancomment_loggedoff";
    const COMPETENCY_PLAN_COMMENT_LOGGEDIN = "message_provider_moodle_competencyplancomment_loggedin";

    /**
     * @var \stdClass
     */
    private $user;

    /**
     * @var array
     */
    private $preferences;

    /**
     * add_comment_helper constructor.
     *
     * @param \stdClass         $user       The user that is owning the plan and his/her plan
     *                                      is having a new comment on it
     */
    public function __construct(\stdClass $user)
    {
        $this->user = $user;
        $this->preferences = [];
    }

    /**
     * Method of injecting the preferences of user
     * into
     * @param string $preference
     * @param mixed  $value         The value of preference, and sometimes it could be null
     * @return void
     */
    public function add_user_preference($preference, $value) {
        $this->preferences[$preference] = $value;
    }

    /**
     * Method of validating the user preferences,
     * and there must only be two preferences for
     * the helper class to use for validate sending
     * the email or not
     */
    private function validate() {
        if (count($this->preferences) > 2) {
            $message = [
                "The helper class " . self::class,
                "should only have two preferences",
                "which are message_provider_moodle_competencyplancomment_loggedin",
                "and message_provider_moodle_competencyplancomment_loggedoff"
            ];
            throw new \coding_exception(implode(" ", $message));
        }
    }

    /**
     * Method for validating this user's preference
     * about sending the email when another user
     * is adding a comment into this user's
     *
     * @param int   $now                The Epox time that is needed to checking the user state
     * @param int   $defaulttimetoshow  The minute (threshold) determine whether user is logged out or not
     *
     * @return bool
     * @throws \coding_exception
     */
    public function is_sending_email_notification($now, $defaulttimetoshow=5) {
        $this->validate();

        $valuetobecheck = "email";
        $userstate = $this->get_user_state($now, $defaulttimetoshow);
        foreach ($this->preferences as $preference => $value) {
            $values = explode(",", $value);
            if (stripos($preference, $userstate) !== false && in_array($valuetobecheck, $values)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method of checking the record within the sessions table
     * if the user is logged off, then the user's id should not
     * be found within the table.
     *
     * There might be a scenario where the
     * session might be within the table,
     * but it is expired, therefore, the method
     * needs to check whether it is expired or not
     * under 5 minutes comparing with the current time
     *
     * @param int $now                  The result of using time() method (Epox time)
     * @param int $timetoshowuser       In second
     * @return bool
     */
    private function is_user_logged_off($now, $timetoshowuser=300): bool {
        global $DB;

        $records = $DB->get_records("sessions", array(
            'userid' => $this->user->id
        ), "timecreated");

        if (!empty($records)) {
            $lastrecord = end($records);
            if (!is_bool($lastrecord)) {
                $lastaccess = isset($lastrecord->timecreated) ? $lastrecord->timecreated : 0;
                if ($lastaccess + $timetoshowuser > $now) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Since using the lastaccess value of user is not quite accurately for the user state,
     * therefore, checking the records of user within the table sessions would help
     * to detect whether the user is actually logged off
     *
     * @param int $now                      The Epox time of present, this is normally a return
     *                                      data from function time()
     *
     * @param int $defaulttimetoshowusers   This is the default time to show users, by default it is 5 minutes
     */
    public function get_user_state($now, $defaulttimetoshowusers=5) {
        global $CFG;

        if (isset($CFG->block_online_users_timetosee)) {
            $timetoshowusers = $CFG->block_online_users_timetosee * 60;
        } else {
            $timetoshowusers = $defaulttimetoshowusers * 60;
        }

        if ($this->is_user_logged_off($now, $timetoshowusers)) {
            return "loggedoff";
        }

        $lastaccess = isset($this->user->lastaccess) ? $this->user->lastaccess : 0;

        if (($now - $timetoshowusers) < $lastaccess) {
            $userstate = 'loggedin';
        } else {
            $userstate = 'loggedoff';
        }

        return $userstate;
    }
}