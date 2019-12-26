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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package block_admin_subnav
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Admin sub-navigation block installation.
 */
function xmldb_block_admin_subnav_install() {
    global $DB;

    $blockname = 'admin_subnav';

    // Check if the block instance has been previously added.
    $added = $DB->record_exists('block_instances', ['blockname' => $blockname, 'pagetypepattern' => 'admin-*']);
    if (!$added && class_exists('moodle_page')) { // We need to be able to use moodle_page.
        $page = new moodle_page();
        $page->set_context(context_system::instance());
        $page->blocks->add_blocks(['side-pre' => [$blockname]], 'admin-*', null, null, 2);
    }
}
