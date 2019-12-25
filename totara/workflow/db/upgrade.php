<?php
/*
 * This file is part of Totara LMS
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_workflow
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Workflow plugin upgrade.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_totara_workflow_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018101900) {
        // Clean up old config setting if it exists.
        $oldclass = 'core\\workflow\\coursecreate\\standard';
        $newclass = 'core\\workflow\\core_course\\coursecreate\\standard';
        $oldsetting = get_config('totara_workflow', $oldclass);
        if (!is_null($oldsetting)) {
            if (!empty($oldsetting)) {
                // If old setting was enabled, turn it on.
                $workflow = $newclass::instance();
                $workflow->enable();
            }
            // Remove old setting.
            unset_config($oldclass, 'totara_workflow');
        }
        upgrade_plugin_savepoint(true, 2018101900, 'totara', 'workflow');
    }

    return true;
}
