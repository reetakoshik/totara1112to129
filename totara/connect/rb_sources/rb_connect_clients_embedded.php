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
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Listing of TC clients.
 */
class rb_connect_clients_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;

    public function __construct() {
        $this->url = '/totara/connect/index.php';
        $this->source = 'connect_clients';
        $this->shortname = 'connect_clients';
        $this->fullname = get_string('embeddedreportname', 'rb_source_connect_clients');
        $this->columns = array(
            array('type' => 'connect_clients', 'value' => 'clientname', 'heading' => null),
            array('type' => 'connect_clients', 'value' => 'clientidnumber', 'heading' => null),
            array('type' => 'connect_clients', 'value' => 'clienturl', 'heading' => null),
            array('type' => 'connect_clients', 'value' => 'cohorts', 'heading' => null),
            array('type' => 'connect_clients', 'value' => 'courses', 'heading' => null),
            array('type' => 'connect_clients', 'value' => 'clientcomment', 'heading' => null),
            array('type' => 'connect_clients', 'value' => 'timecreated', 'heading' => null),
            array('type' => 'connect_clients', 'value' => 'status', 'heading' => null),
        );

        $this->filters = array(
        );

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * There is no user data here.
     * @return null|boolean
     */
    public function embedded_global_restrictions_supported() {
        return false;
    }

    /**
     * Hide this embedded report if TC not enabled.
     * @return bool
     */
    public static function is_report_ignored() {
        global $CFG;
        return empty($CFG->enableconnectserver);
    }

    public function is_capable($reportfor, $report) {
        $context = context_system::instance();
        return has_capability('moodle/site:config', $context, $reportfor);
    }
}
