<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_reportbuilder
 */

/**
 * Generic filter for URL fields.
 */
class rb_filter_url extends rb_filter_type {

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    public function getOperators() {
        return array(
            self::RB_FILTER_CONTAINS => get_string('isanyvalue', 'filters'),
            self::RB_FILTER_ISEMPTY => get_string('isempty', 'filters'),
            self::RB_FILTER_ISNOTEMPTY => get_string('isnotempty', 'totara_reportbuilder'));
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param MoodleQuickForm $mform a MoodleForm object to setup
     */
    public function setupForm(&$mform) {
        global $SESSION;
        $label = format_string($this->label);
        $defaultvalue = $this->defaultvalue;

        $objs = array();
        $objs['select'] = $mform->createElement('select', $this->name.'_op', null, $this->getOperators());
        $objs['select']->setLabel(get_string('limiterfor', 'filters', $label));

        $mform->setType($this->name . '_op', PARAM_INT);
        $grp =& $mform->addElement('group', $this->name . '_grp', $label, $objs, '', false);
        $this->add_help_button($mform, $grp->_name, 'filterurl', 'filters');

        // Set default values.
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        } else if (!empty($defaultvalue)) {
            $this->set_data($defaultvalue);
        }

        if (isset($defaults['operator'])) {
            $mform->setDefault($this->name . '_op', $defaults['operator']);
        }
    }

    /**
     * Retrieves data from the form data
     *
     * @param stdClass $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata) {
        $field    = $this->name;
        $operator = $field . '_op';
        if (array_key_exists($operator, $formdata)) {
            if ($formdata->$operator != self::RB_FILTER_ISEMPTY && $formdata->$operator != self::RB_FILTER_ISNOTEMPTY) {
                // No data - no change except for empty and not empty filters.
                return false;
            }
            return array('operator' => (int)$formdata->$operator);
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where
     *
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    public function get_sql_filter($data) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/core/searchlib.php');

        $operator = $data['operator'];
        $query    = $this->get_field();

        if ($operator != self::RB_FILTER_ISEMPTY && $operator != self::RB_FILTER_ISNOTEMPTY) {
            return array('', array());
        }

        switch($operator) {
            case self::RB_FILTER_ISEMPTY: // Empty - may also be null.
                return array("({$query} = '' OR ({$query}) IS NULL)", array());
            case self::RB_FILTER_ISNOTEMPTY: // Not empty (NOT NULL).
                return array("({$query} != '' AND ({$query}) IS NOT NULL)", array());
            default:
                return array('', array());
        }
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {

        $operator  = $data['operator'];
        $operators = $this->getOperators();
        $label     = $this->label;

        $a = new stdClass();
        $a->label    = $label;
        $a->operator = $operators[$operator];

        switch ($operator) {
            case self::RB_FILTER_CONTAINS:
            case self::RB_FILTER_DOESNOTCONTAIN:
            case self::RB_FILTER_ISEQUALTO:
            case self::RB_FILTER_STARTSWITH:
            case self::RB_FILTER_ENDSWITH:
                return get_string('textlabel', 'filters', $a);
            case self::RB_FILTER_ISEMPTY:
                return get_string('textlabelnovalue', 'filters', $a);
            case self::RB_FILTER_ISNOTEMPTY:
                return get_string('textlabelnovalue', 'filters', $a);
        }

        return '';
    }
}
