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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_org extends rb_base_source {
    function __construct() {
        $this->base = '{org}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_org');
        $this->usedcomponents[] = 'totara_hierarchy';

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {
        global $DB;

        $pathconcatsql = $DB->sql_concat('o.path', "'/'", "'%'");
        $global_restriction_join_ja = $this->get_global_report_restriction_join('ja', 'userid');
        $list = $DB->sql_group_concat_unique($DB->sql_cast_2char('c.fullname'), '<br>');

        $joinlist = array(
            new rb_join(
                'framework',
                'INNER',
                '{org_framework}',
                'base.frameworkid = framework.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'parent',
                'LEFT',
                '{org}',
                'base.parentid = parent.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'comps',
                'LEFT',
                "(SELECT oc.organisationid, {$list} AS list
                    FROM {org_competencies} oc
               LEFT JOIN {comp} c ON oc.competencyid = c.id
                GROUP BY oc.organisationid)",
                'comps.organisationid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'orgtype',
                'LEFT',
                '{org_type}',
                'base.typeid = orgtype.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            // This join is required to keep the joining of org custom fields happy :D
            new rb_join(
                'organisation',
                'INNER',
                '{org}',
                'base.id = organisation.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),

            // A count of all members of this organisation.
            new rb_join(
                'member',
                'LEFT',
                "(SELECT organisationid, COUNT(DISTINCT ja.userid) membercount
                    FROM {job_assignment} ja
                    INNER JOIN {user} u ON u.id = ja.userid
                         {$global_restriction_join_ja}
                   WHERE u.deleted = 0
                GROUP BY ja.organisationid)",
                'base.id = member.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),

            // A count of all members of this organisation and its child organisation.
            new rb_join(
                'membercumulative',
                'LEFT',
                "(SELECT o.id, SUM(oc.membercount) membercountcumulative
                    FROM {org} o
                    INNER JOIN (
                        SELECT o.id, o.path, o.depthlevel, COUNT(DISTINCT ja.userid) membercount
                          FROM {org} o
                    INNER JOIN {job_assignment} ja ON ja.organisationid = o.id
                    INNER JOIN {user} u ON u.id = ja.userid
                               {$global_restriction_join_ja}
                         WHERE u.deleted = 0
                      GROUP BY o.id, o.path, o.depthlevel
                         ) oc ON (oc.path LIKE {$pathconcatsql} OR oc.path = o.path) AND oc.depthlevel >= o.depthlevel
                GROUP BY o.id)",
                'base.id = membercumulative.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array(
        new rb_column_option(
                'org',
                'idnumber',
                get_string('idnumber', 'rb_source_org'),
                "base.idnumber",
                array('dbdatatype' => 'char',
                      'displayfunc' => 'plaintext',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'org',
                'fullname',
                get_string('name', 'rb_source_org'),
                "base.fullname",
                array('displayfunc' => 'org_name_link',
                      'extrafields' => array('orgid' => 'base.id'),
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'org',
                'shortname',
                get_string('shortname', 'rb_source_org'),
                "base.shortname",
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'org',
                'description',
                get_string('description', 'rb_source_org'),
                "base.description",
                array('displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'org\'',
                        'component' => '\'totara_hierarchy\'',
                        'fileid' => 'base.id'
                    ),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text')
            ),
            new rb_column_option(
                'org',
                'orgtypeid',
                get_string('type', 'rb_source_org'),
                'orgtype.id',
                array(
                    'joins' => 'orgtype',
                    'hidden' => true,
                    'selectable' => false
                )
            ),
            new rb_column_option(
                'org',
                'orgtype',
                get_string('type', 'rb_source_org'),
                'orgtype.fullname',
                array('joins' => 'orgtype',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'org',
                'orgtypeidnumber',
                get_string('typeidnumber', 'rb_source_org'),
                'orgtype.idnumber',
                array('joins' => 'orgtype',
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'org',
                'framework',
                get_string('framework', 'rb_source_org'),
                "framework.fullname",
                array('joins' => 'framework',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'org',
                'frameworkidnumber',
                get_string('frameworkidnumber', 'rb_source_org'),
                "framework.idnumber",
                array('joins' => 'framework',
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text')
            ),
            new rb_column_option(
                'org',
                'visible',
                get_string('visible', 'rb_source_org'),
                'base.visible',
                array('displayfunc' => 'yes_or_no')
            ),
            new rb_column_option(
                'org',
                'parentidnumber',
                get_string('parentidnumber', 'rb_source_org'),
                'parent.idnumber',
                array('joins' => 'parent',
                      'displayfunc' => 'plaintext',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'org',
                'parentfullname',
                get_string('parentfullname', 'rb_source_org'),
                'parent.fullname',
                array('joins' => 'parent',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'org',
                'comps',
                get_string('competencies', 'rb_source_org'),
                'comps.list',
                array('joins' => 'comps',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'org',
                'timecreated',
                get_string('timecreated', 'rb_source_org'),
                'base.timecreated',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'org',
                'timemodified',
                get_string('timemodified', 'rb_source_org'),
                'base.timemodified',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            // A count of all members of this organisation.
            new rb_column_option(
                'org',
                'membercount',
                get_string('membercount', 'rb_source_org'),
                'COALESCE(member.membercount, 0)',
                array('joins' => 'member',
                      'displayfunc' => 'integer')
            ),
            // A count of all members of this organisation and its child organisation.
            new rb_column_option(
                'org',
                'membercountcumulative',
                get_string('membercountcumulative', 'rb_source_org'),
                'COALESCE(membercumulative.membercountcumulative, 0)',
                array('joins' => 'membercumulative',
                      'displayfunc' => 'integer')
            ),
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'org',              // type
                'idnumber',         // value
                get_string('idnumber', 'rb_source_org'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'fullname',         // value
                get_string('name', 'rb_source_org'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'shortname',        // value
                get_string('shortname', 'rb_source_org'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'description',      // value
                get_string('description', 'rb_source_org'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'parentidnumber',   // value
                get_string('parentidnumber', 'rb_source_org'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'parentfullname',   // value
                get_string('parentfullname', 'rb_source_org'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'timecreated',      // value
                get_string('timecreated', 'rb_source_org'), // label
                'date'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'timemodified',     // value
                get_string('timemodified', 'rb_source_org'), // label
                'date'              // filtertype
            ),
            new rb_filter_option(
                'org',              // type
                'orgtypeid',        // value
                get_string('type', 'rb_source_org'), // label
                'select',           // filtertype
                array(
                    'selectfunc' => 'orgtypes',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'org',              // type
                'visible',          // value
                get_string('visible', 'rb_source_org'), // label
                'select',           // filtertype
                array(
                    'selectfunc' => 'org_yesno',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
        );

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array();

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'org',
                'value' => 'idnumber',
            ),
            array(
                'type' => 'org',
                'value' => 'fullname',
            ),
            array(
                'type' => 'org',
                'value' => 'framework',
            ),
            array(
                'type' => 'org',
                'value' => 'parentidnumber',
            ),
            array(
                'type' => 'org',
                'value' => 'comps',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'org',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'org',
                'value' => 'idnumber',
                'advanced' => 0,
            ),
            array(
                'type' => 'org',
                'value' => 'parentidnumber',
                'advanced' => 0,
            ),
        );

        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            /*
            // array of rb_column objects, e.g:
            new rb_column(
                '',         // type
                '',         // value
                '',         // heading
                '',         // field
                array()     // options
            )
            */
        );
        return $requiredcolumns;
    }


    /**
     * Displays organisation name as html link
     *
     * @deprecated Since Totara 12.0
     * @param string $orgname
     * @param object Report row $row
     * @return string html link
     */
    public function rb_display_orgnamelink($orgname, $row) {
        debugging('rb_source_org::rb_display_orgnamelink has been deprecated since Totara 12.0. Use totara_hierarchy\rb\display\org_name_link::display', DEBUG_DEVELOPER);
        if (empty($orgname)) {
            return '';
        }
        $url = new moodle_url('/totara/hierarchy/item/view.php', array('prefix' => 'organisation', 'id' => $row->orgid));
        return html_writer::link($url, $orgname);
    }


    //
    //
    // Source specific filter display methods
    //
    //
    function rb_filter_org_yesno() {
        return array(
            1 => get_string('yes'),
            0 => get_string('no')
        );
    }

    function rb_filter_orgtypes() {
        global $DB;

        $types = $DB->get_records('org_type', null, 'fullname', 'id, fullname');
        $list = array();
        foreach ($types as $type) {
            $list[$type->id] = $type->fullname;
        }
        return $list;
    }

} // end of rb_source_org class
