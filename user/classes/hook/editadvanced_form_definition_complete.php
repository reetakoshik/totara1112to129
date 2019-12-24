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
 * User edit form definition complete hook.
 *
 * This hook is called at the end of the user edit form definition, prior to data being set.
 *
 * @package core_user\hook
 */
class editadvanced_form_definition_complete extends \totara_core\hook\base {

    /**
     * The user edit form instance.
     * @var \user_editadvanced_form
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
     * @param \user_editadvanced_form $form
     * @param mixed[] $customdata
     */
    public function __construct(\user_editadvanced_form $form, array $customdata) {
        $this->form = $form;
        $this->customdata = $customdata;
    }
}