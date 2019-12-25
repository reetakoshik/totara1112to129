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
 * @package totara_cohort
 * @category test
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Cohort generator.
 *
 * @package totara_cohort
 * @category test
 */
class totara_cohort_generator extends component_generator_base {
    /**
     * Enable cohort enrolment plugin.
     */
    public function enable_enrol_plugin() {
        $enabled = enrol_get_plugins(true);
        $enabled['cohort'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }

    /**
     * Disable cohort enrolment plugin.
     */
    public function disable_enrol_plugin() {
        $enabled = enrol_get_plugins(true);
        unset($enabled['cohort']);
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }

    /**
     * Add cohort enrolment method to course.
     *
     * @param array|stdClass $record
     * @return stdClass record from enrol table
     */
    public function create_cohort_enrolment($record) {
        global $DB, $CFG;
        $record = (array)$record;

        if (empty($record['cohortid'])) {
            throw new coding_exception('cohortid is required in totara_cohort_generator::create_cohort_enrolment() $record');
        }
        $cohort = $DB->get_record('cohort', array('id' => $record['cohortid']), '*', MUST_EXIST);
        unset($record['cohortid']);
        $record['customint1'] = $cohort->id;

        if (empty($record['courseid'])) {
            throw new coding_exception('courseid is required in totara_cohort_generator::create_cohort_enrolment() $record');
        }
        $course = $DB->get_record('course', array('id' => $record['courseid']), '*', MUST_EXIST);

        if (!isset($record['roleid']) or $record['roleid'] === '') {
            $record['roleid'] = $CFG->learnerroleid;
        } else if ($record['roleid'] == 0) {
            $record['roleid'] = 0;
        } else {
            $role = $DB->get_record('role', array('id' => $record['roleid']), '*', MUST_EXIST);
            $record['roleid'] = $role->id;
        }

        /** @var enrol_cohort_plugin $cohortplugin */
        $cohortplugin = enrol_get_plugin('cohort');
        $id = $cohortplugin->add_instance($course, $record);

        return $DB->get_record('enrol', array('id' => $id));
    }

    /**
     * Add user to cohort.
     *
     * @param array|stdClass $record
     */
    public function create_cohort_member($record) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');
        $record = (array)$record;

        if (empty($record['cohortid'])) {
            throw new coding_exception('cohortid is required in totara_cohort_generator::create_cohort_member() $record');
        }

        if (empty($record['userid'])) {
            throw new coding_exception('userid is required in totara_cohort_generator::create_cohort_member() $record');
        }
        // Make sure user exists.
        $DB->get_record('user', array('id' => $record['userid'], 'deleted' => 0), 'id', MUST_EXIST);

        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        cohort_add_member($record['cohortid'], $record['userid']);
    }

    /**
     * Creates audiences.
     *
     * @deprecated use crate_cohort() from core generator in a loop
     *
     * @param int $numaudience Number of audiences
     * @param array $userids users id to be added to the audience of last user
     * @return array $result Array of audiences id
     */
    public function create_audiences($numaudience, $userids) {
        $result = array();
        $size = floor(count($userids) / $numaudience);
        $listofusers = array_chunk($userids, $size);
        $nextnumber = 1;
        foreach ($listofusers as $users) {
            if ($cohort = $this->create_cohort()) {
                $this->cohort_assign_users($cohort->id, $users);
                $result[$nextnumber] = $cohort->id;
            }
            $nextnumber++;
        }

        return $result;
    }

    /**
     * Create an Audience.
     *
     * @deprecated use core generator
     *
     * @param stdClass|array $record Info related to the cohort table
     * @return stdClass Info related to the cohort table
     */
    public function create_cohort($record = null) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        $record = (array) $record;
        $idnumber = totara_cohort_next_automatic_id();

