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
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program\dataformatter;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_plan\user_learning\program;

class progressbar extends formatter {

    /**
     * @param string $programidfield the database field containing the program id
     */
    public function __construct(string $programidfield) {
        $this->add_required_field('programid', $programidfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_PROGRESS,
        ];
    }

    /**
     * Given a course id and status, gets the progress bar for the current user.
     *
     * @param array $data
     * @param \context $context
     * @return []
     */
    public function get_formatted_value(array $data, \context $context): array {
        global $USER;

        if (!array_key_exists('programid', $data)) {
            throw new \coding_exception("Progress bar data formatter expects 'programid'");
        }

        if (empty($data['programid'])) {
            return [];
        }

        $item = program::one($USER->id, $data['programid']);
        $result = $item->export_for_template();

        if (empty($result->progress->pbar)) {
            return [];
        }

        return $result->progress->pbar;
    }
}
