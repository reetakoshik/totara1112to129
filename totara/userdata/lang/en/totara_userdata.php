<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

$string['actions'] = 'Actions';
$string['activeuser'] = 'Active user';
$string['applychanges'] = 'Apply changes';
$string['audit'] = 'Data audit';
$string['audit_desc'] = 'View a count of how much data is contained in the system for this user.';
$string['auditexecute'] = 'Audit user data';
$string['audititemsprocessed'] = 'Items processed';
$string['audititemserror'] = 'Items returning errors';
$string['audititemsnonemtpy'] = 'Items containing data';
$string['auditsummary'] = 'Audit summary';
$string['audittotalcount'] = 'Total data count';
$string['bulkconfirmtypesetting'] = 'Confirm purge type setting';
$string['bulkoncesuspended'] = 'Once a selected user is suspended, the following data will be deleted.';
$string['bulkoncedeleted'] = 'Once a selected user is deleted, the following data will be deleted.';
$string['bulksettingdetails'] = 'Details';
$string['bulksuspendedalready'] = 'Selected users who are currently suspended will not have the default purge type applied to them.';
$string['createdby'] = 'Created by';
$string['defaultsuspendedpurgetype'] = 'Default purging type for suspended users';
$string['defaultsuspendedpurgetype_desc'] = 'Select a purge type that will be set and applied when a user is suspended, but has no automatic suspended purge type set already.';
$string['defaultsuspendedpurgetypeerror'] = 'This type is used in setting for default suspension action';
$string['defaultdeletedpurgetype'] = 'Default purging type for deleted users';
$string['defaultdeletedpurgetype_desc'] = 'Select a purge type that will be set and applied when a user is deleted, but has no automatic deleted purge type set already.';
$string['defaultdeletedpurgetypeerror'] = 'This type is used in setting for default deletion action';
$string['deletedpurgetype'] = 'Deleted purge type';
$string['deleteduser'] = 'Deleted user';
$string['errorduplicateidnumber'] = 'Same ID number already exists';
$string['errorexporttypedelete'] = 'Purge type cannot be deleted';
$string['errornoexporttypes'] = 'There is no export type suitable for export of own data at the moment.';
$string['errorpurgecancel'] = 'Error cancelling purge';
$string['errorpurgetypedelete'] = 'Purge type cannot be deleted';
$string['eventexportdownloaded'] = 'User data export file downloaded';
$string['export'] = 'User data export';
$string['exportfiledeleted'] = 'Export file was deleted';
$string['exportfiledownload'] = 'Download data export file';
$string['exportfileready'] = 'Your data export file is available for download: {$a->file}

