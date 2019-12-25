<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * facetoface module data generator class
 *
 * @package    mod_facetoface
 * @author     Maria Torres <maria.torres@totaralms.com>
 * @author     Nathan Lewis <nathan.lewis@totaralms.com>
 * @author     Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

use totara_job\job_assignment;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\seminar_event;

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_generator extends testing_module_generator {

    /**
     * The number of rooms created so far.
     * @var int
     */
    protected $roominstancecount = 0;

    /**
     * The number of assets created so far.
     * @var int
     */
    protected $assetinstancecount = 0;

    /**
     * Cache to reduce lookups.
     * @var array
     */
    protected $mapsessioncourse = [];

    /**
     * Cache to reduce lookups.
     * @var array
     */
    protected $mapsessionf2f = [];


    /**
     * Create new facetoface module instance
     * @param array|stdClass $record
     * @param array $options
     * @throws coding_exception
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }

        $defaults = array();
        $defaults['intro'] = 'Test facetoface ' . $i;
        $defaults['introformat'] = FORMAT_MOODLE;
        $defaults['name'] = get_string('pluginname', 'facetoface').' '.$i;
        $defaults['shortname'] = 'facetoface' . $i;
        $defaults['description'] = 'description';
        $defaults['thirdparty'] = null; // Default to username
        $defaults['thirdpartywaitlist'] = 0;
        $defaults['display'] = 6;
        $defaults['showoncalendar'] = '1';
        $defaults['approvaloptions'] = 'approval_none';
        $defaults['usercalentry'] = 1;
        $defaults['multiplesessions'] = 0;
        $defaults['multisignupmaximum'] = 0;
        $defaults['multisignupnoshow'] = 0;
        $defaults['multisignuppartly'] = 0;
        $defaults['multisignupfully'] = 0;
        $defaults['completionstatusrequired'] = '{"100":1}';
        $defaults['managerreserve'] = 0;
        $defaults['maxmanagerreserves'] = 1;
        $defaults['reservecanceldays'] = 1;
        $defaults['reservedays'] = 2;
        foreach ($defaults as $field => $value) {
            if (!isset($record->$field)) {
                $record->$field = $value;
            }
        }

        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        } else {
            $record->cmidnumber = '';
        }

        $record->coursemodule = $this->precreate_course_module($record->course, $options);
        $id = facetoface_add_instance($record, null);
        return $this->post_add_instance($id, $record->coursemodule);
    }

    /**
     * Add facetoface session
     * @param array|stdClass $record
     * @param array $options
     * @throws coding_exception
     * @return bool|int session created
     */
    public function add_session($record, $options = array()) {
        global $USER, $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        $record = (object) (array) $record;

        if (empty($record->facetoface)) {
            throw new coding_exception('Session generator requires $record->facetoface');
        }

        if (!isset($record->sessiondates) && empty($record->sessiondates)) {
            $time = time();
            $sessiondate = new stdClass();
            $sessiondate->timestart = $time;
            $sessiondate->timefinish = $time + (DAYSECS * 2);
            $sessiondate->sessiontimezone = 'Pacific/Auckland';
            $sessiondate->roomid = 0;
            $sessiondate->assetids = array();
            $sessiondates = array($sessiondate);
        } else {
            $sessiondates = $record->sessiondates;
            unset($record->sessiondates);
        }

        if (!isset($record->capacity)) {
            $record->capacity = 10;
        }
        if (!isset($record->allowoverbook)) {
            $record->allowoverbook = 0;
        }
        if (!isset($record->normalcost)) {
            $record->normalcost = '$100';
        }
        if (!isset($record->discountcost)) {
            $record->discountcost = '$NZ20';
        }
        if (!isset($record->discountcost)) {
            $record->discountcost = FORMAT_MOODLE;
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->waitlisteveryone)) {
            $record->waitlisteveryone = 0;
        }
        if (!isset($record->registrationtimestart)) {
            $record->registrationtimestart = 0;
        }
        if (!isset($record->registrationtimefinish)) {
            $record->registrationtimefinish = 0;
        }

        $record->usermodified = $USER->id;

        $seminarevent = new \mod_facetoface\seminar_event();
        $seminarevent->from_record($record);
        $seminarevent->save();
        facetoface_save_dates($seminarevent->to_record(), $sessiondates);

        return $seminarevent->get_id();
    }

    /**
     * Create a room - please use the add_custom_room, or add_site_wide_room methods.
     *
     * @param stdClass|array $record
     * @return mixed
     */
    protected function add_room($record) {
        global $DB, $USER;

        $this->roominstancecount++;
        $record = (object) $record;

        if (!isset($record->name)) {
            $record->name = 'Room '.$this->roominstancecount;
        }
        if (!isset($record->capacity)) {
            // Don't ever bet on the capacity, if you need to be something specific set it to that.
            $record->capacity = floor(rand(5, 50));
        }

        if (!empty($record->allowconflicts)) {
            $record->allowconflicts = 1;
        } else {
            $record->allowconflicts = 0;
        }

        if (!isset($record->description)) {
            $record->description = 'Description for room '.$this->roominstancecount;
        }
        if (!isset($record->custom)) {
            $record->custom = 1;
        }
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        $record->usermodified = $record->usercreated;
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        $record->timemodified = $record->timecreated;
        $id = $DB->insert_record('facetoface_room', $record);
        return $DB->get_record('facetoface_room', array('id' => $id));
    }

    /**
     * Add a custom room.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_custom_room($record) {
        $record = (object)$record;
        $record->custom = 1;
        return $this->add_room($record);
    }

    /**
     * Add a site wide room.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_site_wide_room($record) {
        $record = (object)$record;
        $record->custom = 0;
        return $this->add_room($record);
    }

    /**
     * Add global room.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function create_global_room_for_behat(array $record=[]) {
        return $this->add_site_wide_room($record);
    }

    /**
     * Create a asset - please use the add_custom_asset, or add_site_wide_asset methods.
     *
     * @param stdClass|array $record
     * @return mixed
     */
    protected function add_asset($record) {
        global $DB, $USER;

        $this->assetinstancecount++;
        $record = (object) $record;

        if (!isset($record->name)) {
            $record->name = 'asset '.$this->assetinstancecount;
        }

        if (!empty($record->allowconflicts)) {
            $record->allowconflicts = 1;
        } else {
            $record->allowconflicts = 0;
        }

        if (!isset($record->description)) {
            $record->description = 'Description for asset '.$this->assetinstancecount;
        }
        if (!isset($record->custom)) {
            $record->custom = 1;
        }
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        $record->usermodified = $record->usercreated;
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        $record->timemodified = $record->timecreated;
        $id = $DB->insert_record('facetoface_asset', $record);
        return $DB->get_record('facetoface_asset', array('id' => $id));
    }

    /**
     * Add a custom asset.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_custom_asset($record) {
        $record = (object)$record;
        $record->custom = 1;
        return $this->add_asset($record);
    }

    /**
     * Add a site wide asset.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_site_wide_asset($record) {
        $record = (object)$record;
        $record->custom = 0;
        return $this->add_asset($record);
    }

    /**
     * Resets this generator instance.
     */
    public function reset() {
        $this->roominstancecount = 0;
        $this->assetinstancecount = 0;
        parent::reset();
    }

    /**
     * Create facetoface content (Session)
     * @param stdClass $instance
     * @param array|stdClass $record
     * @return bool|int content created
     */
    public function create_content($instance, $record = array()) {
        $record = (array)$record + array(
                'facetoface' => $instance->id
            );

        return $this->add_session($record);
    }

    /**
     * Create a session for the given course.
     * Creates facetoface for the session as well.
     *
     * @param stdClass $course
     * @param int $daysoffset how many days from now session will occur
     * @return stdClass
     */
    public function create_session_for_course(stdClass $course, int $daysoffset = 1): stdClass {
        // Set up facetoface.
        $facetofacedata = [
            'name' => 'facetoface1',
            'course' => $course->id
        ];
        $facetoface = $this->create_instance($facetofacedata);

        // Set up session.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + $daysoffset * DAYSECS;
        $sessiondate->timefinish = time() + $daysoffset * DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = [
            'facetoface' => $facetoface->id,
            'sessiondates' => [$sessiondate],
        ];
        $sessionid = $this->add_session($sessiondata);

        $this->mapsessioncourse[$sessionid] = $course;
        $this->mapsessionf2f[$sessionid] = $facetoface;

        return facetoface_get_session($sessionid);
    }

    /**
     * Create a signup for given student and session.
     *
     * @param stdClass $student
     * @param stdClass $session
     * @return stdClass
     */
    public function create_signup(stdClass $student, stdClass $session): stdClass {
        global $DB;

        $this->create_job_assignment_if_not_exists($student);

        $discountcode = 'disc1';
        $notificationtype = 1;

        $signup = \mod_facetoface\signup::create($student->id, new \mod_facetoface\seminar_event($session->id), $notificationtype);
        $signup->set_discountcode($discountcode);
        signup_helper::signup($signup);

        return $DB->get_record('facetoface_signups', ['userid' => $student->id, 'sessionid' => $session->id]);
    }

    /**
     * @param stdClass $student
     * @param stdClass $session
     */
    public function create_cancellation(stdClass $student, stdClass $session) {
        $seminarevent = new seminar_event($session->id);
        $signup = signup::create($student->id, $seminarevent);
        if (signup_helper::can_user_cancel($signup)) {
            signup_helper::user_cancel($signup);
        }
    }

    /**
     * @param stdClass $signup
     * @param string $type
     * @param string $filename
     * @param int $itemid  Any integer. Use the same number if you want multiple files for
     *  the same field. See totara_customfield_generator::create_test_file_from_content().
     * @return stored_file
     */
    public function create_file_customfield(stdClass $signup, string $type, string $filename, int $itemid) {
        global $DB;

        $datagenerator = phpunit_util::get_data_generator();
        /** @var totara_customfield_generator $cfgenerator */
        $cfgenerator = $datagenerator->get_plugin_generator('totara_customfield');
        $cfid = $cfgenerator->create_file("facetoface_{$type}", ['f2ffile' => []]);

        $filecontent = 'Test file content';
        $filepath = '/';
        $cfgenerator->create_test_file_from_content($filename, $filecontent, $itemid, $filepath, $signup->userid);

        $cfgenerator->set_file($signup, $cfid['f2ffile'], $itemid, "facetoface{$type}", "facetoface_{$type}");

        $customfieldid = $DB->get_field(
            "facetoface_{$type}_info_data",
            'id',
            ["facetoface{$type}id" => $signup->id, 'fieldid' => $cfid['f2ffile']]
        );

        $syscontext = context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file(
            $syscontext->id,
            'totara_customfield',
            "facetoface{$type}_filemgr",
            $customfieldid,
            $filepath,
            $filename
        );
        return $file;
    }

    /**
     * Create some customfield data that results in the given amount of field and parameter data.
     *
     * @param stdClass $signup
     * @param string $type
     * @param int $fieldcount
     * @param int $paramcount
     * @return array array of facetoface_$type_info_data ids
     */
    public function create_customfield_data(stdClass $signup, string $type, int $fieldcount, int $paramcount): array {
        global $DB;

        $datagenerator = phpunit_util::get_data_generator();
        /** @var totara_customfield_generator $cfgenerator */
        $cfgenerator = $datagenerator->get_plugin_generator('totara_customfield');

        if ($fieldcount < 1) {
            return [];
        }

        $customfieldids = [];
        if ($paramcount) {
            // If we want data in the *info_data_param table, we need one multiselect field with the desired number of options.

            // Create options.
            $options = array_map(function($i) use ($type) {
                return "{$type}_option_{$i}";
            }, range(1, $paramcount));

            // Create customfield.
            $uniquefieldname = "{$type}_multi_{$signup->id}";
            $cfids = $cfgenerator->create_multiselect("facetoface_{$type}", [$uniquefieldname => $options]);

            // Create customfield data with all options selected.
            $cfgenerator->set_multiselect($signup, $cfids[$uniquefieldname], $options, "facetoface{$type}", "facetoface_{$type}");

            $fieldcount --;

            $customfieldids[] = $DB->get_field(
                "facetoface_{$type}_info_data",
                'id',
                ["facetoface{$type}id" => $signup->id, 'fieldid' => $cfids[$uniquefieldname]]
            );
        }

        // Use text field for the other customfields that don't need data in the *info_data_param table.
        for ($i = 1; $i <= $fieldcount; $i ++) {
            $uniquefieldname = "{$type}_text_{$signup->id}_{$i}";
            $cfids = $cfgenerator->create_text("facetoface_{$type}", [$uniquefieldname]);
            $cfgenerator->set_text($signup, $cfids[$uniquefieldname], "value_{$i}", "facetoface{$type}", "facetoface_{$type}");

            $customfieldids[] = $DB->get_field(
                "facetoface_{$type}_info_data",
                'id',
                ["facetoface{$type}id" => $signup->id, 'fieldid' => $cfids[$uniquefieldname]]
            );
        }

        return $customfieldids;
    }

    /**
     * Students need job assignments with manager so we can sign them up to a facetoface session.
     *
     * @param stdClass $student
     */
    protected function create_job_assignment_if_not_exists(stdClass $student) {
        global $DB;
        // Skip if we already created it.
        if (!$DB->record_exists('job_assignment', ['userid' => $student->id])) {
            $datagenerator = phpunit_util::get_data_generator();
            $manager = $datagenerator->create_user();
            $managerja = job_assignment::create_default($manager->id);
            $data = [
                'userid' => $student->id,
                'fullname' => 'student1ja',
                'shortname' => 'student1ja',
                'idnumber' => 'student1ja',
                'managerjaid' => $managerja->id,
            ];
            job_assignment::create($data);
        }
    }

    /**
     * @param array $record
     */
    public function create_custom_room_for_behat(array $record) {
        $this->add_custom_room($record);
    }

    /**
     * @param array $record
     */
    public function create_global_asset_for_behat(array $record) {
        $record['custom'] = 0;
        $this->add_asset($record);
    }
}
