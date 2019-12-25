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
 * User edit form save changes hook.
 *
 * This hook is called after the user data has been saved, before the user is redirected.
 *
 * @package core_user\hook
 */
class editadvanced_form_save_changes extends \totara_core\hook\base {

    /**
     * True if a new user is being created, false if an existing user is being updated.
     * @var bool
     */
    public $iscreating = true;

    /**
     * The user id.
     * During creation this hook is called after the user has been created so we always have an ID.
     * @var int
     */
    public $userid;

    /**
     * The user context.
     * During creation this hook is called after the user has been created so we always have a context.
     * @var \context_user
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
     * @param int $userid
     * @param \stdClass $data Data from the form, via {@see \user_editadvanced_form::get_data()}
     */
    public function __construct($iscreating, $userid, \stdClass $data) {
        $this->iscreating = (bool)$iscreating;
        $this->userid = $userid;
        $this->context = \context_user::instance($userid, MUST_EXIST);
        $this->data = $data;
    }
}