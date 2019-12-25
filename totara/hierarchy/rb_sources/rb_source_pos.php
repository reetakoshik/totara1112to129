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

class rb_source_pos extends rb_base_source {
    function __construct() {
        $this->base = '{pos}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_pos');
        $this->usedcomponents[] = 'totara_hierarchy';

        parent::__construct();
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('positions');
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

        $pathconcatsql = $DB->sql_concat('p.path', "'/'", "'%'");
        $global_restriction_join_ja = $this->get_global_report_restriction_join('ja', 'userid');
        $list = $DB->sql_group_concat_unique($DB->sql_cast_2char('c.fullname'), '<br>');

        $joinlist = array(
            new rb_join(
                'framework',
                'INNER',
                '{pos_framework}',
                'base.frameworkid = framework.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'parent',
                'LEFT',
                '{pos}',
                'base.parentid = parent.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'comps',
                'LEFT',
                "(SELECT oc.positionid, {$list} AS list
                    FROM {pos_competencies} oc
               LEFT JOIN {comp} c ON oc.competencyid = c.id
                GROUP BY oc.positionid)",
                'comps.positionid = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'postype',
                'LEFT',
                '{pos_type}',
                'base.typeid = postype.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            // This join is required to keep the joining of pos custom fields happy :D
            new rb_join(
                'position',
                'INNER',
                '{pos}',
                'base.id = position.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),

            // A count of all members of this position.
            new rb_join(
                'member',
                'LEFT',
                "(SELECT positionid, COUNT(DISTINCT ja.userid) membercount
                    FROM {job_assignment} ja
              INNER JOIN {user} u ON u.id = ja.userid
                         {$global_restriction_join_ja}
                   WHERE u.deleted = 0
                GROUP BY ja.positionid)",
                'base.id = member.positionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),

            // A count of all members of this position and its child positions.
            new rb_join(
                'membercumulative',
                'LEFT',
                "(SELECT p.id, SUM(pc.membercount) membercountcumulative
                    FROM {pos} p
              INNER JOIN (
                        SELECT p.id, p.path, p.depthlevel, COUNT(DISTINCT ja.userid) membercount
                          FROM {pos} p
                    INNER JOIN {job_assignment} ja ON ja.positionid = p.id
                    INNER JOIN {user} u ON u.id = ja.userid
                               {$global_restriction_join_ja}
                         WHERE u.deleted = 0
                      GROUP BY p.id, p.path, p.depthlevel
                         ) pc ON (pc.path LIKE {$pathconcatsql} OR pc.path = p.path) AND pc.depthlevel >= p.depthlevel
                GROUP BY p.id)",
                'base.id = membercumulative.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array(
        new rb_column_option(
                'pos',
                'idnumber',
                get_string('idnumber', 'rb_source_pos'),
                "base.idnumber",
                array('displayfunc' => 'plaintext',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'pos',
                'fullname',
                get_string('name', 'rb_source_pos'),
                "base.fullname",
                array('displayfunc' => 'pos_name_link',
                      'extrafields' => array('posid' => 'base.id'),
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'pos',
                'shortname',
                get_string('shortname', 'rb_source_pos'),
                "base.shortname",
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'pos',
                'description',
                get_string('description', 'rb_source_pos'),
                "base.description",
                array('displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'pos\'',
                        'component' => '\'totara_hierarchy\'',
                        'fileid' => 'base.id'
                    ),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text')
            ),
            new rb_column_option(
                'pos',
                'postypeid',
                get_string('type', 'rb_source_pos'),
                'postype.id',
                array(
                    'joins' => 'postype',
                    'hidden' => true,
                    'selectable' => false
                )
            ),
            new rb_column_option(
                'pos',
                'postype',
                get_string('type', 'rb_source_pos'),
                'postype.fullname',
                array('joins' => 'postype',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'pos',
                'postypeidnumber',
                get_string('typeidnumber', 'rb_source_pos'),
                'postype.idnumber',
                array('joins' => 'postype',
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'pos',
                'framework',
                get_string('framework', 'rb_source_pos'),
                "framework.fullname",
                array('joins' => 'framework',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'pos',
                'frameworkidnumber',
                get_string('frameworkidnumber', 'rb_source_pos'),
                "framework.idnumber",
                array('joins' => 'framework',
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text')
            ),
            new rb_column_option(
                'pos',
                'visible',
                get_string('visible', 'rb_source_pos'),
                'base.visible',
                array('displayfunc' => 'yes_or_no')
            ),
            new rb_column_option(
                'pos',
                'parentidnumber',
                get_string('parentidnumber', 'rb_source_pos'),
                'parent.idnumber',
                array('joins' => 'parent',
                      'displayfunc' => 'plaintext',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'pos',
                'parentfullname',
                get_string('parentfullname', 'rb_source_pos'),
                'parent.fullname',
                array('joins' => 'parent',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'pos',
                'comps',
                get_string('competencies', 'rb_source_pos'),
                'comps.list',
                array('joins' => 'comps',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'pos',
                'timecreated',
                get_string('timecreated', 'rb_source_pos'),
                'base.timecreated',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'pos',
                'timemodified',
                get_string('timemodified', 'rb_source_pos'),
                'base.timemodified',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            // A count of all members of this position.
            new rb_column_option(
                'pos',
                'membercount',
                get_string('membercount', 'rb_source_pos'),
                'COALESCE(member.membercount, 0)',
                array('joins' => 'member',
                      'displayfunc' => 'integer')
            ),
            // A count of all members of this position and its child positions.
            new rb_column_option(
                'pos',
                'membercountcumulative',
                get_string('membercountcumulative', 'rb_source_pos'),
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
                'pos',              // type
                'idnumber',         // value
                get_string('idnumber', 'rb_source_pos'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'fullname',         // value
                get_string('name', 'rb_source_pos'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'shortname',        // value
                get_string('shortname', 'rb_source_pos'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'description',      // value
                get_string('description', 'rb_source_pos'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'parentidnumber',   // value
                get_string('parentidnumber', 'rb_source_pos'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'parentfullname',   // value
                get_string('parentfullname', 'rb_source_pos'), // label
                'text'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'timecreated',      // value
                get_string('timecreated', 'rb_source_pos'), // label
                'date'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'timemodified',     // value
                get_string('timemodified', 'rb_source_pos'), // label
                'date'              // filtertype
            ),
            new rb_filter_option(
                'pos',              // type
                'postypeid',        // value
                get_string('type', 'rb_source_pos'), // label
                'select',           // filtertype
                array(
                    'selectfunc' => 'postypes',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'pos',              // type
                'visible',          // value
                get_string('visible', 'rb_source_pos'), // label
                'select',           // filtertype
                array(
                    'selectfunc' => 'pos_yesno',
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
                'type' => 'pos',
                'value' => 'idnumber',
            ),
            array(
                'type' => 'pos',
                'value' => 'fullname',
            ),
            array(
                'type' => 'pos',
                'value' => 'framework',
            ),
            array(
                'type' => 'pos',
                'value' => 'parentidnumber',
            ),
            array(
                'type' => 'pos',
                'value' => 'comps',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'pos',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'pos',
                'value' => 'idnumber',
                'advanced' => 0,
            ),
            array(
                'type' => 'pos',
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
     * Displays position name as html link
     *
     * @deprecated Since Totara 12.0
     * @param string $posname
     * @param object Report row $row
     * @return string html link
     */
    public function rb_display_posnamelink($posname, $row) {
        debugging('rb_source_pos::rb_display_posnamelink has been deprecated since Totara 12.0. Use totara_hierarchy\rb\display\pos_name_link::display', DEBUG_DEVELOPER);
        if (empty($posname)) {
            return '';
        }
        $url = new moodle_url('/totara/hierarchy/item/view.php', array('prefix' => 'position', 'id' => $row->posid));
        return html_writer::link($url, $posname);
    }


    //
    //
    // Source specific filter display methods
    //
    //
    function rb_filter_pos_yesno() {
        return array(
            1 => get_string('yes'),
            0 => get_string('no')
        );
    }

    function rb_filter_postypes() {
        global $DB;

        $types = $DB->get_records('pos_type', null, 'fullname', 'id, fullname');
        $list = array();
        foreach ($types as $type) {
            $list[$type->id] = $type->fullname;
        }
        return $list;
    }

} // end of rb_source_pos class
