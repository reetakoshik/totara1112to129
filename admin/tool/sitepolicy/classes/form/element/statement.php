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
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\form\element;

defined('MOODLE_INTERNAL') || die();

use  tool_sitepolicy\form\validator\statement_required;

/**
 * Class statement
 *
 * @package tool_sitepolicy\form\element
 */
class statement extends \totara_form\element {

    /** @var \tool_sitepolicy\statement[] */
    private $statements;

    /**
     * Remove add/remove controls from form
     * @var bool
     */
    private $nocontrols = false;

    /**
     * Statement element constructor.
     *
     * @param string $name
     * @param bool $nocontrols
     * @param bool Remove add/remove controls from form
     */
    public function __construct(string $name, bool $nocontrols = false) {
        parent::__construct($name, '');
        $this->nocontrols = $nocontrols;

        $this->attributes = array(
            'size' => null,
            'required' => false,
        );

        $this->add_validator(new statement_required());
    }

    /**
     * Adds a statement to this element.
     *
     * @param \tool_sitepolicy\statement $statement
     */
    private function add_statement(\tool_sitepolicy\statement $statement) {
        $this->statements[] = $statement;
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $this->get_model()->require_finalised();

        if ($this->has_requested_add_statement()) {
            $this->add_statement(new \tool_sitepolicy\statement());
        }

        $nocontrols = $this->nocontrols;
        if ($this->is_frozen()) {
            $nocontrols = true;
        }

        $result = array(
            'form_item_template' => 'tool_sitepolicy/element_consent_statement',
            'nocontrols' => $nocontrols,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'amdmodule' => 'tool_sitepolicy/form_element_consent_statement',
        );

        // Potentially needed if we ever add attributes. Currently not strictly needed.
        $attributes = $this->get_attributes();
        $this->set_attribute_template_data($result, $attributes);

        $result['consent_statements'] = [];
        $count = 1;
        $value = $this->get_field_value();
        foreach ($value[$this->get_name()] as $statement) {
            if ($statement instanceof \tool_sitepolicy\statement) {
                $statement->instance = $count;
                $statement->index = $count - 1;
                $count++;
                $result['consent_statements'][] = $statement->export_for_template();
            }
        }

        if ($this->has_requested_add_statement()) {
            $statement = new \tool_sitepolicy\statement();
            $statement->instance = $count;
            $statement->index = $count - 1;
            // Not strictly needed, but kept in order to reduce the chance of regressions in the future.
            $count++;
            $result['consent_statements'][] = $statement->export_for_template();
        }

        $result['consent_statement_count'] = count($result['consent_statements']);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Returns the value for this field.
     * @return array
     */
    public function get_field_value(): array {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($model->is_form_submitted() and !$this->is_frozen()) {
            $data = $this->get_data();
            return $data;
        }

        $current = $model->get_current_data($name);
        if (!empty($current)) {
            return $current;
        }
        $return = [
            $name => [new \tool_sitepolicy\statement()]
        ];
        return $return;
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data(): array {
        $model = $this->get_model();
        $name = $this->get_name();
        $return = [
            $name => []
        ];


        if ($this->is_frozen()) {
            $current = $model->get_current_data($name);
            if (!isset($current[$name]) || empty($current[$name]) || !is_array($current[$name])) {
                return $return;
            } else {
                // Usually the current data should not be modified, but security is more important here.
                foreach ($current[$name] as $statement) {
                    if ($statement instanceof \tool_sitepolicy\statement) {
                        $return[$name][] = $statement->clean();
                    }
                }
                return $return;
            }
        }

        if (!$this->has_been_submitted()) {
            return $return;
        }

        $data = $this->extract_data();
        if ($data === null || !is_array($data)) {
            // No value in _POST or invalid value format, this should not happen.
            return $return;
        } else {
            foreach ($data as $statement) {
                if ($statement instanceof \tool_sitepolicy\statement) {
                    $return[$name][] = $statement->clean();
                }
            }
            return $return;
        }
    }

    /**
     * Returns true if this element has submitted or reloaded data.
     * @return bool
     */
    private function has_been_submitted(): bool {
        $name = $this->get_name();
        $data = $this->get_model()->get_raw_post_data($name . '__statement_count');
        return (isset($data));
    }

    /**
     * Returns true if the user has clicked the button to add statements.
     * @return bool
     */
    private function has_requested_add_statement(): bool {
        if (!$this->has_been_submitted() || $this->nocontrols) {
            return false;
        }
        $name = $this->get_name();
        $data = $this->get_model()->get_raw_post_data($name . '__addstatement');
        return (!empty($data));
    }

    /**
     * Compare element value.
     * Warning: Doesn't support $value2
     *
     * @param string $operator open of model::OP_XXX operators
     * @param mixed $value2 (not supported)
     * @param bool $finaldata true means use get_data(), false means use get_field_value()
     * @return bool result
     */
    public function compare_value($operator, $value2 = null, $finaldata = true): bool {
        $value1 = null;
        if ($finaldata) {
            $data = $this->get_data();
            $name = $this->get_name();
            if (isset($data[$name])) {
                $value1 = $data[$name];
            }
        } else {
            $value1 = $this->get_field_value();
        }

        foreach ($value1 as $value) {
            if (!empty($value->removedstatement)) {
                continue;
            }
            $statementcomp = $this->get_model()->compare($value->statement, $operator);
            $providedcomp = $this->get_model()->compare($value->provided, $operator);
            $withheldcomp = $this->get_model()->compare($value->withheld, $operator);
            if (!($statementcomp && $providedcomp && $withheldcomp)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Extracts consent statement data from the form submitted data.
     *
     * @return array of \tool_sitepolicy\statement[]
     * @throws \coding_exception
     */
    private function extract_data(): array {
        $name = $this->get_name();
        $data = $this->get_model()->get_raw_post_data();
        $properties = \tool_sitepolicy\statement::get_properties();

        if (!isset($data[$name . '__statement_count'])) {
            // You should check that data has been submit before trying to extract it!
            throw new \coding_exception('Unexpected structure, could not find the statement count.');
        }

        // Clean, just in case.
        $expectedcount = clean_param($data[$name . '__statement_count'], PARAM_INT);
        if ($expectedcount === 0) {
            return [];
        }

        $statements = null;
        foreach ($properties as $property => $type) {
            $propertyname = $name . '__' . $property;

            if (is_null($type)) {
                unset($properties[$name]);
                continue;
            }

            // Special treatment for mandatory, as it is checkbox that not always send data, but when it sends, we
            // need to know exactly which of them sent (e.g. if only first and last was sent they should not become
            // first and second.
            if ($propertyname === 'statements__mandatory') {
                // If nocontrols, the mandatory checkboxes are disabled and therefore not availble in the post data
                // In this case we need to get it from the currentdata
                if (!empty($data[$propertyname])) {
                    $mandatories = $data[$propertyname];
                } else {
                    $curdata = $this->get_model()->get_current_data(null);
                    $mandatories = [];

                    // We first need the dataid to match up with the correct statement in currentdata
                    // We only get here for non-primary translations. In this case all consent_options would
                    // have been persisted and statements will therefore have a negative dataid index in the currentdata structure
                    $dataids = $data[$name . '__dataid'] ?? [];
                    foreach ($dataids as $dataid) {
                        $mandatories[] = $curdata['statements'][-1 * $dataid]->mandatory ?? 0;
                    }
                }

                for ($i = 0; $i < $expectedcount; $i++) {
                    $mandatories[$i] = $mandatories[$i] ?? 0;
                }
                $data[$propertyname] = $mandatories;
            }

            if (!isset($data[$propertyname]) || !is_array($data[$propertyname])) {
                throw new \coding_exception('Expected data structure not present', $propertyname);
            }

            $propertydata = $data[$propertyname];
            if ($statements === null) {
                $statements = [];
                // First pass through.
                if ($expectedcount !== count($propertydata)) {
                    throw new \coding_exception("Property counts do not match, expected {$expectedcount} got " . count($propertydata), $propertyname);
                }
                foreach ($propertydata as $key => $value) {
                    $object = new \tool_sitepolicy\statement();
                    $object->{$property} = $value;
                    $statements[(int)$key] = $object;
                }
                continue;
            }

            // The objects exist now.
            if (count($propertydata) !== $expectedcount) {
                throw new \coding_exception("Property counts do not match, expected {$expectedcount} got " . count($propertydata), $propertyname);
            }
            foreach ($propertydata as $key => $value) {
                $statements[$key]->{$property} = $value;
            }
        }
        return $statements;
    }
}