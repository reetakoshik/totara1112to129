<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\hook;

defined('MOODLE_INTERNAL') || die();

final class program_edit_form_definition_complete extends base {
    /**
     * @var \program_edit_form
     */
    private $form;

    /**
     * This could be either add/edit/view
     * @var string
     */
    private $action;

    /**
     * program_edit_form_definition_complete constructor.
     *
     * @param \program_edit_form    $form
     * @param int                   $programid
     * @param string                $action
     */
    public function __construct(\program_edit_form $form, int $programid, string $action) {
        parent::__construct($programid);
        $this->form = $form;
        $this->action = $action;
    }

    /**
     * @return \program_edit_form
     */
    public function get_form(): \program_edit_form {
        return $this->form;
    }

    /**
     * @return string
     */
    public function get_action(): string {
        return $this->action;
    }
}