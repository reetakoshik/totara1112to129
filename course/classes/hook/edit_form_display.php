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
 * Course edit form display hook.
 *
 * This hook is called immediately before the course edit form is displayed.
 *
 * @package core_course\hook
 */
class edit_form_display extends \totara_core\hook\base {

    /**
     * The form instance that is about to be display.
     * @var \course_edit_form
     */
    public $form;

    /**
     * Customdata from the form instance, which is otherwise private.
     * @var mixed[]
     */
    public $customdata;

    /**
     * The edit_form_display constructor.
     *
     * @param \course_edit_form $form
     * @param mixed[] $customdata
     */
    public function __construct(\course_edit_form $form, array $customdata) {
        $this->form = $form;
        $this->customdata = $customdata;
    }
}