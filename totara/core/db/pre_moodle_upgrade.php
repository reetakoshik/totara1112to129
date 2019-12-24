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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara_core
 */

/*
 * This file is executed before migration from vanilla Moodle installation.
 */

defined('MOODLE_INTERNAL') || die();
global $DB, $CFG;

$dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

// Always update all language packs if we can, because they are used in Totara upgrades/install.
totara_upgrade_installed_languages();

// Add custom Totara completion field to prevent fatal problems during upgrade.
$table = new xmldb_table('course_completions');
$field = new xmldb_field('invalidatecache', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'reaggregate');
if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
}

// One-off fix for incorrect default setting from Moodle.
if (!get_config('scorm', 'protectpackagedownloads')) {
    unset_config('protectpackagedownloads', 'scorm');
}
