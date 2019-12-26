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
 * Reportbuildersource for items in purge types.
 */
final class rb_source_userdata_purge_type_items extends rb_base_source {
    use \totara_userdata\rb\source\purge_type_trait;

    public function __construct() {
        $this->usedcomponents[] = 'totara_userdata';
        $this->base = '{totara_userdata_purge_type_item}';
        $this->joinlist = array();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();

        $this->add_purge_type(new rb_join('purge_type', 'INNER', '{totara_userdata_purge_type}', 'base.purgetypeid = purge_type.id'));

        $this->contentoptions = array();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = array();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_userdata_purge_type_items');

        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Are global restrictions implemented?
     * @return null|bool always false
     */
    public function global_restrictions_supported() {
        // Not related to individual users.
        return false;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'purge_type_item',
            'id',
            'ID',
            'base.id',
            array('displayfunc' => 'integer')
        );

        $columnoptions[] = new rb_column_option(
            'purge_type_item',
            'name',
            get_string('itemname', 'totara_userdata'),
            'base.name',
            array('displayfunc' => 'format_string')
        );

        $columnoptions[] = new rb_column_option(
            'purge_type_item',
            'fullname',
            get_string('itemfullname', 'totara_userdata'),
            'base.name',
            array(
                'nosort' => true,
                'displayfunc' => 'purge_item_fullname',
                'extrafields' => array('component' => "base.component"),
            )
        );

        $columnoptions[] = new rb_column_option(
            'purge_type_item',
            'component',
            get_string('itemcomponent', 'totara_userdata'),
            'base.component',
            array('displayfunc' => 'plaintext')
        );

        $columnoptions[] = new rb_column_option(
            'purge_type_item',
            'purgedata',
            get_string('itempurgedata', 'totara_userdata'),
            'base.purgedata',
            array(
                'displayfunc' => 'yes_or_no',
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'purge_type_item',
            'name',
            get_string('itemname', 'totara_userdata'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'purge_type_item',
            'component',
            get_string('itemcomponent', 'totara_userdata'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'purge_type_item',
            'purgedata',
            get_string('itempurgedata', 'totara_userdata'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        return array(
            array('type' => 'purge_type', 'value' => 'fullname'),
            array('type' => 'purge_type_item', 'value' => 'name'),
            array('type' => 'purge_type_item', 'value' => 'component'),
            array('type' => 'purge_type_item', 'value' => 'purgedata'),
        );
    }

    protected function define_paramoptions() {
        $paramoptions = array(
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
