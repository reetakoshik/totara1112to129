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
 * @package totara_connect
 * @category test
 */

use \totara_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara Connect generator.
 *
 * @package totara_connect
 * @category test
 */
class totara_connect_generator extends component_generator_base {
    protected $clientcount = 0;

    /**
     * To be called from data reset code only, do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
        $this->clientcount = 0;
    }

    /**
     * Creates TC clients.
     *
     * @param stdClass array $record
     * @return stdClass client record
     */
    public function create_client($record = null) {
        global $DB;

        $record = (object)(array)$record;

        $this->clientcount++;
        $i = $this->clientcount;

        $client = new \stdClass();
        $client->status         = util::CLIENT_STATUS_OK;
        $client->clientidnumber = util::create_unique_hash('totara_connect_clients', 'clientidnumber');
        $client->clientsecret   = util::create_unique_hash('totara_connect_clients', 'clientsecret');
        $client->clientname     = empty($record->clientname) ? 'Some client ' . $i : $record->clientname;
        $client->clienturl      = empty($record->clienturl) ? 'https://www.example.com/totara' : rtrim($record->clienturl, '/');
        $client->clienttype     = !isset($record->clienttype) ? 'totaralms' : $record->clienttype;
        $client->clientcomment  = empty($record->clientcomment) ? '' : $record->clientcomment;
        $client->cohortid       = empty($record->cohortid) ? null : $record->cohortid;
        $client->syncprofilefields = !empty($record->syncprofilefields);
        $client->serversecret   = util::create_unique_hash('totara_connect_clients', 'serversecret');
        $client->syncjobs       = !empty($record->syncjobs);
        $client->addnewcohorts  = !empty($record->addnewcohorts);
        $client->addnewcourses  = !empty($record->addnewcourses);
        $client->apiversion     = empty($record->apiversion) ? util::MIN_API_VERSION : $record->apiversion;
        $client->timecreated    = time();
        $client->timemodified   = $client->timecreated;
        $id = $DB->insert_record('totara_connect_clients', $client);

        $client = $DB->get_record('totara_connect_clients', array('id' => $id));

        if (!empty($record->positionframeworks)) {
            foreach ($record->positionframeworks as $fid) {
                \totara_connect\util::add_client_pos_framework($client, $fid);
            }
        }

        if (!empty($record->organisationframeworks)) {
            foreach ($record->organisationframeworks as $fid) {
                \totara_connect\util::add_client_org_framework($client, $fid);
            }
        }

        return $client;
    }
}
