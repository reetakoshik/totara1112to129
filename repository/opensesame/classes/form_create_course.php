<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir. '/coursecatlib.php');

class repository_opensesame_form_create_course extends moodleform {
    protected function definition() {
        global $CFG, $TOTARA_COURSE_TYPES;

        $mform = $this->_form;
        $package = $this->_customdata['package'];
        $editoroptions = $this->_customdata['editoroptions'];

        $courseconfig = get_config('moodlecourse');

        $mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('text','fullname', get_string('fullnamecourse'), ['size' => '50']);
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->addRule('fullname', get_string('maximumchars', '', 1333), 'maxlength', 1333);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->setDefault('fullname', $package->title);

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), ['size' => '20']);
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('maximumchars', '', 255), 'maxlength', 255);
        $mform->setType('shortname', PARAM_TEXT);

        $displaylist = coursecat::make_categories_list('moodle/course:create');
        $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
        $mform->addHelpButton('category', 'coursecategory');
        $mform->addRule('category', get_string('required'), 'required', null, 'client');

        if (empty($CFG->audiencevisibility)) {
            $choices = array();
            $choices['0'] = get_string('hide');
            $choices['1'] = get_string('show');
            $mform->addElement('select', 'visible', get_string('visible'), $choices);
            $mform->addHelpButton('visible', 'visible');
            $mform->setDefault('visible', $courseconfig->visible);
        }

        $coursetypeoptions = array();
        foreach($TOTARA_COURSE_TYPES as $k => $v) {
            $coursetypeoptions[$v] = get_string($k, 'totara_core');
        }
        $mform->addElement('select', 'coursetype', get_string('coursetype', 'totara_core'), $coursetypeoptions);

        $mform->addElement('date_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', time() + 3600 * 24);

        $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'idnumbercourse');
        $mform->setType('idnumber', PARAM_RAW);

        $mform->addElement('header', 'descriptionhdr', get_string('description'));
        $mform->setExpanded('descriptionhdr');

        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        $mform->setDefault('summary_editor', array('text' => text_to_html($package->description, true, true, true), 'format' => FORMAT_HTML));

        totara_add_icon_picker($mform, 'add', 'course', 'default', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $package->id);

        $this->add_action_buttons(true, get_string('createcourse', 'repository_opensesame'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }

        // Add field validation check for duplicate idnumber.
        if (!empty($data['idnumber']) && (empty($data['id']) || $this->course->idnumber != $data['idnumber'])) {
            if ($course = $DB->get_record('course', array('idnumber' => $data['idnumber']), '*', IGNORE_MULTIPLE)) {
                if (empty($data['id']) || $course->id != $data['id']) {
                    $errors['idnumber'] = get_string('courseidnumbertaken', 'error', $course->fullname);
                }
            }
        }

        return $errors;
    }
}
