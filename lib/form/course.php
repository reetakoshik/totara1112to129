<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Course selector field.
 *
 * Allows auto-complete ajax searching for courses and can restrict by enrolment, permissions, viewhidden...
 *
 * @package   core_form
 * @copyright 2015 Damyon Wiese <damyon@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->libdir . '/form/autocomplete.php');

/**
 * Form field type for choosing a course.
 *
 * Allows auto-complete ajax searching for courses and can restrict by enrolment, permissions, viewhidden...
 *
 * @package   core_form
 * @copyright 2015 Damyon Wiese <damyon@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_course extends MoodleQuickForm_autocomplete {

    /**
     * @var array $exclude Exclude a list of courses from the list (e.g. the current course).
     */
    protected $exclude = array();

    /**
     * @var boolean $allowmultiple Allow selecting more than one course.
     */
    protected $multiple = false;

    /**
     * @var array $requiredcapabilities Array of extra capabilities to check at the course context.
     */
    protected $requiredcapabilities = array();

    /**
     * @var bool $limittoenrolled Only allow enrolled courses.
     */
    protected $limittoenrolled = false;

    /**
     * Constructor
     *
     * @param string $elementname Element name
     * @param mixed $elementlabel Label(s) for an element
     * @param array $options Options to control the element's display
     *                       Valid options are:
     *                       'multiple' - boolean multi select
     *                       'exclude' - array or int, list of course ids to never show
     *                       'requiredcapabilities' - array of capabilities. Uses ANY to combine them.
     *                       'currentdata' - array of course ids that are currently selected - must be provided if the course might not be visible to the viewer
     *                       'limittoenrolled' - boolean Limits to enrolled courses.
     *                       'includefrontpage' - boolean Enables the frontpage to be selected.
     */
    public function __construct($elementname = null, $elementlabel = null, $options = array()) {
        global $DB;

        if (isset($options['multiple'])) {
            $this->multiple = $options['multiple'];
        }
        if (isset($options['exclude'])) {
            $this->exclude = $options['exclude'];
            if (!is_array($this->exclude)) {
                $this->exclude = array($this->exclude);
            }
        }
        if (isset($options['requiredcapabilities'])) {
            $this->requiredcapabilities = $options['requiredcapabilities'];
        }
        if (isset($options['limittoenrolled'])) {
            $this->limittoenrolled = $options['limittoenrolled'];
        }

        $validattributes = array(
            'ajax' => 'core/form-course-selector',
            'data-requiredcapabilities' => implode(',', $this->requiredcapabilities),
            'data-exclude' => implode(',', $this->exclude),
            'data-limittoenrolled' => (int)$this->limittoenrolled
        );
        if ($this->multiple) {
            $validattributes['multiple'] = 'multiple';
        }
        if (isset($options['noselectionstring'])) {
            $validattributes['noselectionstring'] = $options['noselectionstring'];
        }
        if (isset($options['placeholder'])) {
            $validattributes['placeholder'] = $options['placeholder'];
        }
        // Front page course option can only be shown if the user has the capability. If the front page is inaccessible but
        // already selected, then it should have been specified in currentdata, and will appear selected in the form element.
        if (!empty($options['includefrontpage']) && has_capability('moodle/course:view', context_course::instance(SITEID))) {
            $validattributes['data-includefrontpage'] = SITEID;
        }

        parent::__construct($elementname, $elementlabel, array(), $validattributes);

        // Set up all currently selected courses as options, so that they will definitely exist.
        if (isset($options['currentdata'])) {
            foreach ($options['currentdata'] as $courseid) {
                if (empty($courseid)) {
                    continue;
                }
                $course = $DB->get_record('course', array('id' => $courseid));
                context_helper::preload_from_record($course);
                $context = context_course::instance($course->id);
                $label = format_string(get_course_display_name_for_list($course), true, ['context' => $context]);
                $this->addOption($label, $courseid);
            }
        }
    }

    /**
     * Set the value of this element. If values can be added or are unknown, we will
     * make sure they exist in the options array.
     * @param string|array $value The value to set.
     * @return boolean
     */
    public function setValue($value) {
        global $DB;

        $courseids = (array) $value;
        $coursestoadd = array();

        // We only need to validate submitted course ids if they are not already in the list of options.
        foreach ($courseids as $courseid) {
            if (!empty($courseid) &&
                (!$this->optionExists($courseid)) &&
                ($courseid !== '_qf__force_multiselect_submission')) {
                array_push($coursestoadd, $courseid);
            }
        }

        foreach ($coursestoadd as $courseid) {
            if (totara_course_is_viewable($courseid)) {
                $context = context_course::instance($courseid);
                if (empty($this->requiredcapabilities) || has_any_capability($this->requiredcapabilities, $context)) {
                    // The course is valid, so add it to the list of options (so that it is not removed during validation).
                    $course = $DB->get_record('course', array('id' => $courseid));
                    context_helper::preload_from_record($course);
                    $context = context_course::instance($course->id);
                    $label = format_string(get_course_display_name_for_list($course), true, ['context' => $context]);
                    $this->addOption($label, $courseid);
                }
            }
        }

        return $this->setSelected($courseids);
    }
}
