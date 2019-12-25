<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\form\validator;

defined('MOODLE_INTERNAL') || die();

use totara_form\element_validator;

/**
 * Class is_valid_course
 * Makes sure that the course name is a course in the database
 * @package block_totara_featured_links
 */
class is_valid_course extends element_validator {

    /**
     * Validate the element.
     *
     * @return void adds errors to element
     */
    public function validate() {
        global $DB;
        $data = $this->element->get_model()->get_raw_post_data();
        $id = $data['course_name_id'];
        if (empty($id)) {
            $this->element->add_error(get_string('course_not_found', 'block_totara_featured_links'));
            return;
        }
        if (!$DB->record_exists('course', ['id' => $id])) {
            $this->element->add_error(get_string('course_not_found', 'block_totara_featured_links'));
            return;
        }
        if (!totara_course_is_viewable($id)) {
            $this->element->add_error(get_string('course_not_found', 'block_totara_featured_links'));
            return;
        }
    }
}