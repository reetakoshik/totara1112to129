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
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\dataformatter;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;

class type_icons extends formatter {

    /** @var type_icon */
    private $typeicondataformatter;

    /**
     * @param string $coursetypefield the database field containing the course type
     */
    public function __construct(string $coursetypefield) {
        $this->add_required_field('coursetype', $coursetypefield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_ICONS,
        ];
    }

    /**
     * Given a coursetype, gets the course type icon, in an array.
     *
     * @param array $data
     * @param \context $context
     * @return \stdClass[]
     */
    public function get_formatted_value(array $data, \context $context): array {
        if (is_null($this->typeicondataformatter)) {
            // We're only using this dataformatter for get_formatter_value, so field doesn't matter.
            $this->typeicondataformatter = new type_icon('');
        }

        $typeicon = $this->typeicondataformatter->get_formatted_value($data, $context);

        if (empty($typeicon)) {
            return [];
        } else {
            return [$typeicon];
        }
    }
}
