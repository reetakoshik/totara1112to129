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

use html_writer;
use moodle_url;
use totara_catalog\dataformatter\formatter;
use totara_catalog\local\config;
use totara_catalog\local\config_form_helper;
use totara_catalog\provider;
use totara_catalog\provider_handler;
use totara_form\form_controller;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for totara catalog configuration form controllers.
 *
 * @package totara_catalog
 */
abstract class base_config_form_controller extends form_controller {

    /** @var base_config_form $form */
    protected $form = null;

    /** @var config_form_helper */
    protected $form_helper;

    /**
     * Get the form key unique to the subclass.
     *
     * @return string
     */
    abstract public function get_form_key(): string;

    /**
     * Factory method for getting a form controller instance for the given form key.
     *
     * @param string $form_key
     * @return base_config_form_controller
     */
    public static function create_from_key(string $form_key): base_config_form_controller {
        $form_controller_class = 'totara_catalog\\form\\config_' . $form_key . '_controller';
        return new $form_controller_class();
    }

    public function __construct() {
        $this->form_helper = config_form_helper::create();
    }

    /**
     * We don't use ajax for form submission but we do for dynamic reload, so we have to
     * extend form_controller and implement its methods.
     */
    public function process_ajax_data() {
    }

    public function get_ajax_form_instance($idsuffix) {
        return $this->get_form_instance();
    }

    /**
     * Get a form instance.
     *
     * @return base_config_form
     */
    public function get_form_instance(): base_config_form {
        if (is_null($this->form)) {
            require_capability('totara/catalog:configurecatalog', \context_system::instance());

            list($currentdata, $params) = $this->get_current_data_and_params();
            $form_class = 'totara_catalog\\form\\config_' . $this->get_form_key();
            $this->form = new $form_class($currentdata, $params, $this->get_form_key());
        }
        return $this->form;
    }

    /**
     * Process submitted form data.
     *
     * This does the actual saving of the data and must be called when the form submission was determined to be valid.
     *
     * @return array [
     *     'data' => totara form data array,
     *     'success_msg' => success notification text,
     *     'warning_msg' => warning notification text
     * ]
     */
    public function process_data(): array {
        $result = [];
        $result['data'] = $this->get_submission_data();
        $this->form_helper->update_from_form_data($result['data']);

        $catalog_link = html_writer::link(
            new moodle_url('/totara/catalog/index.php'),
            get_string('view_catalog', 'totara_catalog')
        );
        $result['success_msg'] = get_string('changes_saved', 'totara_catalog', ['link' => $catalog_link]);

        return $result;
    }

    /**
     * Wrapper for totara form's get_data.
     *
     * Can be overridden when modifications are necessary.
     *
     * @return array
     */
    public function get_submission_data(): array {
        return (array)$this->get_form_instance()->get_data();
    }

    /**
     * Get current_data and params commonly used by all forms.
     *
     * @return array
     */
    public function get_current_data_and_params(): array {
        $currentdata = $this->form_helper->get_config_for_form();
        $currentdata['configformhiddenflag'] = '1';
        $currentdata['tab'] = $this->get_form_key();

        // We need our own indicator to figure out if the current request is a submit/reload. The totara form is_reloaded() etc.
        // doesn't work for us because we freeze the submit buttons in the resulting form.
        $hidden_flag = optional_param('configformhiddenflag', null, PARAM_TEXT);
        $is_submitted_or_reloaded = !is_null($hidden_flag);

        // Get basic info about available and active providers.
        $all_provider_names = [];
        $active_provider_names = [];
        $active_providers = provider_handler::instance()->get_active_providers();
        /** @var provider $providerclass */
        foreach (provider_handler::instance()->get_all_provider_classes() as $providerclass) {
            $object_type = $providerclass::get_object_type();
            $all_provider_names[$object_type] = $providerclass::get_name();
            if (isset($active_providers[$object_type])) {
                $active_provider_names[$object_type] = $providerclass::get_name();
            }
        }

        $params = [
            'all_provider_names' => $all_provider_names,
            'active_provider_names' => $active_provider_names,
            'is_submitted_or_reloaded' => $is_submitted_or_reloaded,
        ];

        // We need placeholder and icon sources data only for item and details forms.
        if (in_array($this->get_form_key(), ['item', 'details'])) {
            list($params['placeholders'], $params['placeholder_optgroups']) = $this->build_placeholder_form_options();
            $params = array_merge($params, $this->build_icon_source_form_options($currentdata, $is_submitted_or_reloaded));
        }

        return [ $currentdata, $params ];
    }

