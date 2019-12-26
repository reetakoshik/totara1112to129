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
 * @author Jon Sharp <jon.sharp@catalyst-eu.net>
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage certification
 *
 *
 * Events handler: http://docs.moodle.org/dev/Events_API
 *
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\totara_program\event\program_assigned',
        'callback' => 'certification_event_handler::assigned',
        'includefile' => 'totara/certification/lib.php',
    ),
    array(
        'eventname' => '\totara_program\event\program_completed',
        'callback' => 'certification_event_handler::completed',
        'includefile' => 'totara/certification/lib.php',
    ),
    array(
        'eventname' => '\totara_certification\event\certification_updated',
        'callback' => 'certification_event_handler::certification_updated',
        'includefile' => 'totara/certification/lib.php',
    ),
    array(
        'eventname' => '\core\event\course_in_progress',
        'callback'  => 'totara_certification_observer::course_in_progress',
    ),
    array(
        'eventname' => '\totara_program\event\program_created',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\totara_program\event\program_updated',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\totara_certification\event\certification_updated',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\totara_program\event\program_deleted',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\tag_added',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\tag_updated',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\tag_removed',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_data_deleted',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\totara_program\event\program_contentupdated',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\course_category_updated',
        'callback'  => 'totara_certification\totara_catalog\certification::object_update_observer',
    ),
    array(
        'eventname' => '\core\event\admin_settings_changed',
        'callback'  => 'totara_certification\totara_catalog\certification\observer\settings_observer::changed',
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_created',
        'callback'  => 'totara_certification\totara_catalog\certification\observer\customfield_changed::update_default_data'
    ),
    array(
        'eventname' => '\totara_customfield\event\customfield_updated',
        'callback'  => 'totara_certification\totara_catalog\certification\observer\customfield_changed::update_default_data'
    ),
);
