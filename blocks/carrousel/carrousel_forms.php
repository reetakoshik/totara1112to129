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
 * @package 
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/totara/dashboard/lib.php');
require_once($CFG->dirroot.'/blocks/carrousel/lib.php');

/**
 * Slider settings form
 */
class carrousel_edit_form extends moodleform {
    
    public function definition() {
        global $CFG, $USER, $FILEPICKER_OPTIONS;
        
       
        $blockid = required_param('blockid', PARAM_INT);
        $id = optional_param('id', 0 ,PARAM_INT);
        $action = optional_param('action', null ,PARAM_TEXT);
        
            
        $mform = & $this->_form;
        $slide = $this->_customdata['slide'];
       
        // General settings.
        $mform->addElement('header', 'generalhdr', get_string('general'));
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('slide_title','block_carrousel'), 'maxlength="255" size="50"');
        $mform->setType('title', PARAM_TEXT);
//        $mform->addRule('title', null, 'required');
        
        
        $mform->addElement('text', 'buttontext', get_string('slide_text','block_carrousel'));
        $mform->setType('buttontext', PARAM_TEXT);
        if (isset($slide->buttontext)) {
            $mform->setDefault('buttontext', $slide->buttontext);
        }

        $select = $mform->addElement(
            'select', 
            'textcolor', 
            get_string('slide_text_color', 'block_carrousel'), 
            [
                'white' => get_string('color_white', 'block_carrousel'), 
                'black' => get_string('color_black', 'block_carrousel'), 
            ]
        );
        $mform->setType('textcolor', PARAM_TEXT);
        if (isset($slide->textcolor)) {
            $select->setSelected($slide->textcolor);
        } else {
            $select->setSelected('white');
        }

        $mform->addElement('text', 'buttonurl', get_string('slide_url','block_carrousel'));
        $mform->setType('buttonurl', PARAM_TEXT);
         if (isset($slide->buttonurl)) {
            $mform->setDefault('buttonurl', $slide->buttonurl);
        }

        $mform->addElement(
            'filemanager', 
            'private_filemanager',
            get_string('slide_image', 'block_carrousel'),
            null,
            [
                'subdirs'        => 0, 
                'maxbytes'       => 50000000, 
                'maxfiles'       => 1,
                'accepted_types' => ['.png', '.jpg', '.gif'] 
            ]
        );

        // Cohorts.
        $mform->addElement('header', 'assignedcohortshdr', get_string('assignedcohorts', 'block_carrousel'));
        if (empty($slide->id)) {
            $cohorts = '';
        } else {
            $cohorts = $slide->cohorts;
        }
        
        $mform->addElement('hidden', 'blockid', $blockid);
        $mform->setType('blockid', PARAM_INT);
        
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'cohorts', $cohorts);
        $mform->setType('cohorts', PARAM_SEQUENCE);

        $cohortsclass = new cohort_carrousel_cohorts();
        $cohortsclass->build_table($id);
        $mform->addElement('html', $cohortsclass->display(true, 'assigned'));

        $mform->addElement('button', 'cohortsbtn', get_string('assigncohorts', 'block_carrousel'));
        $mform->setExpanded('assignedcohortshdr');

        $submittitle = get_string('add', 'block_carrousel');
        if ($id > 0) {
            $submittitle = get_string('savechanges', 'block_carrousel');
        }
        $this->add_action_buttons(true, $submittitle);
        $this->set_data($this->_customdata['slide']);
    }
}

/**
 * Chort assignment and display
 */
class cohort_carrousel_cohorts extends totara_cohort_course_cohorts {
    /**
     * Prepare html for cohorts table
     *
     * @param int $slideid
     */
    public function build_table($slideid) {
        global $DB;

        $this->headers = array(
            get_string('cohortname', 'totara_cohort'),
            get_string('type', 'totara_cohort'),
            get_string('numlearners', 'totara_cohort')
        );
        
        $slide = block_carrousel_get_slide($slideid);
        
        if (!empty($slide->cohorts)) {
            // Go to the database and gets the assignments.
            $sql = "SELECT c.id, c.name AS fullname, c.cohorttype, tdc.cohorts
                FROM {block_carrousel} tdc
                LEFT JOIN {cohort} c ON (c.id IN ( " . $slide->cohorts . " ))
                WHERE tdc.id = ?";
            $records = $DB->get_records_sql($sql, array($slideid));

            // Convert these into html.
            if (!empty($records)) {

                foreach ($records as $key => $record) {

                    if ($key != null)
                        $this->data[] = $this->build_row($record);
                }
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