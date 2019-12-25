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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @package totara_core
 */

/**
 * This lists event observers.
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
        'eventname' => '\totara_core\event\module_completion',
        'callback'  => 'totara_core_observer::criteria_course_calc',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback'  => 'totara_core_observer::user_enrolment',
    ),
    array(
        'eventname' => '\totara_core\event\bulk_enrolments_started',
        'callback'  => 'totara_core_observer::bulk_enrolments_started',
    ),
    array(
        'eventname' => '\totara_core\event\bulk_enrolments_ended',
        'callback'  => 'totara_core_observer::bulk_enrolments_ended',
    ),
    array(
        'eventname' => '\core\event\course_completed',
        'callback'  => 'totara_core_observer::course_criteria_review',
    ),
    array(
        'eventname' => '\core\event\user_confirmed',
        'callback' => 'totara_core_observer::user_confirmed',
        'priority' => 2400,
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback'  => 'totara_core_observer::user_deleted'
    ),

    // Resetting of Totara menu caches.
    array(
        'eventname' => '\totara_reportbuilder\event\report_created',
        'callback'  => 'totara_core_observer::totara_menu_reset_all_caches',
    ),
    array(
        'eventname' => '\totara_reportbuilder\event\report_deleted',
        'callback'  => 'totara_core_observer::totara_menu_reset_all_caches',
    ),
    array(
        'eventname' => '\totara_reportbuilder\event\report_updated',
        'callback'  => 'totara_core_observer::totara_menu_reset_session_cache',
    ),
    array(
        'eventname' => '\totara_program\event\program_completed',
        'callback'  => 'totara_core_observer::totara_menu_reset_session_cache',
    ),

);
