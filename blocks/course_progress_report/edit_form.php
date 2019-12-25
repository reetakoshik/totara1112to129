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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package block_course_progress_report
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/edit_form.php');

class block_course_progress_report_edit_form extends block_edit_form {

    /**
     * Enable general settings
     *
     * @return bool
     */
    protected function has_general_settings() {
        return true;
    }

    /**
     * Form definition for this specific block.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

        parent::specific_definition($mform);

        // Output the form.
        $mform->addElement('header', 'configheader', get_string('customblocksettings', 'block'));

        // Get report for the block.
        $report = $DB->get_record('report_builder', array('shortname' => 'course_progress'), '*', IGNORE_MISSING);

        if (!empty($report->id) && reportbuilder::is_capable($report->id)) {
            $params = array('reportid' => $report->id, 'ispublic' => 1);
            $savedreports = $DB->get_records_menu('report_builder_saved', $params, 'id', 'id, name');

            // Currently it is not possible to have saved search on course progress report,
            // but in case that changes in the future, let's allow people to select saved search.
            if (!empty($savedreports)) {
                // Populate the saved options.
                $savedoptions = array('0' => get_string('allavailabledata', 'block_course_progress_report'));
                $savedoptions = array_replace($savedoptions, $savedreports);

                if (isset($this->block->config->savedsearch) && !isset($savedoptions[$this->block->config->savedsearch])) {
                    $savedoptions[$this->block->config->savedsearch] = get_string('inaccessiblesavedsearch', 'block_course_progress_report');
                }

                // "Saved search" option.
                $mform->addElement('select', 'config_savedsearch', get_string('savedsearch', 'block_course_progress_report'), $savedoptions);
                $mform->addElement('static', 'savedsearchdesc', '', get_string('savedsearchpublic', 'block_course_progress_report'));

                if (!empty($this->block->config->savedsearch)) {
                    $mform->setDefault('config_savedsearch', $this->block->config->savedsearch);
                }
            }

            // "Hide results if empty" option.
            $mform->addElement('advcheckbox', 'config_hideifnoresults', get_string('hideblockifzeroresults', 'block_course_progress_report'));
            $mform->setDefault('config_hideifnoresults', false);
        } else {
            $mform->addElement('static', 'inaccessiblereportdesc', '', get_string('inaccessiblereport', 'block_course_progress_report'));
        }
    }
}
