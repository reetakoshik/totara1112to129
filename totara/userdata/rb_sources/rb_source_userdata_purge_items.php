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

defined('MOODLE_INTERNAL') || die();

/**
 * Reportbuildersource for items that were purged.
 */
final class rb_source_userdata_purge_items extends rb_base_source {
    use \totara_userdata\rb\source\purge_trait,
        \totara_userdata\rb\source\purge_type_trait;

    public function __construct() {
        $this->usedcomponents[] = 'totara_userdata';
        $this->base = '{totara_userdata_purge_item}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = array();

        // NOTE: 'purge' is a reserved word in SQL
        $this->add_purge(new rb_join('xpurge', 'INNER', '{totara_userdata_purge}', 'xpurge.id = base.purgeid'));
        $this->add_purge_type(new rb_join('purge_type', 'INNER', '{totara_userdata_purge_type}', 'xpurge.purgetypeid = purge_type.id', null, 'xpurge'));

        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters =  $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_userdata_purge_items');

        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Are global restrictions implemented?
     * @return null|bool
     */
    public function global_restrictions_supported() {
        // Not easy because deleted users cannot be cohort members.
        return false;
    }

    protected function define_joinlist() {
        $joinlist = array();
        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'purge_item',
            'id',
            'ID',
            "base.id",
            array('displayfunc' => 'integer')
        );

        $columnoptions[] = new rb_column_option(
            'purge_item',
            'name',
            get_string('itemname', 'totara_userdata'),
            "base.name",
            array('displayfunc' => 'format_string')
        );

        $columnoptions[] = new rb_column_option(
            'purge_item',
            'fullname',
            get_string('itemfullname', 'totara_userdata'),
            "base.name",
            array(
                'nosort' => true,
                'displayfunc' => 'purge_item_fullname',
                'extrafields' => array('component' => "base.component"),
            )
        );

        $columnoptions[] = new rb_column_option(
            'purge_item',
            'component',
            get_string('itemcomponent', 'totara_userdata'),
            "base.component",
            array('displayfunc' => 'plaintext')
        );

        $columnoptions[] = new rb_column_option(
            'purge_item',
            'result',
            get_string('result', 'totara_userdata'),
            "base.result",
            array(
                'displayfunc' => 'execution_result',
            )
        );

        $columnoptions[] = new rb_column_option(
            'purge_item',
            'timestarted',
            get_string('timestarted', 'totara_userdata'),
            "base.timestarted",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'purge_item',
            'timefinished',
            get_string('timefinished', 'totara_userdata'),
            "base.timefinished",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'purge_item',
            'name',
            get_string('itemname', 'totara_userdata'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'purge_item',
            'component',
            get_string('itemcomponent', 'totara_userdata'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'purge_item',
            'timestarted',
            get_string('timestarted', 'totara_userdata'),
            'date',
            array(
                'includetime' => true,
            )
        );

        $filteroptions[] = new rb_filter_option(
            'purge_item',
            'timefinished',
            get_string('timefinished', 'totara_userdata'),
            'date',
            array(
                'includetime' => true,
            )
        );

        $filteroptions[] = new rb_filter_option(
            'purge_item',
            'result',
            get_string('result', 'totara_userdata'),
            'multicheck',
            array(
                'selectfunc' => 'purge_item_results',
                'simplemode' => true,
            )
        );

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        return array(
            array('type' => 'user', 'value' => 'fullname'),
            array('type' => 'purge', 'value' => 'origin'),
            array('type' => 'purge_item', 'value' => 'name'),
            array('type' => 'purge_item', 'value' => 'component'),
            array('type' => 'purge_item', 'value' => 'timefinished'),
            array('type' => 'purge_item', 'value' => 'result'),
        );
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option('purgeid', 'base.purgeid'),
            new rb_param_option('userid', 'xpurge.userid', array('xpurge')),
        );

        return $paramoptions;
    }

    public function rb_filter_purge_item_results() {
        return \totara_userdata\userdata\manager::get_results();
    }

    /**
     * Returns expected result for column_test.
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        return 0;
    }
}
