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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/program/rb_sources/rb_source_program.php');

class rb_source_certification extends rb_source_program {

    /**
     * Overwrite instance type value of totara_visibility_where() in rb_source_program->post_config().
     */
    protected $instancetype = 'certification';

    public function __construct() {
        parent::__construct();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_certification');
        $this->sourcewhere = $this->define_sourcewhere();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('certifications');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    protected function define_columnoptions() {

        // Include some standard columns, override parent so they say certification.
        $this->add_program_fields_to_columns($columnoptions, 'base', 'totara_certification');
        $this->add_certification_fields_to_columns($columnoptions, 'certif', 'totara_certification');
        $this->add_course_category_fields_to_columns($columnoptions, 'course_category', 'base', 'programcount');
        $this->add_cohort_program_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_sourcewhere() {
        $sourcewhere = '(base.certifid IS NOT NULL)';

        return $sourcewhere;
    }

    protected function define_joinlist() {

        $joinlist = parent::define_joinlist();

        $this->add_certification_table_to_joinlist($joinlist, 'base', 'certifid');

        return $joinlist;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        // Include some standard filters, override parent so they say certification.
        $this->add_program_fields_to_filters($filteroptions, 'totara_certification');
        $this->add_certification_fields_to_filters($filteroptions, 'totara_certification');
        $this->add_course_category_fields_to_filters($filteroptions, 'base', 'category');
        $this->add_cohort_program_fields_to_filters($filteroptions, 'totara_certification');

        return $filteroptions;
    }
}
