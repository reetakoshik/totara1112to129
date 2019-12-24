<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 *
 * @package auth_approved
 */

namespace auth_approved\rb\display;

use \totara_reportbuilder\rb\display\base;

final class request_profilefields extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $DB;

        if (!$value) {
            return '';
        }

        $customfields = json_decode($value);

        // Get all the fields that appear on sign-up page keyed by their shortname.
        $signupfields = $DB->get_records_sql('SELECT shortname, {user_info_field}.* FROM {user_info_field} WHERE signup = 1 AND visible <> 0');

        $display = '';
        foreach ($customfields as $name => $fieldvalue) {
            $key = str_replace('profile_field_', '', $name);

            // Check if we still have this custom field available.
            // It could have been removed since a user signed up, but we still store data in the requests table.
            // This check would also skip extra data from datetime fields, like 'raw' and 'timezone' settings.
            // Make sure to include '0' as menu and text custom fields can store this value.
            if (!isset($signupfields[$key]) || $fieldvalue === "") {
                continue;
            }

            $displayclass = '\totara_reportbuilder\rb\display\userfield_' . $signupfields[$key]->datatype;
            switch ($signupfields[$key]->datatype) {
                case 'textarea':
                    // Skip textarea fields without any text value.
                    if (empty($fieldvalue->text)) {
                        continue 2;
                    }
                    $format = $fieldvalue->format;
                    $fieldvalue = $fieldvalue->text;
                    break;

                case 'datetime':
                    // Check if we need to display time as well.
                    if (!empty($signupfields[$key]->param3)) {
                        $displayclass = '\totara_reportbuilder\rb\display\nice_datetime';
                    } else {
                        $displayclass = '\totara_reportbuilder\rb\display\nice_date';
                    }
                    break;

                case 'menu':
                    // Check if there are any options in the menu field.
                    if (empty($signupfields[$key]->param1)) {
                        continue 2;
                    }
                    // Get the value of the option because approval requests store keys only.
                    $options = explode("\n", $signupfields[$key]->param1);
                    if (isset($options[(int)$fieldvalue])) {
                        $fieldvalue = $options[(int)$fieldvalue];
                    } else {
                        continue 2;
                    }
                    break;

                case 'date':
                case 'checkbox':
                case 'text':
                    break;

                default:
                    // Unsupported profile fields.
                    continue 2;
            }

            $display .= \html_writer::tag('dt', format_string($signupfields[$key]->name)) .
                        \html_writer::tag('dd', $displayclass::display($fieldvalue, $format, $row, $column, $report));
        }

        return \html_writer::tag('dl', $display, array('class' => 'dl-horizontal'));
    }
}
