<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_customfield
 */

namespace totara_customfield\rb\display;

use totara_reportbuilder\rb\display\base;

/**
 * Class describing column display formatting.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */
class customfield_textarea extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG;
        require_once($CFG->dirroot.'/totara/customfield/field/textarea/field.class.php');

        if (is_null($value) or $value === '') {
            return '';
        }

        $field = "{$column->type}_{$column->value}";
        $extrafields = self::get_extrafields_row($row, $column);

        // Columns generated with "rb_cols_generator_allcustomfields" extradata will be prefixed with type_value_* remove it.
        self::prepare_type_value_prefixed_extrafields($extrafields, $row, $column);

        // Hierarchy custom fields are stored in the FileAPI fileareas using the longform of the prefix
        // extract prefix from field name.
        $pattern = '/(?P<prefix>(.*?))(_all)?_custom_field_(\d+)[a-zA-Z]{0,5}$/';
        $matches = array();
        $found = preg_match($pattern, $field, $matches);
        if (!$found) {
            debugging("Unknown type of custom field '$field'", DEBUG_DEVELOPER);
            return '';
        }
        $helper = \totara_customfield\helper::get_instance();
        if (!$helper->check_if_prefix_recognised($matches['prefix'])) {
            debugging("Unknown prefix '{$matches['prefix']}' for custom field '{$field}'", DEBUG_DEVELOPER);
            return '';
        }

        $class = $helper->get_area_class_by_prefix($matches['prefix']);

        $extradata = array(
            'prefix' => $class::get_area_name(),
            'itemid' => $extrafields->itemid
        );
        $displaytext = \customfield_textarea::display_item_data($value, $extradata);

        if ($format !== 'html') {
            $displaytext = static::to_plaintext($displaytext, true);
        }

        return $displaytext;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
