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
 * @package totara_hierarchy
 */

namespace totara_hierarchy\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended to show a hierarchy path as a human-readable string
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_hierarchy
 */
class hierarchy_nice_path extends base {

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
        global $DB;

        if (empty($value)) {
            return '';
        }

        $extrafields = self::get_extrafields_row($row, $column);

        if (!isset($extrafields->hierarchytype) or !in_array($extrafields->hierarchytype, array('pos', 'org'))) {
            return '';
        }

        $parentid = 0;
        $displaypath = '';

        if (!isset($report->displaycache[__CLASS__])) {
            $report->displaycache[__CLASS__] = array();
        }
        if (!isset($report->displaycache[__CLASS__][$extrafields->hierarchytype])) {
            $report->displaycache[__CLASS__][$extrafields->hierarchytype] = $DB->get_records_menu($extrafields->hierarchytype, null, 'id ASC', 'id, fullname');
        }
        $map = $report->displaycache[__CLASS__][$extrafields->hierarchytype];
        $paths = explode('/', substr($value, 1));
        foreach ($paths as $path) {
            if ($parentid !== 0) {
                // Include ' > ' before name except on top element.
                $displaypath .= ' &gt; ';
            }
            if (isset($map[$path])) {
                $displaypath .= $map[$path];
            } else {
                // Should not happen if paths are correct!
                $displaypath .= get_string('unknown', 'totara_reportbuilder');
            }
            $parentid = $path;
        }

        $displaypath = format_string($displaypath);

        return $displaypath;
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