The export file will be available until {$a->until}, after which it will be removed. If you would like to request another export before then, you will need to delete the available export file first.';
$string['exportincludefiledir'] = 'Include files';
$string['exportincludefiledir_help'] = 'Including files in the export will increase the time it takes to process the export and the size of the export file to be downloaded. It may also impact site performance while the task is in progress.';
$string['exportrequest'] = 'Request data export';
$string['exportrequestpending'] = 'Data export in progress. You will receive a notification once the file is available for download.';
$string['exports'] = 'Exports';
$string['exportscount'] = 'Number of exports';
$string['exportsuserall'] = 'Data export requests';
$string['exportitemselection'] = 'Data to be exported';
$string['exportitemselection_desc'] = 'Select items below to specify which user data will be exported when this export type is applied.';
$string['exportitemselectionfilewarning'] = 'Please be aware that individual items selected for export could contain "hidden" data: data that is about the user, but which they themselves don\'t ordinarily have permission to see in the interface. Our {$a} provides greater detail about data visibility when exporting to help you decide whether an item is suitable for export. We have also drawn attention to it in the help text on individual items below, where visibility of data may be a concern.';
$string['exportitemselectionfilewarninglinklabel'] = 'help documentation';
$string['exportorigin'] = 'Origin';
$string['exportoriginself'] = 'User exporting own data';
$string['exportoriginother'] = 'Other';
$string['exporttype'] = 'Export type';
$string['exporttypeadd'] = 'Add export type';
$string['exporttypeavailablefor'] = 'Permitted use';
$string['exporttypeavailablefor_help'] = 'If deselected pending exports are cancelled and access to previously created export files is rejected.';
$string['exporttypecopyof'] = 'Copy of {$a->fullname}';
$string['exporttypedelete'] = 'Delete export type';
$string['exporttypedeleteconfirm'] = 'Are you sure you want to delete export type "{$a}"?';
$string['exporttypes'] = 'Export types';
$string['exporttypeupdate'] = 'Update export type';
$string['exporttypewhenitemsapplied'] = 'The following data will be exported when this export type is applied.';
$string['fullname'] = 'Full name';
$string['fullnamelink'] = 'Full name (with link)';
$string['incontextid'] = 'Context';
$string['itemcomponent'] = 'Item component';
$string['itemexportdata'] = 'Export data';
$string['itemfullname'] = 'Item';
$string['itemgroup'] = 'Item group';
$string['itemname'] = 'Internal item name';
$string['itempurgedata'] = 'Purge data';
$string['messageprovider:purge_manual_finished'] = 'Manual user data purge finished';
$string['messageprovider:export_self_finished'] = 'Export of own user data finished';
$string['newitem'] = 'New';
$string['newitems'] = 'New items';
$string['noadditionaldatadeleted'] = 'No additional data will be deleted.';
$string['notificationexportselfsubject'] = 'User data export completed';
$string['notificationexportselfmessage'] = 'Your requested data export file is ready for download. It will be available until {$a}, after which it will be removed.';
$string['notificationexportselfmessage_unsuccessful'] = 'The system encountered a problem while processing your data export request. Please contact your administrator for assistance.';
$string['notificationpurgemanualsubject'] = 'Manual purge of user data completed';
$string['notificationpurgemanualmessage'] = 'Manual purge of user {$a->fullnameuser} data was completed: {$a->result}';
$string['pluginname'] = 'User data management';
$string['purgeautocompleted'] = '(purged {$a->timefinished})';
$string['purgeautodefault'] = 'None (Site default: {$a})';
$string['purgeautopending'] = '(pending)';
$string['purgeautomatic'] = 'Automatic data purge';
$string['purgecancelled'] = 'Purge was cancelled';
$string['purgeispending'] = 'This data purge is already scheduled for execution';
$string['purgeitemselection'] = 'Data to be purged';
$string['purgeitemselectiondeleted'] = 'Data to be purged once user is deleted';
$string['purgeitemselection_desc'] = 'Select items below to specify which user data will be deleted when this purge type is applied.';
$string['purgeitemselectionsuspended'] = 'Data to be purged once user is suspended';
$string['purgemanually'] = 'Purge user data';
$string['purgemanuallyareyousure'] = 'Are you sure you would like to delete this data?';
$string['purgemanuallyconfirm'] = 'Confirm manual data purge';
$string['purgemanuallyfollowingwillbe'] = 'The following data will be deleted. This cannot be undone.';
$string['purgemanuallyproceed'] = 'Proceed with purge';
$string['purgemanuallytriggered'] = 'An ad hoc task for manual user data purging was created. You will receive a notification once it has completed successfully.';
$string['purgemanualschedule_desc'] = 'Create an ad hoc task that will delete user data immediately. The specific data deleted will be determined by the purge type you select.';
$string['purgeorigin'] = 'Origin';
$string['purgeorigindeleted'] = 'Automatic purging once user is deleted';
$string['purgeorigindeletedbulkselect'] = 'Select the purge type that will be applied automatically once these users are deleted';
$string['purgeoriginmanual'] = 'Manual data purging';
$string['purgeoriginother'] = 'Other';
$string['purgeoriginsuspended'] = 'Automatic purging once user is suspended';
$string['purgeoriginsuspendedbulkselect'] = 'Select the purge type that will be applied automatically once these users are suspended';
$string['purges'] = 'Purges';
$string['purgescount'] = 'Number of purges';
$string['purgesetautomatic'] = 'Set automatic data purge type';
$string['purgesuserall'] = 'All data purges';
$string['purgesuserpending'] = 'Pending purges';
$string['purgetype'] = 'Purge type';
$string['purgetypeadd'] = 'Add purge type';
$string['purgetypeavailablefor'] = 'Available use';
$string['purgetypeavailablefor_help'] = 'If no options are selected, the purge type will not be available for use. This can be edited at any time. Deselecting a previously selected option makes it unavailable for future application only. Where already assigned to users, it will remain, and any pending manual purges using this type will be completed.

