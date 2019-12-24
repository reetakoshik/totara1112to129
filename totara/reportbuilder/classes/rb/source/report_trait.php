<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * Trait report_trait
 */
trait report_trait {

    /** @var string $reportjoin */
    protected $reportjoin = null;

    /**
     * Add report info
     */
    protected function add_report_to_base() {
        /** @var report_trait|\rb_base_source $this */
        if (isset($this->reportjoin)) {
            throw new \coding_exception('Report info can be added only once!');
        }
        $this->reportjoin = 'base';

        $this->add_report_joins();
        $this->add_report_columns();
        $this->add_report_filters();
    }

    /**
     * Add report info
     *
     * @param \rb_join $join
     */
    protected function add_report(\rb_join $join) {
        /** @var report_trait|\rb_base_source $this */
        if (isset($this->reportjoin)) {
            throw new \coding_exception('Report info can be added only once!');
        }
        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->reportjoin = $join->name;

        $this->add_report_joins();
        $this->add_report_columns();
        $this->add_report_filters();
    }

    /**
     * Add report joins.
     */
    protected function add_report_joins() {
        /** @var report_trait|\rb_base_source $this */
        $join = $this->reportjoin;

        $this->joinlist[] = new \rb_join(
            'column_count',
            'LEFT',
            "(SELECT reportid, count(id) AS count
                FROM {report_builder_columns}
                GROUP BY reportid)",
            "{$join}.id = column_count.reportid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
        $this->joinlist[] = new \rb_join(
            'filter_count',
            'LEFT',
            "(SELECT reportid, count(id) AS count
                FROM {report_builder_filters}
                GROUP BY reportid)",
            "{$join}.id = filter_count.reportid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
        $this->joinlist[] = new \rb_join(
            'scheduled_count',
            'LEFT',
            "(SELECT reportid, count(id) AS count
                FROM {report_builder_schedule}
                GROUP BY reportid)",
            "{$join}.id = scheduled_count.reportid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
        $this->joinlist[] = new \rb_join(
            'saved_count',
            'LEFT',
            "(SELECT reportid, count(id) AS count
                FROM {report_builder_saved}
                GROUP BY reportid)",
            "{$join}.id = saved_count.reportid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
    }

