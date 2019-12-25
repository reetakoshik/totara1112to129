<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_program
 */

namespace totara_program\output;

use totara_core\output\{select_search_text, select_multi, select_region_panel};
use core\output\notification;
use totara_program\assignment\helper;

final class assignments extends \core\output\template {

    /**
     * Creates an assignments template object from program assignments
     *
     * @param array $assignments The program assignments (to be feed to assignment_table)
     * @param int $programid Program Id for the assignments (primarily used in ajax calls)
     * @param bool $toomany Whethere there are too many assignments in this program
     *
     * @return assignments assignments template object
     */
    public static function create_from_assignments(array $assignments, int $programid, bool $toomany): assignments {
        global $OUTPUT;

        $data = [];

        $canupdate = helper::can_update($programid);

        // Add assignment search
        $serachprogramassignments = get_string('searchprogramassignments', 'totara_program');
        $assignmentsearch = select_search_text::create('searchkey', $serachprogramassignments, 'assignmentsearchhidden', null, $serachprogramassignments);
        $data['assignment_search_template_name'] = $assignmentsearch->get_template_name();
        $data['assignment_search_template_data'] = $assignmentsearch->get_template_data();

        // Assignment types
        $types = helper::get_types();
        foreach ($types as $id => $name) {
            $types[$id] = get_string($name, 'totara_program');
        }

        $recentfilter = select_multi::create('recent', '', 'somethign', [1 => get_string('recentlyadded', 'totara_program')]);
        $typefilter = select_multi::create('type', get_string('type', 'totara_program'), 'hiddentitle', $types, []);

        $selectors = [$recentfilter, $typefilter];

        $filter_region = select_region_panel::create(
            get_string('filter','totara_program'),
            $selectors
        );

        $data['assignment_filter_region_template_name'] = $filter_region->get_template_name();
        $data['assignment_filter_region_template_data'] = $filter_region->get_template_data();
        $items = assignment_table::create_from_assignments($assignments);

        if (count($items->get_template_data()['items']) === 0 && $toomany) {
            $data['assignment_table_template_name'] = 'totara_program/assignment__too-many';
            $data['assignment_table_template_data'] = true;
        } else if (count($items->get_template_data()['items']) === 0) {
            $data['assignment_table_template_name'] = 'totara_program/assignment__no-results';
            $data['assignment_table_template_data'] = true;
        } else {
            $data['assignment_table_template_name'] = $items->get_template_name();
            $data['assignment_table_template_data'] = $items->get_template_data();
        }
        $data['has_categories'] = false;
        $data['programid'] = $programid;
        $data['canupdate'] = $canupdate;

        return new assignments($data);
    }

    /**
     * Adds the categories for the filtering
     *
     * @param array an object containing id and name of a category to add
     */
    public function set_categories(array $categories) {
        $this->data['categories'] = [];

        foreach ($categories as $category) {
            $this->data['has_categories'] = true;
            $this->data['categories'][] = [
                'value' => $category->id,
                'text' => $category->name
            ];
        }
    }
}
