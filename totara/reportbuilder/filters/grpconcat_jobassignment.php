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
 * @package totara
 * @subpackage reportbuilder
 */

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/filters/hierarchy_multi.php');

/**
 * Generic filter based on selecting multiple items from a hierarchy.
 */
class rb_filter_grpconcat_jobassignment extends rb_filter_hierarchy_multi {
    const JOB_OPERATOR_ANY = 0;
    const JOB_OPERATOR_CONTAINS = 1;
    const JOB_OPERATOR_NOTCONTAINS = 2;
    const JOB_OPERATOR_EQUALS = 3;
    const JOB_OPERATOR_NOTEQUALS = 4;

    private $jobfield, $jobjoin, $shortname, $showchildren;

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
     * @return rb_filter_jobassignment_multi object
     */
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        // We don't want to call parent construct because of the extra checks, so directly call the base filter.
        rb_filter_type::__construct($type, $value, $advanced, $region, $report, $defaultvalue);


        // The field in the job_assignment table that this filter refers to.
        if (!isset($this->options['jobfield']) || !isset($this->options['jobjoin'])) {
            throw new ReportBuilderException('Job assignment filters must have a \'jobfield\' set that maps
                to a field in the job_assignment table. And a \'jobjoin\' which defines the table with more
                information on the associated object');
        }

        $this->jobfield = $this->options['jobfield'];
        $this->jobjoin = $this->options['jobjoin'];

        switch($this->jobfield) {
            case 'positionid':
                $this->shortname = 'pos';
                $this->showchildren = true;
                break;
            case 'organisationid':
                $this->shortname = 'org';
                $this->showchildren = true;
                break;
            case 'managerjaid':
                $this->shortname = 'man';
                $this->showchildren = false;
                break;
            case 'appraiserid':
                $this->shortname = 'app';
                $this->showchildren = false;
                break;
            default:
                throw new ReportBuilderException("Jobfield:'{$this->jobfield}' has not been implemented.");
                break;
        }
    }

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    function get_operators() {
        global $CFG;
        // Basic operators that we allow users to use if multiple job assignments are disabled.
        $operators = [
            self::JOB_OPERATOR_ANY => get_string('isanyvalue', 'filters'),
            self::JOB_OPERATOR_CONTAINS => get_string('filtercontains', 'totara_reportbuilder'),
            self::JOB_OPERATOR_NOTCONTAINS => get_string('filtercontainsnot', 'totara_reportbuilder'),
        ];

        // Operators that make sense to add only when multiple job assignments are enabled.
        if (!empty($CFG->totara_job_allowmultiplejobs)) {
            $operators = array_merge($operators, [
                self::JOB_OPERATOR_EQUALS => get_string('filterequals', 'totara_reportbuilder'),
                self::JOB_OPERATOR_NOTEQUALS => get_string('filterequalsnot', 'totara_reportbuilder')
            ]);
        }

        return $operators;
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data) {
        global $DB;

        $value     = explode(',', $data['value']);
        $label = $this->label;
        $type = $this->options['jobjoin'];

        if (empty($value)) {
            return '';
        }

        $a = new stdClass();
        $a->label    = $label;

        $selected = array();
        list($isql, $iparams) = $DB->get_in_or_equal($value);
        $items = $DB->get_records_select($type, "id {$isql}", $iparams);
        foreach ($items as $item) {
            if ($this->shortname == 'man' || $this->shortname == 'app') {
                $item->fullname = isset($item->fullname) ? $item->fullname : fullname($item);
            }
            $selected[] = '"' . format_string($item->fullname) . '"';
        }

        $orstring = get_string('or', 'totara_reportbuilder');
        $a->value    = implode($orstring, $selected);

        return get_string('selectlabelnoop', 'filters', $a);
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    function setupForm(&$mform) {
        global $SESSION, $DB;
        $label = format_string($this->label);
        $advanced = $this->advanced;

        // Get saved values.
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $saved = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        }

        // Container for currently selected items.
        $objs = array();
        $objs['operator'] = $mform->createElement('select', $this->name.'_op', null, $this->get_operators());
        $objs['operator']->setLabel(get_string('limiterfor', 'filters', $label));


        $content = html_writer::tag('div', '', array('class' => 'list-' . $this->name));

        // Create list of saved items.
        if (isset($saved['value'])) {
            list($insql, $inparams) = $DB->get_in_or_equal(explode(',', $saved['value']));
            $items = $DB->get_records_select($this->jobjoin, "id {$insql}", $inparams);
            if (!empty($items)) {
                $list = html_writer::start_tag('div', array('class' => 'list-' . $this->name ));
                foreach ($items as $item) {
                    $list .= display_selected_item($this->shortname, $item, $this->name);
                }
                $list .= html_writer::end_tag('div');
                $content .= $list;
            }
        }

        // Add choose link.
        $content .= display_choose_items_link($this->name, $this->shortname);

        $objs['static'] = $mform->createElement('static', $this->name . '_list', null, $content);

        // Only show the children checkbox for hierarchies.
        if ($this->showchildren) {
            $objs['child'] =& $mform->createElement('checkbox', $this->name.'_child', null, get_string('jobassign_children', 'totara_reportbuilder'));
        }

        $grp =& $mform->addElement('group', $this->name . '_grp', $label, $objs, '', false);
        $this->add_help_button($mform, $grp->_name, 'reportbuilderjobassignmentfilter', 'totara_reportbuilder');
        $mform->disabledIf($this->name . '_child', $this->name . '_op', 'eq', 0);

        if ($advanced) {
            $mform->setAdvanced($this->name.'_grp');
        }

        $mform->addElement('hidden', $this->name);
        $mform->setType($this->name, PARAM_SEQUENCE);
        $mform->setType($this->name . '_op', PARAM_INT);

        // Set saved values.
        if (isset($saved)) {
            $mform->setDefault($this->name, $saved['value']);
            $mform->setDefault($this->name . '_op', $saved['operator']);
            $mform->setDefault($this->name . '_child', $saved['children']);
        }

        // Add maximum select value.
        if (!empty($this->options['selectionlimit'])) {
            $mform->addElement('hidden', $this->name . '_selection_limit', $this->options['selectionlimit']);
            $mform->setType($this->name . '_selection_limit', PARAM_INT);
        }
    }

