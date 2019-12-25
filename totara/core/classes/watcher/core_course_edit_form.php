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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\watcher;

use \core_course\hook\edit_form_definition_complete;
use \core_course\hook\edit_form_save_changes;
use \core_course\hook\edit_form_display;

/**
 * Class for managing Course edit form hooks.
 *
 * This class manages watchers for three hooks:
 *
 *    1. \core_course\hook\edit_form_definition_complete
 *        Gets called at the end of the course_edit_form definition.
 *        Through this watcher we can make any adjustments to the form definition we want, including adding
 *        Totara specific elements.
 *
 *    2. \core_course\hook\edit_form_save_changes
 *        Gets called after the form has been submit and the initial saving has been done, before the user is redirected.
 *        Through this watcher we can save any custom element data we need to.
 *
 *    3. \core_course\hook\edit_form_display
 *        Gets called immediately before the form is displayed and is used to initialise any required JS.
 *
 * @package totara_core\watcher
 */
class core_course_edit_form {

    /**
     * Hook watcher that extends the course edit form with Totara specific elements.
     *
     * @param edit_form_definition_complete $hook
     */
    public static function extend_form(edit_form_definition_complete $hook) {
        global $CFG;

        // Totara: extra includes
        require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
        require_once($CFG->dirroot.'/cohort/lib.php');
        require_once($CFG->dirroot.'/totara/cohort/lib.php');
        require_once($CFG->dirroot.'/totara/program/lib.php');

        $mform = $hook->form->_form;

        // First up visibility.
        // If audience visibility is enabled then we don't want to show the traditional visibility select.
        if (!empty($CFG->audiencevisibility)) {
            $mform->removeElement('visible');
        }

        // Add the Totara course type before startdate.
        self::add_course_type_controls_to_form($hook);

        // Add the course completion modifications to the form.
        self::add_course_completion_controls_to_form($hook);

        // Add course icons to the form.
        self::add_course_icons_controls_to_form($hook);

        // Add enrolled audiences controls to the form.
        self::add_enrolled_learning_controls_to_form($hook);

        // Add audience based visibility controls to form.
        self::add_audience_visibility_controls_to_form($hook);

        // When custom fields gets converted to use hooks this is where they would go for courses.
    }

    /**
     * Course edit for hook watcher that is called immediately before the edit form is display.
     *
     * This watcher is used to load any JS required by the form modifications made in the {@see self::extend_form()} watcher.
     *
     * @param edit_form_display $hook
     */
    public static function display_form(edit_form_display $hook) {
        // Set up JS
        local_js(array(
            TOTARA_JS_UI,
            TOTARA_JS_ICON_PREVIEW,
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW
        ));

        self::initialise_enrolled_learning_js($hook);
        self::initialise_audience_visibility_js($hook);
        self::initialise_course_icons_js($hook);
    }