Once purge type is created, the number of users who have this type assigned for automatic purging is indicated in brackets.';
$string['purgetypecopyof'] = 'Copy of {$a->fullname}';
$string['purgetypedelete'] = 'Delete purge type';
$string['purgetypedeleteconfirm'] = 'Are you sure you want to delete purge type "{$a}"?';
$string['purgetypenolongerapply'] = 'Purge type can no longer be set.';
$string['purgetypes'] = 'Purge types';
$string['purgetypeupdate'] = 'Update purge type';
$string['purgetypeuserstatus'] = 'User status restriction';
$string['purgetypeuserstatus_help'] = 'This user data purge type can only be applied to users with the user status selected here. This user status determines what settings will be available on the next step, and therefore can’t be changed later.';
$string['purgetypewhenitemsapplied'] = 'The following data will be deleted when this purge type is applied.';
$string['repurge'] = 'Reapply purging';
$string['repurge_help'] = 'If selected, all user accounts with this purge type setting will be purged (including previously purged accounts).';
$string['repurgewarning'] = 'This purge type will be reapplied to {$a} users. Site performance may be impacted while the purges are being completed.';
$string['result'] = 'Result';
$string['resultsuccess'] = 'Success';
$string['resulterror'] = 'Error';
$string['resultkipped'] = 'Skipped';
$string['resultcancelled'] = 'Cancelled';
$string['resulttimedout'] = 'Timed out';
$string['selectedusers'] = 'Selected users';
$string['selectpurgetype'] = 'Select purge type';
$string['selfexportenable'] = 'Allow users to export their own data';
$string['selfexportenable_desc'] = 'To allow users to export their own user data, this setting needs to be enabled, and at least one export type created that allows for own data export. A user will also need to have permission “Export own user data”.';
$string['setdeletedpurgetype'] = 'Set deleted purge type';
$string['setpurgetypeconfirm'] = 'Confirm data purge type setting';
$string['setpurgetypeconfirmbulk'] = 'Confirm bulk data purge type setting';
$string['setsuspendedpurgetype'] = 'Set suspended purge type';
$string['settings'] = 'Settings';
$string['showingresultsforuser'] = 'Showing results for User: {$a->fullname} only.';
$string['suspendedpurgetype'] = 'Suspended purge type';
$string['suspendeduser'] = 'Suspended user';
$string['userdata:config'] = 'Configure user data management';
$string['userdata:exportself'] = 'Export own user data';
$string['userdata:purgemanual'] = 'Purge user data manually';
$string['userdata:purgesetdeleted'] = 'Set deleted user purge type';
$string['userdata:purgesetsuspended'] = 'Set suspended user purge type';
$string['userdata:viewexports'] = 'View user data exports';
$string['userdata:viewinfo'] = 'View user data configuration';
$string['userdata:viewpurges'] = 'View user data purges';
$string['userid'] = 'User ID';
$string['userinfo'] = 'User data';
$string['userstatus'] = 'User status';
$string['timecreated'] = 'Created';
$string['taskmisc'] = 'Miscellaneous maintenance tasks';
$string['taskpurgedeleted'] = 'Automatic user data purging of deleted users';
$string['taskpurgesuspended'] = 'Automatic user data purging of suspended users';
$string['timefinished'] = 'Finished';
$string['timechanged'] = 'Changed';
$string['timestarted'] = 'Started';
$string['userdataitemexport_request'] = 'User data export files';
$string['userdataitemexport_request_help'] = 'Logs of user data export requests will not be deleted.';
