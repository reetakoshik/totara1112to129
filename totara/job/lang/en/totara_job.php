<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_job
 */

$string['addjobassignment'] = 'Add job assignment';
$string['allowsignuporganisation'] = 'Organisation';
$string['allowsignuporganisationsecurityrisk'] =  'Security risk: Please be aware that while this option is enabled, information about organisations will be public.';
$string['allowsignuporganisation_help'] = 'When this option is selected and **Self-registration** is enabled,
the organisation field will be available for users in the sign-up.';
$string['allowsignupposition'] = 'Position';
$string['allowsignuppositionfields'] = 'Allow job assignment fields';
$string['allowsignuppositionsecurityrisk'] =  'Security risk: Please be aware that while this option is enabled, information about positions will be public.';
$string['allowsignupposition_help'] = 'When this option is selected and **Self-registration** is enabled,
the position field will be available for users in the sign-up.';
$string['allowsignupmanager'] = 'Manager';
$string['allowsignupmanagersecurityrisk'] =  'Security risk: Please be aware that while this option is enabled, information about users and their job assignments will be public.';
$string['allowsignupmanager_help'] = 'When this option is selected and **Self-registration** is enabled,
the manager field will be available for users in the sign-up.';
$string['appraiser'] = 'Appraiser';
$string['chooseappraiser'] = 'Choose appraiser';
$string['chooseappraiser_help'] = 'Click **Choose appraiser** to select the user\'s appraiser.';
$string['choosemanager'] = 'Choose manager';
$string['choosemanager_help'] = 'Click **Choose manager** to select the user\'s manager.';
$string['chooseorganisation'] = 'Choose organisation';
$string['chooseorganisation_help'] = 'Click **Choose organisation** to select where the user works in the organisation. This will be useful for reporting purposes.';
$string['chooseposition'] = 'Choose position';
$string['chooseposition_help'] = 'Click **Choose position** to select the correct position (job role) for the user. This is useful for reporting purposes.';
$string['choosetempmanager'] = 'Choose temporary manager';
$string['choosetempmanager_help'] = 'A temporary manager can be assigned. The assigned Temporary Manager will have the same rights as a normal manager, for the specified amount of time.

Click **Choose temporary manager** to select a temporary manager.

