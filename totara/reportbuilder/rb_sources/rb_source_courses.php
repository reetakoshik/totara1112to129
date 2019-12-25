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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_courses extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;

    function __construct() {
        $this->base = '{course}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->defaulttoolbarsearchcolumns = $this->define_defaultsearchcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_courses');
        $this->usedcomponents[] = 'totara_cohort';

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

        $list = $DB->sql_group_concat_unique($DB->sql_cast_2char('m.name'), '|');
        $joinlist = array(
            new rb_join(
                'mods',
                'LEFT',
                "(SELECT cm.course, {$list} AS list
                    FROM {course_modules} cm
               LEFT JOIN {modules} m ON m.id = cm.module
                GROUP BY cm.course)",
                'mods.course = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        // Include some standard joins.
        $this->add_context_tables($joinlist, 'base', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_core_course_category_tables($joinlist,
            'base', 'category');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'base', 'id');
        $this->add_totara_cohort_course_tables($joinlist, 'base', 'id');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array(
            new rb_column_option(
                'course',
                'mods',
                get_string('content', 'rb_source_courses'),
                "mods.list",
                array('joins' => 'mods', 'displayfunc' => 'course_mod_icons')
            ),
        );

        // Include some standard columns.
        $this->add_core_course_columns($columnoptions, 'base');
        $this->add_core_course_category_columns($columnoptions, 'course_category', 'base');
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_totara_cohort_course_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'course',         // type
                'mods',           // value
                get_string('coursecontent', 'rb_source_courses'), // label
                'multicheck',     // filtertype
                array(            // options
                    'selectfunc' => 'modules_list',
                    'concat' => true, // Multicheck filter need to know that we work with concatenated values
                    'simplemode' => true,
                    'showcounts' => array(
                            'joins' => array("LEFT JOIN (SELECT course, name FROM {course_modules} cm " .
                                                          "LEFT JOIN {modules} m ON m.id = cm.module) course_mods_filter ".
                                                    "ON base.id = course_mods_filter.course"),
                            'dataalias' => 'course_mods_filter',
                            'datafield' => 'name')
                )
            )
        );

        // Include some standard filters.
        $this->add_core_course_filters($filteroptions, 'base', 'id');
        $this->add_core_course_category_filters($filteroptions, 'base', 'category');
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array(

            new rb_content_option(
                'date',
                get_string('startdate', 'rb_source_courses'),
                'base.startdate'
            ),
        );
        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'courseid',
                'base.id'
            ),
            new rb_param_option(
                'category',
                'base.category'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'course_category',
                'value' => 'path',
                'advanced' => 0,
            )
        );

        return $defaultfilters;
    }

    protected function define_defaultsearchcolumns() {
        $defaultsearchcolumns = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
            ),
            array(
                'type' => 'course',
                'value' => 'summary',
            ),
        );

        return $defaultsearchcolumns;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array();
        $requiredcolumns[] = new rb_column(
            'ctx',
            'id',
            '',
            "ctx.id",
            array('joins' => 'ctx')
        );
        $requiredcolumns[] = new rb_column(
            'base',
            'category',
            '',
            "base.category"
        );
        $requiredcolumns[] = new rb_column(
            'visibility',
            'id',
            '',
            "base.id"
        );
        $requiredcolumns[] = new rb_column(
            'visibility',
            'visible',
            '',
            "base.visible"
        );
        $requiredcolumns[] = new rb_column(
            'visibility',
            'audiencevisible',
            '',
            "base.audiencevisible"
        );
        return $requiredcolumns;
    }


    //
    //
    // Source specific column display methods
    //
    //

    /**
     * Display course module icons
     *
     * @deprecated Since Totara 12.0
     * @param $mods
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_modicons($mods, $row, $isexport = false) {
        debugging('rb_source_courses::rb_display_modicons has been deprecated since Totara 12.0. Use course_mod_icons::display', DEBUG_DEVELOPER);
        global $OUTPUT, $CFG;
        $modules = explode('|', $mods);
        $mods = array();

        // Sort module list before displaying to make
        // cells all consistent
        foreach ($modules as $mod) {
            if (empty($mod)) {
                continue;
            }
            $module = new stdClass();
            $module->name = $mod;
            if (get_string_manager()->string_exists('pluginname', $mod)) {
                $module->localname = get_string('pluginname', $mod);
            } else {
                $module->localname = ucfirst($mod);
            }
            $mods[] = $module;
        }
        \core_collator::asort_objects_by_property($mods, 'localname');

        $out = array();
        $glue = '';

        foreach ($mods as $module) {
            if ($isexport) {
                $out[] = $module->localname;
                $glue = ', ';
            } else {
                $glue = '';
                if (file_exists($CFG->dirroot . '/mod/' . $module->name . '/pix/icon.gif') ||
                    file_exists($CFG->dirroot . '/mod/' . $module->name . '/pix/icon.png')) {
                    $out[] = $OUTPUT->pix_icon('icon', $module->localname, $module->name);
                } else {
                    $out[] = $module->name;
                }
            }
        }

        return implode($glue, $out);
    }


    public function post_config(reportbuilder $report) {
        // Don't include the front page (site-level course).
        $categorysql = $report->get_field('base', 'category', 'base.category') . " <> :sitelevelcategory";
        $categoryparams = array('sitelevelcategory' => 0);

        $reportfor = $report->reportfor; // ID of the user the report is for.
        list($visiblesql, $visibleparams) = $report->post_config_visibility_where('course', 'base', $reportfor);

        // Combine the results.
        $report->set_post_config_restrictions(array($categorysql . " AND " . $visiblesql,
            array_merge($categoryparams, $visibleparams)));
    }

} // End of rb_source_courses class.
