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
 * @package totara_connect
 */

/**
 * TC server install hook.
 */
function xmldb_totara_connect_install() {
    // Cloning of Totara Connect servers is no supported.
    // Admins would have to hack this setting or reinstall this plugin.
    // This universe-unique-TC-server-ID must not change ever!
    $idnumber = sha1(random_string(100) . get_site_identifier());
    set_config('serveridnumber', $idnumber, 'totara_connect');
}
