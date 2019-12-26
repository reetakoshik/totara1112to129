<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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

namespace totara_reportbuilder\rb\source;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait report_schedule_trait
 */
trait report_schedule_trait {

    /** @var string $reportschedulejoin */
    protected $reportschedulejoin = null;

    /**
     * Add report info
     */
    protected function add_report_schedule_to_base() {
        /** @var report_schedule_trait|\rb_base_source $this */
        if (isset($this->reportschedulejoin)) {
            throw new \coding_exception('Report schedule info can be added only once!');
        }
        $this->reportschedulejoin = 'base';

        $this->add_report_schedule_joins();
        $this->add_report_schedule_columns();
        $this->add_report_schedule_filters();
    }

    /**
     * Add report schedule info
     *
     * @param \rb_join $join
     */
    protected function add_report_schedule(\rb_join $join) {
        /** @var report_schedule_trait|\rb_base_source $this */
        if (isset($this->reportschedulejoin)) {
            throw new \coding_exception('Report schedule info can be added only once!');
        }
        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->reportschedulejoin = $join->name;

        $this->add_report_schedule_joins();
        $this->add_report_schedule_columns();
        $this->add_report_schedule_filters();
    }

    /**
     * Add report schedule joins.
     */
    protected function add_report_schedule_joins() {
        /** @var report_schedule_trait|\rb_base_source $this */
        $join = $this->reportschedulejoin;

        $this->joinlist[] = new \rb_join(
            'moduser',
            'LEFT',
            '{user}',
            "moduser.id = $join.usermodified",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
    }

    /**
     * Add report schedule columns.
     */
    protected function add_report_schedule_columns() {
        global $DB;
        /** @var report_schedule_trait|\rb_base_source $this */
        $join = $this->reportschedulejoin;
        $usednamefields = \totara_get_all_user_name_fields_join('moduser', null, true);
        $allnamefields = \totara_get_all_user_name_fields_join('moduser');

        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'format',
            get_string('format', 'rb_source_scheduled_reports'),
            "{$join}.format",
            [
                'displayfunc' => 'report_export_format',
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'next',
            get_string('nextschedule', 'rb_source_scheduled_reports'),
            "{$join}.schedule",
            [
                'displayfunc' => 'report_schedule_next',
                'extrafields' => ['frequency' => "{$join}.frequency", 'nextreport' => "{$join}.nextreport"],
                'joins' => $join,
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'schedule',
            get_string('schedule', 'rb_source_scheduled_reports'),
            "{$join}.schedule",
            [
                'displayfunc' => 'report_schedule_schedule',
                'extrafields' => ['frequency' => "{$join}.frequency"],
                'joins' => $join,
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'actions',
            get_string('actions', 'rb_source_scheduled_reports'),
            "{$join}.id",
            [
                'displayfunc' => 'report_schedule_actions',
                'noexport' => true,
                'nosort' => true,
                'joins' => $join,
                'capability' => ['totara/reportbuilder:managescheduledreports'],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'exportdestination',
            get_string('exportdestination', 'rb_source_scheduled_reports'),
            "{$join}.exporttofilesystem",
            [
                'displayfunc' => 'report_export_destination',
                'joins' => $join,
            ]
        );

        $from = "FROM {report_builder_schedule_email_audience} schedule_recipient_audience
                 JOIN {cohort} cohort ON cohort.id = schedule_recipient_audience.cohortid
                WHERE {$join}.id = schedule_recipient_audience.scheduleid";
        $concat = $DB->sql_group_concat('schedule_recipient_audience.cohortid', ',', 'cohort.name ASC');
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'schedule_audience',
            get_string('schedule_audience', 'rb_source_scheduled_reports'),
            "(SELECT $concat $from)",
            [
                'displayfunc' => 'report_schedule_audiences',
                'capability' => ['moodle/cohort:view'],
                'iscompound' => true,
                'issubquery' => true,
            ]
        );
        $from = "FROM {report_builder_schedule_email_systemuser} schedule_recipient_systemuser
                 JOIN {user} u ON u.id = schedule_recipient_systemuser.userid AND u.deleted = 0
                WHERE {$join}.id = schedule_recipient_systemuser.scheduleid";
        $concat = $DB->sql_group_concat('schedule_recipient_systemuser.userid', ',', 'u.lastname ASC, u.firstname ASC');
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'schedule_systemuser',
            get_string('schedule_systemuser', 'rb_source_scheduled_reports'),
            "(SELECT $concat $from)",
            [
                'displayfunc' => 'report_schedule_systemusers',
                'capability' => ['moodle/user:viewdetails'],
                'iscompound' => true,
                'issubquery' => true,
            ]
        );
        $from = "FROM {report_builder_schedule_email_external} schedule_recipient_external
                WHERE {$join}.id = schedule_recipient_external.scheduleid";
        $concat = $DB->sql_group_concat('schedule_recipient_external.email', ',', 'schedule_recipient_external.email ASC');
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'schedule_external',
            get_string('schedule_external', 'rb_source_scheduled_reports'),
            "(SELECT $concat $from)",
            [
                'displayfunc' => 'plaintext',
                'iscompound' => true,
                'issubquery' => true,
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'user_modified',
            get_string('user_modified', 'rb_source_scheduled_reports'),
            $DB->sql_concat_join("' '", $usednamefields),
            [
                'joins' => 'moduser',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'user',
                'extrafields' => $allnamefields
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'schedule',
            'last_modified',
            get_string('last_modified', 'rb_source_scheduled_reports'),
            "{$join}.lastmodified",
            [
                'displayfunc' => 'nice_datetime'
            ]
        );
    }

    /**
     * Add report schedule filters.
     */
    protected function add_report_schedule_filters() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        /** @var report_schedule_trait|\rb_base_source $this */
        $this->filteroptions[] = new \rb_filter_option(
            'schedule',
            'format',
            get_string('format', 'rb_source_scheduled_reports'),
            'select',
            [
                'selectchoices' => reportbuilder_get_export_options(),
                'simplemode' => true,
            ]
        );
    }

}
