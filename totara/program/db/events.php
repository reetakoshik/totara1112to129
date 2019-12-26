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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage program
 */

/**
 * this file should be used for all program event definitions and handers.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$observers = array(
    array(
        'eventname' => '\totara_program\event\program_unassigned',
        'callback'  => 'totara_program_observer::unassigned',
    ),
    array(
        'eventname' => '\totara_program\event\program_completed',
        'callback'  => 'totara_program_observer::completed',
    ),
    array(
        'eventname' => '\totara_program\event\program_courseset_completed',
        'callback'  => 'totara_program_observer::courseset_completed',
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback'  => 'totara_program_observer::assignments_firstlogin',
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback'  => 'totara_program_observer::user_deleted',
    ),
    array(
        'eventname' => '\core\event\user_confirmed',
        'callback'  => 'totara_program_observer::user_confirmed',
        'priority'  => 2400,
    ),
    array(
        'eventname' => '\core\event\course_deleted',
        'callback'  => 'totara_program_observer::course_deleted',
    ),
    array(
        'eventname' => '\totara_cohort\event\members_updated',
        'callback'  => 'totara_program_observer::cohort_members_updated',
    ),
    array(
        'eventname' => '\core\event\cohort_member_added',
        'callback'  => 'totara_program_observer::cohort_members_updated',
    ),
    array(
        'eventname' => '\core\event\cohort_member_removed',
        'callback'  => 'totara_program_observer::cohort_members_updated',
    ),
    array(
        'eventname' => '\totara_job\event\job_assignment_updated',
        'callback'  => 'totara_program_observer::job_assignment_updated',
    ),
    array(
        'eventname' => '\core\event\course_in_progress',
        'callback'  => 'totara_program_observer::course_in_progress',
    ),
    array(
        'eventname' => '\totara_program\event\program_contentupdated',
        'callback'  => '\totara_program\rb_course_sortorder_helper::handle_program_contentupdated',
    ),
    array(
        'eventname' => '\totara_program\event\program_deleted',
        'callback'  => '\totara_program\rb_course_sortorder_helper::handle_program_deleted',
    ),
    array(
        'eventname' => '\totara_program\event\program_created',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\totara_program\event\program_updated',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\totara_program\event\program_deleted',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\tag_added',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\tag_updated',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\tag_removed',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_data_deleted',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\totara_program\event\program_contentupdated',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\course_category_updated',
        'callback'  => 'totara_program\totara_catalog\program::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\admin_settings_changed',
        'callback'  => 'totara_program\totara_catalog\program\observer\settings_observer::changed',
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_created',
        'callback'  => 'totara_program\totara_catalog\program\observer\customfield_changed::update_default_data'
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_updated',
        'callback'  => 'totara_program\totara_catalog\program\observer\customfield_changed::update_default_data'
    ),
);
