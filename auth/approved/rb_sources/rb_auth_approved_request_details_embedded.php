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

class rb_auth_approved_request_details_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/auth/approved/index.php'; // The report is used in different pages, but we need some url here.
        $this->source = 'auth_approved_requests';
        $this->shortname = 'auth_approved_request_details';
        $this->fullname = get_string('reportdetails', 'auth_approved');

        $this->columns = array(
            array('type' => 'request', 'value' => 'firstname', 'heading' => null),
            array('type' => 'request', 'value' => 'lastname', 'heading' => null),
            array('type' => 'request', 'value' => 'username', 'heading' => null),
            array('type' => 'request', 'value' => 'email', 'heading' => null),
            array('type' => 'request', 'value' => 'confirmed', 'heading' => null),
        );

        if (isset($data['requestid'])) {
            $this->embeddedparams['requestid'] = $data['requestid'];
        } else {
            $this->embeddedparams['requestid'] = -1;
        }

        parent::__construct();
    }

    public function is_capable($reportfor, $report) {
        return has_capability('auth/approved:approve', context_system::instance(), $reportfor);
    }
}
