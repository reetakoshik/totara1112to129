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

namespace block_totara_featured_links\tile;

use totara_form\form\element\static_html;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_form_visibility
 * Defines the visibility for the course tile
 * Relies heavily on {@link learning_item_visibility}
 * @package block_totara_featured_links\tile
 */
class course_form_visibility extends learning_item_form_visibility {

    /**
     * Overriding to that a message about the course visibility can be added at the top.
     */
    public function definition() {
        global $CFG, $COHORT_VISIBILITY;

        parent::definition();

        /** @var course_tile $tile */
        $tile = $this->parameters['tile'];

        if (empty($tile->get_course())) {
            $state = get_string('course_has_been_deleted', 'block_totara_featured_links');
        } else {
            if (empty($CFG->audiencevisibility)) {
                // This check is moved from require_login().

                if ($tile->get_course()->visible) {
                    $state = get_string('visible');
                } else {
                    $state = get_string('course_hidden', 'block_totara_featured_links');
                }
            } else {
                $state = $COHORT_VISIBILITY[$tile->get_course()->audiencevisible];
            }
        }
        $this->model->get_items()[0]->add(
            new static_html(
                'coursevisibility',
                get_string('coursevisibility', 'block_totara_featured_links'),
                $state
            ),
            0
        );
    }
}