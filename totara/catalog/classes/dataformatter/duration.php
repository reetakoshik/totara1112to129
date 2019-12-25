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

class duration extends formatter {

    /**
     * @param string $startfield the database field containing the start date
     * @param string $endfield the database field containing the end date
     */
    public function __construct(string $startfield, string $endfield) {
        $this->add_required_field('start', $startfield);
        $this->add_required_field('end', $endfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_TEXT,
        ];
    }

    /**
     * Given start and end date integers, gets the text duration (in days).
     *
     * @param array $data
     * @param \context $context
     * @return string e.g. "5 days"
     */
    public function get_formatted_value(array $data, \context $context): string {
        if (!array_key_exists('start', $data)) {
            throw new \coding_exception("duration data formatter expects 'start'");
        }
        if (!array_key_exists('end', $data)) {
            throw new \coding_exception("duration data formatter expects 'end'");
        }

        $now = new \DateTimeImmutable();
        $start = $now->setTimestamp($data['start']);
        $end = $now->setTimestamp($data['end']);
        $days = $end->diff($start)->days;

        return get_string('numdays', 'moodle', $days);
    }
}
