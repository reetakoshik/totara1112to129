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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_dashboard
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot . '/lib/formslib.php');
require_once('lib.php');

/**
 * Dashboard settings form
 */
class totara_dashboard_edit_form extends moodleform {
    public function definition() {
        $mform = & $this->_form;
        $dashboard = $this->_customdata['dashboard'];

        // General settings.
        $mform->addElement('header', 'generalhdr', get_string('general'));
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'totara_dashboard'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333);

        $mform->addElement('checkbox', 'locked', get_string('locked', 'totara_dashboard'));
        $mform->addHelpButton('locked', 'locked', 'totara_dashboard');

        // Cohorts.
        $mform->addElement('header', 'assignedcohortshdr', get_string('assignedcohorts', 'totara_dashboard'));
        if (empty($dashboard->id)) {
            $cohorts = '';
        } else {
            $cohorts = $dashboard->cohorts;
        }
        // Availability.
        $availopts = array();
        $availopts[] = $mform->createElement('radio', 'published', '', get_string('availablenone', 'totara_dashboard'), totara_dashboard::NONE);
        $availopts[] = $mform->createElement('radio', 'published', '', get_string('availableall', 'totara_dashboard'), totara_dashboard::ALL);
        $availopts[] = $mform->createElement('radio', 'published', '', get_string('availableaudience', 'totara_dashboard'), totara_dashboard::AUDIENCE);

        $mform->addGroup($availopts, 'published', get_string('availability', 'totara_dashboard'), html_writer::empty_tag('br'));

        // Set a default.
        $defaultpublished = $this->_customdata['dashboard']->published;
        if (empty($this->_customdata['dashboard']->id)) {
            $defaultpublished = totara_dashboard::ALL;
        }
        $mform->setDefault('published[published]', $defaultpublished);

        $mform->addElement('hidden', 'cohorts', $cohorts);
        $mform->setType('cohorts', PARAM_SEQUENCE);

        $cohortsclass = new totara_cohort_dashboard_cohorts();
        $cohortsclass->build_table($dashboard->id);
        $mform->addElement('html', $cohortsclass->display(true, 'assigned'));

        $mform->addElement('button', 'cohortsbtn', get_string('assigncohorts', 'totara_dashboard'));
        $mform->disabledIf('cohortsbtn', 'published[published]', 'neq', 1);
        $mform->setExpanded('assignedcohortshdr');

        $submittitle = get_string('createdashboard', 'totara_dashboard');
        if ($dashboard->id > 0) {
            $submittitle = get_string('savechanges', 'totara_dashboard');
        }
        $this->add_action_buttons(true, $submittitle);
        $this->set_data($this->_customdata['dashboard']);
    }

    /**
     * Get data with published radio support.
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->published = $data->published['published'];
        }
        return $data;
    }
}

/**
 * Chort assignment and display
 */
class totara_cohort_dashboard_cohorts extends totara_cohort_course_cohorts {
    /**
     * Prepare html for cohorts table
     *
     * @param int $dashboardid
     */
    public function build_table($dashboardid) {
        global $DB;

        $this->headers = array(
            get_string('cohortname', 'totara_cohort'),
            get_string('type', 'totara_cohort'),
            get_string('numlearners', 'totara_cohort')
        );

        // Go to the database and gets the assignments.
        $sql = "SELECT c.id, c.name AS fullname, c.cohorttype
                FROM {totara_dashboard_cohort} tdc
                LEFT JOIN {cohort} c ON (c.id = tdc.cohortid)
                WHERE tdc.dashboardid = ?";
        $records = $DB->get_records_sql($sql, array($dashboardid));

        // Convert these into html.
        if (!empty($records)) {
            foreach ($records as $record) {
                $this->data[] = $this->build_row($record);
            }
        }
    }

    /**
     * Creates each row for the table.
     *
     * @param stdClass|int $item the dashboard object
     * @param bool $readonly prevents deleting rows
     * @return string html
     */
    public function build_row($item, $readonly = false) {
        global $OUTPUT;

        // If its not an object it must be an id. Treat it as such.
        if (!is_object($item)) {
            $item = $this->get_item((int)$item);
        }

        $cohorttypes = cohort::getCohortTypes();
        $cohortstring = $cohorttypes[$item->cohorttype];

        $row = array();
        $delete = '';
        if (!$readonly) {
            $delete = '&nbsp;' . html_writer::link('#', $OUTPUT->pix_icon('t/delete', get_string('delete')),
                      array('title' => get_string('delete'), 'class'=>'dashboardcohortdeletelink'));
        }
        $row[] = html_writer::start_tag('div', array('id' => 'cohort-item-'.$item->id, 'class' => 'item')) .
                 format_string($item->fullname) . $delete . html_writer::end_tag('div');

        $row[] = $cohortstring;
        $row[] = $this->user_affected_count($item);

        return $row;
    }

    /**
     * Prints out the actual html
     *
     * @param bool $return
     * @param string $type Type of the table
     * @return string html
     */
    public function display($return = false, $type = 'enrolled') {
        $table = new html_table();
        $table->attributes = array('class' => 'generaltable');
        $table->id = 'dashboard-cohorts-table-' . $type;
        $table->head = $this->headers;

        if (!empty($this->data)) {
            $table->data = $this->data;
        }
        $htmltable = html_writer::table($table);
        $htmlfieldset = html_writer::tag('fieldset', $htmltable, array('class' =>'assignment_category cohorts'));
        $htmlinner = html_writer::div($htmlfieldset, '', array('id' => 'assignment_categories'));
        $html = html_writer::div($htmlinner, '', array('id' => 'dashboard-cohorts-assignment'));

        if ($return) {
            return $html;
        }
        echo $html;
    }
}
