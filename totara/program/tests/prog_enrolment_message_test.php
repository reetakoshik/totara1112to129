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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') or die();

global $CFG;
require_once($CFG->dirroot . "/totara/program/program.class.php");
require_once($CFG->dirroot . "/totara/program/program_messages.class.php");
require_once($CFG->dirroot . "/totara/program/program_message.class.php");


/**
 * Class prog_enrolment_message_test
 */
class prog_enrolment_message_test extends advanced_testcase {
    /**
     * @var string
     */
    private $duedate = "2018-12-29";

    /**
     * Data builder
     * Create a user
     * Create a program
     * Create a message for program
     * Create a due date for program
     *
     * @param bool  $notifymanager
     * @return array
     * @throws dml_exception
     */
    private function prepare_data(bool $notifymanager = false): array {
        global $DB;
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Kian',
            'lastname'  => 'Batman',
            'lang'      => 'en_us'
        ]);

        $id = $DB->insert_record('prog', (object)[
            'category' => 1,
            'sortorder' => 0,
            'fullname' => 'This is spartan',
            'shortname' => 'spartan 101'
        ]);

        $DB->insert_record("prog_completion", (object)[
            'programid' => $id,
            'userid' => $user->id,
            'status' => 0,
            'timedue' => strtotime($this->duedate)
        ]);

        $msgdata = [
            'programid'         => $id,
            'messagetype'       => MESSAGETYPE_ENROLMENT,
            'sortorder'         => 1,
            'messagesubject'    => 'this is test',
            'mainmessage'       => 'this is due date %duedate%',
            'notifymanager'     => $notifymanager ? 1: 0,
            'managersubject'    => "This is manager notification",
            'managermessage'    => "this is due date %duedate%",
            'triggertime'       => 0
        ];

        $msgid = $DB->insert_record("prog_message", (object)$msgdata);

        $msgdata['id'] = $msgid;
        $msgobj = new \prog_enrolment_message($msgdata['programid'], (object)$msgdata);

        return array($user, $msgobj);
    }

    /**
     * Data builder
     * Create a manager
     * Create a job assignment for manager
     * Create a job assignment for user and
     * assign the created manager to a user
     *
     * @param stdClass $user
     */
    private function prepare_manager(stdClass $user): array{
        global $DB;

        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Loc',
            'lastname'  => 'Nugyen',
            'lang'      => 'en'
        ]);

        $id = $DB->insert_record("job_assignment", (object)[
            'userid'                    => $manager->id,
            'usermodified'              => $manager->id,
            'idnumber'                  => 1,
            'timecreated'               => time(),
            'timemodified'              => time(),
            'sortorder'                 => 1,
            'totarasync'                => 0,
            'synctimemodified'          => 0,
            'positionassignmentdate'    => time(),
        ]);

        $DB->insert_record("job_assignment", (object)[
            'userid'                    => $user->id,
            'idnumber'                  => 1919,
            'usermodified'              => $manager->id,
            'timecreated'               => time(),
            'timemodified'              => time(),
            'sortorder'                 => 1,
            'managerjaid'               => $id,
            'totarasync'                => 0,
            'synctimemodified'          => 0,
            'positionassignmentdate'    => time(),
        ]);

        return array($manager);
    }

    /**
     * The method of formatting the date time
     * base on the user language
     * @param stdClass $user
     * @param string   $original        The original date time to be format
     * @return string
     */
    private function format_user_date_time(stdClass $user, string $original): string {
        $format = get_string_manager()->get_string("strftimedatefulllong", "langconfig", null, $user->lang);
        $dt = date(str_replace("%", "", $format), strtotime($original));
        if (!$dt) {
            return "";
        }

        return $dt;
    }

    /**
     * The method that accessing inside the object prog_enrolment_message
     * and retrieve the attribute $replacementvars, since there is no
     * interface to retrieve it
     *
     * @param prog_enrolment_message $msgobj
     * @return array
     */
    public function get_enrolment_message_replacement_vars(\prog_enrolment_message $msgobj): array {
        $refClass = new ReflectionClass($msgobj);
        $property = $refClass->getProperty("replacementvars");
        if (!$property) {
            throw new ReflectionException();
        }

        $property->setAccessible(true);
        return $property->getValue($msgobj);
    }

    /**
     * Since the data due date that have been set to
     * "2018-12-29" and the format for en_us pack is '%m/%d/%Y',
     * therefore the result that the test suite
     * should be expecting is "12/29/2018".
     *
     * The test suite is more about assuring that the translation is stick to
     * whatever the user's locale language is.
     *
     * @throws dml_exception
     */
    public function test_format_message(): void {
        $this->resetAfterTest(true);

        /**
         * @var \prog_enrolment_message $msgobj
         * @var stdClass                $user
         */
        list($user, $msgobj) = $this->prepare_data();

        $msgobj->set_replacementvars($user, []);
        $msgdata = $msgobj->get_student_message_data();

        $message = $msgobj->replacevars($msgdata->fullmessage);

        $dt = $this->format_user_date_time($user, $this->duedate);
        $this->assertEquals("this is due date {$dt}", $message);
    }

    /**
     * The test case of sending the email to both the user
     * and also the manager. Within this test,
     * we are only asserting the duedate value format with
     * the format from the manager language pack only only
     */
    public function test_send_message(): void {
        global $CFG;
        $CFG->smtphosts = null;
        $this->resetAfterTest(true);

        /**
         * @var stdClass                $usr
         * @var \prog_enrolment_message $msgobj
         */
        list($usr, $msgobj) = $this->prepare_data(true);
        list($manager) = $this->prepare_manager($usr);

        $msgobj->send_message($usr);

        $replacementvars = $this->get_enrolment_message_replacement_vars($msgobj);
        $dt = $this->format_user_date_time($manager, $this->duedate);

        $this->assertEquals($dt, $replacementvars['duedate']);
    }
}