    /**
     * Build form parameters for icon source options.
     *
     * @param array $currentdata
     * @param bool $is_submitted_or_reloaded
     * @return array
     */
    private function build_icon_source_form_options(array $currentdata, bool $is_submitted_or_reloaded): array {
        $params_icons = [];
        foreach (provider_handler::instance()->get_active_providers() as $provider) {
            $element_key = $this->form_helper->build_element_key(
                $this->get_form_key() . '_additional_icons',
                $provider->get_object_type()
            );
            if ($is_submitted_or_reloaded) {
                $currentdata[$element_key] = optional_param($element_key, [], PARAM_RAW);
                if ($currentdata[$element_key] !== []) {
                    $currentdata[$element_key] = json_decode($currentdata[$element_key]) ?? [];
                }
            }
            $params_icons[$element_key] = $currentdata[$element_key] ?? [];
        }
        return $params_icons;
    }

    /**
     * Build form parameters for placeholder options.
     *
     * @return array
     */
    private function build_placeholder_form_options(): array {
        $formatter_types = [
            'title' => formatter::TYPE_PLACEHOLDER_TITLE,
            'text' => formatter::TYPE_PLACEHOLDER_TEXT,
            'icon' => formatter::TYPE_PLACEHOLDER_ICON,
            'icons' => formatter::TYPE_PLACEHOLDER_ICONS,
            'richtext' => formatter::TYPE_PLACEHOLDER_RICH_TEXT,
        ];
        $placeholder_data = [];
        foreach (provider_handler::instance()->get_active_providers() as $provider) {
            $object_type = $provider->get_object_type();
            foreach ($formatter_types as $placeholder_type => $formatter_type) {
                $placeholder_data[$object_type][$placeholder_type] = [];
                foreach ($provider->get_dataholders($formatter_type) as $placeholder) {
                    $optgroup_name = (string)$placeholder->category;
                    $placeholder_data[$object_type][$placeholder_type][$optgroup_name][$placeholder->key] = $placeholder->name;
                }
            }
        }

        return $this->form_helper->build_optgroups_for_placeholders($placeholder_data);
    }


    /**
     * Configured data may have been saved with options that aren't valid any more, e.g. when a previously configured
     * placeholder is removed from the code. So remove invalid options from currentdata to avoid totara form errors.
     *
     * @param array $currentdata
     * @param array $params
     * @return array
     */
    protected function remove_invalid_currentdata(array $currentdata, array $params): array {
        $form_key = $this->get_form_key();
        if (in_array($form_key, ['item', 'details'])) {
            $active_providers = $params['active_provider_names'];
            $placeholders = $params['placeholders'];
            $provider_defaults = config::instance()->get_provider_defaults();

            // Title
            foreach ($active_providers as $key => $label) {
                $element_key = $this->form_helper->build_element_key($form_key . '_title', $key);
                if (!empty($currentdata[$element_key]) &&
                    !array_key_exists($currentdata[$element_key], $placeholders[$key]['title'])) {
                    $currentdata[$element_key] = $provider_defaults[$form_key . '_title'][$key];
                }
            }

            // Description
            if ($currentdata[$form_key . '_description_enabled'] === '1') {
                foreach ($active_providers as $key => $label) {
                    if (!empty($placeholders[$key]['text'])) {
                        $element_key = $this->form_helper->build_element_key($form_key . '_description', $key);
                        if (!empty($currentdata[$element_key]) &&
                            !array_key_exists($currentdata[$element_key], $placeholders[$key]['text'])) {
                            $currentdata[$element_key] = base_config_form::EMPTY_OPTION_VALUE;
                        }
                    }
                }
            }

            // Additional text field(s)
            $additional_text_count = $currentdata[$form_key . '_additional_text_count'];
            if ($additional_text_count > 0) {
                foreach ($active_providers as $key => $label) {
                    if (!empty($placeholders[$key]['text'])) {
                        for ($i = 0; $i < $additional_text_count; $i++) {
                            $element_key = $this->form_helper->build_element_key($form_key . '_additional_text', $key, $i);
                            if (!empty($currentdata[$element_key]) &&
                                !array_key_exists($currentdata[$element_key], $placeholders[$key]['text'])) {
                                $currentdata[$element_key] = base_config_form::EMPTY_OPTION_VALUE;
                            }
                        }
                    }
                }
            }
        }

        return $currentdata;
    }
}
