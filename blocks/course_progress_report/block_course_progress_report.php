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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_course_progress_report
 */

/**
 * Main block file
 *
 * @deprecated since Totara 12. See readme.txt.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_course_progress_report
 */
class block_course_progress_report extends block_base {

    public function init() {
        $this->title = get_string('title', 'block_course_progress_report');
    }

    public function applicable_formats() {
        return array('site-index' => true);
    }

    public function instance_allow_multiple() {
        return false;
    }

    private function can_view() {
        return (isloggedin() and !isguestuser());
    }

    public function specialization() {
        $this->title = get_string('title', 'block_course_progress_report');
    }

    public function get_content() {
        global $CFG, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if ($this->can_view()) {
            // Report builder lib is required for the embedded report.
            require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

            $sid = 0;
            if (!empty($this->block->config->savedsearch)) {
                $sid = $this->block->config->savedsearch;
            }

            // Get report for the block.
            if (!$reportrecord = $DB->get_record('report_builder', array('shortname' => 'course_progress'), '*', IGNORE_MISSING)) {
                debugging('The "course_progress" embedded report could not be found.', DEBUG_DEVELOPER);
                return $this->content;
            }

            // Verify global restrictions.
            $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
            try {
                $config = (new rb_config())->set_sid($sid)->set_global_restriction_set($globalrestrictionset);
                $report = reportbuilder::create_embedded('course_progress', $config);
            } catch (moodle_exception $e) {
                // Don't break page if report became unavailable.
                return $this->content;
            }

            // Ensure that the toolbar search is disabled, as this will not work from the report block.
            $report->hidetoolbar = true;
            $report->include_js();

            $debug = optional_param('debug', 0, PARAM_INT);
            $renderer = $this->page->get_renderer('totara_reportbuilder');
            // This must be done after the header and before any other use of the report.
            list($reporthtml, $debughtml) = $renderer->report_html($report, $debug);

            // We only want to show the report if it is not empty, or if it is empty but has been filtered and has total results.
            if (!empty($this->config->hideifnoresults)) {
                // Done inside a nested IF just to be extremely sure the count calls aren't executed unless needed.
                // Filtered count will do for this as it bypasses the can_display_total_count setting.
                if ($report->get_filtered_count() == 0) {
                    return $this->content;
                }
            }

            $this->content->text .= $debughtml;
            $this->content->text .= $reporthtml;
        }
        return $this->content;
    }
}
