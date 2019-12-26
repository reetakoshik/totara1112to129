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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_appraisal
 * @category test
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Appraisal generator.
 *
 * @package totara_appaisal
 * @category test
 */
class totara_appraisal_generator extends component_generator_base {

    // Default name when created a learning plan.
    const DEFAULT_NAME = 'Test Appraisal';
    const DEFAULT_NAME_STAGE = 'Test Stage';
    const DEFAULT_NAME_PAGE = 'Test Page';
    const DEFAULT_NAME_QUESTION = 'Test Question';
    const DEFAULT_NAME_MESSAGE = 'Test Message';

    /**
     * @var integer Keep a count of how many appraisals have been created.
     */
    private $appraisalcount = 0;

    /**
     * @var integer Keep a count of how many stages have been created.
     */
    private $stagecount = 0;

    /**
     * @var integer Keep a count of how many pages have been created.
     */
    private $pagecount = 0;

    /**
     * @var integer Keep a count of how many questions have been created.
     */
    private $questioncount = 0;

    /**
     * @var integer Keep a count of how many messages have been created.
     */
    private $messagecount = 0;

    /**
     * To be called from data reset code only, do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
        $this->appraisalcount = 0;
        $this->stagecount = 0;
        $this->pagecount = 0;
        $this->questioncount = 0;
        $this->messagecount = 0;
    }

    /**
     * Create an appraisal.
     *
     * @param  array    $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_appraisal($data = array()) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        // Increment the count of appraisals.
        $i = ++$this->appraisalcount;

        // Create some default values for the appraisal.
        $defaults = array ('userid' => $USER->id,
            'name' => self::DEFAULT_NAME . ' ' . $i,
            'description' => '<p>' . self::DEFAULT_NAME . ' ' . $i . ' description</p>',
            'status' => 0
        );

        // Merge the defaults and the given data and cast into an object.
        $data = (object) array_merge($defaults, (array) $data);

        // Create a new appraisal.
        $appraisal = new appraisal();
        $appraisal->set($data);
        $appraisal->save();

        return $appraisal;
    }

    /**
     * Create an appraisal stage.
     *
     * @param  integer $appraisalid Record id of the appraisal to add the stage to.
     * @param  array $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_stage($appraisalid, $data = array()) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        // Increment the count of stages.
        $i = ++$this->stagecount;

        // Build due date.
        $now = time();
        $timedue = $now + ($i * (2 * WEEKSECS));

        // Create some default values for the stage.
        $defaults = array ('appraisalid' => $appraisalid,
            'name' => self::DEFAULT_NAME_STAGE . ' ' . $i,
            'description' => '<p>' . self::DEFAULT_NAME_STAGE . ' ' . $i . ' description</p>',
            'timedue' => $timedue
        );

        // Convert a string based timedue to a timestamp.
        if (isset($data['timedue']) && !is_numeric($data['timedue'])) {
            $oldtz = date_default_timezone_get();
            date_default_timezone_set(core_date::get_default_php_timezone());
            $timestamp = strtotime($data['timedue']);
            date_default_timezone_set($oldtz);

            // Update timedue if the date is valid.
            if ($timestamp) {
                $data['timedue'] = $timestamp;
            } else {
                unset($data['timedue']);
            }
        }

        // Merge the defaults and the given data and cast into an object.
        $data = (object) array_merge($defaults, (array) $data);

        // Create a new stage.
        $stage = new appraisal_stage();
        $stage->set($data);
        $stage->save();

        return $stage;
    }

    /**
     * Create an appraisal stage for Behat.
     *
     * @param  array    $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_stage_for_behat($data = array()) {
        global $DB;

        // We need to know the appraisal name so we can look it up and get the id for the new stage.
        if (isset($data['appraisal'])) {
            $appraisalid = $DB->get_field('appraisal', 'id', array('name' => $data['appraisal']), MUST_EXIST);
            unset($data['appraisal']);
        } else {
            return false;
        }

        return $this->create_stage($appraisalid, $data);
    }

    /**
     * Create an appraisal page.
     *
     * @param  integer  $stageid Record id of the stage to add the page to.
     * @param  array    $data    Optional data.
     * @return stdClass Created instance.
     */
    public function create_page($stageid, $data = array()) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        // Increment the count of pages.
        $i = ++$this->pagecount;

