<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @package tool_sitepolicy
 */

class rb_tool_sitepolicy_embedded extends rb_base_embedded
{
    public $url, $source, $fullname, $columns, $filters;
    public $contentmode, $embeddedparams;

    public function __construct()
    {
        global $CFG;

        require_once("{$CFG->dirroot}/admin/tool/sitepolicy/rb_sources/rb_source_tool_sitepolicy.php");

        $this->url = '/admin/tool/sitepolicy/sitepolicyreport.php';
        $this->source = 'tool_sitepolicy';
        $this->shortname = 'tool_sitepolicy';
        $this->fullname = get_string('sourcetitle', 'rb_source_tool_sitepolicy');

        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    public function embedded_global_restrictions_supported()
    {
        return false;
    }


    protected function define_columns()
    {
        return rb_source_tool_sitepolicy::get_default_columns();
    }

    protected function define_filters()
    {
        return rb_source_tool_sitepolicy::get_default_filters();
    }

    public function is_capable($reportfor, $report)
    {
        return true;

    }
}
