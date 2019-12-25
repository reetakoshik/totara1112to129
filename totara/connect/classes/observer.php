<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_connect
 */

namespace totara_connect;

use \totara_connect\util;

/**
 * Event observer for Totara Connect server.
 *
 * @package totara_connect
 */
class observer {
    /**
     * Called when user logs in.
     *
     * We want to hijack the wants URL in case this is a SSO login.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $SESSION, $CFG;

        if (empty($CFG->enableconnectserver)) {
            return;
        }

        if (!empty($SESSION->totaraconnectssostarted)) {
            $params = $SESSION->totaraconnectssostarted;
            unset($SESSION->totaraconnectssostarted);
            $url = new \moodle_url('/totara/connect/sso_request.php', $params);
            $SESSION->wantsurl = $url->out(false);
        }
    }

    /**
     * Called when users logs out.
     *
     * We want to propagate the login to all clients.
     *
     * @param \core\event\user_loggedout $event
     */
    public static function user_loggedout(\core\event\user_loggedout $event) {
        global $DB, $SESSION, $CFG;

        if (empty($CFG->enableconnectserver)) {
            return;
        }

        unset($SESSION->totaraconnectssostarted);

        $sid = $event->other['sessionid'];

        $sessions = $DB->get_records('totara_connect_sso_sessions', array('sid' => $sid));

        // Delete sessions first in case we are interrupted somehow.
        $DB->delete_records('totara_connect_sso_sessions', array('sid' => $sid));

        foreach ($sessions as $session) {
            $client = $DB->get_record('totara_connect_clients', array('id' => $session->clientid));
            if (!$client) {
                continue;
            }
            util::terminate_sso_session($client, $session);
        }
    }

    /**
     * Called when course is created.
     *
     * We want to automatically add courses if requested.
     *
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $DB, $CFG;

        if (empty($CFG->enableconnectserver)) {
            return;
        }

        $clients = $DB->get_records('totara_connect_clients',
            array('status' => util::CLIENT_STATUS_OK, 'addnewcourses' => 1));

        if (!$clients) {
            return;
        }

        foreach ($clients as $client) {
            util::add_client_course($client, $event->courseid);
        }
    }

    /**
     * Called when cohort is created.
     *
     * We want to automatically add cohorts if requested.
     *
     * @param \core\event\cohort_created $event
     */
    public static function cohort_created(\core\event\cohort_created $event) {
        global $DB, $CFG;

        if (empty($CFG->enableconnectserver)) {
            return;
        }

        $clients = $DB->get_records('totara_connect_clients',
            array('status' => util::CLIENT_STATUS_OK, 'addnewcohorts' => 1));

        if (!$clients) {
            return;
        }

        foreach ($clients as $client) {
            util::add_client_cohort($client, $event->objectid);
        }
    }
}
