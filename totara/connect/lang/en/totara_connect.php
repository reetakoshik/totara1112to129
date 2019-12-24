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
 */

$string['addnewcourses'] = 'Add new courses';
$string['addnewcourses_help'] = 'If enabled, each new course created in the LMS will be synchronised with the client to create a matching new group (Totara Social) or audience (Totara). The enrolled users of the courses will be added as members to the respective groups or audiences created.';
$string['addnewcohorts'] = 'Add new audiences';
$string['addnewcohorts_help'] = 'If enabled, each new audience created in the LMS will be synchronised with the client to create a new group (Totara Social) or audience (Totara). The members of the audiences are added as members to the respective groups or new audiences.';
$string['allowpluginsepservices'] = 'Allow plugin service requests';
$string['allowpluginsepservices_help'] = 'If enabled code from this client may call any remote plugin services on this server. Enable for trusted clients only, this feature is not required for standard SSO functionality';
$string['cancelsso'] = 'Return to {$a}';
$string['clientadd'] = 'Add client';
$string['clientedit'] = 'Add client';
$string['clients'] = 'Client systems';
$string['clientsetupsecret'] = 'Client setup secret';
$string['clientsetupsecret_help'] = 'This string is given to the client system administrator when creating a server connection request.';
$string['clienttype'] = 'Client type';
$string['clienturl'] = 'Client URL';
$string['clienturl_help'] = 'URL of the Totara Connect Client system.';
$string['cohorts'] = 'Synchronised audiences';
$string['cohortsadd'] = 'Add audience';
$string['comment'] = 'Comment';
$string['connect:manage'] = 'Manage the Totara Connect server and connecting clients';
$string['courses'] = 'Synchronised courses';
$string['coursesadd'] = 'Add course';
$string['enableconnectserver'] = 'Enable Totara Connect server';
$string['enableconnectserver_desc'] = 'Totara Connect is a single-sign-on and user identity solution for multiple Totara servers.';
$string['errorclientadd'] = 'Error registering new client';
$string['errorduplicateclient'] = 'Client with this URL is already active.';
$string['errorhttpclient'] = 'For security reasons all Totara Connect clients should be hosted via a secure protocol (https).';
$string['errorhttpserver'] = 'For security reasons Totara Connect servers should be hosted via a secure protocol (https).';
$string['organisationframeworks'] = 'Sync organisation frameworks';
$string['organisationframeworks_help'] = 'Client systems will be allowed to synchronise organisations from selected frameworks.';
$string['pluginname'] = 'Totara Connect server';
$string['positionframeworks'] = 'Sync position frameworks';
$string['positionframeworks_help'] = 'Client systems will be allowed to synchronise positions from selected frameworks.';
$string['restricttocohort'] = 'Restrict to audience';
$string['restricttocohort_help'] = 'If an audience is selected then only members of this audience are synchronised to the client system. This restriction is applied to all synchronised audiences and courses too.';
$string['server'] = 'Totara Connect server';
$string['settingspage'] = 'Settings';
$string['syncjobs'] = 'Sync job assignments';
$string['syncjobs_help'] = 'If enabled then job assignments are added to the user data, manager relationships are not included.';
$string['syncprofilefields'] = 'Sync custom profile fields';
$string['syncprofilefields_help'] = 'If enabled then custom profile fields are added to the user data.';
$string['syncpasswords'] = 'Sync user passwords';
$string['syncpasswords_desc'] = 'Enable if you want the Connect server to send user password hashes to clients.';
$string['timecreated'] = 'Registered';
$string['timemodified'] = 'Modified';
$string['warningloginas'] = 'You need to logout from "Login as" session to do single sing-on.';
