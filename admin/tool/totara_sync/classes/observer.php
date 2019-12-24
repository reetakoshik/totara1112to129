<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

namespace tool_totara_sync;

class observer {

    public static function profilefield_updated(\totara_customfield\event\profilefield_updated $event) {
        global $CFG;

        $eventinfo = $event->get_info();

        if (!empty($eventinfo->oldshortname) && $eventinfo->oldshortname != $eventinfo->shortname) {
            // Require user element so we can create an instance of the user element.
            require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/user.php');

            $element = new \totara_sync_element_user();
            $sources = $element->get_sources();
            foreach ($sources as $source) {
                $plugin = $source->get_name();

                // Get value of existing setting.
                $configvalue = get_config($plugin, 'import_customfield_' . $eventinfo->oldshortname);

                if ($configvalue) {
                    unset_config('import_customfield_' . $eventinfo->oldshortname, $plugin);
                    set_config('import_customfield_' . $eventinfo->shortname, $configvalue, $plugin);
                }

                $configvalue = get_config($plugin, 'fieldmapping_customfield_' . $eventinfo->oldshortname);
                if ($configvalue) {
                    unset_config('fieldmapping_customfield_' . $eventinfo->oldshortname, $plugin);
                    set_config('fieldmapping_customfield_' . $eventinfo->shortname, $configvalue, $plugin);
                }
            }
        }
    }
}
