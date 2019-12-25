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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort;

defined('MOODLE_INTERNAL') || die();

/**
 * Cohort learning plan class that deals with the learning plan configuration when dynamic creation is enabled.
 */
class learning_plan_config {

    /**
     * The table that stores the plan config data.
     */
    const TABLE = 'cohort_plan_config';

    /**
     * The id of this config, if it has been created yet.
     * @var int
     */
    public $id;

    /**
     * The cohort id.
     * @var int
     */
    public $cohortid;

    /**
     * Learning plan template id.
     * @var int
     */
    public $plantemplateid;

    /**
     * Learning plan status.
     * @var int
     */
    public $planstatus;

    /**
     * Exclude auto plan creation for users who have an existing, manually created plan based on the template id.
     * @var bool
     */
    public $excludecreatedmanual;

    /**
     * Exclude auto plan creation for users who have an existing, automatically created plan based on the template id.
     * @var bool
     */
    public $excludecreatedauto;

    /**
     * Exclude auto plan creation for users who have a completed plan based on the template id.
     * @var bool
     */
    public $excludecompleted;

    /**
     * Automatically create new plans for new members of the cohort.
     * @var bool
     */
    public $autocreatenew;

    /**
     * Returns a learning plan cohort configuration object - given a cohort id.
     *
     * @param int $cohortid
     * @return learning_plan_config The configuration object populated with any saved data.
     */
    public static function get_config($cohortid) {
        global $DB;

        $data = $DB->get_record(self::TABLE, array('cohortid' => $cohortid));

        if (!$data) {
            return new learning_plan_config(null, $cohortid);
        }

        $config = new learning_plan_config(
            $data->id,
            $data->cohortid,
            $data->plantemplateid,
            $data->planstatus,
            $data->excludecreatedmanual,
            $data->excludecreatedauto,
            $data->excludecompleted,
            $data->autocreatenew
        );

        return $config;
    }

    /**
     * learning_plan_config constructor.
     *
     * @param int $id
     * @param int $cohortid
     * @param int $plantemplateid
     * @param int $planstatus
     * @param bool $excludecreatedmanual
     * @param bool $excludecreatedauto
     * @param bool $excludecompleted
     * @param bool $autocreatenew
     */
    protected function __construct($id = null, $cohortid = null, $plantemplateid = null, $planstatus = null,
                                   $excludecreatedmanual = true, $excludecreatedauto = true, $excludecompleted = true,
                                   $autocreatenew = false) {

        $this->id = $id;
        $this->cohortid = $cohortid;
        $this->plantemplateid = $plantemplateid;
        $this->planstatus = $planstatus;
        $this->excludecreatedmanual = (bool)$excludecreatedmanual;
        $this->excludecreatedauto = (bool)$excludecreatedauto;
        $this->excludecompleted = (bool)$excludecompleted;
        $this->autocreatenew = (bool)$autocreatenew;

        $this->validate();

    }

    /**
     * Validates that the state of this config option.
     */
    protected function validate() {
        // Simple validation.
        // We need to ensure that autocreatenew is not set when excludecreatedauto is not set.
        if (empty($this->excludecreatedauto)) {
            $this->autocreatenew = false;
        }
    }

    /**
     * Saves this config object to the database.
     */
    public function save() {
        global $DB;

        $this->validate();

        $record = $this->get_record();

        if (empty($this->id)) {
            // Create a new record.
            $this->id = $DB->insert_record(self::TABLE, $record);
        } else {
            $record->id = $this->id;
            // Update the record.
            $DB->update_record(self::TABLE, $record);
        }
    }

    /**
     * Returns the config object as a plain record.
     *
     * @return \stdClass
     */
    public function get_record() {

        // First make sure it is valid.
        $this->validate();

        $record = new \stdClass;
        if (!empty($this->id)) {
            $record->id = $this->id;
        }
        $record->cohortid = $this->cohortid;
        $record->plantemplateid = $this->plantemplateid;
        $record->planstatus = $this->planstatus;
        $record->excludecreatedmanual = $this->excludecreatedmanual;
        $record->excludecreatedauto = $this->excludecreatedauto;
        $record->excludecompleted = $this->excludecompleted;
        $record->autocreatenew = $this->autocreatenew;

        return $record;
    }

    /**
     * Returns true if this config requires plans to be dynamically created for new audience members.
     * @return bool
     */
    public function auto_create_new() {
        if (!empty($this->id) && $this->autocreatenew && $this->excludecreatedauto) {
            return true;
        }
        return false;
    }

    /**
     * Convert a stdclass object from the plan generator adhoc task to a proper object.
     *
     * @param stdclass $config The config object from the adhoc task.
     * @return learning_plan_config $config
     */
    public static function convert(\stdClass $config) {

        $config = new learning_plan_config(
            $config->id,
            $config->cohortid,
            $config->plantemplateid,
            $config->planstatus,
            $config->excludecreatedmanual,
            $config->excludecreatedauto,
            $config->excludecompleted,
            $config->autocreatenew
        );

        return $config;
    }

}