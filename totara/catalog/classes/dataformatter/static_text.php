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

class static_text extends formatter {

    /** @var string */
    private $text;

    /**
     * @param string $text Some text to be displayed. Must already be sanitised if it came from user input.
     */
    public function __construct(string $text) {
        $this->text = $text;
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_TEXT,
            formatter::TYPE_FTS,
        ];
    }

    /**
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {
        return format_string($this->text, true, ['context' => $context]);
    }
}
