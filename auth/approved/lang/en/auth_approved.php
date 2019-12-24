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
 * @author Andrew Bell <andrewb@learningpool.com>
 * @author Ryan Lynch <ryanlynch@learningpool.com>
 * @author Barry McKay <barry@learningpool.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['allframeworks'] = 'All frameworks';
$string['allowexternaldefaults'] = 'Allow external defaults';
$string['allowexternaldefaults_desc'] = 'If enabled it is possible to specify sign up form defaults via page parameters, e.g. login/signup.php?organisationid=12.';
$string['allowmanager'] = 'Allow manager selection';
$string['allowmanagerfreetext'] = 'Allow free-text manager';
$string['allowmanagerfreetext_desc'] = 'Allow self registering users to enter free text for the manager field. The position will be selected later during manual approval process because the selection cannot be automated.';
$string['allowmanager_desc'] = 'If enabled users will be able to select their manager in the sign up form.

*Warning: this settings exposes jobs of available users to public.*';
$string['alloworganisation'] = 'Allow organisation selection';
$string['alloworganisationframeworks'] = 'Show organisation from selected frameworks';
$string['alloworganisationfreetext'] = 'Allow free-text organisation';
$string['alloworganisationfreetext_desc'] = 'Allow self registering users to enter free text for the organisation field. The position will be selected later during manual approval process because the selection cannot be automated.';
$string['alloworganisation_desc'] = 'If enabled users will be able to select their organisation in the sign up form.

*Warning: this settings exposes the list of available organisations to public.*';
$string['allowposition'] = 'Allow position selection';
$string['allowpositionframeworks'] = 'Show positions from selected frameworks';
$string['allowposition_desc'] = 'If enabled users will be able to select their position in the sign up form.

*Warning: this settings exposes the list of available positions to public.*';
$string['allowpositionfreetext'] = 'Allow free-text position';
$string['allowpositionfreetext_desc'] = 'Allow self registering users to enter free text for the position field. The position will be selected later during manual approval process because the selection cannot be automated.';
$string['approve'] = 'Approve';
$string['approvesure'] = 'Are you sure you want to approve this request?';
$string['approved:approve'] = 'Approve account request';
$string['bulkaction'] = 'Bulk change {$a} requests';
$string['bulkexec'] = 'Execute';
$string['bulkactionapprove'] = 'Approve';
$string['bulkactionapproveconfirm'] = 'Are you sure you want to bulk approve {$a} requests?';
$string['bulkactionmanager'] = 'Set manager';
$string['bulkactionmanagerselect'] = 'Select manager for {$a} requests';
$string['bulkactionmessage'] = 'Send message';
$string['bulkactionmessageconfirm'] = 'Are you sure you want to bulk send messages to {$a} requests?';
$string['bulkactionorganisation'] = 'Set organisation';
$string['bulkactionorganisationselect'] = 'Select organisation for {$a} requests';
$string['bulkactionposition'] = 'Set position';
$string['bulkactionpositionselect'] = 'Select position for {$a} requests';
$string['bulkactionreject'] = 'Reject';
$string['bulkactionrejectconfirm'] = 'Are you sure you want to bulk reject {$a} requests?';
$string['cannotfindorg'] = 'If you are unable to find your organisation, please contact us at {$a}';
$string['cannotfindpos'] = 'If you are unable to find your position, please contact us at {$a}';
$string['cannotfindmgr'] = 'If you are unable to find your manager, please contact us at {$a}';
$string['confirmed'] = 'Email confirmed';
$string['confirmtokenaccepted'] = 'Thank you for confirming your account request, an email should have been sent to your address at {$a} with information describing the account approval process.';
$string['confirmtokenacceptedapproved'] = 'Thank you for confirming your account request, you can now log in using your requested username: {$a}';
$string['confirmtokenapproved'] = 'User account request was already approved';
$string['confirmtokenconfirmed'] = 'User account request was already confirmed';
$string['confirmtokeninvalid'] = 'Invalid confirmation request';
$string['confirmtokenrejected'] = 'User account request was already rejected';
$string['createrequest'] = 'Request account';
$string['custommessage'] = 'Custom message for user';
$string['emailconfirm'] = 'Confirm your email address';
$string['emailconfirmationsubject'] = '{$a->sitename}: Confirmation of account request';
$string['emailconfirmationbody'] = 'Dear {$a->firstname} {$a->lastname},

A new account has been requested at \'{$a->sitename}\'
using your email address.

Please go to this web address to confirm your request:

<{$a->link}>

If you need help, please contact support at this address: {$a->support}
';
$string['emailconfirmedsubject'] = '{$a->sitename}: Account request confirmed';
$string['emailconfirmedbody'] = 'Dear {$a->firstname} {$a->lastname},

Thank you for confirming your account request at \'{$a->sitename}\',
we will keep you informed about the progress of account approval.


