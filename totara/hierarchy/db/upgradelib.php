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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Iterates over all of the custom fields for the given field prefix and gives them sequential sortorders.
 *
 * This function is designed to fix the sort orders of custom fields used within hierarchies.
 * Due to a bug in Totara the sort order may not be sequential, there may be duplicates, and there
 * may be gaps.
 *
 * Rather than identify the problem records this function simply ensures consistent sorting of custom
 * fields by reading them out in a prescribed way and then ensuring sequential sort orders exist.
 * The order it reads out is based on code that exists at the time of writing.
 *
 * @param string $tableprefix
 */
function totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix) {
    global $DB;

    $table = $tableprefix.'_info_field';

    $rs = $DB->get_recordset($table, [], 'typeid ASC, sortorder ASC, id ASC', 'id,typeid,sortorder');
    $fieldsbytype = array();
    foreach ($rs as $field) {
        $typeid = $field->typeid;
        if (!isset($fieldsbytype[$typeid])) {
            $fieldsbytype[$typeid] = [$field];
        } else {
            $fieldsbytype[$typeid][] = $field;
        }
    }
    $rs->close();
    unset($rs);

    foreach ($fieldsbytype as $typeid => $fields) {
        $sortorder = 1;
        foreach ($fields as $field) {
            if ($field->sortorder != $sortorder) {
                $field->sortorder = $sortorder;
                $DB->update_record($table, $field, true);
            }
            $sortorder++;
        }
        // Explicitly unset this object to reduce memory as we progress.
        // Shouldn't be needed, but doesn't hurt!
        unset($fieldsbytype[$typeid]);
    }
}