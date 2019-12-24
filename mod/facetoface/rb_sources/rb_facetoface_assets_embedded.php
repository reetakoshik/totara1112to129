<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 */

defined('MOODLE_INTERNAL') || die();

class rb_facetoface_assets_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/mod/facetoface/asset/manage.php';
        $this->source = 'facetoface_asset';
        $this->shortname = 'facetoface_assets';
        $this->fullname = get_string('embedded:seminarassets', 'mod_facetoface');
        $this->columns = array(
            array('type' => 'asset', 'value' => 'name', 'heading' => null),
            array('type' => 'asset', 'value' => 'allowconflicts', 'heading' => null),
            array('type' => 'asset', 'value' => 'visible', 'heading' => null),
            array('type' => 'asset', 'value' => 'actions', 'heading' => null)
        );

        $this->filters = array(
            array('type' => 'asset', 'value' => 'name', 'advanced' => 0),
            array('type' => 'asset', 'value' => 'assetavailable', 'advanced' => 0)
        );

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        // Only show published.
        $this->embeddedparams = array('custom' => '0');


        parent::__construct();
    }

    public function is_capable($reportfor, $report) {
        $context = context_system::instance();
        return has_capability('totara/core:modconfig', $context, $reportfor);
    }
}
