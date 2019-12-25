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
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package totara_reportbuilder
 */

class rb_filter_system_role extends rb_filter_type {

    /**
     * @const Logical operator indicating the role is assigned to the user.
     */
    const OPERATOR_ASSIGNED = 1;

    /**
     * @const Logical operator indicating the role is not assigned to the user.
     */
    const OPERATOR_NOTASSIGNED = 2;

    /**
     * @var array Cached list of value role ids for the filter.
     */
    private $role_ids = [];

    /**
     * Construct
     *
     * @param string $type The filter type (from the db or embedded source).
     * @param string $value The filter value (from the db or embedded source).
     * @param integer $advanced If the filter should be shown by default (0) or only when advanced options are shown (1).
     * @param integer $region Which region this filter appears in.
     * @param \reportbuilder $report The report this filter is for.
     *
     * @return rb_filter_select object
     */
    public function __construct($type, $value, $advanced, $region, $report) {
        parent::__construct($type, $value, $advanced, $region, $report);

        if (empty($this->options['selectchoices'])) {
            debugging("No selectchoices array provided for filter '{$this->name}' in source '" . get_class($report->src) . "'.", DEBUG_DEVELOPER);
            $this->options['selectchoices'] = array();
        } else if (!is_array($this->options['selectchoices'])) {
            debugging("selectchoices provided for filter '{$this->name}' in source '" . get_class($report->src) . "' must be an array.", DEBUG_DEVELOPER);
            $this->options['selectchoices'] = array();
        } else {
            // Get a list of valid role ids.
            $this->role_ids = array_filter(array_keys($this->options['selectchoices']));
        }

        if (empty($this->options['attributes'])) {
            $this->options['attributes'] = array();
        }
    }

    /**
     * Adds controls specific to this filter in the form.
     *
     * @param MoodleQuickForm $mform a MoodleForm object to setup.
     */
    public function setupForm(&$mform) {
        global $SESSION;

        $objs = array();
        // Create the operator options.
        foreach ($this->get_operator_options() as $value => $label) {
            $objs[] = $mform->createElement('radio', $this->name.'_operator', null, $label, $value);
            $objs[] = $mform->createElement('static', null, null, html_writer::empty_tag('br'));
        }
        $mform->setType($this->name . '_operator', PARAM_INT);

        // Create the selectchoices select.
        $objs[] = $mform->createElement('select', $this->name, null, $this->get_custom_options(), $this->options['attributes']);
        $mform->setType($this->name, PARAM_TEXT);

        // Add the options as a group to the form.
        $mform->addElement('group', $this->name . '_grp', format_string($this->label), $objs, '', false);

        if ($this->advanced) {
            $mform->setAdvanced($this->name . '_grp');
        }

        // Set default values.
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        }
        if (isset($defaults['operator'])) {
            $mform->setDefault($this->name . '_operator', $defaults['operator']);
        } else {
            $options = array_keys($this->get_operator_options());
            $default = array_shift($options);
            $mform->setDefault($this->name . '_operator', $default);
        }
        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, $defaults['value']);
        }
    }

    /**
     * Retrieves and validates the form data.
     *
     * @param object $formdata data submit in the form.
     * @return mixed array filter data or false when filter is invalid.
     */
    public function check_data($formdata) {
        // Get the form values and do a basic validation.
        $field = $this->name;
        $value = isset($formdata->$field) ? $formdata->$field : null;

        $field_operator = $this->name . '_operator';
        $value_operator = isset($formdata->$field_operator) ? intval($formdata->$field_operator) : null;

        $valid = array ();

        // Validate the form data and check that the values are valid.
        if (isset($value) && array_key_exists($field, $formdata) && in_array($value, array_keys($this->get_custom_options()))) {
            $valid['value'] = $value;
        }
        if (isset($value_operator) && array_key_exists($field_operator, $formdata) && in_array($value_operator, array_keys($this->get_operator_options()))) {
            $valid['operator'] = $value_operator;
        }
        if ($valid) {
            return $valid;
        }

        return false;
    }

    /**
     * Returns the SQL and parameters required to apply the filter.
     *
     * @param array $data filter settings.
     * @return array SQL string and $params.
     */
    public function get_sql_filter($data) {
        global $DB;

        $sql = '';
        $params = array();

        if ($data['value'] !== '') {
            $table = rb_unique_param('role_assignments');
            $context = rb_unique_param('system_role');
            $params[$context] = SYSCONTEXTID;

            if ($data['value']) {
                $field = rb_unique_param('system_role');
                $params[$field] = $data['value'];

                $sql = "EXISTS(SELECT roleid FROM {role_assignments} {$table} WHERE {$table}.userid = base.id AND {$table}.contextid = :{$context} AND {$table}.roleid = :{$field})";
            } else {
                list($in_sql, $in_params) = $DB->get_in_or_equal($this->role_ids, SQL_PARAMS_NAMED, $this->filtertype);
                $sql = "EXISTS(SELECT roleid FROM {role_assignments} {$table} WHERE {$table}.userid = base.id AND {$table}.contextid = :{$context} AND {$table}.roleid {$in_sql})";

                $params = array_merge($params, $in_params);
            }

            if ($data['operator'] == self::OPERATOR_NOTASSIGNED) {
                $sql = 'NOT ' . $sql;
            }
        } else {
            $sql = ' 1=1 ';
        }

        return array($sql, $params);
    }

    /**
     * Returns a description of the filter for use in a saved search.
     *
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {
        $options = $this->get_custom_options();
        $operator = ($data['operator'] == 1 ? 'assigned' : 'notassigned');

        if ($data['operator'] && $data['value'] == '0') {
            $label = get_string($operator . 'anyrole', 'totara_reportbuilder');
        } else if ($data['operator'] && !empty($data['value'])) {
            $label = get_string($operator . 'role', 'totara_reportbuilder', array('role' => $options[$data['value']]));
        } else {
            $label = get_string('noroleselected' ,'totara_reportbuilder');
        }

        return $label;
    }


    /**
     * Returns an array of comparison operators.
     *
     * @return array of comparison operators.
     */
    private function get_operator_options() {
        return array(
            self::OPERATOR_ASSIGNED => get_string('assigned', 'totara_reportbuilder'),
            self::OPERATOR_NOTASSIGNED => get_string('notassigned', 'totara_reportbuilder')
        );
    }

    /**
     * Returns an array of formatted select options.
     *
     * @return array of options.
     */
    private function get_custom_options() {
        $options = array();

        // Make sure the options from selectchoices are set up correctly for the form.
        foreach ($this->options['selectchoices'] as $key => $option) {
            $formattedoption = format_string($option);

            if (!is_numeric($key)) {
                if ($key === $option) {
                    $formattedkey = $formattedoption;
                } else {
                    $formattedkey = format_string($key);
                }
            } else {
                // If the key is numeric just use it as is.
                $formattedkey = $key;
            }

            $options[$formattedkey] = $formattedoption;
        }

        return $options;
    }
}
