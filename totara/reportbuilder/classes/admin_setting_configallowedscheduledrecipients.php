<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin setting for scheduled reports recipients in scheduled reportbuilder.
 */
class totara_reportbuilder_admin_setting_configallowedscheduledrecipients extends admin_setting_configmulticheckbox {
    /**
     * Constructs the new scheduled reports recipients setting.
     */
    public function __construct() {
        $options = [
            'audiences'          => new lang_string('audiences', 'totara_reportbuilder'),
            'systemusers'        => new lang_string('systemusers', 'totara_reportbuilder'),
            'emailexternalusers' => new lang_string('emailexternalusers', 'totara_reportbuilder'),
            'sendtoself'         => new lang_string('sendtoself', 'totara_reportbuilder')
        ];

        parent::__construct(
            'totara_reportbuilder/allowedscheduledrecipients',
            new lang_string('scheduledreportsrecipients', 'totara_reportbuilder'),
            null,
            [
                'audiences'          => 1,
                'systemusers'        => 1,
                'emailexternalusers' => 1,
                'sendtoself'         => 1
            ],
            $options
        );
    }

    /**
     * Saves the setting(s) provided in $data
     *
     * @param array $data An array of data, if not array returns empty str
     * @return mixed empty string on useless data or bool true=success, false=failed
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }
        if (!$this->load_choices() || empty($this->choices)) {
            return '';
        }
        // $data['xxxxx'] is default value of admin_setting_configmulticheckbox class if nothing is selected
        // remove it as it is not required anymore.
        unset($data['xxxxx']);
        if (empty($data)) {
            return get_string('error:allowedscheduledrecipients', 'totara_reportbuilder');
        }
        $result = [];
        foreach ($data as $key => $value) {
            if ($value and array_key_exists($key, $this->choices)) {
                $result[] = $key;
            }
        }
        return $this->config_write($this->name, implode(',', $result)) ? '' : get_string('errorsetting', 'admin');
    }
}