If the name you are looking for does not appear in the list, it might be that the user does not have the necessary rights to act as a temporary manager.';
$string['confirmdeletejobassignment'] = '<strong>Permanently delete the "{$a}" job assignment?</strong>';
$string['currentlyselected'] = 'Currently selected';
$string['deletejobassignment'] = 'Delete job assignment';
$string['deletexjobassignment'] = 'Delete {$a} job assignment';
$string['dialogmanageraddemptyjob'] = '{$a->fullname} - create empty job assignment';
$string['dialogmanagercreateemptyjob'] = 'Create empty job assignment';
$string['dialogmanagerjob'] = '{$a->fullname} - {$a->job}';
$string['dialogmanagerneedsjobentry'] = '{$a->fullname} - requires job assignment entry';
$string['dialogmanageremail'] = '{$a->fullname} ({$a->email})';
$string['dialogmanageremailaddemptyjob'] = '{$a->fullname} ({$a->email}) - create empty job assignment';
$string['dialogmanageremailjob'] = '{$a->fullname} ({$a->email}) - {$a->job}';
$string['dialogmanageremailneedsjobentry'] = '{$a->fullname} ({$a->email}) - requires job assignment entry';
$string['editjobassignment'] = 'Edit this job assignment';
$string['enabletempmanagers'] = 'Enable temporary managers';
$string['enabletempmanagersdesc'] = 'Enable functionality that allows for assigning a temporary manager to a user. Disabling this will cause all current temporary managers to be unassigned on next cron run.';
$string['error:appraisernotselected'] = 'Please select an appraiser';
$string['error:datenotinfuture'] = 'The date needs to be in the future';
$string['error:jobassignmentidnumberunique'] = 'Job assignment ID Number must be unique for each user - a job with this ID Number already exists for this user';
$string['error:jobcircular'] = 'Selecting this job assignment will create a circular management structure. Please select another.';
$string['error:managerdeleted'] = 'The manager "{$a->username}" has been deleted from the system, please select another manager.';
$string['error:managerhasjobassignment'] = 'Please select the managers existing job assignment';
$string['error:managernotselected'] = 'Please select a manager';
$string['error:onlyonejobassignmentallowed'] = 'Only one job assignment per user is allowed - the specified user already has a job assignment';
$string['error:organisationnotselected'] = 'Please select an organisation';
$string['error:positionnotselected'] = 'Please select a position';
$string['error:positionvalidationfailed'] = 'The problems indicated below must be fixed before your changes can be saved.';
$string['error:startafterfinish'] = 'Start date must not be later than finish date';
$string['error:tempmanagerexpirynotset'] = 'An expiry date for the temporary manager needs to be set';
$string['error:tempmanagerhasjobassignment'] = 'Please select the temporary managers existing job assignment';
$string['error:tempmanagernotselected'] = 'Please select a temporary manager';
$string['error:userownmanager'] = 'A user cannot be assigned as their own manager';
$string['eventjobassignmentupdated'] = 'User job assignment updated';
$string['eventjobassignmentviewed'] = 'User job assignment viewed';
$string['globalsettings'] = 'Global Settings';
$string['job:managejobs'] = 'Manager jobs';
$string['jobassignment'] = 'Job assignment';
$string['jobassignmentadd'] = 'Add job assignment';
$string['jobassignmentdefaultfullname'] = 'Unnamed job assignment (ID: {$a})';
$string['jobassignmentenddate'] = 'End date';
$string['jobassignmentenddate_help'] = 'Date that the user ends in this job assignment. This date can be used in dynamic audience rules. However, this date is NOT used to determine if the job assignment is **active** in any other part of Totara.';
$string['jobassignmentfullname'] = 'Full name';
$string['jobassignmentfullname_help'] = 'The full name of the job assignment. Used when job assignment is displayed and for selecting job assignments in dialogs.';
$string['jobassignmentidnumber'] = 'ID Number';
$string['jobassignmentidnumber_help'] = 'Used when syncing job assignment data from external sources. Must be unique for each user (but two users could have the same job assignment ID Number).';
$string['jobassignments'] = 'Job assignments';
$string['jobassignmentsaved'] = 'Job assignment saved';
$string['jobassignmentshortname'] = 'Short name';
$string['jobassignmentshortname_help'] = 'Only used as additional information on this page.';
$string['jobassignmentstartdate'] = 'Start date';
$string['jobassignmentstartdate_help'] = 'Date that the user started in this job assignment. This date can be used in dynamic audience rules. However, this date is NOT used to determine if the job assignment is **active** in any other part of Totara.';
$string['jobmanagement'] = 'Job Management';
$string['manager'] = 'Manager';
$string['managernomatchja'] = 'Make sure you are selecting a job assignment linked to your manager. If that is what you were doing, delete your manager selection and try selecting it again. Or have your manager assigned after sign-up.';
$string['movedownxjobassignment'] = 'Move down {$a} job assignment';
$string['moveupxjobassignment'] = 'Move up {$a} job assignment';
$string['nopositionsassigned'] = 'No positions currently assigned to this user';
$string['nojobassignments'] = 'This user has no job assignments';
$string['organisation'] = 'Organisation';
$string['pluginname'] = 'Totara job assignments';
$string['position'] = 'Position';
$string['selected'] = 'Selected';
$string['setting:allowautodefault'] = 'Allow automatic creation of default job assignments';
$string['setting:allowautodefault_description'] = 'When performing tasks where a user must have a job assignment, allow automatic creation of a default job assignment if none exists already.';
$string['setting:allowmultiplejobs'] = 'Allow multiple job assignments';
$string['setting:allowmultiplejobs_description'] = 'Allows users to have more than one job assignment.';
$string['tempmanager'] = 'Temporary manager';
$string['tempmanagerassignmsgmgr'] = '{$a->tempmanager} has been assigned as temporary manager to {$a->staffmember} (one of your team members).<br>Temporary manager expiry: {$a->expirytime}.';
$string['tempmanagerassignmsgmgrsubject'] = '{$a->tempmanager} is now temporary manager for {$a->staffmember}';
$string['tempmanagerassignmsgstaff'] = '{$a->tempmanager} has been assigned as temporary manager to you.<br>Temporary manager expiry: {$a->expirytime}.';
$string['tempmanagerassignmsgstaffsubject'] = '{$a->tempmanager} is now your temporary manager';
$string['tempmanagerassignmsgtmpmgr'] = 'You have been assigned as temporary manager to {$a->staffmember}.<br>Temporary manager expiry: {$a->expirytime}.';
$string['tempmanagerassignmsgtmpmgrsubject'] = 'You are now {$a->staffmember}\'s temporary manager';
$string['tempmanagerexpirydate'] = 'Temporary manager expiry date';
$string['tempmanagerexpirydate_help'] = 'Click the calendar icon to select the date the temporary manager will expire.';
$string['tempmanagerexpirydays'] = 'Temporary manager expiry days';
$string['tempmanagerexpirydaysdesc'] = 'Set a default temporary manager expiry period (in days).';
$string['tempmanagerexpiryupdatemsgmgr'] = 'The expiry date for {$a->staffmember}\'s temporary manager ({$a->tempmanager}) has been updated to {$a->expirytime}.';
$string['tempmanagerexpiryupdatemsgmgrsubject'] = 'Expiry date updated for {$a->staffmember}\'s temporary manager';
$string['tempmanagerexpiryupdatemsgstaff'] = 'The expiry date for {$a->tempmanager} (your temporary manager) has been updated to {$a->expirytime}.';
$string['tempmanagerexpiryupdatemsgstaffsubject'] = 'Expiry date updated for your temporary manager';
$string['tempmanagerexpiryupdatemsgtmpmgr'] = 'Your expiry date as temporary manager for {$a->staffmember} has been updated to {$a->expirytime}.';
$string['tempmanagerexpiryupdatemsgtmpmgrsubject'] = 'Temporary manager expiry updated for {$a->staffmember}';
$string['tempmanagerrestrictselection'] = 'Temporary manager selection';
$string['tempmanagerrestrictselectiondesc'] = 'Determine which users will be available in the temporary manager selection dialog. Selecting \'Only staff managers\' will remove any assigned temporary managers who don\'t have the \'staff manager\' role on the next cron run.';
$string['tempmanagers'] = 'Temporary managers';
$string['tempmanagerselectionallusers'] = 'All users';
$string['tempmanagerselectiononlymanagers'] = 'Only staff managers';
$string['tempmanagersupporttext'] = ' Note, only current team managers can be selected.';
$string['updatejobassignment'] = 'Update job assignment';
$string['updatetemporarymanagerstask'] = 'Update temporary managers';
$string['userdataitemappraiser_assignments'] = 'Assignments as appraiser';
$string['userdataitemjob_assignments'] = 'Job assignments';
$string['warningallstafftypeassigned'] = ' {$a->countstaffassigned} will lose their assigned manager and {$a->counttempstaffassigned} will lose their temporary manager.';
$string['warningstaffaffectednote'] = 'This may result in users being left without a manager in the system.';
$string['warningstaffassigned'] = ' {$a} will lose their assigned manager.';
$string['warningtempstaffassigned'] = ' {$a} will lose their temporary manager.';
$string['xpositions'] = '{$a}\'s Positions';
