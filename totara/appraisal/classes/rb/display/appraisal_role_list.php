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
 * @package totara_appraisal
 */

namespace totara_appraisal\rb\display;

/**
 * Class describing column display formatting.
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_appraisal
 */
class appraisal_role_list extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        // If the appraisal is complete return an empty string.
        if (empty($value)) {
            return '';
        }

        $extra = self::get_extrafields_row($row, $column);

        $roleids = explode('|', $value);

        $roles = \appraisal::get_roles();

        $rolenames = array();

        foreach ($roleids as $id) {
            $rolename = get_string($roles[$id], 'totara_appraisal');

            // Only add link to user's profile if using HTML format.
            if ($format == 'html') {
                $extrakey = 'role_' . $id;
                if ($extra->$extrakey != 0) { // Make sure the role is assigned to a user.
                    $roleurl = new \moodle_url('/user/view.php', array('id' => $extra->$extrakey));
                    $rolenames[] = \html_writer::link($roleurl, $rolename);
                } else {
                    $rolenames[] = $rolename;
                }
            } else {
                $rolenames[] = $rolename;
            }
        }

        $rolelist = implode(', ', $rolenames);

        return $rolelist;
    }
}
