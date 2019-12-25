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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for course module icons
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class course_mod_icons extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT, $CFG;

        $isexport = ($format !== 'html');

        $modules = explode('|', $value);
        $mods = array();

        // Sort module list before displaying to make
        // cells all consistent.
        foreach ($modules as $mod) {
            if (empty($mod)) {
                continue;
            }
            $module = new \stdClass();
            $module->name = $mod;
            if (get_string_manager()->string_exists('pluginname', $mod)) {
                $module->localname = get_string('pluginname', $mod);
            } else {
                $module->localname = ucfirst($mod);
            }
            $mods[] = $module;
        }
        \core_collator::asort_objects_by_property($mods, 'localname');

        $out = array();
        $glue = '';

        foreach ($mods as $module) {
            if ($isexport) {
                $out[] = $module->localname;
                $glue = ', ';
            } else {
                $glue = '';
                if (file_exists($CFG->dirroot . '/mod/' . $module->name . '/pix/icon.gif') ||
                    file_exists($CFG->dirroot . '/mod/' . $module->name . '/pix/icon.png')) {
                    $out[] = $OUTPUT->pix_icon('icon', $module->localname, $module->name);
                } else {
                    $out[] = $module->name;
                }
            }
        }

        return implode($glue, $out);
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
