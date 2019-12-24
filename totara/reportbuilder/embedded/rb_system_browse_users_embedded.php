<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package totara_reportbuilder
 */

class rb_system_browse_users_embedded extends rb_base_embedded {

    public function __construct($data) {

        $this->url = '/admin/user.php';
        $this->source = 'user';
        $this->shortname = 'system_browse_users'; // This must be unique, lets try make it really unique.
        $this->fullname = get_string('userlist', 'admin');
        $this->columns = array(
            array(
                'type' => 'user',
                'value' => 'namelinkicon',
                'heading' => get_string('userfullname', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'username',
                'heading' => get_string('username', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'emailunobscured',
                'heading' => get_string('useremail', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'deleted',
                'heading' => get_string('userstatus', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'lastloginrelative',
                'heading' => get_string('lastlogin', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'actions',
                'heading' => get_string('actions', 'totara_reportbuilder'),
            ),
        );

        $this->filters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'user',
                'value' => 'deleted',
                'advanced' => 0,
                'defaultvalue' => ['operator'=> 1, 'value' => 0],
            ),
            array(
                'type' => 'user',
                'value' => 'username',
                'advanced' => 1,
            ),
            array(
                'type' => 'user',
                'value' => 'emailunobscured',
                'advanced' => 1,
                'fieldname' => get_string('useremail', 'totara_reportbuilder'),
            ),
        );

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $userid id of the user this report is being generated for
     * @param reportbuilder $report the report object.
     * @return boolean true if the user can access this report.
     */
    public function is_capable($userid, $report) {
        $systemcontext = context_system::instance();

        return has_any_capability([
            'moodle/user:update',
            'moodle/user:delete'
        ], $systemcontext);
    }

    /**
     * Define if Global Report restrictions are supported.
     *
     * @return boolean
     */
    public function embedded_global_restrictions_supported() {
        return true;
    }
}
