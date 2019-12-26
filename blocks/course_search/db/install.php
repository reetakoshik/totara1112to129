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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_course_search
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Course search block installation.
 */
function xmldb_block_course_search_install() {
    global $DB;

    $name = 'course_search';
    $during_migration = get_config('block_' . $name, 'frontpage_migration');
    // Migrate course search frontpage setting to the new block.
    if ($during_migration) {
        unset_config('frontpage_migration', 'block_' . $name);
        $tryupgrade = true;

        if ($tryupgrade && !class_exists('moodle_page')) {
            // We need to be able to use moodle_page.
            $tryupgrade = false;
        }

        if ($tryupgrade && !defined('SITEID')) {
            // We don't know the siteid.
            $tryupgrade = false;
        }

        $course = $DB->get_record('course', ['id' => SITEID]);
        if ($tryupgrade && !$course) {
            // We don't have the site course.
            $tryupgrade = false;
        }

        if ($tryupgrade) {
            // This block was added during frontpage migration.
            // Add an instance of this block to the site page.
            $page = new moodle_page();
            $page->set_course($course);
            $page->blocks->add_blocks(['main' => [$name]], 'site-index');
        }
    }
}