    function definition_after_data(&$mform) {
        global $DB;

        $group = $mform->getElementValue($this->name . '_grp');
        $operator = !empty($group[$this->name . '_op']) ? $group[$this->name . '_op'] : 0;
        $children = !empty($group[$this->name . '_child']) ? $group[$this->name . '_child'] : 0;

        // Don't set anything if the operator is set to any.
        if (empty($operator[0])) {
            return true;
        }

        if ($ids = $mform->getElementValue($this->name)) {
            list($insql, $inparams) = $DB->get_in_or_equal(explode(',', $ids));
            $items = $DB->get_records_select($this->jobjoin, "id {$insql}", $inparams);
            if (!empty($items)) {
                $list = html_writer::start_tag('div', array('class' => 'list-' . $this->name ));
                foreach ($items as $item) {
                    $list .= display_selected_item($this->shortname, $item, $this->name);
                }
                $list .= html_writer::end_tag('div');

                // link to add items
                $list .= display_choose_hierarchy_items_link($this->name, $this->shortname);

                $mform->setDefault($this->name.'_list', $list);
            }
        }

        $values = array();
        $values[$this->name . '_op'] = $operator;
        $values[$this->name . '_child'] = $children;
        $grpel =& $mform->getElement($this->name . '_grp');
        $grpel->setValue($values);
    }


    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field = $this->name;
        $operator = $field . '_op';
        $children = $field . '_child';

        if (isset($formdata->$field) && !empty($formdata->$field)) {
            return array(
                'operator' => $formdata->$operator,
                'value' => $formdata->$field,
                'children' => isset($formdata->$children) ? $formdata->$children : 0
            );
        }

