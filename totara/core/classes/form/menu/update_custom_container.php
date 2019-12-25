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

use \totara_core\totara\menu\item;
use \totara_core\totara\menu\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Form to update custom container.
 */
final class update_custom_container extends \totara_form\form {
    public function definition() {
        $options = $this->get_parameters()['parentidoptions'];
        $parentid = new \totara_form\form\element\select('parentid', get_string('menuitem:formitemparent', 'totara_core'), $options);
        $this->model->add($parentid);

        $title = new \totara_form\form\element\text('title', get_string('menuitem:formitemtitle', 'totara_core'), PARAM_TEXT);
        $title->add_help_button('menuitem:formitemtitle', 'totara_core');
        $title->set_attributes(array('required'=> 1, 'maxlength' => 1024, 'size' => 100));
        $this->model->add($title);

        $options = array(
            item::VISIBILITY_SHOW => get_string('menuitem:show', 'totara_core'),
            item::VISIBILITY_HIDE => get_string('menuitem:hide', 'totara_core'),
            item::VISIBILITY_CUSTOM => get_string('menuitem:showcustom', 'totara_core'),
        );
        $visibility = new \totara_form\form\element\radios('visibility', get_string('menuitem:formitemvisibility', 'totara_core'), $options);
        $this->model->add($visibility);

        $this->model->add_action_buttons(true, get_string('savechanges'));

        $this->model->add(new \totara_form\form\element\hidden('id', PARAM_INT));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array list of errors
     */
    public function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        if (trim($data['title']) === '') {
            $errors['title'] = get_string('required');
        }

        return $errors;
    }
}
