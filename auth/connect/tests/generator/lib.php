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
 * @category test
 */

use \auth_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara Connect client generator.
 *
 * @package auth_connect
 * @category test
 */
class auth_connect_generator extends component_generator_base {
    protected $servercount = 0;
    protected $serverusercount = 0;
    protected $servercohortcount = 0;
    protected $servercoursecount = 0;

    /**
     * To be called from data reset code only, do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
        $this->servercount = 0;
        $this->serverusercount = 0;
        $this->servercohortcount = 0;
        $this->servercoursecount = 0;
    }

    /**
     * Creates TC server record on client.
     *
     * @param stdClass array $record
     * @return stdClass server record
     */
    public function create_server($record = null) {
        global $DB;

        $record = (object)(array)$record;

        $this->servercount++;
        $i = $this->servercount;

        $server = new \stdClass();
        $server->status         = util::SERVER_STATUS_OK;
        $server->serveridnumber = util::create_unique_hash('auth_connect_servers', 'serveridnumber');
        $server->serversecret   = util::create_unique_hash('auth_connect_servers', 'serversecret');
        $server->serverurl      = empty($record->serverurl) ? 'https://www.example.com/tcc' : rtrim($record->serverurl, '/');
        $server->servername     = empty($record->servername) ? 'TC server ' . $i : $record->servername;
        $server->servercomment  = empty($record->servercomment) ? '' : $record->servercomment;
        $server->clientidnumber = util::create_unique_hash('auth_connect_servers', 'clientidnumber');
        $server->clientsecret   = util::create_unique_hash('auth_connect_servers', 'clientsecret');
        $server->apiversion     = empty($record->apiversion) ? util::MIN_API_VERSION : $record->apiversion;
        $server->timecreated    = time();
        $server->timemodified   = $server->timecreated;
        $id = $DB->insert_record('auth_connect_servers', $server);

        return $DB->get_record('auth_connect_servers', array('id' => $id));
    }

    /**
     * Migrate user to TC account.
     *
     * @param stdClass $server
     * @param stdClass $user
     * @param int $serveruserid
     */
    public function migrate_server_user($server, $user, $serveruserid) {
        global $DB;

        if ($user->auth !== 'connect') {
            $user->auth = 'connect';
            $DB->set_field('user', 'auth', $user->auth, array('id' => $user->id));
        }

        $record = new stdClass();
        $record->serverid     = $server->id;
        $record->serveruserid = $serveruserid;
        $record->userid       = $user->id;
        $record->timecreated  = time();
        $DB->insert_record('auth_connect_users', $record);
    }

    /**
     * Create fake server user data.
     *
     * @param array|stdClass $record for create_user generator
     * @param array $options for create_user generator
     * @return array user data in server format
     */
    public function get_fake_server_user($record = null, array $options = null) {
        global $DB;

        $this->serverusercount++;
        $clonefrom = $this->datagenerator->create_user($record, $options);

        // Create some fake client data.
        $client = new \stdClass;
        $client->syncjobs = 0;
        $client->apiversion = util::MIN_API_VERSION;

        // Let's rely on the TC server code, the calling code may tweak settings to get different results.
        \totara_connect\util::prepare_user_for_client($client, $clonefrom);

        $serveruser = clone($clonefrom);

        // Eliminated the cloned user.
        delete_user($clonefrom);
        $DB->delete_records('user', array('id' => $clonefrom->id));

        // offset the ids a bit to help with tests.
        $serveruser->id = (string)($serveruser->id + 10000 + $this->serverusercount);
        return (array)$serveruser;
    }

    /**
     * Create fake server cohort data.
     *
     * @param array|stdClass $record for create_cohort generator
     * @param array $options for create_cohort generator
     * @return array cohort data in server format
     */
    public function get_fake_server_cohort($record = null, array $options = null) {
        $this->servercohortcount++;
        $clonefrom = $this->datagenerator->create_cohort($record, $options);
        $servercohort = clone($clonefrom);

        // Eliminated the cloned cohort.
        cohort_delete_cohort($clonefrom);

        // offset the ids a bit to help with tests.
        $servercohort->id = (string)($servercohort->id + 10000 + $this->servercohortcount);
        $servercohort->members = array();
        return (array)$servercohort;
    }

    /**
     * Create fake server course data.
     *
     * @param array|stdClass $record for create_course generator
     * @param array $options for create_course generator
     * @return array course data in server format
     */
    public function get_fake_server_course($record = null, array $options = null) {
        $this->servercoursecount++;
        $clonefrom = $this->datagenerator->create_course($record, $options);
        $servercourse = clone($clonefrom);

        // Eliminated the cloned course.
        delete_course($clonefrom, false);

        // offset the ids a bit to help with tests.
        $servercourse->id = (string)($servercourse->id + 10000 + $this->servercoursecount);
        $servercourse->members = array();
        return (array)$servercourse;
    }
}
