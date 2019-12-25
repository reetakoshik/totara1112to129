<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_course
 */

namespace core_course\hook;

/**
 * Course edit form save changes hook.
 *
 * This hook is called after the course data has been saved, before the user is redirected.
 *
 * @package core_course\hook
 */
class edit_form_save_changes extends \totara_core\hook\base {

    /**
     * True if a new course is being created, false if an existing course is being updated.
     * @var bool
     */
    public $iscreating = true;

    /**
     * The course id.
     * During creation this hook is called after the course has been created so we always have an ID.
     * @var int
     */
    public $courseid;

    /**
     * The course context.
     * During creation this hook is called after the course has been created so we always have a context.
     * @var \context_course
     */
    public $context;

    /**
     * Data submit by the user, retrieved via the form.
     * @var \stdClass
     */
    public $data;

    /**
     * The edit_form_save_changes constructor.
     *
     * @param bool $iscreating
     * @param int $courseid
     * @param \stdClass $data Data from the form, via {@see \course_edit_form::get_data()}
     */
    public function __construct($iscreating, $courseid, \stdClass $data) {
        $this->iscreating = (bool)$iscreating;
        $this->courseid = $courseid;
        $this->context = \context_course::instance($courseid, MUST_EXIST);
        $this->data = $data;
    }
}