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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

$watchers = [
    [
        // The priority of this watcher must be after the core_edit_form from totara.
        'hookname' => '\core_course\hook\edit_form_definition_complete',
        'callback' => '\totara_catalog\watcher\course_form_watcher::add_searchmetadata_to_course_form',
        'priority' => 200
    ],
    [
        'hookname' => '\core_course\hook\edit_form_save_changes',
        'callback' => '\totara_catalog\watcher\course_form_watcher::process_searchmetadata_for_course'
    ],
    [
        'hookname' => '\totara_program\hook\program_edit_form_definition_complete',
        'callback' => '\totara_catalog\watcher\program_form_watcher::add_searchmetadata_to_program_form',
        'priority' => 100
    ],
    [
        'hookname' => '\totara_program\hook\program_edit_form_save_changes',
        'callback' => '\totara_catalog\watcher\program_form_watcher::process_searchmetadata_for_program'
    ],
    [
        'hookname' => '\totara_core\hook\fts_repopulation',
        'callback' => '\totara_catalog\watcher\fts_watcher::rebuild_catalog'
    ]
];