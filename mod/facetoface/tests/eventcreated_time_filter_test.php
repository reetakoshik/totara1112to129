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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;

class mod_facetoface_eventcreated_time_filter_testcase extends advanced_testcase {
    /**
     * Returning the id of the created report
     * @return int
     */
    private function create_reportbuilder_with_filter(): int {
        global $DB;

        // Creating own report here
        $id = $DB->insert_record("report_builder", (object)[
            'fullname' => 'Seminar sessions',
            'shortname' => 'ssession',
            'source' => 'facetoface_summary',
            'hidden' => 0,
            'cache' => 0,
            'accessmode' => REPORT_BUILDER_ACCESS_MODE_NONE,
            'contentmode' => REPORT_BUILDER_CONTENT_MODE_NONE,
            'embedded' => 0
        ]);

        /** @var rb_source_facetoface_summary $source */
        $source = reportbuilder::get_source_object('facetoface_summary');
        $i = 1;
        foreach ($source->columnoptions as $columnoption) {
            if (!empty($columnoption->deprecated)) {
                continue;
            }

            // Only add the default column option here
            foreach ($source->defaultcolumns as $defaultcolumn) {
                if ($columnoption->type == $defaultcolumn['type'] &&
                    $columnoption->value == $defaultcolumn['value']) {
                    $DB->insert_record("report_builder_columns", (object)[
                        'reportid' => $id,
                        'type' => $columnoption->type,
                        'value' => $columnoption->value,
                        'transform' => $columnoption->transform,
                        'aggregate' => $columnoption->aggregate,
                        'heading' => $columnoption->defaultheading,
                        'sortorder' => $i,
                        'hidden' => 0,
                        'customheading' => ''
                    ]);
                    break;
                }
            }

            $i++;
        }

        // Specifically add session-eventtimecreated filter here for the report
        foreach ($source->filteroptions as $filteroption) {
            if ($filteroption->type == 'session'
                && $filteroption->value == 'eventtimecreated') {
                $DB->insert_record('report_builder_filters', (object)[
                    'reportid' => $id,
                    'type' => $filteroption->type,
                    'value' => $filteroption->value,
                    'sortorder' => 1,
                    'advanced' => 0,
                    'filtername' => $filteroption->label,
                    'customname' => '',
                    'region' => rb_filter_type::RB_FILTER_REGION_STANDARD,
                    'defaultvalue' => ''
                ]);
                break;
            }
        }

        return $id;
    }

    /**
     * @param int $timecreated
     * @return void
     */
    private function generate_facetoface(int $timecreated): void {
        $gen = $this->getDataGenerator();
        /** @var mod_facetoface_generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');

        $course = $gen->create_course(null, ['createsections' => 1]);
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $seminar = new seminar($f2f->id);
        $event = new seminar_event();
        $event->set_facetoface($seminar->get_id());
        $event->save();

        // Updating the time created here for the event
        $event->set_timecreated($timecreated);
        $event->save();

        $time = time();
        $session = new seminar_session();
        $session->set_sessionid($event->get_id());
        $session->set_timestart($time);
        $session->set_timefinish($time + 7200);
        $session->save();

    }

    public function test_filter_seminar_session_by_eventtimecreated(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('facetoface_hidecost', 1, null);
        $time = time();

        $this->generate_facetoface(strtotime("2018-12-22"));
        $this->generate_facetoface(strtotime("2018-12-19"));
        $reportid = $this->create_reportbuilder_with_filter();

        // Setting the filters input here for the reportbuilder
        $_POST = array(
            'sesskey' => sesskey(),
            '_qf__report_builder_standard_search_form' => 1,
            'session-eventtimecreated_sck' => 1,
            'session-eventtimecreated_sdt' => array(
                'day' => '22',
                'month' => '12',
                'year' => '2018'
            ),
            'submitgroupstandard' => array(
                'addfilter' => 'Search'
            )
        );

        $reportbuilder = reportbuilder::create($reportid);

        list($sql, $params, $cache) = $reportbuilder->build_query(false, true);
        $data = $DB->get_records_sql($sql, $params);

        // As there are two seminar with two different events + two differen session dates,
        // however the filter was only for searching for the time created after the specific time

        $this->assertCount(1, $data);
    }
}