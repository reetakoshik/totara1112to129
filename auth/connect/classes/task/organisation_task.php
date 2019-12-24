<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_connect
 */


namespace auth_connect\task;

use auth_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Organisations sync task.
 */
class organisation_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskorganisation', 'auth_connect');
    }

    /**
     * Do the job.
     */
    public function execute() {
        global $DB;

        if (!is_enabled_auth('connect')) {
            return;
        }

        $servers = $DB->get_records('auth_connect_servers', array('status' => util::SERVER_STATUS_OK));
        foreach ($servers as $server) {
            util::sync_organisations($server);
        }
    }
}
