<?php
/**
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\watcher;

use auth_approved\hook\add_request;

defined('MOODLE_INTERNAL') || die();

/**
 * Watch for auth approval requests.
 */
final class add_request_watcher {
    /**
     * A watcher to add extra data to approval requests.
     *
     * @param auth_approved\hook\add_request $hook
     * @return void
     */
    public static function add_extra_data_to_request(add_request $hook): void {
        global $SESSION;

        // Get data from hook and set extradata column.
        $data = $hook->data;

        // Get ids of all site policies presented to user if provided.
        if (!empty($SESSION->userconsentids)) {
            $data->extradata['userconsentids'] = $SESSION->userconsentids;
        }
    }
}