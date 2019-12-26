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
 * This file is executed before any upgrade of Totara site.
 * This file is not executed during initial installation or upgrade from vanilla Moodle.
 *
 * Note that Totara 1.x and 2.2.x testes are in lib/setup.php, we can get here only from higher versions.
 */

defined('MOODLE_INTERNAL') || die();
global $DB, $CFG, $TOTARA;
require_once(__DIR__ . '/upgradelib.php');

$dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

// Always update all language packs if we can, because they are used in Totara upgrade/install scripts.
totara_upgrade_installed_languages();

// Add parentid to context table and create context_map table.
totara_core_upgrade_context_tables();

// Migrate block title from storing in the config to a new model.
totara_core_migrate_old_block_titles();

// One-off fix for incorrect default setting from Moodle.
if (!empty($CFG->totara_build) and $CFG->totara_build < '20181026.00') {
    if (!get_config('scorm', 'protectpackagedownloads')) {
        unset_config('protectpackagedownloads', 'scorm');
    }
}
