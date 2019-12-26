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
 * @author  Brian Quinn <brian@learningpool.com>
 * @author Finbar Tracey <finbar@learningpool.com>
 * @package block_totara_report_table
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/edit_form.php');

class block_totara_report_table_edit_form extends block_edit_form {

    /**
     * Form definition for this specific block.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $USER, $DB, $CFG, $PAGE;

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

        // Include the required JavaScript.
        $PAGE->requires->string_for_js('allavailabledata', 'block_totara_report_table');
        $PAGE->requires->js_call_amd('block_totara_report_table/module', 'populatelist', array());

        // Output the form.
        $mform->addElement('header', 'configheader', get_string('customblocksettings', 'block'));

        // Generate any missing embedded reports when we load this form.
        reportbuilder::generate_embedded_reports();

        // Report selection.
        $reportoptions = array('' => get_string('choosedots', 'core'));

        $allowed = reportbuilder::get_permitted_reports($USER->id, true);
        foreach ($allowed as $report) {
            $reportoptions[$report->id] = format_string($report->fullname);
        }

        if (isset($this->block->config->reportid) && !isset($reportoptions[$this->block->config->reportid])) {
            $reportoptions[$this->block->config->reportid] = get_string('inaccessiblereport', 'block_totara_report_table');
        }

        $mform->addElement('select', 'config_reportid', get_string('report', 'totara_reportbuilder'), $reportoptions);

        if (!empty($this->block->config->reportid)) {
            $mform->setDefault('config_reportid', $this->block->config->reportid);
        }

        // Hack to get submitted reportid value to prepare saved searches before they filtered out.
        $reportid = optional_param('config_reportid', 0, PARAM_INT);

        if (!$reportid && isset($this->block->config->reportid)) {
            $reportid = $this->block->config->reportid;
        }

        // Populate the saved options.
        $savedoptions = array('0' => get_string('allavailabledata', 'block_totara_report_table'));

        if ($reportid && isset($allowed[$reportid])) {
            $params = array('reportid' => $reportid, 'ispublic' => 1);

            $savedreports = $DB->get_records_menu('report_builder_saved', $params, 'id', 'id, name');

            if (!empty($savedreports)) {
                $savedoptions = array_replace($savedoptions, $savedreports);
            }
            if (isset($this->block->config->savedsearch) && !isset($savedoptions[$this->block->config->savedsearch])) {
                $savedoptions[$this->block->config->savedsearch] = get_string('inaccessiblesavedsearch','block_totara_report_table');
            }
        }

        // Saved search.
        $mform->addElement('select', 'config_savedsearch', get_string('savedsearch', 'block_totara_report_table'),
                $savedoptions);
        $mform->addElement('static', 'savedsearchdesc', '', get_string('savedsearchpublic', 'block_totara_report_table'));

        if (!empty($this->block->config->savedsearch)) {
            $mform->setDefault('config_savedsearch', $this->block->config->savedsearch);
        }

        $mform->disabledIf('config_savedsearch', 'config_reportid', 'eq', '');

        // Hide results.
        $mform->addElement('advcheckbox', 'config_hideifnoresults',
            get_string('hideblockifzeroresults', 'block_totara_report_table'));
        $mform->setDefault('config_hideifnoresults', false);
    }
}
