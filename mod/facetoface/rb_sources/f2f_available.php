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
 * @author Learning Pool
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');

/**
 * Filter for rooms/assets to find those not used during specified time
 */
abstract class rb_filter_f2f_available extends rb_filter_type {
    /**
     * When the filter of asset availability is set
     * to value "Any time"
     * @var int
     */
    const ANY_TIME=0;

    /**
     * When the filter of asset availability is set to
     * value "Free between the following times"
     * @var int
     */
    const BETWEEN_TIME=1;

    public function setupForm(&$mform) {
        global $CFG, $PAGE, $SESSION;

        // Help to keep interval between dates when user changes start date.
        $jsmodule = array(
         'name' => 'totara_f2f_dateintervalkeeper',
         'fullpath' => '/mod/facetoface/js/dateintervalkeeper.js'
        );
        $PAGE->requires->js_init_call('M.totara_f2f_dateintervalkeeper.init', array(
            $this->name . '_start',
            $this->name . '_end'
        ), false, $jsmodule);

        $label = format_string($this->label);
        $options = array(
            self::ANY_TIME => get_string('anytime', 'facetoface'),
            self::BETWEEN_TIME => get_string('freebetween', 'facetoface')
        );

        $objs = array();
        $objs[] = $mform->createElement('select', $this->name . '_enable', get_string('available', 'mod_facetoface'), $options);
        $objs[] = $mform->createElement('static', null, null, html_writer::empty_tag('br'));

        $showtimezone = false;
        if (!empty($CFG->facetoface_displaysessiontimezones)) {
            $showtimezone = true;
        }

        $objs[] = $mform->createElement(
            'date_time_selector',
            $this->name . '_start',
            null,
            array(
                'step' => 1,
                'optional' => false,
                'showtimezone' => $showtimezone
            )
        );
        $objs[] = $mform->createElement('static', null, null, html_writer::empty_tag('br'));

        $objs[] = $mform->createElement(
            'date_time_selector',
            $this->name . '_end',
            get_string('sessionfinishtime', 'facetoface'),
            array(
                'step' => 1,
                'optional' => false,
                'showtimezone' => $showtimezone
            )
        );

        $mform->addElement('static', $this->name . '_err');
        $grp = $mform->addElement('group', $this->name.'_grp', $label, $objs, '', false);
        $mform->addHelpButton($grp->_name, 'filter_'.$this->value, 'facetoface');

        $mform->disabledIf($this->name . '_start[day]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_start[month]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_start[year]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_start[hour]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_start[minute]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_start[calendar]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_end[day]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_end[month]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_end[year]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_end[hour]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_end[minute]', $this->name . '_enable', 'eq', self::ANY_TIME);
        $mform->disabledIf($this->name . '_end[calendar]', $this->name . '_enable', 'eq', self::ANY_TIME);
        if ($showtimezone) {
            $mform->disabledIf($this->name . '_start[timezone]', $this->name . '_enable', 'eq', self::ANY_TIME);
            $mform->disabledIf($this->name . '_end[timezone]', $this->name . '_enable', 'eq', self::ANY_TIME);
        }

        // Set default values.
        if (isset($SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name])) {
            $defaults = $SESSION->reportbuilder[$this->report->get_uniqueid()][$this->name];
        }

        if (isset($defaults['start'])) {
            $mform->setDefault($this->name.'_start', $defaults['start']);
        }
        if (isset($defaults['end'])) {
            $mform->setDefault($this->name.'_end', $defaults['end']);
        }
        if (isset($defaults['enable'])) {
            $mform->setDefault($this->name.'_enable', $defaults['enable']);
        }
    }

    public function definition_after_data(MoodleQuickForm $mform) {
        // Filters don't support validation. Do workaround.
        // This almost works, but when I set error it ignores whole form (no submit)
        // which is equal to "no filter" and displays all data. Also "Clear"
        // button doesn't work (doesn't reset anything). If this two issues
        // addressed it will be better, but I can't spend more time on that
        // right now.
//        $elems = $mform->getElement($this->name . '_grp')->getElements();
//        $data = array();
//        foreach($elems as $elem) {
//            if (!$elem->getName()) {
//                continue;
//            }
//            $values = array($elem->getName() => $elem->getValue());
//            $exported = $elem->exportValue($values);
//            $data[$elem->getName()] = is_array($exported) ? $exported[$elem->getName()] : $exported;
//        }
//        if ($data[$this->name . '_start'] > $data[$this->name . '_end']) {
//           $mform->setElementError($this->name . '_err', get_string('error:sessionstartafterend', 'facetoface'));
//        }
    }

    public function check_data($formdata) {
        $data = array();

        $data['start'] = $formdata->{$this->name . '_start'};
        $data['end'] = $formdata->{$this->name . '_end'};
        $data['enable'] = $formdata->{$this->name . '_enable'};

        return $data;
    }

    public function get_sql_filter($data) {
        if ($data['start'] > $data['end']) {
            return array(" 1=0 ", array());
        }
        else if (isset($data['enable']) && $data['enable'] == self::ANY_TIME)
        {
            return array(" 1=1 ", array());
        }

        return $this->get_sql_snippet($data['start'], $data['end']);
    }

    /**
     * Actual function to get SQL restriction (WHERE part) of available item.
     */
    abstract function get_sql_snippet($sessionstarts, $sessionends);
}
