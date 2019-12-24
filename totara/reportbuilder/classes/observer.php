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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class totara_reportbuilder_observer {

    /**
     * Event that is triggered when a user is deleted.
     *
     * Removes an user from any scheduled reports they are associated with, tables to clear are
     * report_builder_schedule_email_audience
     * report_builder_schedule_email_systemuser
     * report_builder_schedule_email_external
     * report_builder_schedule
     *
     * @param \core\event\user_deleted $event
     *
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;

        $transaction = $DB->start_delegated_transaction();

        // If user is an owner of scheduled reports, delete all scheduled reports.
        $reports = $DB->get_records('report_builder_schedule', array('userid' => $userid), 'id', 'id, reportid');
        foreach ($reports as $report) {
            $DB->delete_records('report_builder_schedule_email_audience',   array('scheduleid' => $report->id));
            $DB->delete_records('report_builder_schedule_email_systemuser', array('scheduleid' => $report->id));
            $DB->delete_records('report_builder_schedule_email_external',   array('scheduleid' => $report->id));
            $DB->delete_records('report_builder_schedule', array('id' => $report->id));
        }
        // Remove the system user from scheduled reports.
        $DB->delete_records('report_builder_schedule_email_systemuser', array('userid' => $userid));

        $transaction->allow_commit();
    }
}
