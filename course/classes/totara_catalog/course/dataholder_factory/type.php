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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\dataholder_factory;

defined('MOODLE_INTERNAL') || die();

use core_course\totara_catalog\course\dataformatter\type as format_course_type;
use core_course\totara_catalog\course\dataformatter\type_icon as type_icon_dataformatter;
use core_course\totara_catalog\course\dataformatter\type_icons as type_icons_dataformatter;
use totara_catalog\dataformatter\formatter;
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;

class type extends dataholder_factory {

    public static function get_dataholders(): array {
        return [
            new dataholder(
                'type',
                new \lang_string('coursetype', 'totara_core'),
                [
                    formatter::TYPE_PLACEHOLDER_TEXT => new format_course_type(
                        'base.coursetype'
                    ),
                ]
            ),
            new dataholder(
                'type_icon',
                new \lang_string('coursetype', 'totara_core'),
                [
                    formatter::TYPE_PLACEHOLDER_ICON => new type_icon_dataformatter(
                        'base.coursetype'
                    ),
                    formatter::TYPE_PLACEHOLDER_ICONS => new type_icons_dataformatter(
                        'base.coursetype'
                    ),
                ]
            ),
        ];
    }
}
