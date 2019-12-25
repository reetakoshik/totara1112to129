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
 * Trait export_type_trait
 *
 * @property string[] $usedcomponents
 * @property rb_join[] $joinlist
 * @property rb_column_option[] $columnoptions
 * @property rb_filter_option[] $filteroptions
 */
trait export_type_trait {
    /** @var string $exporttypejoin */
    protected $exporttypejoin = null;

    /**
     * Add export_type info
     */
    protected function add_export_type_to_base() {
        if (isset($this->exporttypejoin)) {
            throw new \coding_exception('export_type info can be added only once!');
        }
        $this->exporttypejoin = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_userdata', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_userdata';
        }

        $this->add_export_type_joins();
        $this->add_export_type_columns();
        $this->add_export_type_filters();
    }

    /**
     * Add export_type info
     *
     * @param rb_join $join
     */
    protected function add_export_type(rb_join $join) {
        if (isset($this->exporttypejoin)) {
            throw new \coding_exception('export_type info can be added only once!');
        }
        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->exporttypejoin = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_userdata', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_userdata';
        }

        $this->add_export_type_joins();
        $this->add_export_type_columns();
        $this->add_export_type_filters();
    }

    /**
     * Add export_type joins.
     */
    protected function add_export_type_joins() {
        $join = $this->exporttypejoin;
    }

    /**
     * Add export_type columns.
     */
    protected function add_export_type_columns() {
        $join = $this->exporttypejoin;

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'id',
            'ID',
            "$join.id",
            array(
                'joins' => array($join),
                'displayfunc' => 'integer'
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'fullname',
            get_string('fullname', 'totara_userdata'),
            "$join.fullname",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'defaultheading' => get_string('exporttype', 'totara_userdata'),
                'dbdatatype' => 'char',
                'displayfunc' => 'format_string',
                'outputformat' => 'text',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'fullnamelink',
            get_string('fullnamelink', 'totara_userdata'),
            "$join.fullname",
            array(
                'defaultheading' => get_string('exporttype', 'totara_userdata'),
                'dbdatatype' => 'char',
                'displayfunc' => 'export_type_fullnamelink',
                'extrafields' => array('id' => "$join.id"),
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'idnumber',
            get_string('idnumber'),
            "$join.idnumber",
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'plaintext',
                'outputformat' => 'text',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'description',
            get_string('description'),
            "$join.description",
            array(
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'joins' => array($join),
                'displayfunc' => 'format_text'
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'availablefor',
            get_string('exporttypeavailablefor', 'totara_userdata'),
            "$join.id",
            array(
                'displayfunc' => 'export_type_availablefor',
                'extrafields' => array('allowself' => "$join.allowself"),
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'includefiledir',
            get_string('exportincludefiledir', 'totara_userdata'),
            "$join.includefiledir",
            array(
                'displayfunc' => 'yes_or_no',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
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

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'timechanged',
            get_string('timechanged', 'totara_userdata'),
            "$join.timechanged",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'newitems',
            get_string('newitems', 'totara_userdata'),
            "$join.id",
            array(
                'displayfunc' => 'export_type_newitems',
                'nosort' => true,
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'export_type',
            'actions',
            get_string('actions', 'totara_userdata'),
            "$join.id",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'displayfunc' => 'export_type_actions',
                'nosort' => true,
                'noexport' => true,
                'joins' => array($join),
            )
        );
    }

    /**
     * @return string[]
     */
    public function rb_filter_export_type_list() {
        global $DB;
        $options = $DB->get_records_menu('totara_userdata_export_type', array(), '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);
        return $options;
    }

    /**
     * Add export_type filters.
     */
    protected function add_export_type_filters() {
        $join = $this->exporttypejoin;

        $this->filteroptions[] = new \rb_filter_option(
            'export_type',
            'fullname',
            get_string('fullname'),
            'text',
            array(
                'addtypetoheading' => ($join !== 'base'),
            )
        );

        $this->filteroptions[] = new \rb_filter_option(
            'export_type',
            'id',
            get_string('exporttype', 'totara_userdata'),
            'select',
            array(
                'selectfunc' => 'export_type_list',
            )
        );

        $this->filteroptions[] = new \rb_filter_option(
            'export_type',
            'idnumber',
            get_string('idnumber'),
            'text',
            array(
                'addtypetoheading' => ($join !== 'base'),
            )
        );
    }
}
