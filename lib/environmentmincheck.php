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
 */

/*
 * This script is included as first thing on all pages including installers.
 * This is not a full environment test, we just make sure users have
 * the correct PHP environment. The full environment test is done later
 * using information from admin/environment.xml file.
 *
 *  - Do not use any Totara function here.
 *  - Do not create any variables here.
 *  - Do not change any PHP settings here.
 *
 * Terminates PHP execution with status code 1 on error.
 */

// Check that PHP is of a sufficient version as soon as possible
if (version_compare(phpversion(), '7.1.8', '<')) {
    $phpversion = phpversion();
    echo("Totara requires at least PHP 7.1.8 (currently using version $phpversion). Please upgrade your server software.\n");
    exit(1);
}

// Make sure iconv is available.
if (!function_exists('iconv')) {
    echo("Totara requires the iconv PHP extension. Please install or enable the iconv extension.\n");
    exit(1);
}

// Make sure xml extension is available - we need it to load full environment tests.
if (!extension_loaded('xml')) {
    echo("Totara requires the xml PHP extension. Please install or enable the xml extension.\n");
    exit(1);
}

// Make sure php5-json is available.
if (!function_exists('json_encode') or !function_exists('json_decode')) {
    echo("Totara requires the json PHP extension. Please install or enable the json extension.\n");
    exit(1);
}
