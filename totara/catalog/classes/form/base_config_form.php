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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\form;

use totara_catalog\local\config;
use totara_catalog\local\config_form_helper;
use totara_form\form;
use totara_form\form\element\action_button;
use totara_form\form\element\hidden;
use totara_form\form\group\buttons;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for totara catalog configuration forms.
 *
 * @package totara_catalog
 */
abstract class base_config_form extends form {

    private $form_helper = null;

    const EMPTY_OPTION_VALUE = '';
    const CANCEL_BTN_NAME = 'cancelbutton';
    const SUBMIT_BTN_NAME = 'submitbutton';

    /**
     * Have to check our cancelbutton separately because totara form doesn't like that it
     * is set back to frozen after submit.
     *
     * @return bool
     */
    public function is_cancelled(): bool {
        $cancelbutton = optional_param(self::CANCEL_BTN_NAME, null, PARAM_TEXT);
        if (!is_null($cancelbutton)) {
            return true;
        }

        return parent::is_cancelled();
    }

    /**
     * Add a standardised empty option at the beginning of a given options array. Used for select elements.
     *
     * @param array $options
     * @param string $lang_key
     * @return array
     */
    protected function add_empty_option(array $options, string $lang_key = 'empty_select_option'): array {
        $empty_option = [self::EMPTY_OPTION_VALUE => get_string($lang_key, 'totara_catalog')];
        return $empty_option + $options;
    }

    /**
     * Add a submit & cancel button group to the form.
     *
     * @return buttons
     */
    public function add_action_buttons(): buttons {
        $this->model->add(new hidden('tab', PARAM_TEXT));
        $this->model->add(new hidden('configformhiddenflag', PARAM_TEXT));

        /** @var buttons $button_group */
        $button_group = $this->model->add(new buttons('actionbuttonsgroup'), -1);

        $submitbutton = new action_button(
            self::SUBMIT_BTN_NAME,
            get_string('save', 'totara_catalog'),
            action_button::TYPE_SUBMIT
        );
        $button_group->add($submitbutton);

        $cancelbutton = new action_button(
            self::CANCEL_BTN_NAME,
            get_string('undo_changes', 'totara_catalog'),
            action_button::TYPE_CANCEL
        );
        $button_group->add($cancelbutton);

        // Freeze buttons initially except for ajax reload. JS must take care of unfreezing when a change is made to the form.
        if (!$this->model->get_raw_post_data('___tf_reload')) {
            $submitbutton->set_frozen(true);
            $cancelbutton->set_frozen(true);
        }
        return $button_group;
    }

    /**
     * @return config_form_helper
     */
    protected function form_helper(): config_form_helper {
        if (is_null($this->form_helper)) {
            $this->form_helper = new config_form_helper();
        }
        return $this->form_helper;
    }

    /**
     * @return config
     */
    protected function config(): config {
        return $this->form_helper()->config;
    }

    /**
     * Remove selected elements if corresponding placeholders don't exist any more.
     *
     * @param $selected_keys
     * @param array $placeholders
     * @return array
     */
    protected function remove_invalid_placeholders($selected_keys, array $placeholders): array {
        if (empty($selected_keys) || !is_array($selected_keys)) {
            return [];
        }
        return array_filter(
            $selected_keys,
            function ($key) use ($placeholders) {
                return isset($placeholders[$key]);
            }
        );
    }
}
