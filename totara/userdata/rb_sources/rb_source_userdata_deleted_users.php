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
 * Reportbuildersource for deleted users.
 */
final class rb_source_userdata_deleted_users extends rb_base_source {
    public function __construct() {
        $this->usedcomponents[] = 'totara_userdata';
        $this->base = "{user}";
        $this->sourcewhere = " base.deleted = 1 ";
        $this->sourceparams = array();
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = array();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = array();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_userdata_deleted_users');

        $this->cacheable = false;

        $this->purge_irrelevant_data();

        parent::__construct();
    }

    /**
     * We cannot apply restrictions to deleted users.
     * @return null|bool always false
     */
    public function global_restrictions_supported() {
        return false;
    }

    /**
     * Purge column and filter options that are not applicable to deleted users.
     */
    protected function purge_irrelevant_data() {
        foreach ($this->columnoptions as $key => $columnoption) {
            if ($columnoption->type === 'user' and $columnoption->value === 'usercohortids') {
                unset($this->columnoptions[$key]);
                continue;
            }
        }
        foreach ($this->filteroptions as $key => $filteroption) {
            if ($filteroption->type === 'user' and $filteroption->value === 'usercohortids') {
                unset($this->filteroptions[$key]);
                continue;
            }
        }
    }

    protected function define_joinlist() {
        $joinlist = array();

        $joinlist[] = new rb_join(
            'user_extra',
            'LEFT',
            '{totara_userdata_user}',
            'base.id = user_extra.userid');

        $joinlist[] = new rb_join(
            'suspended_purge_type',
            'LEFT',
            '{totara_userdata_purge_type}',
            'user_extra.suspendedpurgetypeid = suspended_purge_type.id',
            null,
            'user_extra');

        $joinlist[] = new rb_join(
            'deleted_purge_type',
            'LEFT',
            '{totara_userdata_purge_type}',
            'user_extra.deletedpurgetypeid = deleted_purge_type.id',
            null,
            'user_extra');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $usednamefields = totara_get_all_user_name_fields_join('base', null, true);

        $columnoptions = array();

        $this->add_core_user_columns($columnoptions, 'base');

        $columnoptions[] = new rb_column_option(
            'suspended_purge_type',
            'fullname',
            get_string('suspendedpurgetype', 'totara_userdata'),
            'suspended_purge_type.fullname',
            array(
                'displayfunc' => 'purge_type_fullnamelink',
                'extrafields' => array('id' => "suspended_purge_type.id"),
                'joins' => array('suspended_purge_type')
            )
        );

        $columnoptions[] = new rb_column_option(
            'suspended_purge_type',
            'id',
            'ID',
            'suspended_purge_type.id',
            array(
                'addtypetoheading' => true,
                'joins' => array('suspended_purge_type'),
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'deleted_purge_type',
            'fullname',
            get_string('deletedpurgetype', 'totara_userdata'),
            'deleted_purge_type.fullname',
            array(
                'displayfunc' => 'purge_type_fullnamelink',
                'extrafields' => array('id' => "deleted_purge_type.id"),
                'joins' => array('deleted_purge_type')
            )
        );

        $columnoptions[] = new rb_column_option(
            'deleted_purge_type',
            'id',
            'ID',
            'deleted_purge_type.id',
            array(
                'addtypetoheading' => true,
                'joins' => array('deleted_purge_type'),
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'user',
            'actions',
            get_string('actions', 'totara_userdata'),
            'base.id',
            array(
                'displayfunc' => 'deleted_user_actions',
                'noexport' => true,
                'nosort' => true,
                'extrafields' => array(
                    'fullname' => $DB->sql_concat_join("' '", $usednamefields),
                    'username' => 'base.username',
                    'email' => 'base.email',
                    'deleted' => 'base.deleted',
                    'idnumber' => 'base.idnumber',
                    'totarasync' => 'base.totarasync',
                )
            )
        );

        return $columnoptions;
    }

    public function rb_filter_purge_type_suspended_list() {
        global $DB;
        $options = $DB->get_records_menu('totara_userdata_purge_type', array('userstatus' => \totara_userdata\userdata\target_user::STATUS_SUSPENDED), '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);
        return $options;
    }

    public function rb_filter_purge_type_deleted_list() {
        global $DB;
        $options = $DB->get_records_menu('totara_userdata_purge_type', array('userstatus' => \totara_userdata\userdata\target_user::STATUS_DELETED), '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);
        return $options;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_core_user_filters($filteroptions);

        $filteroptions[] = new rb_filter_option(
            'suspended_purge_type',
            'id',
            get_string('suspendedpurgetype', 'totara_userdata'),
            'select',
            array(
                'selectfunc' => 'purge_type_suspended_list',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'deleted_purge_type',
            'id',
            get_string('deletedpurgetype', 'totara_userdata'),
            'select',
            array(
                'selectfunc' => 'purge_type_deleted_list',
            )
        );

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        return array(
            array('type' => 'user', 'value' => 'id'),
            array('type' => 'user', 'value' => 'fullname'),
            array('type' => 'user', 'value' => 'username'),
            array('type' => 'user', 'value' => 'idnumber'),
            array('type' => 'user', 'value' => 'emailunobscured'),
        );
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
