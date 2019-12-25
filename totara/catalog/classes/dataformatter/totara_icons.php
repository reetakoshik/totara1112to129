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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

class totara_icons extends totara_icon {

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_ICONS,
        ];
    }

    /**
     * Totara icons data formatter. Returns one icon in an array.
     *
     * Expects $data to contain keys 'id' and 'icon_type'.
     * 'icon_type' should be one of TOTARA_ICON_TYPE_COURSE or TOTARA_ICON_TYPE_PROGRAM.
     *
     * @param array $data
     * @param \context $context
     * @return \stdClass[]
     */
    public function get_formatted_value(array $data, \context $context): array {
        return [parent::get_formatted_value($data, $context)];
    }
}
