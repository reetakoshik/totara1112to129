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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package core_user
 */

namespace core_user\hook;

/**
 * User edit form display hook.
 *
 * This hook is called immediately before the user edit form is displayed.
 *
 * @package core_user\hook
 */
class editadvanced_form_display extends \totara_core\hook\base {

    /**
     * The form instance that is about to be display.
     * @var \user_editadvanced_form
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
     * @param \user_editadvanced_form $form
     * @param mixed[] $customdata
     */
    public function __construct(\user_editadvanced_form $form, array $customdata) {
        $this->form = $form;
        $this->customdata = $customdata;
    }
}