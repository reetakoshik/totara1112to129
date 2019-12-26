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

$string['allowpluginsepservices'] = 'Allow requests to plugin services';
$string['allowpluginsepservices_desc'] = 'Allow requests from Totara Connect servers to access services defined in any plugins';
$string['autossoserver'] = 'Automatic single sign-on via server';
$string['autossoserver_desc'] = 'Select Totara Connect server for automatic single sign-on.';
$string['comment'] = 'Comment';
$string['confirmdelete'] = 'Type server ID number to confirm';
$string['deletingserver'] = 'Delete in progress';
$string['errorhttp'] = 'For security reasons all Totara Connect clients should be hosted via a secure protocol (https).';
$string['errorprofiledit'] = 'Error updating local user information';
$string['migratebyuniqueid'] = 'Totara Connect unique ID';
$string['migratemap'] = 'Account mapping';
$string['migratemap_desc'] = 'Map user accounts during migration using the selected field. Make sure the selected user field is locked and cannot be modified by ordinary users or customised during user self registration both on the server and clients.';
$string['migrateusers'] = 'Migrate local accounts';
$string['migrateusers_desc'] = 'If enabled preexisting local accounts are automatically migrated to Totara Connect accounts. Totara Connect accounts can log in only via single sign-on.

Make sure the selected account mapping cannot be abused by Totara Connect server users to hijack existing client accounts. For example when using username mapping, users should not be allowed to sign up for new accounts on the Totara Connect server.';
$string['pluginname'] = 'Totara Connect client';
$string['registercancel'] = 'Cancel connection';
$string['registerinfo'] = 'Send this information to the Totara Connect server administrator:<ul>
<li>Client url: {$a->url}</li>
<li>Client setup secret: {$a->secret}</li>
</ul>';
$string['registerrequest'] = 'Connect to new server';
$string['removeuser'] = 'Action to take when a user is removed from the restricted audience';
$string['removeuser_desc'] = 'If Totara Connect users are restricted to an audience on the server this setting specifies what happens with local accounts when the user is removed from that audience on the server. Please note that any synchronised users who are deleted from the server will also be deleted from the local site.';
$string['retrylogin'] = 'Retry';
$string['serverdelete'] = 'Delete server';
$string['serverdeleteauth'] = 'Migrate to auth plugin';
$string['serverdeleteuser'] = 'Existing accounts';
$string['serveredit'] = 'Edit server';
$string['serverrequest'] = 'Add connection';
$string['serverspage'] = 'Servers';
$string['serversynced'] = 'Server data was synchronised';
$string['serversyncerror'] = 'Error synchronising server data';
$string['ssoerroralreadyloggedin'] = 'Single sign-on failed because you are logged in already.';
$string['ssoerrorgeneral'] = 'Unknown problem detected during single sign-on, either retry or restart your web browser.';
$string['ssoerrorloginfailure'] = 'Invalid log in on single sign-on server.';
$string['ssoerrorlogintimeout'] = 'Single sign-on request was not completed in allocated time frame, please retry.';
$string['ssoerrornotallowed'] = 'You are not allowed to access this server.';
$string['ssologinfailed'] = 'Single sign-on failed';
$string['sync'] = 'Synchronise';
$string['syncjobs'] = 'Synchronise job assignments';
$string['syncjobs_desc'] = 'If enabled job assignments will be automatically copied from the server. Managers and appraisers are not synchronised, other manual modifications in synchronised job assignments will be automatically reverted. Manual job assignments can be still used and are not affected.';
$string['syncorganisations'] = 'Synchronise organisations';
$string['syncorganisations_desc'] = 'If enabled organisation frameworks and organisations will be automatically copied from the server. It is recommended to change permissions so that users cannot modify organisations manually. Organisation types must be created manually with the same idnumbers and matching custom fields on server and client, custom fields should be locked on clients.';
$string['syncpositions'] = 'Synchronise positions';
$string['syncpositions_desc'] = 'If enabled position frameworks and positions will be automatically copied from the server. It is recommended to change permissions so that users cannot modify positions manually. Position types must be created manually with the same idnumbers and matching custom fields on server and client, custom fields should be locked on clients.';
$string['syncprofilefields'] = 'Synchronise custom profile fields';
$string['syncprofilefields_desc'] = 'If enabled custom profile field data for users will be automatically copied from the server. Matching custom profile fields must be created manually on the client.';
$string['timecreated'] = 'Time registered';
$string['timemodified'] = 'Time modified';
$string['taskcleanup'] = 'General cleanup task';
$string['taskhandshake'] = 'API handshake';
$string['taskorganisation'] = 'Organisations sync task';
$string['taskposition'] = 'Positions sync task';
$string['taskuser'] = 'Users sync task';
$string['taskusercollection'] = 'User collections sync task';