    /**
     * Course edit form save watcher.
     *
     * This watcher is called when saving data from the form, allowing us to process any custom elements that need processing.
     *
     * @param edit_form_save_changes $hook
     */
    public static function save_form(edit_form_save_changes $hook) {
        global $CFG;

        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/cohort/lib.php');
        require_once($CFG->dirroot.'/totara/cohort/lib.php');
        require_once($CFG->dirroot.'/totara/program/lib.php');

        if (!$hook->iscreating) {
            // Ensure all completion records are created.
            completion_start_user_bulk($hook->courseid);
        }

        if (!empty($hook->data->image)) {
            course_save_image($hook->data, $hook->courseid);
        }

        $changedenrolledlearning = self::save_enrolled_learning_changes($hook);

        if (!empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $hook->context)) {
            // Update audience visibility.
            self::save_audience_visibility_changes($hook);

            // If enrolled learning changed and audience visibility is on and can be managed then update the audiences.
            if ($changedenrolledlearning) {
                require_once("$CFG->dirroot/enrol/cohort/locallib.php");
                enrol_cohort_sync(new \null_progress_trace(), $hook->courseid);
            }
        }
    }

    /**
     * Adds course type controls to the course definition.
     *
     * Adds the following controls:
     *    - coursetype (select)
     *
     * Coursetype is a column on the course table so there is no corresponding save code.
     *
     * @param edit_form_definition_complete $hook
     */
    protected static function add_course_type_controls_to_form(edit_form_definition_complete $hook) {
        global $TOTARA_COURSE_TYPES;

        $mform = $hook->form->_form;
        $coursetypeoptions = array();
        foreach($TOTARA_COURSE_TYPES as $k => $v) {
            $coursetypeoptions[$v] = get_string($k, 'totara_core');
        }
        $mform->insertElementBefore(
            $mform->createElement('select', 'coursetype', get_string('coursetype', 'totara_core'), $coursetypeoptions),
            'startdate'
        );
    }

    /**
     * Add course completion controls to the form definition.
     *
     * Adds the following two fields:
     *    - completionstartonenrol (advcheckbox)
     *    - completionprogressonview (advcheckbox)
     *
     * Both completionstartonenrol, and completionprogressonview are columns on the course table so there is no
     * corresponding save code.
     *
     * @param edit_form_definition_complete $hook
     */
    protected static function add_course_completion_controls_to_form(edit_form_definition_complete $hook) {

        $mform = $hook->form->_form;
        $courseconfig = get_config('moodlecourse');

        // For the next part we need the element AFTER 'Enable completion'.
        $beforename = null;
        $next = false;
        foreach (array_keys($mform->_elementIndex) as $elname) {
            if ($elname === 'enablecompletion') {
                $next = true;
            } else if ($next) {
                $beforename = $elname;
                break;
            }
        }

        // Now the progress view option.
        if (\completion_info::is_enabled_for_site()) {
            // Ok we know where to insert our new element, now create and insert it.
            $mform->insertElementBefore(
                $mform->createElement('advcheckbox', 'completionprogressonview', get_string('completionprogressonview', 'completion')),
                $beforename
            );
            $mform->setDefault('completionprogressonview', $courseconfig->completionprogressonview);
            $mform->disabledIf('completionprogressonview', 'enablecompletion', 'eq', 0);
            $mform->addHelpButton('completionprogressonview', 'completionprogressonview', 'completion');
        } else {
            // We're not worried about where we insert this, just do it at the end.
            $mform->addElement('hidden', 'completionprogressonview');
            $mform->setType('completionprogressonview', PARAM_INT);
            $mform->setDefault('completionprogressonview', 0);
        }

        // Completion starts on enrol option is deprecated, setting as hidden field to remove the option from settings form.
        $mform->addElement('hidden', 'completionstartonenrol');
        $mform->setType('completionstartonenrol', PARAM_INT);
        $mform->setDefault('completionstartonenrol', 0);
    }

    /**
     * Add course icon selection controls to the course definition.
     *
     * Adds the following fields to the form:
     *    - iconheader (iconheader)
     *    - icon (hidden)
     *    - currenticon (static)
     *    - image (filepicker for background image in the Grid Catalogue)
     *
     * JavaScript is required for this element and is loaded by (@see self::initialise_course_icons_js()}
     * Icon is a column on the course table so there is no corresponding save code.
     *
     * @param edit_form_definition_complete $hook
     */
    protected static function add_course_icons_controls_to_form(edit_form_definition_complete $hook) {
        global $CFG;

        $mform = $hook->form->_form;
        $course = $hook->customdata['course'];
        $nojs = (isset($hook->customdata['nojs'])) ? $hook->customdata['nojs'] : 0 ;

        // For the next part we need these elements at the start of "Appearance section".
        $beforename = 'lang';
        $courseicon = isset($course->icon) ? $course->icon : 'default';
        $iconhtml = totara_icon_picker_preview('course', $courseicon);

        if ($nojs == 1) {
            $mform->insertElementBefore(
                $mform->createElement('static', 'currenticon', get_string('currenticon', 'totara_core'), $iconhtml),
                $beforename
            );
            $path = $CFG->dirroot . '/totara/core/pix/courseicons';
            $replace = array(
                '.png' => '',
                '_' => ' ',
                '-' => ' '
            );
            $icons = array();
            foreach (scandir($path) as $icon) {
                if ($icon == '.' || $icon == '..') { continue;}
                $iconfile = str_replace('.png', '', $icon);
                $iconname = strtr($icon, $replace);
                $icons[$iconfile] = ucwords($iconname);
            }
            $mform->insertElementBefore(
                $mform->createElement('select', 'icon', get_string('icon', 'totara_core'), $icons),
                $beforename
            );
            $mform->setDefault('icon', $courseicon);
            $mform->setType('icon', PARAM_TEXT);
        } else {
            $buttonhtml = \html_writer::empty_tag('input', array(
                'type' => 'button',
                'value' => get_string('chooseicon', 'totara_program'),
                'id' => 'show-icon-dialog'
            ));
            // Hidden inputs can be safely added at the end.
            $mform->addElement('hidden', 'icon');
            $mform->setType('icon', PARAM_TEXT);
            $mform->insertElementBefore(
                $mform->createElement('static', 'currenticon', get_string('currenticon', 'totara_core'), $iconhtml . $buttonhtml),
                $beforename
            );
        }

        // Add background image element for the Grid Catalogue.
        $options = ['accept_types' => 'web_image', 'maxfiles' => 1, 'subdirs' => false];
        $mform->insertElementBefore(
            $mform->createElement('filemanager', 'image', get_string('courseimage'), null, $options),
            $beforename
        );
        $mform->addHelpButton('image', 'courseimage');

        // NOTE: this is a nasty hack, but it should work consistently in legacy Moodle forms for now...
        $draftitemid = file_get_submitted_draft_itemid('image');
        if (!empty($course->id)) {
            $context = \context_course::instance($course->id);
            file_prepare_draft_area($draftitemid, $context->id, 'course', 'images', 0, $options);
        }
        $mform->setDefault('image', $draftitemid);
    }

    /**
     * Adds the enrolled learning controls to the edit form.
     *
     * These controls allow the user to select one or more cohorts to enrol in the course automatically.
     *
     * Adds the following elements:
     *    - enrolledcohortshdr (header)
     *    - cohortsenrolled (hidden)
     *    - cohortsaddenrolled (button)
     *
     * JavaScript for these elements is loaded via {@see self::initialise_enrolled_learning_js()}
     * Data from the elements is saved via {@see self::save_enrolled_learning_changes()}
     *
     * @param edit_form_definition_complete $hook
     * @throws \coding_exception
     */
    protected static function add_enrolled_learning_controls_to_form(edit_form_definition_complete $hook) {
        global $OUTPUT;

        if (!enrol_is_enabled('cohort')) {
            // Nothing to do here, cohort enrolment is not available.
            return;
        }

        $mform = $hook->form->_form;
        $course = $hook->customdata['course'];
        if (!empty($course->id)) {
            $coursecontext = \context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = \context_coursecat::instance($hook->customdata['category']->id);;
        }

        if (!has_all_capabilities(['moodle/course:enrolconfig', 'enrol/cohort:config'], $context)) {
            // Nothing to do here, the user cannot manage cohort enrolments.
            return;
        }

        $beforename = 'groups';
        $mform->insertElementBefore(
            $mform->createElement('header','enrolledcohortshdr', get_string('enrolledcohorts', 'totara_cohort')),
            $beforename
        );

        // Audience deletion warning message.
        $warning = $OUTPUT->notification(get_string('cohortdeletionwarning', 'totara_cohort'), 'warning');

        /** @var \HTML_QuickForm_html $cohortdeletionwarning */
        $cohortdeletionwarning = $mform->createElement('html', $warning);
        $cohortdeletionwarning->setName('cohortdeletionwarning');
        $mform->insertElementBefore($cohortdeletionwarning, $beforename);

        if (empty($course->id)) {
            $cohorts = '';
        } else {
            $cohorts = totara_cohort_get_course_cohorts($course->id, null, 'c.id');
            $cohorts = !empty($cohorts) ? implode(',', array_keys($cohorts)) : '';
        }

        $mform->addElement('hidden', 'cohortsenrolled', $cohorts);
        $mform->setType('cohortsenrolled', PARAM_SEQUENCE);
        $cohortsclass = new \totara_cohort_course_cohorts(COHORT_ASSN_VALUE_ENROLLED);
        $cohortsclass->build_table(!empty($course->id) ? $course->id : 0);

        /** @var \HTML_QuickForm_html $cohorttable */
        $cohorttable = $mform->createElement('html', $cohortsclass->display(true));
        $cohorttable->setName('cohorttable');
        $mform->insertElementBefore($cohorttable, $beforename);

        $mform->insertElementBefore(
            $mform->createElement('button', 'cohortsaddenrolled', get_string('cohortsaddenrolled', 'totara_cohort')),
            $beforename
        );
        $mform->setExpanded('enrolledcohortshdr');
    }

    /**
     * Adds audience visibility controls to the form if audience visibility has been enabled.
     *
     * Adds the following elements:
     *    - visiblecohortshdr (header)
     *    - audiencevisible (select)
     *    - cohortsvisible (hidden)
     *    - cohortsaddvisible (button)
     *
     * JavaScript for these elements is loaded via {@see self::initialise_audience_visibility_js()}
     * Data from the elements is saved via {@see self::save_audience_visibility_changes()}
     *
     * @param edit_form_definition_complete $hook
     */
    protected static function add_audience_visibility_controls_to_form(edit_form_definition_complete $hook) {
        global $CFG, $COHORT_VISIBILITY;

        if (empty($CFG->audiencevisibility)) {
            // Nothing to do here, audience visibility is not enabled.
            return;
        }

        $courseconfig = get_config('moodlecourse');
        $mform = $hook->form->_form;
        $course = $hook->customdata['course'];
        if (!empty($course->id)) {
            $coursecontext = \context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = \context_coursecat::instance($hook->customdata['category']->id);;
        }

        if (!has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
            // Nothing to do here the user cannot manage visibility in this context.
            return;
        }

        // Only show the Audiences Visibility functionality to users with the appropriate permissions.
        $beforename = 'groups';

        $mform->insertElementBefore(
            $mform->createElement('header', 'visiblecohortshdr', get_string('audiencevisibility', 'totara_cohort')),
            $beforename
        );
        $mform->insertElementBefore(
            $mform->createElement('select', 'audiencevisible', get_string('visibility', 'totara_cohort'), $COHORT_VISIBILITY),
            $beforename
        );
        $mform->addHelpButton('audiencevisible', 'visiblelearning', 'totara_cohort');

        if (empty($course->id)) {
            $mform->setDefault('audiencevisible', $courseconfig->visiblelearning);
            $cohorts = '';
        } else {
            $cohorts = totara_cohort_get_visible_learning($course->id);
            $cohorts = !empty($cohorts) ? implode(',', array_keys($cohorts)) : '';
        }

        $mform->addElement('hidden', 'cohortsvisible', $cohorts);
        $mform->setType('cohortsvisible', PARAM_SEQUENCE);
        $cohortsclass = new \totara_cohort_visible_learning_cohorts();
        $instanceid = !empty($course->id) ? $course->id : 0;
        $instancetype = COHORT_ASSN_ITEMTYPE_COURSE;
        $cohortsclass->build_visible_learning_table($instanceid, $instancetype);

        /** @var \HTML_QuickForm_html $cohortvisibilityelement */
        $cohortvisibilityelement = $mform->createElement('html', $cohortsclass->display(true, 'visible'));
        $cohortvisibilityelement->setName('cohortvisibility');
        $mform->insertElementBefore($cohortvisibilityelement, $beforename);

        $mform->insertElementBefore(
            $mform->createElement('button', 'cohortsaddvisible', get_string('cohortsaddvisible', 'totara_cohort')),
            $beforename
        );
        $mform->setExpanded('visiblecohortshdr');
    }

    /**
     * Initialise JS for the enrolled learning elements.
     *
     * Elements are initialised by {@see self::add_enrolled_learning_controls_to_form()}.
     * Data is saved by {@see self::save_enrolled_learning_changes()}.
     *
     * @param edit_form_display $hook
     */
    protected static function initialise_enrolled_learning_js(edit_form_display $hook) {
        global $PAGE;

        $course = $hook->customdata['course'];
        if (empty($course->id)) {
            $instancetype = COHORT_ASSN_ITEMTYPE_CATEGORY;
            $instanceid = $hook->customdata['category']->id;
            $enrolledselected = '';
        } else {
            $instancetype = COHORT_ASSN_ITEMTYPE_COURSE;
            $instanceid = $course->id;
            $enrolledselected = totara_cohort_get_course_cohorts($course->id, null, 'c.id');
            $enrolledselected = !empty($enrolledselected) ? implode(',', array_keys($enrolledselected)) : '';
        }

        $PAGE->requires->strings_for_js(array('coursecohortsenrolled'), 'totara_cohort');
        $jsmodule = array(
            'name' => 'totara_cohortdialog',
            'fullpath' => '/totara/cohort/dialog/coursecohort.js',
            'requires' => array('json'));
        $args = array(
            'args'=>'{"enrolledselected":"' . $enrolledselected . '",'.
                '"COHORT_ASSN_VALUE_ENROLLED":' . COHORT_ASSN_VALUE_ENROLLED .
                ', "instancetype":"' . $instancetype . '", "instanceid":"' . $instanceid . '"}'
        );
        $PAGE->requires->js_init_call('M.totara_coursecohort.init', $args, true, $jsmodule);
    }

    /**
     * Initialise JS for audience visibility controls.
     *
     * Elements are initialised by {@see self::add_audience_visibility_controls_to_form()}.
     * Data is saved by {@see self::save_audience_visibility_changes()}.
     *
     * @param edit_form_display $hook\
     */
    protected static function initialise_audience_visibility_js(edit_form_display $hook) {
        global $CFG, $PAGE;

        if (empty($CFG->audiencevisibility)) {
            // Audience visibility is not enabled - nothing to do.
            return;
        }

        $course = $hook->customdata['course'];

        if (empty($course->id)) {
            $instancetype = COHORT_ASSN_ITEMTYPE_CATEGORY;
            $instanceid = $hook->customdata['category']->id;
            $visibleselected = '';
        } else {
            $instancetype = COHORT_ASSN_ITEMTYPE_COURSE;
            $instanceid = $course->id;
            $visibleselected = totara_cohort_get_visible_learning($course->id);
            $visibleselected = !empty($visibleselected) ? implode(',', array_keys($visibleselected)) : '';
        }

        $PAGE->requires->strings_for_js(array('coursecohortsvisible'), 'totara_cohort');
        $jsmodule = array(
            'name' => 'totara_visiblecohort',
            'fullpath' => '/totara/cohort/dialog/visiblecohort.js',
            'requires' => array('json'));
        $args = array(
            'args'=>'{"visibleselected":"' . $visibleselected .
                '", "type":"course", "instancetype":"' . $instancetype .
                '", "instanceid":"' . $instanceid . '"}'
        );
        $PAGE->requires->js_init_call('M.totara_visiblecohort.init', $args, true, $jsmodule);
    }

    /**
     * Initialises JS for course icons.
     *
     * Elements are initialised by {@see self::add_course_icons_controls_to_form()}.
     * Data is saved automatically.
     *
     * @param edit_form_display $hook
     */
    protected static function initialise_course_icons_js(edit_form_display $hook) {
        global $PAGE;

        $course = $hook->customdata['course'];

        // Icon picker.
        $PAGE->requires->string_for_js('chooseicon', 'totara_program');
        $iconjsmodule = array(
            'name' => 'totara_iconpicker',
            'fullpath' => '/totara/core/js/icon.picker.js',
            'requires' => array('json'));
        $currenticon = isset($course->icon) ? $course->icon : 'default';
        $iconargs = array(
            'args' => '{"selected_icon":"' . $currenticon . '","type":"course"}'
        );
        $PAGE->requires->js_init_call('M.totara_iconpicker.init', $iconargs, false, $iconjsmodule);
    }

    /**
     * Saves changes to enrolled learning.
     *
     * @param edit_form_save_changes $hook
     * @return bool
     */
    protected static function save_enrolled_learning_changes(edit_form_save_changes $hook) {
        global $DB;

        if (!enrol_is_enabled('cohort')) {
            // Nothing to do here, we can't use cohort enrolment.
            return false;
        }

        if (!has_all_capabilities(['moodle/course:enrolconfig', 'enrol/cohort:config'], $hook->context)) {
            // Nothing to do here, the user can't config enrolments.
            return false;
        }

        $data = $hook->data;
        $courseid = $hook->courseid;
        $changesmade = false;

        $currentcohorts = totara_cohort_get_course_cohorts($courseid, null, 'c.id, e.id AS associd');
        $currentcohorts = !empty($currentcohorts) ? $currentcohorts : array();
        $newcohorts = !empty($data->cohortsenrolled) ? explode(',', $data->cohortsenrolled) : array();

        if ($todelete = array_diff(array_keys($currentcohorts), $newcohorts)) {
            ignore_user_abort(true);
            // Delete removed cohorts
            foreach ($todelete as $cohortid) {
                totara_cohort_delete_association($cohortid, $currentcohorts[$cohortid]->associd, COHORT_ASSN_ITEMTYPE_COURSE);
            }
            $changesmade = true;
        }

        if ($newcohorts = array_diff($newcohorts, array_keys($currentcohorts))) {
            // Add new cohort associations
            foreach ($newcohorts as $cohortid) {
                $cohort = $DB->get_record('cohort', array('id' => $cohortid));
                if (!$cohort) {
                    continue;
                }
                if (!has_capability('moodle/cohort:view', \context::instance_by_id($cohort->contextid))) {
                    continue;
                }
                totara_cohort_add_association($cohortid, $courseid, COHORT_ASSN_ITEMTYPE_COURSE);
            }
            $changesmade = true;
        }
        \cache_helper::purge_by_event('changesincourse');
        return $changesmade;
    }

    /**
     * Saves changes to audience visibility.
     *
     * @param edit_form_save_changes $hook
     * @return bool
     */
    protected static function save_audience_visibility_changes(edit_form_save_changes $hook) {
        global $CFG, $DB;

        if (empty($CFG->audiencevisibility)) {
            // Nothing to do here, audience visibility is not enabled.
            return false;
        }

        if (!has_capability('totara/coursecatalog:manageaudiencevisibility', $hook->context)) {
            // Nothing to do here, the user does not have permission to change this.
        }

        $data = $hook->data;
        $courseid = $hook->courseid;
        $changesmade = false;

        $visiblecohorts = totara_cohort_get_visible_learning($courseid);
        $visiblecohorts = !empty($visiblecohorts) ? $visiblecohorts : array();
        $newvisible = !empty($data->cohortsvisible) ? explode(',', $data->cohortsvisible) : array();
        if ($todelete = array_diff(array_keys($visiblecohorts), $newvisible)) {
            ignore_user_abort(true);
            // Delete removed cohorts.
            foreach ($todelete as $cohortid) {
                totara_cohort_delete_association($cohortid, $visiblecohorts[$cohortid]->associd,
                    COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            }
            $changesmade = true;
        }

        if ($newvisible = array_diff($newvisible, array_keys($visiblecohorts))) {
            // Add new cohort associations.
            foreach ($newvisible as $cohortid) {
                $cohort = $DB->get_record('cohort', array('id' => $cohortid));
                if (!$cohort) {
                    continue;
                }
                if (!has_capability('moodle/cohort:view', \context::instance_by_id($cohort->contextid))) {
                    continue;
                }
                totara_cohort_add_association($cohortid, $courseid, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            }
            $changesmade = true;
        }
        \cache_helper::purge_by_event('changesincourse');
        return $changesmade;
    }
}
