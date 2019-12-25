<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/certification/lib.php');

class rb_source_certification_membership extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = $this->define_base();
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->usedcomponents[] = 'totara_certification';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_certification_membership');

        parent::__construct();
    }

    private function define_base() {
        global $DB;

        $uniqueid = $DB->sql_concat_join("','", array('ccall.userid', 'prog.id'));

        return "(SELECT " . $uniqueid . " AS id, ccall.userid, ccall.certifid, prog.id AS programid
                   FROM (SELECT cc.userid, cc.certifid
                           FROM {certif_completion} cc
                          UNION
                         SELECT cch.userid, cch.certifid
                           FROM {certif_completion_history} cch) ccall
                   JOIN {prog} prog ON ccall.certifid = prog.certifid)";
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {
        $joinlist = array();

        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        $this->add_certification_table_to_joinlist($joinlist, 'base', 'certifid');

        $joinlist[] = new rb_join(
            'certif_completion',
            'LEFT',
            '{certif_completion}',
            "certif_completion.userid = base.userid AND certif_completion.certifid = base.certifid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $this->add_user_fields_to_columns($columnoptions);
        $this->add_certification_fields_to_columns($columnoptions, 'certif', 'totara_certification');

        $columnoptions[] = new rb_column_option(
            'certmembership',
            'status',
            get_string('status', 'rb_source_certification_membership'),
            'certif_completion.status',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certif_status',
            )
        );
        $columnoptions[] = new rb_column_option(
            'certmembership',
            'iscertified',
            get_string('iscertified', 'rb_source_certification_membership'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('iscertified', 'rb_source_certification_membership'),
            )
        );
        $columnoptions[] = new rb_column_option(
            'certmembership',
            'isassigned',
            get_string('isassigned', 'rb_source_certification_membership'),
            'CASE WHEN certif_completion.id IS NOT NULL THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
            )
        );
        $columnoptions[] = new rb_column_option(
            'certmembership',
            'editcompletion',
            get_string('editcompletion', 'rb_source_certification_membership'),
            'base.id',
            array(
                'joins' => array('certif_completion'),
                'displayfunc' => 'edit_completion',
                'extrafields' => array(
                    'userid' => 'base.userid',
                    'progid' => 'base.programid',
                ),
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_user_fields_to_filters($filteroptions);
        $this->add_certification_fields_to_filters($filteroptions, 'totara_certification');

        $filteroptions[] = new rb_filter_option(
            'certmembership',
            'status',
            get_string('status', 'rb_source_certification_membership'),
            'select',
            array(
                'selectfunc' => 'status',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certmembership',
            'isassigned',
            get_string('isassigned', 'rb_source_certification_membership'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'certmembership',
            'iscertified',
            get_string('iscertified', 'rb_source_certification_membership'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );

        return $filteroptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'programid',
                'base.programid',
                'base'
            ),
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'certmembership',
                'value' => 'status',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'certmembership',
                'value' => 'status',
                'advanced' => 0,
            ),
        );
        return $defaultfilters;
    }

    public function rb_filter_status() {
        global $CERTIFSTATUS;

        $out = array();
        foreach ($CERTIFSTATUS as $code => $statusstring) {
            $out[$code] = get_string($statusstring, 'totara_certification');
        }
        return $out;
    }

    public function rb_display_edit_completion($id, $row, $isexport) {
        // Ignores $id == certif_completion id, because the user might have been unassigned and only history records exist.
        if ($isexport) {
            return get_string('editcompletion', 'rb_source_certification_membership');
        }

        $url = new moodle_url('/totara/certification/edit_completion.php',
            array('id' => $row->progid, 'userid' => $row->userid));
        return html_writer::link($url, get_string('editcompletion', 'rb_source_certification_membership'));
    }
}
