<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\phpunit;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

/**
 * Utility methods for reportbuilder tests.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */
trait report_testing {
    /**
     * Enable report caching and generate report cache.
     *
     * @param int $reportid report id
     */
    protected function enable_caching($reportid) {
        global $DB;

        if ($DB->get_dbfamily() !== 'postgres') {
            $this->preventResetByRollback();
        }

        set_config('enablereportcaching', 1);
        // Schedule cache.
        $DB->execute('UPDATE {report_builder} SET cache = 1 WHERE id = ?', array($reportid));
        reportbuilder_schedule_cache($reportid, array('initschedule' => 1));
        // Generate cache.
        reportbuilder_generate_cache($reportid);
    }

    /**
     * Disable report caching.
     *
     * @param int $reportid report id
     */
    protected function disable_caching($reportid) {
        global $DB;

        // Unschedule cache.
        $DB->execute('UPDATE {report_builder} SET cache = 0 WHERE id = ?', array($reportid));
        set_config('enablereportcaching', 0);
    }

    /**
     * Delete all columns of report.
     *
     * @param \reportbuilder $report
     */
    protected function delete_columns(\reportbuilder $report) {
        global $DB;
        $DB->delete_records('report_builder_columns', array('reportid' => $report->_id));
    }

    /**
     * Add a new report column.
     *
     * @param \reportbuilder $report
     * @param string $type
     * @param string $value
     * @param string $transform
     * @param string $aggregate
     * @param string $heading
     * @param bool $hidden
     * @return array of records
     */
    protected function add_column(\reportbuilder $report, $type, $value, $transform, $aggregate, $heading, $hidden) {
        global $DB;

        $column = $report->src->new_column_from_option($type, $value, $transform, $aggregate, $heading, !empty($heading), $hidden);
        \reportbuilder::reset_source_object_cache();

        $sortorder = $DB->get_field('report_builder_columns', 'MAX(sortorder) + 1', array('reportid' => $report->_id));
        if (!$sortorder) {
            $sortorder = 1;
        }

        $todb = new \stdClass();
        $todb->reportid = $report->_id;
        $todb->type = $column->type;
        $todb->value = $column->value;
        $todb->heading = $column->heading;
        $todb->hidden = $column->hidden;
        $todb->transform = $column->transform;
        $todb->aggregate = $column->aggregate;
        $todb->sortorder = $sortorder;
        $todb->customheading = $column->customheading;
        $id = $DB->insert_record('report_builder_columns', $todb);

        return $DB->get_records('report_builder_columns', array('id' => $id));
    }

    /**
     * Add a new filter to report
     * This will not update report instance, so use it before new reportbuilder.
     *
     * @param int $reportid
     * @param string $type
     * @param string $value
     * @param int $advanced
     * @param string $filtername
     * @param int $customname
     * @param int $region
     * @param array $defaultvalue
     * @return array of records
     */
    protected function add_filter($reportid, $type, $value, $advanced, $filtername, $customname, $region, $defautvalue = array()) {
        global $DB;

        $sortorder = $DB->get_field('report_builder_filters', 'MAX(sortorder) + 1', array('reportid' => $reportid));
        if (!$sortorder) {
            $sortorder = 1;
        }

        $todb = new \stdClass();
        $todb->reportid = $reportid;
        $todb->type = $type;
        $todb->value = $value;
        $todb->advanced = $advanced;
        $todb->filtername = $filtername;
        $todb->customname = $customname;
        $todb->region = $region;
        $todb->defaultvalue = !empty($defautvalue) ? serialize($defautvalue): '';
        $todb->sortorder = $sortorder;
        $id = $DB->insert_record('report_builder_filters', $todb);

        return $DB->get_records('report_builder_filters', array('id' => $id));
    }

    /**
     * Set report settings
     * This will not update report instance, so use it before new reportbuilder.
     *
     * @param int $reportid
     * @param string $type
     * @param string $name
     * @param int $value
     * @return array of records
     */
    protected function set_setting($reportid, $type, $name, $value) {
        global $DB;

        $todb = new \stdClass();
        $todb->reportid = $reportid;
        $todb->type = $type;
        $todb->name = $name;
        $todb->value = $value;
        $id = $DB->get_field('report_builder_settings', 'id', array('reportid' => $reportid, 'type' => $type, 'name' => $name),
                IGNORE_MISSING);
        if ($id) {
            $todb->id = $id;
            $DB->update_record('report_builder_settings', $todb);
        } else {
            $id = $DB->insert_record('report_builder_settings', $todb);
        }

        return $DB->get_records('report_builder_settings', array('id' => $id));
    }

    /**
     * Add report graph settings
     * This will not update report instance, so use it before new reportbuilder.
     *
     * @param int $reportid
     * @param string $type
     * @param int $stacked
     * @param int $maxrecords
     * @param string $category
     * @param string $legend
     * @param array $series
     * @param string $settings
     * @return array of records
     */
    protected function add_graph($reportid, $type, $stacked, $maxrecords, $category, $legend, array $series, $settings) {
        global $DB;

        $todb = new \stdClass();
        $todb->reportid = $reportid;
        $todb->type = $type;
        $todb->stacked = $stacked;
        $todb->maxrecords = $maxrecords;
        $todb->category = $category;
        $todb->legend = $legend;
        $todb->series = json_encode($series);
        $todb->settings = $settings;

        $id = $DB->insert_record('report_builder_graph', $todb);

        return $DB->get_records('report_builder_graph', array('id' => $id));
    }

    /**
     * Create new reportbuilder report.
     *
     * @param string $source
     * @param string $fullname
     * @param bool $showtotalcount
     * @return int report id
     */
    protected function create_report($source, $fullname, $showtotalcount = false, $embedded = 0) {
        global $DB;

        $todb = new \stdClass();
        $todb->fullname = $fullname;
        $todb->shortname = \reportbuilder::create_shortname($fullname);
        $todb->source = $source;
        $todb->hidden = 0;
        $todb->recordsperpage = 40;
        $todb->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $todb->embedded = $embedded;
        $todb->showtotalcount = $showtotalcount ? 1 : 0;
        $todb->id = $DB->insert_record('report_builder', $todb);

        // Set up access permissions.
        reportbuilder_set_default_access($todb->id);

        return $todb->id;
    }
}
