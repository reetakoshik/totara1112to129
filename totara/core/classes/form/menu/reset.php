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
 * @package totara_core
 */

namespace totara_core\form\menu;

defined('MOODLE_INTERNAL') || die();

/**
 * Reset Main menu items confirmation form.
 */
final class reset extends \totara_form\form {
    public function definition() {
        global $OUTPUT;

        $warning = $OUTPUT->notification(get_string('menuitem:resetwarnign', 'totara_core'), \core\output\notification::NOTIFY_WARNING);
        $this->model->add(new \totara_form\form\element\static_html('warning', '', $warning));

        $options = array(
            '1' => get_string('menuitem:resetbackupcustom', 'totara_core'),
            '0' => get_string('menuitem:resetdeletecustom', 'totara_core'),
        );
        $backupcustom = new \totara_form\form\element\radios('backupcustom', get_string('menuitem:resetcustomoption', 'totara_core'), $options);
        $backupcustom->set_attributes(array('required'=> 1));
        $this->model->add($backupcustom);

        $this->model->add_action_buttons(true, get_string('reset'));

        $this->model->add(new \totara_form\form\element\hidden('reset', PARAM_INT));
    }
}
