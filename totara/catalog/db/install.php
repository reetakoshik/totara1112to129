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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

function xmldb_totara_catalog_install() {
    global $CFG;

    // Set the new config variable controlling which catalog to display.
    if (empty($CFG->catalogtype)) {
        $catalogtype = 'totara';
        if (isset($CFG->enhancedcatalog)) {
            $previous_setting = (string)$CFG->enhancedcatalog;
            if ($previous_setting === '1') {
                $catalogtype = 'enhanced';
            } else if ($previous_setting === '0') {
                $catalogtype = 'moodle';
            }
        }

        set_config('catalogtype', $catalogtype);
    }

    // Fire an adhoc task to populate the catalog - will happen first time cron runs.
    $adhoctask = new \totara_catalog\task\refresh_catalog_adhoc();
    $adhoctask->set_component('totara_catalog');
    core\task\manager::queue_adhoc_task($adhoctask);
}
