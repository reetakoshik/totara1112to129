<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Generic filter based on a list of values.
 */
class rb_filter_select extends rb_filter_type {

    /**
     * Constructor
     *
     * @param string $type The filter type (from the db or embedded source)
     * @param string $value The filter value (from the db or embedded source)
     * @param integer $advanced If the filter should be shown by default (0) or only
     *                          when advanced options are shown (1)
     * @param integer $region Which region this filter appears in.
     * @param reportbuilder object $report The report this filter is for
     * @param array $defaultvalue Default value for the filter
     *
     * @return rb_filter_select object
     */
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        // set defaults for optional rb_filter_select options
        if (!isset($this->options['simplemode'])) {
            $this->options['simplemode'] = false;
        }
        if (!isset($this->options['selectfunc'])) {
            if (!isset($this->options['selectchoices'])) {
                debugging("No selectchoices provided for filter '{$this->name}' in source '" .
                    get_class($report->src) . "'", DEBUG_DEVELOPER);
                $this->options['selectchoices'] = array();
            }
        }
        if (!isset($this->options['attributes'])) {
            $this->options['attributes'] = array();
        }
    }

    /**
     * Returns an array of comparison operators
     *
     * Only used by full select (not by simple select)
     * @return array of comparison operators
     */
    function get_operators() {
        return array(0 => get_string('isanyvalue', 'filters'),
                     1 => get_string('isequalto', 'filters'),
                     2 => get_string('isnotequalto', 'filters'));
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        global $SESSION;
        $label = format_string($this->label);
        $advanced = $this->advanced;
        $defaultvalue = $this->defaultvalue;
        $simplemode = $this->options['simplemode'];
        $attr = $this->options['attributes'];

        $options = array();
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

        if ($simplemode) {
            // simple select mode
            $choices = array('' => get_string('anyvalue', 'filters')) + $options;
            $mform->addElement('select', $this->name, $label, $choices, $attr);
            $mform->setType($this->name, PARAM_TEXT);

            $this->add_help_button($mform, $this->name, 'filtersimpleselect', 'filters');
            if ($advanced) {
                $mform->setAdvanced($this->name);
            }
        } else {
            // full select mode
            $objs = array();
            $objs['select'] = $mform->createElement('select', $this->name.'_op', null, $this->get_operators());
            $objs['text'] = $mform->createElement('select', $this->name, null, $options, $attr);
            $objs['select']->setLabel(get_string('limiterfor', 'filters', $label));
            $objs['text']->setLabel(get_string('valuefor', 'filters', $label));
            $mform->setType($this->name . '_op', PARAM_INT);
            $mform->setType($this->name, PARAM_TEXT);
            $grp =& $mform->addElement('group', $this->name . '_grp', $label, $objs, '', false);
            $this->add_help_button($mform, $grp->_name, 'filterselect', 'filters');
            $mform->disabledIf($this->name, $this->name . '_op', 'eq', 0);
            if ($advanced) {
                $mform->setAdvanced($this->name . '_grp');
            }
        }

        // set default values
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        } else if (!empty($defaultvalue)) {
            $this->set_data($defaultvalue);
        }

        if (!$simplemode && isset($defaults['operator'])) {
            $mform->setDefault($this->name . '_op', $defaults['operator']);
        }
        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, $defaults['value']);
        }

    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field    = $this->name;
        $simplemode = $this->options['simplemode'];

        // Prevent applying filter when value is not sent.
        if (!isset($formdata->$field)) {
            return false;
        }

        if ($simplemode) {
            if (isset($formdata->$field)) {
                return array('value'    => (string)$formdata->$field);
            }
        } else {
            $operator = $field . '_op';
            if (isset($formdata->$operator) && $formdata->$operator != 0) {
                return array('operator' => (int)$formdata->$operator,
                    'value'    => (string)$formdata->$field);
            }
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        global $DB;

        $value = explode(',', $data['value']);
        $query = $this->get_field();
        $simplemode = $this->options['simplemode'];

        if ($simplemode) {
            if (count($value) == 1 && current($value) == '') {
                // return 1=1 instead of TRUE for MSSQL support
                return array(' 1=1 ', array());
            } else {
                // use "equal to" operator for simple select
                $operator = 1;
            }
        } else {
            $operator = $data['operator'];
        }

        if ($operator == 0) {
            // return 1=1 instead of TRUE for MSSQL support
            return array(' 1=1 ', array());
        } else if ($operator == 1) {
            // equal
            list($insql, $inparams) = $DB->get_in_or_equal($value, SQL_PARAMS_NAMED,
                rb_unique_param('fsequal_'));
            return array("{$query} {$insql}", $inparams);
        } else {
            // not equal
            list($insql, $inparams) = $DB->get_in_or_equal($value, SQL_PARAMS_NAMED,
                rb_unique_param('fsequal_'), false);
            // check for null case for is not operator
            return array("({$query} {$insql} OR ({$query}) IS NULL)", $inparams);
        }
    }


    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        $value = $data['value'];
        $simplemode = $this->options['simplemode'];
        $label = format_string($this->label);

        if ($simplemode && $value === '') {
            $a = new stdClass();
            $a->label    = $label;
            $a->value    = get_string('anyvalue', 'filters');
            $a->operator = get_string('isequalto', 'filters');

            return get_string('selectlabel', 'filters', $a);
        }

        $options = $this->options['selectchoices'][$value];

        if ($simplemode) {
            $a = new stdClass();
            $a->label    = $label;
            $a->value    = '"' . s($options) . '"';
            $a->operator = get_string('isequalto', 'filters');
        } else {
            $operators = $this->get_operators();
            $operator  = $data['operator'];
            if (empty($operator)) {
                return '';
            }

            $a = new stdClass();
            $a->label    = $label;
            $a->value    = '"' . s($options) . '"';
            $a->operator = $operators[$operator];
        }

        return get_string('selectlabel', 'filters', $a);
    }
}
