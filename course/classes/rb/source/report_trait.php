<?php
/*
 * This file is part of Totara Learn
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package core_course
 */

namespace core_course\rb\source;

use moodleform, HTML_QuickForm_static, HTML_QuickForm_button, MoodleQuickForm_hidden;
use context_course, coursecat_helper, completion_completion, course_in_list, moodle_url;
use report_builder_course_expand_form;

defined('MOODLE_INTERNAL') || die();

trait report_trait {

    /**
     * Adds some common course info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'course' table
     *
     * @return True
     */
    protected function add_core_course_columns(&$columnoptions, $join='course') {
        global $DB;

        $columnoptions[] = new \rb_column_option(
            'course',
            'fullname',
            get_string('coursename', 'totara_reportbuilder'),
            "$join.fullname",
            array('joins' => $join,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'courselink',
            get_string('coursenamelinked', 'totara_reportbuilder'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'course_link',
                'defaultheading' => get_string('coursename', 'totara_reportbuilder'),
                'extrafields' => array('course_id' => "$join.id",
                                       'course_visible' => "$join.visible",
                                       'course_audiencevisible' => "$join.audiencevisible")
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'courseexpandlink',
            get_string('courseexpandlink', 'totara_reportbuilder'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'course_expand',
                'defaultheading' => get_string('coursename', 'totara_reportbuilder'),
                'extrafields' => array(
                    'course_id' => "$join.id",
                    'course_visible' => "$join.visible",
                    'course_audiencevisible' => "$join.audiencevisible"
                )
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'courselinkicon',
            get_string('coursenamelinkedicon', 'totara_reportbuilder'),
            "$join.fullname",
            array(
                'joins' => $join,
                'displayfunc' => 'course_icon_link',
                'defaultheading' => get_string('coursename', 'totara_reportbuilder'),
                'extrafields' => array(
                    'course_id' => "$join.id",
                    'course_icon' => "$join.icon",
                    'course_visible' => "$join.visible",
                    'course_audiencevisible' => "$join.audiencevisible"
                )
            )
        );
        $audvisibility = get_config(null, 'audiencevisibility');
        if (empty($audvisibility)) {
            $coursevisiblestring = get_string('coursevisible', 'totara_reportbuilder');
            $audvisibilitystring = get_string('audiencevisibilitydisabled', 'totara_reportbuilder');
        } else {
            $coursevisiblestring = get_string('coursevisibledisabled', 'totara_reportbuilder');
            $audvisibilitystring = get_string('audiencevisibility', 'totara_reportbuilder');
        }
        $columnoptions[] = new \rb_column_option(
            'course',
            'visible',
            $coursevisiblestring,
            "$join.visible",
            array(
                'joins' => $join,
                'displayfunc' => 'yes_or_no'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'audvis',
            $audvisibilitystring,
            "$join.audiencevisible",
            array(
                'joins' => $join,
                'displayfunc' => 'cohort_visibility'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'icon',
            get_string('courseicon', 'totara_reportbuilder'),
            "$join.icon",
            array(
                'joins' => $join,
                'displayfunc' => 'course_icon',
                'defaultheading' => get_string('courseicon', 'totara_reportbuilder'),
                'extrafields' => array(
                    'course_name' => "$join.fullname",
                    'course_id' => "$join.id",
                )
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'shortname',
            get_string('courseshortname', 'totara_reportbuilder'),
            "$join.shortname",
            array('joins' => $join,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'plaintext')
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'idnumber',
            get_string('courseidnumber', 'totara_reportbuilder'),
            "$join.idnumber",
            array('joins' => $join,
                  'displayfunc' => 'plaintext',
                  'dbdatatype' => 'char',
                  'outputformat' => 'text')
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'id',
            get_string('courseid', 'totara_reportbuilder'),
            "$join.id",
            array('joins' => $join,
                  'displayfunc' => 'integer')
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'timecreated',
            get_string('coursedatecreated', 'totara_reportbuilder'),
            "$join.timecreated",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'startdate',
            get_string('coursestartdate', 'totara_reportbuilder'),
            "$join.startdate",
            array(
                'joins' => $join,
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'name_and_summary',
            get_string('coursenameandsummary', 'totara_reportbuilder'),
            // Case used to merge even if one value is null.
            "CASE WHEN $join.fullname IS NULL THEN $join.summary
                WHEN $join.summary IS NULL THEN $join.fullname
                ELSE " . $DB->sql_concat("$join.fullname", "'" . \html_writer::empty_tag('br') . "'",
                    "$join.summary") . ' END',
            array(
                'joins' => $join,
                'displayfunc' => 'editor_textarea',
                'extrafields' => array(
                    'filearea' => '\'summary\'',
                    'component' => '\'course\'',
                    'context' => '\'context_course\'',
                    'recordid' => "$join.id"
                )
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'summary',
            get_string('coursesummary', 'totara_reportbuilder'),
            "$join.summary",
            array(
                'joins' => $join,
                'displayfunc' => 'editor_textarea',
                'extrafields' => array(
                    'format' => "$join.summaryformat",
                    'filearea' => '\'summary\'',
                    'component' => '\'course\'',
                    'context' => '\'context_course\'',
                    'recordid' => "$join.id"
                ),
                'dbdatatype' => 'text',
                'outputformat' => 'text'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'coursetypeicon',
            get_string('coursetypeicon', 'totara_reportbuilder'),
            "$join.coursetype",
            array(
                'joins' => $join,
                'displayfunc' => 'course_type_icon',
                'defaultheading' => get_string('coursetypeicon', 'totara_reportbuilder'),
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course',
            'coursetype',
            get_string('coursetype', 'totara_reportbuilder'),
            "$join.coursetype",
            array(
                'joins' => $join,
                'displayfunc' => 'course_type',
                'defaultheading' => get_string('coursetype', 'totara_reportbuilder'),
            )
        );
        // add language option
        $columnoptions[] = new \rb_column_option(
            'course',
            'language',
            get_string('courselanguage', 'totara_reportbuilder'),
            "$join.lang",
            array(
                'joins' => $join,
                'displayfunc' => 'language_code'
            )
        );

        return true;
    }

    /**
     * Adds some common course filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_core_course_filters(&$filteroptions) {
        $filteroptions[] = new \rb_filter_option(
            'course',
            'fullname',
            get_string('coursename', 'totara_reportbuilder'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'shortname',
            get_string('courseshortname', 'totara_reportbuilder'),
            'text'
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'idnumber',
            get_string('courseidnumber', 'totara_reportbuilder'),
            'text'
        );
        $audvisibility = get_config(null, 'audiencevisibility');
        if (empty($audvisibility)) {
            $coursevisiblestring = get_string('coursevisible', 'totara_reportbuilder');
            $audvisiblilitystring = get_string('audiencevisibilitydisabled', 'totara_reportbuilder');
        } else {
            $coursevisiblestring = get_string('coursevisibledisabled', 'totara_reportbuilder');
            $audvisiblilitystring = get_string('audiencevisibility', 'totara_reportbuilder');
        }
        $filteroptions[] = new \rb_filter_option(
            'course',
            'visible',
            $coursevisiblestring,
            'select',
            array(
                'selectchoices' => array(0 => get_string('no'), 1 => get_string('yes')),
                'simplemode' => true
            )
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'audvis',
            $audvisiblilitystring,
            'select',
            array(
                'selectchoices' => array(
                    COHORT_VISIBLE_NOUSERS => get_string('visiblenousers', 'totara_cohort'),
                    COHORT_VISIBLE_ENROLLED => get_string('visibleenrolled', 'totara_cohort'),
                    COHORT_VISIBLE_AUDIENCE => get_string('visibleaudience', 'totara_cohort'),
                    COHORT_VISIBLE_ALL => get_string('visibleall', 'totara_cohort')),
                'simplemode' => true
            )
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'timecreated',
            get_string('coursedatecreated', 'totara_reportbuilder'),
            'date',
            array('castdate' => true)
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'startdate',
            get_string('coursestartdate', 'totara_reportbuilder'),
            'date',
            array('castdate' => true)
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'name_and_summary',
            get_string('coursenameandsummary', 'totara_reportbuilder'),
            'textarea'
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'coursetype',
            get_string('coursetype', 'totara_reportbuilder'),
            'multicheck',
            array(
                'selectfunc' => 'course_types',
                'simplemode' => true,
                'showcounts' => array(
                        'joins' => array("LEFT JOIN {course} coursetype_filter ON base.id = coursetype_filter.id"),
                        'dataalias' => 'coursetype_filter',
                        'datafield' => 'coursetype')
            )
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'language',
            get_string('courselanguage', 'totara_reportbuilder'),
            'select',
            array(
                'selectfunc' => 'course_languages',
                'attributes' => \rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new \rb_filter_option(
            'course',
            'id',
            get_string('coursemultiitem', 'totara_reportbuilder'),
            'course_multi'
        );
        return true;
    }

    /**
     * Adds the course table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'course id' field
     * @param string $field Name of course id field to join on
     * @param string $jointype Type of Join (INNER, LEFT, RIGHT)
     * @return boolean
     */
    protected function add_core_course_tables(&$joinlist, $join, $field, $jointype = 'LEFT') {

        $joinlist[] = new \rb_join(
            'course',
            $jointype,
            '{course}',
            "course.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        return true;
    }

    /**
     * Adds the course_category table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include course_category
     * @param string $join Name of the join that provides the 'course' table
     * @param string $field Name of category id field to join on
     * @return boolean True
     */
    protected function add_core_course_category_tables(&$joinlist,
        $join, $field) {

        $joinlist[] = new \rb_join(
            'course_category',
            'LEFT',
            '{course_categories}',
            "course_category.id = $join.$field",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $join
        );

        return true;
    }


    /**
     * Adds some common course category info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $catjoin Name of the join that provides the
     *                        'course_categories' table
     * @param string $coursejoin Name of the join that provides the
     *                           'course' table
     * @return True
     */
    protected function add_core_course_category_columns(&$columnoptions,
        $catjoin='course_category', $coursejoin='course', $column='coursecount') {
        $columnoptions[] = new \rb_column_option(
            'course_category',
            'name',
            get_string('coursecategory', 'totara_reportbuilder'),
            "$catjoin.name",
            array('joins' => $catjoin,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );
        $columnoptions[] = new \rb_column_option(
            'course_category',
            'namelink',
            get_string('coursecategorylinked', 'totara_reportbuilder'),
            "$catjoin.name",
            array(
                'joins' => $catjoin,
                'displayfunc' => 'course_category_link',
                'defaultheading' => get_string('category', 'totara_reportbuilder'),
                'extrafields' => array('cat_id' => "$catjoin.id",
                                        'cat_visible' => "$catjoin.visible",
                                        $column => "{$catjoin}.{$column}")
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course_category',
            'idnumber',
            get_string('coursecategoryidnumber', 'totara_reportbuilder'),
            "$catjoin.idnumber",
            array(
                'joins' => $catjoin,
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'char',
                'outputformat' => 'text'
            )
        );
        $columnoptions[] = new \rb_column_option(
            'course_category',
            'id',
            get_string('coursecategoryid', 'totara_reportbuilder'),
            "$coursejoin.category",
            array('joins' => $coursejoin,
                  'displayfunc' => 'integer')
        );
        return true;
    }


    /**
     * Adds some common course category filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_core_course_category_filters(&$filteroptions) {
        $filteroptions[] = new \rb_filter_option(
            'course_category',
            'id',
            get_string('coursecategory', 'totara_reportbuilder'),
            'select',
            array(
                'selectfunc' => 'course_categories_list',
                'attributes' => \rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new \rb_filter_option(
            'course_category',
            'path',
            get_string('coursecategorymultichoice', 'totara_reportbuilder'),
            'category',
            array(),
            'course_category.path',
            'course_category'
        );
        return true;
    }


    /**
     * Expanding content to display when clicking a course.
     * Will be placed inside a table cell which is the width of the table.
     * Call required_param to get any param data that is needed.
     * Make sure to check that the data requested is permitted for the viewer.
     *
     * @return string
     */
    public function rb_expand_course_details() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');
        require_once($CFG->dirroot . '/course/renderer.php');
        require_once($CFG->dirroot . '/lib/coursecatlib.php');
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $courseid = required_param('expandcourseid', PARAM_INT);
        $userid = $USER->id;

        if (!totara_course_is_viewable($courseid)) {
            ajax_result(false, get_string('coursehidden'));
            exit();
        }

        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        $chelper = new coursecat_helper();

        $formdata = array(
            // The following are required.
            'summary' => $chelper->get_course_formatted_summary(new course_in_list($course)),
            'status' => null,
            'courseid' => $courseid,

            // The following are optional, and depend upon state.
            'inlineenrolmentelements' => null,
            'enroltype' => null,
            'progress' => null,
            'enddate' => null,
            'grade' => null,
            'action' => null,
            'url' => null,
        );

        $coursecontext = context_course::instance($course->id, MUST_EXIST);
        $enrolled = is_enrolled($coursecontext, $userid);

        $inlineenrolments = array();
        if ($enrolled) {
            $ccompl = new completion_completion(array('userid' => $userid, 'course' => $courseid));
            $complete = $ccompl->is_complete();
            if ($complete) {
                $coursecompletion = $DB->get_record('course_completions', array('userid' => $userid, 'course' => $courseid));
                $coursecompletedon = userdate($coursecompletion->timecompleted, get_string('strfdateshortmonth', 'langconfig'));

                $formdata['status'] = get_string('coursestatuscomplete', 'totara_reportbuilder');
                $formdata['progress'] = get_string('coursecompletedon', 'totara_reportbuilder', $coursecompletedon);

                if ($course->showgrades && has_capability('moodle/grade:view', $coursecontext, $userid)) {
                    $sql = "SELECT gg.*
                          FROM {grade_grades} gg
                          JOIN {grade_items} gi
                            ON gg.itemid = gi.id
                          WHERE gg.userid = ?
                            AND gi.courseid = ?
                            AND gi.itemtype = 'course'";
                    $grade = $DB->get_record_sql($sql, array($userid, $courseid));
                    if ($grade) {
                        if (!isset($grade->finalgrade)) {
                            $formdata['grade'] = '-';
                        } else {
                            $formdata['grade'] = get_string('xpercent', 'totara_core', $grade->finalgrade);
                        }
                    }
                }
            } else {
                $formdata['status'] = get_string('coursestatusenrolled', 'totara_reportbuilder');

                list($statusdpsql, $statusdpparams) = $this->get_dp_status_sql($userid, $courseid);
                $statusdp = $DB->get_record_sql($statusdpsql, $statusdpparams);
                $progress = totara_display_course_progress_bar($userid, $courseid,
                    $statusdp->course_completion_statusandapproval);
                // Highlight if the item has not yet been approved.
                if ($statusdp->approved == DP_APPROVAL_UNAPPROVED || $statusdp->approved == DP_APPROVAL_REQUESTED) {
                    $progress .= dp_get_approval_status_from_code($statusdp->approved);
                }
                $formdata['progress'] = $progress;

                // Course not finished, so no end date for course.
                $formdata['enddate'] = '';
            }
            $formdata['url'] = new moodle_url('/course/view.php', array('id' => $courseid));
            $formdata['action'] =  get_string('launchcourse', 'totara_program');
        } else {
            $formdata['status'] = get_string('coursestatusnotenrolled', 'totara_reportbuilder');

            $instances = enrol_get_instances($courseid, true);
            $plugins = enrol_get_plugins(true);

            $enrolmethodlist = array();
            foreach ($instances as $instance) {
                if (!isset($plugins[$instance->enrol])) {
                    continue;
                }
                $plugin = $plugins[$instance->enrol];
                if (enrol_is_enabled($instance->enrol)) {
                    $enrolmethodlist[] = $plugin->get_instance_name($instance);
                    // If the enrolment plugin has a course_expand_hook then add to a list to process.
                    if (method_exists($plugin, 'course_expand_get_form_hook')
                        && method_exists($plugin, 'course_expand_enrol_hook')) {
                        $enrolment = array ('plugin' => $plugin, 'instance' => $instance);
                        $inlineenrolments[$instance->id] = (object) $enrolment;
                    }
                }
            }
            $enrolmethodstr = implode(', ', $enrolmethodlist);
            $realuser = \core\session\manager::get_realuser();

            $inlineenrolmentelements = $this->get_inline_enrolment_elements($inlineenrolments);
            $formdata['inlineenrolmentelements'] = $inlineenrolmentelements;
            $formdata['enroltype'] = $enrolmethodstr;

            if (is_viewing($coursecontext, $realuser->id) || is_siteadmin($realuser->id)) {
                $formdata['action'] = get_string('viewcourse', 'totara_program');
                $formdata['url'] = new moodle_url('/course/view.php', array('id' => $courseid));
            }
        }

        $mform = new report_builder_course_expand_form(null, $formdata);

        if (!empty($inlineenrolments)) {
            $this->process_enrolments($mform, $inlineenrolments);
        }

        return $mform->render();
    }

    /**
     * @param $inlineenrolments array of objects containing matching instance/plugin pairs
     * @return array of form elements
     */
    private function get_inline_enrolment_elements(array $inlineenrolments) {
        global $CFG;

        require_once($CFG->dirroot . '/lib/pear/HTML/QuickForm/button.php');
        require_once($CFG->dirroot . '/lib/pear/HTML/QuickForm/static.php');

        $retval = array();
        foreach ($inlineenrolments as $inlineenrolment) {
            $instance = $inlineenrolment->instance;
            $plugin = $inlineenrolment->plugin;
            $enrolform = $plugin->course_expand_get_form_hook($instance);

            $nameprefix = 'instanceid_' . $instance->id . '_';

            // Currently, course_expand_get_form_hook check if the user can self enrol before creating the form, if not, it will
            // return the result of the can_self_enrol function which could be false or a string.
            if (!$enrolform || is_string($enrolform)) {
                $retval[] = new HTML_QuickForm_static(null, null, $enrolform);
                continue;
            }

            if ($enrolform instanceof moodleform) {
                foreach ($enrolform->_form->_elements as $element) {
                    if ($element->_type == 'button' || $element->_type == 'submit') {
                        continue;
                    } else if ($element->_type == 'group') {
                        $newelements = array();
                        foreach ($element->getElements() as $subelement) {
                            if ($subelement->_type == 'button' || $subelement->_type == 'submit') {
                                continue;
                            }
                            $elementname = $subelement->getName();
                            $newelement  = $nameprefix . $elementname;
                            $subelement->setName($newelement);
                            if (!empty($enrolform->_form->_types[$elementname]) && $subelement instanceof MoodleQuickForm_hidden) {
                                $subelement->setType($newelement, $enrolform->_form->_types[$elementname]);
                            }
                            $newelements[] = $subelement;
                        }
                        if (count($newelements)>0) {
                            $element->setElements($newelements);
                            $retval[] = $element;
                        }
                    } else {
                        $elementname = $element->getName();
                        $newelement  = $nameprefix . $elementname;
                        $element->setName($newelement);
                        if (!empty($enrolform->_form->_types[$elementname]) && $element instanceof MoodleQuickForm_hidden) {
                            $element->setType($newelement, $enrolform->_form->_types[$elementname]);
                        }
                        $retval[] = $element;
                    }
                }
            }

            if (count($inlineenrolments) > 1) {
                $enrollabel = get_string('enrolusing', 'totara_reportbuilder', $plugin->get_instance_name($instance->id));
            } else {
                $enrollabel = get_string('enrol', 'totara_reportbuilder');
            }
            $name = $instance->id;

            $retval[] = new HTML_QuickForm_button($name, $enrollabel, array('class' => 'expandenrol'));
        }
        return $retval;
    }

    /**
     * @param $mform
     * @param $inlineenrolments
     */
    private function process_enrolments($mform, $inlineenrolments) {
        global $CFG;

        if ($formdata = $mform->get_data()) {
            $submittedinstance = required_param('instancesubmitted', PARAM_INT);
            $inlineenrolment = $inlineenrolments[$submittedinstance];
            $instance = $inlineenrolment->instance;
            $plugin = $inlineenrolment->plugin;
            $nameprefix = 'instanceid_' . $instance->id . '_';
            $nameprefixlength = strlen($nameprefix);

            $valuesforenrolform = array();
            foreach ($formdata as $name => $value) {
                if (substr($name, 0, $nameprefixlength) === $nameprefix) {
                    $name = substr($name, $nameprefixlength);
                    $valuesforenrolform[$name] = $value;
                }
            }
            $enrolform = $plugin->course_expand_get_form_hook($instance);

            $enrolform->_form->updateSubmission($valuesforenrolform, null);

            $enrolled = $plugin->course_expand_enrol_hook($enrolform, $instance);
            if ($enrolled) {
                $mform->_form->addElement('hidden', 'redirect', $CFG->wwwroot . '/course/view.php?id=' . $instance->courseid);
            }

            foreach ($enrolform->_form->_errors as $errorname => $error) {
                $mform->_form->_errors[$nameprefix . $errorname] = $error;
            }
        }
    }

    /**
     * Get course progress status for user according his record of learning
     *
     * @param int $userid
     * @param int $courseid
     * @return array
     */
    public function get_dp_status_sql($userid, $courseid) {
        global $CFG;
        require_once($CFG->dirroot.'/totara/plan/rb_sources/rb_source_dp_course.php');
        // Use base query from rb_source_dp_course, and column/joins of statusandapproval.
        $base_sql = $this->get_dp_status_base_sql($userid);
        $sql = "SELECT CASE WHEN dp_course.planstatus = " . DP_PLAN_STATUS_COMPLETE . "
                            THEN dp_course.completionstatus
                            ELSE course_completion.status
                            END AS course_completion_statusandapproval,
                       dp_course.approved AS approved
                 FROM ".$base_sql. " base
                 LEFT JOIN {course_completions} course_completion
                   ON (base.courseid = course_completion.course
                  AND base.userid = course_completion.userid)
                 LEFT JOIN (SELECT p.userid AS userid, p.status AS planstatus,
                                   pc.courseid AS courseid, pc.approved AS approved,
                                   pc.completionstatus AS completionstatus
                              FROM {dp_plan} p
                             INNER JOIN {dp_plan_course_assign} pc ON p.id = pc.planid) dp_course
                   ON dp_course.userid = base.userid AND dp_course.courseid = base.courseid
                WHERE base.userid = ? AND base.courseid = ?";
        return array($sql, array($userid, $courseid));
    }

    /**
     * Get base sql for course record of learning.
     *
     * @param int|null $userid
     *
     * @return string
     */
    public function get_dp_status_base_sql(int $userid = null) {
        global $DB;

        // Apply global user restrictions.
        $global_restriction_join_ue = $this->get_global_report_restriction_join('ue', 'userid');
        $global_restriction_join_cc = $this->get_global_report_restriction_join('cc', 'userid');
        $global_restriction_join_p1 = $this->get_global_report_restriction_join('p1', 'userid');

        $uniqueid = $DB->sql_concat_join(
            "','",
            array(
                $DB->sql_cast_2char('userid'),
                $DB->sql_cast_2char('courseid')
            )
        );

        $ue_userid_sql = '';
        $cc_userid_sql = '';
        $p1_userid_sql = '';
        if ($userid) {
            $ue_userid_sql = " AND ue.userid = {$userid} ";
            $cc_userid_sql = " AND cc.userid = {$userid} ";
            $p1_userid_sql = " AND p1.userid = {$userid} ";
        }

        return  "(SELECT {$uniqueid} AS id, userid, courseid
                    FROM (SELECT ue.userid AS userid, e.courseid AS courseid
                           FROM {user_enrolments} ue
                           JOIN {enrol} e ON ue.enrolid = e.id {$ue_userid_sql}
                           {$global_restriction_join_ue}
                          UNION
                         SELECT cc.userid AS userid, cc.course AS courseid
                           FROM {course_completions} cc
                           {$global_restriction_join_cc}
                          WHERE cc.status > " . COMPLETION_STATUS_NOTYETSTARTED . "
                           {$cc_userid_sql}
                          UNION
                         SELECT p1.userid AS userid, pca1.courseid AS courseid
                           FROM {dp_plan_course_assign} pca1
                           JOIN {dp_plan} p1 ON pca1.planid = p1.id {$p1_userid_sql}
                           {$global_restriction_join_p1}
                    )
                basesub)";
    }
}
