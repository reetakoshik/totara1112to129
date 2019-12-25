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

class ordered_list extends formatter {

    /** @var string */
    private $sourcedelimiter;

    /** @var string */
    private $resultdelimiter;

    /**
     * @param string $listfield the database field containing the array of some text (use $DB->group_concat)
     * @param string $sourcedelimiter indicating how the source items are delimited
     * @param string $resultdelimiter indicating how the resulting items should be delimited
     */
    public function __construct(string $listfield, string $sourcedelimiter = ',', string $resultdelimiter = ', ') {
        $this->add_required_field('list', $listfield);
        $this->sourcedelimiter = $sourcedelimiter;
        $this->resultdelimiter = $resultdelimiter;
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_TEXT,
            formatter::TYPE_FTS,
        ];
    }

    /**
     * Ordered list data formatter.
     *
     * Expects $data to contain key 'list' which is a string containing items with a delimiter.
     *
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {

        if (!array_key_exists('list', $data)) {
            throw new \coding_exception("List data formatter expects 'list'");
        }

        if (empty($data['list'])) {
            return '';
        }

        $items = explode($this->sourcedelimiter, $data['list']);

        $items = array_map(
            function ($item) use ($context) {
                return format_string($item, true, ['context' => $context]);
            },
            $items
        );

        sort($items);

        return implode($this->resultdelimiter, $items);
    }
}
