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
 * @package totara_connect
 */

use \totara_connect\util;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

class totara_connect_form_client_edit extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $client = $this->_customdata;

        $strrequired = get_string('required');

        $mform->addElement('text', 'clientname', get_string('name'), 'size="70"');
        $mform->setType('clientname', PARAM_TEXT);
        $mform->addRule('clientname', $strrequired, 'required', null, 'client');

        $types = array(
            '' => '',
            'totaralms' => 'Totara LMS',
            'totarasocial' => 'Totara Social',
        );
        $mform->addElement('select', 'clienttype', get_string('clienttype', 'totara_connect'), $types);
        $mform->hardFreeze('clienttype');

        $mform->addElement('static', 'clienturl', get_string('url'));
        $mform->addHelpButton('clienturl', 'clienturl', 'totara_connect');

        $mform->addElement('advcheckbox', 'allowpluginsepservices', get_string('allowpluginsepservices', 'totara_connect'));
        $mform->addHelpButton('allowpluginsepservices', 'allowpluginsepservices', 'totara_connect');

        $cohorts = $DB->get_records_menu('cohort', array('contextid' => context_system::instance()->id), 'name ASC', 'id, name');
        $cohorts[0] = get_string('no');
        $mform->addElement('select', 'cohortid', get_string('restricttocohort', 'totara_connect'), $cohorts);
        $mform->addHelpButton('cohortid', 'restricttocohort', 'totara_connect');

        $mform->addElement('advcheckbox', 'syncprofilefields', get_string('syncprofilefields', 'totara_connect'));
        $mform->addHelpButton('syncprofilefields', 'syncprofilefields', 'totara_connect');

        $mform->addElement('advcheckbox', 'syncjobs', get_string('syncjobs', 'totara_connect'));
        $mform->addHelpButton('syncjobs', 'syncjobs', 'totara_connect');

        if (!totara_feature_disabled('positions')) {
            $options = array();
            $frameworks = $DB->get_records('pos_framework', array(), 'sortorder ASC');
            foreach ($frameworks as $framework) {
                $options[$framework->id] = $framework->fullname;
                if ($framework->idnumber !== '') {
                    $options[$framework->id] .= ' [' . $framework->idnumber . ']';
                }
            }
            if ($options and $client->status == util::CLIENT_STATUS_OK) {
                $mform->addElement('select', 'positionframeworks', get_string('positionframeworks', 'totara_connect'), $options, array('multiple' => true));
                $mform->addHelpButton('positionframeworks', 'positionframeworks', 'totara_connect');
            }
        }

        $options = array();
        $frameworks = $DB->get_records('org_framework', array(), 'sortorder ASC');
        foreach ($frameworks as $framework) {
            if (!$framework->visible and !in_array($framework->id, $client->positionframeworks)) {
                continue;
            }
            $options[$framework->id] = $framework->fullname;
            if ($framework->idnumber !== '') {
                $options[$framework->id] .= ' [' . $framework->idnumber . ']';
            }
        }
        if ($options and $client->status == util::CLIENT_STATUS_OK) {
            $mform->addElement('select', 'organisationframeworks', get_string('organisationframeworks', 'totara_connect'), $options, array('multiple' => true));
            $mform->addHelpButton('organisationframeworks', 'organisationframeworks', 'totara_connect');
        }

        $mform->addElement('advcheckbox', 'addnewcourses', get_string('addnewcourses', 'totara_connect'));
        $mform->addHelpButton('addnewcourses', 'addnewcourses', 'totara_connect');
        $mform->addElement('advcheckbox', 'addnewcohorts', get_string('addnewcohorts', 'totara_connect'));
        $mform->addHelpButton('addnewcohorts', 'addnewcohorts', 'totara_connect');

        if ($client->status == util::CLIENT_STATUS_OK) {
            $mform->addElement('header', 'cohortshdr', get_string('cohorts', 'totara_connect'));
            $cohortsclass = new totara_connect_cohorts($client);
            $cohortsclass->init_page_js();
            $cohortsclass->build_table();
            $mform->addElement('html', $cohortsclass->display(true));
            $mform->addElement('button', 'cohortsadd', get_string('cohortsadd', 'totara_connect'));
            $mform->addElement('hidden', 'cohorts', implode(',', array_keys($cohortsclass->get_cohorts('c.id'))));
            $mform->setType('cohorts', PARAM_SEQUENCE);
            $mform->setExpanded('cohortshdr', $cohortsclass->has_data());

            $mform->addElement('header', 'courseshdr', get_string('courses', 'totara_connect'));
            $coursesclass = new totara_connect_courses($client);
            $coursesclass->init_page_js();
            $coursesclass->build_table();
            $mform->addElement('html', $coursesclass->display(true));
            $mform->addElement('button', 'coursesadd', get_string('coursesadd', 'totara_connect'));
            $mform->addElement('hidden', 'courses', implode(',', array_keys($coursesclass->get_courses('c.id'))));
            $mform->setType('courses', PARAM_SEQUENCE);
            $mform->setExpanded('courseshdr', $coursesclass->has_data());

            $mform->addElement('header', '');
        }

        $mform->addElement('textarea', 'clientcomment', get_string('comment', 'totara_connect'));
        $mform->setType('clientcomment', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();

        $this->set_data($client);
    }
}
