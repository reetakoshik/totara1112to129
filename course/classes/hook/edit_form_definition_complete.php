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
 * Course edit form definition complete hook.
 *
 * This hook is called at the end of the course edit form definition, prior to data being set.
 *
 * @package core_course\hook
 */
class edit_form_definition_complete extends \totara_core\hook\base {

    /**
     * The course edit form instance.
     * @var \course_edit_form
     */
    public $form;

    /**
     * Custom data belonging to the form.
     * This is protected on the form thus needs to be provided.
     * @var mixed[]
     */
    public $customdata;

    /**
     * The edit_form_definition_complete constructor.
     *
     * @param \course_edit_form $form
     * @param mixed[] $customdata
     */
    public function __construct(\course_edit_form $form, array $customdata) {
        $this->form = $form;
        $this->customdata = $customdata;
    }
}