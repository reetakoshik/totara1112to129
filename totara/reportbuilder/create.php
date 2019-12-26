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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */

/**
 * Page containing new report form
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/report_forms.php');

admin_externalpage_setup('rbmanagereports');

$output = $PAGE->get_renderer('totara_reportbuilder');

$returnurl = $CFG->wwwroot . '/totara/reportbuilder/index.php';

// form definition
$mform = new report_builder_new_form();

// form results check
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {

    if (empty($fromform->submitbutton)) {
        totara_set_notification(
            get_string('error:unknownbuttonclicked', 'totara_reportbuilder'),
            $returnurl);
    }
    // create new record here
    $todb = new stdClass();
    $todb->fullname = $fromform->fullname;
    $todb->shortname = reportbuilder::create_shortname($fromform->fullname);
    $todb->source = ($fromform->source != '0') ? $fromform->source : null;
    $todb->hidden = $fromform->hidden;
    $todb->recordsperpage = 40;
    $todb->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
    $todb->accessmode = REPORT_BUILDER_ACCESS_MODE_ANY; // default to limited access
    $todb->embedded = 0;
    if (isset($fromform->globalrestriction)) {
        // In case somebody adds it to the UI in the future.
        $todb->globalrestriction = $fromform->globalrestriction;
    } else {
        // Always use the default even if GUR not active at the moment.
        $todb->globalrestriction = get_config('reportbuilder', 'globalrestrictiondefault');
    }
    $todb->timemodified = time();

    try {
        $transaction = $DB->start_delegated_transaction();

        $newid = $DB->insert_record('report_builder', $todb);

        // by default we'll require a role but not set any, which will restrict report access to
        // the site administrators only
        reportbuilder_set_default_access($newid);

        // create columns for new report based on default columns
        $src = reportbuilder::get_source_object($fromform->source);
        if (isset($src->defaultcolumns) && is_array($src->defaultcolumns)) {
            $defaultcolumns = $src->defaultcolumns;
            $so = 1;
            foreach ($defaultcolumns as $option) {
                $heading = isset($option['heading']) ? $option['heading'] :
                    null;
                $hidden = isset($option['hidden']) ? $option['hidden'] : 0;
                $column = $src->new_column_from_option($option['type'],
                    $option['value'], null, null, $heading, !empty($heading), $hidden);
                $todb = new stdClass();
                $todb->reportid = $newid;
                $todb->type = $column->type;
                $todb->value = $column->value;
                $todb->heading = $column->heading;
                $todb->hidden = $column->hidden;
                $todb->transform = $column->transform;
                $todb->aggregate = $column->aggregate;
                $todb->sortorder = $so;
                $todb->customheading = 0; // initially no columns are customised
                $DB->insert_record('report_builder_columns', $todb);
                $so++;
            }
        }
        // create filters for new report based on default filters
        if (isset($src->defaultfilters) && is_array($src->defaultfilters)) {
            $defaultfilters = $src->defaultfilters;
            $so = 1;
            foreach ($defaultfilters as $option) {
                $todb = new stdClass();
                $todb->reportid = $newid;
                $todb->type = $option['type'];
                $todb->value = $option['value'];
                $todb->advanced = isset($option['advanced']) ? $option['advanced'] : 0;
                $todb->defaultvalue = isset($option['defaultvalue']) ? serialize($option['defaultvalue']) : '';
                $todb->sortorder = $so;
                $todb->region = isset($option['region']) ? $option['region'] : rb_filter_type::RB_FILTER_REGION_STANDARD;
                $DB->insert_record('report_builder_filters', $todb);
                $so++;
            }
        }
        // Create toolbar search columns for new report based on default toolbar search columns.
        if (isset($src->defaulttoolbarsearchcolumns) && is_array($src->defaulttoolbarsearchcolumns)) {
            foreach ($src->defaulttoolbarsearchcolumns as $option) {
                $todb = new stdClass();
                $todb->reportid = $newid;
                $todb->type = $option['type'];
                $todb->value = $option['value'];
                $DB->insert_record('report_builder_search_cols', $todb);
            }
        }
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($newid, $config, false); // No access control for managing of reports here.
        \totara_reportbuilder\event\report_created::create_from_report($report, false)->trigger();
        $transaction->allow_commit();
        redirect($CFG->wwwroot . '/totara/reportbuilder/general.php?id='.$newid);
    } catch (ReportBuilderException $e) {
        $transaction->rollback($e);
        trigger_error($e->getMessage(), E_USER_WARNING);
    } catch (Exception $e) {
        $transaction->rollback($e);
        redirect($returnurl, get_string('error:couldnotcreatenewreport', 'totara_reportbuilder'));
    }
}

/** @var totara_reportbuilder_renderer $output */
echo $output->header();

// User generated reports.
echo $output->heading(get_string('createreport', 'totara_reportbuilder'));

// display mform
$mform->display();

echo $output->footer();