        $record['name'] = (isset($record['name'])) ? $record['name'] : 'tool_generator_' . $idnumber;
        $record['idnumber'] = (isset($record['idnumber'])) ? $record['idnumber'] : $idnumber;
        $record['contextid'] = (isset($record['contextid'])) ? $record['contextid'] : context_system::instance()->id;
        $record['cohorttype'] = (isset($record['cohorttype'])) ? $record['cohorttype'] : cohort::TYPE_STATIC;
        $record['description'] = (isset($record['description'])) ? $record['description'] : 'Audience create by tool_generator';
        $record['descriptionformat'] = (isset($record['descriptionformat'])) ? $record['descriptionformat'] : FORMAT_HTML;

        return $this->datagenerator->create_cohort($record);
    }

    /**
     * Assign users to the cohort.
     *
     * @param int $cohortid Cohort ID
     * @param array $userids Array of users IDs that need to be assigned to the audience
     */
    public function cohort_assign_users($cohortid, $userids = array()) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        // Assign audience.
        if (!empty($userids)) {
            foreach ($userids as $key => $userid) {
                cohort_add_member($cohortid, $userid);
            }
        }
    }

    /**
     * Add particular mock params to cohort rules
     *
     * @param int $rulesetid Ruleset ID
     * @param string $ruletype Rule type
     * @param string $rulename Rule name
     * @param array $ruleparams Params to add
     * @param array $rulevalues List of values
     * @param string $paramname Current possible values (listofvalues, listofids, managerid, cohortids)
     */
    public function create_cohort_rule_params($rulesetid, $ruletype, $rulename, $ruleparams, $rulevalues, $paramname = 'listofvalues') {
        global $DB, $USER;
        $data = array($ruleparams);
        foreach ($rulevalues as $l) {
            $data[] = array($paramname => $l);
        }
        $ruleid = cohort_rule_create_rule($rulesetid, $ruletype, $rulename);
        foreach ($data as $d) {
            foreach ($d as $name => $value) {
                $todb = new stdClass();
                $todb->ruleid = $ruleid;
                $todb->name = $name;
                $todb->value = $value;
                $todb->timecreated = time();
                $todb->timemodified = time();
                $todb->modifierid = $USER->id;
                $DB->insert_record('cohort_rule_params', $todb);
            }
        }
    }

    /**
     * Remove all current rules and params for a particular cohort ruleset.
     *
     * @param int $rulesetid   The id of the cohort to remove the rules from.
     */
    public function cohort_clean_ruleset($rulesetid) {
        global $DB;

        // Delete all of the associated params.
        $sql = "DELETE
                  FROM {cohort_rule_params}
                 WHERE EXISTS (SELECT 1
                                 FROM {cohort_rules}
                                 WHERE {cohort_rules}.id = {cohort_rule_params}.ruleid
                                 AND {cohort_rules}.rulesetid = :rsid
                             )";
        $params = array('rsid' => $rulesetid);
        $DB->execute($sql, $params);

        // Delete all of the associated rules.
        $sql = "DELETE FROM {cohort_rules} WHERE rulesetid = :rsid";
        $DB->execute($sql, $params);
    }

    /**
     * Get array of rule IDs based on
     *
     * @param int $collectionid Collection id where the rules are
     * @param string $rulegroup Group where the rule belongs to
     * @param string $rulename Name of the type of rule we are dealing with
     *
     * @return  array of ruleids
     */
    public function cohort_get_ruleids($collectionid, $rulegroup, $rulename) {
        global $DB;

        $sql = "SELECT cr.id
            FROM {cohort_rule_collections} crc
            INNER JOIN {cohort_rulesets} crs
              ON crc.id = crs.rulecollectionid
            INNER JOIN {cohort_rules} cr
              ON cr.rulesetid = crs.id
            WHERE crc.id = ?
              AND cr.ruletype = ?
              AND cr.name = ?";

        return $DB->get_fieldset_sql($sql, array($collectionid, $rulegroup, $rulename));
    }
}
