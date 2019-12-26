<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package block_course_navigation
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Course navigation block installation.
 */
function xmldb_block_course_navigation_install() {
    global $DB;

    // Check if we are installing during the navigation migration.
    if (get_config('block_course_navigation', 'navigation_migration')) {
        unset_config('navigation_migration', 'block_course_navigation');

        if (!class_exists('moodle_page')) {
            // We need to be able to use moodle_page.
            return;
        }

        if (!$courses = $DB->get_records('course')) {
            // We don't have any courses yet.
            return;
        }

        // Add an instance of this block to each course page.
        foreach ($courses as $course) {
            $page = new moodle_page();
            $page->set_course($course);
            $page->blocks->add_blocks(['side-pre' => ['course_navigation']], '*', null, true, -10);
        }
    }
}
