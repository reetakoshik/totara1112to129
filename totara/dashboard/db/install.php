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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_dashboard
 */

function xmldb_totara_dashboard_install() {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    // Add default my learning dashboard on install.

    // We want all or nothing here.
    $transaction = $DB->start_delegated_transaction();

    $todb = [
        'name' => get_string('mylearning', 'totara_core'), // Multi-lang sites will need to update this themselves.
        'published' => 2, // For all logged in users.
        'locked' => 0,
        'sortorder' => 0
    ];
    $dashboardid = $DB->insert_record('totara_dashboard', $todb);

    $defaultblockinstances = [
        [
            'blockname' => 'last_course_accessed',
            'defaultregion' => 'side-pre',
            'defaultweight' => -3
        ],
        [
            'blockname' => 'totara_dashboard',
            'defaultregion' => 'side-pre',
            'defaultweight' => -2
        ],
        [
            'blockname' => 'totara_my_learning_nav',
            'defaultregion' => 'side-pre',
            'defaultweight' => -1
        ],
        [
            'blockname' => 'current_learning',
            'defaultregion' => 'main',
            'defaultweight' => -2
        ],
        [
            'blockname' => 'totara_tasks',
            'defaultregion' => 'main',
            'defaultweight' => -1
        ],
        [
            'blockname' => 'totara_alerts',
            'defaultregion' => 'main',
            'defaultweight' => 0
        ],
        [
            'blockname' => 'news_items',
            'defaultregion' => 'side-post',
            'defaultweight' => 0
        ],
        [
            'blockname' => 'calendar_upcoming',
            'defaultregion' => 'side-post',
            'defaultweight' => -1
        ],
        [
            'blockname' => 'badges',
            'defaultregion' => 'side-post',
            'defaultweight' => -2
        ],
    ];

    $availableblocks = $DB->get_records_menu('block', ['visible' => 1], '', 'id,name');

    foreach ($defaultblockinstances as $blockinstance) {

        // Add common properties.
        $blockinstance['parentcontextid'] = context_system::instance()->id; // System context.
        $blockinstance['showinsubcontexts'] = 0;
        $blockinstance['pagetypepattern'] = 'totara-dashboard-' . $dashboardid; // Determines the dashboard the block is included on.
        $blockinstance['subpagepattern'] = 'default'; // Indicates this is the site wide default, not a user dashboard.
        $blockinstance['configdata'] = '';

        // Add the block instances.
        $biid = $DB->insert_record('block_instances', $blockinstance);

        // Ensure context is properly created.
        context_block::instance($biid, MUST_EXIST);
    }

    $transaction->allow_commit();

}

