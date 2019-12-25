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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Day/month picker admin setting for report builder settings.
 *
 */
class totara_reportbuilder_admin_setting_configdaymonthpicker extends admin_setting {
    /**
     * Constructor
     * @param string $name unique ascii name, either 'mysetting' for settings that in config,
     *                     or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename localised name
     * @param string $description localised long description
     * @param mixed $defaultsetting string or array depending on implementation
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Gets the current settings as an array
     *
     * @return mixed Null if none, else array of settings
     */
    public function get_setting() {
        $result = $this->config_read($this->name);
        if (is_null($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Store the data as ddmm string.
     *
     * @param string $data
     * @return bool true if success, false if not
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }
        $result = $this->config_write($this->name, date("dm", mktime(0, 0, 0, $data['m'], $data['d'], 0)));

        return ($result ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Returns day/month select+select fields.
     *
     * @param string $data
     * @param string $query
     * @return string html select+select fields and wrapping div(s)
     */
    public function output_html($data, $query='') {
        // Default settings.
        $default = $this->get_defaultsetting();

        if (is_array($default)) {
            $defaultday = $default['d'];
            $defaultmonth = $default['m'];
            $defaultinfo = date('j F', mktime(0, 0, 0, $defaultmonth, $defaultday, 0));
        } else {
            $defaultinfo = null;
        }

        // Saved settings - needs to parse the default array as well for upgrades.
        if (is_array($data)) {
            $day = $data['d'];
            $month = $data['m'];
        } else {
            $day = substr($data, 0, 2);
            $month = substr($data, 2, 2);
        }

        $days = array_combine(range(1,31), range(1,31));
        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $mname = date("F", mktime(0, 0, 0, $i, 10));
            $months[$i] = $mname;
        }

        $return = html_writer::start_tag('div', array('class' => 'form-daymonth defaultsnext'));
        $return .= html_writer::tag('label', get_string('financialyeardaystart', 'totara_reportbuilder', $this->visiblename),
            array('for' => 'menu' . $this->get_full_name() . 'd', 'class' => 'accesshide'));
        $return .= html_writer::select($days, $this->get_full_name() . '[d]' , (int)$day);
        $return .= html_writer::tag('label', get_string('financialyearmonthstart', 'totara_reportbuilder', $this->visiblename),
            array('for' => 'menu' . $this->get_full_name() . 'm', 'class' => 'accesshide'));
        $return .= html_writer::select($months, $this->get_full_name() . '[m]', (int)$month);
        $return .= html_writer::end_tag('div');

        return format_admin_setting($this, $this->visiblename, $return, $this->description, false, '', $defaultinfo, $query);
    }
}