If you need help, please contact support at this address: {$a->support}
';
$string['emailconfirmsent'] = 'Thank you for requesting a new user account. An email should have been sent to your address at {$a}. It contains instructions to confirm the ownership of this email address.';
$string['domainwhitelist'] = 'Automatic approval whitelist';
$string['domainwhitelist_desc'] = '
A list of email domains that are approved automatically after email confirmation. Accounts cannot be approved automatically if any required data is missing or becomes invalid.

Domains are separated by spaces, e.g. \'gmail.com hotmail.com\'. To allow subdomains prefix the domain with a full stop, e.g. \'.example.com\' whitelists test@test.example.com but test@example.com is excluded.

<i>Warning: this filter acts AFTER the common authentication restrictions (allowemailaddresses, denyemailaddresses) are applied.</i>
';
$string['enablerecaptcha'] = 'Enable reCAPTCHA';
$string['enablerecaptcha_desc'] = 'Adds a visual/audio confirmation form element to the sign-up page. This helps protect your site against spammers. Please note that reCAPTCHA needs to be also configured in site settings. See <http://www.google.com/recaptcha> for more details.';
$string['errorapprove'] = 'Error approving account request "{$a}"';
$string['errorapprovebulk'] = 'Error bulk approving {$a} requests. Edit requests and validate them first before trying to approve again';
$string['errornopermissiontoselectmanager'] = 'You do not have the rights (totara/hierarchy:assignuserposition) to select a manager';
$string['errornopermissiontoselectorganisation'] = 'You do not have the rights (totara/hierarchy:assignuserposition) to select an organisation';
$string['errornopermissiontoselectposition'] = 'You do not have the rights (totara/hierarchy:assignuserposition) to select a position';
$string['errordeletedmanager'] = 'Possibly deleted manager';
$string['errordeletedorganisation'] = 'Possibly deleted organisation';
$string['errordeletedposition'] = 'Possibly deleted position';
$string['errormanagerbulk'] = 'Error bulk changing managers for {$a} requests';
$string['errormessage'] = 'Error sending message to {$a}';
$string['errormessagebulk'] = 'Error bulk sending messages to {$a} requests';
$string['errormissingmgr'] = 'Missing manager';
$string['errormissingorg'] = 'Missing organisation';
$string['errormissingpos'] = 'Missing position';
$string['errormissingsecurityquestion'] = 'Missing security question answer';
$string['errororganisationbulk'] = 'Error bulk changing organisations for {$a} requests';
$string['errorpositionbulk'] = 'Error bulk changing positions for {$a} requests';
$string['errorprocessedinterim'] = 'The request from "{$a}" has already been processed';
$string['errorreject'] = 'Error rejecting account request "{$a}"';
$string['errorrejectbulk'] = 'Error bulk rejecting {$a} requests';
$string['errorunknownmanagerjaid'] = 'Cannot process request from "{$a->email}"; no such manager with jaid: {$a->managerjaid}';
$string['errorunknownorganisationid'] = 'Cannot process request from "{$a->email}"; no such organisation with id: {$a->organisationid}';
$string['errorunknownpositionid'] = 'Cannot process request from "{$a->email}"; no such position with id: {$a->positionid}';
$string['errorvalidation'] = 'Request from "{$a}" cannot be processed because it has validation errors';
$string['eventrequestadded'] = 'User added new account request';
$string['eventrequestapproved'] = 'Account request was approved';
$string['eventrequestconfirmed'] = 'Account request email was confirmed';
$string['eventrequestrejected'] = 'Account request was rejected';
$string['instructions'] = 'Sign up request form instructions';
$string['instructions_desc'] = 'This text will be displayed at the top of the sign up form. No default text is provided because the process depends on combination of settings on this page.';
$string['loginsteps'] = '
For full access to courses you\'ll need to take a minute to create a new
account for yourself on this web site. Each of the individual courses may also
have a one-time "enrolment key", which you won\'t need until later.
<br/><br/>
Click on the button below to start the registration process.
';
$string['managereitherselectionorfreeformrequired'] = 'You must provide either a manager or free text manager';
$string['managerfreetext'] = 'Manager free text';
$string['managerorganisationframeworks'] = 'Manager organisation frameworks';
$string['managerorganisationframeworks_desc'] = 'This setting allows you to specify which organisations are used to find available managers.';
$string['managerpositionframeworks'] = 'Manager position frameworks';
$string['managerpositionframeworks_desc'] = 'This setting allows you to specify which positions are used to find available managers.';
$string['managerselect'] = 'Select a manager';
$string['message'] = 'Send message';
$string['messagebody'] = 'Message body';
$string['messageprovider:autoapproved_request'] = 'Automatic request approval notification';
$string['messageprovider:confirmed_request'] = 'Confirmed request awaiting approval notification';
$string['messageprovider:unconfirmed_request'] = 'New unconfirmed request notification';
$string['messagesure'] = 'Send email to {$a->email}?';
$string['messagesubject'] = 'Message subject';
$string['noframeworkrestriction'] = 'All {$a} frameworks';
$string['nomanagerselected'] = 'No manager selected';
$string['notificationautoapprovedrequest'] = 'New account with username "{$a->username}" was automatically approved for applicant "{$a->fullname}" with confirmed email address "{$a->email}".

