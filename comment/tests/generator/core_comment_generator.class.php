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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package core_comment
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Comments generator
 *
 * @package core_comment
 */
class core_comment_generator extends component_generator_base {

    /**
     * Add a new comment to an item.
     *
     * @param String $component
     * @param String $area
     * @param int $itemid
     * @param \context $context
     * @param String $commenttext
     * @param stdClass $cm
     * @param stdClass $course
     *
     * @return stdClass Object containing details of the comment that was added.
     */
    public function add_comment($component, $area, $itemid, $context, $commenttext, $cm = null, $course = null) {
        global $CFG;

        require_once($CFG->dirroot . '/comment/lib.php');

        $options = new stdClass();
        $options->component = $component;
        $options->area = $area;
        $options->itemid = $itemid;
        $options->context = $context;
        $options->cm = $cm;
        $options->course = $course;

        $comment = new comment($options);

        $newcomment = $comment->add($commenttext);

        return $newcomment;
    }
}
