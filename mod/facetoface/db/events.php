<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author David Curry <david.curry@totaralms.com>
 * @package mod_facetoface
 */

/**
 * this file should be used for all facetoface event definitions and handers.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
 * Events in seminars are widely used for interaction with external (to seminar itself) features:
 * 1. Responding to external events that need internal functionality: unenrols, suspensions, etc
 * 2. Responding to internal events that need external functionality: calendars, completions, notifications, etc.
 *      This is done, to limit responsibilities of states. E.g. state doesn't need to know whether it should send
 *      notifications or change completions state.
 */
$observers = [
    [
        'eventname' => \core\event\user_deleted::class,
        'callback' => '\mod_facetoface\event_handler::user_deleted',
    ],
    [
        'eventname' => \totara_core\event\user_suspended::class,
        'callback' => '\mod_facetoface\event_handler::user_suspended',
    ],
    [
        'eventname' => \core\event\user_enrolment_deleted::class,
        'callback' => '\mod_facetoface\event_handler::user_unenrolled',
    ],
    [
        'eventname' => \mod_facetoface\event\booking_booked::class,
        'callback' => '\mod_facetoface\event_handler::mark_completion_in_progress'
    ],
    [
        'eventname' => \mod_facetoface\event\booking_waitlisted::class,
        'callback' => '\mod_facetoface\event_handler::mark_completion_in_progress'
    ],
    [
        'eventname' => \mod_facetoface\event\booking_booked::class,
        'callback' => '\mod_facetoface\event_handler::add_calendar_booked_entry'
    ],
    [
        'eventname' => \mod_facetoface\event\booking_waitlisted::class,
        'callback' => '\mod_facetoface\event_handler::add_calendar_booked_entry'
    ],
    [
        'eventname' => \mod_facetoface\event\booking_cancelled::class,
        'callback' => '\mod_facetoface\event_handler::remove_calendar_booked_entry'
    ],
    [
        'eventname' => \mod_facetoface\event\booking_booked::class,
        'callback' => '\mod_facetoface\event_handler::send_notification_booked'
    ],
    [
        'eventname' => \mod_facetoface\event\booking_waitlisted::class,
        'callback' => '\mod_facetoface\event_handler::send_notification_waitlisted'
    ],
    [
        'eventname' => \mod_facetoface\event\booking_requested::class,
        'callback' => '\mod_facetoface\event_handler::send_notification_requested'
    ],
    [
        'eventname' => \totara_job\event\job_assignment_deleted::class,
        'callback'  => '\mod_facetoface\event_handler::job_assignment_deleted',
    ],
];
