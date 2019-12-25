<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */

use \auth_connect\util;

/**
 * Admin setting for auto SSO.
 */
class auth_connect_setting_autossoserver extends admin_setting_configselect {
    public function __construct() {
        parent::__construct(
            'auth_connect/autossoserver',
            new lang_string('autossoserver', 'auth_connect'),
            new lang_string('autossoserver_desc', 'auth_connect'),
            '',
            null);
    }

    public function load_choices() {
        global $DB;

        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = array();
        $this->choices[''] = get_string('none');

        $servers = $DB->get_records('auth_connect_servers', array('status' => util::SERVER_STATUS_OK));
        foreach ($servers as $server) {
            $this->choices[$server->id] = $server->servername;
        }

        return true;
    }
}
