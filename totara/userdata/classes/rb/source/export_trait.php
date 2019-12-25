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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\rb\source;

use rb_column_option;
use rb_filter_option;
use rb_join;

/**
 * Trait export_trait
 *
 * @property string[] $usedcomponents
 * @property rb_join[] $joinlist
 * @property rb_column_option[] $columnoptions
 * @property rb_filter_option[] $filteroptions
 */
trait export_trait {
    /** @var string $exportjoin */
    protected $exportjoin = null;

    /**
     * Add export info
     */
    protected function add_export_to_base() {
        if (isset($this->exportjoin)) {
            throw new \coding_exception('export info can be added only once!');
        }
        $this->exportjoin = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_userdata', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_userdata';
        }

        $this->add_export_joins();
        $this->add_export_columns();
        $this->add_export_filters();
    }

    /**
     * Add export info
     *
     * @param rb_join $join
     */
    protected function add_export(rb_join $join) {
        if (isset($this->exportjoin)) {
            throw new \coding_exception('export info can be added only once!');
        }
        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->exportjoin = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_userdata', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_userdata';
        }

        $this->add_export_joins();
        $this->add_export_columns();
        $this->add_export_filters();
    }

    /**
     * Add export joins.
     */
    protected function add_export_joins() {
        $join = $this->exportjoin;

        $this->add_core_user_tables($this->joinlist, $join, 'userid');
        $this->add_core_user_tables($this->joinlist, $join, 'usercreated', 'usercreated');
    }

    /**
     * Add export columns.
     */
    protected function add_export_columns() {
        $join = $this->exportjoin;

        $this->columnoptions[] = new rb_column_option(
            'export',
            'id',
            'ID',
            "$join.id",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'joins' => array($join),
                'displayfunc' => 'integer'
            )
        );

        $this->columnoptions[] = new rb_column_option(
            'export',
            'origin',
            get_string('exportorigin', 'totara_userdata'),
            "$join.origin",
            array(
                'displayfunc' => 'export_origin',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new rb_column_option(
            'export',
            'contextid',
            get_string('incontextid', 'totara_userdata'),
            "$join.contextid",
            array(
                'joins' => array($join),
                'displayfunc' => 'integer'
            )
        );

        $this->columnoptions[] = new rb_column_option(
            'export',
            'timecreated',
            get_string('timecreated', 'totara_userdata'),
            "$join.timecreated",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new rb_column_option(
            'export',
            'timestarted',
            get_string('timestarted', 'totara_userdata'),
            "$join.timestarted",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new rb_column_option(
            'export',
            'timefinished',
            get_string('timefinished', 'totara_userdata'),
            "$join.timefinished",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new rb_column_option(
            'export',
            'result',
            get_string('result', 'totara_userdata'),
            "$join.result",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'displayfunc' => 'execution_result',
                'joins' => array($join),
            )
        );

        $this->add_core_user_columns($this->columnoptions);
        $this->add_core_user_columns($this->columnoptions, 'usercreated', 'usercreated', true);

        // A bit of hackery to get links to user info page instead of profile.
        foreach ($this->columnoptions as $columnotion) {
            if ($columnotion->type === 'user' and $columnotion->value === 'namelink') {
                $columnotion->displayfunc = 'link_user_info';
                break;
            }
        }
    }

    /**
     * @return string[]
     */
    public function rb_filter_export_list() {
        global $DB;
        $options = $DB->get_records_menu('totara_userdata_export', array(), '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);
        return $options;
    }

    /**
     * Add export filters.
     */
    protected function add_export_filters() {
        $join = $this->exportjoin;

        $this->filteroptions[] = new rb_filter_option(
            'export',
            'origin',
            get_string('exportorigin', 'totara_userdata'),
            'multicheck',
            array(
                'selectfunc' => 'export_origins',
                'simplemode' => true
            )
        );

        $this->filteroptions[] = new rb_filter_option(
            'export',
            'timecreated',
            get_string('timecreated', 'totara_userdata'),
            'date',
            array(
                'addtypetoheading' => ($join !== 'base'),
                'includetime' => true,
            )
        );

        $this->filteroptions[] = new rb_filter_option(
            'export',
            'timestarted',
            get_string('timestarted', 'totara_userdata'),
            'date',
            array(
                'addtypetoheading' => ($join !== 'base'),
                'includetime' => true,
            )
        );

        $this->filteroptions[] = new rb_filter_option(
            'export',
            'timefinished',
            get_string('timefinished', 'totara_userdata'),
            'date',
            array(
                'addtypetoheading' => ($join !== 'base'),
                'includetime' => true,
            )
        );

        $this->filteroptions[] = new rb_filter_option(
            'export',
            'result',
            get_string('result', 'totara_userdata'),
            'multicheck',
            array(
                'addtypetoheading' => ($join !== 'base'),
                'selectfunc' => 'export_results',
                'simplemode' => true
            )
        );

        $this->add_core_user_filters($this->filteroptions);
        $this->add_core_user_filters($this->filteroptions, 'usercreated', true);
    }

    /**
     * @return string[]
     */
    public function rb_filter_export_results() {
        return \totara_userdata\userdata\manager::get_results();
    }

    /**
     * @return string[]
     */
    public function rb_filter_export_origins() {
        return \totara_userdata\local\export::get_origins();
    }
}
