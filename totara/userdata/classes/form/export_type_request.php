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

defined('MOODLE_INTERNAL') || die();

/**
 * Form for user export request.
 */
final class export_type_request extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $exporttypes = \totara_userdata\userdata\manager::get_export_types('self');
        $exporttypes = array('' => get_string('choosedots')) + $exporttypes;
        $exporttypeid = new \totara_form\form\element\select('exporttypeid', get_string('exporttype', 'totara_userdata'), $exporttypes);
        $this->model->add($exporttypeid);
        $exporttypeid->set_attribute('required', 1);

        $this->model->add_action_buttons(true, get_string('exportrequest', 'totara_userdata'));
    }
}