You can go to this web address to see all account requests:

<{$a->link}>
';
$string['notificationautoapprovedrequestsubject'] = 'New account request was approved automatically';
$string['notificationconfirmrequest'] = 'Applicant "{$a->fullname}", who requested an account with username "{$a->username}", has just confirmed their email address "{$a->email}".

Please go to this web address to approve or reject the account request:

<{$a->link}>
';
$string['notificationconfirmrequestsubject'] = 'New account request requires approval';
$string['notificationnewrequest'] = 'Applicant "{$a->fullname}" requested an account with username "{$a->username}"; they were asked to confirm their email address "{$a->email}".

You can go to this web address to see all account requests:

<{$a->link}>
';
$string['notificationnewrequestsubject'] = 'Account request awaits email confirmation';
$string['organisationeitherselectionorfreeformrequired'] = 'You must provide either an organisation or free text organisation';
$string['organisationframeworks'] = 'Available organisation frameworks';
$string['organisationframeworks_desc'] = 'This setting allows you to specify which frameworks are used to find organisations available for selection.';
$string['organisationfreetext'] = 'Organisation free text';
$string['organisationselect'] = 'Select an organisation';
$string['plugindisabled'] = 'Self-registration plugin is disabled';
$string['pluginname'] = 'Self-registration with approval';
$string['positioneitherselectionorfreeformrequired'] = 'You must provide either a position or free text position';
$string['positionframeworks'] = 'Available position frameworks';
$string['positionframeworks_desc'] = 'This setting allows you to specify which frameworks are used to find positions available for selection.';
$string['positionfreetext'] = 'Position free text';
$string['positionselect'] = 'Select a position';
$string['profilefields'] = 'Additional request data';
$string['reject'] = 'Reject';
$string['rejectsure'] = 'Are you sure you want to reject this request?';
$string['reportdetails'] = 'Self-registration request details';
$string['reportrequests'] = 'Self-registration requests';
$string['reportpending'] = 'Pending requests';
$string['requestapprovedsubject'] = '{$a->sitename}: Account request approved';
$string['requestapprovedbody'] = 'Dear {$a->firstname} {$a->lastname},

A new account has been created at \'{$a->sitename}\' as requested.

You may login via the following link

<{$a->link}>

Your username is: {$a->username}

{$a->custommessage}

If you need help, please contact support at this address: {$a->support}
';
$string['requestemailexists'] = 'Pending request with the same email address already exists';
$string['requestrejectedsubject'] = '{$a->sitename}: Account request rejected';
$string['requestrejectedbody'] = 'Dear {$a->firstname} {$a->lastname},

Unfortunately your account request at \'{$a->sitename}\'
was rejected.

{$a->custommessage}

If you need help, please contact support at this address: {$a->support}
';
$string['requeststatus'] = 'Status';
$string['requeststatusapproved'] = 'Approved';
$string['requeststatuspending'] = 'Pending';
$string['requeststatusrejected'] = 'Rejected';
$string['requesttimecreated'] = 'Time requested';
$string['requesttimemodified'] = 'Time modified';
$string['requesttimeresolved'] = 'Time resolved';
$string['requestusernameexists'] = 'Pending request with the same username already exists';
$string['requireapproval'] = 'Require approval';
$string['requireapproval_desc'] = 'If enabled all requests for new accounts require manual approval. If disabled new accounts are approved automatically after email address confirmation.

*Warning: if disabled spammers may easily create large number of new accounts on this server.*';
$string['requiremanager'] = 'Require manager selection';
$string['requiremanager_desc'] = 'If enabled approval requires selection of manager. If users are not allowed to make a selection in the sign up form then approver needs to select a manager before approval.';
$string['requireorganisation'] = 'Require organisation selection';
$string['requireorganisation_desc'] = 'If enabled approval requires selection of organisation. If users are not allowed to make a selection in the sign up form then approver needs to select an organisation before approval.';
$string['requireposition'] = 'Require position selection';
$string['requireposition_desc'] = 'If enabled approval requires selection of position. If users are not allowed to make a selection in the sign up form then approver needs to select a position before approval.';
$string['successapprove'] = 'Account request "{$a}" was approved';
$string['successapprovebulk'] = 'Bulk approved {$a} requests';
$string['successmanagerbulk'] = 'Bulk set managers for {$a} requests';
$string['successmessage'] = 'Sent message to "{$a}"';
$string['successmessagebulk'] = 'Bulk sent messages to {$a} requests';
$string['successorganisationbulk'] = 'Bulk set organisations for {$a} requests';
$string['successpositionbulk'] = 'Bulk set positions for {$a} requests';
$string['successreject'] = 'Account request "{$a}" was rejected';
$string['successrejectbulk'] = 'Bulk rejected {$a} requests';
$string['searchformanager'] = 'Search';
$string['userdataitemapproval_request'] = 'Approval requests';
$string['userdataitemapproval_request_snapshot'] = 'Approval request snapshots';

