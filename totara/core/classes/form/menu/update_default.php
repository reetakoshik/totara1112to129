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

use totara_core\totara\menu\container;
use \totara_core\totara\menu\item;
use \totara_core\totara\menu\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Form to update default container.
 */
final class update_default extends \totara_form\form {
    public function definition() {
        /** @var item $node */
        $node = $this->get_parameters()['item'];

        $options = $this->get_parameters()['parentidoptions'];
        $parentid = new \totara_form\form\element\select('parentid', get_string('menuitem:formitemparent', 'totara_core'), $options);
        $this->model->add($parentid);

        $customtitle = new \totara_form\form\element\checkbox('customtitle', get_string('menuitem:formitemcustomtitle', 'totara_core'));
        $this->model->add($customtitle);

        // Hack around wrongly protected class.
        $rc = new \ReflectionClass($node->get_classname());
        $rcdefaulttitle = $rc->getMethod('get_default_title');
        $rcdefaulttitle->setAccessible(true);
        $text = $rcdefaulttitle->invoke($node);
        $defaulttitle = new \totara_form\form\element\static_html('defaulttitle', get_string('menuitem:formitemtitle', 'totara_core'), $text);
        $this->model->add($defaulttitle);
        $this->model->add_clientaction(new \totara_form\form\clientaction\hidden_if($defaulttitle))->is_equal($customtitle, '1');

        $title = new \totara_form\form\element\text('title', get_string('menuitem:formitemtitle', 'totara_core'), PARAM_TEXT);
        $title->add_help_button('menuitem:formitemtitle', 'totara_core');
        $title->set_attributes(array('maxlength' => 1024, 'size' => 100));
        $this->model->add($title);
        $this->model->add_clientaction(new \totara_form\form\clientaction\hidden_if($title))->is_equal($customtitle, '0');

        $options = array(
            item::VISIBILITY_SHOW => get_string('menuitem:showwhenrequired', 'totara_core'),
            item::VISIBILITY_HIDE => get_string('menuitem:hide', 'totara_core'),
            item::VISIBILITY_CUSTOM => get_string('menuitem:showcustom', 'totara_core'),
        );
        if ($node instanceof container) {
            // Access control for container makes little sense, so use simple Show string.
            $options[item::VISIBILITY_SHOW] = get_string('menuitem:show', 'totara_core');
        }
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

        if ($data['customtitle']) {
            if (trim($data['title']) === '') {
                $errors['title'] = get_string('required');
            }
        }

        return $errors;
    }
}
