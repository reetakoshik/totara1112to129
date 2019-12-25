<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\form\element;

defined('MOODLE_INTERNAL') || die();

use totara_form\form\element\radios;

/**
 * The sitepolicy form element displays a sitepolicy statement as well as
 * all consent statements associated with it.
 * It ensures that rendering of a policy is done in a consistent manner both
 * during previewing while creating the policy or obtaining actual consent
 * from the user
 *
 * @package tool_sitepolicy\form\element
 */
class sitepolicy extends \totara_form\element {

    /** @var array $options */
    private $options;

    /**
     * Sitepolicy preview element constructor.
     *
     * @param string $name
     * @param string[] $options associative array "option value"=>"option text"
     */
    public function __construct($name, array $options) {
        if (func_num_args() > 2) {
            debugging('Extra unused constructor parameters detected.', DEBUG_DEVELOPER);
        }

        parent::__construct($name, '');
        if (empty($options)) {
            throw new \coding_exception('List of sitepolicy options cannot be empty');
        }

        if (!array_key_exists('title', $options)) {
            throw new \coding_exception('"title" option must be provided');
        }
        if (!array_key_exists('policytext', $options)) {
            throw new \coding_exception('"policytext" option must be provided');
        }
        if (!array_key_exists('statements', $options)) {
            throw new \coding_exception('"statements" option must be provided');
        }

        // Normalise the values that are stored as keys.
        $this->options = [];
        foreach ($options as $k => $v) {
            $this->options[(string)$k] = $v;
        }

        $this->options['policytextformat'] = $this->options['policytextformat'] ?? FORMAT_HTML;
        $this->options['whatsnewformat'] = $this->options['whatsnewformat'] ?? FORMAT_HTML;
    }

    /**
     * Get submitted data without validation.
     * All options are returned
     *
     * @return array
     */
    public function get_data() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($this->is_frozen()) {
            return [$name => $this->get_current_value()];
        }

        // Get all post data and filter the options out
        $data = $model->get_raw_post_data();
        $options = array_filter($data, function ($key) {
            return preg_match('/^option[0-9]/', $key);
        }, ARRAY_FILTER_USE_KEY);

        return [$name => $options];
    }

    /**
     * Get the value of sitepolicy options.
     *
     * @return string|null
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($model->is_form_submitted() and !$this->is_frozen()) {
            $data = $this->get_data();
            if (isset($data[$name])) {
                return $data[$name];
            }
        }

        return null;
    }

    /**
     * No files from sitepolicy
     *
     * @return array
     */
    public function get_files() {
        return [];
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $this->get_model()->require_finalised();

        $result = [
            'form_item_template' => 'tool_sitepolicy/element_sitepolicy',
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'title' => $this->options['title'],
            'policytext' => format_text($this->options['policytext'], $this->options['policytextformat']),
        ];

        if (!empty($this->options['whatsnew'])) {
            $result['whatsnew'] = format_text($this->options['whatsnew'], $this->options['whatsnewformat']);
        }

        $viewonly = $this->options['viewonly'] ?? false;
        $result['statements'] = [];
        foreach ($this->options['statements'] as $idx => $consentstatement) {
            $statement = $consentstatement['mandatory']
                ? get_string('userconsenttoaccess', 'tool_sitepolicy', $consentstatement['statement'])
                : $consentstatement['statement'];

            $dataid = $consentstatement['dataid'] ?? $idx;
            $radiobutton = new radios("option$dataid", $statement,
                    ['1' => $consentstatement['provided'], '0' => $consentstatement['withheld']]);
            $radiobutton->set_parent($this->get_parent());
            $radiobutton->set_attribute('required', !$viewonly);
            $result['statements'][] = $radiobutton->export_for_template($output);
        }

        // Potentially needed if we ever add attributes. Currently not strictly needed.
        $attributes = $this->get_attributes();
        $this->set_attribute_template_data($result, $attributes);

        return $result;
    }

    /**
     * Returns current value or nothing.
     *
     * @return string|null
     */
    protected function get_initial_value() {
        return $this->get_current_value();
    }
}