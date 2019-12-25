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
 * Reportbuildersource for data purges.
 */
final class rb_source_userdata_purges extends rb_base_source {
    use \totara_userdata\rb\source\purge_trait,
        \totara_userdata\rb\source\purge_type_trait;

    public function __construct() {
        $this->usedcomponents[] = 'totara_userdata';
        $this->base = '{totara_userdata_purge}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = array();

        $this->add_purge_to_base();
        $this->add_purge_type(new rb_join('purge_type', 'INNER', '{totara_userdata_purge_type}', 'base.purgetypeid = purge_type.id'));

        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters =  $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_userdata_purges');

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
        return array();
    }

    protected function define_columnoptions() {
        return array();
    }

    protected function define_filteroptions() {
        return array();
    }

    protected function define_defaultcolumns() {
        return array(
            array('type' => 'purge', 'value' => 'id'),
            array('type' => 'purge', 'value' => 'timecreated'),
            array('type' => 'purge', 'value' => 'origin'),
            array('type' => 'user', 'value' => 'namelink'),
            array('type' => 'purge_type', 'value' => 'fullnamelink'),
            array('type' => 'purge', 'value' => 'timefinished'),
            array('type' => 'purge', 'value' => 'result'),
            array('type' => 'purge', 'value' => 'actions'),
        );
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option('userid', 'base.userid'),
            new rb_param_option('purgetypeid', 'base.purgetypeid'),
        );

        return $paramoptions;
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
