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

class progressbar extends formatter {

    /**
     * @param string $courseidfield the database field containing the course id
     * @param string $statusfield the database field containing the course status
     */
    public function __construct(string $courseidfield, string $statusfield) {
        $this->add_required_field('courseid', $courseidfield);
        $this->add_required_field('status', $statusfield);
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
     * @return array
     */
    public function get_formatted_value(array $data, \context $context): array {
        global $COMPLETION_STATUS, $PAGE, $USER;

        if (!array_key_exists('courseid', $data)) {
            throw new \coding_exception("Progress bar data formatter expects 'courseid'");
        }

        if (!array_key_exists('status', $data)) {
            throw new \coding_exception("Progress bar data formatter expects 'status'");
        }

        if (empty($data['courseid'])) {
            return [];
        }

        if (empty($COMPLETION_STATUS[$data['status']])) {
            throw new \coding_exception(
                "Unknown or empty status passed to progress bar dataformatter when courseid was also provided: " . $data['status']
            );
        }

        /** @var \totara_core_renderer $renderer */
        $renderer = $PAGE->get_renderer('totara_core');

        $result = $renderer->export_course_progress_for_template($USER->id, $data['courseid'], $data['status']);

        if (empty($result->pbar)) {
            return [];
        }

        return $result->pbar;
    }
}
