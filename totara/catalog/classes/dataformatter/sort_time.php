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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

/**
 * Used to format a date suitable for the sort_time field in the catalog.
 */
class sort_time extends formatter {

    /**
     * @param string $datefield the database field containing the date
     */
    public function __construct(string $datefield) {
        $this->add_required_field('date', $datefield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_SORT_TIME,
        ];
    }

    /**
     * Given a date integer, gets the text date in the current user's format.
     *
     * @param array $data
     * @param \context $context
     * @return int
     */
    public function get_formatted_value(array $data, \context $context): int {
        if (!array_key_exists('date', $data)) {
            throw new \coding_exception("sort_time data formatter expects 'date'");
        }

        return (int)$data['date'];
    }
}
