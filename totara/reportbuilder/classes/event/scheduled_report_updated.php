<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The scheduled report created event class.
 */
class scheduled_report_updated extends \core\event\base {
    /**
     * @var bool flag for prevention of direct create() call.
     */
    protected static $preventcreatecall = true;

    /** @var \stdClass */
    protected $scheduledreport;

    /**
     * Create instance of event.
     *
     * @param \stdClass $scheduledreport scheduled report details.
     * @return scheduled_report_updated
     */
    public static function create_from_schedule(\stdClass $scheduledreport) {
        $other = [
            'reportid' => $scheduledreport->reportid,
            'modifier' => $scheduledreport->usermodified
        ];

        $data = [
            'context' => \context_system::instance(),
            'objectid' => $scheduledreport->id,
            'other' => $other
        ];

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;

        $event->scheduledreport = $scheduledreport;
        return $event;
    }

    /**
     * Get scheduler instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \stdClass
     */
    public function get_scheduled_report() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_scheduled_report() is intended for event observers only');
        }
        return $this->scheduledreport;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'report_builder_schedule';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventscheduledreportupdated', 'totara_reportbuilder');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $report = $this->other['reportid'];
        $modifier = $this->other['modifier'];

        $msg = sprintf(
            "The user with id '%s' updated a scheduled report (scheduleid='%d', reportid='%s')",
            $modifier, $this->objectid, $report
        );
        return $msg;
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        global $CFG;
        $logurl = $this->get_url()->out(false);
        $logurl = str_replace($CFG->wwwroot . '/totara/reportbuilder/', '', $logurl);
        return [SITEID, 'reportbuilder', 'new scheduled report', $logurl, 'schedule ID=' . $this->objectid];
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/reportbuilder/scheduled.php', ['id' => $this->objectid]);
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call scheduled_report_updated::create() directly, use scheduled_report_updated::create_from_schedule() instead.');
        }

        parent::validate_data();
    }
}
