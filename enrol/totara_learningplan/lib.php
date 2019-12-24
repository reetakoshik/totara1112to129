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
 * @author Chris Wharton <chrisw@catalyst.net.nz>
 * @package totara
 * @subpackage enrol_totara_learningplan
 */

defined('MOODLE_INTERNAL') || die();

class enrol_totara_learningplan_plugin extends enrol_plugin {

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        global $DB;

        if (!totara_feature_visible('learningplans')) {
            return null;
        }

        $context = context_course::instance($courseid);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/guest:config', $context)) {
            return NULL;
        }

        if ($DB->record_exists('enrol', array('courseid' => $courseid, 'enrol' => 'totara_learningplan'))) {
            return NULL;
        }

        return new moodle_url('/enrol/totara_learningplan/addinstance.php', array('sesskey' => sesskey(), 'id' => $courseid));
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass  $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        if (!totara_feature_visible('learningplans')) {
            // Allow deleting only when learning plans disabled so that they can get rid of preexisting
            // enrolments before the learning plans were disabled.
            $context = context_course::instance($instance->courseid);
            return has_capability('enrol/totara_learningplan:unenrol', $context);
        }

        return false;
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        if (!totara_feature_visible('learningplans')) {
            return null;
        }

        $fields = array('roleid' => $this->get_config('roleid', 0));
        return $this->add_instance($course, $fields);
    }

    /**
     * Add new instance of enrol_totara_learningplan plugin.
     * @param object $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = NULL) {

        return parent::add_instance($course);
    }

    /**
     * Enrols user onto a course in an approved plan.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $OUTPUT, $USER, $DB;

        $course = $DB->get_record('course', array('id' => $instance->courseid));
        if ($this->is_user_approved($instance->courseid)) {
            // get default roleid
            $instance->roleid = parent::get_config('roleid');
            parent::enrol_user($instance, $USER->id, $instance->roleid);

            totara_set_notification($OUTPUT->container(get_string('nowenrolled', 'enrol_totara_learningplan', $course->fullname), 'plan_box'), null, array('class' => 'notifysuccess'));
        } else {
            // this isn't an approved course in their learning plan or learning plan isn't approved
            $link = $OUTPUT->action_link(new moodle_url('/totara/plan/index.php', array('userid' => $USER->id)), get_string('learningplan', 'enrol_totara_learningplan'));
            $form = get_string('notpermitted', 'enrol_totara_learningplan', $link);

            if (!empty($course->guest)) {
                $destination = new moodle_url('/course/view.php', array('id' => $course->id));
                $form .= get_string('guestaccess', 'enrol_totara_learningplan', $destination);
            } else {
                $destination = new moodle_url("/course/index.php");
            }
            return $OUTPUT->container($form, 'plan_box plan_box_action') . $OUTPUT->continue_button($destination);
        }
    }

    /**
     * Attempt to automatically enrol current user in course without any interaction,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course enrol instance
     * @return bool false means not enrolled
     */
    public function try_autoenrol(stdClass $instance) {
        global $USER, $DB;

        if (!totara_feature_visible('learningplans')) {
            return false;
        }

        $course = $DB->get_record('course', array('id' => $instance->courseid));
        if ($this->is_user_approved($instance->courseid)) {
            // Get default roleid.
            $instance->roleid = parent::get_config('roleid');
            parent::enrol_user($instance, $USER->id, $instance->roleid);

            $viewurl = new moodle_url('/course/view.php', array('id' => $course->id));
            totara_set_notification(get_string('nowenrolled', 'enrol_totara_learningplan', $course->fullname), $viewurl->out(), array('class' => 'notifysuccess'));
        }
        return false;
    }

    /**
     * Check if the user has approval to enrol in the course
     *
     * @param int courseid the id of the course to check
     * @return bool
     */
    public function is_user_approved($courseid) {
        global $DB, $USER, $CFG;

        require_once("{$CFG->dirroot}/totara/plan/lib.php");

        $sql = "SELECT dpp.id
            FROM {dp_plan} dpp
            INNER JOIN {dp_plan_course_assign} dppca
            ON dppca.planid = dpp.id
            WHERE dppca.courseid = :courseid
            AND dpp.userid = :userid
            AND dppca.approved = :approved
            AND dpp.status = :status
            ";
        $params = array(
            'courseid' => $courseid,
            'userid' => $USER->id,
            'approved' => DP_APPROVAL_APPROVED,
            'status' => DP_PLAN_STATUS_APPROVED
        );

        if ($DB->record_exists_sql($sql, $params)) {
            return true;
        }
    }

    /**
     * Get the name of the enrolment plugin
     *
     * @return string
     */
    public function get_name() {
        return 'totara_learningplan';
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
        if ($this->allow_unenrol($instance) && has_capability("enrol/totara_learningplan:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
        }
        return $actions;
    }

    /**
     * Can current user disable learning plan enrolments in a course?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('moodle/course:enrolconfig', $context);
    }
}
