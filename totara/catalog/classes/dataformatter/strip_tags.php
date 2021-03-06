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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

class strip_tags extends formatter {

    /**
     * @param string $textfield the database field containing the text
     */
    public function __construct(string $textfield) {
        $this->add_required_field('text', $textfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_SORT_TEXT,
        ];
    }

    /**
     * Given a text string possibly containing html tags, strip them out.
     *
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {

        if (!array_key_exists('text', $data)) {
            throw new \coding_exception("Strip tags data formatter expects 'text'");
        }

        return strip_tags($data['text']);
    }
}
