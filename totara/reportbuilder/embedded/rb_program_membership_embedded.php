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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

class rb_program_membership_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/totara/program/index.php?viewtype=program';
        $this->source = 'program_membership';
        $this->shortname = 'program_membership';
        $this->fullname = get_string('programmembership', 'totara_program');
        $this->columns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('name', 'rb_source_user'),
            ),
            array(
                'type' => 'progmembership',
                'value' => 'status',
                'heading' => get_string('status', 'rb_source_program_membership'),
            ),
            array(
                'type' => 'progmembership',
                'value' => 'editcompletion',
                'heading' => get_string('editcompletion', 'rb_source_program_membership'),
            ),
        );

        $this->filters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
        );

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->embeddedparams = array();
        if (isset($data['programid'])) {
            $this->embeddedparams['programid'] = $data['programid'];
        }

        parent::__construct();
    }

    /**
     * Is this embedded report usable?
     *
     * If true returned the report is not displayed in the list of all embedded reports.
     * If source is ignored then this method is irrelevant.
     *
     * @return bool
     */
    public static function is_report_ignored() {
        global $CFG;
        return empty($CFG->enableprogramcompletioneditor);
    }

    /**
     * Check if the user is capable of accessing this report.
     * We use $reportfor instead of $USER->id and $report->get_param_value() instead of getting report params
     * some other way so that the embedded report will be compatible with the scheduler (in the future).
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        global $CFG;

        if (empty($this->embeddedparams['programid'])) {
            $context = context_system::instance();
        } else {
            $context = context_program::instance($this->embeddedparams['programid']);
        }

        return !empty($CFG->enableprogramcompletioneditor) && has_capability('totara/program:editcompletion', $context, $reportfor);
    }
}
