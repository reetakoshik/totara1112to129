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
 * Trait report_saved_trait
 */
trait report_saved_trait {

    /** @var string $reportsavedjoin */
    protected $reportsavedjoin = null;

    /**
     * Add report info
     */
    protected function add_saved_search_to_base() {
        /** @var report_saved_trait|\rb_base_source $this */
        if (isset($this->reportsavedjoin)) {
            throw new \coding_exception('Report saved info can be added only once!');
        }
        $this->reportsavedjoin = 'base';

        $this->add_report_saved_joins();
        $this->add_report_saved_columns();
        $this->add_report_saved_filters();
    }

    /**
     * Add report saved info
     *
     * @param \rb_join $join
     */
    protected function add_saved_search(\rb_join $join) {
        /** @var report_saved_trait|\rb_base_source $this */
        if (isset($this->reportsavedjoin)) {
            throw new \coding_exception('Report saved info can be added only once!');
        }
        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->reportsavedjoin = $join->name;

        $this->add_report_saved_joins();
        $this->add_report_saved_columns();
        $this->add_report_saved_filters();
    }

    /**
     * Add report saved joins.
     */
    protected function add_report_saved_joins() {
        /** @var report_saved_trait|\rb_base_source $this */
        $join = $this->reportsavedjoin;
    }

    /**
     * Add report saved columns.
     */
    protected function add_report_saved_columns() {
        /** @var report_saved_trait|\rb_base_source $this */
        $join = $this->reportsavedjoin;

        $this->columnoptions[] = new \rb_column_option(
            'saved',
            'name',
            get_string('savedsearchname', 'totara_reportbuilder'),
            "{$join}.name",
            [
                'joins' => [$join],
                'displayfunc' => 'format_string'
            ]
        );
    }

    /**
     * Add report saved filters.
     */
    protected function add_report_saved_filters() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        /** @var report_saved_trait|\rb_base_source $this */
        $this->filteroptions[] = new \rb_filter_option(
            'saved',
            'name',
            get_string('name', 'totara_reportbuilder'),
            'text'
        );
    }
}
