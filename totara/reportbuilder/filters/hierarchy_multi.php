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

/**
 * Generic filter based on selecting multiple items from a hierarchy.
 */
class rb_filter_hierarchy_multi extends rb_filter_type {

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
     * @return rb_filter_hierarchy_multi object
     */
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        // Refers to the name of the main table e.g. 'pos', 'org' or 'comp'
        if (!isset($this->options['hierarchytype'])) {
            // hierarchy type required for this filter
            throw new ReportBuilderException(get_string('hierarchyfiltermusthavetype',
                'totara_reportbuilder',
                (object)array('type' => $type, 'value' => $value, 'source' => get_class($report->src))));
        }
    }

    /**
     * Returns an array of comparison operators
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
        $type = $this->options['hierarchytype'];

        // Container for currently selected items.
        $content = html_writer::tag('div', '', array('class' => 'list-' . $this->name));
        $content .= display_choose_hierarchy_items_link($this->name, $type);
        $mform->addElement('static', $this->name.'_list', $label, $content);

        if ($advanced) {
            $mform->setAdvanced($this->name.'_list');
        }

        $mform->addElement('hidden', $this->name);
        $mform->setType($this->name, PARAM_SEQUENCE);

        // set default values
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        } else if (!empty($defaultvalue)) {
            $this->set_data($defaultvalue);
        }

        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, $defaults['value']);
        }

    }

    function definition_after_data(&$mform) {
        global $DB;
        $type = $this->options['hierarchytype'];

        if ($ids = $mform->getElementValue($this->name)) {
            list($isql, $iparams) = $DB->get_in_or_equal(explode(',', $ids));
            $items = $DB->get_records_select($type, "id {$isql}", $iparams);
            if (!empty($items)) {
                $out = html_writer::start_tag('div', array('class' => 'list-' . $this->name));
                foreach ($items as $item) {
                    $out .= display_selected_hierarchy_item($item, $this->name);
                }
                $out .= html_writer::end_tag('div');

                // link to add items
                $out .= display_choose_hierarchy_items_link($this->name, $type);

                $mform->setDefault($this->name.'_list', $out);
            }
        }

    }


    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    function check_data($formdata) {
        $field    = $this->name;

        if (isset($formdata->$field) && !empty($formdata->$field) ) {
            return array('value'    => $formdata->$field);
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
        $query    = $this->get_field();

        // don't filter if none selected
        if (empty($items)) {
            // return 1=1 instead of TRUE for MSSQL support
            return array(' 1=1 ', array());
        }
        list($insql, $inparams) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED,
            rb_unique_param('fhm').'_');

        return array("{$query} {$insql}", $inparams);
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
        $type = $this->options['hierarchytype'];

        if (empty($value)) {
            return '';
        }

        $a = new stdClass();
        $a->label    = $label;

        $selected = array();
        list($isql, $iparams) = $DB->get_in_or_equal($value);
        $items = $DB->get_records_select($type, "id {$isql}", $iparams);
        foreach ($items as $item) {
            $selected[] = '"' . format_string($item->fullname) . '"';
        }

        $orstring = get_string('or', 'totara_reportbuilder');
        $a->value    = implode($orstring, $selected);

        return get_string('selectlabelnoop', 'filters', $a);
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
            'totara_hierarchy' => array('chooseposition', 'selected', 'chooseorganisation', 'currentlyselected', 'selectcompetency'),
            'totara_reportbuilder' => array('chooseorgplural', 'chooseposplural', 'choosecompplural')
        );
        $jsdetails->args = array('filter_to_load' => 'hierarchy_multi', null, null, $this->name, 'reportid' => $this->report->_id);

        foreach ($jsdetails->strings as $scomponent => $sstrings) {
            $PAGE->requires->strings_for_js($sstrings, $scomponent);
        }

        $PAGE->requires->js_call_amd('totara_reportbuilder/filter_dialogs', 'init', $jsdetails->args);
    }
}


/**
 * Given a hierarchy item object returns the HTML to display it as a filter selection
 *
 * @param object $item A hierarchy object containing id and name properties
 * @param string $filtername The identifying name of the current filter
 *
 * @return string HTML to display a selected item
 */
function display_selected_hierarchy_item($item, $filtername) {
    global $OUTPUT;
    $out = html_writer::start_tag('div', array('data-filtername' =>  $filtername,
        'data-id' => $item->id, 'class' => 'multiselect-selected-item'));
    $out .= format_string($item->fullname);
    $deleteicon = $OUTPUT->flex_icon('delete');
    $out .= html_writer::link('#', $deleteicon);
    $out .= html_writer::end_tag('div');
    return $out;
}

/**
 * Helper function to display the 'add item' link to the filter
 *
 * @param string $filtername Name of the form element
 *
 * @return string HTML to display the link
 */
function display_choose_hierarchy_items_link($filtername, $type) {
    return html_writer::tag('div', html_writer::link('#', get_string("choose{$type}plural", 'totara_reportbuilder'),
        array('id' => "show-{$filtername}-dialog")),
        array('class' => "rb-{$type}-add-link"));
}
