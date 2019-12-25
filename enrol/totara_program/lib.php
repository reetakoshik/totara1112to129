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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package enrol
 * @subpackage totara_program
 */

defined('MOODLE_INTERNAL') || die();

class enrol_totara_program_plugin extends enrol_plugin {

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        global $DB;

        if (!totara_feature_visible('programs') && !totara_feature_visible('certifications')) {
            return null;
        }

        $context = context_course::instance($courseid);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/guest:config', $context)) {
            return NULL;
        }

        if ($DB->record_exists('enrol', array('courseid' => $courseid, 'enrol' => 'totara_program'))) {
            return NULL;
        }

        return new moodle_url('/enrol/totara_program/addinstance.php', array('sesskey' => sesskey(), 'id' => $courseid));
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass  $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        if (!totara_feature_visible('programs') && !totara_feature_visible('certifications')) {
            // Allow deleting only when programs disabled so that they can get rid of preexisting
            // enrolemnts before the programs were disabled.
            $context = context_course::instance($instance->courseid);
            return has_capability('enrol/totara_program:unenrol', $context);
        }

        return false;
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        if (!totara_feature_visible('programs') && !totara_feature_visible('certifications')) {
            return null;
        }

        $fields = array('enrolperiod' => $this->get_config('enrolperiod', 0), 'roleid' => $this->get_config('roleid', 0));
        return $this->add_instance($course, $fields);
    }

    /**
     * Add new instance of enrol_totara_program plugin.
     * @param object $course
     * @param array instance fields
     * @return int id of new instance, or id of existing instance
     */
    public function add_instance($course, array $fields = NULL) {

        $instance = $this->get_instance_for_course($course->id);
        if (!$instance) {
            return parent::add_instance($course);
        } else {
            return $instance->id;
        }
    }

    /**
     * Get the name of the enrolment plugin
     *
     * @return string
     */
    public function get_name() {
        return 'totara_program';
    }

    /**
     * Users are able to be un-enroled from a course
     *
     * @return bool
     */
    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * Process enrolments for users who are unassigned from a program.
     * Behaviour here will depend on the 'unenrolaction' config setting for the plugin.
     *
     * @param stdClass instance of the enrol_totara_program class for a particular course
     * @param array ids of users that have been unassigned.
     *
     * @return void
     */
    public function process_program_unassignments($instance, $userids = array()) {
        global $DB;
        // Do not continue if there is nothing to do or $userids is not an array.
        if (!is_array($userids) || empty($userids)) {
            return;
        }

        // Divide the users into batches to prevent sql problems.
        $batches = array_chunk($userids, $DB->get_max_in_params());
        unset($userids);

        $unenrolaction = $this->get_config('unenrolaction', ENROL_EXT_REMOVED_SUSPEND);

        foreach ($batches as $userids) {
            // Get all the active enrolments with this plugin for these users.
            list($insql, $inparams) = $DB->get_in_or_equal($userids);
            array_push($inparams, $instance->id);
            $active_enrolments = $DB->get_fieldset_select('user_enrolments', 'userid', "userid $insql AND enrolid = ?", $inparams);

            // Depending on the plugin settings, unenrol or suspend the unassigned users from this course.
            switch ($unenrolaction) {
                case ENROL_EXT_REMOVED_UNENROL:
                    $useridbatches = array_chunk($active_enrolments, BATCH_INSERT_MAX_ROW_COUNT);
                    foreach ($useridbatches as $key => $batch) {
                        $this->unenrol_user_bulk($instance, $batch);
                    }
                    break;
                case ENROL_EXT_REMOVED_SUSPEND:
                case ENROL_EXT_REMOVED_SUSPENDNOROLES:
                    foreach ($active_enrolments as $userid) {
                        // Suspend the enrolment.
                        $this->update_user_enrol($instance, $userid, ENROL_USER_SUSPENDED);
                        $context = context_course::instance($instance->courseid);
                        // If ENROL_EXT_REMOVED_SUSPENDNOROLES remove them from all roles.
                        if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                            role_unassign_all(array('userid' => $userid, 'contextid' => $context->id, 'component' => 'enrol_totara_program', 'itemid' => $instance->id));
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Process enrolments for users who are reassigned to a program.
     * Result here will depend on what the 'unenrolaction' config setting for the plugin was set
     * to at the time the user was unenrolled. Only existing enrolment records are reactivated for
     * the given users - if an enrolment record does not exist for a user then this function does
     * not create it.
     *
     * @param stdClass $instance instance of the enrol_totara_program class for a particular course
     * @param array $userids ids of users that have been assigned.
     */
    public function process_program_reassignments($instance, $userids = array()) {
        global $DB;

        // Do not continue if there is nothing to do or $userids is not an array.
        if (!is_array($userids) || empty($userids)) {
            return;
        }

        // Divide the users into batches to prevent sql problems.
        $batches = array_chunk($userids, $DB->get_max_in_params());
        unset($userids);
        foreach ($batches as $batch) {
            list($insql, $params) = $DB->get_in_or_equal($batch, SQL_PARAMS_NAMED);
            $params['enrolid'] = $instance->id;
            $params['suspended'] = ENROL_USER_SUSPENDED;
            $suspendedenrolments = $DB->get_fieldset_select('user_enrolments', 'userid',
                "userid $insql AND enrolid = :enrolid AND status = :suspended", $params);

            foreach ($suspendedenrolments as $userid) {
                // Reactivate the enrolment. Use the API, not db update!
                $this->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE);
            }
        }
    }

    /**
     * Get the instance of this plugin attached to a course if any
     * @param int $courseid id of course
     * @return object|bool $instance or false if not found
     */
    public function get_instance_for_course($courseid) {
        global $DB;
        return $DB->get_record('enrol', array('enrol' => 'totara_program', 'courseid' => $courseid));
    }

    /**
     * Attempt to automatically enrol current user in course without any interaction,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * This function checks if the given course is in a course set group which is currently available
     * to the user. In certifications, when a user is on a path which doesn't contain the given
     * course, the user will not be able to enrol.
     *
     * @param stdClass $instance course enrol instance
     * @return bool|int false means not enrolled, integer means timeend
     */
    public function try_autoenrol(stdClass $instance) {
        global $CFG, $USER, $DB;

        if (!totara_feature_visible('programs') && !totara_feature_visible('certifications')) {
            return false;
        }

        if ($course = $DB->get_record('course', array('id' => $instance->courseid))) {
            //because of use of constants and program class functions, best to leave the prog_can_enter_course function where it is
            require_once($CFG->dirroot . '/totara/program/lib.php');
            $result = prog_can_enter_course($USER, $course);

            if ($result->enroled) {
                //if we just enrolled them, set a notification
                if ($result->notify) {
                    $a = new stdClass();
                    $a->course = $course->fullname;
                    $a->program = $result->program;
                    require_once($CFG->dirroot . '/course/lib.php');
                    $courseformat = course_get_format($course);
                    $viewurl = new moodle_url('/course/view.php', array('id' => $course->id));
                    totara_set_notification(get_string('nowenrolled', 'enrol_totara_program', $a), $viewurl->out(), array('class' => 'notifysuccess'));
                }
                //return 0 sets enrolment with no time limit
                return 0;
            }
        }
        return false;
    }

    /**
     * Gets an array of the user enrolment actions
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol($instance) && has_capability("enrol/totara_program:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
        }
        return $actions;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        // There is only one totara_program enrol instance allowed per course.
        if ($instances = $DB->get_records('enrol', array('courseid' => $data->courseid, 'enrol' => 'manual'), 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        global $DB;

        $ue = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid));
        $enrol = false;
        if ($ue and $ue->status == ENROL_USER_ACTIVE) {
            // We do not want to restrict current active enrolments, let's kind of merge the times only.
            // This prevents some teacher lockouts too.
            if ($data->status == ENROL_USER_ACTIVE) {
                if ($data->timestart > $ue->timestart) {
                    $data->timestart = $ue->timestart;
                    $enrol = true;
                }

                if ($data->timeend == 0) {
                    if ($ue->timeend != 0) {
                        $enrol = true;
                    }
                } else if ($ue->timeend == 0) {
                    $data->timeend = 0;
                } else if ($data->timeend < $ue->timeend) {
                    $data->timeend = $ue->timeend;
                    $enrol = true;
                }
            }
        }

        if ($enrol) {
            $defaultrole = $this->get_config('roleid');
            $this->enrol_user($instance, $userid, $defaultrole, $data->timestart, $data->timeend, $data->status);
        }
    }

    /**
     * Restore role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {
        role_assign($roleid, $userid, $contextid, 'enrol_'.$this->get_name(), $instance->id);
    }

    /**
     * Can current user disable program enrolments in a course?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('moodle/course:enrolconfig', $context);
    }
}

/**
 * Indicates API features that the enrol plugin supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function enrol_totara_program_supports($feature) {
    switch($feature) {
        case ENROL_RESTORE_TYPE:
            return ENROL_RESTORE_EXACT;

        default:
            return null;
    }
}
