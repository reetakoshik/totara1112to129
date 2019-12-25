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
 * Delete Main menu item.
 */
final class delete extends \totara_form\form {
    public function definition() {
        global $OUTPUT;

        $itemtitle = $this->get_parameters()['itemtitle'];

        $warning = $OUTPUT->notification(get_string('menuitem:delete', 'totara_core', $itemtitle), \core\output\notification::NOTIFY_WARNING);
        $this->model->add(new \totara_form\form\element\static_html('warning', '', $warning));

        $options = $this->get_parameters()['parentidoptions'];
        $parentid = new \totara_form\form\element\select('parentid', get_string('menuitem:formitemparent', 'totara_core'), $options);
        $parentid->set_frozen(true);
        $this->model->add($parentid);

        $this->model->add(new \totara_form\form\element\static_html('title', get_string('menuitem:formitemtitle', 'totara_core'), $itemtitle));

        $this->model->add_action_buttons(true, get_string('delete'));

        $this->model->add(new \totara_form\form\element\hidden('id', PARAM_INT));
    }
}