        return false;
    }


    /**
     * Returns the condition to be used with SQL where
     *
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {
        global $DB;

        $items    = explode(',', $data['value']);
        $operator = $data['operator'];
        $children = $data['children'];
        $userfield    = $this->get_field();
        $jobfield    = $this->jobfield;
        $unique   = rb_unique_param('uja'.$this->shortname);

        // don't filter if none selected
        if (empty($items) || $operator == 0) {
            // return 1=1 instead of TRUE for MSSQL support
            return array(' (1 = 1) ', array());
        }

        // Include child hierarchies if the
        if ($children && in_array($this->shortname, array('pos', 'org'))) {
            $childitems = array();
            $childsql = "SELECT id FROM {{$this->shortname}} WHERE (1=0)";
            foreach ($items as $item) {
                $childsql .= ' OR path LIKE \'%/' . $item . '/%\'';
            }
            $items = array_merge($items, $DB->get_fieldset_sql($childsql, array()));
        }
        $items = array_unique($items); // Make sure the items are unique, otherwise the counting magic later would fail.

        $not = ($operator == self::JOB_OPERATOR_NOTCONTAINS or $operator == self::JOB_OPERATOR_NOTEQUALS) ? 'NOT ' : '';
        list($insql, $params) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED, $unique);
        $jobtable = $unique . 'tab';

        // Build the sql statement from the filter options.
        if (!empty($this->options['extjoin']) && !empty($this->options['extfield'])) {

            // Allow for one layer of abstraction for managerjaid etc.
            $exttable = $unique . 'ext';
            $extjoin = $this->options['extjoin'];
            $extfield = $this->options['extfield'];
            $fromsql = " FROM {job_assignment} {$jobtable} " .
                " INNER JOIN {{$extjoin}} {$exttable} " .
                " ON {$jobtable}.{$jobfield} = {$exttable}.id " .
                " WHERE {$jobtable}.userid = {$userfield} " .
                " AND {$exttable}.{$extfield} {$insql}";
            $field = "{$exttable}.{$extfield}";

        } else {
            $fromsql = " FROM {job_assignment} {$jobtable} " .
                " WHERE {$jobtable}.userid = {$userfield} " .
                " AND {$jobtable}.{$jobfield} {$insql}";
            $field = "{$jobtable}.{$jobfield}";
        }

        $count = count($items);
        if ($count === 1 || $operator == self::JOB_OPERATOR_CONTAINS || $operator == self::JOB_OPERATOR_NOTCONTAINS) {
            $query = " {$not}EXISTS (SELECT 1 " . $fromsql . ")";

        } else if ($operator == self::JOB_OPERATOR_EQUALS || $operator == self::JOB_OPERATOR_NOTEQUALS) {
            $query = " {$not}EXISTS (
                SELECT COUNT(DISTINCT $field)
              $fromsql
                HAVING COUNT(DISTINCT $field) = $count
            )";
        }

        return array($query, $params);
    }

    /**
     * Include Js for this filter
     *
     */
    public function include_js() {
        global $PAGE;

        $code = array();
        $code[] = TOTARA_JS_DIALOG;
        $code[] = TOTARA_JS_TREEVIEW;
        local_js($code);

        $jsdetails = new stdClass();
        $jsdetails->strings = array(
            'totara_job' => array('choosemanager'),
            'totara_hierarchy' => array('chooseposition', 'selected', 'chooseorganisation', 'currentlyselected', 'selectcompetency'),
            'totara_reportbuilder' => array('chooseorgplural', 'chooseposplural', 'choosecompplural')
        );
        $jsdetails->args = array('filter_to_load' => 'jobassign_multi', null, null, $this->name, 'reportid' => $this->report->_id);

        foreach ($jsdetails->strings as $scomponent => $sstrings) {
            $PAGE->requires->strings_for_js($sstrings, $scomponent);
        }

        $PAGE->requires->js_call_amd('totara_reportbuilder/filter_dialogs', 'init', $jsdetails->args);
    }
}

function display_choose_items_link($name, $type) {
    switch ($type) {
        case 'pos':
        case 'org':
            return display_choose_hierarchy_items_link($name, $type);
        case 'man':
        case 'app':
        case 'user':
            return display_choose_user_items_link($name, $type);
        default:
            return '';
    }
}

function display_selected_item($type, $item, $filtername) {
    switch ($type) {
        case 'pos':
        case 'org':
            return display_selected_hierarchy_item($item, $filtername);
        case 'man':
        case 'app':
        case 'user':
            return display_selected_user_item($item, $filtername);
        default:
            return '';
    }
}

function display_choose_user_items_link($filtername, $type) {
    return html_writer::tag('div', html_writer::link('#', get_string("choose{$type}plural", 'totara_reportbuilder'),
        array('id' => "show-{$filtername}-dialog")),
        array('class' => "rb-{$type}-add-link"));
}

function display_selected_user_item($item, $filtername) {
    global $OUTPUT;

    $deletestr = get_string('delete');

    $out = html_writer::start_tag('div', array('data-filtername' =>  $filtername,
        'data-id' => $item->id, 'class' => 'multiselect-selected-item'));
    $out .= fullname($item);
    $deleteicon = $OUTPUT->flex_icon('times-danger');
    $out .= html_writer::link('#', $deleteicon);
    $out .= html_writer::end_tag('div');
    return $out;
}
