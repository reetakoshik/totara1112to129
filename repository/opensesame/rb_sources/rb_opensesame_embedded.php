<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */


class rb_opensesame_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;

    public function __construct($data) {
        $this->url = '/repository/opensesame/index.php';
        $this->source = 'opensesame';
        $this->shortname = 'opensesame';
        $this->fullname = get_string('embeddedreportname', 'rb_source_opensesame');
        $this->columns = array(
            array('type' => 'opensesame', 'value' => 'bundlename', 'heading' => null),
            array('type' => 'opensesame', 'value' => 'title', 'heading' => null),
            array('type' => 'opensesame', 'value' => 'mobilecompatibility', 'heading' => null),
            array('type' => 'opensesame', 'value' => 'timecreated', 'heading' => null),
        );

        $this->filters = array(
            array(
                'type' => 'opensesame',
                'value' => 'bundlename',
                'advanced' => 0,
            ),
            array(
                'type' => 'opensesame',
                'value' => 'title',
                'advanced' => 0,
            ),
            array(
                'type' => 'opensesame',
                'value' => 'mobilecompatibility',
                'advanced' => 0,
            )
        );

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    public function is_capable($reportfor, $report) {
        $context = context_system::instance();
        return has_capability('repository/opensesame:managepackages', $context, $reportfor);
    }
}
