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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\form;

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Select user status before adding new purge type.
 */
final class purge_type_add extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {

        $options = target_user::get_user_statuses();
        $userstatus = new \totara_form\form\element\radios('userstatus', get_string('purgetypeuserstatus', 'totara_userdata'), $options);
        $userstatus->set_attribute('required', 1);
        $userstatus->add_help_button('purgetypeuserstatus', 'totara_userdata');
        $this->model->add($userstatus);

        $this->model->add_action_buttons(true, get_string('continue'));

    }
}
