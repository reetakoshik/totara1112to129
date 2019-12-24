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

namespace repository_opensesame\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/lib/filelib.php");

/**
 * Utility class for communication with Totara registration server.
 */
class totaralms_com {
    /**
     * Register Totara server with OpenSesame.
     *
     * @param string $tenantname
     * @param string $tenanttype
     * @param string $repositorykey
     * @param string $tenantdemosecret
     * @return array JSend encoded result
     */
    public static function provision_tenant($tenantname, $tenanttype, $repositorykey, $tenantdemosecret) {
        global $CFG, $USER;

        $url = 'https://subscriptions.totara.community/local/opensesameadmin/request/provison_tenant.php';
        
        $data = array(
            'tenantname' => $tenantname,
            'tenanttype' => $tenanttype,
            'wwwroot' => clean_param($CFG->wwwroot, PARAM_URL),
            'siteidentifier' => get_site_identifier(),
            'supportemail' => empty($CFG->supportemail) ? $USER->email : $CFG->supportemail,
            'repositorykey' => $repositorykey,
            'tenantdemosecret' => $tenantdemosecret,
        );
        $data['checksum'] = sha1(implode(array_values($data)));

        $result = download_file_content($url, null, $data, false, 60, 60);

        if (!$result) {
            return array(
                'status' => 'error',
                'message' => get_string('errorcannotaccesstotaralms', 'repository_opensesame', 'https://subscriptions.totara.community/'),
            );
        }
        $result = json_decode($result, true);
        if (!empty($result['error'])) {
            $result['status'] = 'error';
            $result['message'] = $result['error'];
        } else if (!isset($result['status'])) {
            return array(
                'status' => 'error',
                'message' => get_string('errorcannotaccesstotaralms', 'repository_opensesame', 'https://subscriptions.totara.community/'),
            );
        } else if ($result['status'] === 'success') {
            // Add params to result to make the code simpler.
            $result['data']['TenantName'] = $tenantname;
            $result['data']['TenantType'] = $tenanttype;
        }

        return $result;
    }

    /**
     * Unregister Totara server.
     *
     * @param string $tenantid
     * @param string $repositorykey
     * @return array JSend encoded result
     */
    public static function remove_tenant($tenantid, $repositorykey) {
        $url = 'https://subscriptions.totara.community/local/opensesameadmin/request/remove_tenant.php';

        $data = array(
            'tenantid' => $tenantid,
            'repositorykey' => $repositorykey,
        );

        $result = download_file_content($url, null, $data, false, 60, 60);

        if (!$result) {
            return array(
                'status' => 'error',
                'message' => get_string('errorcannotaccesstotaralms', 'repository_opensesame', 'https://subscriptions.totara.community/'),
            );
        }
        $result = json_decode($result, true);
        if (!empty($result['error'])) {
            $result['status'] = 'error';
            $result['message'] = $result['error'];
        } else if (!isset($result['status'])) {
            return array(
                'status' => 'error',
                'message' => get_string('errorcannotaccesstotaralms', 'repository_opensesame', 'https://subscriptions.totara.community/'),
            );
        }

        return $result;
    }
}