        // Create some default values for the page.
        $defaults = array (
            'appraisalstageid' => $stageid,
            'name' => self::DEFAULT_NAME_PAGE . ' ' . $i
        );

        // Merge the defaults and the given data and cast into an object.
        $data = (object) array_merge($defaults, (array) $data);

        // Create a new page.
        $page = new appraisal_page();
        $page->set($data);
        $page->save();

        return $page;
    }

    /**
     * Create an appraisal page for Behat.
     *
     * @param  array    $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_page_for_behat($data = array()) {
        global $DB;

        // We need to know the appraisal name so we can look it up and get the id for the stage.
        if (isset($data['appraisal'])) {
            $appraisalid = $DB->get_field('appraisal', 'id', array('name' => $data['appraisal']), MUST_EXIST);
            unset($data['appraisal']);
        } else {
            return false;
        }

        // We need to know the stage name so we can look it up and get the id for the new page.
        if (isset($data['stage'])) {
            $stageid = $DB->get_field('appraisal_stage', 'id', array('appraisalid' => $appraisalid, 'name' => $data['stage']), MUST_EXIST);
            unset($data['stage']);
        } else {
            return false;
        }

        return $this->create_page($stageid, $data);
    }

    /**
     * Create an appraisal question.
     *
     * @param  integer  $pageid  Record id of the page to add the question to.
     * @param  array    $data    Optional data.
     * @return stdClass Created instance.
     */
    public function create_question($pageid, $data = array()) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        // Increment the count of questions.
        $i = ++$this->questioncount;

        // Create some default values for the page.
        $defaults = array (
            'appraisalstagepageid' => $pageid,
            'name' => self::DEFAULT_NAME_QUESTION . ' ' . $i,
            'roles' => array(appraisal::ROLE_LEARNER => appraisal::ACCESS_CANANSWER),
            'datatype' => 'text',
        );

        // Merge the defaults and the given data and cast into an object.
        $data = (object) array_merge($defaults, (array) $data);

        // Create a new page.
        $question = new appraisal_question();
        $question->attach_element($data->datatype); // I don't understand why datatype is set here and not in the set method.
        $question->set($data);
        $question->save();

        return $question;
    }

    /**
     * Create a complex appraisal question with multiple roles.
     *
     * @param  integer  $pageid  Record id of the page to add the question to.
     * @param  array    $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_complex_question($pageid, $data = array()) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        $i = ++$this->questioncount;

        // Default roles are Learner, Manager, Appraiser.
        $defaults = array(
            'appraisalstagepageid' => $pageid,
            'name' => self::DEFAULT_NAME_QUESTION . ' ' . $i,
            'roles' => array(appraisal::ROLE_LEARNER => appraisal::ACCESS_CANANSWER,
                             appraisal::ROLE_MANAGER => 3,
                             appraisal::ROLE_APPRAISER => 3
                         ),
        );

        // Set datatype which determines the type of question.
        $defaults['datatype'] = 'longtext';

        // Merge the defaults and the given data and cast into an object.
        $data = (object) array_merge($defaults, (array) $data);

        // Create a new page.
        $qmanager = new question_manager();
        $qmanager->reset();
        $question = new appraisal_question();
        $element = $qmanager->create_element($question, $data->datatype);
        $question->attach_element($element);
        $question->set($data);
        $question->save();
        $question->get_element()->save();

        return $question;
    }

    /**
     * Designed for use with create_question_for_behat.
     *
     * @param array $data - data array
     */
    private function setup_ratingnumeric_question(array &$data) {

        // Make sure we have some default values for things set by $extradata.
        $data['rangefrom'] = 1;
        $data['rangeto'] = 10;
        $data['list'] = "1"; // DISPLAY_SLIDER
        if (!empty($data['ExtraInfo'])) {
            $extrainfo = explode(',', $data['ExtraInfo']);

            foreach ($extrainfo as $extra) {
                list($name, $value) = explode(':', $extra);

                switch($name) {
                    case 'Range':
                        // Data should be formatted X-Y
                        list($min, $max) = explode('-', $value);
                        $data['rangefrom'] = (int)$min;
                        $data['rangeto'] = (int)$max;
                        break;
                    case 'Display':
                        // Acceptable values are slider or text.
                        switch ($value) {
                            case 'slider':
                                $data['list'] = "1"; // DISPLAY_SLIDER
                                break(2);
                            case 'text':
                                $data['list'] = "2"; // DISPLAY_INPUT
                                break(2);
                            default:
                                return false;
                        }
                    default:
                        return false;
                }
            }
            unset($data['ExtraInfo']);
        }

        $data['setdefault'] = null;
        if (isset($data['default']) && is_numeric($data['default'])) {
            $data['setdefault'] = true;
            $data['defaultvalue'] = $data['default'];

            unset($data['default']);
        }
    }

    /**
     * Designed for use with create_question_for_behat.
     *
     * @param array $data - data array
     */
    private function setup_ratingcustom_question(array &$data) {
        $choices = array();

        // Make sure we have some default values for things set by $extradata.
        $data['listtype']['list'] = "1"; //DISPLAY_RADIO
        if (!empty($data['ExtraInfo'])) {
            $extrainfo = explode(',', $data['ExtraInfo']);

            foreach ($extrainfo as $extra) {
                list($name, $value) = explode(':', $extra);

                switch($name) {
                    case 'Display':
                        // Acceptable values are radio or menu.
                        switch ($value) {
                            case 'slider':
                                $data['listtype']['list'] = "1"; //DISPLAY_RADIO
                                break(2);
                            case 'text':
                                $data['listtype']['list'] = "2"; // DISPLAY_MENU
                                break(2);
                            default:
                                return false;
                        }
                    case 'Scores':
                        $scores = explode(';', $value);
                        foreach ($scores as $idx => $score) {
                            $choice = array();
                            $choice['option'] = 'choice' . ($idx + 1);
                            $choice['score'] = $score;
                            $choice['default'] = "0";

                            $choices[] = $choice;
                        }
                        break;
                    default:
                        return false;
                }
            }
            unset($data['ExtraInfo']);
        }

        if (count($choices) == 0) {
            // Set up some default options.
            for ($index = 1; $index < 6; $index++) {
                $choice = array();
                $choice['option'] = 'choice' . $index;
                $choice['score'] = $index * 2;
                $choice['default'] = "0";

                $choices[] = $choice;
            }
        }

        if (isset($data['default']) && $data['default'] !== '') {
            foreach ($choices as $idx => $choice) {
                if ($choice['option'] == $data['default']) {
                    // For some reason assigning $choice['default'] doesn't always work
                    $choices[$idx]['default'] = "1";
                }
            }

            $data['selectedchoices'] = 1;
            unset($data['default']);
        }

        $data['choice'] = $choices;
        $data['saveoptions'] = 0;
        $data['multiselectfield'] = 2;
    }

    /**
     * Designed for use with create_question_for_behat.
     *
     * @param array $data - data array
     */
    private function setup_aggregate_question(array &$data) {
        $options = appraisal::get_aggregation_question_list($data['appraisalstagepageid']);

        // Should contain question names.
        $selections = array();
        $useUnans = false;
        $useZero = false;

        if (!isset($data['ExtraInfo']) or $data['ExtraInfo'] === '*') {
            $selections = array_keys($options);
        } else {
            $extrainfo = explode(';', $data['ExtraInfo']);

            // For now having at most 2 options.
            // Using foreach for future expansion
            foreach ($extrainfo as $extra) {
                $valuepair = explode(':', $extra);
                if (count ($valuepair) == 1) {
                    $questions = explode(',', $valuepair[0]);
                    // Match the question name on string like "Stagename - Pagename : Questionname".
                    foreach ($questions as $quest) {
                        foreach ($options as $key => $option) {
                            $pattern = '/.+ - .+ : ' . $quest . '/';
                            if (preg_match($pattern, $option)) {
                                $selections[] = $key;
                                continue(2);
                            }
                        }
                    }
                }
                else {
                    switch ($valuepair[0]) {
                        case 'UseUnans':
                            $useUnans = strtolower($valuepair[1][0]) == 't' ? true : false;
                            break;
                        case 'UseZero':
                            $useZero = strtolower($valuepair[1][0]) == 't' ? true : false;
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        unset($data['ExtraInfo']);

        $data['multiselectfield'] = $selections;
        $data['aggregateaverage'] = "1";
        $data['aggregatemedian'] = "1";
        $data['aggregateincludeunanswered'] = $useUnans;
        $data['aggregateincludezero'] = $useZero;
    }

    /**
     * Create an appraisal question for Behat.
     *
     * @param  array    $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_question_for_behat($data = array()) {
        global $DB;

        // Increment the count of questions.
        $i = ++$this->questioncount;

        // We need to know the appraisal name so we can look it up and get the id for the stage.
        if (isset($data['appraisal'])) {
            $appraisalid = $DB->get_field('appraisal', 'id', array('name' => $data['appraisal']), MUST_EXIST);
            unset($data['appraisal']);
        } else {
            return false;
        }

        // We need to know the stage name so we can look it up and get the id for the page.
        if (isset($data['stage'])) {
            $stageid = $DB->get_field('appraisal_stage', 'id', array('appraisalid' => $appraisalid, 'name' => $data['stage']), MUST_EXIST);
            unset($data['stage']);
        } else {
            return false;
        }

        // We need to know the page name so we can look it up and get the id for the new question.
        if (isset($data['page'])) {
            $pageid = $DB->get_field('appraisal_stage_page', 'id', array('appraisalstageid' => $stageid, 'name' => $data['page']), MUST_EXIST);
            unset($data['page']);
        } else {
            return false;
        }

        $data['appraisalstagepageid'] = $pageid;

        if (!isset($data['name'])) {
            $data['name'] = self::DEFAULT_NAME_QUESTION . ' ' . $i;
        }

        if (isset($data['roles'])) {
            // For now - learner gets cananswer, other gets cananswer and canviewother
            $roles = explode(',', $data['roles']);
            unset($data['roles']);

            foreach ($roles as $role) {
                switch ($role) {
                    case 'learner':
                        $data['roles'][appraisal::ROLE_LEARNER] = appraisal::ACCESS_CANANSWER;
                        break;
                    case 'manager':
                        $data['roles'][appraisal::ROLE_MANAGER] = appraisal::ACCESS_CANANSWER + appraisal::ACCESS_CANVIEWOTHER;
                        break;
                    case 'teamlead':
                        $data['roles'][appraisal::ROLE_TEAM_LEAD] = appraisal::ACCESS_CANANSWER + appraisal::ACCESS_CANVIEWOTHER;
                        break;
                    case 'appraiser':
                        $data['roles'][appraisal::ROLE_APPRAISER] = appraisal::ACCESS_CANANSWER + appraisal::ACCESS_CANVIEWOTHER;
                        break;
                }
            }
        } else {
            $data['roles'] = array(
                appraisal::ROLE_LEARNER => appraisal::ACCESS_CANANSWER,
                appraisal::ROLE_MANAGER => appraisal::ACCESS_CANANSWER + appraisal::ACCESS_CANVIEWOTHER,
                appraisal::ROLE_APPRAISER => appraisal::ACCESS_CANANSWER + appraisal::ACCESS_CANVIEWOTHER
            );
        }

        if (!isset($data['type'])) {
            $data['type'] = 'text';
        }

        // Custom setup functions per question type.
        $funcname = "setup_{$data['type']}_question";
        if ($data['type'] != 'text') {
            if (!is_callable(array($this, $funcname))) {
                return false;
            }
            $this->$funcname($data);
        }

        // Merge the defaults and the given data and cast into an object.
        // Create a new page.
        $qmanager = new question_manager();
        $qmanager->reset();
        $question = new appraisal_question();
        $element = $qmanager->create_element($question, $data['type']);
        $question->attach_element($element);
        $question->set((object) $data);
        $question->save();
        $question->get_element()->save();

        return true;
    }

    /**
     * Assign group of users to an appraisal
     *
     * @param appraisal $appraisal The Appraisal ID where we want to assign the group of users
     * @param string $grouptype The Group type. e.g: cohort, pos, org.
     * @param int $instanceid The instance ID. ID of the cohort, organisation or position.
     * @throws moodle_exception
     */
    public function create_group_assignment($appraisal, $grouptype, $instanceid) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        $grouptypes = totara_assign_appraisal::get_assignable_grouptypes();
        if (!in_array($grouptype, $grouptypes)) {
            $a = new stdClass();
            $a->grouptype = $grouptype;
            $a->module = 'appraisal';
            print_error('error:assignmentgroupnotallowed', 'totara_core', null, $a);
        }

        // Assigning group.
        $urlparams = array('includechildren' => false, 'listofvalues' => array($instanceid));
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        $grouptypeobj = $assign->load_grouptype($grouptype);
        $grouptypeobj->handle_item_selector($urlparams);
    }

    /**
     * Assign a group of users to an appraisal for Behat.
     *
     * @param  array    $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_assignment_for_behat($data = array()) {
        global $DB;

        // Get the appraisal object.
        if (isset($data['appraisal'])) {
            $appraisalid = $DB->get_field('appraisal', 'id', array('name' => $data['appraisal']), MUST_EXIST);
            $appraisal = new appraisal($appraisalid);
        } else {
            return false;
        }

        // validate the type of group we want to assign to the appraisal and get the record id.
        if (isset($data['type'])) {
            $type = $data['type'];
            unset($data['type']);

            switch ($type) {
                case 'cohort':
                case 'audience':
                    $type = 'cohort';
                    break;
                case 'org':
                case 'organisation':
                    $type = 'org';
                    break;
                case 'pos':
                case 'position':
                    $type = 'pos';
                    break;
                default:
                    return false;
            }

            // Get the record id of the group we want to assign to the appraisal.
            $typeid = $DB->get_field($type, 'id', array('idnumber' => $data['id']), MUST_EXIST);
        } else {
            return false;
        }

        return $this->create_group_assignment($appraisal, $type, $typeid);
    }

    /**
     * Create an appraisal message.
     *
     * @param  integer  $appraisalid Record id of the appraisal to add the message to.
     * @param  array    $data        Optional data.
     * @return stdClass Created instance.
     */
    public function create_message($appraisalid, $data = array()) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        // Increment the count of questions.
        $i = ++$this->messagecount;

        // Create some default values for the message.
        $defaults = array (
            'appraisalid' => $appraisalid,
            'stageid' => 0,
            'event' => appraisal_message::EVENT_APPRAISAL_ACTIVATION,
            'delta' => 0,
            'deltaperiod' => 0,
            'roles' => array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER, appraisal::ROLE_TEAM_LEAD, appraisal::ROLE_APPRAISER),
            'completeby' => appraisal_message::MESSAGE_SEND_ANY_STATE,
            'messageto' => 'all',
            'name' => self::DEFAULT_NAME_MESSAGE . ' ' . $i,
        );

        // Merge the defaults and the given data and cast into an object.
        $data = (object) array_merge($defaults, (array) $data);

        // If not set, the body of the message is dependant on what
        // 'name' gets set to so set it afterthe array_merge.
        if (!isset($data->body)) {
            $data->body = $data->name . ' body';
        }

        // Create a new message.
        $message = new appraisal_message();
        $message->event_appraisal($data->appraisalid);
        if ($data->stageid != 0) {
            $message->event_stage($data->stageid, $data->event);
        }
        $message->set_delta($data->delta, $data->deltaperiod);
        $message->set_roles($data->roles, $data->completeby);

        if ($data->messageto == 'all') {
            $message->set_message(0, $data->name, $data->body);
        } else if (is_array($data->roles)) {

            // Generate a role-based suffix so we can identify the email.
            foreach ($data->roles as $role) {
                switch ($role) {
                    case appraisal::ROLE_LEARNER:
                        $for_suffix = ' for ' . get_string('rolelearner', 'totara_appraisal');
                        break;
                    case appraisal::ROLE_MANAGER:
                        $for_suffix = ' for ' . get_string('rolemanager', 'totara_appraisal');
                        break;
                    case appraisal::ROLE_TEAM_LEAD:
                        $for_suffix = ' for ' . get_string('rolelearner', 'totara_appraisal');
                        break;
                    case appraisal::ROLE_APPRAISER:
                        $for_suffix = ' for ' . get_string('rolelearner', 'totara_appraisal');
                        break;
                }

                $message->set_message($role, $data->name . $for_suffix, $data->body . $for_suffix);
            }
        } else {
            return false;
        }

        $message->save();

        return $message;
    }

    /**
     * Create an appraisal message for Behat.
     *
     * @param  array    $data Optional data.
     * @return stdClass Created instance.
     */
    public function create_message_for_behat($data = array()) {
        global $DB;

        // Remove any data items that have no value so the defaults are used in create_message.
        foreach ($data as $key => $value) {
            if ($value === '') unset ($data[$key]);
        }

        // We need to know the appraisal name so we can look it up and get the id for the stage.
        if (isset($data['appraisal'])) {
            $appraisalid = $DB->get_field('appraisal', 'id', array('name' => $data['appraisal']), MUST_EXIST);
            unset($data['appraisal']);
        } else {
            return false;
        }

        if (isset($data['stage'])) {
            $data['stageid'] = $DB->get_field('appraisal_stage', 'id', array('name' => $data['stage']), MUST_EXIST);
            unset($data['stage']);
        }

        // Make sure event is properly formatted and a valid value.
        if (isset($data['event'])) {
            $data['event'] = strtolower(str_replace(' ', '_', $data['event']));

            if (!in_array($data['event'], array(appraisal_message::EVENT_APPRAISAL_ACTIVATION, appraisal_message::EVENT_STAGE_COMPLETE, appraisal_message::EVENT_STAGE_DUE))) {
                return false;
            }
        }

        // Validate the value for delta a.k.a. 'How much earlier/later' number
        if (isset($data['delta']) && !intval($data['delta'])) {
            return false;
        }

        // Validate the value for delta period a.k.a. 'How much earlier/later' calender units.
        if (isset($data['deltaperiod'])) {
            switch ($data['deltaperiod']) {
                case 'day':
                case 'days':
                    $data['deltaperiod'] = appraisal_message::PERIOD_DAY;
                    break;
                case 'week':
                case 'weeks':
                    $data['deltaperiod'] = appraisal_message::PERIOD_WEEK;
                    break;
                case 'month':
                case 'months':
                    $data['deltaperiod'] = appraisal_message::PERIOD_MONTH;
                    break;
                default:
                    return false;
            }
        }

        // Validate and concert any values given for the message recipients.
        if (isset($data['recipients'])) {
            if ($data['recipients'] != 'all') {
                $valid_roles = array();
                $roles = explode(',', $data['recipients']);

                if ($roles) {
                    foreach ($roles as $rolename) {
                        $rolename = trim($rolename);

                        // Check the role's that have been givenare valid.
                        switch ($rolename) {
                            case 'learner':
                                $role = appraisal::ROLE_LEARNER;
                                break;
                            case 'manager':
                                $role = appraisal::ROLE_MANAGER;
                                break;
                            case "manager's manager":
                            case 'team lead':
                                $role = appraisal::ROLE_TEAM_LEAD;
                                break;
                            case 'appraiser':
                                $role = appraisal::ROLE_APPRAISER;
                                break;
                            default:
                                return false;
                        }

                        $data['roles'][$rolename] = $role;
                    }
                } else {
                    return false;
                }
            }

            unset($data['recipients']);
        }

        // Validate the messageto value.
        if (isset($data['messageto']) && $data['messageto'] != 'all' && $data['messageto'] != 'each') {
            return false;
        }

        // Validate the completeby value.
        if (isset($data['completeby'])) {
            if (strtolower($data['completeby']) == 'incomplete') {
                $data['completeby'] = -1;
            } else if (strtolower($data['completeby']) == 'complete') {
                $data['completeby'] = 1;
            } else if ($data['completeby'] != "-1" && $data['completeby'] != "1") {
                return false;
            }
        }

        return $this->create_message($appraisalid, $data);
    }

    /**
     * Activate an appraisal.
     *
     * @param int $appraisalid Appraisal ID to activate.
     */
    public function activate($appraisalid) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        $appraisal = new appraisal($appraisalid);
        $appraisal->activate();
    }

    /**
     * Assign an appraisal to a job assignment.
     *
     * @param  array $data
     * @return void
     */
    public function create_appraisal_job_assignments_for_behat($data = array()) {
        global $DB;

        // Get the appraisal object.
        if (isset($data['appraisal'])) {
            $appraisalid = $DB->get_field('appraisal', 'id', array('name' => $data['appraisal']), MUST_EXIST);
            $appraisal = new appraisal($appraisalid);
        } else {
            return false;
        }

        // Get the job assignment.
        if (isset($data['jobassignment'])) {
            $jobassignment = $DB->get_record('job_assignment', array('idnumber' => $data['jobassignment']));
        } else {
            return false;
        }

        $appraisal_user_assignment = appraisal_user_assignment::get_user($appraisalid, $jobassignment->userid);
        $appraisal_user_assignment->with_job_assignment($jobassignment->id);
    }

}
