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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_core
 */

class email_setting_schedule {

    /**
     * DB row schedule object
     *
     * @var stdClass
     */
    private $schedule = null;

    /**
     * Constructor
     *
     * @param int $scheduleid The schedule ID
     */
    public function __construct($scheduleid) {
        global $DB, $USER;

        // Check schedule report exist.
        if (!$schedule = $DB->get_record('report_builder_schedule', array('id' => $scheduleid))) {
            return null;
        }

        // Check the user can execute this function.
        if ($schedule->userid != $USER->id) {
            require_capability('totara/reportbuilder:managescheduledreports', context_system::instance());
        }

        $this->schedule = $schedule;
    }

    /**
     * Get a list of audience to email the schedule report passed as param.
     *
     * @param int $scheduleid The schedule ID
     * @return array
     */
    public static function get_audiences_to_email($scheduleid) {
        global $DB;

        $sql = 'SELECT sea.cohortid AS id, c.name AS fullname
                  FROM {report_builder_schedule_email_audience} sea
             LEFT JOIN {cohort} c
                    ON sea.cohortid = c.id
                 WHERE sea.scheduleid = :scheduleid';
        return $DB->get_records_sql($sql, array('scheduleid' => $scheduleid));
    }

    /**
     * Get a list of system users to email the schedule report passed as param.
     *
     * @param int $scheduleid The schedule ID
     * @return array
     */
    public static function get_system_users_to_email($scheduleid) {
        global $DB;

        $usernamefields = get_all_user_name_fields(true, 'u');

        $sql = 'SELECT ses.userid AS id, ' . $usernamefields . '
                  FROM {report_builder_schedule_email_systemuser} ses
             LEFT JOIN {user} u
                    ON ses.userid = u.id
                 WHERE ses.scheduleid = :scheduleid';
        $results = $DB->get_records_sql($sql, array('scheduleid' => $scheduleid));

        foreach ($results as $result) {
            $result->fullname = fullname($result);
        }

        return $results;
    }

    /**
     * Get a list of external users to email the schedule report passed as param.
     *
     * @param int $scheduleid The schedule ID
     * @return array
     */
    public static function get_external_users_to_email($scheduleid) {
        global $DB;

        $select = 'scheduleid = :scheduleid';
        $params = array('scheduleid' => $scheduleid);
        return $DB->get_fieldset_select('report_builder_schedule_email_external', 'email', $select, $params);
    }

    /**
     * Get an array of user objects corresponding to the audience email setting for the schedule report given.
     *
     * @param int $scheduleid The schedule report id
     * @return array Array of user objects
     */
    protected function get_emails_audiences() {
        global $DB;

        $sql = "SELECT DISTINCT u.*
              FROM {report_builder_schedule_email_audience} sea
        INNER JOIN {cohort_members} cm
                ON sea.cohortid = cm.cohortid
        INNER JOIN {user} u
                ON cm.userid = u.id
             WHERE sea.scheduleid = :scheduleid";

        return $DB->get_records_sql($sql, array('scheduleid' => $this->schedule->id));

    }

    /**
     * Get an array of user objects corresponding to the system user email setting for the schedule report given.
     *
     * @return array Array of user objects
     */
    private function get_emails_system_users() {
        global $DB;

        $sql = "SELECT u.*
              FROM {report_builder_schedule_email_systemuser} ses
        INNER JOIN {user} u
                ON ses.userid = u.id
             WHERE ses.scheduleid = :scheduleid";

        return $DB->get_records_sql($sql, array('scheduleid' => $this->schedule->id));
    }

    /**
     * Get all system users to email.
     *
     * @return array
     */
    public function get_all_system_users_to_email() {
        $usersaudiences = $this->get_emails_audiences();
        $systemusers = $this->get_emails_system_users();

        return array_merge($systemusers, $usersaudiences);
    }

    /**
     * Delete email records unset in the schedule report settings.
     *
     * @param string $table Table where the records need to be deleted
     * @param string $field Condition to delete records
     * @param array $emails Array containing the fields that need to be deleted
     * @return bool Result of the operation
     */
    private function delete_email_setting($table, $field, $emails) {
        global $DB;

        if (!empty($emails)) {
            $param = array('scheduleid' => $this->schedule->id);
            list($sqlin, $sqlparm) = $DB->get_in_or_equal($emails, SQL_PARAMS_NAMED);
            return $DB->execute("
                DELETE
                  FROM {{$table}}
                 WHERE scheduleid =:scheduleid
                   AND {$field} {$sqlin}",
                array_merge($param, $sqlparm)
            );
        }
    }

    /**
     * Add email records set in the schedule report settings.
     * Schedule_email tables has the same structure, They just differ in a field name, so we can take advantage
     * of that structure and save records for any of those tables.
     *
     * @param string $table Table where the records need to be created
     * @param string $field Field Corresponding to the data in the $emails array
     * @param array $emails Array containing the fields that need to be created
     * @return bool Result of the operation
     */
    private function add_email_setting($table, $field, $emails) {
        global $DB;

        if (!empty($emails)) {
            $todb = array();
            $scheduleid = $this->schedule->id;
            foreach ($emails as $emailsetting) {
                $record = new stdClass();
                $record->scheduleid = $scheduleid;
                $record->{$field} = $emailsetting;
                $todb[] = $record;
            }

            return $DB->insert_records_via_batch($table, $todb);
        }
    }

    /**
     * Save Email setting to be sent when the schedule report.
     *
     * @param array $audiences Array of audiences to save
     * @param array $systemusers Array of system users to save
     * @param array $externalusers Array of external users to save
     */
    public function set_email_settings(array $audiences, array $systemusers, array $externalusers) {
        global $DB;

        // Get current values settings to determine if we need to delete or create some records.
        $select = 'scheduleid = :scheduleid';
        $param = array('scheduleid' => $this->schedule->id);
        $currentaudiences = $DB->get_fieldset_select('report_builder_schedule_email_audience', 'cohortid', $select, $param);
        $currentsystemusers = $DB->get_fieldset_select('report_builder_schedule_email_systemuser', 'userid', $select, $param);
        $currentexternalusers = $DB->get_fieldset_select('report_builder_schedule_email_external', 'email', $select, $param);

        // Define audiences to be added or removed.
        $audiencestoinsert = array_diff($audiences, $currentaudiences);
        $audiencestodelete = array_diff($currentaudiences, $audiences);

        // Define system users to be added or removed.
        $sysuserstoinsert = array_diff($systemusers, $currentsystemusers);
        $sysuserstodelete = array_diff($currentsystemusers, $systemusers);

        // Define external users to be added or removed.
        $extuserstoinsert = array_diff($externalusers, $currentexternalusers);
        $extuserstodelete = array_diff($currentexternalusers, $externalusers);

        // Deleting records.
        $this->delete_email_setting('report_builder_schedule_email_audience', 'cohortid', $audiencestodelete);
        $this->delete_email_setting('report_builder_schedule_email_systemuser', 'userid', $sysuserstodelete);
        $this->delete_email_setting('report_builder_schedule_email_external', 'email', $extuserstodelete);

        // Inserting records.
        $this->add_email_setting('report_builder_schedule_email_audience', 'cohortid', $audiencestoinsert);
        $this->add_email_setting('report_builder_schedule_email_systemuser', 'userid', $sysuserstoinsert);
        $this->add_email_setting('report_builder_schedule_email_external', 'email', $extuserstoinsert);
    }
}
