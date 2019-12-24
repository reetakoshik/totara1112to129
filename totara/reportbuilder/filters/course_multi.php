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
 * @author Brendan Cox <brendan.cox@.totaralms.com>
 * @package totara_reportbuilder
 */

/**
 * Class that allows for filtering of by multiple courses at once.
 */
class rb_filter_course_multi extends rb_filter_type {

    // Constants relating to comparison operators for this filter.
    const COURSE_MULTI_ANYVALUE   = 0;
    const COURSE_MULTI_EQUALTO    = 1;
    const COURSE_MULTI_NOTEQUALTO = 2;

    /**
     * Returns an array of comparison operators.
     */
    public function get_operators() {
        return array(self::COURSE_MULTI_ANYVALUE   => get_string('isanyvalue', 'filters'),
                     self::COURSE_MULTI_EQUALTO    => get_string('isequalto', 'filters'),
                     self::COURSE_MULTI_NOTEQUALTO => get_string('isnotequalto', 'filters'));
    }

    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        global $SESSION, $DB;
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        // We need to check the user has permission to view the courses in the saved
        // search as these may be a search created by someone else who can view
        // a different selection of courses.
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];

            if (isset($defaults['value'])) {
                $courseids = array_filter(explode(',', $defaults['value']));

                // Remove course ids that aren't viewable by this user.
                foreach ($courseids as $key => $courseid) {
                    $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
                    if (!$course || !totara_course_is_viewable($course)) {
                        unset($courseids[$key]);
                    }
                }

                $defaults['value'] = implode(',', $courseids);

                // Even if operator is set, if there are no more course ids after checking what the user
                // can view, we set the operator to 'Is any value'.
                if (!isset($defaults['operator']) || empty($courseids)) {
                    $defaults['operator'] = self::COURSE_MULTI_ANYVALUE;
                }

            } else {
                $defaults['value'] = '';
                $defaults['operator'] = self::COURSE_MULTI_ANYVALUE;
            }

            $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name] = $defaults;
        }

    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    public function setupForm(&$mform) {
        global $SESSION;

        $label = format_string($this->label);
        $advanced = $this->advanced;

        $objs = array();
        $objs[] =& $mform->createElement('select', $this->name.'_op', $label, $this->get_operators());
        $objs[] =& $mform->createElement('static', 'title'.$this->name, '',
            html_writer::tag('span', '', array('id' => $this->name . 'title', 'class' => 'dialog-result-title')));
        $mform->setType($this->name.'_op', PARAM_TEXT);

        // Can't use a button because id must be 'show-*-dialog' and formslib appends 'id_' to ID.
        $objs[] =& $mform->createElement('static', 'selectorbutton',
            '',
            html_writer::empty_tag('input', array('type' => 'button',
                'class' => 'rb-filter-button rb-filter-choose-course',
                'value' => get_string('coursemultiitemchoose', 'totara_reportbuilder'),
                'id' => 'show-' . $this->name . '-dialog')));

        // Container for currently selected items.
        $content = html_writer::tag('div', '', array('class' => 'rb-filter-content-list list-' . $this->name));
        $objs[] =& $mform->createElement('static', $this->name.'_list', '', $content);

        // Create a group for the elements.
        $grp =& $mform->addElement('group', $this->name.'_grp', $label, $objs, '', false);
        $this->add_help_button($mform, $grp->_name, 'reportbuilderdialogfilter', 'totara_reportbuilder');

        if ($advanced) {
            $mform->setAdvanced($this->name.'_grp');
        }

        $mform->addElement('hidden', $this->name, '');
        $mform->setType($this->name, PARAM_SEQUENCE);

        // Set default values.
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        }
        if (isset($defaults['operator'])) {
            $mform->setDefault($this->name . '_op', $defaults['operator']);
        }
        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, $defaults['value']);
        }
    }

    /**
     * Definition after data.
     * @param object $mform a MoodleForm object to setup
     */
    public function definition_after_data(&$mform) {
        global $DB;

        if ($ids = $mform->getElementValue($this->name)) {
            $idsarray = explode(',', $ids);
            list($insql, $inparams) = $DB->get_in_or_equal($idsarray);
            if ($courses = $DB->get_records_select('course', "id ".$insql, $inparams)) {
                $out = html_writer::start_tag('div', array('class' => 'rb-filter-content-list list-' . $this->name));
                foreach ($courses as $course) {
                    $out .= self::display_selected_course_item($course, $this->name);
                }
                $out .= html_writer::end_tag('div');

                $mform->setDefault($this->name . '_list', $out);
            }
        }
    }

    /**
     * Retrieves data from the form data.
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata) {
        $field = $this->name;
        $operator = $field . '_op';

        if (isset($formdata->$field) && $formdata->$field != '') {
            $data = array('operator' => (int)$formdata->$operator,
                'value'    => (string)$formdata->$field);

            return $data;
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where.
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    public function get_sql_filter($data) {
        global $DB;

        $courseids = explode(',', $data['value']);
        $query = $this->get_field();
        $operator  = $data['operator'];

        switch($operator) {
            case self::COURSE_MULTI_EQUALTO:
                $equal = true;
                break;
            case self::COURSE_MULTI_NOTEQUALTO:
                $equal = false;
                break;
            default:
                // Return 1=1 instead of TRUE for MSSQL support.
                return array(' 1=1 ', array());
        }

        // None selected - match everything.
        if (empty($courseids)) {
            // Using 1=1 instead of TRUE for MSSQL support.
            return array(' 1=1 ', array());
        }

        list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid', $equal);
        $sql = ' ('.$query.') '.$insql;

        return array($sql, $params);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {
        global $DB;

        $operator  = $data['operator'];
        $values = explode(',', $data['value']);

        if (empty($operator) || empty($values)) {
            return '';
        }

        $a = new stdClass();
        $a->label = $this->label;

        $selected = array();
        list($insql, $inparams) = $DB->get_in_or_equal($values);
        if ($courses = $DB->get_records_select('course', "id ".$insql, $inparams, 'id')) {
            foreach ($courses as $course) {
                $selected[] = '"' . format_string($course->fullname) . '"';
            }
        }

        $orandstr = ($operator == self::COURSE_MULTI_EQUALTO) ? 'or': 'and';
        $a->value = implode(get_string($orandstr, 'totara_reportbuilder'), $selected);
        $operators = $this->get_operators();
        $a->operator = $operators[$operator];

        return get_string('selectlabel', 'filters', $a);
    }

    /**
     * Include Js for this filter.
     */
    public function include_js() {
        global $PAGE;

        $code = array();
        $code[] = TOTARA_JS_DIALOG;
        $code[] = TOTARA_JS_TREEVIEW;
        local_js($code);

        $jsdetails = new stdClass();
        $jsdetails->strings = array(
            'totara_reportbuilder' => array('coursemultiitemchoose'),
        );
        $jsdetails->args = array('filter_to_load' => 'course_multi', null, null, $this->name, 'reportid' => $this->report->_id);

        foreach ($jsdetails->strings as $scomponent => $sstrings) {
            $PAGE->requires->strings_for_js($sstrings, $scomponent);
        }

        $PAGE->requires->js_call_amd('totara_reportbuilder/filter_dialogs', 'init', $jsdetails->args);
    }

    /**
     * Given a course item object returns the HTML to display it as a filter selection.
     *
     * @param object $item A category object containing id and name properties.
     * @param string $filtername The identifying name of the current filter.
     *
     * @return string HTML to display a selected item or an empty string if the user cannot view the course.
     */
    public static function display_selected_course_item($course, $filtername) {
        global $OUTPUT;

        $strdelete = get_string('delete');

        $out = html_writer::start_tag('div', array('data-filtername' => $filtername,
            'data-id' => $course->id,
            'class' => 'multiselect-selected-item'));
        $out .= format_string($course->fullname);
        $out .= $OUTPUT->action_icon('#', new pix_icon('/t/delete', $strdelete, 'moodle'), null,
            array('class' => 'action-icon delete'));
        $out .= html_writer::end_tag('div');

        return $out;
    }
}
