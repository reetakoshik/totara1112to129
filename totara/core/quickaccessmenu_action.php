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
 * @package totara_core
 */

/**
 * Processes actions to add/remove and item when on an item page.
 * This script either succeeds and redirects or throws an exception.
 * @var stdClass $USER The global USER object.
 */

define('NON_RETURNABLE_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

$action = required_param('action', PARAM_ALPHA);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/totara/core/quickaccessmenu_action.php', ['action' => $action]));
$PAGE->set_cacheable(false);

require_login(null, false, null, false);
require_capability('totara/core:editownquickaccessmenu', $context);
require_sesskey();

$actions = [
    /**
     * Adds the given item to the user's menu.
     * @param int $userid
     */
    'add' => function(int $userid): string {
        $key = required_param('key', PARAM_ALPHANUMEXT);
        $group = required_param('group', PARAM_ALPHANUMEXT);
        if ($group === '-1') {
            $newgroup = \totara_core\quickaccessmenu\group::create_group(null, $userid);
            $group = $newgroup->get_key();
        }
        \totara_core\quickaccessmenu\external::add_item($key, $group, $userid);
        return get_string('quickaccessmenu:success:itemadded', 'totara_core');
    },

    /**
     * Removes the given item from the user's menu.
     * @param int $userid
     */
    'remove' => function(int $userid): string {
        $key = required_param('key', PARAM_ALPHANUMEXT);
        \totara_core\quickaccessmenu\external::remove_item($key, $userid);
        return get_string('quickaccessmenu:success:itemremoved', 'totara_core');
    },
];

// If the action doesn't exist then exception, coding_exception is fine, no hacking tolerated here.
if (!isset($actions[$action])) {
    throw new coding_exception('Invalid action', $action);
}

$notification = call_user_func($actions[$action], $USER->id);
$returnurl = \totara_core\quickaccessmenu\helper::get_quickaction_returnurl();
redirect($returnurl, $notification, null, \core\output\notification::NOTIFY_SUCCESS);
