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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage totara_question
 */

class question_datepicker extends question_base{
    public static function get_info() {
        return array('group' => question_manager::GROUP_QUESTION,
                     'type' => get_string('questiontypedate', 'totara_question'));
    }

    /**
     * Add database fields definition that represent current customfield
     *
     * @see question_base::get_xmldb()
     * @return array()
     */
    public function get_xmldb() {
        $fields = array();
        $fields[$this->get_prefix_form()] = new xmldb_field($this->get_prefix_db(), XMLDB_TYPE_INTEGER, 10);
        if (!empty($this->param1['withtimezone'])) {
            $fields[$this->get_prefix_form() . 'tz'] = new xmldb_field($this->get_prefix_db() . 'tz', XMLDB_TYPE_TEXT);
        }
        return $fields;
    }

    /**
     * Customfield specific settings elements
     *
     * @param MoodleQuickForm $form
     */
    protected function add_field_specific_settings_elements(MoodleQuickForm $form, $readonly, $moduleinfo) {
        $form->addElement('header', 'dateheader', get_string('dateselection', 'totara_question'));
        $form->setExpanded('dateheader');

        if ($readonly) {
            $form->addElement('static', 'startyear', get_string('datefirstyear', 'totara_question'));
            $form->addElement('static', 'stopyear', get_string('datelastyear', 'totara_question'));
        } else {
            $form->addElement('text', 'startyear', get_string('datefirstyear', 'totara_question'));
            $form->setDefault('startyear', '1970');
            $form->addElement('text', 'stopyear', get_string('datelastyear', 'totara_question'));
            $form->setDefault('stopyear', '2037'); // Year 2038 problem for 32 bit int's.
            $strrequired = get_string('fieldrequired', 'totara_question');
            $form->addRule('startyear', $strrequired, 'required', null, 'client');
            $form->addRule('stopyear', $strrequired, 'required', null, 'client');
        }
        $form->setType('startyear', PARAM_INT);
        $form->setType('stopyear', PARAM_INT);

        $form->addElement('advcheckbox', 'withtime', '', get_string('dateincludetime', 'totara_question'));

        $form->addElement('advcheckbox', 'withtimezone', '', get_string('dateincludetimezone', 'totara_question'));
        $form->disabledIf('withtimezone', 'withtime');
    }

    protected function define_validate($data, $files) {
        $err = array();
        if ($data->startyear >= $data->stopyear) {
            $err['startyear'] = get_string('dateinvalid', 'totara_question');
        }
        return $err;
    }

    public function define_get(stdClass $toform) {
        $toform->startyear = $this->param1['startyear'];
        $toform->stopyear = $this->param1['stopyear'];
        $toform->withtime = $this->param1['withtime'];
        $toform->withtimezone = !empty($this->param1['withtimezone']);
        return $toform;
    }

    public function define_set(stdClass $fromform) {
        $param1 = array();
        $param1['startyear'] = $fromform->startyear;
        $param1['stopyear'] = $fromform->stopyear;
        $param1['withtime'] = $fromform->withtime;
        $param1['withtimezone'] = $fromform->withtime && !empty($fromform->withtimezone);
        $this->param1 = $param1;
        return $fromform;
    }

    /**
     * Determines if the date includes the time.
     *
     * @return bool
     */
    public function with_time() {
        if (is_array($this->param1) && isset($this->param1['withtime'])) {
            return (bool)$this->param1['withtime'];
        }
    }

    public function edit_set(stdClass $data, $source) {
        if ($source == 'form') {
            $datetimeformfield = $this->get_prefix_form();
            $this->values = ['datetime' => $data->$datetimeformfield];

            if (!empty($this->param1['withtimezone'])) {
                $timezoneformfield = $this->get_prefix_form() . '_timezone';
                if (isset($data->$timezoneformfield)) {
                    $this->values['timezone'] = $data->$timezoneformfield;
                } else {
                    // The timezone property is not set if the date form element was disabled.
                    $this->values['timezone'] = '';
                }
            }
        } else { // From DB.
            $datetimeformfield = $this->get_prefix_db();
            $this->values = ['datetime' => $data->$datetimeformfield];

            if (!empty($this->param1['withtimezone'])) {
                $timezoneformfield = $this->get_prefix_db() . 'tz';
                $this->values['timezone'] = $data->$timezoneformfield;
            }
        }
    }

    public function edit_get($dest) {
        $data = new stdClass();

        if ($dest == 'form') {
            $datetimeformfield = $this->get_prefix_form();
            if ($this->values['datetime'] == 0) {
                $data->$datetimeformfield = null;
            } else {
                $data->$datetimeformfield = $this->values['datetime'];

                if (!empty($this->param1['withtimezone'])) {
                    $timezoneformfield = $this->get_prefix_form() . '_timezone';
                    $data->$timezoneformfield = $this->values['timezone'];
                }
            }
        } else { // For DB.
            $datetimeformfield = $this->get_prefix_db();
            $data->$datetimeformfield = $this->values['datetime'];

            if (!empty($this->param1['withtimezone'])) {
                $timezoneformfield = $this->get_prefix_db() . 'tz';
                $data->$timezoneformfield = $this->values['timezone'];
            }
        }

        return $data;
    }

    /**
     * Add form elements that represent current field
     *
     * @see question_base::add_field_specific_edit_elements()
     * @param MoodleQuickForm $form Form to alter
     */
    public function add_field_specific_edit_elements(MoodleQuickForm $form) {
        $attributes = array(
            'startyear' => $this->param1['startyear'],
            'stopyear'  => $this->param1['stopyear'],
            'timezone'  => $this->values['timezone'] ?? 99,
            'optional'  => !$this->required,
            'showtimezone' => $this->param1['withtime'] && !empty($this->param1['withtimezone']),
        );

        // Check if they wanted to include time as well.
        if ($this->param1['withtime']) {
            $form->addElement('date_time_selector', $this->get_prefix_form(), $this->label, $attributes);
        } else {
            $form->addElement('date_selector', $this->get_prefix_form(), $this->label, $attributes);
        }
        if ($this->required) {
            $form->addRule($this->get_prefix_form(), get_string('required'), 'required');
        }
    }

    public function to_html($value) {
        if ($this->param1['withtime']) {
            $format = get_string('strfdateattime', 'langconfig');

            if (!empty($this->param1['withtimezone'])) {
                $datetime = $this->storage->get_element()->values['datetime'];
                $timezone = $this->storage->get_element()->values['timezone'];
                return userdate($datetime, $format, $timezone) . ' ' . $timezone;
            }
        } else {
            $format = get_string('strfdateshortmonth', 'langconfig');
        }
        return userdate($value, $format);
    }

    /**
     * If this element has any answerable form fields, or it's a view only (informational or static) element.
     *
     * @see question_base::is_answerable()
     * @return bool
     */
    public function is_answerable() {
        return true;
    }
}
