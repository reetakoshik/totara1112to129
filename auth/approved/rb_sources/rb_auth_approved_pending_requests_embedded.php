<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Andrew Bell <andrewb@learningpool.com>
 * @author Ryan Lynch <ryanlynch@learningpool.com>
 * @author Barry McKay <barry@learningpool.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__. '/rb_source_auth_approved_requests.php');

class rb_auth_approved_pending_requests_embedded extends rb_base_embedded {

    public function __construct() {
        $this->url = '/auth/approved/index.php';
        $this->source = 'auth_approved_requests';
        $this->shortname = 'auth_approved_pending_requests';
        $this->fullname = get_string('reportpending', 'auth_approved');

        $this->columns = array(
            array('type' => 'request', 'value' => 'firstname', 'heading' => null),
            array('type' => 'request', 'value' => 'lastname', 'heading' => null),
            array('type' => 'request', 'value' => 'username', 'heading' => null),
            array('type' => 'request', 'value' => 'email', 'heading' => null),
            array('type' => 'request', 'value' => 'confirmed', 'heading' => null),
            array('type' => 'request', 'value' => 'timecreated', 'heading' => null),
            array('type' => 'request', 'value' => 'actions', 'heading' => null),
        );

        $this->filters = array(
            array('type' => 'request', 'value' => 'confirmed', 'advanced' => 0),
        );

        $this->embeddedparams['status'] = \auth_approved\request::STATUS_PENDING;

        $this->requiredcolumns = $this->define_requiredcolumns();

        parent::__construct();
    }


    protected function define_requiredcolumns() {
        $requiredcolumns = array();

        // We need request id for bulk actions,
        // unfortunately there does not seem to be a way
        // to add it to embedded embedded report only.
        $requiredcolumns[] = new rb_column(
            'bulk',
            'id',
            '',
            "base.id",
            array(
                'required' => 'true',
                'hidden' => 'true'
            )
        );
        $requiredcolumns[] = new rb_column(
            'bulk',
            'timemodified',
            '',
            "base.timemodified",
            array(
                'required' => 'true',
                'hidden' => 'true'
            )
        );
        $requiredcolumns[] = new rb_column(
            'bulk',
            'status',
            '',
            "base.status",
            array(
                'required' => 'true',
                'hidden' => 'true'
            )
        );

        return $requiredcolumns;
    }

    public function is_capable($reportfor, $report) {
        // This capability check needs to match external page definition in settings.php file.
        return has_capability('auth/approved:approve', context_system::instance(), $reportfor);
    }
}
