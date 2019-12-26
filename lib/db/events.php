<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Definition of core event observers.
 *
 * The observers defined in this file are notified when respective events are triggered. All plugins
 * support this.
 *
 * For more information, take a look to the documentation available:
 *     - Events API: {@link http://docs.moodle.org/dev/Event_2}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package   core
 * @category  event
 * @copyright 2007 onwards Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of legacy event handlers.

$handlers = array(
    // No more old events!
);

// List of events_2 observers.

$observers = array(

    array(
        'eventname'   => '\core\event\course_module_completion_updated',
        'callback'    => 'core_badges_observer::course_module_criteria_review',
    ),
    array(
        'eventname'   => '\core\event\course_completed',
        'callback'    => 'core_badges_observer::course_criteria_review',
    ),
    array(
        'eventname'   => '\core\event\user_updated',
        'callback'    => 'core_badges_observer::profile_criteria_review',
    ),
    array(
        'eventname'   => '\totara_program\event\program_completed',
        'callback'    => 'core_badges_observer::program_criteria_review',
    ),
    array(
        'eventname'   => '\totara_cohort\event\members_updated',
        'callback'    => 'core_badges_observer::cohort_criteria_review',
    ),
    array(
        'eventname'   => '\core\event\cohort_member_added',
        'callback'    => 'core_badges_observer::cohort_criteria_review',
    ),

    // Added by Totara.
    array(
        'eventname' => '\core\event\course_created',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_updated',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_deleted',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\tag_added',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\tag_updated',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\tag_removed',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_data_deleted',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_section_updated',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_section_deleted',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_module_created',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_category_updated',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\course_restored',
        'callback'  => 'core_course\totara_catalog\course::object_update_observer'
    ),
    array(
        'eventname' => '\core\event\admin_settings_changed',
        'callback'  => 'core_tag\totara_catalog\tags_observer::changed'
    ),
    array(
        'eventname' => '\core\event\tag_area_updated',
        'callback'  => 'core_tag\totara_catalog\tags_observer::tag_area_updated'
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_created',
        'callback'  => 'core_course\totara_catalog\course\observer\customfield_changed::update_default_data'
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_updated',
        'callback'  => 'core_course\totara_catalog\course\observer\customfield_changed::update_default_data'
    ),
);

// List of all events triggered by Moodle can be found using Events list report.