    /**
     * Add report columns.
     */
    protected function add_report_columns() {
        /** @var report_trait|\rb_base_source $this */
        $join = $this->reportjoin;

        $this->columnoptions[] = new \rb_column_option(
            'report',
            'name',
            get_string('reportname', 'totara_reportbuilder'),
            "{$join}.fullname",
            [
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'namelinkedit',
            get_string('reportnamelinkedit', 'totara_reportbuilder'),
            "{$join}.fullname",
            [
                'displayfunc' => 'report_link_edit',
                'defaultheading' => get_string('reportname', 'totara_reportbuilder'),
                'extrafields' => [
                    'id' => "{$join}.id",
                ],
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'namelinkview',
            get_string('reportnamelinkview', 'totara_reportbuilder'),
            "{$join}.fullname",
            [
                'displayfunc' => 'report_link_view',
                'defaultheading' => get_string('reportname', 'totara_reportbuilder'),
                'extrafields' => [
                    'id' => "{$join}.id",
                    'embedded' => "{$join}.embedded",
                    'shortname' => "{$join}.shortname",
                ],
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'namelinkeditview',
            get_string('reportnamelinkeditview', 'totara_reportbuilder'),
            "{$join}.fullname",
            [
                'displayfunc' => 'report_link_edit_and_view',
                'defaultheading' => get_string('reportname', 'totara_reportbuilder'),
                'extrafields' => [
                    'id' => "{$join}.id",
                    'embedded' => "{$join}.embedded",
                    'shortname' => "{$join}.shortname",
                ],
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'id',
            get_string('reportid', 'totara_reportbuilder'),
            "{$join}.id",
            [
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'embedded',
            get_string('reportembedded', 'totara_reportbuilder'),
            "{$join}.embedded",
            [
                'displayfunc' => 'yes_no',
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'hidden',
            get_string('reporthidden', 'totara_reportbuilder'),
            "{$join}.hidden",
            [
                'displayfunc' => 'yes_no',
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'source',
            get_string('reportsource', 'totara_reportbuilder'),
            "{$join}.source",
            [
                'displayfunc' => 'report_source_name',
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'actions',
            get_string('reportactions', 'totara_reportbuilder'),
            "{$join}.id",
            [
                'displayfunc' => 'report_manage_actions',
                'extrafields' => ['embedded' => "{$join}.embedded", 'cache' => "{$join}.cache"],
                'noexport' => true,
                'nosort' => true,
                'capability' => ['totara/reportbuilder:managereports', 'totara/reportbuilder:manageembeddedreports'],
                'joins' => [$join],
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'columncount',
            get_string('numcolumns', 'totara_reportbuilder'),
            'CASE WHEN column_count.count IS NULL THEN 0 ELSE column_count.count END',
            [
                'joins' => 'column_count'
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'filtercount',
            get_string('numfilters', 'totara_reportbuilder'),
            'CASE WHEN filter_count.count IS NULL THEN 0 ELSE filter_count.count END',
            [
                'joins' => 'filter_count'
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'scheduledcount',
            get_string('numscheduled', 'totara_reportbuilder'),
            'CASE WHEN scheduled_count.count IS NULL THEN 0 ELSE scheduled_count.count END',
            [
                'joins' => 'scheduled_count'
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'savedcount',
            get_string('numsaved', 'totara_reportbuilder'),
            'CASE WHEN saved_count.count IS NULL THEN 0 ELSE saved_count.count END',
            [
                'joins' => 'saved_count'
            ]
        );
        $this->columnoptions[] = new \rb_column_option(
            'report',
            'globalrestrictions',
            get_string('globalrestriction', 'totara_reportbuilder'),
            "{$join}.globalrestriction",
            [
                'displayfunc' => 'report_global_restrictions',
                'extrafields' => ['source' => "{$join}.source"],
                'joins' => [$join],
            ]
        );
    }

    /**
     * Add report filters.
     */
    protected function add_report_filters() {
        global $CFG;

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        // Get list of sources. Need the static var to avoid recursion as get_source_list() instanitates each source.
        // Can be fixed by making it possible to get source name without instantiating source object.
        static $sourcelist;
        if (is_null($sourcelist)) {
            $sourcelist = [];
            $sourcelist = \reportbuilder::get_source_list();
        }

        /** @var report_trait|\rb_base_source $this */
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'name',
            get_string('reportname', 'totara_reportbuilder'),
            'text'
        );
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'embedded',
            get_string('reportembedded', 'totara_reportbuilder'),
            'select',
            [
                'selectchoices' => [1 => get_string('yes'), 0 => get_string('no')],
                'simplemode' => true,
            ]
        );
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'hidden',
            get_string('reporthidden', 'totara_reportbuilder'),
            'select',
            [
                'selectchoices' => [1 => get_string('yes'), 0 => get_string('no')],
                'simplemode' => true,
            ]
        );
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'source',
            get_string('reportsource', 'totara_reportbuilder'),
            'select',
            [
                'selectchoices' => $sourcelist,
                'simplemode' => true,
            ]
        );
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'columncount',
            get_string('numcolumns', 'totara_reportbuilder'),
            'number'
        );
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'filtercount',
            get_string('numfilters', 'totara_reportbuilder'),
            'number'
        );
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'scheduledcount',
            get_string('numscheduled', 'totara_reportbuilder'),
            'number'
        );
        $this->filteroptions[] = new \rb_filter_option(
            'report',
            'savedcount',
            get_string('numsaved', 'totara_reportbuilder'),
            'number'
        );
    }

}
