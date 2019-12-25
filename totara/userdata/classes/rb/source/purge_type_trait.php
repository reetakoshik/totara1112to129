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
 * Trait purge_type_trait
 *
 * @property string[] $usedcomponents
 * @property rb_join[] $joinlist
 * @property rb_column_option[] $columnoptions
 * @property rb_filter_option[] $filteroptions
 */
trait purge_type_trait {
    /** @var string $purgetypejoin */
    protected $purgetypejoin = null;

    /**
     * Add purge_type info
     */
    protected function add_purge_type_to_base() {
        if (isset($this->purgetypejoin)) {
            throw new \coding_exception('purge_type info can be added only once!');
        }
        $this->purgetypejoin = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_userdata', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_userdata';
        }

        $this->add_purge_type_joins();
        $this->add_purge_type_columns();
        $this->add_purge_type_filters();
    }

    /**
     * Add purge_type info
     *
     * @param rb_join $join
     */
    protected function add_purge_type(rb_join $join) {
        if (isset($this->purgetypejoin)) {
            throw new \coding_exception('purge_type info can be added only once!');
        }
        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->purgetypejoin = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_userdata', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_userdata';
        }

        $this->add_purge_type_joins();
        $this->add_purge_type_columns();
        $this->add_purge_type_filters();
    }

    /**
     * Add purge_type joins.
     */
    protected function add_purge_type_joins() {
        $join = $this->purgetypejoin;
    }

    /**
     * Add purge_type columns.
     */
    protected function add_purge_type_columns() {
        $join = $this->purgetypejoin;

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
            'id',
            'ID',
            "$join.id",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'joins' => array($join),
                'displayfunc' => 'integer'
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
            'userstatus',
            get_string('purgetypeuserstatus', 'totara_userdata'),
            "$join.userstatus",
            array(
                'displayfunc' => 'purge_type_userstatus',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
            'fullname',
            get_string('fullname', 'totara_userdata'),
            "$join.fullname",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'defaultheading' => get_string('purgetype', 'totara_userdata'),
                'dbdatatype' => 'char',
                'displayfunc' => 'format_string',
                'outputformat' => 'text',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
            'fullnamelink',
            get_string('fullnamelink', 'totara_userdata'),
            "$join.fullname",
            array(
                'defaultheading' => get_string('purgetype', 'totara_userdata'),
                'dbdatatype' => 'char',
                'displayfunc' => 'purge_type_fullnamelink',
                'extrafields' => array('id' => "$join.id"),
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
            'idnumber',
            get_string('idnumber'),
            "$join.idnumber",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'dbdatatype' => 'char',
                'displayfunc' => 'plaintext',
                'outputformat' => 'text',
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
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
            'purge_type',
            'availablefor',
            get_string('purgetypeavailablefor', 'totara_userdata'),
            "$join.id",
            array(
                'displayfunc' => 'purge_type_availablefor',
                'extrafields' => array('allowmanual' => "$join.allowmanual", 'allowdeleted' => "$join.allowdeleted", 'allowsuspended' => "$join.allowsuspended"),
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
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
            'purge_type',
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
            'purge_type',
            'newitems',
            get_string('newitems', 'totara_userdata'),
            "$join.id",
            array(
                'displayfunc' => 'purge_type_newitems',
                'nosort' => true,
                'joins' => array($join),
            )
        );

        $this->columnoptions[] = new \rb_column_option(
            'purge_type',
            'actions',
            get_string('actions', 'totara_userdata'),
            "$join.id",
            array(
                'addtypetoheading' => ($join !== 'base'),
                'displayfunc' => 'purge_type_actions',
                'nosort' => true,
                'noexport' => true,
                'joins' => array($join),
            )
        );
    }

    /**
     * @return string[]
     */
    public function rb_filter_purge_type_list() {
        global $DB;
        $options = $DB->get_records_menu('totara_userdata_purge_type', array(), '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);
        return $options;
    }

    /**
     * Add purge_type filters.
     */
    protected function add_purge_type_filters() {
        $join = $this->purgetypejoin;

        $this->filteroptions[] = new \rb_filter_option(
            'purge_type',
            'fullname',
            get_string('fullname', 'totara_userdata'),
            'text',
            array(
                'addtypetoheading' => ($join !== 'base'),
            )
        );

        $this->filteroptions[] = new \rb_filter_option(
            'purge_type',
            'id',
            get_string('purgetype', 'totara_userdata'),
            'select',
            array(
                'selectfunc' => 'purge_type_list',
            )
        );

        $this->filteroptions[] = new \rb_filter_option(
            'purge_type',
            'idnumber',
            get_string('idnumber'),
            'text',
            array(
                'addtypetoheading' => ($join !== 'base'),
            )
        );

    }
}
